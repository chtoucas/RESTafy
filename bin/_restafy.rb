#!/usr/bin/env ruby

# http://www.awesomecommandlineapps.com/gems.html
# http://mentalized.net/journal/2010/03/08/5_ways_to_run_commands_from_ruby/
# http://stackoverflow.com/questions/3159945/running-command-line-commands-within-ruby-script

require 'fileutils'
require 'rbconfig'

#---------------------------------------------------------------------------------------------------

module RESTafy
    @@Base    = File.join(File.expand_path(File.dirname(__FILE__)), '..')
    @@TmpDir  = File.join(@@Base, 'tmp')
    @@LogFile = File.join(@@TmpDir, 'php.log')

    Dir.mkdir_p(@@TmpDir)      unless File.exists?(@@TmpDir)
    FileUtils.touch(@@LogFile) unless File.exists?(@@LogFile)

    def self.exec(argv, quiet)
        if argv.empty? then
            raise 'argv can not be empty.'
        end

        cmd = PHPCmd.new(argv)
        cmd.ini('include_path', Cygwin::join(@@Base, 'lib'))
        cmd.ini('error_log', Cygwin::path(@@LogFile))
        cmd.opt('-c', Cygwin::join(@@Base, 'etc', quiet ? 'php-quiet.ini' : 'php.ini'))

        Kernel.exec(cmd.to_s())
    end
end

class PHPCmd
    def initialize(argv)
        @argv = argv
        @opts = {}
        @ini  = {}
    end

    def ini(key, value)
        @ini[key] = value
    end

    def opt(key, value)
        @opts[key] = value
    end

    def to_s
        # INI settings.
        ini  = @ini.map { |k, v| '-d "%s=%s"' % [k, v] }
        # Other arguments passed to the PHP interpreter.
        opts = @opts.map { |k, v| '%s "%s"' % [k, v] }

        args = opts.push(ini).push(@argv).join(' ')

        "/usr/bin/env php #{args}"
    end
end

module Cygwin
    def self.path(path)
        # Cf. http://www.cygwin.com/cygwin-ug-net/using-utils.html#cygpath
        # NB: There is a problem in cygwin whith paths containing a tilde (short-name DOS style).
        # Nevertheless when the file already exists, the problem disappears.
        self.is? ? %x(cygpath -law -- "#{path}").strip! : path
    end

    def self.join(*args)
        path(File.join(args))
    end

    def self.is?
        # Cf. http://stackoverflow.com/questions/11784109/detecting-operating-systems-in-ruby
        @is ||= (
            case RbConfig::CONFIG['host_os']
            when /cygwin/
                then true
            when /darwin|mac os|linux|solaris|bsd/
                then false
            else raise 'Unsupported OS.'
            end
        )
    end
end

#---------------------------------------------------------------------------------------------------

# EOF
