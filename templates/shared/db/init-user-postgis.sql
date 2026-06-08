-- PostgreSQL initialization script (PostGIS variant)
-- Note: User and database are already created via environment variables
-- POSTGRES_USER (from env) = 'user'
-- POSTGRES_DB (from env) = 'db'
-- The postgis/postgis image ships the PostGIS extension; we still enable it per database.

-- Enable PostGIS in the main database
CREATE EXTENSION IF NOT EXISTS postgis;

-- Create test database (conditional creation using SELECT/gexec)
SELECT 'CREATE DATABASE db_test'
    WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'db_test')\gexec

-- Connect to test database and enable PostGIS
\c db_test
CREATE EXTENSION IF NOT EXISTS postgis;

-- Return to main database
\c db

-- Grant additional privileges if needed
-- ALTER USER user CREATEDB;
