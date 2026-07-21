#!/usr/bin/env bash
#
# apply-plugin-patches.sh — apply this repo's local deltas to the vendored
# plugin trees during the Docker build, run against the copied tree after
# `COPY ./plugins`. The vendored trees under plugins/ are pristine upstream@SHA
# copies (see scripts/update-plugins.sh); our changes live in patches/ and are
# applied here so they stay explicit and auditable.
#
# The `patch` steps are fail-loud: if a plugin update moves a patched line the
# build breaks, so the drift is noticed. See AGENTS.md for the plugin model.
#
# Usage: apply-plugin-patches.sh [plugins-dir] [patches-dir]
#   plugins-dir  default /var/www/html/plugins
#   patches-dir  default /tmp/patches

set -euo pipefail

PLUGINS_DIR="${1:-/var/www/html/plugins}"
PATCHES_DIR="${2:-/tmp/patches}"

patch -p1 -d "${PLUGINS_DIR}/Motives"     < "${PATCHES_DIR}/motives-category-sentinel.patch"
patch -p1 -d "${PLUGINS_DIR}/TelegramBot" < "${PATCHES_DIR}/telegrambot-category-cast.patch"
cp "${PATCHES_DIR}/attachments-pages.htaccess" "${PLUGINS_DIR}/Attachments/pages/.htaccess"
chown -R www-data:www-data "${PLUGINS_DIR}"
