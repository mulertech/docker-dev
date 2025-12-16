-- PostgreSQL initialization script
-- Note: User and database are already created via environment variables
-- POSTGRES_USER (from env) = 'user'
-- POSTGRES_DB (from env) = 'db'

-- Enable pgvector extension for vector embeddings in main database
CREATE EXTENSION IF NOT EXISTS vector;

-- Create test database (conditional creation using SELECT/gexec)
SELECT 'CREATE DATABASE db_test'
    WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'db_test')\gexec

-- Connect to test database and enable pgvector extension
    \c db_test
CREATE EXTENSION IF NOT EXISTS vector;

-- Return to main database
\c db

-- Grant additional privileges if needed
-- ALTER USER user CREATEDB;