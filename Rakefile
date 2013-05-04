require 'fileutils'
require 'rake'
require 'rake/packagetask'

PKG_VERSION = '0.1.0'

PROJECT_DIR = File.expand_path(File.dirname(__FILE__))
LIB_DIR     = File.join(PROJECT_DIR, 'lib')
BLIB_DIR    = File.join(PROJECT_DIR, 'blib')

task :default   => ['test:lib']

namespace :test do
  task :lib do
    #`prove -r --ext=.phpt --exec 'php -n -c ./etc/phpt.ini -f' t/`
  end

  task :blib do
  end
end

