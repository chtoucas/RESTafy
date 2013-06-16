#!/usr/bin/env ruby

require_relative '../RESTafy'

RESTafyEnv::init
RESTafy.new.test(ARGV)

