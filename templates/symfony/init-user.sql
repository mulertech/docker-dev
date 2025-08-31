-- Create user with network access like in the old working system
CREATE USER IF NOT EXISTS 'user'@'%' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON db.* TO 'user'@'%';
-- Grant permission to create databases
GRANT CREATE ON *.* TO 'user'@'%';
FLUSH PRIVILEGES;