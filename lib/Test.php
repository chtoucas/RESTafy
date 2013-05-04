<?php
// FIXME: plenty of things to be fixed.

namespace Test;

const CODE_SUCCESS  = 0;
const CODE_FATAL    = 255;

/*!
 * When you are not yet sure of the number of tests to run.
 * Only useful during development phase.
 */
function no_plan() {
  _check_plan(\TRUE);

  $test =& _test_builder();

  \register_shutdown_function('Test\__no_plan_shutdown');
}

/*!
 * Declare the number of tests to run
 *
 * \param $_max_ (integer) Number of tests
 */
function plan($_max_) {
  _check_plan(\TRUE);

  $max = (int)$_max_;

  if ($max > 0) {
    $test =& _test_builder();
    $test->num_of_expected_tests = $max;

    echo "1..$max\n";

    \register_shutdown_function('Test\__plan_shutdown');
  }
  elseif ($max == 0) {
    _test_die('You said to run 0 tests');
  }
  else {
    _test_die("Number of tests must be a positive integer. You gave it '$max'");
  }
}

/*!
 * Skip all tests
 */
function skip_all($_reason_) {
  //$reason = isset($_arg_) ? "# $_arg_" : '';
  echo "1..0 $_reason_\n";

  exit(CODE_SUCCESS);
}

// #############################################################################

/*!
 * Evaluates the expression $_test_, if TRUE reports success, otherwise
 * reports a failure
 *
 * \code
 *  ok($got === $expected, $test_name);
 * \endcode
 *
 * \param $_test_ (boolean) Expression to test
 * \param $_name_ (string) Test name
 * \return TRUE if test passed, FALSE otherwise
 */
function ok($_test_, $_name_ = '') {
  _check_plan();

  $test =& _test_builder();

  $test->num_of_tests++;

  if (empty($_name_)) {
    $name = '';
  }
  elseif (substr($_name_, 0, 1) == '#') {
    $name = $_name_;
  }
  else {
    $name = "- $_name_";
  }

  if (($todo = _todo()) !== \FALSE) {
    /* flag the test as a TODO */
    $todo->seen++;
    $name .= " # TODO {$todo->why}";
  }

  if ($_test_ === \TRUE) {
    echo "ok $test->num_of_tests $name\n";

    $test->num_of_successes++;
  }
  else {
    echo "not ok $test->num_of_tests $name\n";

    $test->num_of_failures++;

    // display the source of the prob
    $calltree = debug_backtrace();

    if (
      (   isset($_SERVER['PHP_SELF'])
      && $_SERVER['PHP_SELF'] !== ''
      && strstr($calltree['0']['file'], $_SERVER['PHP_SELF']))
      ||
      (   isset($_SERVER['argv'][0])
      && strstr($calltree['0']['file'], $_SERVER['argv'][0]))
    ) {
      $file = $calltree['0']['file'];
      $line = $calltree['0']['line'];
    }
    else {
      $file = $calltree['1']['file'];
      $line = $calltree['1']['line'];
    }

    // FIXME: $file = str_replace($_SERVER['SERVER_ROOT'], 't', $file);

    diag("   Failed test '$_name_'");
    diag("   in $file at line $line");
  }

  return $_test_;
}

// #############################################################################

/*!
 * Compare $_got_ and $_expected_ with ===
 *
 * \code
 *  is($got, $expected, $test_name);
 * \endcode
 *
 * \param $_got_ (mixed) Evaluated expression
 * \param $_expected_ (mixed) Expected result
 * \param $_name_ (string) Test name
 * \return TRUE if test passed, FALSE otherwise
 */
function is($_got_, $_expected_, $_name_ = '') {
  _check_plan();

  $passed = ok($_got_ === $_expected_, $_name_);

  if (!$passed) {
    diag("          got: '$_got_'");
    diag("     expected: '$_expected_'");
  }

  return $passed;
}

/*!
 * Same as is() but compare $_got_ and $_expected_ with the operator !==
 *
 * \code
 *  isnt($got, $expected, $test_name);
 * \endcode
 *
 * \param   $_got_ (mixed) Evaluated expression
 * \param   $_expected_ (mixed) Result
 * \param   $_name_ (string) Test name
 * \return  TRUE if test passed, FALSE otherwise
 */
function isnt($_got_, $_expected_, $_name_ = '') {
  _check_plan();

  $passed = ok($_got_ !== $_expected_, $_name_);

  if (!$passed) {
    diag("     '$_got_'");
    diag('         !=');
    diag("     '$_expected_'");
  }

  return $passed;
}

/*! \brief  Similar to ok(), but matches $_got_ against $_pattern_
 * \param   $_got_ (string) Evaluated expression
 * \param   $_pattern_ (string) RegEx
 * \param   $_name_ (string) Test name
 * \return  TRUE on success, FALSE on failure
 */
function like($_got_, $_pattern_, $_name_ = '') {
  _check_plan();

  $passed = ok(preg_match($_pattern_, $_got_), $_name_);
  $passed = ok(preg_match($_pattern_, $_got_), $_name_);

  if (!$passed) {
    diag("                 '$_got_'");
    diag("   doesn't match '$_pattern_'");
  }

  return $passed;
}

/*! \brief  Checks that $_got_ does not match $_pattern_
 * \param   $_got_ (string) Evaluated expression
 * \param   $_pattern_ (string) RegEx
 * \param   $_name_ (string) Test name
 * \return  TRUE if test passed, FALSE otherwise
 */
function unlike($_got_, $_pattern_, $_name_ = '') {
  _check_plan();

  $passed = ok(!preg_match($_pattern_, $_got_), $_name_);

  if (!$passed) {
    diag("           '$_got_'");
    diag("   matches '$_pattern_'");
  }

  return $passed;
}

/*! \brief  Compare $_got_ to $_expected_ using the operator $_operator_
 * \param   $_got_ (mixed) Evaluated expression
 * \param   $_operator_ (string) A PHP operator
 * \param   $_expected_ (mixed) Expecte result
 * \param   $_name_ (string) Test name
 * \return  TRUE if test passed, FALSE otherwise
 */
function cmp_ok($_got_, $_operator_, $_expected_, $_name_ = '') {
  _check_plan();

  $passed = ok(eval("return (\$_got_ $_operator_ \$_expected_);"), $_name_);

  if (!$passed) {
    diag('          got:' . print_r($_got_, \TRUE));
    diag('     expected:' . print_r($_expected_, \TRUE));
  }

  return $passed;
}

function can_ok($_obj_, $_methods_) {
  _check_plan();

  $passed = \TRUE;
  $errors = array();

  $class = get_class($_obj_);

  while (list(, $method) = each($_methods_))
  {
    if (!method_exists($_obj_, $method)) {
      $passed = \FALSE;
      $errors[] = "   {$class}->$method failed";
    }
  }

  if ($passed) {
    ok(\TRUE, "method_exists(\$_obj_, ...)");
  }
  else {
    ok(\FALSE, "method_exists(\$_obj_, ...)");

    while (list(, $error) = each($errors))
    {
      diag($error);
    }
  }

  return $passed;
}

function isa_ok($_obj_, $_class_, $_obj_name_ = 'The object') {
  _check_plan();

  $got = get_class($_obj_);

    /*
    if (TEST_MORE_PHP_5) {
        $passed = ($got == $_class_);
    } else {
        $passed = ($got == strtolower($_class_));
    }
     */

  $passed = ($got == $_class_);

  if ($passed) {
    ok(\TRUE, "$_obj_name_ isa $_class_");
  } else {
    ok(\FALSE, "$_obj_name_ isa $_class_");
    diag("     $_obj_name_ isn't a '$_class_' it's a '$got'");
  }

  return $passed;
}

function pass($_name_ = '') {
  _check_plan();

  return ok(\TRUE, $_name_);
}

function fail($_name_ = '') {
  _check_plan();

  return ok(\FALSE, $_name_);
}

function include_ok($_library_) {
  _check_plan();

  return ok((include $_library_) == 1, 'Loading library ' . $_library_);
}

function require_ok($_library_) {
  _check_plan();

  return ok((require $_library_) == 1, 'Loading library ' . $_library_);
}

function dl_ok($_extension_) {
  // TODO: check availability of dynamicly loaded extensions.

  _check_plan();

  return ok( dl($_extension_) );
}

function is_deeply() {
  // TODO:

  _check_plan();
}

function diag($_message_) {
  _check_plan();

  echo "# $_message_\n";
}

function skip($_why_, $_how_many_) {
  _check_plan();

  for ($i = 0; $i < $_how_many_; $i++)
  {
    ok(\TRUE, "# SKIP $_why_");
  }
}

function todo($_why_, $_how_many_) {
  $todo = new stdClass();
  $todo->why  = $_why_;
  $todo->max  = $_how_many_;
  $todo->seen = 0;

  _todo($todo);
}

function todo_skip($_why_, $_how_many_) {
  _check_plan();

  for ($i = 0; $i < $_how_many_; $i++)
  {
    ok(\TRUE, "# TODO & SKIP $_why_");
  }
}

function BAIL_OUT($_reason_) {
  _check_plan();

  _test_die("Bail out! $_reason_");
}

// #############################################################################

function & _test_builder() {
  static $test;

  if ($test === NULL) {
    $test = new \stdClass();

    $test->num_of_successes = 0;   /* number of successful tests */
    $test->num_of_failures  = 0;   /* number of failed tests */
    $test->num_of_tests     = 0;   /* number of passed tests */

    $test->has_died = \FALSE;

    \register_shutdown_function('Test\__test_shutdown');
  }

  return $test;
}

function _test_die($_reason_) {
  $test = _test_builder();
  $test->has_died = \TRUE;

  echo "$_reason_\n";

  exit(CODE_FATAL);
}

function _check_plan($_planning_ = \FALSE) {
  static $planned = \FALSE;

  if ($_planning_) {
    if ($planned) {
      _test_die('You tried to plan twice');
    } else {
      $planned = \TRUE;
    }
  }
  else {
    if (!$planned) {
      _test_die('You did not make any plan!');
    }
  }
}

function _todo($_todo_ = NULL) {
  static $todo = \FALSE;

  if ($_todo_ !== NULL) {
    $todo = $_todo_;
  }

  if ($todo !== \FALSE && $todo->seen == $todo->max) {
    $todo = \FALSE;
  }

  return $todo;
}

// #############################################################################

function __test_shutdown() {
  $test= _test_builder();

  if ($test->has_died) {
    exit(CODE_FATAL);
  }
}

function __no_plan_shutdown() {
  $test = _test_builder();

  echo "1..$test->num_of_tests\n";

  if ($test->num_of_failures > 0) {
    diag("Looks like you failed {$test->num_of_failures} tests of {$test->num_of_tests}.");
    $exit_code = min(CODE_FATAL - 1, $test->num_of_failures);
  }
  else {
    $exit_code = CODE_SUCCESS;
  }

  exit($exit_code);
}

function __plan_shutdown() {
  $test = _test_builder();

  $diff = $test->num_of_expected_tests - $test->num_of_tests;

  if ($diff > 0) {
    $num_extra = $diff;
    diag("Looks like you planned {$test->num_of_expected_tests} tests but only ran {$test->num_of_tests}.");
  }
  elseif ($diff < 0) {
    $num_extra = - $diff;
    diag("Looks like you planned {$test->num_of_expected_tests} tests but ran $num_extra extra.");
  }
  else {
    $num_extra = 0;
  }

  if ($test->num_of_failures > 0) {
    diag("Looks like you failed {$test->num_of_failures} tests of {$test->num_of_tests}.");
    $exit_code
      = min(CODE_FATAL - 1, $test->num_of_failures + $num_extra);
  }
  else {
    $exit_code
      = $num_extra > 0 ? CODE_FATAL : CODE_SUCCESS;
  }

  exit($exit_code);
}

// EOF

