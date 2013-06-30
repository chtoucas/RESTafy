# http://tomdoc.org/
# https://github.com/bbatsov/ruby-style-guide#percent-literals

require 'fileutils'
require 'open3'
require 'rbconfig'
require 'singleton'

#---------------------------------------------------------------------------------------------------

module RESTafy

  module Term

    module Colors
      def self.red(text);      "\e[31m#{text}\e[0m" end
      def self.green(text);    "\e[32m#{text}\e[0m" end
      def self.yellow(text);   "\e[33m#{text}\e[0m" end
      def self.magenta(text);  "\e[35m#{text}\e[0m" end
      def self.cyan(text);     "\e[36m#{text}\e[0m" end
    end

    # Confess something to the user.
    def confess(text)
      puts Colors.cyan(text)
    end

    # Warn of errors.
    def warn(text)
      puts Colors.red(text)
    end

    # Die of errors.
    def croak(text)
      puts Colors.red(text)
      exit(1)
    end

    # Success message.
    def bless(text)
      puts Colors.green(text)
    end

    module_function :confess
    module_function :warn
    module_function :croak
    module_function :bless
  end

  class Tasks
    include Term

    def initialize(env = nil)
      @env = env || Env.instance
      @factory = CmdFactory.new(@env)
    end

    def clean_env
      confess 'clean_env() not yet implemented.'
    end

    def prepare_env
      FileUtils.mkdir_p(@env.tmp_dir) unless File.exists?(@env.tmp_dir)
      if @env.is_cygwin?
        # Under cygwin, unless the file already exists, paths containing a tilde (short-name DOS
        # style) are not expanded.
        FileUtils.touch(@env.log_file) unless File.exists?(@env.log_file)
      end
    end

    def prove(dir, blib)
      @factory.prove_cmd(dir, blib).system()
    end

    def lint_dir(dir)
      errs = []
      pattern = File.join(dir, '**', '*.php')
      # Iterate over all PHP files and lint them.
      Dir.glob(pattern) do |file|
        errs << file unless lint(file)
        print '.'
      end
      puts ''
      if errs.length == 0
        bless 'No syntax errors detected.'
      elsif errs.length == 1
        warn %q{There is 1 malformed file: "%s".} % errs[0]
      else
        warn 'There are %s malformed files:' % errs.length
        errs.each { |err| warn '  %s' % err }
      end
    end

    def lint(file)
      cmd = @factory.lint_cmd(file)
      stdout, stderr, status = cmd.capture()
      status.success? && stdout.chomp! =~ /^No syntax errors detected in/
    end

    def blib
      # php -w
      confess 'blib() not yet implemented.'
    end
  end

  

  class CmdFactory
    def initialize(env)
      @env = env
    end

    def prove_cmd(dir, blib)
      php_cmd [libpath('prove.php'), dir], blib, false
    end

    def runtest_cmd(file, blib)
      php_cmd [libpath('runtest.php'), file], blib, false
    end

    def lint_cmd(file)
      PHPLint.new(@env.php_exe, quoted_path(file))
    end

    def strip_cmd(file)
      PHPStrip.new(@env.php_exe, quoted_path(file))
    end

    def php_cmd(argv, blib, debug)
      cmd = PHPCmd.new(@env.php_exe)
      cmd.argv = argv
      cmd.include_path = blib ? blib_dir : lib_dir
      cmd.error_log = log_file
      cmd.ini = debug ? ini_dbg : ini
      return cmd
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

    def libpath(libexec)
      path = File.join(@env.libexec_dir, libexec)
      quoted_path(path)
    end

    def path(path)
      # Cf. http://www.cygwin.com/cygwin-ug-net/using-utils.html#cygpath
      @env.is_cygwin? ? %x{cygpath -law -- "#{path}"}.chomp! : path
    end
  end

  

  class Env
    include Singleton

    attr_reader :base_dir
    attr_accessor :php_exe

    def initialize
      @base_dir = File.expand_path(File.dirname(__FILE__))
      @php_exe  = '/usr/bin/env php'
    end

    def blib_dir;     @blib_dir     ||= File.join(base_dir, '_blib') end
    def build_dir;    @build_dir    ||= File.join(base_dir, '_build') end
    def etc_dir;      @etc_dir      ||= File.join(base_dir, 'etc') end
    def lib_dir;      @lib_dir      ||= File.join(base_dir, 'lib') end
    def libexec_dir;  @libexec_dir  ||= File.join(base_dir, 'libexec') end
    def tmp_dir;      @tmp_dir      ||= File.join(base_dir, 'tmp') end
    def ini;          @ini          ||= File.join(etc_dir, 'php.ini') end
    def ini_dbg;      @ini_dbg      ||= File.join(etc_dir, 'php-dbg.ini') end
    def log_file;     @log_file     ||= File.join(tmp_dir, 'php.log') end

    def is_cygwin?
      @is_cygwin ||= RbConfig::CONFIG['host_os'] == 'cygwin'
    end
  end

  

  module ForeignCmd
    # http://mentalized.net/journal/2010/03/08/5_ways_to_run_commands_from_ruby/
    # http://stackoverflow.com/questions/3159945/running-command-line-commands-within-ruby-script

    # Completely replace the current process: stderr & stdout output.
    def exec
      Kernel.exec(self.to_s())
    end

    # Execute in a subshell: stderr output, stdout captured.
    def backticks
      %x(self.to_s())
    end

    # stderr & stdout captured.
    def open
      Open3.popen3(self.to_s())
    end

    # stderr & stdout captured.
    def capture
      Open3.capture3(self.to_s())
    end

    # Execute in a subshell: stderr & stdout output.
    def system
      Kernel.system(self.to_s())
    end
  end

  

  class PHPCmd
    include ForeignCmd

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

    def to_s
      # Arguments passed to the PHP interpreter.
      opts = @opts.map { |k, v| %q{%s "%s"} % [k, v] }
      # INI settings.
      ini  = @ini.map { |k, v| %q{-d "%s=%s"} % [k, v] }

      args = (opts << ini << @argv).join(' ')

      %{#{@exe} #{args}}
    end

    protected

    def ini(key, value)
      @ini[key] = value
    end

    def opt(key, value)
      @opts[key] = value
    end
  end

  class PHPLint < PHPCmd
    def initialize(exe, file)
      super(exe)
      @argv << '-l' << file
    end
  end

  class PHPStrip < PHPCmd
    def initialize(exe, file)
      super(exe)
      @argv << '-w' << file
    end
  end
end

# EOF
