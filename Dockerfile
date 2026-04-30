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
# at build time if upstream drifts.
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
    && rm -rf /tmp/patches \
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

# Install additional plugins
ENV SOURCE_TAG v2.9.0
RUN set -xe && \
        curl -fSL https://github.com/mantisbt-plugins/source-integration/tarball/${SOURCE_TAG} -o /tmp/source.tar.gz && \
        mkdir /tmp/source && \
        tar -xz --strip-components=1 -f /tmp/source.tar.gz -C /tmp/source/ && \
        cp -r /tmp/source/Source /tmp/source/SourceGitlab /tmp/source/SourceGithub /var/www/html/plugins/ && \
        rm -r /tmp/source

# Community plugins from https://github.com/mantisbt-plugins
# Most repos have PluginName.php at the root, so we extract the tarball straight
# into /var/www/html/plugins/<PluginName>/. TelegramBot is special — its repo
# carries the plugin in a TelegramBot/ subdirectory, so we extract to a temp
# dir and copy that subdir over.
ENV VEDITOR_REF=v1.1.2
ENV ANNOUNCE_REF=v2.4.6
ENV MOTIVES_REF=master
ENV SETDUEDATE_REF=main
ENV TELEGRAMBOT_REF=release-1.6.0
ENV LINKEDCUSTOMFIELDS_REF=v2.0.2
ENV DD_FILTER_REF=main
ENV INLINECOLUMNCONFIGURATION_REF=v2.0.0
ENV STATISTICS_REF=main
ENV SNIPPETS_REF=v2.5.0
RUN set -xe && \
        for spec in \
                "VEditor:${VEDITOR_REF}" \
                "Announce:${ANNOUNCE_REF}" \
                "Motives:${MOTIVES_REF}" \
                "SetDuedate:${SETDUEDATE_REF}" \
                "LinkedCustomFields:${LINKEDCUSTOMFIELDS_REF}" \
                "DD_Filter:${DD_FILTER_REF}" \
                "InlineColumnConfiguration:${INLINECOLUMNCONFIGURATION_REF}" \
                "Statistics:${STATISTICS_REF}"; \
                "Snippets:${SNIPPETS_REF}"; \
        do \
                repo="${spec%%:*}"; ref="${spec##*:}"; \
                curl -fSL "https://github.com/mantisbt-plugins/${repo}/tarball/${ref}" -o /tmp/plugin.tar.gz; \
                mkdir -p "/var/www/html/plugins/${repo}"; \
                tar -xz --strip-components=1 -f /tmp/plugin.tar.gz -C "/var/www/html/plugins/${repo}/"; \
                rm /tmp/plugin.tar.gz; \
        done && \
        curl -fSL "https://github.com/mantisbt-plugins/TelegramBot/tarball/${TELEGRAMBOT_REF}" -o /tmp/telegrambot.tar.gz && \
        mkdir /tmp/telegrambot && \
        tar -xz --strip-components=1 -f /tmp/telegrambot.tar.gz -C /tmp/telegrambot/ && \
        cp -r /tmp/telegrambot/TelegramBot /var/www/html/plugins/ && \
        rm -rf /tmp/telegrambot /tmp/telegrambot.tar.gz && \
        chown -R www-data:www-data /var/www/html/plugins

COPY ./mantis-entrypoint /usr/local/bin/mantis-entrypoint

CMD ["mantis-entrypoint"]
