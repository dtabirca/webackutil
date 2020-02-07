#!/bin/bash

mysqlexe="D:\xampp\mysql\bin\mysql.exe"

BACKUP_FOLDER=$1
ARCHIVE=$2
WEBSITE=$3
DB_HOST=$4
DB_PORT=$5
DB_USER=$6
DB_PASS=$7
BACKUPF=${ARCHIVE::-7}

cd $BACKUP_FOLDER
if test -f "$ARCHIVE"; then
	tar -xvzf $ARCHIVE $BACKUPF
else
	echo Cannot extract from archive
fi

if [ -z "$DB_PASS" ]
then
	$mysqlexe -h $DB_HOST -P $DB_PORT -u $DB_USER "$WEBSITE" < $BACKUPF
else
	$mysqlexe -h $DB_HOST -P $DB_PORT -u $DB_USER -p"$DB_PASS" "$WEBSITE" < $BACKUPF
fi

rm $BACKUPF 