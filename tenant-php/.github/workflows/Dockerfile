FROM hyperf/hyperf:8.2-alpine-v3.18-swow
LABEL maintainer="MineManage Developers <group@stye.cn>" version="1.0" license="MIT" app.name="MineManage"

ARG timezone

ENV TIMEZONE=${timezone:-"Asia/Shanghai"} \
    APP_ENV=prod \
    SCAN_CACHEABLE=(true)

RUN set -ex \
    && php -v \
    && php -m \
    && php --ri swow \
    && cd /etc/php* \
    && { \
        echo "upload_max_filesize=128M"; \
        echo "post_max_size=128M"; \
        echo "memory_limit=1G"; \
        echo "date.timezone=${TIMEZONE}"; \
    } | tee conf.d/99_overrides.ini \
    && ln -sf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone \
    && rm -rf /var/cache/apk/* /tmp/* /usr/share/man \
    && echo -e "\033[42;37m Build Completed :).\033[0m\n"

WORKDIR /opt/www

COPY . /opt/www

RUN composer install --no-dev -o && php bin/hyperf.php

EXPOSE 9501 9502 9503

ENTRYPOINT ["php", "/opt/www/bin/hyperf.php", "start"]
