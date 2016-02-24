HOSTS=“aliyun238057 aliyun238060 aliyun238229 aliyun238233 aliyun243436 aliyun243456 aliyun246587 aliyun246600 aliyun281325 aliyun317429 aliyun317433 aliyun317450 aliyun317457 aliyun317467 aliyun317471 aliyun347134 aliyun347135 aliyun347137 aliyun364963 aliyun364965 aliyun364967 aliyun364968"

for HOSTNAME in ${HOSTS} ; do
 ssh ${HOSTNAME} 'mkdir -p "/usr/local/lsyncd-2.1.5";touch lsyncd.conf;cd /usr/local/lsyncd-2.1.5/1;echo "settings {
    logfile      ="/usr/local/lsyncd-2.1.5/var/lsyncd.log",
    statusFile   ="/usr/local/lsyncd-2.1.5/var/lsyncd.status",
    inotifyMode  = "CloseWrite",
    maxProcesses = 7,
    }
sync {
    default.rsyncssh,
    source    = "/ace/log/iqg",
    host      = "work@27.115.51.166",
    targetdir = "/data/logs/iqg-api/111/$HOSTNAME",
    -- excludeFrom = "/etc/rsyncd.d/rsync_exclude.lst",
    -- maxDelays = 5,
    delay = 0,
    -- init = false,
    rsync    = {
        binary = "/usr/bin/rsync",
        archive = true,
        compress = true,
        verbose   = true,
        _extra = {"--bwlimit=2000"},
        },
    ssh = {
        port  =  5822
        }
    }
" > lsyncd.conf '
done

