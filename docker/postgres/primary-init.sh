#!/bin/bash
set -e

# Setup replication user and physical replication slot on primary database
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE USER replicator WITH REPLICATION ENCRYPTED PASSWORD 'replicator_secret';
    SELECT * FROM pg_create_physical_replication_slot('slotsaver_standby_slot');
EOSQL

echo "host replication replicator all md5" >> "$PGDATA/pg_hba.conf"
pg_ctl -D "$PGDATA" reload
