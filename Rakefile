require 'fileutils'
require 'rake'
require 'rake/packagetask'
require_relative 'RESTafy'

PKG_VERSION = '0.1.0'

restafy = RESTafy.new

task :default   => ['test:lib']

task :init do
    RESTafyEnv::prepare
end

task :blib do
    restafy.blib
end

task :lint do
    restafy.lint
end

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

#    task :prove do
#    	prove -r --ext=.phpt --exec 'php -n -c ./etc/phpt.ini -d include_path=./lib -f' t/
#    end
end

# Always run this task, whatever happens.
Rake::Task['init'].invoke
