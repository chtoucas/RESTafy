require 'fileutils'
require 'rake'
require 'rake/packagetask'
require_relative 'RESTafy'

PKG_VERSION = '0.1.0'

task :default   => ['test:lib']

task :init do
    RESTafy::init
end

namespace :test do
    task :lib do
        RESTafy::prove('t', false)
    end

    task :blib do
        RESTafy::prove('t', true)
    end

    task :samples do
        RESTafy::prove('samples', false)
    end
end

# Always run this task, whatever happens.
Rake::Task['init'].invoke
