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

## Adminer Database Administration

Adminer provides a simple web interface to manage your PostgreSQL database with **automatic connection**:

### Access
- **URL**: Open `http://localhost:PORT` (port configured in your .env file)
- **No login required** - automatically connects to PostgreSQL!

### Features
- **Zero configuration** - opens directly in your database
- **Table browser** - all tables listed on the left sidebar
- **Data viewing** - click any table to see its content immediately
- **SQL query interface** - run custom SQL queries
- **Data editing** - edit records directly in the interface
- **Database structure** - view table schemas and relationships

### Usage
1. Start your environment: `./vendor/bin/mtdocker up -d`
2. Open Adminer in your browser (URL shown in terminal)
3. **That's it!** - You're already connected to your PostgreSQL database

Much simpler than pgAdmin - no credentials to enter, just instant access to your data!