# http://mentalized.net/journal/2010/03/08/5_ways_to_run_commands_from_ruby/
# http://stackoverflow.com/questions/3159945/running-command-line-commands-within-ruby-script

require 'fileutils'
require 'rbconfig'
require 'singleton'

#---------------------------------------------------------------------------------------------------

class RESTafy
    def initialize(env = nil)
        @env = env || RESTafyEnv.instance
    end

    def exec()
        if ARGV.empty? then
            raise 'ARGV can not be empty.'
        end

        Kernel.exec(build_cmd(ARGV, false, true).to_s())
    end

    def prove(dir, blib)
        Kernel.exec(prove_cmd(dir, blib).to_s())
    end

    def test(file)
        Kernel.exec(test_cmd(file).to_s())
    end

    protected

    def build_cmd(argv, blib, debug)
        cmd = PHPCmd.new
        cmd.argv = argv
        cmd.ini('include_path', blib ? blibdir : libdir)
        cmd.ini('error_log', log)
        cmd.opt('-c', debug ? ini_dbg : ini)
        return cmd
    end

    def prove_cmd(dir, blib)
        argv = [File.join('libexec', 'prove.php'), dir]
        return build_cmd(argv, blib, false)
    end

    def test_cmd(file)
        argv = [File.join('libexec', 'runtest.php'), file]
        return build_cmd(argv, false, false)
    end

    private

    def log
        @log ||= path(@env.logfile)
    end

    def blibdir
        @blibdir ||= path(@env.blibdir)
    end

    def libdir
        @libdir ||= path(@env.libdir)
    end

    def ini
        @ini ||= path(@env.ini)
    end

    def ini_dbg
        @ini_dbg ||= path(@env.ini_dbg)
    end

    def path(path)
        # Cf. http://www.cygwin.com/cygwin-ug-net/using-utils.html#cygpath
        # NB: There is a problem in cygwin whith paths containing a tilde (short-name DOS style).
        # Nevertheless when the file already exists, the problem disappears.
        @env.is_cygwin? ? %x(cygpath -law -- "#{path}").strip! : path
    end
end


#---------------------------------------------------------------------------------------------------

class RESTafyEnv
    include Singleton

    def initialize
        @base = File.expand_path(File.dirname(__FILE__))
    end

    def self.prepare
        inst = self.instance
        Dir.mkdir(inst.tmpdir) unless File.exists?(inst.tmpdir)
        if inst.is_cygwin? then
            FileUtils.touch(inst.logfile) unless File.exists?(inst.logfile)
        end
    end

    def libdir
        @lib ||= File.join @base, 'lib'
    end

    def blibdir
        @blib ||= File.join @base, 'blib'
    end

    def ini
        @ini ||= File.join @base, 'etc', 'php.ini'
    end

    def ini_dbg
        @ini_dbg ||= File.join @base, 'etc', 'php-dbg.ini'
    end

    def logfile
        @logfile ||= File.join tmpdir, 'php.log'
    end

    def tmpdir
        @tmp ||= File.join @base, 'tmp'
    end

    def is_cygwin?
        @is_cygwin ||= (
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
