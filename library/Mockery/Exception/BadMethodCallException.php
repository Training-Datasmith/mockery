<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
namespace Mockery\Exception;

class BadMethodCallException extends \BadMethodCallException implements Mockery_Exception_Interface
{
    /**
     * @var bool
     */
    private $dismissed = false;
    public function dismiss(): void
    {
        $this->dismissed = true;
        // we sometimes stack them
        $previous = $this->get_previous();
        if (!$previous instanceof self) {
            return;
        }
        $previous->dismiss();
    }
    /**
     * @return bool
     */
    public function dismissed()
    {
        return $this->dismissed;
    }
}