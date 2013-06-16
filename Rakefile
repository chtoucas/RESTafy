require 'fileutils'
require 'rake'
require 'rake/packagetask'
require_relative 'RESTafy'

PKG_VERSION = '0.1.0'

RESTafyEnv::prepare
restafy = RESTafy.new

task :default   => ['test:lib']

namespace :test do
    task :lib do
        restafy.prove('t', false)
    end

    task :blib do
        restafy.prove('t', true)
    end

    task :samples do
        restafy.prove('samples', false)
    end
end

# Always run this task, whatever happens.
#Rake::Task['init'].invoke
