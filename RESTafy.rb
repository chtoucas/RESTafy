# http://mentalized.net/journal/2010/03/08/5_ways_to_run_commands_from_ruby/
# http://stackoverflow.com/questions/3159945/running-command-line-commands-within-ruby-script

require 'fileutils'
require 'open3'
require 'rbconfig'
require 'singleton'

#---------------------------------------------------------------------------------------------------

class RESTafy
    def initialize(env = nil)
        @env = env || RESTafyEnv.instance
    end

    # Tasks

    def prove(dir, blib)
        system prove_cmd(dir, blib).to_s
    end

    def lint_dir(dir)
        errs = []
        pattern = File.join(dir, '**', '*.php')
        Dir.glob(pattern) do |file|
            errs << file unless lint(file)
            print '.'
        end
        puts ''
        if errs.length == 0 then
            RESTafy::success 'No syntax errors detected.'
        elsif errs.length == 1 then
            RESTafy::warn %q{There is 1 malformed file: "%s"} % errs[0]
        else
            RESTafy::warn 'There are %s malformed files:' % errs.length
            errs.each { |err| RESTafy::warn '  %s' % err }
        end
    end

    def lint(file)
        stdin, stdout, stderr = Open3.popen3 lint_cmd(file, false).to_s
        stdout.readlines[0].chomp! =~ %r{^No syntax errors detected in}
    end

    def blib
        # php -w
        puts "TODO"
    end

    def build_cmd(argv, blib, debug)
        cmd = PHPCmd.new(@env.php_exe)
        cmd.argv = argv
        cmd.include_path = blib ? blib_dir : lib_dir
        cmd.error_log = log_file
        cmd.ini = debug ? ini_dbg : ini
        return cmd
    end

    protected

    def self.warn(text);    puts "\033[31m#{text}\033[0m" end
    def self.success(text); puts "\033[32m#{text}\033[0m" end

    def prove_cmd(dir, blib)
        exe = File.join(@env.libexec_dir, 'prove.php')
        build_cmd [quoted_path(exe), dir], blib, false
    end

    def test_cmd(argv)
        exe = File.join(@env.libexec_dir, 'runtest.php')
        build_cmd [quoted_path(exe)].push(argv), false, false
    end

    def lint_cmd(file, blib)
        PHPLint.new(@env.php_exe, quoted_path(file))
    end

    def strip_cmd(file, blib)
        PHPStrip.new(@env.php_exe, quoted_path(file))
    end

    private

    def blib_dir; @blib_dir ||= path(@env.blib_dir) end
    def ini;      @ini      ||= path(@env.ini) end
    def ini_dbg;  @ini_dbg  ||= path(@env.ini_dbg) end
    def lib_dir;  @lib_dir  ||= path(@env.lib_dir) end
    def log_file; @log_file ||= path(@env.log_file) end

    def quoted_path(path)
        %q{"} + path(path) + %q{"}
    end

    def path(path)
        # Cf. http://www.cygwin.com/cygwin-ug-net/using-utils.html#cygpath
        # NB: There is a problem in cygwin whith paths containing a tilde (short-name DOS style).
        # Nevertheless when the file already exists, the problem disappears.
        @env.is_cygwin? ? %x{cygpath -law -- "#{path}"}.chomp! : path
    end
end


#---------------------------------------------------------------------------------------------------

class RESTafyEnv
    include Singleton

    def initialize
        @base_dir = File.expand_path(File.dirname(__FILE__))
        @php_exe  = '/usr/bin/env php'
    end

    def self.prepare
        inst = self.instance
        Dir.mkdir(inst.tmp_dir) unless File.exists?(inst.tmp_dir)
        if inst.is_cygwin? then
            FileUtils.touch(inst.log_file) unless File.exists?(inst.log_file)
        end
    end

    def php_exe;      @php_exe end
    def base_dir;     @base_dir end

    def blib_dir;     @blib_dir     ||= File.join(@base_dir, 'blib') end
    def etc_dir;      @etc_dir      ||= File.join(@base_dir, 'etc') end
    def lib_dir;      @lib_dir      ||= File.join(@base_dir, 'lib') end
    def libexec_dir;  @libexec_dir  ||= File.join(@base_dir, 'libexec') end
    def tmp_dir;      @tmp_dir      ||= File.join(@base_dir, 'tmp') end
    def ini;          @ini          ||= File.join(etc_dir, 'php.ini') end
    def ini_dbg;      @ini_dbg      ||= File.join(etc_dir, 'php-dbg.ini') end
    def log_file;     @log_file     ||= File.join(tmp_dir, 'php.log') end

    def is_cygwin?
        @is_cygwin ||= RbConfig::CONFIG['host_os'] =~ %r{cygwin}
    end
end


#---------------------------------------------------------------------------------------------------

class PHPCmd
    attr_writer :argv

    def initialize(exe)
        @exe  = exe
        @argv = []
        @opts = {}
        @ini  = {}
    end

    def include_path=(path)
        ini 'include_path', path
    end

    def error_log=(path)
        ini 'error_log', path
    end

    def ini=(path)
        opt '-c', path
    end

    def ini(key, value)
        @ini[key] = value
    end

    def opt(key, value)
        @opts[key] = value
    end

    def to_s
        # Arguments passed to the PHP interpreter.
        opts = @opts.map { |k, v| %q{%s "%s"} % [k, v] }
        # INI settings.
        ini  = @ini.map { |k, v| %q{-d "%s=%s"} % [k, v] }

        args = (opts << ini << @argv).join(' ')

        %Q{#{@exe} #{args}}
    end
end

class PHPLint < PHPCmd
    def initialize(exe, file)
        super(exe)
        @file = file
        @argv << '-l' << file
    end
end

class PHPStrip < PHPCmd
    def initialize(exe, file)
        super(exe)
        @file = file
        @argv << '-w' << file
    end
end

#---------------------------------------------------------------------------------------------------

# EOF
