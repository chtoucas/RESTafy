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

        system(build_cmd(ARGV, false, true).to_s)
    end

    def prove(dir, blib)
        system(prove_cmd(dir, blib).to_s)
    end

    def test(file)
        system(test_cmd(file).to_s)
    end

    def lint
        # php -l
        pattern = @env.lib_dir + '/**/*.php'
        Dir.glob(pattern) do |file|
            system(lint_cmd(file, false).to_s)
        end
    end

    def blib
        # php -w
        puts "TODO"
    end

    protected

    def build_cmd(argv, blib, debug)
        cmd = PHPCmd.new
        cmd.argv = argv
        cmd.ini('include_path', blib ? blib_dir : lib_dir)
        cmd.ini('error_log', log_file)
        cmd.opt('-c', debug ? ini_dbg : ini)
        return cmd
    end

    def prove_cmd(dir, blib)
    	exe = File.join @env.libexec_dir, 'prove.php'
        argv = [quoted_path(exe), dir]
        return build_cmd(argv, blib, false)
    end

    def test_cmd(file)
    	exe = File.join @env.libexec_dir, 'runtest.php'
        argv = [quoted_path(exe), file]
        return build_cmd(argv, false, false)
    end

    def lint_cmd(file, blib)
        return build_cmd(['-l', quoted_path(file)], blib, false)
    end

    private

    def blib_dir
        @blib_dir ||= path(@env.blib_dir)
    end

    def ini
        @ini ||= path(@env.ini)
    end

    def ini_dbg
        @ini_dbg ||= path(@env.ini_dbg)
    end

    def lib_dir
        @lib_dir ||= path(@env.lib_dir)
    end

    def log_file
        @log_file ||= path(@env.log_file)
    end

    def quoted_path(path)
	'"' + path(path) + '"'
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
        Dir.mkdir(inst.tmp_dir) unless File.exists?(inst.tmp_dir)
        if inst.is_cygwin? then
            FileUtils.touch(inst.log_file) unless File.exists?(inst.log_file)
        end
    end

    # Project directories.

    def blib_dir
        @blib_dir ||= File.join @base, 'blib'
    end

    def etc_dir
        @etc_dir ||= File.join @base, 'etc'
    end

    def lib_dir
        @lib_dir ||= File.join @base, 'lib'
    end

    def libexec_dir
        @libexec_dir ||= File.join @base, 'libexec'
    end

    def tmp_dir
        @tmp_dir ||= File.join @base, 'tmp'
    end

    def ini
        @ini ||= File.join etc_dir, 'php.ini'
    end

    def ini_dbg
        @ini_dbg ||= File.join etc_dir, 'php-dbg.ini'
    end

    def log_file
        @log_file ||= File.join tmp_dir, 'php.log'
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
