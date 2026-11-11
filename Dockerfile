# syntax=docker/dockerfile:1
# AuryaDeco : image de production (PHP + Apache), pour EasyPanel.
ARG PHP_IMAGE=php:8.4.26-apache-bookworm

# ---------- Étape 1 : compilation des extensions PHP ----------
# Les bibliothèques de développement restent dans cette étape.
FROM ${PHP_IMAGE} AS extensions
RUN apt-get update \
 && apt-get install -y --no-install-recommends libjpeg62-turbo-dev libpng-dev libwebp-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" gd pdo_mysql opcache

# ---------- Étape 2 : image d'exécution ----------
FROM ${PHP_IMAGE} AS runtime

# Bibliothèques nécessaires à l'exécution de GD uniquement.
RUN apt-get update \
 && apt-get install -y --no-install-recommends libjpeg62-turbo libpng16-16 libwebp7 \
 && rm -rf /var/lib/apt/lists/*

COPY --from=extensions /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=extensions /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

ENV PORT=8080 \
    APP_ENV=production

# PHP de production + réglages AuryaDeco.
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-auryadeco.ini"

# Apache : modules utiles, port lu dans $PORT, droits pour tourner sans root.
COPY docker/apache.conf /etc/apache2/conf-available/zz-auryadeco.conf
RUN a2enmod rewrite headers expires deflate remoteip \
 && a2enconf zz-auryadeco \
 && sed -ri 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf \
 && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf \
 && chown -R www-data:www-data /var/run/apache2 /var/lock/apache2 /var/log/apache2

# Démarrage : préparation de la base puis Apache.
COPY docker/install-cli.php /opt/auryadeco/install-cli.php
COPY uploads/.htaccess /opt/auryadeco/uploads.htaccess
COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/auryadeco-entrypoint

# Code de la boutique (voir .dockerignore pour les fichiers exclus).
COPY --chown=www-data:www-data . /var/www/html/
RUN rm -rf /var/www/html/docker /var/www/html/Dockerfile \
 && mkdir -p /var/www/html/uploads \
 && chown www-data:www-data /var/www/html/uploads

USER www-data
WORKDIR /var/www/html
VOLUME ["/var/www/html/uploads"]
EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
  CMD curl -fsS "http://127.0.0.1:${PORT}/health" > /dev/null || exit 1

# L'image de base arrête Apache proprement avec SIGWINCH (arrêt gracieux).
STOPSIGNAL SIGWINCH
ENTRYPOINT ["auryadeco-entrypoint"]
CMD ["apache2-foreground"]
