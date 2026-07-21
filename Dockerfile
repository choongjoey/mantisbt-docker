FROM php:8.5-apache

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

# hadolint ignore=DL3008
RUN set -xe \
    && apt-get update \
    && apt-get install --no-install-recommends -y \
        # PHP dependencies
        libfreetype6-dev libpng-dev libjpeg-dev libpq-dev libxml2-dev \
        # New in PHP 7.4, required for mbstring, see https://github.com/docker-library/php/issues/880
        libonig-dev \
        # Used to apply the VEditor source patch against core/bug_api.php
        patch \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install gd mbstring mysqli pgsql pdo_pgsql soap \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite

ENV MANTIS_VER 2.28.1
ENV MANTIS_MD5 be44e7fb65682d536f24f5aa5b7656cf
ENV MANTIS_URL https://sourceforge.net/projects/mantisbt/files/mantis-stable/${MANTIS_VER}/mantisbt-${MANTIS_VER}.tar.gz
ENV MANTIS_FILE mantisbt.tar.gz

# Source patches applied to the upstream MantisBT tree (e.g. VEditor hook in
# core/bug_api.php). Kept as unified diffs so future MantisBT bumps fail loudly
# at build time if upstream drifts. The core patches below are applied inline in
# the install RUN (stage-bound: after tar extract, before plugins exist); the
# plugin-tree deltas in patches/ are applied later by apply-plugin-patches.sh.
COPY ./patches /tmp/patches

# Install MantisBT itself
RUN set -xe \
    && curl -fSL "${MANTIS_URL}" -o "${MANTIS_FILE}" \
    && md5sum "${MANTIS_FILE}" \
    && echo "${MANTIS_MD5}  ${MANTIS_FILE}" | md5sum -c \
    && tar -xz --strip-components=1 -f "${MANTIS_FILE}" \
    && rm "${MANTIS_FILE}" \
    && rm -r doc \
    && patch -p1 -d /var/www/html < /tmp/patches/veditor-bug_api.patch \
    && patch -p1 -d /var/www/html < /tmp/patches/attachments-bug_change_status_page.patch \
    && patch -p1 -d /var/www/html < /tmp/patches/attachments-bug_update_page.patch \
    && chown -R www-data:www-data . \
    # Apply PHP and config fixes
    # Use the default production configuration
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo 'mysqli.allow_local_infile = Off' >> "$PHP_INI_DIR/conf.d/mantis.php.ini" \
    && echo 'display_errors = Off ' >> "$PHP_INI_DIR/conf.d/mantis.php.ini" \
    && echo 'log_errors = On ' >> "$PHP_INI_DIR/conf.d/mantis.php.ini" \
    && echo 'error_log = /dev/stderr' >> "$PHP_INI_DIR/conf.d/mantis.php.ini" \
    && echo 'upload_max_filesize = 50M ' >> "$PHP_INI_DIR/conf.d/mantis.php.ini" \
    && echo 'post_max_size = 51M ' >> "$PHP_INI_DIR/conf.d/mantis.php.ini" \
    && echo 'register_argc_argv = Off' >> "$PHP_INI_DIR/conf.d/mantis.php.ini"

COPY config_inc.php /var/www/html/config/config_inc.php

# Plugins are vendored in-repo under plugins/ (pristine upstream@SHA trees plus
# the repo-owned FieldDescriptions). No plugins are fetched from the network at
# build time — see scripts/update-plugins.sh and plugins/VENDOR.md for how the
# vendored trees are refreshed and pinned. Our local deltas stay explicit in
# patches/ and are applied by apply-plugin-patches.sh against the copied tree.
COPY ./plugins /var/www/html/plugins
COPY ./scripts/apply-plugin-patches.sh /tmp/apply-plugin-patches.sh
RUN set -xe && \
        bash /tmp/apply-plugin-patches.sh && \
        rm -rf /tmp/patches /tmp/apply-plugin-patches.sh

COPY ./mantis-entrypoint /usr/local/bin/mantis-entrypoint

CMD ["mantis-entrypoint"]
