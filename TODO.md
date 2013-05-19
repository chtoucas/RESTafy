Narvalo\Test
============

* SKIP & TODO
* Abstract the directives TODO & SKIP.
* Test the Test.
* Comments & doc.
* Review all FIXME & TODO.
* Currently, one can not create a new test module inside a subtest.
* Failed include directive produce two error messages.
* Add timers to Harness & Runner
* BUG: if we run the harness twice in a row, it fails.
* check all constructors for validity
* reset states
* can TAP normal and error streams use the same FH?
* flush on handles to ensure correct ordering
* in a subtest, we should unindent in bailout
* Test::Differences, Test::Deeper, Test::Class, Test::Most

Several ways to report an error:
* throws an Exception for any internal error and for fatal error
    where we can not use TestProducer::bailOut()
* trigger_error()
  - E_USER_ERROR for any fatal error during GC
  - c E_USER_WARNING for any non-fatal error where we can not use \c TestProducer::Warn()
* TestProducer::bailOut() for remaining fatal errors
* TestProducer::Warn() for remaining non-fatal errors

Narvalo
=======

* Not yet working on it.

RESTafy
=======

* Not yet working on it.


