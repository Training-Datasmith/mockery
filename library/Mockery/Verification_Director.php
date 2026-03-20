<?php

declare (strict_types=1);
/**
 * Mockery (https://docs.mockery.io/)
 *
 * @copyright https://github.com/mockery/mockery/blob/HEAD/COPYRIGHT.md
 * @license https://github.com/mockery/mockery/blob/HEAD/LICENSE BSD 3-Clause License
 * @link https://github.com/mockery/mockery for the canonical source repository
 */
namespace Mockery;

class Verification_Director
{
    /**
     * @var VerificationExpectation
     */
    private $expectation;
    /**
     * @var ReceivedMethodCalls
     */
    private $received_method_calls;
    public function __construct(Received_Method_Calls $received_method_calls, Verification_Expectation $expectation)
    {
        $this->received_method_calls = $received_method_calls;
        $this->expectation = $expectation;
    }
    /**
     * @return self
     */
    public function at_least()
    {
        return $this->clone_without_count_validators_apply_and_verify('atLeast', []);
    }
    /**
     * @return self
     */
    public function at_most()
    {
        return $this->clone_without_count_validators_apply_and_verify('atMost', []);
    }
    /**
     * @param int $minimum
     * @param int $maximum
     *
     * @return self
     */
    public function between($minimum, $maximum)
    {
        return $this->clone_without_count_validators_apply_and_verify('between', [$minimum, $maximum]);
    }
    /**
     * @return self
     */
    public function once()
    {
        return $this->clone_without_count_validators_apply_and_verify('once', []);
    }
    /**
     * @param int $limit
     *
     * @return self
     */
    public function times($limit = null)
    {
        return $this->clone_without_count_validators_apply_and_verify('times', [$limit]);
    }
    /**
     * @return self
     */
    public function twice()
    {
        return $this->clone_without_count_validators_apply_and_verify('twice', []);
    }
    public function verify(): void
    {
        $this->received_method_calls->verify($this->expectation);
    }
    /**
     * @template TArgs
     *
     * @param TArgs $args
     *
     * @return self
     */
    public function with(...$args)
    {
        return $this->clone_apply_and_verify('with', $args);
    }
    /**
     * @return self
     */
    public function with_any_args()
    {
        return $this->clone_apply_and_verify('withAnyArgs', []);
    }
    /**
     * @template TArgs
     *
     * @param TArgs $args
     *
     * @return self
     */
    public function with_args($args)
    {
        return $this->clone_apply_and_verify('withArgs', [$args]);
    }
    /**
     * @return self
     */
    public function with_no_args()
    {
        return $this->clone_apply_and_verify('withNoArgs', []);
    }
    /**
     * @param string $method
     * @param array  $args
     */
    protected function clone_apply_and_verify($method, $args): self
    {
        $verification_expectation = clone $this->expectation;
        $verification_expectation->{$method}(...$args);
        $verification_director = new self($this->received_method_calls, $verification_expectation);
        $verification_director->verify();
        return $verification_director;
    }
    /**
     * @param string $method
     * @param array  $args
     */
    protected function clone_without_count_validators_apply_and_verify($method, $args): self
    {
        $verification_expectation = clone $this->expectation;
        $verification_expectation->clear_count_validators();
        $verification_expectation->{$method}(...$args);
        $verification_director = new self($this->received_method_calls, $verification_expectation);
        $verification_director->verify();
        return $verification_director;
    }
}