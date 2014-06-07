#!/usr/bin/env ruby

require_relative '../RESTafy'
include RESTafy

if ARGV.empty? then Term.croak 'ARGV can not be empty.' end

CmdFactory
  .new(Env.instance)
  .php_cmd(ARGV, false, true)
  .exec()

# EOF
