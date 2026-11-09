<?php

declare(strict_types=1);

/**
 * Verify cluster package pins (when aliased) and gap guards for Packagist-only org deps.
 */

$root = dirname(__DIR__);
$installedPhpPath = $root . '/vendor/composer/installed.php';
$installedJsonPath = $root . '/vendor/composer/installed.json';

$forbiddenReferences = [
    'phpunit/phpunit' => [
        'a37d66fabf01af0eedabffe271b70dbe71723001',
        '698cbbc1e75a1ef1968f9cbfec89be056b288d0f',
    ],
    'nikic/php-parser' => [
        '50f0d9c9d0e3cff1163c959c50aaaaa4a7115f08',
        'ccb6c31631175a7cdaa9dac93d7dd029eb2ef004',
    ],
];

/** @var list<string> */
$pinPackageNames = ['phpunit/phpunit', 'nikic/php-parser'];

if (!is_readable($installedPhpPath)) {
    fwrite(STDERR, "check-cluster-pins: vendor/composer/installed.php not found; run composer update first.\n");
    exit(1);
}

if (!is_readable($installedJsonPath)) {
    fwrite(STDERR, "check-cluster-pins: vendor/composer/installed.json not found; run composer update first.\n");
    exit(1);
}

/** @var array{versions?: array<string, array<string, mixed>>} $installedPhp */
$installedPhp = require $installedPhpPath;
$versionsPhp = $installedPhp['versions'] ?? [];

$rawJson = file_get_contents($installedJsonPath);
if ($rawJson === false) {
    fwrite(STDERR, "check-cluster-pins: could not read installed.json.\n");
    exit(1);
}

/** @var array{packages?: list<array<string, mixed>>, 'packages-dev'?: list<array<string, mixed>>} $installedJson */
$installedJson = json_decode($rawJson, true, 512, JSON_THROW_ON_ERROR);
$jsonPackages = array_merge($installedJson['packages'] ?? [], $installedJson['packages-dev'] ?? []);

/** @var array<string, array{reference: string, url: string}> $versionsJson */
$versionsJson = [];
foreach ($jsonPackages as $package) {
    if (!isset($package['name'], $package['source']['reference'])) {
        continue;
    }
    $url = isset($package['source']['url']) ? (string) $package['source']['url'] : '';
    $versionsJson[$package['name']] = [
        'reference' => strtolower((string) $package['source']['reference']),
        'url' => $url,
    ];
}

/**
 * @param array<string, array<string, mixed>> $versionsPhp
 * @param array<string, array{reference: string, url: string}> $versionsJson
 * @param list<string> $names
 *
 * @return array<string, array{reference: string, alias?: string}>
 */
function buildLiveExpected(array $versionsPhp, array $versionsJson, array $names): array
{
    $expected = [];

    foreach ($names as $name) {
        if (!isset($versionsPhp[$name], $versionsJson[$name])) {
            continue;
        }

        $refPhp = $versionsPhp[$name]['reference'] ?? null;
        if (!is_string($refPhp) || strlen($refPhp) !== 40) {
            continue;
        }

        $refPhp = strtolower($refPhp);
        $refJson = $versionsJson[$name]['reference'];

        if (!hash_equals($refPhp, $refJson)) {
            fwrite(STDERR, sprintf(
                "check-cluster-pins: live %s reference mismatch between installed.php (%s) and installed.json (%s).\n",
                $name,
                $refPhp,
                $refJson
            ));
            exit(1);
        }

        $entry = ['reference' => $refPhp];
        $aliases = $versionsPhp[$name]['aliases'] ?? [];
        if (is_array($aliases) && $aliases !== []) {
            $entry['alias'] = (string) $aliases[0];
        }

        $expected[$name] = $entry;
    }

    return $expected;
}

/**
 * @param array<string, array<string, mixed>> $versionsPhp
 * @param array<string, array{reference: string, url: string}> $versionsJson
 * @param array<string, array{reference: string, alias?: string}> $expectedMap
 * @param list<string>                    $messages
 */
function runPinChecks(array $versionsPhp, array $versionsJson, array $expectedMap, array &$messages = []): bool
{
    $failed = false;

    foreach ($expectedMap as $name => $want) {
        if (!isset($versionsPhp[$name])) {
            $messages[] = sprintf('check-cluster-pins: %s is not installed (installed.php).', $name);
            $failed = true;
            continue;
        }

        if (!isset($versionsJson[$name])) {
            $messages[] = sprintf('check-cluster-pins: %s is not installed (installed.json).', $name);
            $failed = true;
            continue;
        }

        if (array_key_exists('alias', $want)) {
            $aliases = $versionsPhp[$name]['aliases'] ?? [];
            if (!is_array($aliases) || !in_array($want['alias'], $aliases, true)) {
                $messages[] = sprintf(
                    'check-cluster-pins: %s alias mismatch (expected %s in aliases %s).',
                    $name,
                    $want['alias'],
                    json_encode($aliases)
                );
                $failed = true;
            }
        }

        $refPhp = $versionsPhp[$name]['reference'] ?? null;
        if (!is_string($refPhp) || strlen($refPhp) !== 40) {
            $messages[] = sprintf('check-cluster-pins: %s has no 40-char reference in installed.php.', $name);
            $failed = true;
        } else {
            $refPhp = strtolower($refPhp);
            if (!hash_equals(strtolower($want['reference']), $refPhp)) {
                $messages[] = sprintf(
                    'check-cluster-pins: %s reference mismatch in installed.php (expected %s, got %s).',
                    $name,
                    $want['reference'],
                    $refPhp
                );
                $failed = true;
            }
        }

        $refJson = $versionsJson[$name]['reference'];
        if (!hash_equals(strtolower($want['reference']), $refJson)) {
            $messages[] = sprintf(
                'check-cluster-pins: %s reference mismatch in installed.json (expected %s, got %s).',
                $name,
                $want['reference'],
                $refJson
            );
            $failed = true;
        }

        if (is_string($refPhp) && $refPhp !== $refJson) {
            $messages[] = sprintf(
                'check-cluster-pins: %s reference mismatch between installed.php (%s) and installed.json (%s).',
                $name,
                $refPhp,
                $refJson
            );
            $failed = true;
        }
    }

    return !$failed;
}

/**
 * @param array<string, array<string, mixed>> $versionsPhp
 * @param array<string, array{reference: string, url: string}> $versionsJson
 * @param array<string, list<string>> $forbiddenReferences
 */
function runGapGuards(array $versionsPhp, array $versionsJson, array $forbiddenReferences): bool
{
    $failed = false;

    $gapRules = [
        'phpunit/phpunit' => [
            'url_needle' => 'sebastianbergmann/phpunit',
            'url_forbid' => 'Training-Datasmith/phpunit',
        ],
        'nikic/php-parser' => [
            'url_needle' => 'nikic/PHP-Parser',
            'url_forbid' => 'Training-Datasmith',
        ],
    ];

    foreach ($gapRules as $name => $rule) {
        if (!isset($versionsPhp[$name]) || !isset($versionsJson[$name])) {
            fwrite(STDERR, sprintf("check-cluster-pins: gap guard failed: %s is not installed.\n", $name));
            $failed = true;
            continue;
        }

        $refPhp = $versionsPhp[$name]['reference'] ?? '';
        $refJson = $versionsJson[$name]['reference'] ?? '';
        if (!is_string($refPhp) || strlen($refPhp) !== 40) {
            fwrite(STDERR, sprintf("check-cluster-pins: gap guard failed: %s has no installed.php reference.\n", $name));
            $failed = true;
        } else {
            $refPhp = strtolower($refPhp);
        }

        if ($refJson === '' || strlen($refJson) !== 40) {
            fwrite(STDERR, sprintf("check-cluster-pins: gap guard failed: %s has no installed.json reference.\n", $name));
            $failed = true;
        }

        if (is_string($refPhp) && $refPhp !== $refJson) {
            fwrite(STDERR, sprintf(
                "check-cluster-pins: gap guard failed: %s reference differs between install files (%s vs %s).\n",
                $name,
                $refPhp,
                $refJson
            ));
            $failed = true;
        }

        $url = $versionsJson[$name]['url'];
        if (strpos($url, $rule['url_needle']) === false) {
            fwrite(STDERR, sprintf(
                "check-cluster-pins: gap guard failed: %s must come from Packagist (%s), got url %s.\n",
                $name,
                $rule['url_needle'],
                $url
            ));
            $failed = true;
        }

        if (strpos($url, $rule['url_forbid']) !== false) {
            fwrite(STDERR, sprintf(
                "check-cluster-pins: gap guard failed: %s must not come from org fork (url %s).\n",
                $name,
                $url
            ));
            $failed = true;
        }

        foreach ($forbiddenReferences[$name] ?? [] as $forbidden) {
            $forbidden = strtolower($forbidden);
            if (is_string($refPhp) && hash_equals($forbidden, $refPhp)) {
                fwrite(STDERR, sprintf(
                    "check-cluster-pins: gap guard failed: %s is pinned to forbidden org SHA %s.\n",
                    $name,
                    $forbidden
                ));
                $failed = true;
            }
        }
    }

    return !$failed;
}

$liveExpected = buildLiveExpected($versionsPhp, $versionsJson, $pinPackageNames);

$liveMessages = [];
if (!runPinChecks($versionsPhp, $versionsJson, $liveExpected, $liveMessages)) {
    foreach ($liveMessages as $message) {
        fwrite(STDERR, $message . "\n");
    }
    exit(1);
}

if (!runGapGuards($versionsPhp, $versionsJson, $forbiddenReferences)) {
    exit(1);
}

if (!isset($versionsPhp['phpunit/phpunit']['reference'], $versionsJson['phpunit/phpunit']['reference'])) {
    fwrite(STDERR, "check-cluster-pins: cannot run wrong-SHA demo without phpunit/phpunit.\n");
    exit(1);
}

$liveRef = strtolower((string) $versionsPhp['phpunit/phpunit']['reference']);
if (strlen($liveRef) !== 40 || $liveRef !== $versionsJson['phpunit/phpunit']['reference']) {
    fwrite(STDERR, "check-cluster-pins: phpunit/phpunit references must agree before wrong-SHA demo.\n");
    exit(1);
}

$wrongSha = '0000000000000000000000000000000000000000';
$phpunitExpected = [
    'phpunit/phpunit' => $liveExpected['phpunit/phpunit'] ?? ['reference' => $liveRef],
];

$wrongPhp = $versionsPhp;
$wrongPhp['phpunit/phpunit']['reference'] = $wrongSha;

$phpDemoMessages = [];
$phpDemoPassed = runPinChecks($wrongPhp, $versionsJson, $phpunitExpected, $phpDemoMessages);

if ($phpDemoPassed) {
    fwrite(STDERR, "check-cluster-pins: wrong-SHA installed.php demo did not fail as expected.\n");
    exit(1);
}
$phpDemoBlob = implode("\n", $phpDemoMessages);
if (strpos($phpDemoBlob, 'reference mismatch in installed.php') === false) {
    fwrite(STDERR, "check-cluster-pins: wrong-SHA installed.php demo did not fail on installed.php side.\n");
    exit(1);
}
if (strpos($phpDemoBlob, 'reference mismatch in installed.json') !== false) {
    fwrite(STDERR, "check-cluster-pins: wrong-SHA installed.php demo unexpectedly failed on installed.json side.\n");
    exit(1);
}
fwrite(STDOUT, "check-cluster-pins: wrong-SHA installed.php demo failed as expected\n");

$wrongJson = $versionsJson;
$wrongJson['phpunit/phpunit']['reference'] = $wrongSha;

$jsonDemoMessages = [];
$jsonDemoPassed = runPinChecks($versionsPhp, $wrongJson, $phpunitExpected, $jsonDemoMessages);

if ($jsonDemoPassed) {
    fwrite(STDERR, "check-cluster-pins: wrong-SHA installed.json demo did not fail as expected.\n");
    exit(1);
}
$jsonDemoBlob = implode("\n", $jsonDemoMessages);
if (strpos($jsonDemoBlob, 'reference mismatch in installed.json') === false) {
    fwrite(STDERR, "check-cluster-pins: wrong-SHA installed.json demo did not fail on installed.json side.\n");
    exit(1);
}
if (strpos($jsonDemoBlob, 'reference mismatch in installed.php') !== false) {
    fwrite(STDERR, "check-cluster-pins: wrong-SHA installed.json demo unexpectedly failed on installed.php side.\n");
    exit(1);
}
fwrite(STDOUT, "check-cluster-pins: wrong-SHA installed.json demo failed as expected\n");

fwrite(STDOUT, sprintf("gap phpunit/phpunit on Packagist @ %s\n", $liveRef));
$parserRef = $versionsJson['nikic/php-parser']['reference'] ?? '(missing)';
fwrite(STDOUT, sprintf("gap nikic/php-parser on Packagist @ %s\n", $parserRef));
fwrite(STDOUT, "check-cluster-pins: all checks passed.\n");

exit(0);
