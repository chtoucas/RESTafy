require 'rake'

BEGIN {
  require_relative 'RESTafy'

  $tasks = RESTafy::Tasks.new()
}

PKG_VERSION = '0.1.0'

task :default   => ['test:lib']

task :init do
  $tasks.prepare_env()
end

task :blib do
  $tasks.blib()
end

task :lint do
  $tasks.lint_dir('lib')
end

namespace :test do
  task :lib do
    $tasks.prove('t', false)
  end

  task :blib do
    $tasks.prove('t', true)
  end

  task :samples do
    $tasks.prove('samples', false)
  end

  #task :prove do
  #  prove -r --ext=.phpt --exec 'php -n -c ./etc/phpt.ini -d include_path=./lib -f' t/
  #end
end

# Before anything, run this task.
Rake::Task['init'].invoke

# EOF
