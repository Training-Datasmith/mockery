.. index::
    single: Mockery; Exceptions

Mockery Exceptions
==================

Mockery throws three types of exceptions when it cannot verify a mock object.

#. ``\Mockery\Exception\Invalid_Count_Exception``
#. ``\Mockery\Exception\Invalid_Order_Exception``
#. ``\Mockery\Exception\No_Matching_Expectation_Exception``

You can capture any of these exceptions in a try...catch block to query them
for specific information which is also passed along in the exception message
but is provided separately from getters should they be useful when logging or
reformatting output.

\Mockery\Exception\Invalid_Count_Exception
----------------------------------------

The exception class is used when a method is called too many (or too few)
times and offers the following methods:

* ``get_mock()`` - return actual mock object
* ``getMockName()`` - return the name of the mock object
* ``getMethodName()`` - return the name of the method the failing expectation
  is attached to
* ``getExpectedCount()`` - return expected calls
* ``getExpectedCountComparative()`` - returns a string, e.g. ``<=`` used to
  compare to actual count
* ``getActualCount()`` - return actual calls made with given argument
  constraints

\Mockery\Exception\Invalid_Order_Exception
----------------------------------------

The exception class is used when a method is called outside the expected order
set using the ``ordered()`` and ``globally()`` expectation modifiers. It
offers the following methods:

* ``get_mock()`` - return actual mock object
* ``getMockName()`` - return the name of the mock object
* ``getMethodName()`` - return the name of the method the failing expectation
  is attached to
* ``getExpectedOrder()`` - returns an integer represented the expected index
  for which this call was expected
* ``getActualOrder()`` - return the actual index at which this method call
  occurred.

\Mockery\Exception\No_Matching_Expectation_Exception
-------------------------------------------------

The exception class is used when a method call does not match any known
expectation.  All expectations are uniquely identified in a mock object by the
method name and the list of expected arguments. You can disable this exception
and opt for returning NULL from all unexpected method calls by using the
earlier mentioned shouldIgnoreMissing() behaviour modifier. This exception
class offers the following methods:

* ``get_mock()`` - return actual mock object
* ``getMockName()`` - return the name of the mock object
* ``getMethodName()`` - return the name of the method the failing expectation
  is attached to
* ``getActualArguments()`` - return actual arguments used to search for a
  matching expectation
