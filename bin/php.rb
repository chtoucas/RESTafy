#!/usr/bin/env ruby

# http://www.awesomecommandlineapps.com/gems.html
# http://mentalized.net/journal/2010/03/08/5_ways_to_run_commands_from_ruby/
# http://stackoverflow.com/questions/3159945/running-command-line-commands-within-ruby-script

require 'rbconfig'
require 'fileutils'

#---------------------------------------------------------------------------------------------------

def os
    # Cf. http://stackoverflow.com/questions/11784109/detecting-operating-systems-in-ruby
    @os ||= (
        case RbConfig::CONFIG['host_os']
        when /cygwin/
            then 'cygwin'
        when /darwin|mac os|linux|solaris|bsd/
            then 'unix'
        else raise 'Unsupported OS.'
        end
    )
end

def _(path)
    # Cf. http://www.cygwin.com/cygwin-ug-net/using-utils.html#cygpath
    # NB: There is a problem in cygwin whith paths containing a tilde (short-name DOS style).
    # Nevertheless when the file already exists, the problem disappears.
    os == 'cygwin' ? %x(cygpath -law -- "#{path}").strip! : path
end

#---------------------------------------------------------------------------------------------------

if ARGV.empty? then
    raise 'Missing input file.'
end

# Project directory.
dir = File.join(File.expand_path(File.dirname(__FILE__)), '..')
# Temp directory.
tmpdir = File.join(dir, 'tmp')
Dir.mkdir_p(tmpdir) unless File.exists?(tmpdir)
# Log file.
logfile = File.join(tmpdir, 'php.log')
FileUtils.touch(logfile) unless File.exists?(logfile)

# PHP inline configuration.
ini = {
    'include_path' => _(File.join(dir, 'lib')),
    'error_log'    => _(logfile)
}.map { |k, v| '-d "%s=%s"' % [k, v] }
# Other arguments passed to the PHP interpreter.
opts = {
    # PHP ini file.
    '-c' => _(File.join(dir, 'etc', 'php.ini')),
}.map { |k, v| '%s "%s"' % [k, v] }

args = opts.push(ini).push(ARGV).join(' ')

exec("/usr/bin/env php #{args}")

# EOF
