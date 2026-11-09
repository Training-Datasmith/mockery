.. index::
    single: Quick Reference

Quick Reference
===============

The purpose of this page is to give a quick and short overview of some of the
most common Mockery features.

Do read the :doc:`../reference/index` to learn about all the Mockery features.

Integrate Mockery with PHPUnit, either by extending the ``MockeryTestCase``:

.. code-block:: php

    use \Mockery\Adapter\Phpunit\MockeryTestCase;

    class MyTest extends MockeryTestCase
    {
    }

or by using the ``MockeryPHPUnitIntegration`` trait:

.. code-block:: php

    use \PHPUnit\Framework\TestCase;
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    class MyTest extends TestCase
    {
        use MockeryPHPUnitIntegration;
    }

Creating a test double:

.. code-block:: php

    $testDouble = \Mockery::mock('MyClass');

Creating a test double that implements a certain interface:

.. code-block:: php

    $testDouble = \Mockery::mock('MyClass, MyInterface');

Expecting a method to be called on a test double:

.. code-block:: php

    $testDouble = \Mockery::mock('MyClass');
    $testDouble->should_receive('foo');

Expecting a method to **not** be called on a test double:

.. code-block:: php

    $testDouble = \Mockery::mock('MyClass');
    $testDouble->shouldNotReceive('foo');

Expecting a method to be called on a test double, once, with a certain argument,
and to return a value:

.. code-block:: php

    $mock = \Mockery::mock('MyClass');
    $mock->should_receive('foo')
        ->once()
        ->with($arg)
        ->and_return($returnValue);

Expecting a method to be called on a test double and to return a different value
for each successive call:

.. code-block:: php

    $mock = \Mockery::mock('MyClass');
    $mock->should_receive('foo')
        ->and_return(1, 2, 3);

    $mock->foo(); // int(1);
    $mock->foo(); // int(2);
    $mock->foo(); // int(3);
    $mock->foo(); // int(3);

Creating a runtime partial test double:

.. code-block:: php

    $mock = \Mockery::mock('MyClass')->make_partial();

Creating a spy:

.. code-block:: php

    $spy = \Mockery::spy('MyClass');

Expecting that a spy should have received a method call:

.. code-block:: php

    $spy = \Mockery::spy('MyClass');

    $spy->foo();

    $spy->shouldHaveReceived()->foo();

Not so simple examples
^^^^^^^^^^^^^^^^^^^^^^

Creating a mock object to return a sequence of values from a set of method
calls:

.. code-block:: php

    use \Mockery\Adapter\Phpunit\MockeryTestCase;

    class SimpleTest extends MockeryTestCase
    {
        public function testSimpleMock()
        {
            $mock = \Mockery::mock(array('pi' => 3.1416, 'e' => 2.71));
            $this->assertEquals(3.1416, $mock->pi());
            $this->assertEquals(2.71, $mock->e());
        }
    }

Creating a mock object which returns a self-chaining Undefined object for a
method call:

.. code-block:: php

    use \Mockery\Adapter\Phpunit\MockeryTestCase;

    class UndefinedTest extends MockeryTestCase
    {
        public function testUndefinedValues()
        {
            $mock = \Mockery::mock('mymock');
            $mock->should_receive('divideBy')->with(0)->and_return_undefined();
            $this->assertTrue($mock->divideBy(0) instanceof \Mockery\Undefined);
        }
    }

Creating a mock object with multiple query calls and a single update call:

.. code-block:: php

    use \Mockery\Adapter\Phpunit\MockeryTestCase;

    class DbTest extends MockeryTestCase
    {
        public function testDbAdapter()
        {
            $mock = \Mockery::mock('db');
            $mock->should_receive('query')->and_return(1, 2, 3);
            $mock->should_receive('update')->with(5)->and_return(NULL)->once();

            // ... test code here using the mock
        }
    }

Expecting all queries to be executed before any updates:

.. code-block:: php

    use \Mockery\Adapter\Phpunit\MockeryTestCase;

    class DbTest extends MockeryTestCase
    {
        public function testQueryAndUpdateOrder()
        {
            $mock = \Mockery::mock('db');
            $mock->should_receive('query')->and_return(1, 2, 3)->ordered();
            $mock->should_receive('update')->and_return(NULL)->once()->ordered();

            // ... test code here using the mock
        }
    }

Creating a mock object where all queries occur after startup, but before finish,
and where queries are expected with several different params:

.. code-block:: php

    use \Mockery\Adapter\Phpunit\MockeryTestCase;

    class DbTest extends MockeryTestCase
    {
        public function testOrderedQueries()
        {
            $db = \Mockery::mock('db');
            $db->should_receive('startup')->once()->ordered();
            $db->should_receive('query')->with('CPWR')->and_return(12.3)->once()->ordered('queries');
            $db->should_receive('query')->with('MSFT')->and_return(10.0)->once()->ordered('queries');
            $db->should_receive('query')->with(\Mockery::pattern("/^....$/"))->and_return(3.3)->atLeast()->once()->ordered('queries');
            $db->should_receive('finish')->once()->ordered();

            // ... test code here using the mock
        }
    }
