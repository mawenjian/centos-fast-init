#!/bin/bash
#       /etc/rc.d/init.d/stunnel
#
# Starts the stunnel daemon
#
# chkconfig: 345 70 30
# description: Stunnel Server is a ...
# processname: stunnel
# config: /etc/stunnel/stunnel.conf

# Source function library.
. /etc/init.d/functions

test -x /usr/sbin/stunnel || exit 0
RETVAL=0
#
#       See how we were called.
#
prog="stunnel"
start() {
    # Check if stunnel is already running
    if [ ! -f /var/lock/subsys/stunnel ]; 
    then
    echo -n $"Starting $prog: "
    daemon /usr/sbin/stunnel
    RETVAL=$?
    [ $RETVAL -eq 0 ] && touch /var/lock/subsys/stunnel
    echo
    fi
    return $RETVAL
}
stop() {
    echo -n $"Stopping $prog: "
    killproc /usr/sbin/stunnel
    RETVAL=$?
    [ $RETVAL -eq 0 ] && rm -f /var/lock/subsys/stunnel
    echo
    return $RETVAL
}
restart() {
    stop
    start
}
reload() {
    restart
}
status() {
    status /usr/sbin/stunnel
}
case "$1" in
start)
    start
    ;;
stop)
    stop
    ;;
reload|restart)
    restart
    ;;
status)
    status
    ;;
*)
    echo $"Usage: $0 {start|stop|restart|reload|status}"
    exit 1
esac
exit $?
exit $RETVAL
