#!/usr/bin/env bash
set -euo pipefail

WP="wp --allow-root"
export WP_CLI_CACHE_DIR="/tmp/.wp-cli/cache"
mkdir -p "$WP_CLI_CACHE_DIR"
SITE_URL="http://sphynx.local.mermaid:8081"
DB_HOST="mariadb-sphynx"
DB_NAME="wp_sphynx"
DB_USER="sphynx_user"
DB_PASS="sphynx_pass"

cd /var/www/html

if [ ! -f wp-load.php ]; then
  echo "Downloading WordPress core..."
  $WP core download --skip-content --force
fi

if [ ! -f wp-config.php ]; then
  echo "Generating wp-config.php..."
  $WP config create \
    --dbname="$DB_NAME" \
    --dbuser="$DB_USER" \
    --dbpass="$DB_PASS" \
    --dbhost="$DB_HOST" \
    --dbcharset="utf8mb4" \
    --dbcollate="utf8mb4_unicode_ci" \
    --skip-check \
    --extra-php <<'PHP'
define( 'WP_HOME', 'http://sphynx.local.mermaid:8081' );
define( 'WP_SITEURL', 'http://sphynx.local.mermaid:8081' );
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SAVEQUERIES', false );
define( 'WP_REDIS_HOST', 'redis-sphynx' );
define( 'WP_REDIS_PORT', 6379 );
define( 'WP_REDIS_TIMEOUT', 0.5 );
define( 'WP_REDIS_READ_TIMEOUT', 0.5 );
define( 'WP_REDIS_DATABASE', 0 );
define( 'WP_CACHE', true );
define( 'DISALLOW_FILE_EDIT', true );
define( 'DISABLE_WP_CRON', true );
PHP
fi

$WP config set DISALLOW_FILE_EDIT true --type=constant --raw --quiet
$WP config set WP_CACHE true --type=constant --raw --quiet
$WP config set WP_DEBUG false --type=constant --raw --quiet
$WP config set WP_DEBUG_LOG false --type=constant --raw --quiet
$WP config set WP_DEBUG_DISPLAY false --type=constant --raw --quiet
$WP config set SAVEQUERIES false --type=constant --raw --quiet
$WP config set DISABLE_WP_CRON true --type=constant --raw --quiet
$WP config set WP_REDIS_HOST "redis-sphynx" --type=constant --quiet
$WP config set WP_REDIS_PORT 6379 --type=constant --raw --quiet
$WP config set WP_REDIS_TIMEOUT 0.5 --type=constant --raw --quiet
$WP config set WP_REDIS_READ_TIMEOUT 0.5 --type=constant --raw --quiet
$WP config set WP_REDIS_DATABASE 0 --type=constant --raw --quiet
$WP config set WP_HOME "$SITE_URL" --type=constant --quiet
$WP config set WP_SITEURL "$SITE_URL" --type=constant --quiet

if ! $WP core is-installed >/dev/null 2>&1; then
  echo "Installing WordPress core..."
  $WP core install \
    --url="$SITE_URL" \
    --title="Miss Mermaid · Sphynx" \
    --admin_user="mm_admin" \
    --admin_password='MissMermaid!dev123' \
    --admin_email='admin@sphynx.local.mermaid'
fi

$WP option update siteurl "$SITE_URL" >/dev/null
$WP option update home "$SITE_URL" >/dev/null
