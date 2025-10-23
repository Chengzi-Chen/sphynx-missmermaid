#!/usr/bin/env bash
set -euo pipefail

WP="wp --allow-root"
export WP_CLI_CACHE_DIR="/tmp/.wp-cli/cache"
mkdir -p "$WP_CLI_CACHE_DIR"
CONTENT_FILE="/opt/sphynx-content/content_sphynx_en.json"
TMP_FILE="/tmp/seed_sphynx.php"

if [ ! -f "$CONTENT_FILE" ]; then
  echo "Content file not found at $CONTENT_FILE" >&2
  exit 1
fi

cat <<'PHP' > "$TMP_FILE"
<?php
$path = getenv('CONTENT_FILE');
if (!$path || !file_exists($path)) {
    throw new RuntimeException('CONTENT_FILE missing.');
}
$json = file_get_contents($path);
if ($json === false) {
    throw new RuntimeException('Unable to read JSON content.');
}
$data = json_decode($json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    throw new RuntimeException('Invalid JSON: ' . json_last_error_msg());
}
if (empty($data['pages']) || !is_array($data['pages'])) {
    throw new RuntimeException('JSON missing pages definition.');
}

$page_ids = [];
foreach ($data['pages'] as $slug => $page_data) {
    $title = $page_data['title'] ?? ucwords(str_replace('-', ' ', $slug));
    $content = $page_data['content'] ?? '';
    $excerpt = $page_data['excerpt'] ?? '';
    $page = get_page_by_path($slug);
    if (!$page) {
        $page_id = wp_insert_post([
            'post_type'   => 'page',
            'post_status' => 'publish',
            'post_name'   => $slug,
            'post_title'  => $title,
            'post_content'=> $content,
            'post_excerpt'=> $excerpt,
        ]);
    } else {
        $page_id = $page->ID;
        wp_update_post([
            'ID'           => $page_id,
            'post_title'   => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_status'  => 'publish',
        ]);
    }
    if (is_wp_error($page_id)) {
        throw new RuntimeException('Failed to seed page: ' . $slug);
    }

    if (!empty($data['rank_math_meta'][$slug]) && is_array($data['rank_math_meta'][$slug])) {
        foreach ($data['rank_math_meta'][$slug] as $meta_key => $meta_value) {
            update_post_meta($page_id, $meta_key, $meta_value);
        }
    }

    $page_ids[$slug] = (int) $page_id;
}

if (!empty($page_ids['home'])) {
    update_option('show_on_front', 'page');
    update_option('page_on_front', $page_ids['home']);
}
if (!empty($page_ids['blog'])) {
    update_option('page_for_posts', $page_ids['blog']);
}

if (!empty($data['menus']) && is_array($data['menus'])) {
    $locations = get_theme_mod('nav_menu_locations', []);
    foreach ($data['menus'] as $location => $slugs) {
        $menu_name = $location === 'primary' ? 'Main Navigation' : ($location === 'footer' ? 'Footer Links' : ucfirst($location));
        $menu = wp_get_nav_menu_object($menu_name);
        if (!$menu) {
            $menu_id = wp_create_nav_menu($menu_name);
        } else {
            $menu_id = $menu->term_id;
        }
        $items = wp_get_nav_menu_items($menu_id, ['post_status' => 'publish,draft']);
        if ($items) {
            foreach ($items as $item) {
                wp_delete_post($item->ID, true);
            }
        }
        if (is_array($slugs)) {
            foreach ($slugs as $slug) {
                if (empty($page_ids[$slug])) {
                    continue;
                }
                wp_update_nav_menu_item($menu_id, 0, [
                    'menu-item-object'     => 'page',
                    'menu-item-object-id'  => $page_ids[$slug],
                    'menu-item-type'       => 'post_type',
                    'menu-item-status'     => 'publish',
                    'menu-item-title'      => get_the_title($page_ids[$slug]),
                ]);
            }
        }
        $locations[$location] = $menu_id;
    }
    set_theme_mod('nav_menu_locations', $locations);
}

echo 'Seed data applied.';
PHP

CONTENT_FILE="$CONTENT_FILE" $WP eval-file "$TMP_FILE"
rm -f "$TMP_FILE"
