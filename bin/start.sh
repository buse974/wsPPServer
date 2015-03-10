#!/bin/bash

HOST=""
PORT=""

while getopts "h:p:" option
do
    case $option in
        h)
            HOST="-h "$OPTARG
        ;;
        p)
            PORT="-p "$OPTARG
        ;;	
    esac
done

DIR=`dirname "$(realpath $0)"`
php $DIR/run.php $HOST $PORT
