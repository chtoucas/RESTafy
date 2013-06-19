#!/usr/bin/env ruby

require_relative '../RESTafy'

if ARGV.empty? then RESTafy::TermCarp.croak 'ARGV can not be empty.' end

RESTafy::CmdFactory.new().php_cmd(ARGV, false, true).exec()

# EOF
