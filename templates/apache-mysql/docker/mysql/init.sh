#!/bin/bash

# MySQL initialization script
# This script runs automatically at the first container startup

echo "Initializing MySQL database..."

# If backup files exist in the backups/ folder
if [ "$(ls -A /docker-entrypoint-initdb.d/*.sql 2>/dev/null)" ]; then
    echo "Backup files detected, importing..."
    
    # Import all SQL files found
    for file in /docker-entrypoint-initdb.d/*.sql; do
        if [ -f "$file" ]; then
            echo "Importing $file..."
            mysql -u root -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE < "$file"
        fi
    done
    
    echo "Backup import completed."
else
    echo "No backup found, creating empty database."
fi

echo "MySQL initialization completed."