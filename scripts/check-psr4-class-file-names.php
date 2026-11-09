<?php

declare(strict_types=1);

$root = getenv('MOCKERY_ROOT');
if ($root === false || $root === '') {
    $root = dirname(__DIR__);
}
require $root . '/scripts/lib/Psr4_Class_File_Name_Checker.php';

$checker = new Psr4_Class_File_Name_Checker($root);
$mismatches = $checker->findMismatches();

if ($mismatches !== []) {
    fwrite(STDERR, "check-psr4-class-file-names: class/file name mismatches:\n");
    foreach ($mismatches as $row) {
        if (strncmp($row['name'], '(stale', 7) === 0) {
            fwrite(
                STDERR,
                sprintf(
                    "  stale allowlist entry: %s | %s (%s)\n",
                    $row['rel'],
                    $row['stem'],
                    $row['kind']
                )
            );
            continue;
        }
        fwrite(
            STDERR,
            sprintf(
                "  %s: declared %s (%s), file base %s\n",
                $row['rel'],
                $row['name'],
                $row['kind'],
                $row['stem']
            )
        );
    }
    exit(1);
}

exit(0);
