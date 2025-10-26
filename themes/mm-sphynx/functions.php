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
                    'key'               => 'field_mm_kitten_id',
                    'label'             => __( 'Kitten ID', 'mm-sphynx' ),
                    'name'              => 'kitten_id',
                    'type'              => 'text',
                    'required'          => 1,
                    'instructions'      => __( 'Unique identifier used internally and on adoption dossiers.', 'mm-sphynx' ),
                    'wrapper'           => [ 'width' => '25' ],
                ],
                [
                    'key'          => 'field_mm_birthdate',
                    'label'        => __( 'Birthdate', 'mm-sphynx' ),
                    'name'         => 'birthdate',
                    'type'         => 'date_picker',
                    'display_format' => 'Y-m-d',
                    'return_format'  => 'Y-m-d',
                    'first_day'      => 1,
                    'wrapper'        => [ 'width' => '25' ],
                ],
                [
                    'key'          => 'field_mm_age_auto',
                    'label'        => __( 'Age (auto)', 'mm-sphynx' ),
                    'name'         => 'age_auto',
                    'type'         => 'text',
                    'instructions' => __( 'Calculated from birthdate for display. Refresh editor to update.', 'mm-sphynx' ),
                    'wrapper'      => [ 'width' => '25' ],
                    'readonly'     => 1,
                    'disabled'     => 1,
                ],
                [
                    'key'      => 'field_mm_sex',
                    'label'    => __( 'Sex', 'mm-sphynx' ),
                    'name'     => 'sex',
                    'type'     => 'select',
                    'choices'  => [
                        'male'   => __( 'Male', 'mm-sphynx' ),
                        'female' => __( 'Female', 'mm-sphynx' ),
                    ],
                    'wrapper'  => [ 'width' => '25' ],
                    'return_format' => 'value',
                ],
                [
                    'key'      => 'field_mm_color',
                    'label'    => __( 'Color', 'mm-sphynx' ),
                    'name'     => 'color',
                    'type'     => 'select',
                    'choices'  => [
                        'blue'        => __( 'Blue', 'mm-sphynx' ),
                        'seal'        => __( 'Seal', 'mm-sphynx' ),
                        'cream'       => __( 'Cream', 'mm-sphynx' ),
                        'red'         => __( 'Red', 'mm-sphynx' ),
                        'white'       => __( 'White', 'mm-sphynx' ),
                        'black'       => __( 'Black', 'mm-sphynx' ),
                        'tabby'       => __( 'Tabby', 'mm-sphynx' ),
                        'bicolor'     => __( 'Bi-color', 'mm-sphynx' ),
                        'other'       => __( 'Other', 'mm-sphynx' ),
                    ],
                    'multiple' => 1,
                    'ui'       => 1,
                    'return_format' => 'value',
                ],
                [
                    'key'      => 'field_mm_personality',
                    'label'    => __( 'Personality Tags', 'mm-sphynx' ),
                    'name'     => 'personality_tags',
                    'type'     => 'checkbox',
                    'choices'  => [
                        'gentle'       => __( 'Gentle', 'mm-sphynx' ),
                        'active'       => __( 'Active', 'mm-sphynx' ),
                        'social'       => __( 'Social', 'mm-sphynx' ),
                        'affectionate' => __( 'Affectionate', 'mm-sphynx' ),
                        'curious'      => __( 'Curious', 'mm-sphynx' ),
                        'playful'      => __( 'Playful', 'mm-sphynx' ),
                        'confident'    => __( 'Confident', 'mm-sphynx' ),
                        'lap-lover'    => __( 'Lap Lover', 'mm-sphynx' ),
                    ],
                    'layout'   => 'horizontal',
                    'ui'       => 0,
                ],
                [
                    'key'          => 'field_mm_price',
                    'label'        => __( 'Price (USD)', 'mm-sphynx' ),
                    'name'         => 'price',
                    'type'         => 'number',
                    'wrapper'      => [ 'width' => '25' ],
                    'prepend'      => '$',
                    'min'          => 0,
                    'step'         => 1,
                ],
                [
                    'key'      => 'field_mm_status',
                    'label'    => __( 'Status', 'mm-sphynx' ),
                    'name'     => 'status',
                    'type'     => 'select',
                    'choices'  => [
                        'available' => __( 'Available', 'mm-sphynx' ),
                        'upcoming'  => __( 'Upcoming', 'mm-sphynx' ),
                        'reserved'  => __( 'Reserved', 'mm-sphynx' ),
                        'adopted'   => __( 'Adopted', 'mm-sphynx' ),
                    ],
                    'default_value' => 'available',
                    'return_format' => 'value',
                ],
                [
                    'key'       => 'field_mm_litter',
                    'label'     => __( 'Litter', 'mm-sphynx' ),
                    'name'      => 'litter_id',
                    'type'      => 'post_object',
                    'post_type' => [ 'litter' ],
                    'return_format' => 'id',
                    'ui'        => 1,
                    'allow_null'=> 1,
                ],
                [
                    'key'   => 'field_mm_media_gallery',
                    'label' => __( 'Media Gallery', 'mm-sphynx' ),
                    'name'  => 'media_gallery',
                    'type'  => 'gallery',
                    'preview_size' => 'medium',
                ],
                [
                    'key'        => 'field_mm_video_urls',
                    'label'      => __( 'Video URLs', 'mm-sphynx' ),
                    'name'       => 'video_urls',
                    'type'       => 'repeater',
                    'collapsed'  => '',
                    'layout'     => 'table',
                    'button_label' => __( 'Add Video URL', 'mm-sphynx' ),
                    'sub_fields' => [
                        [
                            'key'   => 'field_mm_video_url_single',
                            'label' => __( 'Video URL', 'mm-sphynx' ),
                            'name'  => 'video_url',
                            'type'  => 'url',
                        ],
                    ],
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
            $post_id  = get_the_ID();
            $status   = get_field( 'status', $post_id ) ?: 'available';
            $price    = get_field( 'price', $post_id );
            $gallery  = get_field( 'media_gallery', $post_id ) ?: [];
            $videos   = get_field( 'video_urls', $post_id ) ?: [];
            $colors   = get_field( 'color', $post_id );
            $featured = (bool) get_field( 'featured', $post_id );
            $birth    = get_field( 'birthdate', $post_id );

            $gallery_urls = [];
            foreach ( $gallery as $image ) {
                if ( isset( $image['sizes']['large'] ) ) {
                    $gallery_urls[] = $image['sizes']['large'];
                } elseif ( isset( $image['url'] ) ) {
                    $gallery_urls[] = $image['url'];
                }
            }

            $video_urls = [];
            if ( is_array( $videos ) ) {
                foreach ( $videos as $video ) {
                    if ( isset( $video['video_url'] ) && $video['video_url'] ) {
                        $video_urls[] = esc_url_raw( $video['video_url'] );
                    }
                }
            }

            $kitten = [
                'id'           => $post_id,
                'title'        => get_the_title(),
                'permalink'    => get_permalink(),
                'kitten_id'    => get_field( 'kitten_id', $post_id ),
                'sex'          => get_field( 'sex', $post_id ),
                'color'        => is_array( $colors ) ? array_values( $colors ) : ( $colors ? [ $colors ] : [] ),
                'personality'  => get_field( 'personality_tags', $post_id ) ?: [],
                'price'        => '' !== $price && null !== $price ? (float) $price : null,
                'status'       => $status,
                'status_label' => $status_choices[ $status ] ?? ucfirst( $status ),
                'featured'     => $featured,
                'litter'       => get_field( 'litter_id', $post_id ),
                'thumbnail'    => get_the_post_thumbnail_url( $post_id, 'large' ),
                'gallery'      => $gallery_urls,
                'videos'       => $video_urls,
                'birthdate'    => $birth,
                'age'          => mm_sphynx_calculate_age_string( $birth ),
                'excerpt'      => has_excerpt( $post_id ) ? get_the_excerpt() : '',
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

            $timeline[] = [
                'id'        => $post_id,
                'title'     => get_field( 'litter_id', $post_id ) ?: get_the_title(),
                'queen'     => get_field( 'queen', $post_id ),
                'sire'      => get_field( 'sire', $post_id ),
                'due_date'  => get_field( 'due_date', $post_id ),
                'born_date' => get_field( 'born_date', $post_id ),
                'status'    => $status,
                'status_label' => $status_choices[ $status ] ?? ucfirst( $status ),
                'note'      => get_field( 'note', $post_id ),
            ];
        }
        wp_reset_postdata();
    }

    return $timeline;
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

    wp_localize_script(
        'mm-sphynx-kittens',
        'mmSphynxKittens',
        [
            'kittens' => $dataset['all'],
            'forms'   => [
                'apply'    => mm_sphynx_get_form_id( 'adoption_apply' ),
                'waitlist' => mm_sphynx_get_form_id( 'adoption_apply' ),
                'select'   => mm_sphynx_get_form_id( 'select_kitten' ),
            ],
            'litters' => $litters,
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
    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
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

    $user = get_user_by( 'email', $email );
    if ( ! ( $user instanceof \WP_User ) ) {
        $username = sanitize_user( current( explode( '@', $email ) ) );
        if ( ! $username ) {
            $username = 'guardian_' . wp_generate_password( 6, false );
        }
        $password = wp_generate_password( 24 );
        $user_id  = wp_insert_user(
            [
                'user_login' => $username,
                'user_email' => $email,
                'user_pass'  => $password,
                'role'       => mm_sphynx_get_applicant_role(),
                'display_name' => sanitize_text_field( $form_data['guardian_name'] ?? $email ),
            ]
        );

        if ( is_wp_error( $user_id ) ) {
            return;
        }
        $user = get_user_by( 'id', $user_id );
    }

    if ( ! ( $user instanceof \WP_User ) ) {
        return;
    }

    mm_sphynx_ensure_applicant_role( $user );

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

    $status  = get_user_meta( $user->ID, '_mm_application_status', true ) ?: 'applicant';
    $mapping = [
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

    $result = mm_sphynx_transition_status(
        $user_id,
        $status,
        sprintf(
            /* translators: %s admin username */
            __( 'Manual status change by %s.', 'mm-sphynx' ),
            wp_get_current_user()->display_name
        ),
        'admin'
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
    $key    = 'mm_admin_notice_' . get_current_user_id();
    $notice = get_transient( $key );
    if ( ! $notice ) {
        return;
    }

    delete_transient( $key );

    $type    = 'success' === $notice['type'] ? 'notice-success' : 'notice-error';
    $message = $notice['message'] ?? '';

    if ( ! $message ) {
        return;
    }
    ?>
    <div class="notice <?php echo esc_attr( $type ); ?> is-dismissible">
        <p><?php echo esc_html( $message ); ?></p>
    </div>
    <?php
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
    if ( empty( $_SERVER['HTTP_HOST'] ) || ! preg_match( '#^https?://#i', $url ) ) {
        return $url;
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
