#!/bin/bash
# openoffice.org headless server script
#
# chkconfig: 2345 80 30
# description: headless openoffice server script
# processname: openoffice
#
# Author: Christophe Robert
#
DIR=`dirname "$(readlink -f $0)"`
 
WSSEVER="php "$DIR"/run.php"
OPTIONS='-p 5432'
ERROR=1
PIDFILE=/var/run/wsocket-server.pid
set -e

start() {
        if [ -f $PIDFILE ] && [ -e /proc/`cat $PIDFILE` ]; then
                echo "Web Socket server has already started."
        else
                if [ -f $PIDFILE ] && [ ! -e /proc/`cat $PIDFILE` ]; then
                        rm -f $PIDFILE
                fi

                $WSSEVER $OPTIONS & > /dev/null 2>&1
                if [ $? = 0 ]; then
                echo $! > $PIDFILE
                fi
                ERROR=$?
        fi
}

stop() {
	if [ -f $PIDFILE ]; then
		echo "Stopping Web Socket server."
		kill -TERM `cat $PIDFILE`
		rm -f $PIDFILE
	fi
}

case "$1" in
	start)
		start
		;;
	stop)
		stop
		;;
	restart|reload)
        	stop
        	start
        ;;
  	*)
        	echo $"Usage: $0 {start|stop|restart|reload}"
        	ERROR=1
	esac
exit $ERROR
