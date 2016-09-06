set :application, "monitor.iqg.com"

default_run_options[:pty] = true
ssh_options[:forward_agent] = true

set :app_path,    "app"

set :repository,  "git@github.com:iqg/iqianggou-monitor.git"
set :branch,      "master"
set :scm,         :git
set :deploy_to,   "/data/app/monitorcenter"

set :normalize_asset_timestamps, false
set :linked_dirs, ""
set :shared_children,     [app_path + "/logs"]
set :shared_files, ['app/config/parameters.yml']
set :use_sudo, false
set :file_permissions_paths,  [fetch(:linked_dirs)]

set  :deploy_via, :remote_cache

set :model_manager, "doctrine"

set  :keep_releases,  10

# Be more verbose by uncommenting the following line
logger.level = Logger::MAX_LEVEL

set :stages,        %w(dev staging online aliyun)
set :default_stage, "dev"
set :stage_dir,     "app/config/deploy_stage"

require 'capistrano/ext/multistage'

namespace :deploy do
    task :restart, :roles => :app, :except => { :no_release => true } do
        run "mkdir #{release_path}/app/cache/"
        run "chmod -R 777 #{release_path}/app/cache/"
        run "ln -s #{deploy_to}/shared/config/parameters.yml #{release_path}/app/config/parameters.yml"
        run "rm    #{release_path}/src/DWD/DataBundle/Command/iqg_analyse_log/config.ini"
        run "rm    #{release_path}/src/DWD/DataBundle/Command/hsq_analyse_log/hsq-internalapi/config.ini"
        run "rm    #{release_path}/src/DWD/DataBundle/Command/hsq_analyse_log/hsq-openapi/config.ini"
        run "ln -s #{deploy_to}/shared/config.ini #{release_path}/src/DWD/DataBundle/Command/iqg_analyse_log/config.ini"
        run "ln -s #{deploy_to}/shared/hsq-internalapi/config.ini #{release_path}/src/DWD/DataBundle/Command/hsq_analyse_log/hsq-internalapi/config.ini"
        run "ln -s #{deploy_to}/shared/hsq-openapi/config.ini #{release_path}/src/DWD/DataBundle/Command/hsq_analyse_log/hsq-openapi/config.ini"
        run "ln -s #{deploy_to}/shared/web/uploads #{release_path}/web/upload"
        run "cp -r #{deploy_to}/shared/vendor/ #{release_path}/"
        run "php #{release_path}/vendor/sensio/distribution-bundle/Sensio/Bundle/DistributionBundle/Resources/bin/build_bootstrap.php"
        run "rm -rf #{release_path}/.git #{release_path}/.gitignore #{release_path}/READNE.md"
        run "supervisorctl -c /etc/supervisord/supervisord.conf restart php-fpm"
        run "php #{release_path}/app/console assets:install #{release_path}/web/ --symlink"
    end
end
