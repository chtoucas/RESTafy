#!/usr/bin/env ruby

require_relative '../RESTafy'

if ARGV.empty? then raise 'ARGV can not be empty.' end

cmd = RESTafy::CmdFactory.new().php_cmd(ARGV, false, true)
exec(cmd.to_s())

