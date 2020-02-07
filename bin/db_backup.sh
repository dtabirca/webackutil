#!/bin/bash

# windows gitbash test
mysqldumpexe="D:\xampp\mysql\bin\mysqldump.exe"
#mysqldumpexe="mysqldump"

BACKUP_FOLDER=$1
WEBSITE=$2
DB_HOST=$3
DB_PORT=$4
DB_USER=$5
DB_PASS=$6
NOW=$(date +"%m-%d-%Y_%H%M%S")
BKNAME="${WEBSITE}.${NOW}.backup.sql"
if [ -z "$DB_PASS" ]
then
	$mysqldumpexe --add-drop-table --routines -h $DB_HOST -P $DB_PORT -u $DB_USER "$WEBSITE" > "${BACKUP_FOLDER}${BKNAME}"
else
	$mysqldumpexe --add-drop-table --routines -h $DB_HOST -P $DB_PORT -u $DB_USER -p"$DB_PASS" "$WEBSITE" > "${BACKUP_FOLDER}${BKNAME}"
fi	
cd ${BACKUP_FOLDER}
if test -f "$BKNAME"; then
	tar cfzv "${BKNAME}.tar.gz" $BKNAME
	rm $BKNAME
else
	echo Cannot create archive
fi