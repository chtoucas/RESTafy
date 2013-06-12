#!/usr/bin/env ruby

# http://www.awesomecommandlineapps.com/gems.html
# http://mentalized.net/journal/2010/03/08/5_ways_to_run_commands_from_ruby/
# http://stackoverflow.com/questions/3159945/running-command-line-commands-within-ruby-script

require 'rbconfig'

def os
    # http://stackoverflow.com/questions/11784109/detecting-operating-systems-in-ruby
    @os ||= (
        case RbConfig::CONFIG['host_os']
        when /cygwin/
            then 'cygwin'
        when /darwin|mac os|linux|solaris|bsd/
            then 'unix'
        else raise 'Unsupported OS'
        end
    )
end

def _(path)
    # Cf. http://www.cygwin.com/cygwin-ug-net/using-utils.html#cygpath
    # FIXME: There is a problem in cygwin when the path contains a tilde: ~
    os == 'cygwin' ? (`cygpath -wal -- "#{path}"`).strip! : path
end

# Project directories.
dir = File.join(File.expand_path(File.dirname(__FILE__)), '..')
tmpdir = File.join(dir, 'tmp')

Dir.mkdir(tmpdir) unless File.exists?(tmpdir)

opts = {
    # PHP ini file.
    '-c' => _(File.join(dir, 'etc', 'php.ini')),
    # PHP include path.
    '-d' => 'include_path=%s' % _(File.join(dir, 'lib')),
    # PHP error log.
    #'-d' => 'error_log=%s' % _(File.join(tmpdir, 'php.log')),
}
args = opts.map{ |k, v| '%s "%s"' % [k, v]}.push(ARGV).join(' ')

exec("/usr/bin/env php #{args}")

