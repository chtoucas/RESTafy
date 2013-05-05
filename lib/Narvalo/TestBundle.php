<?php
// FIXME: plenty of things to be fixed.

namespace Narvalo\Test;

use \Narvalo\Test\Internal as _;

/*!
 * When you are not yet sure of the number of tests to run.
 * Only useful during development phase.
 */
function no_plan() {
  _\check_plan(\TRUE);
  _\start_no_plan();
}

/*!
 * Declare the number of tests to run
 *
 * \param $_max_ (integer) Number of tests
 */
function plan($_max_) {
  _\check_plan(\TRUE);

  $max = (int)$_max_;

  if ($max > 0) {
    _\start_plan($max);

    echo "1..$max\n";
  }
  elseif (0 === $max) {
    _\test_die('You said to run 0 tests');
  }
  else {
    _\test_die("Number of tests must be a positive integer. You gave it '$max'");
  }
}

/*!
 * Skip all tests
 */
function skip_all($_reason_) {
  //$reason = isset($_arg_) ? "# $_arg_" : '';
  echo "1..0 $_reason_\n";

  exit(_\SUCCESS_CODE);
}

// #################################################################################################

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
  _\check_plan();

  $test =& _\build_test();

  $test->num_of_tests++;

  if (empty($_name_)) {
    $name = '';
  }
  elseif ('#' === \substr($_name_, 0, 1)) {
    $name = $_name_;
  }
  else {
    $name = "- $_name_";
  }

  if (\FALSE !== ($todo = _\test_todo())) {
    // flag the test as a TODO
    $todo->seen++;
    $name .= " # TODO {$todo->why}";
  }

  if (\TRUE === $_test_) {
    echo "ok $test->num_of_tests $name\n";

    $test->num_of_successes++;
  }
  else {
    echo "not ok $test->num_of_tests $name\n";

    $test->num_of_failures++;

    // display the source of the prob
    $calltree = \debug_backtrace();

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

    _\diag("   Failed test '$_name_'");
    _\diag("   in $file at line $line");
  }

  return $_test_;
}

// #################################################################################################

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
  _\check_plan();

  $passed = ok($_got_ === $_expected_, $_name_);

  if (!$passed) {
    _\diag("          got: '$_got_'");
    _\diag("     expected: '$_expected_'");
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
  _\check_plan();

  $passed = ok($_got_ !== $_expected_, $_name_);

  if (!$passed) {
    _\diag("     '$_got_'");
    _\diag('         !=');
    _\diag("     '$_expected_'");
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
  _\check_plan();

  $passed = ok(\preg_match($_pattern_, $_got_), $_name_);
  $passed = ok(\preg_match($_pattern_, $_got_), $_name_);

  if (!$passed) {
    _\diag("                 '$_got_'");
    _\diag("   doesn't match '$_pattern_'");
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
  _\check_plan();

  $passed = ok(!\preg_match($_pattern_, $_got_), $_name_);

  if (!$passed) {
    _\diag("           '$_got_'");
    _\diag("   matches '$_pattern_'");
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
  _\check_plan();

  $passed = ok(eval("return (\$_got_ $_operator_ \$_expected_);"), $_name_);

  if (!$passed) {
    _\diag('          got:' . print_r($_got_, \TRUE));
    _\diag('     expected:' . print_r($_expected_, \TRUE));
  }

  return $passed;
}

function can_ok($_obj_, $_methods_) {
  _\check_plan();

  $passed = \TRUE;
  $errors = array();

  $class = \get_class($_obj_);

  while (list(, $method) = each($_methods_)) {
    if (!\method_exists($_obj_, $method)) {
      $passed = \FALSE;
      $errors[] = "   {$class}->$method failed";
    }
  }

  if ($passed) {
    ok(\TRUE, "method_exists(\$_obj_, ...)");
  }
  else {
    ok(\FALSE, "method_exists(\$_obj_, ...)");

    while (list(, $error) = each($errors)) {
      _\diag($error);
    }
  }

  return $passed;
}

function isa_ok($_obj_, $_class_, $_obj_name_ = 'The object') {
  _\check_plan();

  $got = \get_class($_obj_);

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
    _\diag("     $_obj_name_ isn't a '$_class_' it's a '$got'");
  }

  return $passed;
}

function pass($_name_ = '') {
  _\check_plan();

  return ok(\TRUE, $_name_);
}

function fail($_name_ = '') {
  _\check_plan();

  return ok(\FALSE, $_name_);
}

function panic() {
  // TODO:

  _\check_plan();
}

function include_ok($_library_) {
  _\check_plan();

  return ok((include $_library_) == 1, 'Loading library ' . $_library_);
}

function require_ok($_library_) {
  _\check_plan();

  return ok((require $_library_) == 1, 'Loading library ' . $_library_);
}

function dl_ok($_extension_) {
  // TODO: check availability of dynamicly loaded extensions.

  _\check_plan();

  return ok(dl($_extension_));
}

function is_deeply() {
  // TODO:

  _\check_plan();
}

function skip($_why_, $_how_many_) {
  _\check_plan();

  for ($i = 0; $i < $_how_many_; $i++) {
    ok(\TRUE, "# SKIP $_why_");
  }
}

function todo($_why_, $_how_many_) {
  $todo = new \stdClass();
  $todo->why  = $_why_;
  $todo->max  = $_how_many_;
  $todo->seen = 0;

  _\test_todo($todo);
}

function todo_skip($_why_, $_how_many_) {
  _\check_plan();

  for ($i = 0; $i < $_how_many_; $i++) {
    ok(\TRUE, "# TODO & SKIP $_why_");
  }
}

function BAIL_OUT($_reason_) {
  _\check_plan();

  _\test_die("Bail out! $_reason_");
}

// #################################################################################################

namespace Narvalo\Test\Internal;

const SUCCESS_CODE  = 0;
const FATAL_CODE    = 255;

function check_plan($_planning_ = \FALSE) {
  static $planned = \FALSE;

  if ($_planning_) {
    if ($planned) {
      test_die('You tried to plan twice');
    } else {
      $planned = \TRUE;
    }
  }
  else {
    if (!$planned) {
      test_die('You did not make any plan!');
    }
  }
}

function start_no_plan() {
  \register_shutdown_function(__NAMESPACE__.'\__no_plan_shutdown');
}

function start_plan($_max_) {
  $test =& build_test();
  $test->num_of_expected_tests = $_max_;

  \register_shutdown_function(__NAMESPACE__.'\__plan_shutdown');
}

function & build_test() {
  static $test;

  if ($test === \NULL) {
    $test = new \stdClass();

    $test->num_of_successes = 0;   // number of successful tests
    $test->num_of_failures  = 0;   // number of failed tests
    $test->num_of_tests     = 0;   // number of passed tests

    $test->has_died = \FALSE;

    \register_shutdown_function(__NAMESPACE__.'\__test_shutdown');
  }

  return $test;
}

function test_die($_reason_) {
  $test = build_test();
  $test->has_died = \TRUE;

  echo "$_reason_\n";

  exit(FATAL_CODE);
}

function test_todo($_todo_ = NULL) {
  static $todo = \FALSE;

  if (\NULL !== $_todo_) {
    $todo = $_todo_;
  }

  if (\FALSE !== $todo && $todo->seen == $todo->max) {
    $todo = \FALSE;
  }

  return $todo;
}

function diag($_message_) {
  check_plan();

  echo "# $_message_\n";
}

// #################################################################################################

function __test_shutdown() {
  $test = build_test();

  if ($test->has_died) {
    exit(FATAL_CODE);
  }
}

function __no_plan_shutdown() {
  $test = build_test();

  echo "1..$test->num_of_tests\n";

  if ($test->num_of_failures > 0) {
    diag("Looks like you failed {$test->num_of_failures} tests of {$test->num_of_tests}.");
    $exit_code = min(FATAL_CODE - 1, $test->num_of_failures);
  }
  else {
    $exit_code = SUCCESS_CODE;
  }

  exit($exit_code);
}

function __plan_shutdown() {
  $test = build_test();

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
    $exit_code = \min(FATAL_CODE - 1, $test->num_of_failures + $num_extra);
  }
  else {
    $exit_code = $num_extra > 0 ? FATAL_CODE : SUCCESS_CODE;
  }

  exit($exit_code);
}

// EOF

