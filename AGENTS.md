# AGENTS.md

Contributor and agent guide for `mantisbt-docker`. This is the single source of
truth for how the repo is built and maintained; `CLAUDE.md` just imports it.

## Project overview

This repo packages [MantisBT](https://www.mantisbt.org/) as a Docker image on
top of `php:8.5-apache`. It is a fork of
[okainov/mantisbt-docker](https://github.com/okainov/mantisbt-docker) that adds
PostgreSQL support, an env-driven config, an `admin`-folder toggle, and a
curated set of community plugins. The published image is
`choongjoey/mantisbt` (also mirrored to `ghcr.io/<repo>`).

MantisBT core is pinned to `2.28.1` and downloaded from SourceForge at build
time (MD5-checked). Community plugins are **vendored in-repo** (see below).

## Architecture

- **Base image:** `php:8.5-apache`. The Dockerfile installs the PHP extensions
  MantisBT needs (`gd`, `mbstring`, `mysqli`, `pgsql`, `pdo_pgsql`, `soap`),
  enables `mod_rewrite`, and applies a few `php.ini` hardening/limit tweaks.
- **Core install:** the `MANTIS_VER` / `MANTIS_MD5` / `MANTIS_URL` ENVs drive a
  `curl | md5sum -c | tar` install of MantisBT into `/var/www/html`. Core
  patches (VEditor + Attachments) are applied here.
- **Config:** `config_inc.php` is copied to `/var/www/html/config/`. It reads
  almost everything from environment variables (`DB_*` with legacy `MYSQL_*`
  fallbacks, `MASTER_SALT`, `EMAIL_*`, `SMTP_*`) and `include`s an optional
  `config_inc_addon.php` so users can extend config without rebuilding. It also
  sets a relaxed Content-Security-Policy (see gotchas).
- **Entrypoint:** `mantis-entrypoint` renames the `admin/` folder to `.admin`
  (removing it from the web root) unless `MANTIS_ENABLE_ADMIN` is set to a
  non-zero value, then runs `apache2-foreground`. This is the admin-folder
  toggle referenced in the README.

## Key files

| Path | Purpose |
|---|---|
| `Dockerfile` | Image build: core install, config, core patches, plugin copy |
| `config_inc.php` | Env-driven MantisBT config + CSP header |
| `mantis-entrypoint` | Admin-folder toggle + Apache launch |
| `docker-compose.yaml` | **Reference/example** compose file (not production-ready) |
| `plugins/` | Vendored plugin trees (pristine `upstream@SHA`) + repo-owned `FieldDescriptions` |
| `plugins/VENDOR.md` | Generated provenance: repo / requested ref / resolved SHA / date |
| `patches/` | Unified diffs + htaccess applied to core and plugin trees |
| `scripts/plugins.manifest` | `<repo> <ref>` list of plugins to vendor |
| `scripts/update-plugins.sh` | Re-runnable vendoring script (writes `plugins/` + `VENDOR.md`) |
| `scripts/apply-plugin-patches.sh` | Applies the `patches/` plugin deltas to the copied tree at build time |
| `.dockerignore` | Whitelist — must keep `plugins/**`, `patches/**`, and `scripts/apply-plugin-patches.sh` |
| `.github/workflows/` | CI (`main.yml`: pre-commit + hadolint + build) and publish |

## Build, lint, run

```bash
docker build .                 # build the image (podman build . works too)
hadolint Dockerfile            # Dockerfile lint (CI parity)
pre-commit run --all-files     # yaml / whitespace / EOF checks (CI parity)
docker compose up -d           # run locally on localhost:8989 (podman compose too)
```

All container commands work identically with `podman`. CI (`.github/workflows/main.yml`)
runs pre-commit, hadolint, and `docker build .` on every push/PR to `master`.

## Plugin model

Plugins are **vendored** — a pristine, byte-for-byte copy of each upstream tree
is committed under `plugins/`, frozen to an immutable commit SHA. This makes
each update a reviewable PR diff, makes builds reproducible and offline, and
removes the supply-chain risk of fetching mutable branches at build time.

Three layers:

1. **Vendored pristine trees** (`plugins/<Name>/`) — untouched `upstream@SHA`,
   so they stay auditable against upstream. Written by
   `scripts/update-plugins.sh` from `scripts/plugins.manifest`; provenance is
   recorded in `plugins/VENDOR.md`.
2. **`patches/`** — our local deltas (the Motives and TelegramBot source fixes
   and the Attachments `.htaccess`), applied to the copied tree at build time by
   `scripts/apply-plugin-patches.sh`. Keeping them separate means our changes
   stay explicit and the vendored trees remain pristine. These `patch` steps are
   **fail-loud**: if an upstream
   update moves the patched lines, the build breaks so the drift is noticed.
3. **`plugins/FieldDescriptions/`** — repo-owned, not managed by the script or
   listed in the manifest.

MantisBT core is **not** vendored (too large; already MD5-pinned) — it stays a
build-time download from SourceForge.

### Supply-chain rationale (recorded debate outcome)

The old build fetched ~14 plugins by `curl` at build time with no checksums,
and four tracked mutable `master`/`main` branches — a force-pushed or
compromised upstream branch would land in the image on the next rebuild.

- **Inline-fetch vs external-script is not a security lever**: a script that
  fetches at build time has the same exposure. The real levers are
  **immutability + provenance**.
- **Vendoring** gives the strongest control: reviewable diffs on every update,
  reproducible/offline builds, and immunity to upstream force-push/deletion/
  compromise between deliberate updates. The cost is repo size and owning the
  update cadence — acceptable here since the repo already vendored
  `FieldDescriptions` and maintains local patches.
- **Design choice:** vendor **pristine** upstream trees and keep our deltas in
  `patches/`, applied at build time by `scripts/apply-plugin-patches.sh` — so
  vendored code stays auditable
  against upstream and our changes stay explicit.

## Gotchas

- **`.dockerignore` is a whitelist** (`*` then `!`-includes). It must keep
  `!plugins` / `!plugins/**` and `!patches` / `!patches/**`, or the vendored
  plugins and patches won't be in the build context and the build will fail.
- **Fail-loud on upstream drift** is intentional: the core MD5 check and every
  `patch` step break the build if upstream changed. Don't paper over a failure —
  re-roll the patch or re-verify the checksum (see Maintenance guide).
- **CSP is relaxed** in two places: `config_inc.php` widens `img-src` to allow
  `blob:`/`data:` so VEditor's TinyMCE clipboard-paste flow works, and the
  FieldDescriptions plugin further relaxes it at runtime via
  `http_csp_add('script-src', "'unsafe-inline'")`. Tighten the base policy via
  `config_inc_addon.php` if you don't use VEditor.

## Maintenance guide

### Add a plugin

1. Add a `<repo> <ref> [subdirs]` line to `scripts/plugins.manifest`. The
   optional third field handles repos that don't map one-to-one onto
   `plugins/<repo>` — no script edit needed:
   - a repo that ships **several plugins as top-level subdirs** (like
     `source-integration`) — list them: `source-integration v2.9.0 Source,SourceGitlab,SourceGithub`;
   - a plugin that **lives in a subdir** (like `TelegramBot`, whose
     `TelegramBot/` folder is the plugin) — name that subdir:
     `TelegramBot release-1.6.0 TelegramBot`.
   A plain repo (PluginName.php at the root) needs no third field.
2. Run `bash scripts/update-plugins.sh`.
3. Commit the new `plugins/<Name>/` tree and the updated `plugins/VENDOR.md`.
4. If it needs a source fix, add a unified diff to `patches/` and a `patch`
   step to `scripts/apply-plugin-patches.sh`.
5. Add a row to the README **Bundled community plugins** table.

### Remove a plugin

1. Delete its line from `scripts/plugins.manifest`.
2. `git rm -r plugins/<Name>` (for `source-integration`, remove `Source`,
   `SourceGitlab`, `SourceGithub`).
3. Drop any related `patches/` file and its `patch` step in
   `scripts/apply-plugin-patches.sh`.
4. Remove its README table row.
5. Prune its entry from `plugins/VENDOR.md` (or just re-run the script).

### Update a plugin

1. Change the ref in `scripts/plugins.manifest`.
2. Run `bash scripts/update-plugins.sh`.
3. Review the vendored tree diff and the resolved-SHA change in
   `plugins/VENDOR.md` in the PR.
4. Rebuild; if a `patches/` diff no longer applies, re-roll it against the new
   tree.

### Update MantisBT core

1. Bump `MANTIS_VER` in the Dockerfile.
2. Download the new tarball and recompute the MD5 (`md5sum mantisbt-<ver>.tar.gz`);
   update `MANTIS_MD5`.
3. `docker build .`. The core `patch` steps (`veditor-bug_api`,
   `attachments-*`) are fail-loud — if upstream core files drifted the build
   breaks, so re-roll those patches against the new tree.
4. Sanity-check plugin compatibility and run the `/admin` DB upgrade after
   deploying.
