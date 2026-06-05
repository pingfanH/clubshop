#!/bin/sh
set -eu

echo "Waiting for MySQL (${MYSQL_HOST}:${MYSQL_PORT})..."
i=0
while [ "$i" -lt 60 ]; do
  if mysqladmin ping \
    -h"${MYSQL_HOST}" \
    -P"${MYSQL_PORT}" \
    -u"${MYSQL_USER}" \
    -p"${MYSQL_PASSWORD}" \
    --silent >/dev/null 2>&1; then
    break
  fi
  i=$((i + 1))
  sleep 2
done

if [ "$i" -ge 60 ]; then
  echo "MySQL is not ready after 120s."
  exit 1
fi

imported=0
for file in \
  /sql/10-update.sql \
  /sql-extra/update_schema.sql \
  /sql-extra/update_user_table.sql \
  /sql-extra/assign_merchant_permissions.sql; do
  if [ -f "$file" ]; then
    echo "Importing: $file"
    mysql --force \
      -h"${MYSQL_HOST}" \
      -P"${MYSQL_PORT}" \
      -u"${MYSQL_USER}" \
      -p"${MYSQL_PASSWORD}" \
      "${MYSQL_DATABASE}" < "$file"
    imported=1
  fi
done

if [ "$imported" -eq 0 ]; then
  echo "No SQL files found to import."
  exit 1
fi

echo "Database initialization finished."
