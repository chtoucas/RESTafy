# http://www.awesomecommandlineapps.com/gems.html
# http://mentalized.net/journal/2010/03/08/5_ways_to_run_commands_from_ruby/
# http://stackoverflow.com/questions/3159945/running-command-line-commands-within-ruby-script

require 'fileutils'
require 'rbconfig'

#---------------------------------------------------------------------------------------------------

module RESTafy
    @@base = File.expand_path(File.dirname(__FILE__))

    # Properties.

    def self.libdir
        @@lib ||= File.join @@base, 'lib'
    end

    def self.blibdir
        @@blib ||= File.join @@base, 'blib'
    end

    def self.ini
        @@ini ||= File.join @@base, 'etc', 'php.ini'
    end

    def self.ini_dbg
        @@ini_dbg ||= File.join @@base, 'etc', 'php-dbg.ini'
    end

    def self.logfile
        @@logfile ||= File.join self::tmpdir, 'php.log'
    end

    def self.tmpdir
        @@tmp ||= File.join @@base, 'tmp'
    end

    def self.php_log
        @@php_log ||= abspath(logfile)
    end

    def self.php_blibdir
      @@php_blibdir ||= abspath(blibdir)
    end

    def self.php_libdir
      @@php_libdir ||= abspath(libdir)
    end

    def self.php_ini
      @@php_ini ||= abspath(ini)
    end

    def self.php_ini_dbg
      @@php_ini_dbg ||= abspath(ini_dbg)
    end

    # Core methods.

    def self.init()
      Dir.mkdir(tmpdir)        unless File.exists?(tmpdir)
      FileUtils.touch(logfile) unless File.exists?(logfile)
    end

    def self.exec()
        if ARGV.empty? then
            raise 'ARGV can not be empty.'
        end

        Kernel.exec(build_cmd(ARGV, false, true).to_s())
    end

    def self.prove(dir, blib)
        Kernel.exec(prove_cmd(dir, blib).to_s())
    end

    def self.test(file)
        Kernel.exec(test_cmd(file).to_s())
    end

    def self.build_cmd(argv, blib, debug)
        cmd = PHPCmd.new
        cmd.argv = argv
        cmd.ini('include_path', blib ? php_blibdir : php_libdir)
        cmd.ini('error_log', php_log)
        cmd.opt('-c', debug ? php_ini_dbg : php_ini)
        return cmd
    end

    def self.prove_cmd(dir, blib)
        argv = [File.join('libexec', 'prove.php'), dir]
        return build_cmd(argv, blib, false)
    end

    def self.test_cmd(file)
        argv = [File.join('libexec', 'runtest.php'), file]
        return build_cmd(argv, false, false)
    end

    def self.abspath(*args)
        path = args.length == 1 ? args[0] : File.join(args)

        # Cf. http://www.cygwin.com/cygwin-ug-net/using-utils.html#cygpath
        # NB: There is a problem in cygwin whith paths containing a tilde (short-name DOS style).
        # Nevertheless when the file already exists, the problem disappears.
        self::is_cygwin? ? %x(cygpath -law -- "#{path}").strip! : path
    end

    def self.is_cygwin?
        @@is_cygwin ||= (
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

class PHPCmd
    @@exe = '/usr/bin/env php'

    attr_writer :argv

    def initialize
        @argv = []
        @opts = {}
        @ini  = {}
    end

    def self.exe=(php)
        @@exe = php
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

        "#{@@exe} #{args}"
    end
end

#---------------------------------------------------------------------------------------------------

# EOF
