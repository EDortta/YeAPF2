FROM php:8.4-fpm-alpine

WORKDIR /srv/app
COPY tests/http-environment/app/ /srv/app/
RUN mkdir -p /srv/app/.config /srv/app/assets /srv/app/logs

HEALTHCHECK --interval=5s --timeout=3s --retries=12 CMD ["php-fpm", "-t"]
CMD ["php-fpm", "-F"]
