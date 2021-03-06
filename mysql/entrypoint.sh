#!/bin/bash
set -e

DATADIR="$(mysqld --verbose --help 2>/dev/null | awk '$1 == "datadir" { print $2; exit }')"

tempSqlFile='/tmp/mysql-first-time.sql'
backupSqlFile='/docker-entrypoint-initdb.d/backup.sql'
if [ ! -d "$DATADIR/mysql" ]; then
    if [ -z "$MYSQL_ROOT_PASSWORD" -a -z "$MYSQL_ALLOW_EMPTY_PASSWORD" ]; then
        echo >&2 'ERROR: Database is uninitialized and MYSQL_ROOT_PASSWORD is not set.'
        exit 1
    fi

    echo 'Initializing Database...'
    mysql_install_db --datadir="$DATADIR"
    echo 'Database Initialized.'


    cat > "$tempSqlFile" <<ENDOFSQL
        DELETE FROM mysql.user ;
        CREATE USER 'root'@'%' IDENTIFIED BY '${MYSQL_ROOT_PASSWORD}' ;
        GRANT ALL ON *.* TO 'root'@'%' WITH GRANT OPTION ;
        DROP DATABASE IF EXISTS test ;
ENDOFSQL

    if [ "$MYSQL_USER" -a "$MYSQL_PASSWORD" ]; then
        echo "CREATE USER '$MYSQL_USER'@'%' IDENTIFIED BY '$MYSQL_PASSWORD' ;" >> "$tempSqlFile"

        echo "GRANT ALL ON *.* TO '$MYSQL_USER'@'%' ;" >> "$tempSqlFile"

    fi

    echo 'FLUSH PRIVILEGES ;' >> "$tempSqlFile"

    if [ -f "$backupSqlFile" ]; then
        echo "Using provided backup file."
    else
        echo "No backup file provided, downloading latest backup from LegendHUB drive."
        wget "drive.google.com/uc?export=download&id=1ahubTEMFuRfbK6o0KANL4em_T2zKypQ8" -O "/docker-entrypoint-initdb.d/backup.sql"
    fi

    sed -i s/legendhub/$MYSQL_DATABASE/g $backupSqlFile
    sed -i s/legendhub/$MYSQL_USER/g $backupSqlFile
    sed -Ei 's/\$\{MYSQL_USER\}/'"$MYSQL_USER"'/g; s/\$\{MYSQL_PASSWORD\}/'"$MYSQL_PASSWORD"'/g; s/\$\{MYSQL_DATABASE\}/'"$MYSQL_DATABASE"'/g' /etc/cron.d/cron-mysql
    echo "CREATE DATABASE \`$MYSQL_DATABASE\`;USE \`$MYSQL_DATABASE\`;" | cat - $backupSqlFile > temp && mv temp $backupSqlFile
else
    rm -f $backupSqlFile
    rm -f $tempSqlFile
fi

service cron restart

mkdir -p /backups/public
mkdir -p /backups/private

mkdir -p /var/run/mysqld
chown -R mysql:mysql /var/run/mysqld
chown -R mysql:mysql "$DATADIR"

echo "Starting mysql service..."
service mysql start
if [ -f $tempSqlFile ]; then
    echo "---------------------------"
    echo "Loading init file..."
    mysql --max_allowed_packet=32505856 -u root < $tempSqlFile
    echo "Init file loaded."
fi
if [ -f $backupSqlFile ]; then
    echo "---------------------------"
    echo "Loading from backup file..."
    mysql --max_allowed_packet=32505856 -u root -p"$MYSQL_ROOT_PASSWORD" < $backupSqlFile
    echo "Backup file loaded."
fi
echo "Startup Complete."
echo "Waiting..."
while true; do
    sleep 1
done
