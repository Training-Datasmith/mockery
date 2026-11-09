<?php

declare(strict_types=1);

/**
 * Token-based scan for class/interface/trait/enum declarations vs file basename.
 */
final class Psr4_Class_File_Name_Checker
{
    private const ALLOWLIST_PATH = 'scripts/psr4-class-file-name-allowlist.txt';

    /** @var list<string> */
    private const DECL_KINDS = ['class', 'interface', 'trait', 'enum'];

    /** @var string */
    private $root;

    /** @var array<string, array<string, string>> */
    private $allowlist = [];

    public function __construct(string $root)
    {
        $this->root = rtrim($root, '/');
    }

    /**
     * @return list<array{rel: string, name: string, stem: string, kind: string}>
     */
    public function findMismatches(): array
    {
        $this->loadAllowlist();
        $mismatches = [];
        $seenDeclared = [];

        foreach (['library', 'tests'] as $top) {
            $base = $this->root . '/' . $top;
            if (!is_dir($base)) {
                continue;
            }
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
            );
            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                $path = $file->getPathname();
                $rel = substr($path, strlen($this->root) + 1);
                $stem = $file->getBasename('.php');
                $code = file_get_contents($path);
                if ($code === false) {
                    throw new RuntimeException('Could not read ' . $rel);
                }

                foreach ($this->collectDeclarations($code) as $decl) {
                    $key = $rel . "\0" . $decl['name'];
                    $seenDeclared[$key] = true;
                    if ($decl['name'] === $stem) {
                        continue;
                    }
                    if ($this->isAllowlisted($rel, $decl['name'])) {
                        continue;
                    }
                    $mismatches[] = [
                        'rel' => $rel,
                        'name' => $decl['name'],
                        'stem' => $stem,
                        'kind' => $decl['kind'],
                    ];
                }
            }
        }

        $stale = $this->findStaleAllowlistEntries($seenDeclared);
        foreach ($stale as $entry) {
            $mismatches[] = [
                'rel' => $entry['file'],
                'name' => '(stale allowlist)',
                'stem' => $entry['class'],
                'kind' => 'stale:' . $entry['reason'],
            ];
        }

        usort($mismatches, static function (array $a, array $b): int {
            return [$a['rel'], $a['name']] <=> [$b['rel'], $b['name']];
        });

        return $mismatches;
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     *
     * @return list<array{kind: string, name: string}>
     */
    public function collectDeclarations(string $code): array
    {
        $tokens = token_get_all($code);
        $n = count($tokens);
        $declarations = [];

        for ($i = 0; $i < $n; $i++) {
            if (!$this->isDeclarationStart($tokens, $i, $kind, $nameIndex)) {
                continue;
            }
            $anchor = $kind === 'class' && $this->isReadonlyClassKeyword($tokens, $i)
                ? $this->indexAfterKeyword($tokens, $i, 'class')
                : $i;
            if ($kind === 'class' && $this->isAnonymousClass($tokens, $anchor)) {
                continue;
            }

            $name = $this->readDeclarationName($tokens, $nameIndex);
            if ($name === null) {
                continue;
            }

            $declarations[] = ['kind' => $kind, 'name' => $name];
        }

        return $declarations;
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     */
    private function isDeclarationStart(array $tokens, int $i, ?string &$kind, ?int &$nameIndex): bool
    {
        $kind = null;
        $nameIndex = null;
        $t = $tokens[$i];
        if (!is_array($t)) {
            return false;
        }

        if ($t[0] === T_CLASS) {
            if ($this->isClassConst($tokens, $i)) {
                return false;
            }
            if ($this->hasReadonlyBefore($tokens, $i)) {
                return false;
            }
            $kind = 'class';
            $nameIndex = $i + 1;

            return true;
        }

        if ($t[0] === T_INTERFACE) {
            $kind = 'interface';
            $nameIndex = $i + 1;

            return true;
        }

        if ($t[0] === T_TRAIT) {
            $kind = 'trait';
            $nameIndex = $i + 1;

            return true;
        }

        if (defined('T_ENUM') && $t[0] === T_ENUM) {
            $kind = 'enum';
            $nameIndex = $i + 1;

            return true;
        }

        if ($t[0] === T_STRING && strcasecmp($t[1], 'enum') === 0) {
            $kind = 'enum';
            $nameIndex = $i + 1;

            return true;
        }

        if ($this->isReadonlyClassKeyword($tokens, $i)) {
            $kind = 'class';
            $nameIndex = $this->indexAfterKeyword($tokens, $i, 'class') + 1;

            return true;
        }

        return false;
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     */
    private function isReadonlyClassKeyword(array $tokens, int $readonlyIndex): bool
    {
        $t = $tokens[$readonlyIndex];
        if (!is_array($t)) {
            return false;
        }
        $isReadonly = (defined('T_READONLY') && $t[0] === T_READONLY)
            || ($t[0] === T_STRING && strcasecmp($t[1], 'readonly') === 0);
        if (!$isReadonly) {
            return false;
        }
        $classIndex = $this->indexAfterKeyword($tokens, $readonlyIndex, 'class');

        return $classIndex !== -1;
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     */
    private function indexAfterKeyword(array $tokens, int $start, string $keyword): int
    {
        $n = count($tokens);
        for ($j = $start + 1; $j < $n; $j++) {
            $t = $tokens[$j];
            if (is_array($t) && ($t[0] === T_WHITESPACE || $t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT)) {
                continue;
            }
            if (is_array($t) && $t[0] === T_STRING && strcasecmp($t[1], $keyword) === 0) {
                return $j;
            }
            if ($keyword === 'class' && is_array($t) && $t[0] === T_CLASS) {
                return $j;
            }

            return -1;
        }

        return -1;
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     */
    private function isClassConst(array $tokens, int $classIndex): bool
    {
        $j = $classIndex - 1;
        while ($j >= 0) {
            $p = $tokens[$j];
            if (is_array($p) && ($p[0] === T_WHITESPACE || $p[0] === T_COMMENT || $p[0] === T_DOC_COMMENT)) {
                $j--;
                continue;
            }
            if (is_string($p) && $p === ':') {
                return true;
            }
            if (is_array($p) && $p[0] === T_DOUBLE_COLON) {
                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     */
    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     */
    private function hasReadonlyBefore(array $tokens, int $classIndex): bool
    {
        $j = $classIndex - 1;
        while ($j >= 0) {
            $p = $tokens[$j];
            if (is_array($p) && ($p[0] === T_WHITESPACE || $p[0] === T_COMMENT || $p[0] === T_DOC_COMMENT)) {
                $j--;
                continue;
            }
            if (is_array($p) && ((defined('T_READONLY') && $p[0] === T_READONLY)
                || ($p[0] === T_STRING && strcasecmp($p[1], 'readonly') === 0))) {
                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     */
    private function isAnonymousClass(array $tokens, int $classIndex): bool
    {
        $j = $classIndex - 1;
        while ($j >= 0) {
            $p = $tokens[$j];
            if (is_array($p) && ($p[0] === T_WHITESPACE || $p[0] === T_COMMENT || $p[0] === T_DOC_COMMENT
                || $p[0] === T_ABSTRACT || $p[0] === T_FINAL
                || (defined('T_READONLY') && $p[0] === T_READONLY)
                || ($p[0] === T_STRING && strcasecmp($p[1], 'readonly') === 0))) {
                $j--;
                continue;
            }
            if (is_array($p) && $p[0] === T_NEW) {
                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     */
    private function readDeclarationName(array $tokens, int $start): ?string
    {
        $n = count($tokens);
        for ($i = $start; $i < $n; $i++) {
            $t = $tokens[$i];
            if (is_array($t) && ($t[0] === T_WHITESPACE || $t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT)) {
                continue;
            }
            if (is_array($t) && $t[0] === T_STRING) {
                return $t[1];
            }

            return null;
        }

        return null;
    }

    private function loadAllowlist(): void
    {
        $path = $this->root . '/' . self::ALLOWLIST_PATH;
        if (!is_readable($path)) {
            $this->allowlist = [];

            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new RuntimeException('Could not read allowlist');
        }
        foreach ($lines as $lineno => $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || strpos($trimmed, '#') === 0) {
                continue;
            }
            $parts = explode('|', $trimmed, 3);
            if (count($parts) !== 3) {
                throw new RuntimeException(sprintf('Invalid allowlist line %d: %s', $lineno + 1, $trimmed));
            }
            [$file, $class, $reason] = $parts;
            $file = trim($file);
            $class = trim($class);
            $reason = trim($reason);
            if ($file === '' || $class === '' || $reason === '') {
                throw new RuntimeException(sprintf('Empty allowlist field on line %d', $lineno + 1));
            }
            $this->allowlist[$file][$class] = $reason;
        }
    }

    private function isAllowlisted(string $rel, string $class): bool
    {
        return isset($this->allowlist[$rel][$class]);
    }

    /**
     * @param array<string, true> $seenDeclared keys file\0class
     *
     * @return list<array{file: string, class: string, reason: string}>
     */
    private function findStaleAllowlistEntries(array $seenDeclared): array
    {
        $stale = [];
        foreach ($this->allowlist as $file => $classes) {
            foreach ($classes as $class => $reason) {
                $key = $file . "\0" . $class;
                if (!isset($seenDeclared[$key])) {
                    $stale[] = ['file' => $file, 'class' => $class, 'reason' => $reason];
                }
            }
        }

        return $stale;
    }

    public static function allowlistPath(string $root): string
    {
        return rtrim($root, '/') . '/' . self::ALLOWLIST_PATH;
    }
}
