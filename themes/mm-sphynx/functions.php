<?php
/**
 * Theme bootstrap for Miss Mermaid · Sphynx.
 */

define( 'MM_SPHYNX_VERSION', wp_get_theme( 'mm-sphynx' )->get( 'Version' ) ?: '0.1.0' );

mm_sphynx_register_hooks();

function mm_sphynx_register_hooks(): void {
    add_action( 'after_setup_theme', 'mm_sphynx_setup' );
    add_action( 'wp_enqueue_scripts', 'mm_sphynx_enqueue_assets' );
    add_action( 'wp_enqueue_scripts', 'mm_sphynx_enqueue_kittens_assets' );
    add_action( 'init', 'mm_sphynx_disable_emojis' );
    add_action( 'init', 'mm_sphynx_disable_embeds' );
    add_action( 'init', 'mm_sphynx_register_content_models' );
    add_action( 'acf/init', 'mm_sphynx_register_acf_groups' );
    add_action( 'after_switch_theme', 'mm_sphynx_flush_rewrite_rules' );
    add_action( 'admin_init', 'mm_sphynx_register_kitten_admin_views' );
    add_action( 'restrict_manage_posts', 'mm_sphynx_render_kitten_admin_filters' );
    add_action( 'pre_get_posts', 'mm_sphynx_handle_kitten_queries' );
    add_action( 'init', 'mm_sphynx_register_roles' );
    add_action( 'init', 'mm_sphynx_handle_magic_request' );
    add_action( 'init', 'mm_sphynx_handle_magic_login' );
    add_action( 'fluentform_submission_inserted', 'mm_sphynx_on_form_submission', 10, 4 );
    add_filter( 'posts_orderby', 'mm_sphynx_apply_custom_status_order', 10, 2 );
    add_filter( 'acf/validate_value/name=kitten_id', 'mm_sphynx_validate_unique_kitten_id', 10, 4 );
    add_filter( 'acf/prepare_field/name=age_auto', 'mm_sphynx_prepare_age_field' );
    add_filter( 'manage_users_columns', 'mm_sphynx_users_columns' );
    add_filter( 'manage_users_custom_column', 'mm_sphynx_users_custom_column', 10, 3 );
    add_filter( 'user_row_actions', 'mm_sphynx_applicant_row_actions', 10, 2 );
    add_action( 'admin_init', 'mm_sphynx_handle_admin_applicant_action' );
    add_action( 'admin_notices', 'mm_sphynx_admin_notices' );
    add_shortcode( 'mm_magic_login', 'mm_sphynx_magic_login_shortcode' );
    add_shortcode( 'mm_application_portal', 'mm_sphynx_application_portal_shortcode' );
    add_shortcode( 'mm_form', 'mm_sphynx_form_shortcode' );
}

function mm_arr_get( $array, $key, $default = '' ) {
    if ( is_array( $array ) && array_key_exists( $key, $array ) ) {
        return $array[ $key ];
    }

    return $default;
}

function mm_bool( $value ): bool {
    $filtered = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

    return null === $filtered ? (bool) $value : $filtered;
}

function mm_sphynx_placeholder_image(): string {
    return trailingslashit( get_stylesheet_directory_uri() ) . 'assets/img/placeholder.svg';
}

function mm_sphynx_setup(): void {
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ] );
    add_theme_support( 'post-thumbnails' );
    add_image_size( 'mm-card', 640, 480, true );

    register_nav_menus(
        [
            'primary' => __( 'Primary Menu', 'mm-sphynx' ),
            'footer'  => __( 'Footer Menu', 'mm-sphynx' ),
        ]
    );
}

function mm_sphynx_enqueue_assets(): void {
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

function mm_sphynx_register_content_models(): void {
    $kitten_labels = [
        'name'               => __( 'Kittens', 'mm-sphynx' ),
        'singular_name'      => __( 'Kitten', 'mm-sphynx' ),
        'add_new_item'       => __( 'Add New Kitten', 'mm-sphynx' ),
        'edit_item'          => __( 'Edit Kitten', 'mm-sphynx' ),
        'new_item'           => __( 'New Kitten', 'mm-sphynx' ),
        'view_item'          => __( 'View Kitten', 'mm-sphynx' ),
        'view_items'         => __( 'View Kittens', 'mm-sphynx' ),
        'search_items'       => __( 'Search Kittens', 'mm-sphynx' ),
        'not_found'          => __( 'No kittens found', 'mm-sphynx' ),
        'not_found_in_trash' => __( 'No kittens found in Trash', 'mm-sphynx' ),
        'all_items'          => __( 'All Kittens', 'mm-sphynx' ),
        'menu_name'          => __( 'Kittens', 'mm-sphynx' ),
    ];

    register_post_type(
        'kitten',
        [
            'labels'             => $kitten_labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_rest'       => true,
            'menu_icon'          => 'dashicons-pets',
            'supports'           => [ 'title', 'thumbnail', 'custom-fields' ],
            'has_archive'        => true,
            'rewrite'            => [
                'slug'       => 'kittens',
                'with_front' => false,
            ],
            'hierarchical'       => false,
            'menu_position'      => 6,
        ]
    );

    $litter_labels = [
        'name'               => __( 'Litters', 'mm-sphynx' ),
        'singular_name'      => __( 'Litter', 'mm-sphynx' ),
        'add_new_item'       => __( 'Add New Litter', 'mm-sphynx' ),
        'edit_item'          => __( 'Edit Litter', 'mm-sphynx' ),
        'new_item'           => __( 'New Litter', 'mm-sphynx' ),
        'view_item'          => __( 'View Litter', 'mm-sphynx' ),
        'view_items'         => __( 'View Litters', 'mm-sphynx' ),
        'search_items'       => __( 'Search Litters', 'mm-sphynx' ),
        'not_found'          => __( 'No litters found', 'mm-sphynx' ),
        'not_found_in_trash' => __( 'No litters found in Trash', 'mm-sphynx' ),
        'all_items'          => __( 'All Litters', 'mm-sphynx' ),
        'menu_name'          => __( 'Litters', 'mm-sphynx' ),
    ];

    register_post_type(
        'litter',
        [
            'labels'             => $litter_labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_admin_bar'  => true,
            'show_in_rest'       => true,
            'menu_icon'          => 'dashicons-category',
            'supports'           => [ 'title', 'custom-fields' ],
            'has_archive'        => false,
            'rewrite'            => false,
            'hierarchical'       => false,
        ]
    );
}

function mm_sphynx_flush_rewrite_rules(): void {
    mm_sphynx_register_content_models();
    flush_rewrite_rules();
}

function mm_sphynx_register_acf_groups(): void {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group(
        [
            'key'                   => 'group_mm_sphynx_kitten',
            'title'                 => __( 'Kitten Details', 'mm-sphynx' ),
            'fields'                => [
                [
                    'key'          => 'field_mm_kitten_id',
                    'label'        => __( 'Kitten ID', 'mm-sphynx' ),
                    'name'         => 'kitten_id',
                    'type'         => 'text',
                    'required'     => 1,
                    'instructions' => __( 'Unique identifier used internally and across all collateral.', 'mm-sphynx' ),
                    'wrapper'      => [ 'width' => '20' ],
                ],
                [
                    'key'          => 'field_mm_status',
                    'label'        => __( 'Status', 'mm-sphynx' ),
                    'name'         => 'status',
                    'type'         => 'select',
                    'choices'      => [
                        'available' => __( 'Available', 'mm-sphynx' ),
                        'upcoming'  => __( 'Upcoming', 'mm-sphynx' ),
                        'reserved'  => __( 'Reserved', 'mm-sphynx' ),
                        'adopted'   => __( 'Adopted', 'mm-sphynx' ),
                    ],
                    'default_value' => 'available',
                    'wrapper'       => [ 'width' => '20' ],
                ],
                [
                    'key'          => 'field_mm_price',
                    'label'        => __( 'Price (USD)', 'mm-sphynx' ),
                    'name'         => 'price',
                    'type'         => 'number',
                    'wrapper'      => [ 'width' => '20' ],
                    'prepend'      => '$',
                    'min'          => 0,
                    'step'         => 1,
                ],
                [
                    'key'          => 'field_mm_sex',
                    'label'        => __( 'Sex', 'mm-sphynx' ),
                    'name'         => 'sex',
                    'type'         => 'select',
                    'choices'      => [
                        'male'   => __( 'Male', 'mm-sphynx' ),
                        'female' => __( 'Female', 'mm-sphynx' ),
                    ],
                    'wrapper'      => [ 'width' => '20' ],
                ],
                [
                    'key'          => 'field_mm_color_text',
                    'label'        => __( 'Color Notes', 'mm-sphynx' ),
                    'name'         => 'color',
                    'type'         => 'text',
                    'instructions' => __( 'Comma separated. Example: "blue, cream, bicolor".', 'mm-sphynx' ),
                    'wrapper'      => [ 'width' => '20' ],
                ],
                [
                    'key'          => 'field_mm_birthday',
                    'label'        => __( 'Birthday', 'mm-sphynx' ),
                    'name'         => 'birthday',
                    'type'         => 'date_picker',
                    'display_format' => 'Y-m-d',
                    'return_format'  => 'Y-m-d',
                    'first_day'      => 1,
                    'wrapper'        => [ 'width' => '20' ],
                ],
                [
                    'key'          => 'field_mm_age_hint',
                    'label'        => __( 'Age Hint', 'mm-sphynx' ),
                    'name'         => 'age_hint',
                    'type'         => 'text',
                    'instructions' => __( 'Friendly age label (e.g., "11 months").', 'mm-sphynx' ),
                    'wrapper'      => [ 'width' => '20' ],
                ],
                [
                    'key'          => 'field_mm_temperament_tags',
                    'label'        => __( 'Temperament Tags', 'mm-sphynx' ),
                    'name'         => 'temperament_tags',
                    'type'         => 'textarea',
                    'instructions' => __( 'Semicolon separated tags (e.g., "gentle;affectionate;playful").', 'mm-sphynx' ),
                    'rows'         => 2,
                    'wrapper'      => [ 'width' => '40' ],
                ],
                [
                    'key'          => 'field_mm_short_description',
                    'label'        => __( 'Card Summary', 'mm-sphynx' ),
                    'name'         => 'short_description',
                    'type'         => 'textarea',
                    'rows'         => 3,
                    'instructions' => __( 'Displayed on grid cards and used as excerpt.', 'mm-sphynx' ),
                ],
                [
                    'key'          => 'field_mm_cover_image',
                    'label'        => __( 'Cover Image URL', 'mm-sphynx' ),
                    'name'         => 'cover_image',
                    'type'         => 'url',
                    'instructions' => __( 'External or media library URL used for grid thumbnail.', 'mm-sphynx' ),
                    'wrapper'      => [ 'width' => '50' ],
                ],
                [
                    'key'          => 'field_mm_gallery_urls',
                    'label'        => __( 'Gallery URLs', 'mm-sphynx' ),
                    'name'         => 'gallery',
                    'type'         => 'repeater',
                    'layout'       => 'table',
                    'button_label' => __( 'Add Image', 'mm-sphynx' ),
                    'sub_fields'   => [
                        [
                            'key'   => 'field_mm_gallery_url_single',
                            'label' => __( 'Image URL', 'mm-sphynx' ),
                            'name'  => 'image_url',
                            'type'  => 'url',
                        ],
                    ],
                ],
                [
                    'key'          => 'field_mm_video_urls',
                    'label'        => __( 'Video URLs', 'mm-sphynx' ),
                    'name'         => 'videos',
                    'type'         => 'repeater',
                    'layout'       => 'table',
                    'button_label' => __( 'Add Video URL', 'mm-sphynx' ),
                    'sub_fields'   => [
                        [
                            'key'   => 'field_mm_video_url_single',
                            'label' => __( 'Video URL', 'mm-sphynx' ),
                            'name'  => 'video_url',
                            'type'  => 'url',
                        ],
                    ],
                ],
                [
                    'key'          => 'field_mm_parent_sire',
                    'label'        => __( 'Sire', 'mm-sphynx' ),
                    'name'         => 'parent_sire',
                    'type'         => 'text',
                    'wrapper'      => [ 'width' => '50' ],
                ],
                [
                    'key'          => 'field_mm_parent_dam',
                    'label'        => __( 'Dam', 'mm-sphynx' ),
                    'name'         => 'parent_dam',
                    'type'         => 'text',
                    'wrapper'      => [ 'width' => '50' ],
                ],
                [
                    'key'          => 'field_mm_health_notes',
                    'label'        => __( 'Health Notes', 'mm-sphynx' ),
                    'name'         => 'health_notes',
                    'type'         => 'textarea',
                    'rows'         => 3,
                ],
                [
                    'key'          => 'field_mm_value_points',
                    'label'        => __( 'Value Points', 'mm-sphynx' ),
                    'name'         => 'value_points',
                    'type'         => 'textarea',
                    'rows'         => 3,
                ],
                [
                    'key'          => 'field_mm_care_profile',
                    'label'        => __( 'Care Profile', 'mm-sphynx' ),
                    'name'         => 'care_profile',
                    'type'         => 'textarea',
                    'rows'         => 3,
                ],
                [
                    'key'          => 'field_mm_apply_url',
                    'label'        => __( 'Apply URL Override', 'mm-sphynx' ),
                    'name'         => 'apply_url',
                    'type'         => 'url',
                    'instructions' => __( 'Defaults to /apply?kitten={ID} when empty.', 'mm-sphynx' ),
                    'wrapper'      => [ 'width' => '50' ],
                ],
                [
                    'key'          => 'field_mm_apply_text',
                    'label'        => __( 'Apply Button Text', 'mm-sphynx' ),
                    'name'         => 'apply_text',
                    'type'         => 'text',
                    'default_value'=> __( 'Apply for this kitten', 'mm-sphynx' ),
                    'wrapper'      => [ 'width' => '50' ],
                ],
                [
                    'key'     => 'field_mm_featured',
                    'label'   => __( 'Featured', 'mm-sphynx' ),
                    'name'    => 'featured',
                    'type'    => 'true_false',
                    'ui'      => 1,
                    'ui_on_text'  => __( 'Yes', 'mm-sphynx' ),
                    'ui_off_text' => __( 'No', 'mm-sphynx' ),
                ],
            ],
            'location'              => [
                [
                    [
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'kitten',
                    ],
                ],
            ],
            'position'              => 'normal',
            'style'                 => 'default',
            'active'                => true,
        ]
    );

    acf_add_local_field_group(
        [
            'key'      => 'group_mm_sphynx_litter',
            'title'    => __( 'Litter Details', 'mm-sphynx' ),
            'fields'   => [
                [
                    'key'      => 'field_mm_litter_id',
                    'label'    => __( 'Litter ID', 'mm-sphynx' ),
                    'name'     => 'litter_id',
                    'type'     => 'text',
                    'required' => 1,
                ],
                [
                    'key'   => 'field_mm_litter_queen',
                    'label' => __( 'Queen', 'mm-sphynx' ),
                    'name'  => 'queen',
                    'type'  => 'text',
                ],
                [
                    'key'   => 'field_mm_litter_sire',
                    'label' => __( 'Sire', 'mm-sphynx' ),
                    'name'  => 'sire',
                    'type'  => 'text',
                ],
                [
                    'key'   => 'field_mm_due_date',
                    'label' => __( 'Due Date', 'mm-sphynx' ),
                    'name'  => 'due_date',
                    'type'  => 'date_picker',
                    'display_format' => 'Y-m-d',
                    'return_format'  => 'Y-m-d',
                    'first_day'      => 1,
                ],
                [
                    'key'   => 'field_mm_due_window',
                    'label' => __( 'Due Window', 'mm-sphynx' ),
                    'name'  => 'due_window',
                    'type'  => 'text',
                    'instructions' => __( 'Example: 2025-12 ~ 2026-01', 'mm-sphynx' ),
                ],
                [
                    'key'   => 'field_mm_born_date',
                    'label' => __( 'Born Date', 'mm-sphynx' ),
                    'name'  => 'born_date',
                    'type'  => 'date_picker',
                    'display_format' => 'Y-m-d',
                    'return_format'  => 'Y-m-d',
                    'first_day'      => 1,
                ],
                [
                    'key'     => 'field_mm_litter_status',
                    'label'   => __( 'Status', 'mm-sphynx' ),
                    'name'    => 'status',
                    'type'    => 'select',
                    'choices' => [
                        'planned'  => __( 'Planned', 'mm-sphynx' ),
                        'confirmed'=> __( 'Confirmed', 'mm-sphynx' ),
                        'born'     => __( 'Born', 'mm-sphynx' ),
                        'retired'  => __( 'Retired', 'mm-sphynx' ),
                    ],
                    'return_format' => 'value',
                ],
                [
                    'key'   => 'field_mm_expected_colors',
                    'label' => __( 'Expected Colors', 'mm-sphynx' ),
                    'name'  => 'expected_colors',
                    'type'  => 'textarea',
                    'rows'  => 2,
                    'instructions' => __( 'Separate values with commas or semicolons.', 'mm-sphynx' ),
                ],
                [
                    'key'   => 'field_mm_slots_total',
                    'label' => __( 'Total Slots', 'mm-sphynx' ),
                    'name'  => 'slots_total',
                    'type'  => 'number',
                    'wrapper' => [ 'width' => '50' ],
                ],
                [
                    'key'   => 'field_mm_policy_highlight',
                    'label' => __( 'Policy Highlight', 'mm-sphynx' ),
                    'name'  => 'policy_highlight',
                    'type'  => 'textarea',
                    'rows'  => 3,
                ],
                [
                    'key'   => 'field_mm_join_text',
                    'label' => __( 'Join CTA Text', 'mm-sphynx' ),
                    'name'  => 'join_text',
                    'type'  => 'text',
                    'wrapper' => [ 'width' => '50' ],
                ],
                [
                    'key'   => 'field_mm_join_url',
                    'label' => __( 'Join CTA URL', 'mm-sphynx' ),
                    'name'  => 'join_url',
                    'type'  => 'url',
                    'wrapper' => [ 'width' => '50' ],
                ],
                [
                    'key'   => 'field_mm_litter_note',
                    'label' => __( 'Note', 'mm-sphynx' ),
                    'name'  => 'note',
                    'type'  => 'textarea',
                    'rows'  => 4,
                ],
            ],
            'location' => [
                [
                    [
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'litter',
                    ],
                ],
            ],
            'position' => 'normal',
            'style'    => 'default',
            'active'   => true,
        ]
    );
}

function mm_sphynx_register_kitten_admin_views(): void {
    add_filter( 'manage_kitten_posts_columns', 'mm_sphynx_customize_kitten_columns' );
    add_action( 'manage_kitten_posts_custom_column', 'mm_sphynx_render_kitten_columns', 10, 2 );
}

function mm_sphynx_customize_kitten_columns( array $columns ): array {
    $new_columns = [
        'cb'         => $columns['cb'] ?? '<input type="checkbox" />',
        'id'         => __( 'ID', 'mm-sphynx' ),
        'kitten_id'  => __( 'Kitten ID', 'mm-sphynx' ),
        'sex'        => __( 'Sex', 'mm-sphynx' ),
        'color'      => __( 'Color', 'mm-sphynx' ),
        'status'     => __( 'Status', 'mm-sphynx' ),
        'price'      => __( 'Price', 'mm-sphynx' ),
        'featured'   => __( 'Featured', 'mm-sphynx' ),
        'date'       => $columns['date'] ?? __( 'Date', 'mm-sphynx' ),
    ];

    return $new_columns;
}

function mm_sphynx_render_kitten_columns( string $column, int $post_id ): void {
    switch ( $column ) {
        case 'id':
            echo (int) $post_id;
            break;
        case 'kitten_id':
            echo esc_html( (string) get_field( 'kitten_id', $post_id ) );
            break;
        case 'sex':
            $sex = (string) get_field( 'sex', $post_id );
            echo esc_html( ucfirst( $sex ) );
            break;
        case 'color':
            $color = get_field( 'color', $post_id );
            if ( is_array( $color ) ) {
                echo esc_html( implode( ', ', array_map( 'ucfirst', $color ) ) );
            } else {
                echo esc_html( (string) $color );
            }
            break;
        case 'status':
            $status = (string) get_field( 'status', $post_id );
            echo esc_html( ucfirst( $status ) );
            break;
        case 'price':
            $price = get_field( 'price', $post_id );
            if ( '' !== $price && null !== $price ) {
                echo esc_html( '$' . number_format_i18n( (float) $price, 0 ) );
            }
            break;
        case 'featured':
            $featured = (bool) get_field( 'featured', $post_id );
            echo $featured ? '&#10003;' : '&mdash;';
            break;
    }
}

function mm_sphynx_render_kitten_admin_filters( string $post_type ): void {
    if ( 'kitten' !== $post_type ) {
        return;
    }

    $current_status   = isset( $_GET['kitten_status'] ) ? sanitize_text_field( wp_unslash( $_GET['kitten_status'] ) ) : '';
    $current_featured = isset( $_GET['kitten_featured'] ) ? sanitize_text_field( wp_unslash( $_GET['kitten_featured'] ) ) : '';

    ?>
    <select name="kitten_status" id="filter-by-kittten-status">
        <option value=""><?php esc_html_e( 'All statuses', 'mm-sphynx' ); ?></option>
        <?php foreach ( mm_sphynx_get_status_choices() as $value => $label ) : ?>
            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_status, $value ); ?>>
                <?php echo esc_html( $label ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select name="kitten_featured" id="filter-by-kittten-featured">
        <option value=""><?php esc_html_e( 'Featured?', 'mm-sphynx' ); ?></option>
        <option value="1" <?php selected( $current_featured, '1' ); ?>><?php esc_html_e( 'Featured', 'mm-sphynx' ); ?></option>
        <option value="0" <?php selected( $current_featured, '0' ); ?>><?php esc_html_e( 'Not featured', 'mm-sphynx' ); ?></option>
    </select>
    <?php
}

function mm_sphynx_handle_kitten_queries( \WP_Query $query ): void {
    if ( is_admin() && $query->is_main_query() && 'kitten' === $query->get( 'post_type' ) ) {
        $meta_query = [];

        if ( isset( $_GET['kitten_status'] ) && '' !== $_GET['kitten_status'] ) {
            $status       = sanitize_text_field( wp_unslash( $_GET['kitten_status'] ) );
            $meta_query[] = [
                'key'     => 'status',
                'value'   => $status,
                'compare' => '=',
            ];
        }

        if ( isset( $_GET['kitten_featured'] ) && '' !== $_GET['kitten_featured'] ) {
            $featured     = sanitize_text_field( wp_unslash( $_GET['kitten_featured'] ) );
            $meta_query[] = [
                'key'   => 'featured',
                'value' => '1' === $featured ? '1' : '0',
            ];
        }

        if ( ! empty( $meta_query ) ) {
            if ( count( $meta_query ) > 1 ) {
                $meta_query['relation'] = 'AND';
            }

            $query->set( 'meta_query', $meta_query );
        }

        return;
    }

    if ( $query->is_main_query() && ! is_admin() && ( $query->is_post_type_archive( 'kitten' ) || 'kitten' === $query->get( 'post_type' ) ) ) {
        $meta_query = [
            'featured_clause' => [
                'key'   => 'featured',
                'type'  => 'NUMERIC',
            ],
            'status_clause'   => [
                'key'   => 'status',
                'type'  => 'CHAR',
            ],
        ];

        $query->set( 'posts_per_page', -1 );
        $query->set(
            'orderby',
            [
                'featured_clause' => 'DESC',
                'status_clause'   => 'ASC',
                'date'            => 'DESC',
            ]
        );

        $query->set( 'meta_query', $meta_query );
        $query->set( 'mm_sphynx_status_sort', 1 );
    }
}

function mm_sphynx_apply_custom_status_order( string $orderby, \WP_Query $query ): string {
    if ( ! $query->get( 'mm_sphynx_status_sort' ) ) {
        return $orderby;
    }

    global $wpdb;
    // Because WP reuses the same meta table join for multiple clauses, fetch aliases.
    $meta_clauses = $query->get( 'meta_query' );
    $aliases      = $query->meta_query->get_clauses();

    $featured_alias = $aliases['featured_clause']['alias'] ?? '';
    $status_alias   = $aliases['status_clause']['alias'] ?? '';

    if ( ! $featured_alias || ! $status_alias ) {
        return $orderby;
    }

    $status_values = array_map( 'esc_sql', array_keys( mm_sphynx_get_status_choices() ) );
    $status_sql    = sprintf(
        "FIELD(%s.meta_value, '%s')",
        $status_alias,
        implode( "','", $status_values )
    );

    $orderby = sprintf(
        '%1$s.meta_value+0 DESC, %2$s, %3$s',
        $featured_alias,
        $status_sql,
        "{$wpdb->posts}.post_date DESC"
    );

    return $orderby;
}

function mm_sphynx_validate_unique_kitten_id( $valid, $value, $field, $input ) {
    if ( true !== $valid || ! $value ) {
        return $valid;
    }

    $value   = (string) $value;
    $post_id = isset( $_POST['post_ID'] ) ? (int) $_POST['post_ID'] : 0;

    $existing = get_posts(
        [
            'post_type'      => 'kitten',
            'post_status'    => [ 'publish', 'draft', 'pending', 'future', 'private' ],
            'fields'         => 'ids',
            'posts_per_page' => 1,
            'exclude'        => $post_id ? [ $post_id ] : [],
            'meta_query'     => [
                [
                    'key'   => 'kitten_id',
                    'value' => $value,
                ],
            ],
        ]
    );

    if ( ! empty( $existing ) ) {
        $valid = __( 'This Kitten ID is already in use. Please provide a unique value.', 'mm-sphynx' );
    }

    return $valid;
}

function mm_sphynx_prepare_age_field( array $field ): array {
    if ( ! function_exists( 'acf_get_post_id' ) ) {
        return $field;
    }

    $post_id = acf_get_post_id();
    if ( ! $post_id ) {
        return $field;
    }

    $birthdate = get_field( 'birthdate', $post_id );
    if ( ! $birthdate ) {
        $field['value'] = '';
        return $field;
    }

    try {
        $birth = new DateTime( $birthdate );
        $now   = new DateTime( 'now', wp_timezone() );
        $diff  = $birth->diff( $now );

        $parts = [];
        if ( $diff->y > 0 ) {
            $parts[] = sprintf(
                _n( '%s year', '%s years', $diff->y, 'mm-sphynx' ),
                number_format_i18n( $diff->y )
            );
        }
        if ( $diff->m > 0 ) {
            $parts[] = sprintf(
                _n( '%s month', '%s months', $diff->m, 'mm-sphynx' ),
                number_format_i18n( $diff->m )
            );
        }
        if ( empty( $parts ) && $diff->d >= 0 ) {
            $parts[] = sprintf(
                _n( '%s day', '%s days', $diff->d, 'mm-sphynx' ),
                number_format_i18n( $diff->d )
            );
        }

        $field['value'] = implode( ' ', $parts );
    } catch ( Exception $e ) {
        $field['value'] = '';
    }

    return $field;
}

function mm_sphynx_get_status_choices(): array {
    return [
        'available' => __( 'Available', 'mm-sphynx' ),
        'upcoming'  => __( 'Upcoming', 'mm-sphynx' ),
        'reserved'  => __( 'Reserved', 'mm-sphynx' ),
        'adopted'   => __( 'Adopted', 'mm-sphynx' ),
    ];
}

function mm_sphynx_parse_text_list( $value ): array {
    if ( ! is_string( $value ) ) {
        return [];
    }

    $parts = preg_split( '/[,;\n]+/', $value );
    if ( ! is_array( $parts ) ) {
        return [];
    }

    $parts = array_map(
        static function ( $item ) {
            return trim( (string) $item );
        },
        $parts
    );

    $parts = array_filter(
        $parts,
        static function ( $item ) {
            return '' !== $item;
        }
    );

    return array_values( $parts );
}

function mm_sphynx_get_forms_registry(): array {
    $registry = get_option( 'mm_sphynx_forms', [] );
    return is_array( $registry ) ? $registry : [];
}

function mm_sphynx_get_form_id( string $slug ): ?int {
    $registry = mm_sphynx_get_forms_registry();
    $id       = $registry[ $slug ] ?? null;

    return $id ? (int) $id : null;
}

function mm_sphynx_get_kitten_dataset(): array {
    static $cache;
    if ( null !== $cache ) {
        return $cache;
    }

    $query = new WP_Query(
        [
            'post_type'      => 'kitten',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => [
                'menu_order' => 'ASC',
                'date'       => 'DESC',
            ],
        ]
    );

    $kittens        = [];
    $status_choices = mm_sphynx_get_status_choices();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id      = get_the_ID();
            $kitten_id    = (string) get_field( 'kitten_id', $post_id );
            $status       = get_field( 'status', $post_id ) ?: 'available';
            $price        = get_field( 'price', $post_id );
            $colors_raw   = (string) get_field( 'color', $post_id );
            $featured     = (bool) get_field( 'featured', $post_id );
            $birthday     = get_field( 'birthday', $post_id );
            $age_hint     = (string) get_field( 'age_hint', $post_id );
            $temperaments = mm_sphynx_parse_text_list( (string) get_field( 'temperament_tags', $post_id ) );
            $short_desc   = (string) get_field( 'short_description', $post_id );
            $cover_image  = (string) get_field( 'cover_image', $post_id );
            $health_notes = (string) get_field( 'health_notes', $post_id );
            $value_points = mm_sphynx_parse_text_list( (string) get_field( 'value_points', $post_id ) );
            $care_profile = (string) get_field( 'care_profile', $post_id );
            $apply_url    = (string) get_field( 'apply_url', $post_id );
            $apply_text   = (string) get_field( 'apply_text', $post_id );

            $gallery_rows = get_field( 'gallery', $post_id ) ?: [];
            $gallery_urls = [];
            if ( is_array( $gallery_rows ) ) {
                foreach ( $gallery_rows as $row ) {
                    if ( isset( $row['image_url'] ) && $row['image_url'] ) {
                        $gallery_urls[] = esc_url_raw( $row['image_url'] );
                    }
                }
            }

            $video_rows = get_field( 'videos', $post_id ) ?: [];
            $video_urls = [];
            if ( is_array( $video_rows ) ) {
                foreach ( $video_rows as $row ) {
                    if ( isset( $row['video_url'] ) && $row['video_url'] ) {
                        $video_urls[] = esc_url_raw( $row['video_url'] );
                    }
                }
            }

            if ( ! $cover_image && ! empty( $gallery_urls ) ) {
                $cover_image = $gallery_urls[0];
            }

            $colors = mm_sphynx_parse_text_list( $colors_raw );

            $age_auto = mm_sphynx_calculate_age_string( $birthday );
            if ( ! $age_hint && $age_auto ) {
                $age_hint = $age_auto;
            }

            if ( ! $short_desc && has_excerpt( $post_id ) ) {
                $short_desc = get_the_excerpt();
            }

            if ( ! $apply_url ) {
                $apply_url = home_url( '/apply' );
                if ( $kitten_id ) {
                    $apply_url = add_query_arg( 'kitten', rawurlencode( $kitten_id ), $apply_url );
                }
            }

            if ( ! $apply_text ) {
                $apply_text = __( 'Apply for this kitten', 'mm-sphynx' );
            }

            $kitten = [
                'id'           => $post_id,
                'title'        => get_the_title(),
                'permalink'    => get_permalink(),
                'kitten_id'    => $kitten_id,
                'sex'          => get_field( 'sex', $post_id ),
                'color'        => $colors,
                'temperament'  => $temperaments,
                'price'        => '' !== $price && null !== $price ? (float) $price : null,
                'status'       => $status,
                'status_label' => $status_choices[ $status ] ?? ucfirst( $status ),
                'featured'     => $featured,
                'thumbnail'    => $cover_image,
                'cover_image'  => $cover_image,
                'gallery'      => $gallery_urls,
                'videos'       => $video_urls,
                'birthdate'    => $birthday,
                'age'          => $age_auto,
                'age_hint'     => $age_hint,
                'short_description' => $short_desc,
                'health_notes' => $health_notes,
                'value_points' => $value_points,
                'care_profile' => $care_profile,
                'parents'      => [
                    'sire' => (string) get_field( 'parent_sire', $post_id ),
                    'dam'  => (string) get_field( 'parent_dam', $post_id ),
                ],
                'apply_url'    => $apply_url,
                'apply_text'   => $apply_text,
                'excerpt'      => $short_desc,
                'timestamp'    => (int) get_post_time( 'U', true, $post_id ),
            ];

            $kittens[] = $kitten;
        }
        wp_reset_postdata();
    }

    $status_order  = array_keys( $status_choices );
    $status_weight = array_flip( $status_order );

    usort(
        $kittens,
        static function ( $a, $b ) use ( $status_weight ) {
            if ( $a['featured'] !== $b['featured'] ) {
                return $a['featured'] ? -1 : 1;
            }

            $weight_a = $status_weight[ $a['status'] ] ?? PHP_INT_MAX;
            $weight_b = $status_weight[ $b['status'] ] ?? PHP_INT_MAX;

            if ( $weight_a !== $weight_b ) {
                return $weight_a - $weight_b;
            }

            return ( $b['timestamp'] ?? 0 ) <=> ( $a['timestamp'] ?? 0 );
        }
    );

    $available = array_values(
        array_filter(
            $kittens,
            static function ( $kitten ) {
                return 'available' === $kitten['status'];
            }
        )
    );

    $reserve = array_values(
        array_filter(
            $kittens,
            static function ( $kitten ) {
                return 'available' !== $kitten['status'];
            }
        )
    );

    $cache = [
        'all'       => $kittens,
        'available' => $available,
        'reserve'   => $reserve,
    ];

    return $cache;
}

function mm_sphynx_calculate_age_string( $birthdate ): string {
    if ( ! $birthdate ) {
        return '';
    }

    try {
        $birth = new DateTime( $birthdate );
        $now   = new DateTime( 'now', wp_timezone() );
        $diff  = $birth->diff( $now );
    } catch ( Exception $e ) {
        return '';
    }

    $parts = [];
    if ( $diff->y > 0 ) {
        $parts[] = sprintf(
            _n( '%s year', '%s years', $diff->y, 'mm-sphynx' ),
            number_format_i18n( $diff->y )
        );
    }
    if ( $diff->m > 0 ) {
        $parts[] = sprintf(
            _n( '%s month', '%s months', $diff->m, 'mm-sphynx' ),
            number_format_i18n( $diff->m )
        );
    }
    if ( empty( $parts ) && $diff->d >= 0 ) {
        $parts[] = sprintf(
            _n( '%s day', '%s days', $diff->d, 'mm-sphynx' ),
            number_format_i18n( $diff->d )
        );
    }

    return implode( ' ', $parts );
}

function mm_sphynx_get_litter_timeline(): array {
    $query = new WP_Query(
        [
            'post_type'      => 'litter',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => [
                'date' => 'DESC',
            ],
        ]
    );

    $timeline        = [];
    $status_choices  = [
        'planned'   => __( 'Planned', 'mm-sphynx' ),
        'confirmed' => __( 'Confirmed', 'mm-sphynx' ),
        'born'      => __( 'Born', 'mm-sphynx' ),
        'retired'   => __( 'Retired', 'mm-sphynx' ),
    ];

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id = get_the_ID();
            $status  = get_field( 'status', $post_id ) ?: 'planned';

            $litter_code = get_field( 'litter_id', $post_id ) ?: get_the_title();
            $due_window = get_field( 'due_window', $post_id );
            $expected_colors = mm_sphynx_parse_text_list( (string) get_field( 'expected_colors', $post_id ) );

            $timeline[] = [
                'id'              => $post_id,
                'title'           => $litter_code,
                'queen'           => get_field( 'queen', $post_id ),
                'sire'            => get_field( 'sire', $post_id ),
                'due_date'        => get_field( 'due_date', $post_id ),
                'due_window'      => $due_window,
                'born_date'       => get_field( 'born_date', $post_id ),
                'status'          => $status,
                'status_label'    => $status_choices[ $status ] ?? ucfirst( $status ),
                'expected_colors' => $expected_colors,
                'slots_total'     => (int) get_field( 'slots_total', $post_id ),
                'policy_highlight'=> get_field( 'policy_highlight', $post_id ),
                'join_text'       => get_field( 'join_text', $post_id ) ?: __( 'Join waitlist', 'mm-sphynx' ),
                'join_url'        => get_field( 'join_url', $post_id ) ?: add_query_arg( 'litter', sanitize_title( $litter_code ), home_url( '/waitlist' ) ),
                'note'            => get_field( 'note', $post_id ),
            ];
        }
        wp_reset_postdata();
    }

    return $timeline;
}

function mm_sphynx_get_adoption_flow(): array {
    $steps = get_option( 'mm_sphynx_adoption_flow', [] );
    if ( ! is_array( $steps ) ) {
        return [];
    }

    usort(
        $steps,
        static function ( $a, $b ) {
            return ( $a['order'] ?? 0 ) <=> ( $b['order'] ?? 0 );
        }
    );

    return array_values( $steps );
}

function mm_sphynx_enqueue_kittens_assets(): void {
    if ( ! ( is_post_type_archive( 'kitten' ) || is_page() && 'kittens' === get_post_field( 'post_name', get_queried_object_id() ) ) ) {
        return;
    }

    $style_path = get_stylesheet_directory() . '/assets/css/kittens.css';
    $script_path = get_stylesheet_directory() . '/assets/js/kittens.js';

    $style_version  = file_exists( $style_path ) ? filemtime( $style_path ) : MM_SPHYNX_VERSION;
    $script_version = file_exists( $script_path ) ? filemtime( $script_path ) : MM_SPHYNX_VERSION;

    wp_enqueue_style(
        'mm-sphynx-kittens',
        get_stylesheet_directory_uri() . '/assets/css/kittens.css',
        [],
        $style_version
    );

    wp_enqueue_script(
        'mm-sphynx-kittens',
        get_stylesheet_directory_uri() . '/assets/js/kittens.js',
        [],
        $script_version,
        true
    );

    $dataset = mm_sphynx_get_kitten_dataset();
    $litters = mm_sphynx_get_litter_timeline();
    $adoption_flow = mm_sphynx_get_adoption_flow();

    wp_localize_script(
        'mm-sphynx-kittens',
        'mmSphynxKittens',
        [
            'kittens'      => $dataset['all'],
            'forms'        => [
                'apply'    => mm_sphynx_get_form_id( 'adoption_apply' ),
                'waitlist' => mm_sphynx_get_form_id( 'waitlist' ) ?: mm_sphynx_get_form_id( 'adoption_apply' ),
                'select'   => mm_sphynx_get_form_id( 'select_kitten' ),
            ],
            'litters'      => $litters,
            'adoptionFlow' => $adoption_flow,
            'waitlistUrl'  => home_url( '/waitlist/' ),
            'placeholder'  => mm_sphynx_placeholder_image(),
        ]
    );
}

function mm_sphynx_get_portal_url(): string {
    $default = home_url( '/kittens/' );
    return apply_filters( 'mm_sphynx_portal_url', $default );
}

function mm_sphynx_get_applicant_role(): string {
    return 'mm_applicant';
}

function mm_sphynx_register_roles(): void {
    $role = mm_sphynx_get_applicant_role();
    if ( ! get_role( $role ) ) {
        add_role(
            $role,
            __( 'Sphynx Applicant', 'mm-sphynx' ),
            [
                'read'         => true,
                'edit_posts'   => false,
                'delete_posts' => false,
            ]
        );
    }
}

function mm_sphynx_get_applicant_statuses(): array {
    return [
        'pending'      => [
            'label'   => __( 'Pending Review', 'mm-sphynx' ),
            'message' => __( 'Your dossier reached our guardianship team. Expect a concierge reply within 48 hours.', 'mm-sphynx' ),
        ],
        'applicant'    => [
            'label'   => __( 'Applicant', 'mm-sphynx' ),
            'message' => __( 'Your dossier reached our guardianship team. Expect a concierge reply within 48 hours.', 'mm-sphynx' ),
        ],
        'approved'     => [
            'label'   => __( 'Approved', 'mm-sphynx' ),
            'message' => __( 'Welcome to the inner circle. Please prepare your reservation deposit to secure priority access.', 'mm-sphynx' ),
        ],
        'rejected'     => [
            'label'   => __( 'Pause', 'mm-sphynx' ),
            'message' => __( 'We are unable to progress this journey right now. Reach out if circumstances evolve.', 'mm-sphynx' ),
        ],
        'paid_deposit' => [
            'label'   => __( 'Deposit Received', 'mm-sphynx' ),
            'message' => __( 'Your reservation is recorded. Watch the portal for selection invitations and studio updates.', 'mm-sphynx' ),
        ],
        'selected'     => [
            'label'   => __( 'Selection Submitted', 'mm-sphynx' ),
            'message' => __( 'Our concierge is reviewing your pairing request and will confirm alignment shortly.', 'mm-sphynx' ),
        ],
        'contract_sent'=> [
            'label'   => __( 'Contract Sent', 'mm-sphynx' ),
            'message' => __( 'Contract materials are en route. Please review, sign, and upload within three business days.', 'mm-sphynx' ),
        ],
        'paid_full'    => [
            'label'   => __( 'Balance Received', 'mm-sphynx' ),
            'message' => __( 'Your adoption balance is confirmed. Final preparations and health clearances are underway.', 'mm-sphynx' ),
        ],
        'ready'        => [
            'label'   => __( 'Kitten Preparing', 'mm-sphynx' ),
            'message' => __( 'Your Miss Mermaid companion is in final spa care. Expect travel coordination shortly.', 'mm-sphynx' ),
        ],
        'complete'     => [
            'label'   => __( 'Adoption Complete', 'mm-sphynx' ),
            'message' => __( 'Thank you for joining the Miss Mermaid family. Share updates via the alumni stories form any time.', 'mm-sphynx' ),
        ],
    ];
}

function mm_sphynx_get_application_status_label( string $status ): string {
    $statuses = mm_sphynx_get_applicant_statuses();
    return $statuses[ $status ]['label'] ?? ucfirst( str_replace( '_', ' ', $status ) );
}

function mm_sphynx_get_default_email_templates(): array {
    return [
        'approve'        => [
            'subject' => __( 'Your application is approved', 'mm-sphynx' ),
            'body'    => __( "Dear {name},\n\nGreat news — your application has been approved. Next step: {next_step_link}.\nWe recommend reviewing payment options and policies here: {policies_link}.\n\nWarmly,\nMiss Mermaid · Sphynx", 'mm-sphynx' ),
        ],
        'reject'         => [
            'subject' => __( 'Your application status', 'mm-sphynx' ),
            'body'    => __( "Dear {name},\n\nThank you for your interest. After careful review, we won’t proceed at this time. You’re welcome to stay on our public updates list.\n\nWarm regards,\nMiss Mermaid · Sphynx", 'mm-sphynx' ),
        ],
        'payment_instr'  => [
            'subject' => __( 'How to complete your payment', 'mm-sphynx' ),
            'body'    => __( "Hi {name},\n\nYou may pay via Zelle (preferred) or PayPal Friends & Family. Upload proof here: {upload_link}.\nDeposits are $1000; selection usually opens ~8 weeks.\n\n— Team Miss Mermaid", 'mm-sphynx' ),
        ],
    ];
}

function mm_sphynx_get_email_templates(): array {
    $templates = get_option( 'mm_sphynx_email_templates', [] );
    if ( ! is_array( $templates ) ) {
        $templates = [];
    }

    return array_merge( mm_sphynx_get_default_email_templates(), $templates );
}

function mm_sphynx_get_email_placeholders( ?\WP_User $user, array $context = [] ): array {
    $name = '';
    $kitten_id = $context['kitten_id'] ?? '';
    $litter_code = $context['litter_code'] ?? '';

    if ( $user instanceof \WP_User ) {
        if ( $user->display_name ) {
            $name = $user->display_name;
        } elseif ( $user->user_email ) {
            $name = current( explode( '@', $user->user_email ) );
        }

        $profile = get_user_meta( $user->ID, '_mm_application_profile', true );
        if ( is_array( $profile ) ) {
            if ( ! $name && ! empty( $profile['guardian_name'] ) ) {
                $name = $profile['guardian_name'];
            }
            if ( ! $kitten_id && ! empty( $profile['kitten_interest'] ) ) {
                $kitten_id = $profile['kitten_interest'];
            }
        }

        if ( ! $kitten_id ) {
            $kitten_id = get_user_meta( $user->ID, '_mm_selected_kitten', true );
        }

        if ( ! $litter_code ) {
            $litter_code = get_user_meta( $user->ID, '_mm_waitlist_litter', true );
        }
    }

    if ( ! $name ) {
        $name = $context['name'] ?? __( 'Guardian', 'mm-sphynx' );
    }

    $placeholders = [
        'name'            => $name,
        'kitten_id'       => $kitten_id ?: ( $context['kitten_id'] ?? '' ),
        'litter_code'     => $litter_code ?: ( $context['litter_code'] ?? '' ),
        'next_step_link'  => $context['next_step_link'] ?? home_url( '/payment/' ),
        'policies_link'   => $context['policies_link'] ?? home_url( '/policies#payment' ),
        'upload_link'     => $context['upload_link'] ?? mm_sphynx_get_portal_url(),
        'portal_link'     => mm_sphynx_get_portal_url(),
    ];

    return $placeholders;
}

function mm_sphynx_render_email_template( string $template_key, ?\WP_User $user, array $context = [] ): ?array {
    $templates = mm_sphynx_get_email_templates();
    if ( empty( $templates[ $template_key ] ) ) {
        return null;
    }

    $template = $templates[ $template_key ];
    $placeholders = mm_sphynx_get_email_placeholders( $user, $context );
    $search  = [];
    $replace = [];
    foreach ( $placeholders as $key => $value ) {
        $search[]  = '{' . $key . '}';
        $replace[] = $value;
    }

    $subject = str_replace( $search, $replace, $template['subject'] ?? '' );
    $body    = str_replace( $search, $replace, $template['body'] ?? '' );

    if ( ! $subject || ! $body ) {
        return null;
    }

    return [
        'subject' => $subject,
        'body'    => $body,
    ];
}

function mm_sphynx_send_templated_email( string $template_key, ?\WP_User $user, array $context = [], string $recipient = '' ): bool {
    $email = mm_sphynx_render_email_template( $template_key, $user, $context );
    if ( ! $email ) {
        return false;
    }

    $to = $recipient ?: ( $user instanceof \WP_User ? $user->user_email : '' );
    if ( ! $to ) {
        return false;
    }

    $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
    return (bool) wp_mail( $to, $email['subject'], $email['body'], $headers );
}

function mm_sphynx_get_status_history( int $user_id ): array {
    $history = get_user_meta( $user_id, '_mm_application_status_history', true );
    if ( ! is_array( $history ) ) {
        return [];
    }

    usort(
        $history,
        static function ( $a, $b ) {
            $a_time = $a['timestamp'] ?? 0;
            $b_time = $b['timestamp'] ?? 0;
            return $a_time <=> $b_time;
        }
    );

    return $history;
}

function mm_sphynx_record_status_history( int $user_id, string $status, string $note = '', string $actor = 'system', array $context = [] ): void {
    $history   = mm_sphynx_get_status_history( $user_id );
    $history[] = [
        'status'    => $status,
        'label'     => mm_sphynx_get_application_status_label( $status ),
        'note'      => $note,
        'actor'     => $actor,
        'timestamp' => time(),
        'context'   => $context,
    ];

    update_user_meta( $user_id, '_mm_application_status_history', $history );
}

function mm_sphynx_send_status_email( \WP_User $user, string $status, array $context = [] ): void {
    $template_map = [
        'approved' => 'approve',
        'rejected' => 'reject',
    ];

    if ( isset( $template_map[ $status ] ) ) {
        if ( mm_sphynx_send_templated_email( $template_map[ $status ], $user, $context ) ) {
            return;
        }
    }

    $statuses = mm_sphynx_get_applicant_statuses();
    $message  = $statuses[ $status ]['message'] ?? '';

    if ( ! $message ) {
        return;
    }

    $subject = sprintf(
        /* translators: %s status label */
        __( 'Miss Mermaid Sphynx — %s', 'mm-sphynx' ),
        mm_sphynx_get_application_status_label( $status )
    );

    $intro = sprintf(
        /* translators: %s guardian display name */
        __( 'Hello %s,', 'mm-sphynx' ),
        $user->display_name ?: $user->user_email
    );

    $body_lines = [
        $intro,
        '',
        $message,
        '',
        sprintf(
            /* translators: %s portal URL */
            __( 'Portal: %s', 'mm-sphynx' ),
            esc_url( mm_sphynx_get_portal_url() )
        ),
        '',
        __( '— Miss Mermaid Guardianship Team', 'mm-sphynx' ),
    ];

    $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
    wp_mail( $user->user_email, $subject, implode( "\n", $body_lines ), $headers );
}

function mm_sphynx_transition_status( int $user_id, string $status, string $note = '', string $actor = 'system', array $context = [] ): bool {
    if ( 'applicant' === $status ) {
        $status = 'pending';
    }
    $statuses = mm_sphynx_get_applicant_statuses();
    if ( ! isset( $statuses[ $status ] ) ) {
        return false;
    }

    $current = get_user_meta( $user_id, '_mm_application_status', true );
    if ( $current === $status && empty( $note ) ) {
        return true;
    }

    update_user_meta( $user_id, '_mm_application_status', $status );
    update_user_meta( $user_id, '_mm_application_status_updated', time() );
    mm_sphynx_record_status_history( $user_id, $status, $note, $actor, $context );

    $user = get_user_by( 'id', $user_id );
    if ( $user instanceof \WP_User ) {
        mm_sphynx_send_status_email( $user, $status, $context );
    }

    return true;
}

function mm_sphynx_update_applicant_profile( int $user_id, array $profile ): void {
    $existing = get_user_meta( $user_id, '_mm_application_profile', true );
    if ( ! is_array( $existing ) ) {
        $existing = [];
    }
    update_user_meta( $user_id, '_mm_application_profile', array_merge( $existing, $profile ) );
}

function mm_sphynx_throttle( string $key, int $limit = 3, int $window = DAY_IN_SECONDS, bool $increment = true ): bool {
    $transient_key = 'mm_throttle_' . md5( $key );
    $now           = time();
    $timestamps    = get_transient( $transient_key );

    if ( ! is_array( $timestamps ) ) {
        $timestamps = [];
    }

    $timestamps = array_filter(
        $timestamps,
        static function ( $timestamp ) use ( $now, $window ) {
            return ( $now - (int) $timestamp ) < $window;
        }
    );

    if ( count( $timestamps ) >= $limit ) {
        set_transient( $transient_key, $timestamps, $window );
        return true;
    }

    if ( $increment ) {
        $timestamps[] = $now;
        set_transient( $transient_key, $timestamps, $window );
    }

    return false;
}

function mm_sphynx_generate_magic_login_url( int $user_id, string $redirect = '' ): ?string {
    $user = get_user_by( 'id', $user_id );
    if ( ! ( $user instanceof \WP_User ) ) {
        return null;
    }

    $token = wp_generate_password( 32, false );
    $hash  = wp_hash_password( $token );

    $data = [
        'hash'     => $hash,
        'expires'  => time() + ( 15 * MINUTE_IN_SECONDS ),
        'redirect' => $redirect ? esc_url_raw( $redirect ) : '',
        'created'  => time(),
    ];

    update_user_meta( $user_id, '_mm_magic_token', $data );

    $url = add_query_arg(
        [
            'mm_magic_login' => 1,
            'uid'            => $user_id,
            'token'          => rawurlencode( $token ),
        ],
        mm_sphynx_get_portal_url()
    );

    return $url;
}

function mm_sphynx_clear_magic_token( int $user_id ): void {
    delete_user_meta( $user_id, '_mm_magic_token' );
}

function mm_sphynx_send_magic_link_email( \WP_User $user, string $url ): void {
    $subject = __( 'Your Miss Mermaid secure login link', 'mm-sphynx' );
    $body    = sprintf(
        /* translators: %1$s guardian name, %2$s magic login URL */
        __( "Hello %1\$s,\n\nHere is your secure magic link to access the Miss Mermaid adoption portal:\n%2\$s\n\nThe link expires in 15 minutes. If you did not request access, please ignore this email.\n\n— Miss Mermaid Guardianship Team", 'mm-sphynx' ),
        $user->display_name ?: $user->user_email,
        esc_url( $url )
    );

    $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
    wp_mail( $user->user_email, $subject, $body, $headers );
}

function mm_sphynx_handle_magic_request(): void {
    $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : '';

    if ( 'POST' !== $request_method ) {
        return;
    }

    if ( empty( $_POST['mm_magic_login_request'] ) ) {
        return;
    }

    check_admin_referer( 'mm_magic_login' );

    $email    = isset( $_POST['mm_magic_email'] ) ? sanitize_email( wp_unslash( $_POST['mm_magic_email'] ) ) : '';
    $redirect = isset( $_POST['mm_magic_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['mm_magic_redirect'] ) ) : '';
    $referer  = wp_get_referer() ?: mm_sphynx_get_portal_url();

    $destination = $redirect ?: $referer ?: home_url( '/' );

    if ( ! $email ) {
        $destination = add_query_arg( 'mm_login', 'missing', $destination );
        wp_safe_redirect( $destination );
        exit;
    }

    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
    if ( mm_sphynx_throttle( 'magic_ip_' . md5( $ip ), 10, DAY_IN_SECONDS ) || mm_sphynx_throttle( 'magic_email_' . md5( strtolower( $email ) ), 5, DAY_IN_SECONDS ) ) {
        $destination = add_query_arg( 'mm_login', 'rate', $destination );
        wp_safe_redirect( $destination );
        exit;
    }

    $user = get_user_by( 'email', $email );
    if ( ! ( $user instanceof \WP_User ) ) {
        $destination = add_query_arg( 'mm_login', 'unknown', $destination );
        wp_safe_redirect( $destination );
        exit;
    }

    $link = mm_sphynx_generate_magic_login_url( $user->ID, $redirect ?: mm_sphynx_get_portal_url() );
    if ( ! $link ) {
        $destination = add_query_arg( 'mm_login', 'error', $destination );
        wp_safe_redirect( $destination );
        exit;
    }

    mm_sphynx_send_magic_link_email( $user, $link );

    $destination = add_query_arg( 'mm_login', 'sent', $destination );
    wp_safe_redirect( $destination );
    exit;
}

function mm_sphynx_handle_magic_login(): void {
    if ( empty( $_GET['mm_magic_login'] ) ) {
        return;
    }

    $user_id = isset( $_GET['uid'] ) ? absint( $_GET['uid'] ) : 0;
    $token   = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

    if ( ! $user_id || ! $token ) {
        wp_safe_redirect( add_query_arg( 'mm_login', 'invalid', mm_sphynx_get_portal_url() ) );
        exit;
    }

    $data = get_user_meta( $user_id, '_mm_magic_token', true );
    if ( ! is_array( $data ) || empty( $data['hash'] ) || empty( $data['expires'] ) ) {
        wp_safe_redirect( add_query_arg( 'mm_login', 'expired', mm_sphynx_get_portal_url() ) );
        exit;
    }

    if ( time() > (int) $data['expires'] ) {
        mm_sphynx_clear_magic_token( $user_id );
        wp_safe_redirect( add_query_arg( 'mm_login', 'expired', mm_sphynx_get_portal_url() ) );
        exit;
    }

    if ( ! wp_check_password( $token, $data['hash'], $user_id ) ) {
        mm_sphynx_clear_magic_token( $user_id );
        wp_safe_redirect( add_query_arg( 'mm_login', 'invalid', mm_sphynx_get_portal_url() ) );
        exit;
    }

    $user = get_user_by( 'id', $user_id );
    if ( ! ( $user instanceof \WP_User ) ) {
        mm_sphynx_clear_magic_token( $user_id );
        wp_safe_redirect( add_query_arg( 'mm_login', 'invalid', mm_sphynx_get_portal_url() ) );
        exit;
    }

    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, true );
    mm_sphynx_clear_magic_token( $user_id );
    update_user_meta( $user_id, '_mm_last_login', time() );

    $redirect = ! empty( $data['redirect'] ) ? $data['redirect'] : mm_sphynx_get_portal_url();
    $redirect = add_query_arg( 'mm_login', 'success', $redirect );

    wp_safe_redirect( $redirect );
    exit;
}

function mm_sphynx_magic_login_shortcode( $atts = [] ): string {
    if ( is_user_logged_in() ) {
        return '<div class="mm-magic-login mm-magic-login--signed">' . esc_html__( 'You are signed in to the Miss Mermaid portal.', 'mm-sphynx' ) . '</div>';
    }

    $atts = shortcode_atts(
        [
            'redirect' => mm_sphynx_get_portal_url(),
        ],
        $atts,
        'mm_magic_login'
    );

    $status = isset( $_GET['mm_login'] ) ? sanitize_key( wp_unslash( $_GET['mm_login'] ) ) : '';
    $messages = [
        'sent'    => __( 'A magic link is on its way to your inbox. Check your email within the next 15 minutes.', 'mm-sphynx' ),
        'success' => __( 'Access granted. Welcome back.', 'mm-sphynx' ),
        'rate'    => __( 'Too many requests today. Please try again tomorrow or contact the guardianship team.', 'mm-sphynx' ),
        'missing' => __( 'Please provide a valid email address to receive your login link.', 'mm-sphynx' ),
        'unknown' => __( 'We could not locate that email. Submit an application or contact concierge for support.', 'mm-sphynx' ),
        'expired' => __( 'That link expired. Request a fresh magic link below.', 'mm-sphynx' ),
        'invalid' => __( 'The login token was invalid. Request a new link to continue.', 'mm-sphynx' ),
        'error'   => __( 'We were unable to generate a login link. Please retry shortly.', 'mm-sphynx' ),
    ];

    ob_start();
    ?>
    <div class="mm-magic-login">
        <?php if ( $status && isset( $messages[ $status ] ) ) : ?>
            <div class="mm-magic-login__notice mm-magic-login__notice--<?php echo esc_attr( $status ); ?>">
                <?php echo esc_html( $messages[ $status ] ); ?>
            </div>
        <?php endif; ?>
        <form method="post" class="mm-magic-login__form">
            <?php wp_nonce_field( 'mm_magic_login' ); ?>
            <input type="hidden" name="mm_magic_login_request" value="1" />
            <input type="hidden" name="mm_magic_redirect" value="<?php echo esc_attr( $atts['redirect'] ); ?>" />
            <label for="mm-magic-email"><?php esc_html_e( 'Email address', 'mm-sphynx' ); ?></label>
            <input type="email" id="mm-magic-email" name="mm_magic_email" required placeholder="you@example.com" />
            <button type="submit" class="mm-button mm-button--primary"><?php esc_html_e( 'Send me a magic link', 'mm-sphynx' ); ?></button>
        </form>
    </div>
    <?php
    return (string) ob_get_clean();
}

function mm_sphynx_form_shortcode( $atts = [] ): string {
    $atts = shortcode_atts(
        [
            'slug'     => '',
            'fallback' => '',
        ],
        $atts,
        'mm_form'
    );

    $slug = sanitize_key( (string) $atts['slug'] );
    if ( ! $slug && ! empty( $atts['fallback'] ) ) {
        $slug = sanitize_key( (string) $atts['fallback'] );
    }

    if ( ! $slug ) {
        return '';
    }

    $form_id = mm_sphynx_get_form_id( $slug );
    if ( ! $form_id ) {
        return '';
    }

    return do_shortcode( '[fluentform id="' . (int) $form_id . '"]' );
}

function mm_sphynx_get_form_slug_by_id( int $form_id ): ?string {
    $registry = mm_sphynx_get_forms_registry();
    foreach ( $registry as $slug => $id ) {
        if ( (int) $id === $form_id ) {
            return $slug;
        }
    }
    return null;
}

function mm_sphynx_locate_applicant_user( array $form_data, $entry ): ?\WP_User {
    if ( is_object( $entry ) && ! empty( $entry->user_id ) ) {
        $user = get_user_by( 'id', (int) $entry->user_id );
        if ( $user instanceof \WP_User ) {
            return $user;
        }
    }

    if ( ! empty( $form_data['email'] ) ) {
        $user = get_user_by( 'email', sanitize_email( $form_data['email'] ) );
        if ( $user instanceof \WP_User ) {
            return $user;
        }
    }

    if ( is_user_logged_in() ) {
        return wp_get_current_user();
    }

    return null;
}

function mm_sphynx_fetch_or_create_applicant( string $email, array $form_data = [] ): ?\WP_User {
    if ( ! $email ) {
        return null;
    }

    $user = get_user_by( 'email', $email );
    if ( $user instanceof \WP_User ) {
        mm_sphynx_ensure_applicant_role( $user );
        return $user;
    }

    $raw_base = sanitize_user( current( explode( '@', $email ) ), true );
    if ( ! $raw_base ) {
        $raw_base = 'guardian';
    }
    $base     = $raw_base;
    $attempts = 1;

    if ( ! function_exists( 'username_exists' ) ) {
        require_once ABSPATH . WPINC . '/user.php';
    }

    while ( username_exists( $base ) ) {
        $base = $raw_base . '_' . $attempts;
        $attempts++;
    }

    $display = sanitize_text_field( $form_data['guardian_name'] ?? '' );
    $password = wp_generate_password( 24 );

    $user_id = wp_insert_user(
        [
            'user_login'   => $base,
            'user_email'   => $email,
            'user_pass'    => $password,
            'role'         => mm_sphynx_get_applicant_role(),
            'display_name' => $display ?: $email,
        ]
    );

    if ( is_wp_error( $user_id ) ) {
        return null;
    }

    $user = get_user_by( 'id', (int) $user_id );
    if ( $user instanceof \WP_User ) {
        mm_sphynx_ensure_applicant_role( $user );
        return $user;
    }

    return null;
}

function mm_sphynx_maybe_update_display_name( \WP_User $user, string $name ): void {
    $name = trim( $name );
    if ( '' === $name ) {
        return;
    }

    if ( $user->display_name === $name ) {
        return;
    }

    if ( '' === trim( (string) $user->display_name ) || $user->display_name === $user->user_email ) {
        wp_update_user(
            [
                'ID'           => $user->ID,
                'display_name' => $name,
            ]
        );
    }
}

function mm_sphynx_ensure_applicant_role( \WP_User $user ): void {
    $role = mm_sphynx_get_applicant_role();
    if ( in_array( 'administrator', $user->roles, true ) || in_array( 'editor', $user->roles, true ) || in_array( 'author', $user->roles, true ) ) {
        return;
    }

    if ( ! in_array( $role, $user->roles, true ) ) {
        $user->add_role( $role );
    }
}

function mm_sphynx_on_form_submission( $entry_id, $form_data, $entry, $form ): void {
    $form_id = 0;
    if ( is_array( $form ) && isset( $form['id'] ) ) {
        $form_id = (int) $form['id'];
    } elseif ( is_object( $form ) && isset( $form->id ) ) {
        $form_id = (int) $form->id;
    }

    if ( ! $form_id ) {
        return;
    }

    $slug = mm_sphynx_get_form_slug_by_id( $form_id );
    if ( ! $slug ) {
        return;
    }

    switch ( $slug ) {
        case 'adoption_apply':
            mm_sphynx_handle_adoption_application( $form_data, $entry_id, $entry );
            break;
        case 'waitlist':
            mm_sphynx_handle_waitlist_submission( $form_data, $entry_id, $entry );
            break;
        case 'payment_proof':
            mm_sphynx_handle_payment_proof( $form_data, $entry_id, $entry );
            break;
        case 'select_kitten':
            mm_sphynx_handle_selection_form( $form_data, $entry_id, $entry );
            break;
    }
}

function mm_sphynx_handle_adoption_application( array $form_data, $entry_id, $entry ): void {
    $email = isset( $form_data['email'] ) ? sanitize_email( $form_data['email'] ) : '';
    if ( ! $email ) {
        return;
    }

    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
    if ( mm_sphynx_throttle( 'apply_email_' . md5( strtolower( $email ) ), 3, DAY_IN_SECONDS ) || mm_sphynx_throttle( 'apply_ip_' . md5( $ip ), 6, DAY_IN_SECONDS ) ) {
        return;
    }

    $user = mm_sphynx_locate_applicant_user( $form_data, $entry );
    if ( ! ( $user instanceof \WP_User ) ) {
        $user = mm_sphynx_fetch_or_create_applicant( $email, $form_data );
    }

    if ( ! ( $user instanceof \WP_User ) ) {
        return;
    }

    mm_sphynx_ensure_applicant_role( $user );
    mm_sphynx_maybe_update_display_name( $user, sanitize_text_field( $form_data['guardian_name'] ?? '' ) );

    $profile = [
        'guardian_name'       => sanitize_text_field( $form_data['guardian_name'] ?? '' ),
        'phone'               => sanitize_text_field( $form_data['phone'] ?? '' ),
        'city_state'          => sanitize_text_field( $form_data['city_state'] ?? '' ),
        'household_overview'  => sanitize_textarea_field( $form_data['household_overview'] ?? '' ),
        'current_pets'        => sanitize_textarea_field( $form_data['current_pets'] ?? '' ),
        'experience'          => sanitize_textarea_field( $form_data['experience'] ?? '' ),
        'home_setting'        => sanitize_text_field( $form_data['home_setting'] ?? '' ),
        'lifestyle_tags'      => isset( $form_data['lifestyle_tags'] ) ? array_map( 'sanitize_text_field', (array) $form_data['lifestyle_tags'] ) : [],
        'preferred_schedule'  => sanitize_text_field( $form_data['preferred_schedule'] ?? '' ),
        'kitten_interest'     => sanitize_text_field( $form_data['kitten_interest'] ?? '' ),
        'application_entry'   => (int) $entry_id,
    ];
    mm_sphynx_update_applicant_profile( $user->ID, $profile );

    update_user_meta( $user->ID, '_mm_last_application_entry', (int) $entry_id );
    mm_sphynx_transition_status( $user->ID, 'applicant', __( 'Application submitted via Fluent Forms.', 'mm-sphynx' ), 'form' );

    $link = mm_sphynx_generate_magic_login_url( $user->ID, mm_sphynx_get_portal_url() );
    if ( $link ) {
        mm_sphynx_send_magic_link_email( $user, $link );
    }
}

function mm_sphynx_handle_waitlist_submission( array $form_data, $entry_id, $entry ): void {
    $email = isset( $form_data['email'] ) ? sanitize_email( $form_data['email'] ) : '';
    if ( ! $email ) {
        return;
    }

    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
    if ( mm_sphynx_throttle( 'waitlist_email_' . md5( strtolower( $email ) ), 3, DAY_IN_SECONDS ) || mm_sphynx_throttle( 'waitlist_ip_' . md5( $ip ), 6, DAY_IN_SECONDS ) ) {
        return;
    }

    $user = mm_sphynx_locate_applicant_user( $form_data, $entry );
    if ( ! ( $user instanceof \WP_User ) ) {
        $user = mm_sphynx_fetch_or_create_applicant( $email, $form_data );
    }

    if ( ! ( $user instanceof \WP_User ) ) {
        return;
    }

    mm_sphynx_ensure_applicant_role( $user );

    $name  = sanitize_text_field( $form_data['guardian_name'] ?? '' );
    $phone = sanitize_text_field( $form_data['phone'] ?? '' );
    $litter = sanitize_text_field( $form_data['litter_code'] ?? '' );
    $notes  = sanitize_textarea_field( $form_data['notes'] ?? '' );

    mm_sphynx_maybe_update_display_name( $user, $name );

    $profile = array_filter(
        [
            'guardian_name'   => $name,
            'phone'           => $phone,
            'litter_interest' => $litter,
            'waitlist_notes'  => $notes,
        ],
        static function ( $value ) {
            return '' !== $value && null !== $value;
        }
    );

    if ( $profile ) {
        mm_sphynx_update_applicant_profile( $user->ID, $profile );
    }

    if ( $litter ) {
        update_user_meta( $user->ID, '_mm_waitlist_litter', $litter );
    }

    update_user_meta( $user->ID, '_mm_waitlist_entry', (int) $entry_id );

    $context = [
        'litter_code' => $litter,
    ];

    $note = $litter
        ? sprintf(
            /* translators: %s: litter code */
            __( 'Waitlist request for litter %s.', 'mm-sphynx' ),
            $litter
        )
        : __( 'Waitlist request submitted.', 'mm-sphynx' );

    mm_sphynx_transition_status( $user->ID, 'applicant', $note, 'form', $context );

    if ( ! is_user_logged_in() ) {
        $link = mm_sphynx_generate_magic_login_url( $user->ID, mm_sphynx_get_portal_url() );
        if ( $link ) {
            mm_sphynx_send_magic_link_email( $user, $link );
        }
    }
}

function mm_sphynx_handle_payment_proof( array $form_data, $entry_id, $entry ): void {
    $user = mm_sphynx_locate_applicant_user( $form_data, $entry );
    if ( ! ( $user instanceof \WP_User ) ) {
        return;
    }

    mm_sphynx_ensure_applicant_role( $user );

    $stage = isset( $form_data['payment_stage'] ) ? sanitize_text_field( $form_data['payment_stage'] ) : '';
    $amount = isset( $form_data['amount'] ) ? floatval( $form_data['amount'] ) : 0.0;
    $note = sprintf(
        /* translators: %1$s payment stage, %2$s amount */
        __( 'Payment proof submitted (%1$s — $%2$s).', 'mm-sphynx' ),
        $stage,
        number_format_i18n( $amount, 2 )
    );

    $status = 'deposit' === $stage ? 'paid_deposit' : 'paid_full';
    mm_sphynx_transition_status( $user->ID, $status, $note, 'form' );

    $history = get_user_meta( $user->ID, '_mm_payment_history', true );
    if ( ! is_array( $history ) ) {
        $history = [];
    }
    $history[] = [
        'stage'    => $stage,
        'amount'   => $amount,
        'date'     => sanitize_text_field( $form_data['payment_date'] ?? '' ),
        'entry_id' => (int) $entry_id,
        'time'     => time(),
    ];
    update_user_meta( $user->ID, '_mm_payment_history', $history );
}

function mm_sphynx_handle_selection_form( array $form_data, $entry_id, $entry ): void {
    $user = mm_sphynx_locate_applicant_user( $form_data, $entry );
    if ( ! ( $user instanceof \WP_User ) ) {
        return;
    }

    mm_sphynx_ensure_applicant_role( $user );

    $kitten_id = isset( $form_data['kitten_id'] ) ? sanitize_text_field( $form_data['kitten_id'] ) : '';
    update_user_meta( $user->ID, '_mm_selected_kitten', $kitten_id );
    update_user_meta( $user->ID, '_mm_selection_entry', (int) $entry_id );

    $note = sprintf(
        /* translators: %s kitten ID */
        __( 'Guardian requested kitten %s.', 'mm-sphynx' ),
        $kitten_id ?: 'N/A'
    );
    mm_sphynx_transition_status( $user->ID, 'selected', $note, 'form' );
}

function mm_sphynx_users_columns( array $columns ): array {
    $columns['mm_status']  = __( 'Adoption Status', 'mm-sphynx' );
    $columns['mm_updated'] = __( 'Status Updated', 'mm-sphynx' );
    $columns['mm_interest'] = __( 'Interest', 'mm-sphynx' );
    return $columns;
}

function mm_sphynx_users_custom_column( $output, string $column_name, int $user_id ) {
    switch ( $column_name ) {
        case 'mm_status':
            $status = get_user_meta( $user_id, '_mm_application_status', true );
            if ( ! $status ) {
                return $output;
            }
            return esc_html( mm_sphynx_get_application_status_label( $status ) );
        case 'mm_updated':
            $timestamp = (int) get_user_meta( $user_id, '_mm_application_status_updated', true );
            if ( ! $timestamp ) {
                return $output;
            }
            return esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) );
        case 'mm_interest':
            $profile = get_user_meta( $user_id, '_mm_application_profile', true );
            $parts   = [];

            if ( is_array( $profile ) ) {
                if ( ! empty( $profile['kitten_interest'] ) ) {
                    $parts[] = sprintf( __( 'Kitten: %s', 'mm-sphynx' ), $profile['kitten_interest'] );
                }
                if ( ! empty( $profile['litter_interest'] ) ) {
                    $parts[] = sprintf( __( 'Litter: %s', 'mm-sphynx' ), $profile['litter_interest'] );
                }
            }

            $selected = get_user_meta( $user_id, '_mm_selected_kitten', true );
            if ( $selected ) {
                $parts[] = sprintf( __( 'Selected: %s', 'mm-sphynx' ), $selected );
            }

            $waitlist = get_user_meta( $user_id, '_mm_waitlist_litter', true );
            if ( $waitlist ) {
                $parts[] = sprintf( __( 'Waitlist: %s', 'mm-sphynx' ), $waitlist );
            }

            if ( empty( $parts ) ) {
                return '&mdash;';
            }

            return esc_html( implode( ' | ', array_unique( $parts ) ) );
    }
    return $output;
}

function mm_sphynx_applicant_row_actions( array $actions, \WP_User $user ): array {
    if ( ! current_user_can( 'list_users' ) ) {
        return $actions;
    }

    if ( ! in_array( mm_sphynx_get_applicant_role(), $user->roles, true ) ) {
        return $actions;
    }

    $status  = get_user_meta( $user->ID, '_mm_application_status', true ) ?: 'pending';
    $mapping = [
        'pending'   => [ 'approve', 'reject' ],
        'applicant' => [ 'approve', 'reject' ],
        'approved'  => [ 'confirm-deposit', 'reject' ],
        'paid_deposit' => [ 'send-contract', 'confirm-full', 'mark-ready' ],
        'contract_sent'=> [ 'confirm-full', 'mark-ready' ],
        'paid_full'    => [ 'mark-ready', 'complete' ],
        'ready'        => [ 'complete' ],
        'selected'     => [ 'send-contract', 'confirm-full' ],
        'rejected'     => [ 'approve' ],
        'complete'     => [],
    ];

    $labels = [
        'approve'        => __( 'Approve', 'mm-sphynx' ),
        'reject'         => __( 'Reject', 'mm-sphynx' ),
        'confirm-deposit'=> __( 'Confirm Deposit', 'mm-sphynx' ),
        'confirm-full'   => __( 'Confirm Full Balance', 'mm-sphynx' ),
        'send-contract'  => __( 'Send Contract', 'mm-sphynx' ),
        'mark-ready'     => __( 'Mark Ready', 'mm-sphynx' ),
        'complete'       => __( 'Complete Adoption', 'mm-sphynx' ),
    ];

    $allowed = $mapping[ $status ] ?? [];
    foreach ( $allowed as $action ) {
        $label = $labels[ $action ] ?? $action;
        $url   = wp_nonce_url(
            add_query_arg(
                [
                    'mm_applicant_action' => $action,
                    'user'                => $user->ID,
                ],
                admin_url( 'users.php' )
            ),
            'mm_applicant_action_' . $action . '_' . $user->ID,
            '_mm_nonce'
        );
        $actions[ 'mm_' . $action ] = '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
    }

    return $actions;
}

function mm_sphynx_handle_admin_applicant_action(): void {
    if ( empty( $_GET['mm_applicant_action'] ) || empty( $_GET['user'] ) ) {
        return;
    }

    if ( ! current_user_can( 'list_users' ) ) {
        return;
    }

    $action  = sanitize_key( wp_unslash( $_GET['mm_applicant_action'] ) );
    $user_id = absint( $_GET['user'] );
    $nonce   = isset( $_GET['_mm_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_mm_nonce'] ) ) : '';

    if ( ! $action || ! $user_id || ! $nonce ) {
        return;
    }

    if ( ! wp_verify_nonce( $nonce, 'mm_applicant_action_' . $action . '_' . $user_id ) ) {
        return;
    }

    $map = [
        'approve'        => 'approved',
        'reject'         => 'rejected',
        'confirm-deposit'=> 'paid_deposit',
        'confirm-full'   => 'paid_full',
        'send-contract'  => 'contract_sent',
        'mark-ready'     => 'ready',
        'complete'       => 'complete',
    ];

    $status = $map[ $action ] ?? null;
    if ( ! $status ) {
        return;
    }

    $context = [];
    if ( 'approved' === $status ) {
        $context['next_step_link'] = home_url( '/payment/' );
        $context['policies_link']  = home_url( '/policies#payment' );
    }

    $current_admin = wp_get_current_user();

    $result = mm_sphynx_transition_status(
        $user_id,
        $status,
        sprintf(
            /* translators: %s admin username */
            __( 'Manual status change by %s.', 'mm-sphynx' ),
            $current_admin->display_name
        ),
        'admin',
        $context
    );

    $message = $result
        ? sprintf( __( 'Status updated to %s.', 'mm-sphynx' ), mm_sphynx_get_application_status_label( $status ) )
        : __( 'Unable to update status.', 'mm-sphynx' );

    set_transient(
        'mm_admin_notice_' . get_current_user_id(),
        [
            'type'    => $result ? 'success' : 'error',
            'message' => $message,
        ],
        MINUTE_IN_SECONDS
    );

    $redirect = remove_query_arg(
        [ 'mm_applicant_action', 'user', '_mm_nonce' ],
        wp_get_referer() ?: admin_url( 'users.php' )
    );
    wp_safe_redirect( $redirect );
    exit;
}

function mm_sphynx_admin_notices(): void {
    $user_id = get_current_user_id();
    $keys    = [
        'mm_admin_notice_' . $user_id,
        'mm_bulk_notice_' . $user_id,
    ];

    foreach ( $keys as $key ) {
        $notice = get_transient( $key );
        if ( ! $notice ) {
            continue;
        }

        delete_transient( $key );

        $type    = 'success' === ( $notice['type'] ?? '' ) ? 'notice-success' : 'notice-error';
        $message = $notice['message'] ?? '';

        if ( ! $message ) {
            continue;
        }
        ?>
        <div class="notice <?php echo esc_attr( $type ); ?> is-dismissible">
            <p><?php echo esc_html( $message ); ?></p>
        </div>
        <?php
    }
}

add_filter( 'bulk_actions-users', 'mm_sphynx_register_user_bulk_actions' );
add_filter( 'handle_bulk_actions-users', 'mm_sphynx_handle_bulk_user_actions', 10, 3 );

function mm_sphynx_register_user_bulk_actions( $actions ) {
    $actions['mm_bulk_approve'] = __( 'Approve Applications', 'mm-sphynx' );
    $actions['mm_bulk_reject']  = __( 'Reject Applications', 'mm-sphynx' );
    return $actions;
}

function mm_sphynx_handle_bulk_user_actions( $redirect_to, string $doaction, array $user_ids ) {
    if ( ! in_array( $doaction, [ 'mm_bulk_approve', 'mm_bulk_reject' ], true ) ) {
        return $redirect_to;
    }

    if ( empty( $user_ids ) || ! current_user_can( 'list_users' ) ) {
        return $redirect_to;
    }

    $status  = 'mm_bulk_approve' === $doaction ? 'approved' : 'rejected';
    $context = [];
    if ( 'approved' === $status ) {
        $context['next_step_link'] = home_url( '/payment/' );
        $context['policies_link']  = home_url( '/policies#payment' );
    }

    $actor = wp_get_current_user()->display_name;
    $count = 0;

    foreach ( $user_ids as $user_id ) {
        $user_id = absint( $user_id );
        if ( ! $user_id ) {
            continue;
        }
        $result = mm_sphynx_transition_status(
            $user_id,
            $status,
            sprintf(
                /* translators: %s admin username */
                __( 'Bulk status change by %s.', 'mm-sphynx' ),
                $actor
            ),
            'admin',
            $context
        );

        if ( $result ) {
            $count++;
        }
    }

    $message = $count
        ? sprintf( __( 'Updated %d application(s).', 'mm-sphynx' ), $count )
        : __( 'No applications were updated.', 'mm-sphynx' );

    set_transient(
        'mm_bulk_notice_' . get_current_user_id(),
        [
            'type'    => $count ? 'success' : 'error',
            'message' => $message,
        ],
        MINUTE_IN_SECONDS
    );

    return $redirect_to;
}

function mm_sphynx_application_portal_shortcode( $atts = [] ): string {
    $atts = shortcode_atts(
        [
            'show_login' => 'true',
        ],
        $atts,
        'mm_application_portal'
    );

    if ( ! is_user_logged_in() ) {
        if ( 'true' !== strtolower( (string) $atts['show_login'] ) ) {
            return '';
        }
        $login = do_shortcode( '[mm_magic_login]' );
        return '<div class="mm-portal mm-portal--locked">' . $login . '</div>';
    }

    $user     = wp_get_current_user();
    $user_id  = $user->ID;
    $status   = get_user_meta( $user_id, '_mm_application_status', true ) ?: 'applicant';
    $profile  = get_user_meta( $user_id, '_mm_application_profile', true );
    $history  = mm_sphynx_get_status_history( $user_id );
    $statuses = mm_sphynx_get_applicant_statuses();

    $status_message = $statuses[ $status ]['message'] ?? '';

    $forms      = mm_sphynx_get_forms_registry();
    $apply_id   = $forms['adoption_apply'] ?? null;
    $payment_id = $forms['payment_proof'] ?? null;
    $select_id  = $forms['select_kitten'] ?? null;

    $can_payment = in_array( $status, [ 'approved', 'paid_deposit', 'contract_sent', 'paid_full', 'ready' ], true );
    $can_select  = in_array( $status, [ 'paid_deposit', 'contract_sent', 'selected' ], true );

    ob_start();
    ?>
    <div class="mm-portal">
        <header class="mm-portal__header">
            <h2><?php echo esc_html( sprintf( __( 'Welcome, %s', 'mm-sphynx' ), $user->display_name ?: $user->user_email ) ); ?></h2>
            <p class="mm-portal__status">
                <strong><?php esc_html_e( 'Current Status:', 'mm-sphynx' ); ?></strong>
                <?php echo esc_html( mm_sphynx_get_application_status_label( $status ) ); ?>
            </p>
            <?php if ( $status_message ) : ?>
                <p class="mm-portal__message"><?php echo esc_html( $status_message ); ?></p>
            <?php endif; ?>
        </header>

        <?php if ( is_array( $profile ) && ! empty( array_filter( $profile ) ) ) : ?>
            <section class="mm-portal__profile">
                <h3><?php esc_html_e( 'Application Snapshot', 'mm-sphynx' ); ?></h3>
                <dl>
                    <?php if ( ! empty( $profile['guardian_name'] ) ) : ?>
                        <div>
                            <dt><?php esc_html_e( 'Guardian', 'mm-sphynx' ); ?></dt>
                            <dd><?php echo esc_html( $profile['guardian_name'] ); ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ( ! empty( $profile['city_state'] ) ) : ?>
                        <div>
                            <dt><?php esc_html_e( 'Location', 'mm-sphynx' ); ?></dt>
                            <dd><?php echo esc_html( $profile['city_state'] ); ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ( ! empty( $profile['home_setting'] ) ) : ?>
                        <div>
                            <dt><?php esc_html_e( 'Home Setting', 'mm-sphynx' ); ?></dt>
                            <dd><?php echo esc_html( ucfirst( (string) $profile['home_setting'] ) ); ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ( ! empty( $profile['kitten_interest'] ) ) : ?>
                        <div>
                            <dt><?php esc_html_e( 'Kitten Interest', 'mm-sphynx' ); ?></dt>
                            <dd><?php echo esc_html( $profile['kitten_interest'] ); ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </section>
        <?php endif; ?>

        <?php if ( ! empty( $history ) ) : ?>
            <section class="mm-portal__timeline">
                <h3><?php esc_html_e( 'Status Timeline', 'mm-sphynx' ); ?></h3>
                <ol>
                    <?php foreach ( $history as $item ) : ?>
                        <li>
                            <strong><?php echo esc_html( $item['label'] ?? '' ); ?></strong>
                            <span aria-hidden="true">·</span>
                            <time datetime="<?php echo esc_attr( gmdate( 'c', $item['timestamp'] ?? time() ) ); ?>">
                                <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item['timestamp'] ?? time() ) ); ?>
                            </time>
                            <?php if ( ! empty( $item['note'] ) ) : ?>
                                <p><?php echo esc_html( $item['note'] ); ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>
        <?php endif; ?>

        <section class="mm-portal__actions">
            <h3><?php esc_html_e( 'Next Steps', 'mm-sphynx' ); ?></h3>
            <ul>
                <li><?php esc_html_e( 'Watch for concierge updates delivered by email and in this portal.', 'mm-sphynx' ); ?></li>
                <li><?php esc_html_e( 'Prepare questions for our wellness and artistry interview.', 'mm-sphynx' ); ?></li>
                <?php if ( $can_payment ) : ?>
                    <li><?php esc_html_e( 'Ready to share a payment confirmation? Use the form below to upload proof.', 'mm-sphynx' ); ?></li>
                <?php endif; ?>
                <?php if ( $can_select ) : ?>
                    <li><?php esc_html_e( 'When selection opens, submit your preferred kitten ID to reserve your match.', 'mm-sphynx' ); ?></li>
                <?php endif; ?>
            </ul>
        </section>

        <?php if ( $can_payment && $payment_id ) : ?>
            <section class="mm-portal__form mm-portal__form--payment" id="form-<?php echo esc_attr( $payment_id ); ?>">
                <h3><?php esc_html_e( 'Payment Proof Upload', 'mm-sphynx' ); ?></h3>
                <?php echo do_shortcode( '[fluentform id="' . (int) $payment_id . '"]' ); ?>
            </section>
        <?php endif; ?>

        <?php if ( $can_select && $select_id ) : ?>
            <section class="mm-portal__form mm-portal__form--selection" id="form-<?php echo esc_attr( $select_id ); ?>">
                <h3><?php esc_html_e( 'Kitten Selection', 'mm-sphynx' ); ?></h3>
                <?php echo do_shortcode( '[fluentform id="' . (int) $select_id . '"]' ); ?>
            </section>
        <?php endif; ?>

        <?php if ( $status === 'applicant' && $apply_id ) : ?>
            <section class="mm-portal__form mm-portal__form--application" id="form-<?php echo esc_attr( $apply_id ); ?>">
                <h3><?php esc_html_e( 'Resubmit Application Details', 'mm-sphynx' ); ?></h3>
                <p><?php esc_html_e( 'Need to refresh your dossier? Submit again and our team will merge the newest details.', 'mm-sphynx' ); ?></p>
                <?php echo do_shortcode( '[fluentform id="' . (int) $apply_id . '"]' ); ?>
            </section>
        <?php endif; ?>
    </div>
    <?php
    return (string) ob_get_clean();
}

function mm_sphynx_is_https_request(): bool {
    return (
        ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] ) ||
        ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ||
        ( isset( $_SERVER['HTTP_CF_VISITOR'] ) && false !== stripos( (string) $_SERVER['HTTP_CF_VISITOR'], 'https' ) )
    );
}

function mm_sphynx_adjust_host_url( string $url ): string {
    if ( empty( $_SERVER['HTTP_HOST'] ) ) {
        return $url;
    }

    if ( ! preg_match( '#^https?://#i', $url ) ) {
        $scheme = mm_sphynx_is_https_request() ? 'https' : 'http';
        $path   = 0 === strpos( $url, '/' ) ? $url : '/' . ltrim( $url, '/' );
        return $scheme . '://' . $_SERVER['HTTP_HOST'] . $path;
    }

    $parts = wp_parse_url( $url );
    if ( empty( $parts['host'] ) ) {
        return $url;
    }

    $legacy_hosts = apply_filters(
        'mm_sphynx_legacy_hosts',
        [
            'sphynx.localtest.me:8081',
            'sphynx.local.mermaid:8081',
            'localhost:8081',
        ]
    );

    $host_with_port = $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' );
    if ( ! in_array( $host_with_port, $legacy_hosts, true ) ) {
        return $url;
    }

    $scheme       = mm_sphynx_is_https_request() ? 'https' : 'http';
    $current_host = $_SERVER['HTTP_HOST'];

    $new_url = $scheme . '://' . $current_host;
    if ( isset( $parts['path'] ) ) {
        $new_url .= $parts['path'];
    }
    if ( ! empty( $parts['query'] ) ) {
        $new_url .= '?' . $parts['query'];
    }
    if ( ! empty( $parts['fragment'] ) ) {
        $new_url .= '#' . $parts['fragment'];
    }

    return $new_url;
}

add_filter( 'wp_nav_menu_objects', 'mm_sphynx_host_aware_menu_urls', 20 );

function mm_sphynx_host_aware_menu_urls( $items ) {
    if ( ! is_array( $items ) ) {
        return $items;
    }

    foreach ( $items as $item ) {
        if ( isset( $item->url ) ) {
            $item->url = mm_sphynx_adjust_host_url( (string) $item->url );
        }
    }

    return $items;
}

add_filter( 'render_block', 'mm_sphynx_host_aware_navigation_block', 20, 2 );

function mm_sphynx_host_aware_navigation_block( $block_content, $block ) {
    if ( empty( $_SERVER['HTTP_HOST'] ) || empty( $block_content ) || ! is_array( $block ) ) {
        return $block_content;
    }

    $target_blocks = [
        'core/navigation-link',
        'core/navigation-submenu',
    ];

    if ( empty( $block['blockName'] ) || ! in_array( $block['blockName'], $target_blocks, true ) ) {
        return $block_content;
    }

    if ( false === stripos( $block_content, 'href=' ) ) {
        return $block_content;
    }

    return preg_replace_callback(
        '#href="([^"]+)"#i',
        static function ( $matches ) {
            $original = $matches[1];
            $updated  = mm_sphynx_adjust_host_url( $original );

            if ( $updated === $original ) {
                return $matches[0];
            }

            return 'href="' . esc_url( $updated ) . '"';
        },
        $block_content
    );
}

add_filter( 'rank_math/frontend/canonical', 'mm_sphynx_adjust_canonical_url' );
add_filter( 'get_canonical_url', 'mm_sphynx_adjust_canonical_url', 10, 2 );

function mm_sphynx_adjust_canonical_url( $canonical, $post = null ) {
    if ( ! is_string( $canonical ) || '' === $canonical ) {
        return $canonical;
    }

    return mm_sphynx_adjust_host_url( $canonical );
}

add_filter( 'robots_txt', 'mm_sphynx_custom_robots_txt', 10, 2 );

function mm_sphynx_custom_robots_txt( string $output, bool $public ): string {
    $lines = [
        'User-agent: *',
        'Allow: /',
        'Sitemap: ' . trailingslashit( home_url() ) . 'wp-sitemap.xml',
    ];

    return implode( "\n", $lines );
}

add_action( 'wp_head', 'mm_sphynx_output_structured_data', 30 );

function mm_sphynx_output_structured_data(): void {
    if ( is_admin() || wp_is_json_request() ) {
        return;
    }

    $schemas = [];

    $breadcrumb = mm_sphynx_build_breadcrumb_schema();
    if ( $breadcrumb ) {
        $schemas[] = $breadcrumb;
    }

    if ( mm_sphynx_is_kittens_archive_view() ) {
        $archive_schema = mm_sphynx_build_kittens_itemlist_schema();
        if ( $archive_schema ) {
            $schemas[] = $archive_schema;
        }
    }

    if ( is_singular( 'kitten' ) ) {
        $single_schema = mm_sphynx_build_single_kitten_schema();
        if ( $single_schema ) {
            $schemas[] = $single_schema;
        }
    }

    foreach ( $schemas as $schema ) {
        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
    }
}

function mm_sphynx_is_kittens_archive_view(): bool {
    if ( is_post_type_archive( 'kitten' ) ) {
        return true;
    }

    if ( is_page() ) {
        $slug = get_post_field( 'post_name', get_queried_object_id() );
        if ( 'kittens' === $slug ) {
            return true;
        }
    }

    return false;
}

function mm_sphynx_map_inventory_status( string $status ): string {
    switch ( $status ) {
        case 'available':
            return 'https://schema.org/InStock';
        case 'reserved':
            return 'https://schema.org/LimitedAvailability';
        case 'upcoming':
            return 'https://schema.org/PreOrder';
        default:
            return 'https://schema.org/OutOfStock';
    }
}

function mm_sphynx_build_product_schema_from_kitten( array $kitten ): array {
    $name = $kitten['kitten_id'] ?: ( $kitten['title'] ?? '' );
    $images = [];

    if ( ! empty( $kitten['cover_image'] ) ) {
        $images[] = mm_sphynx_adjust_host_url( $kitten['cover_image'] );
    }

    if ( ! empty( $kitten['gallery'] ) && is_array( $kitten['gallery'] ) ) {
        foreach ( $kitten['gallery'] as $gallery_url ) {
            $images[] = mm_sphynx_adjust_host_url( $gallery_url );
        }
    }

    $images = array_values( array_unique( array_filter( $images ) ) );

    $raw_url     = $kitten['permalink'] ?? ( isset( $kitten['id'] ) ? get_permalink( $kitten['id'] ) : home_url( '/kittens/' ) );
    $product_url = mm_sphynx_adjust_host_url( $raw_url );

    $product = [
        '@type'        => 'Product',
        'name'         => $name,
        'sku'          => $kitten['kitten_id'] ?: '',
        'url'          => $product_url,
        'description'  => $kitten['short_description'] ?: $kitten['excerpt'] ?: '',
        'brand'        => [
            '@type' => 'Organization',
            'name'  => 'Miss Mermaid Sphynx',
            'url'   => home_url( '/' ),
        ],
    ];

    if ( $images ) {
        $product['image'] = $images;
    }

    $price = $kitten['price'] ?? null;
    $offer = [
        '@type'           => 'Offer',
        'availability'    => mm_sphynx_map_inventory_status( $kitten['status'] ?? '' ),
        'priceCurrency'   => 'USD',
        'url'             => $product_url,
        'seller'          => [
            '@type' => 'Organization',
            'name'  => 'Miss Mermaid Sphynx',
            'url'   => home_url( '/' ),
        ],
    ];

    if ( is_numeric( $price ) && $price > 0 ) {
        $offer['price'] = number_format( (float) $price, 2, '.', '' );
    }

    if ( ! empty( $kitten['status'] ) && 'adopted' === $kitten['status'] ) {
        $offer['availability'] = 'https://schema.org/SoldOut';
    }

    $product['offers'] = $offer;

    if ( ! empty( $kitten['age_hint'] ) ) {
        $product['additionalProperty'][] = [
            '@type' => 'PropertyValue',
            'name'  => 'Age',
            'value' => $kitten['age_hint'],
        ];
    }

    if ( ! empty( $kitten['parents']['sire'] ) || ! empty( $kitten['parents']['dam'] ) ) {
        $lineage = [];
        if ( ! empty( $kitten['parents']['sire'] ) ) {
            $lineage[] = 'Sire: ' . $kitten['parents']['sire'];
        }
        if ( ! empty( $kitten['parents']['dam'] ) ) {
            $lineage[] = 'Dam: ' . $kitten['parents']['dam'];
        }
        $product['additionalProperty'][] = [
            '@type' => 'PropertyValue',
            'name'  => 'Lineage',
            'value' => implode( ' · ', $lineage ),
        ];
    }

    return $product;
}

function mm_sphynx_build_kittens_itemlist_schema(): ?array {
    $dataset = mm_sphynx_get_kitten_dataset();
    $kittens = $dataset['all'] ?? [];
    if ( empty( $kittens ) ) {
        return null;
    }

    $elements = [];
    $position = 1;
    foreach ( $kittens as $kitten ) {
        $product_schema = mm_sphynx_build_product_schema_from_kitten( $kitten );
        $elements[]     = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'item'     => $product_schema,
        ];
    }

    return [
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => 'Miss Mermaid Sphynx Kittens',
        'itemListElement' => $elements,
    ];
}

function mm_sphynx_build_single_kitten_schema(): ?array {
    $post_id   = get_the_ID();
    $kitten_id = get_field( 'kitten_id', $post_id );
    $status    = get_field( 'status', $post_id ) ?: 'available';
    $price     = get_field( 'price', $post_id );

    $kitten = [
        'id'                => $post_id,
        'kitten_id'         => $kitten_id ?: get_the_title( $post_id ),
        'title'             => get_the_title( $post_id ),
        'cover_image'       => get_field( 'cover_image', $post_id ),
        'gallery'           => [],
        'short_description' => get_field( 'short_description', $post_id ) ?: wp_strip_all_tags( get_the_excerpt( $post_id ) ),
        'price'             => $price,
        'status'            => $status,
        'age_hint'          => get_field( 'age_hint', $post_id ),
        'parents'           => [
            'sire' => get_field( 'parent_sire', $post_id ),
            'dam'  => get_field( 'parent_dam', $post_id ),
        ],
    ];

    $gallery_rows = get_field( 'gallery', $post_id );
    if ( is_array( $gallery_rows ) ) {
        foreach ( $gallery_rows as $row ) {
            if ( ! empty( $row['image_url'] ) ) {
                $kitten['gallery'][] = $row['image_url'];
            }
        }
    }

    $images = array_values(
        array_unique(
            array_filter(
                array_map(
                    'mm_sphynx_adjust_host_url',
                    array_merge(
                        $kitten['cover_image'] ? [ $kitten['cover_image'] ] : [],
                        $kitten['gallery']
                    )
                )
            )
        )
    );

    $schema = [
        '@context' => 'https://schema.org',
        '@type'    => 'Product',
        'name'     => $kitten['kitten_id'],
        'sku'      => $kitten['kitten_id'],
        'description' => $kitten['short_description'],
        'brand'       => [
            '@type' => 'Organization',
            'name'  => 'Miss Mermaid Sphynx',
            'url'   => home_url( '/' ),
        ],
        'offers'      => array_filter(
            [
                '@type'         => 'Offer',
                'priceCurrency' => 'USD',
                'price'         => ( is_numeric( $kitten['price'] ) && $kitten['price'] > 0 ) ? number_format( (float) $kitten['price'], 2, '.', '' ) : null,
                'availability'  => mm_sphynx_map_inventory_status( $kitten['status'] ),
                'url'           => get_permalink( $post_id ),
                'seller'        => [
                    '@type' => 'Organization',
                    'name'  => 'Miss Mermaid Sphynx',
                    'url'   => home_url( '/' ),
                ],
            ]
        ),
    ];

    if ( ! empty( $images ) ) {
        $schema['image'] = $images;
    }

    return $schema;
}

function mm_sphynx_build_breadcrumb_schema(): ?array {
    $items = [];
    $position = 1;

    $items[] = [
        '@type'    => 'ListItem',
        'position' => $position++,
        'name'     => __( 'Home', 'mm-sphynx' ),
        'item'     => home_url( '/' ),
    ];

    if ( is_front_page() || is_home() && ! get_option( 'page_for_posts' ) ) {
        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    if ( is_home() ) {
        $blog_page = get_option( 'page_for_posts' );
        if ( $blog_page ) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => get_the_title( $blog_page ),
                'item'     => get_permalink( $blog_page ),
            ];
        }
    } elseif ( is_singular() ) {
        if ( is_singular( 'kitten' ) ) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => __( 'Available Kittens', 'mm-sphynx' ),
                'item'     => home_url( '/kittens/' ),
            ];
        } elseif ( 'post' === get_post_type() ) {
            $blog_page = get_option( 'page_for_posts' );
            if ( $blog_page ) {
                $items[] = [
                    '@type'    => 'ListItem',
                    'position' => $position++,
                    'name'     => get_the_title( $blog_page ),
                    'item'     => get_permalink( $blog_page ),
                ];
            } else {
                $items[] = [
                    '@type'    => 'ListItem',
                    'position' => $position++,
                    'name'     => __( 'Blog', 'mm-sphynx' ),
                    'item'     => home_url( '/blog/' ),
                ];
            }
        } elseif ( is_page() ) {
            $ancestors = array_reverse( get_post_ancestors( get_the_ID() ) );
            foreach ( $ancestors as $ancestor_id ) {
                $items[] = [
                    '@type'    => 'ListItem',
                    'position' => $position++,
                    'name'     => get_the_title( $ancestor_id ),
                    'item'     => get_permalink( $ancestor_id ),
                ];
            }
        }

        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => get_the_title(),
            'item'     => get_permalink(),
        ];
    } elseif ( mm_sphynx_is_kittens_archive_view() ) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => __( 'Available Kittens', 'mm-sphynx' ),
            'item'     => home_url( '/kittens/' ),
        ];
    } elseif ( is_post_type_archive() ) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => post_type_archive_title( '', false ),
            'item'     => home_url( '/' . get_query_var( 'post_type' ) . '/' ),
        ];
    } elseif ( is_archive() ) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => wp_get_document_title(),
            'item'     => esc_url( home_url( add_query_arg( null, null ) ) ),
        ];
    }

    return [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $items,
    ];
}
