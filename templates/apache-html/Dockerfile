FROM httpd:2.4-alpine

ARG USER_ID=1000
ARG GROUP_ID=1000

# Remove existing www-data user and group, then recreate with correct IDs
RUN deluser www-data 2>/dev/null || true \
    && if getent group www-data ; then delgroup www-data; fi \
    && addgroup -g ${GROUP_ID} -S www-data \
    && adduser -u ${USER_ID} -D -S -G www-data www-data

# Change ownership of web directory
RUN chown -R www-data:www-data /usr/local/apache2/htdocs/ \
    && chmod -R 755 /usr/local/apache2/htdocs/

WORKDIR /usr/local/apache2/htdocs/
EXPOSE 80

# Run as www-data user for security
USER www-data

CMD ["httpd-foreground"]