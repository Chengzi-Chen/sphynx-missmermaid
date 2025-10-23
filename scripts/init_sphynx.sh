#!/usr/bin/env bash
set -euo pipefail

WP="wp --allow-root"
export WP_CLI_CACHE_DIR="/tmp/.wp-cli/cache"
mkdir -p "$WP_CLI_CACHE_DIR"
PRIMARY_MENU="Main Navigation"
FOOTER_MENU="Footer Links"

cd /var/www/html

if ! $WP core is-installed >/dev/null 2>&1; then
  echo "WordPress is not installed. Run make fix-install first." >&2
  exit 1
fi

PLUGINS=(
  seo-by-rank-math
  advanced-custom-fields
  fluentform
  nextgen-gallery
  wordfence
  updraftplus
  imagify
  disable-comments
  redis-cache
)

for plugin in "${PLUGINS[@]}"; do
  if $WP plugin is-installed "$plugin" >/dev/null 2>&1; then
    $WP plugin activate "$plugin" >/dev/null
  else
    $WP plugin install "$plugin" --activate --force >/dev/null
  fi
  echo "Activated plugin: $plugin"
done

if command -v wp >/dev/null; then
  $WP redis enable >/dev/null 2>&1 || true
fi

$WP option update permalink_structure '/%postname%/' >/dev/null
$WP rewrite flush --allow-root --hard >/dev/null

mkdir -p wp-content/mu-plugins
cat <<'PHP' > wp-content/mu-plugins/disable-xmlrpc.php
<?php
add_filter( 'xmlrpc_enabled', '__return_false' );
PHP

$WP config set DISALLOW_FILE_EDIT true --type=constant --raw --quiet

if $WP theme is-installed mm-sphynx >/dev/null 2>&1; then
  $WP theme activate mm-sphynx >/dev/null
else
  echo "Theme mm-sphynx not found. Ensure it is mounted before running init." >&2
fi

$WP option update blogdescription 'Champion Sphynx cattery from Miss Mermaid.' >/dev/null

declare -A PAGE_MAP=(
  [home]="Home"
  [about]="About"
  [sphynx]="Sphynx"
  [kittens]="Kittens"
  [adoption]="Adoption"
  [achievements]="Achievements"
  [gallery]="Gallery"
  [blog]="Blog"
  [contact]="Contact"
  [faq]="FAQ"
)

for slug in "${!PAGE_MAP[@]}"; do
  title="${PAGE_MAP[$slug]}"
  existing_id=$($WP post list --post_type=page --pagename="$slug" --field=ID)
  if [ -z "$existing_id" ]; then
    created_id=$($WP post create --post_type=page --post_status=publish --post_title="$title" --post_name="$slug" --porcelain)
    echo "Created page: $title (#$created_id)"
  else
    echo "Page exists: $title (#$existing_id)"
  fi
done

home_id=$($WP post list --post_type=page --pagename=home --field=ID)
blog_id=$($WP post list --post_type=page --pagename=blog --field=ID)

if [ -n "$home_id" ]; then
  $WP option update show_on_front page >/dev/null
  $WP option update page_on_front "$home_id" >/dev/null
fi

if [ -n "$blog_id" ]; then
  $WP option update page_for_posts "$blog_id" >/dev/null
fi

if ! $WP menu list --fields=name --format=csv | tail -n +2 | tr -d '"' | grep -Fxq "${PRIMARY_MENU}"; then
  $WP menu create "$PRIMARY_MENU" >/dev/null 2>&1 || true
fi
if ! $WP menu list --fields=name --format=csv | tail -n +2 | tr -d '"' | grep -Fxq "${FOOTER_MENU}"; then
  $WP menu create "$FOOTER_MENU" >/dev/null 2>&1 || true
fi

home_id=${home_id:-$($WP post list --post_type=page --pagename=home --field=ID)}

MAIN_ORDER=(home about sphynx kittens adoption achievements gallery blog contact faq)
FOOTER_ORDER=(about adoption contact faq blog)

assign_menu_items() {
  local menu_name="$1"
  shift
  local slugs=("$@")
  local menu_id=$($WP menu list --fields=term_id,name --format=csv | awk -F',' -v name="$menu_name" 'NR>1 && $2==name {print $1}')
  if [ -z "$menu_id" ]; then
    menu_id=$($WP menu create "$menu_name" --porcelain)
  fi
  local items=$($WP menu item list "$menu_name" --fields=ID --format=ids || true)
  if [ -n "$items" ]; then
    for item in $items; do
      $WP menu item delete "$menu_name" "$item" >/dev/null
    done
  fi
  for slug in "${slugs[@]}"; do
    page_id=$($WP post list --post_type=page --pagename="$slug" --field=ID)
    if [ -n "$page_id" ]; then
      $WP menu item add-post "$menu_name" "$page_id" >/dev/null
    fi
  done
}

assign_menu_items "$PRIMARY_MENU" "${MAIN_ORDER[@]}"
assign_menu_items "$FOOTER_MENU" "${FOOTER_ORDER[@]}"

$WP menu location assign "$PRIMARY_MENU" primary >/dev/null 2>&1 || true
$WP menu location assign "$FOOTER_MENU" footer >/dev/null 2>&1 || true

echo "Initialization complete."
