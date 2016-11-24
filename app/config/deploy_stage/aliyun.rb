
default_run_options[:pty] = true
ssh_options[:forward_agent] = true

server '114.55.225.154', :app, :web, :primary => true

set :branch,      "master"
set :user,      "work"

set :deploy_to,   "/data/www/monitor"
