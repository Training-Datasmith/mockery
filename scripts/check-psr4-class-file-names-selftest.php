<?php

declare(strict_types=1);

/**
 * In-memory / temp-dir regression tests for the PSR-4 class/file checker.
 */

$root = dirname(__DIR__);
require $root . '/scripts/lib/Psr4_Class_File_Name_Checker.php';

/**
 * @return never
 */
function selftest_fail(string $message): void
{
    fwrite(STDERR, 'check-psr4-class-file-names-selftest: ' . $message . "\n");
    exit(1);
}

function selftest_ok(string $message): void
{
    fwrite(STDERR, 'check-psr4-class-file-names-selftest: ' . $message . "\n");
}

$checker = new Psr4_Class_File_Name_Checker($root);

$nestedGlobal = <<<'PHP'
<?php
namespace {
    abstract class Hidden_In_Namespace_Brace
    {
    }
}
PHP;

$ifBlock = <<<'PHP'
<?php
namespace Tests\SelfTest;
if (true) {
    class Declared_Inside_If
    {
    }
}
PHP;

$readonly = <<<'PHP'
<?php
namespace PHP84;
readonly class Readonly_Mismatch
{
}
PHP;

$fixture = <<<'PHP'
<?php
namespace PHP73;
final class Fixture_Mismatch
{
}
PHP;

$inMemoryCases = [
    [
        'label' => 'namespace brace block',
        'file' => 'tests/Unit/Wrong_File_Name.php',
        'code' => $nestedGlobal,
        'declared' => 'Hidden_In_Namespace_Brace',
    ],
    [
        'label' => 'if block',
        'file' => 'tests/Unit/If_Block_Wrapper.php',
        'code' => $ifBlock,
        'declared' => 'Declared_Inside_If',
    ],
    [
        'label' => 'readonly class',
        'file' => 'tests/Unit/Readonly_Wrapper.php',
        'code' => $readonly,
        'declared' => 'Readonly_Mismatch',
    ],
    [
        'label' => 'tests/Fixture',
        'file' => 'tests/Fixture/PHP73/Fixture_Wrapper.php',
        'code' => $fixture,
        'declared' => 'Fixture_Mismatch',
    ],
];

foreach ($inMemoryCases as $case) {
    $decls = $checker->collectDeclarations($case['code']);
    $names = array_column($decls, 'name');
    if (!in_array($case['declared'], $names, true)) {
        selftest_fail(sprintf('%s did not extract %s', $case['label'], $case['declared']));
    }
    $stem = pathinfo($case['file'], PATHINFO_FILENAME);
    if ($case['declared'] === $stem) {
        selftest_fail(sprintf('%s should be a deliberate mismatch', $case['label']));
    }
}

$tmpdir = sys_get_temp_dir() . '/mockery-psr4-selftest-' . getmypid() . '-' . bin2hex(random_bytes(4));
if (!mkdir($tmpdir, 0700, true) && !is_dir($tmpdir)) {
    selftest_fail('could not create temp directory');
}

$cleanup = static function () use ($tmpdir): void {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tmpdir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $file) {
        if ($file->isDir()) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }
    rmdir($tmpdir);
};

try {
    mkdir($tmpdir . '/scripts', 0700, true);
    file_put_contents($tmpdir . '/scripts/psr4-class-file-name-allowlist.txt', "# empty\n");

    foreach ($inMemoryCases as $case) {
        $full = $tmpdir . '/' . $case['file'];
        $parent = dirname($full);
        if (!is_dir($parent) && !mkdir($parent, 0777, true) && !is_dir($parent)) {
            selftest_fail('mkdir failed for ' . $case['file']);
        }
        file_put_contents($full, $case['code']);
    }

    $tempChecker = new Psr4_Class_File_Name_Checker($tmpdir);
    $tempCheckerLib = $root . '/scripts/lib/Psr4_Class_File_Name_Checker.php';
    // temp checker uses same class from require above; only scans library+tests under tmpdir
    $mismatches = $tempChecker->findMismatches();
    $foundLabels = [];
    foreach ($mismatches as $row) {
        if (strncmp($row['name'], '(stale', 7) === 0) {
            selftest_fail('unexpected stale allowlist in temp tree');
        }
        foreach ($inMemoryCases as $case) {
            if ($row['rel'] === $case['file'] && $row['name'] === $case['declared']) {
                $foundLabels[$case['label']] = true;
            }
        }
    }
    foreach ($inMemoryCases as $case) {
        if (!isset($foundLabels[$case['label']])) {
            selftest_fail(sprintf('temp tree did not report mismatch for %s', $case['label']));
        }
    }

    $checkerScript = $root . '/scripts/check-psr4-class-file-names.php';
    $phpBinary = PHP_BINARY;

    foreach ($inMemoryCases as $case) {
        $miniRoot = $tmpdir . '/case-' . md5($case['label']);
        mkdir($miniRoot . '/scripts/lib', 0700, true);
        copy($tempCheckerLib, $miniRoot . '/scripts/lib/Psr4_Class_File_Name_Checker.php');
        file_put_contents($miniRoot . '/scripts/psr4-class-file-name-allowlist.txt', "# empty\n");
        $full = $miniRoot . '/' . $case['file'];
        mkdir(dirname($full), 0777, true);
        file_put_contents($full, $case['code']);

        $command = escapeshellarg($phpBinary) . ' ' . escapeshellarg($checkerScript);
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $previousRoot = getenv('MOCKERY_ROOT');
        putenv('MOCKERY_ROOT=' . $miniRoot);
        $process = proc_open($command, $descriptors, $pipes, $root, null);
        if ($previousRoot === false) {
            putenv('MOCKERY_ROOT');
        } else {
            putenv('MOCKERY_ROOT=' . $previousRoot);
        }
        if (!is_resource($process)) {
            selftest_fail('proc_open failed for ' . $case['label']);
        }
        fclose($pipes[0]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        if ($exitCode !== 1) {
            selftest_fail(sprintf('%s expected exit 1, got %d (%s)', $case['label'], $exitCode, trim($stderr)));
        }
    }
} finally {
    $cleanup();
}

// ::class must not count as a declaration.
$withClassConst = <<<'PHP'
<?php
final class Class_Const_Self_Test
{
    public function f(): void
    {
        $x = self::class;
    }
}
PHP;
$decls = $checker->collectDeclarations($withClassConst);
if (count($decls) !== 1 || $decls[0]['name'] !== 'Class_Const_Self_Test') {
    selftest_fail('::class was treated as a declaration');
}

// anonymous class must not count.
$anon = '<?php $x = new class { public function m() {} };';
if ($checker->collectDeclarations($anon) !== []) {
    selftest_fail('anonymous class was counted');
}

selftest_ok('passed');
exit(0);
