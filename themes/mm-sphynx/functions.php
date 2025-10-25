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
    add_action( 'init', 'mm_sphynx_register_content_models' );
    add_action( 'acf/init', 'mm_sphynx_register_acf_groups' );
    add_action( 'after_switch_theme', 'mm_sphynx_flush_rewrite_rules' );
    add_action( 'admin_init', 'mm_sphynx_register_kitten_admin_views' );
    add_action( 'restrict_manage_posts', 'mm_sphynx_render_kitten_admin_filters' );
    add_action( 'pre_get_posts', 'mm_sphynx_handle_kitten_queries' );
    add_filter( 'posts_orderby', 'mm_sphynx_apply_custom_status_order', 10, 2 );
    add_filter( 'acf/validate_value/name=kitten_id', 'mm_sphynx_validate_unique_kitten_id', 10, 4 );
    add_filter( 'acf/prepare_field/name=age_auto', 'mm_sphynx_prepare_age_field' );
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
