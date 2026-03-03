-- PostgreSQL initialization script
-- Note: User and database are already created via environment variables
-- This script is for additional setup if needed

-- Create test database
SELECT 'CREATE DATABASE db_test'
    WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'db_test')\gexec

-- Grant additional privileges if needed
-- ALTER USER user CREATEDB;
