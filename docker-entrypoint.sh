#!/bin/sh
# CMAS entrypoint: embedded MariaDB when no external DB is configured,
# then Apache on $PORT (default 80).
set -e
PORT="${PORT:-80}"

if [ -z "$DB_HOST" ] && [ -z "$DATABASE_URL" ]; then
  DB_NAME="${DB_DATABASE:-committee_management_db}"
  DB_PASS="${DB_PASSWORD:-cmas_app_pass}"
  export DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE="$DB_NAME" DB_USERNAME=cmas_app DB_PASSWORD="$DB_PASS"

  mkdir -p /run/mysqld /var/lib/mysql && chown -R mysql:mysql /run/mysqld /var/lib/mysql
  if [ ! -d /var/lib/mysql/mysql ]; then
    mariadb-install-db --user=mysql --datadir=/var/lib/mysql --auth-root-authentication-method=socket >/dev/null
  fi
  mariadbd-safe --datadir=/var/lib/mysql &
  until mysqladmin --silent ping; do sleep 0.5; done

  mysql -uroot -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`; CREATE USER IF NOT EXISTS 'cmas_app'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}'; CREATE USER IF NOT EXISTS 'cmas_app'@'localhost' IDENTIFIED BY '${DB_PASS}'; GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO 'cmas_app'@'127.0.0.1'; GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO 'cmas_app'@'localhost'; FLUSH PRIVILEGES;"
  if ! mysql -uroot -N -e "SELECT 1 FROM \`$DB_NAME\`.users LIMIT 1" 2>/dev/null | grep -q 1; then
    mysql -uroot "$DB_NAME" < /docker-db/committee_management_db.sql
    mysql -uroot "$DB_NAME" < /docker-db/migration_perf_indexes.sql 2>/dev/null || true
  fi
fi

sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/\*:80>/*:${PORT}>/" /etc/apache2/sites-available/000-default.conf
exec "$@"
