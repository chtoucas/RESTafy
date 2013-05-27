Utilities
=========

* Rewrite all utilities to make them more portable, maintainable and reliable.
For instance, a call to mkdir under Cygwin might fail.

Narvalo\Test
============

* canInclude is broken when there is a compile error.
* the whole thing break when there is an exit.
* Interlinked directives and interlaced tags.
* Test the Test.
* Comments & doc.
* Review all FIXME & TODO.
* Failed include directive produce two error messages.
* Add timers to Harness & Runner
* Add OnTestCompleted event?
* BUG: if we run the harness twice in a row, it fails.
* check all constructors for validity
* reset states
* can TAP normal and error streams use the same FH?
* flush on handles to ensure correct ordering
* in a subtest, we should unindent in bailout
* Cf. Test::Differences, Test::Deeper, Test::Class, Test::Most

Narvalo
=======

* Not yet working on it.

RESTafy
=======

* Not yet working on it.


