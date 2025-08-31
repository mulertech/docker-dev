-- Create user with network access like in the old working system
CREATE USER IF NOT EXISTS 'user'@'%' IDENTIFIED BY 'password';
-- Grant all privileges including database creation
GRANT ALL PRIVILEGES ON *.* TO 'user'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;