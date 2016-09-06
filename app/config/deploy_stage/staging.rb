set :branch,      "master"
server "work@27.115.51.166:5822", :app, :web, :db, :primary => true, port: 443
set :deploy_to,   "/data/app/staging.monitorcenter.lab"
