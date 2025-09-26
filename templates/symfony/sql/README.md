# SQL Initialization Files

Place your `.sql` files here to initialize your PostgreSQL database.

## Usage

1. **Copy your backup files** to this directory:
   ```bash
   cp my-database-backup.sql .mtdocker/sql/
   ```

2. **Restart the environment** to apply changes:
   ```bash
   ./vendor/bin/mtdocker down
   ./vendor/bin/mtdocker up -d
   ```

## File execution order

PostgreSQL executes files in alphabetical order:
1. `01-init-user.sql` (system - for additional setup if needed)
2. `02-your-backup.sql` (your files)
3. `03-more-data.sql` (your files)

## Supported formats

- `.sql` files (recommended)
- `.sql.gz` compressed files
- `.sh` shell scripts

## Note

PostgreSQL user and database are automatically created via environment variables in docker-compose.yml, so the init-user.sql script is mainly for additional setup if needed.