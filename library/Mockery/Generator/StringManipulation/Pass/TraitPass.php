<?php

declare(strict_types=1);

/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */

namespace Mockery\Generator\StringManipulation\Pass;

use function array_map;
use function implode;
use function ltrim;

use Mockery\Generator\MockConfiguration;

use function preg_replace;

class TraitPass implements Pass
{
    /**
     * @param  string $code
     * @return string
     */
    public function apply($code, MockConfiguration $config)
    {
        $traits = $config->getTargetTraits();

        if ($traits === []) {
            return $code;
        }

        $useStatements = array_map(static function ($trait): string {
            return 'use \\\\' . ltrim($trait->getName(), '\\') . ';';
        }, $traits);

        return preg_replace('/^{$/m', "{\n    " . implode("\n    ", $useStatements) . "\n", $code);
    }
}
