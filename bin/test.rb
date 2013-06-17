#!/usr/bin/env ruby

require_relative '../RESTafy'

if ARGV.empty? then raise 'ARGV can not be empty.' end

cmd = RESTafy.new.test_cmd ARGV
exec cmd.to_s


