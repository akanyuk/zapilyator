FROM ubuntu:18.04 as sjasmplus-builder

ENV SJASMPLUS_VERSION=1.18.3

# sjasmplus building
WORKDIR /app
RUN apt-get update && apt-get install -y curl make g++
RUN curl -L https://github.com/z00m128/sjasmplus/archive/refs/tags/v${SJASMPLUS_VERSION}.tar.gz | tar xvz --strip-components=1 -C .
RUN make && make install

# webserver starting
RUN apt-get install -y nginx php-fpm tzdata php-mbstring php-iconv php-json php-zip php-simplexml php-gd \
    && ln -s /usr/sbin/php-fpm7 /usr/sbin/php-fpm \
    && rm -rf /etc/nginx/conf.d/* /etc/php7/conf.d/* /etc/php7/php-fpm.d/*

COPY docker-files /
COPY --chown=www-data:www-data src/ /www

WORKDIR /www
ENTRYPOINT ["/start.sh"]
EXPOSE 80
