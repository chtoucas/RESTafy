#!/usr/bin/env ruby

# http://www.awesomecommandlineapps.com/gems.html
# http://mentalized.net/journal/2010/03/08/5_ways_to_run_commands_from_ruby/
# http://stackoverflow.com/questions/3159945/running-command-line-commands-within-ruby-script
# http://www.cygwin.com/cygwin-ug-net/using-utils.html#cygpath

require 'rbconfig'

def os
    # http://stackoverflow.com/questions/11784109/detecting-operating-systems-in-ruby
    @os ||= (
        case RbConfig::CONFIG['host_os']
        when /mswin|msys|mingw|cygwin|bccwin|wince|emc/ then 'windows'
        when /darwin|mac os|linux|solaris|bsd/          then 'unix'
        else raise 'Unknown OS'
        end
    )
end

def get_win_path(path)
    (`cygpath -w -l -a -- "#{path}"`).strip!
end

dir = File.expand_path(File.dirname(__FILE__))
phprc = File.join(dir, 'etc/php.ini')
php_include_path = File.join(dir, 'lib')

if os == 'windows'
    phprc = get_win_path phprc
    php_include_path = get_win_path php_include_path
end

#php_exe = `which php-cli 2>/dev/null`
php_exe = (`which php 2>/dev/null`).strip!

#if !File.executable? php_exe
#    raise 'Unable to find PHP executable'
#end

php_cmd = "#{php_exe} -c \"#{phprc}\" -d include_path=\"#{php_include_path}\" --ini"
#.sub!('~', '\~')

puts php_cmd
#exec(php_cmd)

