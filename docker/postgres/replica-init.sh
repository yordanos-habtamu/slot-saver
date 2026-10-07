#!/bin/bash
set -e

# Pull baseline backup from primary container
rm -rf "$PGDATA"/*

PGPASSWORD=replicator_secret pg_basebackup \
    -h postgres \
    -D "$PGDATA" \
    -U replicator \
    -vP \
    -X stream \
    -R \
    --slot=slotsaver_standby_slot

echo "primary_conninfo = 'host=postgres port=5432 user=replicator password=replicator_secret'" >> "$PGDATA/postgresql.auto.conf"
echo "primary_slot_name = 'slotsaver_standby_slot'" >> "$PGDATA/postgresql.auto.conf"
touch "$PGDATA/standby.signal"

exec postgres -c config_file=/etc/postgresql/postgresql.conf
