require 'fileutils'
require 'rake'
require 'rake/packagetask'

PKG_VERSION = '0.1.0'

BEGIN {
    require_relative 'RESTafy'

    $restafy = RESTafy.new
}

task :default   => ['test:lib']

task :init do
    RESTafyEnv::prepare
end

task :blib do
    $restafy.blib
end

task :lint do
    $restafy.lint_dir 'lib'
end

namespace :test do
    task :lib do
        $restafy.prove 't', false
    end

    task :blib do
        $restafy.prove 't', true
    end

    task :samples do
        $restafy.prove 'samples', false
    end

    #task :prove do
    #    prove -r --ext=.phpt --exec 'php -n -c ./etc/phpt.ini -d include_path=./lib -f' t/
    #end
end

# Before anything, run this task.
Rake::Task['init'].invoke

# EOF
