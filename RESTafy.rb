# http://mentalized.net/journal/2010/03/08/5_ways_to_run_commands_from_ruby/
# http://stackoverflow.com/questions/3159945/running-command-line-commands-within-ruby-script

require 'fileutils'
require 'open3'
require 'rbconfig'
require 'singleton'
require_relative 'etc/Config'

#---------------------------------------------------------------------------------------------------

module RESTafy

  class Tasks
    def initialize
      @factory = CmdFactory.new()
    end

    def prepare
      Dir.mkdir(Env::tmp_dir) unless File.exists?(Env::tmp_dir)
      if Env::is_cygwin? then
        FileUtils.touch(Env::log_file) unless File.exists?(Env::log_file)
      end
    end

    def prove(dir, blib)
      cmd = @factory.prove_cmd(dir, blib)
      system(cmd.to_s())
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
        Tasks::success 'No syntax errors detected.'
      elsif errs.length == 1 then
        Tasks::warn %q{There is 1 malformed file: "%s"} % errs[0]
      else
        Tasks::warn 'There are %s malformed files:' % errs.length
        errs.each { |err| Tasks::warn '  %s' % err }
      end
    end

    def lint(file)
      cmd = @factory.lint_cmd(file)
      stdin, stdout, stderr = Open3.popen3(cmd.to_s())
      stdout.readlines[0].chomp! =~ %r{^No syntax errors detected in}
    end

    def blib
      # php -w
      puts "TODO"
    end

    protected

    def self.warn(text);    puts "\033[31m#{text}\033[0m" end
    def self.success(text); puts "\033[32m#{text}\033[0m" end
  end

  

  class CmdFactory
    def prove_cmd(dir, blib)
      exe = File.join(Env::libexec_dir, 'prove.php')
      php_cmd [quoted_path(exe), dir], blib, false
    end

    def test_cmd(argv)
      exe = File.join(Env::libexec_dir, 'runtest.php')
      php_cmd [quoted_path(exe)].push(argv), false, false
    end

    def lint_cmd(file)
      PHPLint.new(Env::php_exe, quoted_path(file))
    end

    def strip_cmd(file)
      PHPStrip.new(Env::php_exe, quoted_path(file))
    end

    def php_cmd(argv, blib, debug)
      cmd = PHPCmd.new(Env::php_exe)
      cmd.argv = argv
      cmd.include_path = blib ? blib_dir : lib_dir
      cmd.error_log = log_file
      cmd.ini = debug ? ini_dbg : ini
      return cmd
    end

    private

    def blib_dir; @blib_dir ||= path(Env::blib_dir) end
    def ini;      @ini      ||= path(Env::ini) end
    def ini_dbg;  @ini_dbg  ||= path(Env::ini_dbg) end
    def lib_dir;  @lib_dir  ||= path(Env::lib_dir) end
    def log_file; @log_file ||= path(Env::log_file) end

    def quoted_path(path)
      %q{"} + path(path) + %q{"}
    end

    def path(path)
      # Cf. http://www.cygwin.com/cygwin-ug-net/using-utils.html#cygpath
      # NB: There is a problem in cygwin whith paths containing a tilde (short-name DOS style).
      # Nevertheless when the file already exists, the problem disappears.
      Env::is_cygwin? ? %x{cygpath -law -- "#{path}"}.chomp! : path
    end
  end

  

  module Env
    def self.php_exe;      Config::PHP_EXE end
    def self.base_dir;     Config::BASE_DIR end

    def self.blib_dir;     @@blib_dir     ||= File.join(self::base_dir, '_blib') end
    def self.build_dir;    @@build_dir    ||= File.join(self::base_dir, '_build') end
    def self.etc_dir;      @@etc_dir      ||= File.join(self::base_dir, 'etc') end
    def self.lib_dir;      @@lib_dir      ||= File.join(self::base_dir, 'lib') end
    def self.libexec_dir;  @@libexec_dir  ||= File.join(self::base_dir, 'libexec') end
    def self.tmp_dir;      @@tmp_dir      ||= File.join(self::base_dir, 'tmp') end
    def self.ini;          @@ini          ||= File.join(self::etc_dir, 'php.ini') end
    def self.ini_dbg;      @@ini_dbg      ||= File.join(self::etc_dir, 'php-dbg.ini') end
    def self.log_file;     @@log_file     ||= File.join(self::tmp_dir, 'php.log') end

    def self.is_cygwin?
      @@is_cygwin ||= RbConfig::CONFIG['host_os'] =~ %r{cygwin}
    end
  end

  

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

    def to_s
      # Arguments passed to the PHP interpreter.
      opts = @opts.map { |k, v| %q{%s "%s"} % [k, v] }
      # INI settings.
      ini  = @ini.map { |k, v| %q{-d "%s=%s"} % [k, v] }

      args = (opts << ini << @argv).join(' ')

      %Q{#{@exe} #{args}}
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
