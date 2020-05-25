FROM php:7.4-cli-alpine as build
RUN apk update && apk add curl && \
  curl -sS https://getcomposer.org/installer | php \
  && chmod +x composer.phar && mv composer.phar /usr/local/bin/composer

COPY . /var/cache/app
RUN cd /var/cache/app && /usr/local/bin/composer install --no-dev -o -q

FROM php:7.4-cli-alpine
COPY --from=build /var/cache/app /app
CMD [ "/app/housekeeping.php" ]
