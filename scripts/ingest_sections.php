<?php
/**
 * Ingest non-commerce content sections from matrix payload.
 *
 * Usage:
 *   wp eval-file /opt/sphynx-scripts/ingest_sections.php [/path/to/payload.json]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$payload_path = $argv[1] ?? '/opt/sphynx-content/mm_sections_payload.json';
if ( ! file_exists( $payload_path ) ) {
    throw new RuntimeException( sprintf( 'Payload file not found: %s', $payload_path ) );
}

$payload = json_decode( file_get_contents( $payload_path ), true );
if ( ! is_array( $payload ) ) {
    throw new RuntimeException( 'Invalid payload JSON.' );
}

$summary = [
    'pages_updated' => [],
    'posts_created' => [],
];

function mm_sections_get_page_id( string $slug ): ?int {
    $page = get_page_by_path( $slug );
    if ( $page instanceof WP_Post ) {
        return (int) $page->ID;
    }
    return null;
}

function mm_sections_resolve_page( string $slug, string $title ): int {
    $page_id = mm_sections_get_page_id( $slug );
    if ( $page_id ) {
        return $page_id;
    }

    $page_id = wp_insert_post(
        [
            'post_type'   => 'page',
            'post_status' => 'publish',
            'post_title'  => $title,
            'post_name'   => $slug,
        ],
        true
    );

    if ( is_wp_error( $page_id ) ) {
        throw new RuntimeException( $page_id->get_error_message() );
    }

    return (int) $page_id;
}

function mm_sections_anchor( string $value ): string {
    $anchor = strtolower( preg_replace( '/[^a-zA-Z0-9]+/', '-', $value ) );
    return trim( $anchor, '-' );
}

function mm_sections_render_achievements( array $rows ): string {
    if ( empty( $rows ) ) {
        return '';
    }

    usort(
        $rows,
        static function ( $a, $b ) {
            return strcmp( $a['year'] ?? '', $b['year'] ?? '' );
        }
    );

    ob_start();
    ?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"className":"mm-achievements"} -->
<div class="wp-block-group mm-achievements" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)">
    <!-- wp:heading {"textAlign":"center","level":1} -->
    <h1 class="wp-block-heading has-text-align-center">Achievements &amp; Awards</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">Our timeline charts the global milestones that define Miss Mermaid Sphynx. Each accolade celebrates artistry, health, and ethics in equal measure.</p>
    <!-- /wp:paragraph -->

    <!-- wp:list {"className":"mm-achievements__timeline"} -->
    <ul class="mm-achievements__timeline">
        <?php foreach ( $rows as $entry ) :
            $year   = esc_html( (string) ( $entry['year'] ?? '' ) );
            $title  = esc_html( (string) ( $entry['title_en'] ?? '' ) );
            $anchor = mm_sections_anchor( 'year-' . $year );
            ?>
            <li><a href="#<?php echo esc_attr( $anchor ); ?>"><strong><?php echo $year; ?></strong> &mdash; <?php echo $title; ?></a></li>
        <?php endforeach; ?>
    </ul>
    <!-- /wp:list -->

    <?php foreach ( $rows as $entry ) :
        $year        = esc_html( (string) ( $entry['year'] ?? '' ) );
        $title       = esc_html( (string) ( $entry['title_en'] ?? '' ) );
        $description = esc_html( (string) ( $entry['description_en'] ?? '' ) );
        $media       = ! empty( $entry['media_ref'] ) ? esc_url( (string) $entry['media_ref'] ) : '';
        $quote       = esc_html( (string) ( $entry['judge_quote_en'] ?? '' ) );
        $doc         = ! empty( $entry['doc_ref'] ) ? esc_url( (string) $entry['doc_ref'] ) : '';
        $anchor      = mm_sections_anchor( 'year-' . ( $entry['year'] ?? '' ) );
        ?>
        <!-- wp:group {"className":"mm-achievements__entry","anchor":"<?php echo esc_attr( $anchor ); ?>","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} -->
        <div class="wp-block-group mm-achievements__entry" id="<?php echo esc_attr( $anchor ); ?>" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
            <!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":"var:preset|spacing|40"}}} -->
            <div class="wp-block-columns are-vertically-aligned-center">
                <?php if ( $media ) : ?>
                <!-- wp:column {"verticalAlignment":"center","width":"40%"} -->
                <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:40%">
                    <!-- wp:image {"sizeSlug":"large","linkDestination":"media"} -->
                    <figure class="wp-block-image size-large"><img src="<?php echo $media; ?>" alt="<?php echo $title; ?>" loading="lazy"/></figure>
                    <!-- /wp:image -->
                </div>
                <!-- /wp:column -->
                <?php endif; ?>

                <!-- wp:column {"verticalAlignment":"center"} -->
                <div class="wp-block-column is-vertically-aligned-center">
                    <!-- wp:heading {"level":2} -->
                    <h2 class="wp-block-heading"><?php echo $year; ?> · <?php echo $title; ?></h2>
                    <!-- /wp:heading -->

                    <!-- wp:paragraph -->
                    <p><?php echo $description; ?></p>
                    <!-- /wp:paragraph -->

                    <?php if ( $quote ) : ?>
                    <!-- wp:quote -->
                    <blockquote class="wp-block-quote"><p><?php echo $quote; ?></p><cite>CFA Judge</cite></blockquote>
                    <!-- /wp:quote -->
                    <?php endif; ?>

                    <?php if ( $doc ) : ?>
                    <!-- wp:paragraph {"className":"mm-achievements__doc"} -->
                    <p class="mm-achievements__doc"><a href="<?php echo $doc; ?>" target="_blank" rel="noopener">Download official scorecard</a></p>
                    <!-- /wp:paragraph -->
                    <?php endif; ?>
                </div>
                <!-- /wp:column -->
            </div>
            <!-- /wp:columns -->
        </div>
        <!-- /wp:group -->
    <?php endforeach; ?>
</div>
<!-- /wp:group -->
    <?php
    return trim( ob_get_clean() );
}

function mm_sections_render_gallery( array $albums, array $items ): string {
    if ( empty( $albums ) ) {
        return '';
    }

    $by_album = [];
    foreach ( $items as $item ) {
        $key = $item['album_key'] ?? '';
        if ( ! $key ) {
            continue;
        }
        $by_album[ $key ][] = $item;
    }

    ob_start();
    ?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"className":"mm-gallery-page"} -->
<div class="wp-block-group mm-gallery-page" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--40)">
    <!-- wp:heading {"textAlign":"center","level":1} -->
    <h1 class="wp-block-heading has-text-align-center">Gallery Collections</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">Step inside the Miss Mermaid studio. Tour CFA champions, seasonal kitten diaries, and the art collaborations inspired by our sphynx muses.</p>
    <!-- /wp:paragraph -->

    <?php foreach ( $albums as $album ) :
        $key         = $album['album_key'] ?? '';
        $title       = esc_html( (string) ( $album['album_title_en'] ?? '' ) );
        $description = esc_html( (string) ( $album['description_en'] ?? '' ) );
        $cover       = ! empty( $album['cover'] ) ? esc_url( (string) $album['cover'] ) : '';
        $anchor      = mm_sections_anchor( 'gallery-' . $key );
        $gallery_set = $by_album[ $key ] ?? [];
        usort(
            $gallery_set,
            static function ( $a, $b ) {
                return (int) ( $a['order'] ?? 0 ) <=> (int) ( $b['order'] ?? 0 );
            }
        );
        ?>
        <!-- wp:group {"className":"mm-gallery__album","anchor":"<?php echo esc_attr( $anchor ); ?>","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}}} -->
        <div class="wp-block-group mm-gallery__album" id="<?php echo esc_attr( $anchor ); ?>" style="margin-top:var(--wp--preset--spacing--60)">
            <!-- wp:heading {"level":2} -->
            <h2 class="wp-block-heading"><?php echo $title; ?></h2>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p><?php echo $description; ?></p>
            <!-- /wp:paragraph -->

            <?php if ( $cover ) : ?>
            <!-- wp:image {"sizeSlug":"large"} -->
            <figure class="wp-block-image size-large"><img src="<?php echo $cover; ?>" alt="<?php echo $title; ?>" loading="lazy"/></figure>
            <!-- /wp:image -->
            <?php endif; ?>

            <?php if ( $gallery_set ) : ?>
            <!-- wp:gallery {"linkTo":"media","className":"mm-gallery__grid"} -->
            <figure class="wp-block-gallery has-nested-images columns-default is-cropped mm-gallery__grid">
                <?php foreach ( $gallery_set as $media ) :
                    $src     = esc_url( (string) ( $media['media_ref'] ?? '' ) );
                    $caption = esc_html( (string) ( $media['caption_en'] ?? '' ) );
                    if ( ! $src ) {
                        continue;
                    }
                    ?>
                    <!-- wp:image {"sizeSlug":"large","linkDestination":"media"} -->
                    <figure class="wp-block-image size-large"><a href="<?php echo $src; ?>"><img src="<?php echo $src; ?>" alt="<?php echo $caption; ?>" loading="lazy"/></a><?php if ( $caption ) : ?><figcaption class="wp-element-caption"><?php echo $caption; ?></figcaption><?php endif; ?></figure>
                    <!-- /wp:image -->
                <?php endforeach; ?>
            </figure>
            <!-- /wp:gallery -->
            <?php endif; ?>
        </div>
        <!-- /wp:group -->
    <?php endforeach; ?>
</div>
<!-- /wp:group -->
    <?php
    return trim( ob_get_clean() );
}

function mm_sections_render_guides( array $sections ): string {
    if ( empty( $sections ) ) {
        return '';
    }

    usort(
        $sections,
        static function ( $a, $b ) {
            return (int) ( $a['order'] ?? 0 ) <=> (int) ( $b['order'] ?? 0 );
        }
    );

    ob_start();
    ?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|50"}},"className":"mm-guides"} -->
<div class="wp-block-group mm-guides" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)">
    <!-- wp:heading {"textAlign":"center","level":1} -->
    <h1 class="wp-block-heading has-text-align-center">Signature Care Guides</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">Our guardianship rituals marry wellness and artistry. Explore the practices that keep Miss Mermaid sphynx radiant, confident, and deeply connected to their families.</p>
    <!-- /wp:paragraph -->

    <?php foreach ( $sections as $section ) :
        $title = esc_html( (string) ( $section['title_en'] ?? '' ) );
        $body  = esc_html( (string) ( $section['body_copy_en'] ?? '' ) );
        $media = ! empty( $section['media_ref'] ) ? esc_url( (string) $section['media_ref'] ) : '';
        ?>
        <!-- wp:group {"className":"mm-guides__section","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|30"},"border":{"radius":"18px"},"color":{"background":"#111117"}}} -->
        <div class="wp-block-group mm-guides__section has-background" style="border-radius:18px;background-color:#111117;padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)">
            <!-- wp:heading {"level":2} -->
            <h2 class="wp-block-heading"><?php echo $title; ?></h2>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p><?php echo $body; ?></p>
            <!-- /wp:paragraph -->

            <?php if ( $media ) : ?>
            <!-- wp:image {"sizeSlug":"large"} -->
            <figure class="wp-block-image size-large"><img src="<?php echo $media; ?>" alt="<?php echo $title; ?>" loading="lazy"/></figure>
            <!-- /wp:image -->
            <?php endif; ?>
        </div>
        <!-- /wp:group -->
    <?php endforeach; ?>

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">Have more questions? Visit our <a href="/faq">Frequently Asked Questions</a> or message the guardianship team for personalised guidance.</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
    <?php
    return trim( ob_get_clean() );
}

function mm_sections_render_faq( array $entries ): array {
    if ( empty( $entries ) ) {
        return [ '', '' ];
    }

    $blocks = [];
    $schema = [];

    foreach ( $entries as $entry ) {
        $question = trim( (string) ( $entry['question_en'] ?? '' ) );
        $answer   = trim( (string) ( $entry['answer_en'] ?? '' ) );
        if ( ! $question || ! $answer ) {
            continue;
        }
        $blocks[] = sprintf(
            '<details class="mm-faq__item"><summary>%s</summary><p>%s</p></details>',
            esc_html( $question ),
            esc_html( $answer )
        );

        if ( 'yes' === strtolower( (string) ( $entry['schema_yes_no'] ?? '' ) ) ) {
            $schema[] = [
                '@type'          => 'Question',
                'name'           => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $answer,
                ],
            ];
        }
    }

    $content = '';
    if ( $blocks ) {
        $content = '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|30"}},"className":"mm-faq"} -->';
        $content .= '<div class="wp-block-group mm-faq" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">';
        $content .= '<!-- wp:heading {"level":1,"textAlign":"center"} --><h1 class="wp-block-heading has-text-align-center">Frequently Asked Questions</h1><!-- /wp:heading -->';
        $content .= '<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Quick answers to the questions guardians ask most before and after bringing home a Miss Mermaid sphynx.</p><!-- /wp:paragraph -->';
        $content .= '<!-- wp:html -->' . implode( '', $blocks ) . '<!-- /wp:html -->';
        $content .= '</div><!-- /wp:group -->';
    }

    $schema_json = '';
    if ( $schema ) {
        $schema_json = wp_json_encode(
            [
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => $schema,
            ],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
    }

    return [ $content, $schema_json ];
}

function mm_sections_upsert_post( array $entry ): int {
    $slug    = sanitize_title( $entry['slug'] ?? '' );
    $title   = $entry['title_en'] ?? '';
    $excerpt = $entry['excerpt_en'] ?? '';

    if ( ! $slug || ! $title ) {
        return 0;
    }

    $existing = get_page_by_path( $slug, OBJECT, 'post' );
    $postarr  = [
        'post_type'   => 'post',
        'post_status' => 'publish',
        'post_title'  => $title,
        'post_name'   => $slug,
        'post_excerpt'=> $excerpt,
    ];

    if ( $existing instanceof WP_Post ) {
        $postarr['ID'] = $existing->ID;
        $post_id       = wp_update_post( $postarr, true );
    } else {
        $post_id = wp_insert_post( $postarr, true );
    }

    if ( is_wp_error( $post_id ) ) {
        throw new RuntimeException( $post_id->get_error_message() );
    }

    return (int) $post_id;
}

function mm_sections_render_blog_content( array $entry ): string {
    $hero = ! empty( $entry['hero_ref'] ) ? esc_url( (string) $entry['hero_ref'] ) : '';

    $sections = [];
    for ( $i = 1; $i <= 3; $i++ ) {
        $title = $entry[ "section_{$i}_title" ] ?? '';
        $body  = $entry[ "section_{$i}_body" ] ?? '';
        if ( ! $title || ! $body ) {
            continue;
        }
        $sections[] = [
            'title' => esc_html( $title ),
            'body'  => esc_html( $body ),
        ];
    }

    ob_start();
    ?>
<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|40","padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|50"}}}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--50)">
    <?php if ( $hero ) : ?>
    <!-- wp:image {"sizeSlug":"large"} -->
    <figure class="wp-block-image size-large"><img src="<?php echo $hero; ?>" alt="<?php echo esc_attr( $entry['title_en'] ?? '' ); ?>" loading="lazy"/></figure>
    <!-- /wp:image -->
    <?php endif; ?>

    <!-- wp:paragraph {"fontSize":"large"} -->
    <p class="has-large-font-size"><?php echo esc_html( (string) ( $entry['excerpt_en'] ?? '' ) ); ?></p>
    <!-- /wp:paragraph -->

    <?php foreach ( $sections as $section ) : ?>
        <!-- wp:heading {"level":2} -->
        <h2 class="wp-block-heading"><?php echo $section['title']; ?></h2>
        <!-- /wp:heading -->

        <!-- wp:paragraph -->
        <p><?php echo $section['body']; ?></p>
        <!-- /wp:paragraph -->
    <?php endforeach; ?>

    <!-- wp:paragraph -->
    <p>Continue the journey inside our <a href="/achievements">Achievements timeline</a>, revisit detailed <a href="/guides">care guides</a>, and meet current companions on the <a href="/kittens">kittens page</a>.</p>
    <!-- /wp:paragraph -->

    <?php if ( ! empty( $entry['cta_text'] ) && ! empty( $entry['cta_link'] ) ) : ?>
        <!-- wp:buttons -->
        <div class="wp-block-buttons">
            <!-- wp:button {"className":"is-style-outline"} -->
            <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( (string) $entry['cta_link'] ); ?>"><?php echo esc_html( (string) $entry['cta_text'] ); ?></a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->
    <?php endif; ?>
</div>
<!-- /wp:group -->
    <?php
    return trim( ob_get_clean() );
}

function mm_sections_render_policy_page( array $sections, string $title, string $intro ): string {
    ob_start();
    ?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|40"}},"className":"mm-policies"} -->
<div class="wp-block-group mm-policies" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)">
    <!-- wp:heading {"textAlign":"center","level":1} -->
    <h1 class="wp-block-heading has-text-align-center"><?php echo esc_html( $title ); ?></h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center"><?php echo esc_html( $intro ); ?></p>
    <!-- /wp:paragraph -->

    <?php foreach ( $sections as $section ) :
        $heading = esc_html( (string) ( $section['section_title_en'] ?? '' ) );
        $body    = esc_html( (string) ( $section['body_en'] ?? '' ) );
        ?>
        <!-- wp:group {"className":"mm-policies__section","style":{"spacing":{"blockGap":"var:preset|spacing|20"},"color":{"background":"#111117"},"border":{"radius":"16px"}}} -->
        <div class="wp-block-group mm-policies__section has-background" style="border-radius:16px;background-color:#111117">
            <!-- wp:heading {"level":2} -->
            <h2 class="wp-block-heading"><?php echo $heading; ?></h2>
            <!-- /wp:heading -->

            <!-- wp:paragraph -->
            <p><?php echo $body; ?></p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->
    <?php endforeach; ?>
</div>
<!-- /wp:group -->
    <?php
    return trim( ob_get_clean() );
}

function mm_sections_render_contact( array $entries ): string {
    if ( empty( $entries ) ) {
        return '';
    }

    $map = [];
    foreach ( $entries as $entry ) {
        $channel = strtolower( (string) ( $entry['channel'] ?? '' ) );
        if ( ! $channel ) {
            continue;
        }
        $map[ $channel ] = $entry;
    }

    $items = [];
    $label_map = [
        'email'     => __( 'Email', 'miss-mermaid' ),
        'instagram' => 'Instagram',
        'youtube'   => 'YouTube',
    ];

    foreach ( $map as $channel => $entry ) {
        $label = $label_map[ $channel ] ?? ucfirst( $channel );
        $value = trim( (string) ( $entry['value'] ?? '' ) );
        $link  = trim( (string) ( $entry['link'] ?? '' ) );

        if ( 'email' === $channel ) {
            $href  = strpos( $link, '@' ) !== false ? 'mailto:' . $link : $link;
            $items[] = sprintf( '<li><strong>Email:</strong> <a href="%s">%s</a></li>', esc_url( $href ), esc_html( $value ?: $link ) );
        } else {
            if ( $link && strpos( $link, 'http' ) !== 0 ) {
                $link = 'https://' . ltrim( $link, '/' );
            }
            $items[] = sprintf( '<li><strong>%s:</strong> <a href="%s" target="_blank" rel="noopener">%s</a></li>', esc_html( $label ), esc_url( $link ?: '#' ), esc_html( $value ?: $link ) );
        }
    }

    if ( ! $items ) {
        return '';
    }

    $form_id = null;
    if ( function_exists( 'mm_sphynx_get_form_id' ) ) {
        $form_id = mm_sphynx_get_form_id( 'general_contact' );
    }

    ob_start();
    ?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|40"}},"className":"mm-contact"} -->
<div class="wp-block-group mm-contact" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--40)">
    <!-- wp:heading {"textAlign":"center","level":1} -->
    <h1 class="wp-block-heading has-text-align-center">Contact Miss Mermaid</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">Reach our guardianship concierge for adoption inquiries, collaborations, or alumni updates.</p>
    <!-- /wp:paragraph -->

    <!-- wp:list {"className":"mm-contact__channels"} -->
    <ul class="mm-contact__channels">
        <?php echo implode( '', $items ); ?>
    </ul>
    <!-- /wp:list -->

    <?php if ( $form_id ) : ?>
        <!-- wp:shortcode -->
        [fluentform id="<?php echo (int) $form_id; ?>"]
        <!-- /wp:shortcode -->
    <?php endif; ?>
</div>
<!-- /wp:group -->
    <?php
    return trim( ob_get_clean() );
}

// Achievements page.
if ( ! empty( $payload['achievements'] ) ) {
    $content = mm_sections_render_achievements( $payload['achievements'] );
    if ( $content ) {
        $page_id = mm_sections_resolve_page( 'achievements', 'Achievements & Awards' );
        wp_update_post(
            [
                'ID'           => $page_id,
                'post_content' => $content,
            ]
        );
        $summary['pages_updated'][] = 'achievements';
    }
}

// Gallery page.
if ( ! empty( $payload['gallery_albums'] ) ) {
    $content = mm_sections_render_gallery( $payload['gallery_albums'], $payload['gallery_items'] ?? [] );
    if ( $content ) {
        $page_id = mm_sections_resolve_page( 'gallery', 'Gallery' );
        wp_update_post(
            [
                'ID'           => $page_id,
                'post_content' => $content,
            ]
        );
        $summary['pages_updated'][] = 'gallery';
    }
}

// Guides page.
if ( ! empty( $payload['guides'] ) ) {
    $content = mm_sections_render_guides( $payload['guides'] );
    if ( $content ) {
        $page_id = mm_sections_resolve_page( 'guides', 'Care Guides' );
        wp_update_post(
            [
                'ID'           => $page_id,
                'post_content' => $content,
            ]
        );
        $summary['pages_updated'][] = 'guides';
    }
}

// FAQ page.
if ( ! empty( $payload['faq'] ) ) {
    [ $faq_content, $faq_schema ] = mm_sections_render_faq( $payload['faq'] );
    if ( $faq_content ) {
        $page_id = mm_sections_resolve_page( 'faq', 'FAQ' );
        $schema_block = '';
        if ( $faq_schema ) {
            $schema_block = '<!-- wp:html --><script type="application/ld+json">' . $faq_schema . '</script><!-- /wp:html -->';
        }

        wp_update_post(
            [
                'ID'           => $page_id,
                'post_content' => $faq_content . $schema_block,
            ]
        );
        $summary['pages_updated'][] = 'faq';
    }
}

// Blog posts.
if ( ! empty( $payload['blog'] ) ) {
    foreach ( $payload['blog'] as $entry ) {
        $post_id = mm_sections_upsert_post( $entry );
        if ( $post_id ) {
            $content = mm_sections_render_blog_content( $entry );
            wp_update_post(
                [
                    'ID'           => $post_id,
                    'post_content' => $content,
                    'post_excerpt' => $entry['excerpt_en'] ?? '',
                ]
            );
            $summary['posts_created'][] = get_post_field( 'post_name', $post_id );
        }
    }
}

// Policies page.
if ( ! empty( $payload['policies'] ) ) {
    $content = mm_sections_render_policy_page( $payload['policies'], 'Adoption Policies', 'Transparent policies keep every guardian informed before deposits, during selection, and all the way through travel day.' );
    $page_id = mm_sections_resolve_page( 'policies', 'Adoption Policies' );
    wp_update_post(
        [
            'ID'           => $page_id,
            'post_content' => $content,
        ]
    );
    $summary['pages_updated'][] = 'policies';
}

if ( ! empty( $payload['terms'] ) ) {
    $content = mm_sections_render_policy_page( $payload['terms'], 'Terms of Use', 'These terms outline how you may access and engage with the Miss Mermaid Sphynx digital experience.' );
    $page_id = mm_sections_resolve_page( 'terms', 'Terms of Use' );
    wp_update_post(
        [
            'ID'           => $page_id,
            'post_content' => $content,
        ]
    );
    $summary['pages_updated'][] = 'terms';
}

if ( ! empty( $payload['privacy'] ) ) {
    $content = mm_sections_render_policy_page( $payload['privacy'], 'Privacy Notice', 'We safeguard the personal information you share during inquiries, applications, and concierge conversations.' );
    $page_id = mm_sections_resolve_page( 'privacy', 'Privacy Notice' );
    wp_update_post(
        [
            'ID'           => $page_id,
            'post_content' => $content,
        ]
    );
    $summary['pages_updated'][] = 'privacy';
}

if ( ! empty( $payload['contact'] ) ) {
    $content = mm_sections_render_contact( $payload['contact'] );
    if ( $content ) {
        $page_id = mm_sections_resolve_page( 'contact', 'Contact Miss Mermaid' );
        wp_update_post(
            [
                'ID'           => $page_id,
                'post_content' => $content,
            ]
        );
        $summary['pages_updated'][] = 'contact';
    }
}

echo wp_json_encode( $summary, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
