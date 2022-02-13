FROM existenz/webstack:7.4

COPY --chown=php:nginx src/ /www

RUN apk -U --no-cache add php7-mbstring php7-iconv php7-json php-zip php7-simplexml php7-gd
