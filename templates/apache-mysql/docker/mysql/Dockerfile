FROM mysql:8.0

# MySQL configuration for development
RUN echo '[mysqld]' >> /etc/mysql/conf.d/custom.cnf \
    && echo 'max_connections = 200' >> /etc/mysql/conf.d/custom.cnf \
    && echo 'innodb_buffer_pool_size = 128M' >> /etc/mysql/conf.d/custom.cnf \
    && echo 'innodb_log_file_size = 32M' >> /etc/mysql/conf.d/custom.cnf \
    && echo 'query_cache_size = 16M' >> /etc/mysql/conf.d/custom.cnf \
    && echo 'query_cache_type = 1' >> /etc/mysql/conf.d/custom.cnf \
    && echo 'slow_query_log = 1' >> /etc/mysql/conf.d/custom.cnf \
    && echo 'long_query_time = 2' >> /etc/mysql/conf.d/custom.cnf \
    && echo 'general_log = 0' >> /etc/mysql/conf.d/custom.cnf

# Configuration for automatic backup import
# .sql files placed in /docker-entrypoint-initdb.d/ will be automatically executed
COPY docker/mysql/init.sh /docker-entrypoint-initdb.d/

# Execution rights for initialization script
RUN chmod +x /docker-entrypoint-initdb.d/init.sh

EXPOSE 3306