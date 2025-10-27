<?php
/**
 * Full site content hydration from Excel-derived JSON payload.
 *
 * Usage:
 *   wp eval-file /opt/sphynx-scripts/full_ingest.php [/path/to/mm_full_payload.json]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$payload_path = $argv[1] ?? '/opt/sphynx-content/mm_full_payload.json';
if ( ! file_exists( $payload_path ) ) {
	throw new RuntimeException( sprintf( 'Payload file not found: %s', $payload_path ) );
}

$payload = json_decode( file_get_contents( $payload_path ), true );
if ( ! is_array( $payload ) ) {
	throw new RuntimeException( 'Invalid payload JSON.' );
}

$summary = [
	'pages_updated' => [],
	'sections_count' => 0,
	'blog_posts' => 0,
	'kittens' => 0,
	'litters' => 0,
	'seo_entries' => 0,
];

/**
 * Helpers
 */
function mm_full_get_or_create_page( string $slug, string $title ): int {
	$page = get_page_by_path( $slug );
	if ( $page instanceof WP_Post ) {
		return (int) $page->ID;
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

function mm_full_placeholder_img( string $ref ): string {
	$alt = $ref !== '' ? esc_attr( $ref ) : __( 'image placeholder', 'mm-sphynx' );
	return '<img alt="' . $alt . '" src="#" />';
}

function mm_full_escape( $value ): string {
	return esc_html( (string) $value );
}

function mm_full_render_paragraph( string $text ): string {
	if ( '' === trim( $text ) ) {
		return '';
	}
	return '<!-- wp:paragraph --><p>' . mm_full_escape( $text ) . '</p><!-- /wp:paragraph -->';
}

function mm_full_render_heading( string $text, int $level = 2 ): string {
	if ( '' === trim( $text ) ) {
		return '';
	}
	return '<!-- wp:heading {"level":' . $level . '} --><h' . $level . '>' . mm_full_escape( $text ) . '</h' . $level . '><!-- /wp:heading -->';
}

function mm_full_render_image_block( string $media_ref ): string {
	if ( '' === trim( $media_ref ) ) {
		return '';
	}
	$img = mm_full_placeholder_img( $media_ref );
	return '<!-- wp:html -->' . $img . '<!-- /wp:html -->';
}

function mm_full_render_button( string $text, string $url ): string {
	if ( '' === trim( $text ) || '' === trim( $url ) ) {
		return '';
	}
	return '<!-- wp:button {"backgroundColor":"brand-gold","textColor":"brand-black"} --><div class="wp-block-button"><a class="wp-block-button__link has-brand-black-color has-brand-gold-background-color has-text-color has-background wp-element-button" href="' . esc_url( $url ) . '">' . mm_full_escape( $text ) . '</a></div><!-- /wp:button -->';
}

function mm_full_render_outline_button( string $text, string $url ): string {
	if ( '' === trim( $text ) || '' === trim( $url ) ) {
		return '';
	}
	return '<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $url ) . '">' . mm_full_escape( $text ) . '</a></div><!-- /wp:button -->';
}

/**
 * Home content.
 */
function mm_full_render_home( array $sections ): string {
	if ( empty( $sections ) ) {
		return '';
	}

	$get_section = static function ( string $key ) use ( $sections ) {
		foreach ( $sections as $row ) {
			if ( isset( $row['section_key'] ) && $row['section_key'] === $key ) {
				return $row;
			}
		}
		return null;
	};

	$hero      = $get_section( 'hero' );
	$trust_set = array_values(
		array_filter(
			$sections,
			static function ( $row ) {
				return isset( $row['section_key'] ) && 0 === strpos( $row['section_key'], 'trust_' );
			}
		)
	);
	$cta_band = $get_section( 'cta_band' );

	$content = '<!-- wp:group {"tagName":"section","className":"mm-home__hero"} --><div class="wp-block-group mm-home__hero">';
	if ( $hero ) {
		if ( ! empty( $hero['subtitle'] ) ) {
			$content .= '<!-- wp:paragraph {"textColor":"brand-gold","className":"mm-home__eyebrow"} --><p class="mm-home__eyebrow has-brand-gold-color has-text-color">' . mm_full_escape( $hero['subtitle'] ) . '</p><!-- /wp:paragraph -->';
		}
		if ( ! empty( $hero['title'] ) ) {
			$content .= '<!-- wp:heading {"level":1} --><h1>' . mm_full_escape( $hero['title'] ) . '</h1><!-- /wp:heading -->';
		}
		if ( ! empty( $hero['body'] ) ) {
			$content .= mm_full_render_paragraph( $hero['body'] );
		}
		$cta_buttons = '';
		$primary_btn = mm_full_render_button( $hero['cta1_text'] ?? '', $hero['cta1_link'] ?? '' );
		$secondary_btn = mm_full_render_outline_button( $hero['cta2_text'] ?? '', $hero['cta2_link'] ?? '' );
		if ( $primary_btn || $secondary_btn ) {
			$cta_buttons .= '<!-- wp:buttons {"layout":{"type":"flex"}} --><div class="wp-block-buttons">' . $primary_btn . $secondary_btn . '</div><!-- /wp:buttons -->';
		}
		if ( $cta_buttons ) {
			$content .= $cta_buttons;
		}
		if ( ! empty( $hero['media_ref'] ) ) {
			$content .= mm_full_render_image_block( $hero['media_ref'] );
		}
	}
	$content .= '</div><!-- /wp:group -->';

	if ( $trust_set ) {
		$content .= '<!-- wp:group {"tagName":"section","className":"mm-home__trust"} --><div class="wp-block-group mm-home__trust">';
		$content .= '<!-- wp:columns -->';
		$content .= '<div class="wp-block-columns">';
		foreach ( $trust_set as $trust ) {
			$content .= '<!-- wp:column --><div class="wp-block-column">';
			if ( ! empty( $trust['media_ref'] ) ) {
				$content .= mm_full_render_image_block( $trust['media_ref'] );
			}
			if ( ! empty( $trust['title'] ) ) {
				$content .= mm_full_render_heading( $trust['title'], 3 );
			}
			if ( ! empty( $trust['body'] ) ) {
				$content .= mm_full_render_paragraph( $trust['body'] );
			}
			$content .= '</div><!-- /wp:column -->';
		}
		$content .= '</div><!-- /wp:columns -->';
		$content .= '</div><!-- /wp:group -->';
	}

	if ( $cta_band ) {
		$content .= '<!-- wp:group {"tagName":"section","className":"mm-home__band"} --><div class="wp-block-group mm-home__band">';
		if ( ! empty( $cta_band['title'] ) ) {
			$content .= mm_full_render_heading( $cta_band['title'], 3 );
		}
		if ( ! empty( $cta_band['body'] ) ) {
			$content .= mm_full_render_paragraph( $cta_band['body'] );
		}
		if ( ! empty( $cta_band['media_ref'] ) ) {
			$content .= mm_full_render_image_block( $cta_band['media_ref'] );
		}
		$band_btn = mm_full_render_button( $cta_band['cta1_text'] ?? '', $cta_band['cta1_link'] ?? '' );
		if ( $band_btn ) {
			$content .= '<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons">' . $band_btn . '</div><!-- /wp:buttons -->';
		}
		$content .= '</div><!-- /wp:group -->';
	}

	return $content;
}

/**
 * About content.
 */
function mm_full_render_about( array $sections ): string {
	if ( empty( $sections ) ) {
		return '';
	}
	$content = '<!-- wp:group {"tagName":"section","className":"mm-about"} --><div class="wp-block-group mm-about">';
	foreach ( $sections as $section ) {
		$content .= '<!-- wp:group {"tagName":"section","className":"mm-about__section"} --><div class="wp-block-group mm-about__section">';
		$content .= mm_full_render_heading( $section['heading'] ?? '', 2 );
		if ( ! empty( $section['body'] ) ) {
			$content .= mm_full_render_paragraph( $section['body'] );
		}
		if ( ! empty( $section['media_ref'] ) ) {
			$content .= mm_full_render_image_block( $section['media_ref'] );
		}
		$content .= '</div><!-- /wp:group -->';
	}
	$content .= '</div><!-- /wp:group -->';
	return $content;
}

/**
 * Guides content.
 */
function mm_full_render_guides( array $sections ): string {
	if ( empty( $sections ) ) {
		return '';
	}
	$content = '<!-- wp:group {"tagName":"section","className":"mm-guides"} --><div class="wp-block-group mm-guides">';
	$content .= '<!-- wp:heading {"level":1,"textAlign":"center"} --><h1 class="has-text-align-center">' . esc_html__( 'Signature Care Guides', 'mm-sphynx' ) . '</h1><!-- /wp:heading -->';
	foreach ( $sections as $section ) {
		$content .= '<!-- wp:group {"className":"mm-guides__section"} --><div class="wp-block-group mm-guides__section">';
		$content .= mm_full_render_heading( $section['title'] ?? '', 2 );
		if ( ! empty( $section['body'] ) ) {
			$content .= mm_full_render_paragraph( $section['body'] );
		}
		if ( ! empty( $section['media_ref'] ) ) {
			$content .= mm_full_render_image_block( $section['media_ref'] );
		}
		$content .= '</div><!-- /wp:group -->';
	}
	$content .= '</div><!-- /wp:group -->';
	return $content;
}

/**
 * FAQ content.
 */
function mm_full_render_faq( array $entries ): string {
	if ( empty( $entries ) ) {
		return '';
	}
	$details = '';
	$schema  = [];
	foreach ( $entries as $entry ) {
		$question = $entry['question'] ?? '';
		$answer   = $entry['answer'] ?? '';
		if ( '' === trim( $question ) || '' === trim( $answer ) ) {
			continue;
		}
		$details .= '<details class="mm-faq__item"><summary>' . mm_full_escape( $question ) . '</summary><p>' . mm_full_escape( $answer ) . '</p></details>';
		if ( ! empty( $entry['schema'] ) ) {
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

	$content = '<!-- wp:group {"tagName":"section","className":"mm-faq"} --><div class="wp-block-group mm-faq">';
	$content .= '<!-- wp:heading {"level":1,"textAlign":"center"} --><h1 class="has-text-align-center">' . esc_html__( 'Frequently Asked Questions', 'mm-sphynx' ) . '</h1><!-- /wp:heading -->';
	if ( $details ) {
		$content .= '<!-- wp:html -->' . $details . '<!-- /wp:html -->';
	}
	if ( $schema ) {
		$content .= '<!-- wp:html --><script type="application/ld+json">' . wp_json_encode(
			[
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => $schema,
			],
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		) . '</script><!-- /wp:html -->';
	}
	$content .= '</div><!-- /wp:group -->';
	return $content;
}

/**
 * Achievements content.
 */
function mm_full_render_achievements( array $rows ): string {
	if ( empty( $rows ) ) {
		return '';
	}
	usort(
		$rows,
		static function ( $a, $b ) {
			return strcmp( $a['year'] ?? '', $b['year'] ?? '' );
		}
	);

	$content = '<!-- wp:group {"tagName":"section","className":"mm-achievements"} --><div class="wp-block-group mm-achievements">';
	$content .= '<!-- wp:heading {"level":1,"textAlign":"center"} --><h1 class="has-text-align-center">' . esc_html__( 'Achievements & Awards', 'mm-sphynx' ) . '</h1><!-- /wp:heading -->';

	foreach ( $rows as $entry ) {
		$title   = $entry['title_en'] ?? '';
		$year    = $entry['year'] ?? '';
		$anchor  = mm_full_escape( strtolower( str_replace( ' ', '-', 'year-' . $year ) ) );
		$content .= '<!-- wp:group {"className":"mm-achievements__entry","anchor":"' . $anchor . '"} --><div class="wp-block-group mm-achievements__entry" id="' . $anchor . '">';
		$content .= mm_full_render_heading( trim( $year . ' · ' . $title ), 2 );
		if ( ! empty( $entry['media_ref'] ) ) {
			$content .= mm_full_render_image_block( $entry['media_ref'] );
		}
		if ( ! empty( $entry['description_en'] ) ) {
			$content .= mm_full_render_paragraph( $entry['description_en'] );
		}
		if ( ! empty( $entry['judge_quote_en'] ) ) {
			$content .= '<!-- wp:quote --><blockquote class="wp-block-quote"><p>' . mm_full_escape( $entry['judge_quote_en'] ) . '</p></blockquote><!-- /wp:quote -->';
		}
		if ( ! empty( $entry['doc_ref'] ) ) {
			$content .= '<!-- wp:paragraph {"className":"mm-achievements__doc"} --><p class="mm-achievements__doc"><a href="' . esc_url( $entry['doc_ref'] ) . '" target="_blank" rel="noopener">' . esc_html__( 'Download official scorecard', 'mm-sphynx' ) . '</a></p><!-- /wp:paragraph -->';
		}
		$content .= '</div><!-- /wp:group -->';
	}
	$content .= '</div><!-- /wp:group -->';
	return $content;
}

/**
 * Gallery content.
 */
function mm_full_render_gallery( array $gallery ): string {
	$albums = $gallery['albums'] ?? [];
	$items  = $gallery['items'] ?? [];
	if ( empty( $albums ) ) {
		return '';
	}
	$items_by_album = [];
	foreach ( $items as $item ) {
		$key = $item['album_key'] ?? '';
		if ( '' === $key ) {
			continue;
		}
		$items_by_album[ $key ][] = $item;
	}
	$content = '<!-- wp:group {"tagName":"section","className":"mm-gallery"} --><div class="wp-block-group mm-gallery">';
	$content .= '<!-- wp:heading {"level":1,"textAlign":"center"} --><h1 class="has-text-align-center">' . esc_html__( 'Gallery Collections', 'mm-sphynx' ) . '</h1><!-- /wp:heading -->';
	foreach ( $albums as $album ) {
		$key         = $album['album_key'] ?? '';
		$album_items = $items_by_album[ $key ] ?? [];
		$content    .= '<!-- wp:group {"className":"mm-gallery__album"} --><div class="wp-block-group mm-gallery__album">';
		$content    .= mm_full_render_heading( $album['album_title_en'] ?? '', 2 );
		if ( ! empty( $album['description_en'] ) ) {
			$content .= mm_full_render_paragraph( $album['description_en'] );
		}
		if ( ! empty( $album['cover'] ) ) {
			$content .= mm_full_render_image_block( $album['cover'] );
		}
		if ( $album_items ) {
			$figures = '';
			foreach ( $album_items as $media ) {
				$caption = $media['caption_en'] ?? '';
				$img     = mm_full_placeholder_img( $media['media_ref'] ?? '' );
				$figures .= '<figure>' . $img;
				if ( $caption ) {
					$figures .= '<figcaption>' . mm_full_escape( $caption ) . '</figcaption>';
				}
				$figures .= '</figure>';
			}
			if ( $figures ) {
				$content .= '<!-- wp:html --><div class="mm-gallery__grid">' . $figures . '</div><!-- /wp:html -->';
			}
		}
		$content .= '</div><!-- /wp:group -->';
	}
	$content .= '</div><!-- /wp:group -->';
	return $content;
}

/**
 * Policies/terms/privacy rendering.
 */
function mm_full_render_policy_page( array $sections, string $title ): string {
	if ( empty( $sections ) ) {
		return '';
	}
	$content = '<!-- wp:group {"tagName":"section","className":"mm-policy-page"} --><div class="wp-block-group mm-policy-page">';
	$content .= '<!-- wp:heading {"level":1,"textAlign":"center"} --><h1 class="has-text-align-center">' . mm_full_escape( $title ) . '</h1><!-- /wp:heading -->';
	foreach ( $sections as $section ) {
		$content .= '<!-- wp:group {"className":"mm-policy-page__section"} --><div class="wp-block-group mm-policy-page__section">';
		$content .= mm_full_render_heading( $section['section_title_en'] ?? '', 2 );
		if ( ! empty( $section['body_en'] ) ) {
			$content .= mm_full_render_paragraph( $section['body_en'] );
		}
		$content .= '</div><!-- /wp:group -->';
	}
	$content .= '</div><!-- /wp:group -->';
	return $content;
}

/**
 * Contact content.
 */
function mm_full_render_contact( array $entries ): string {
	if ( empty( $entries ) ) {
		return '';
	}
	$items = '';
	foreach ( $entries as $entry ) {
		$channel = $entry['channel'] ?? '';
		$value   = $entry['value'] ?? '';
		$link    = $entry['link'] ?? '';
		if ( '' === $channel || '' === $value ) {
			continue;
		}
		if ( 'email' === strtolower( $channel ) ) {
			$href = strpos( $link, '@' ) !== false ? 'mailto:' . $link : $link;
			$items .= '<li><strong>' . mm_full_escape( ucfirst( $channel ) ) . ':</strong> <a href="' . esc_url( $href ) . '">' . mm_full_escape( $value ) . '</a></li>';
		} else {
			$items .= '<li><strong>' . mm_full_escape( ucfirst( $channel ) ) . ':</strong> <a href="' . esc_url( $link ) . '" target="_blank" rel="noopener">' . mm_full_escape( $value ) . '</a></li>';
		}
	}
	$content = '<!-- wp:group {"tagName":"section","className":"mm-contact"} --><div class="wp-block-group mm-contact">';
	$content .= '<!-- wp:heading {"level":1,"textAlign":"center"} --><h1 class="has-text-align-center">' . esc_html__( 'Contact Miss Mermaid', 'mm-sphynx' ) . '</h1><!-- /wp:heading -->';
	$content .= '<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">' . esc_html__( 'Reach our guardianship concierge for adoption inquiries, collaborations, or alumni updates.', 'mm-sphynx' ) . '</p><!-- /wp:paragraph -->';
	if ( $items ) {
		$content .= '<!-- wp:html --><ul class="mm-contact__list">' . $items . '</ul><!-- /wp:html -->';
	}
	$content .= '</div><!-- /wp:group -->';
	return $content;
}

/**
 * Blog post content builder.
 */
function mm_full_render_blog_post_content( array $entry ): string {
	$sections = $entry['sections'] ?? [];
	$body     = '<!-- wp:group {"tagName":"section","className":"mm-blog-post"} --><div class="wp-block-group mm-blog-post">';
	if ( ! empty( $entry['hero_ref'] ) ) {
		$body .= mm_full_render_image_block( $entry['hero_ref'] );
	}
	if ( ! empty( $entry['excerpt_en'] ) ) {
		$body .= '<!-- wp:paragraph {"fontSize":"large"} --><p class="has-large-font-size">' . mm_full_escape( $entry['excerpt_en'] ) . '</p><!-- /wp:paragraph -->';
	}
	foreach ( $sections as $section ) {
		if ( ! empty( $section['title'] ) ) {
			$body .= mm_full_render_heading( $section['title'], 2 );
		}
		if ( ! empty( $section['body'] ) ) {
			$body .= mm_full_render_paragraph( $section['body'] );
		}
	}
	$body .= '<!-- wp:paragraph --><p>' . esc_html__( 'Continue exploring: visit our', 'mm-sphynx' ) . ' <a href="' . esc_url( home_url( '/achievements/' ) ) . '">' . esc_html__( 'Achievements', 'mm-sphynx' ) . '</a>, <a href="' . esc_url( home_url( '/guides/' ) ) . '">' . esc_html__( 'Care Guides', 'mm-sphynx' ) . '</a>, ' . esc_html__( 'and', 'mm-sphynx' ) . ' <a href="' . esc_url( home_url( '/kittens/' ) ) . '">' . esc_html__( 'Available Kittens', 'mm-sphynx' ) . '</a>.</p><!-- /wp:paragraph -->';
	if ( ! empty( $entry['cta_text'] ) && ! empty( $entry['cta_link'] ) ) {
		$body .= '<!-- wp:buttons {"layout":{"type":"flex"}} --><div class="wp-block-buttons">' . mm_full_render_button( $entry['cta_text'], $entry['cta_link'] ) . '</div><!-- /wp:buttons -->';
	}
	$body .= '</div><!-- /wp:group -->';
	return $body;
}

/**
 * Navigation update.
 */
function mm_full_update_navigation( array $nav_data ): void {
	$nav_posts = get_posts(
		[
			'post_type'      => 'wp_navigation',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		]
	);

	$map = [];
	foreach ( $nav_posts as $nav_post ) {
		$map[ strtolower( $nav_post->post_title ) ] = $nav_post;
	}

	foreach ( [ 'primary', 'footer' ] as $menu_key ) {
		if ( empty( $nav_data[ $menu_key ] ) ) {
			continue;
		}
		$post = $map[ $menu_key ] ?? null;
		if ( ! $post ) {
			continue;
		}
		$content = '';
		foreach ( $nav_data[ $menu_key ] as $item ) {
			$label = $item['label'] ?? '';
			$link  = $item['link'] ?? '';
			if ( '' === trim( $label ) || '' === trim( $link ) ) {
				continue;
			}
			$url = home_url( '/' ) === home_url( $link ) ? home_url( '/' ) : home_url( ltrim( $link, '/' ) );
			if ( 0 === strpos( $link, 'http' ) ) {
				$url = $link;
			} elseif ( 0 === strpos( $link, '/' ) ) {
				$url = home_url( $link );
			}
			$content .= '<!-- wp:navigation-link {"label":"' . mm_full_escape( $label ) . '","url":"' . esc_url( $url ) . '","kind":"custom","isTopLevelLink":true} /-->' . "\n";
		}
		wp_update_post(
			[
				'ID'           => $post->ID,
				'post_content' => trim( $content ),
			]
		);
	}
}

/**
 * SEO helper.
 */
function mm_full_apply_seo_meta( array $entries, array &$summary ): void {
	if ( empty( $entries ) ) {
		return;
	}
	foreach ( $entries as $entry ) {
		$route = $entry['route'] ?? '';
		if ( '' === trim( $route ) ) {
			continue;
		}
		$target_id = null;
		if ( '/' === $route ) {
			$target_id = (int) get_option( 'page_on_front' );
		} else {
			$path = ltrim( $route, '/' );
			$path = untrailingslashit( $path );
			$page = get_page_by_path( $path );
			if ( $page instanceof WP_Post ) {
				$target_id = (int) $page->ID;
			} else {
				$post = get_page_by_path( $path, OBJECT, 'post' );
				if ( $post instanceof WP_Post ) {
					$target_id = (int) $post->ID;
				}
			}
		}
		if ( ! $target_id ) {
			continue;
		}
		update_post_meta( $target_id, 'rank_math_title', $entry['title_en'] ?? '' );
		update_post_meta( $target_id, 'rank_math_description', $entry['meta_description_en'] ?? '' );
		$focus = array_filter(
			array_map(
				'trim',
				[
					$entry['primary_keyword'] ?? '',
					$entry['secondary_keywords'] ?? '',
				]
			)
		);
		if ( $focus ) {
			update_post_meta( $target_id, 'rank_math_focus_keyword', implode( ', ', $focus ) );
		}
		if ( ! empty( $entry['og_image'] ) ) {
			$url = 0 === strpos( $entry['og_image'], 'http' ) ? $entry['og_image'] : home_url( $entry['og_image'] );
			update_post_meta( $target_id, 'rank_math_facebook_image', $url );
		}
		$summary['seo_entries']++;
	}
}

/**
 * Utility to parse delimited strings.
 */
function mm_full_parse_list( string $value ): array {
	$parts = preg_split( '/[,;]+/', $value );
	if ( ! is_array( $parts ) ) {
		return [];
	}
	return array_values(
		array_filter(
			array_map(
				static function ( $item ) {
					return trim( (string) $item );
				},
				$parts
			),
			static function ( $item ) {
				return '' !== $item;
			}
		)
	);
}

/**
 * Kitten upsert.
 */
function mm_full_upsert_kitten( array $kitten, array $detail_map, array &$summary ): void {
	$kitten_id = $kitten['kitten_id'] ?? '';
	if ( '' === $kitten_id ) {
		return;
}
	$existing = get_posts(
		[
			'post_type'      => 'kitten',
			'post_status'    => 'any',
			'meta_key'       => 'kitten_id',
			'meta_value'     => $kitten_id,
			'posts_per_page' => 1,
		]
	);

	if ( $existing ) {
		$post_id = (int) $existing[0]->ID;
	} else {
		$post_id = wp_insert_post(
			[
				'post_type'   => 'kitten',
				'post_status' => 'publish',
				'post_title'  => $kitten_id,
				'post_name'   => sanitize_title( $kitten_id ),
			],
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return;
		}
	}

	update_field( 'kitten_id', $kitten_id, $post_id );
	update_field( 'status', $kitten['status'] ?? 'available', $post_id );
	if ( isset( $kitten['price'] ) && '' !== $kitten['price'] ) {
		update_field( 'price', (float) $kitten['price'], $post_id );
	} else {
		update_field( 'price', null, $post_id );
	}
	update_field( 'sex', $kitten['sex'] ?? '', $post_id );
	update_field( 'color', $kitten['color'] ?? '', $post_id );
	update_field( 'birthday', $kitten['birthday'] ?? '', $post_id );
	update_field( 'age_hint', $kitten['age_hint'] ?? '', $post_id );
	update_field( 'temperament_tags', $kitten['temperament_tags'] ?? '', $post_id );
	update_field( 'short_description', $kitten['short_desc_en'] ?? '', $post_id );
	update_field( 'cover_image', $kitten['thumbnail'] ?? '', $post_id );

	$gallery_rows = [];
	foreach ( mm_full_parse_list( $kitten['gallery_refs'] ?? '' ) as $url ) {
		$gallery_rows[] = [ 'image_url' => $url ];
	}
	update_field( 'gallery', $gallery_rows, $post_id );

	$detail = $detail_map[ $kitten_id ] ?? [];

	$video_rows = [];
	foreach ( mm_full_parse_list( $detail['video_refs'] ?? '' ) as $vid ) {
		$video_rows[] = [ 'video_url' => $vid ];
	}
	update_field( 'videos', $video_rows, $post_id );
	update_field( 'parent_sire', $detail['parents_sire'] ?? '', $post_id );
	update_field( 'parent_dam', $detail['parents_dam'] ?? '', $post_id );
	update_field( 'health_notes', $detail['health_notes_en'] ?? '', $post_id );
	update_field( 'value_points', $detail['value_points_en'] ?? '', $post_id );
	update_field( 'care_profile', $detail['care_profile_en'] ?? '', $post_id );

	$apply_text = $detail['apply_button_text'] ?? '';
	$apply_url  = $detail['apply_target'] ?? '';
	update_field( 'apply_text', $apply_text ?: __( 'Apply for this kitten', 'mm-sphynx' ), $post_id );
	if ( '' === trim( $apply_url ) ) {
		$apply_url = add_query_arg( 'kitten', rawurlencode( $kitten_id ), home_url( '/apply' ) );
	}
	update_field( 'apply_url', $apply_url, $post_id );

	$summary['kittens']++;
}

function mm_full_upsert_litter( array $litter, array &$summary ): void {
	$code = $litter['litter_code'] ?? '';
	if ( '' === trim( $code ) ) {
		return;
}
	$existing = get_posts(
		[
			'post_type'      => 'litter',
			'post_status'    => 'any',
			'meta_key'       => 'litter_id',
			'meta_value'     => $code,
			'posts_per_page' => 1,
		]
	);
	if ( $existing ) {
		$post_id = (int) $existing[0]->ID;
	} else {
		$post_id = wp_insert_post(
			[
				'post_type'   => 'litter',
				'post_status' => 'publish',
				'post_title'  => $code,
				'post_name'   => sanitize_title( $code ),
			],
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return;
		}
	}

	update_field( 'litter_id', $code, $post_id );
	update_field( 'queen', $litter['dam'] ?? '', $post_id );
	update_field( 'sire', $litter['sire'] ?? '', $post_id );
	update_field( 'due_window', $litter['due_window'] ?? '', $post_id );
	update_field( 'expected_colors', $litter['expected_colors'] ?? '', $post_id );
	update_field( 'slots_total', (int) ( $litter['slots_total'] ?? 0 ), $post_id );
	update_field( 'policy_highlight', $litter['policy_highlight_en'] ?? '', $post_id );
	update_field( 'join_text', $litter['join_cta_text'] ?? '', $post_id );
	update_field( 'join_url', $litter['join_target'] ?? '', $post_id );

	$summary['litters']++;
}

/**
 * Page content ingestion.
 */
$page_map = [
	'home'        => [ 'slug' => 'home', 'title' => 'Home', 'builder' => 'mm_full_render_home', 'data_key' => 'home' ],
	'about'       => [ 'slug' => 'about', 'title' => 'About Miss Mermaid', 'builder' => 'mm_full_render_about', 'data_key' => 'about' ],
	'guides'      => [ 'slug' => 'guides', 'title' => 'Care Guides', 'builder' => 'mm_full_render_guides', 'data_key' => 'guides' ],
	'faq'         => [ 'slug' => 'faq', 'title' => 'FAQ', 'builder' => 'mm_full_render_faq', 'data_key' => 'faq' ],
	'achievements'=> [ 'slug' => 'achievements', 'title' => 'Achievements & Awards', 'builder' => 'mm_full_render_achievements', 'data_key' => 'achievements' ],
	'gallery'     => [ 'slug' => 'gallery', 'title' => 'Gallery', 'builder' => 'mm_full_render_gallery', 'data_key' => 'gallery' ],
	'policies'    => [ 'slug' => 'policies', 'title' => 'Adoption Policies', 'builder' => 'mm_full_render_policy_page', 'data_key' => 'policies', 'extra_title' => 'Adoption Policies' ],
	'terms'       => [ 'slug' => 'terms', 'title' => 'Terms of Use', 'builder' => 'mm_full_render_policy_page', 'data_key' => 'terms', 'extra_title' => 'Terms of Use' ],
	'privacy'     => [ 'slug' => 'privacy', 'title' => 'Privacy Notice', 'builder' => 'mm_full_render_policy_page', 'data_key' => 'privacy', 'extra_title' => 'Privacy Notice' ],
	'contact'     => [ 'slug' => 'contact', 'title' => 'Contact', 'builder' => 'mm_full_render_contact', 'data_key' => 'contact' ],
];

foreach ( $page_map as $key => $config ) {
	$data_key = $config['data_key'];
	if ( ! isset( $payload[ $data_key ] ) ) {
		continue;
	}
	$builder = $config['builder'];
	if ( ! function_exists( $builder ) ) {
		continue;
	}
	$page_id = mm_full_get_or_create_page( $config['slug'], $config['title'] );
	$sections_snapshot = 0;
	if ( 'gallery' === $data_key ) {
		$sections_snapshot = count( $payload['gallery']['albums'] ?? [] );
	} elseif ( is_array( $payload[ $data_key ] ) ) {
		$sections_snapshot = count( $payload[ $data_key ] );
	}
	$summary['sections_count'] += $sections_snapshot;

	if ( isset( $config['extra_title'] ) ) {
		$content = call_user_func( $builder, $payload[ $data_key ], $config['extra_title'] );
	} else {
		$content = call_user_func( $builder, $payload[ $data_key ] );
	}
	if ( '' !== $content ) {
		wp_update_post(
			[
				'ID'           => $page_id,
				'post_content' => $content,
			]
		);
		$summary['pages_updated'][] = $config['slug'];
	}
	if ( 'home' === $key ) {
		update_option( 'page_on_front', $page_id );
		update_option( 'show_on_front', 'page' );
	}
}

/**
 * Blog ingestion.
 */
if ( ! empty( $payload['blog'] ) ) {
	foreach ( $payload['blog'] as $entry ) {
		$slug = $entry['slug'] ?? '';
		$title = $entry['title_en'] ?? '';
		if ( '' === trim( $slug ) || '' === trim( $title ) ) {
			continue;
		}
		$existing = get_page_by_path( $slug, OBJECT, 'post' );
		$postarr  = [
			'post_type'   => 'post',
			'post_status' => 'publish',
			'post_title'  => $title,
			'post_name'   => $slug,
			'post_excerpt'=> $entry['excerpt_en'] ?? '',
			'post_content'=> mm_full_render_blog_post_content( $entry ),
		];
		if ( $existing instanceof WP_Post ) {
			$postarr['ID'] = $existing->ID;
			$post_id       = wp_update_post( $postarr, true );
		} else {
			$post_id = wp_insert_post( $postarr, true );
		}
		if ( ! is_wp_error( $post_id ) ) {
			$summary['blog_posts']++;
		}
	}
}

/**
 * Kittens & litters.
 */
$kitten_details = $payload['kitten_details'] ?? [];
if ( ! empty( $payload['kittens'] ) ) {
	foreach ( $payload['kittens'] as $kitten ) {
		mm_full_upsert_kitten( $kitten, $kitten_details, $summary );
	}
}

if ( ! empty( $payload['litters'] ) ) {
	foreach ( $payload['litters'] as $litter ) {
		mm_full_upsert_litter( $litter, $summary );
	}
}

/**
 * Adoption flow option.
 */
if ( ! empty( $payload['adoption_flow'] ) && is_array( $payload['adoption_flow'] ) ) {
	update_option( 'mm_sphynx_adoption_flow', array_values( $payload['adoption_flow'] ) );
}

/**
 * Navigation.
 */
if ( ! empty( $payload['navigation'] ) ) {
	mm_full_update_navigation( $payload['navigation'] );
}

/**
 * SEO metadata.
 */
if ( ! empty( $payload['seo'] ) ) {
	mm_full_apply_seo_meta( $payload['seo'], $summary );
}

echo wp_json_encode(
	[
		'pages_updated'   => array_unique( $summary['pages_updated'] ),
		'sections_count'  => $summary['sections_count'],
		'blog_posts'      => $summary['blog_posts'],
		'kittens'         => $summary['kittens'],
		'litters'         => $summary['litters'],
		'seo_entries'     => $summary['seo_entries'],
	],
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);
