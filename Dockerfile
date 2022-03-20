FROM ubuntu:18.04 as sjasmplus-builder

ENV SJASMPLUS_VERSION=1.18.3

WORKDIR /app
RUN apt-get update && apt-get install -y curl make g++
RUN curl -L https://github.com/z00m128/sjasmplus/archive/refs/tags/v${SJASMPLUS_VERSION}.tar.gz | tar xvz --strip-components=1 -C .
RUN make && make install



RUN apt-get install -y \
    nginx \
    php-fpm \
    tzdata \
    php-mbstring php-iconv php-json php-zip php-simplexml php-gd
RUN ln -s /usr/sbin/php-fpm7 /usr/sbin/php-fpm
# RUN useradd php
# RUN useradd nginx
RUN rm -rf /etc/nginx/conf.d/* /etc/php7/conf.d/* /etc/php7/php-fpm.d/*

COPY docker-files /

WORKDIR /www

ENTRYPOINT ["/start.sh"]

EXPOSE 80

# HEALTHCHECK --interval=5s --timeout=5s CMD curl -f http://127.0.0.1/php-fpm-ping || exit 1

COPY --chown=root:root src/ /www

# FROM existenz/webstack:7.4
# COPY --from=sjasmplus-builder /usr/local/bin/sjasmplus /usr/bin/
# COPY --chown=php:nginx src/ /www
# RUN apk -U --no-cache add php7-mbstring php7-iconv php7-json php-zip php7-simplexml php7-gd
