<?php
/**
 * Theme bootstrap for Miss Mermaid · Sphynx.
 */

define( 'MM_SPHYNX_VERSION', wp_get_theme( 'mm-sphynx' )->get( 'Version' ) ?: '0.1.0' );

mm_sphynx_register_hooks();

function mm_sphynx_register_hooks(): void {
    add_action( 'after_setup_theme', 'mm_sphynx_setup' );
    add_action( 'wp_enqueue_scripts', 'mm_sphynx_enqueue_assets' );
    add_action( 'init', 'mm_sphynx_disable_emojis' );
    add_action( 'init', 'mm_sphynx_disable_embeds' );
}

function mm_sphynx_setup(): void {
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ] );

    register_nav_menus(
        [
            'primary' => __( 'Primary Menu', 'mm-sphynx' ),
            'footer'  => __( 'Footer Menu', 'mm-sphynx' ),
        ]
    );
}

function mm_sphynx_enqueue_assets(): void {
    wp_enqueue_style(
        'mm-sphynx-fonts',
        'https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600&family=Inter:wght@400;500;600;700&family=Lora:wght@400;500;600&family=Playfair+Display:wght@400;600;700&display=swap',
        [],
        null
    );

    $style_file    = get_stylesheet_directory() . '/style.css';
    $style_version = MM_SPHYNX_VERSION . '-' . ( file_exists( $style_file ) ? filemtime( $style_file ) : time() );

    wp_enqueue_style(
        'mm-sphynx-style',
        get_stylesheet_uri(),
        [],
        $style_version
    );

    $custom_css = '
        .wp-block-button__link:not(.has-background),
        .wp-element-button:not(.has-background) {
            background-color: var(--mm-color-gold, #C6A15B);
            color: var(--mm-color-black, #0B0B0F);
            transition: background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }
        .wp-block-button__link:not(.has-background):hover,
        .wp-element-button:not(.has-background):hover {
            background-color: var(--mm-color-gold-strong, #B08C4F);
            color: var(--mm-color-black, #0B0B0F);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.45);
            transform: translateY(-1px);
        }
        a:focus-visible,
        .wp-block-button__link:focus-visible,
        .wp-element-button:focus-visible {
            outline: 2px solid var(--mm-color-gold, #C6A15B);
            outline-offset: 3px;
            box-shadow: 0 0 0 3px rgba(11, 11, 15, 0.65);
        }
    ';

    wp_add_inline_style( 'mm-sphynx-style', $custom_css );
}

function mm_sphynx_disable_emojis(): void {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'emoji_svg_url', '__return_false' );
}

function mm_sphynx_disable_embeds(): void {
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
    remove_action( 'template_redirect', 'rest_output_link_header', 11 );
    remove_action( 'wp_head', 'wp_oembed_add_host_js' );
    remove_action( 'wp_footer', 'wp_oembed_add_host_js' );
    add_filter( 'embed_oembed_discover', '__return_false' );
    add_filter( 'tiny_mce_plugins', 'mm_sphynx_disable_embeds_tiny_mce' );
    add_action( 'wp_footer', 'mm_sphynx_deregister_wp_embed' );
}

function mm_sphynx_disable_embeds_tiny_mce( array $plugins ): array {
    return array_diff( $plugins, [ 'wpembed' ] );
}

function mm_sphynx_deregister_wp_embed(): void {
    wp_deregister_script( 'wp-embed' );
}
