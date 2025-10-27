<?php
/**
 * Update existing kitten posts to align with v2.1 content matrix.
 *
 * Usage:
 *   wp eval-file /opt/sphynx-scripts/update_kittens_v21.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$payload_path = '/opt/sphynx-content/mm_full_payload_v2.1.json';
$names_path   = '/opt/sphynx-content/kitten_name_map_v2.1.json';

if ( ! file_exists( $payload_path ) || ! file_exists( $names_path ) ) {
	return;
}

$payload = json_decode( file_get_contents( $payload_path ), true );
$name_map = json_decode( file_get_contents( $names_path ), true );

if ( empty( $payload['kittens'] ) || ! is_array( $payload['kittens'] ) ) {
	return;
}

$entries = array_values( $payload['kittens'] );
$posts   = get_posts(
	[
		'post_type'   => 'kitten',
		'post_status' => [ 'publish', 'draft' ],
		'numberposts' => count( $entries ),
		'orderby'     => 'ID',
		'order'       => 'ASC',
	]
);

foreach ( $entries as $index => $kitten ) {
	$post = $posts[ $index ] ?? null;
	if ( ! $post ) {
		continue;
	}

	$kitten_id = $kitten['kitten_id'] ?? '';
	if ( '' === $kitten_id ) {
		continue;
	}

	$name       = $name_map[ $kitten_id ] ?? '';
	$post_title = $name ? sprintf( '%s — %s', $name, $kitten_id ) : $kitten_id;

	wp_update_post(
		[
			'ID'         => $post->ID,
			'post_title' => $post_title,
			'post_name'  => sanitize_title( 'kitten-' . $kitten_id ),
			'post_status'=> 'publish',
		]
	);

	update_field( 'kitten_id', $kitten_id, $post->ID );

	if ( isset( $kitten['status'] ) ) {
		update_field( 'status', $kitten['status'], $post->ID );
	}

	if ( isset( $kitten['price'] ) && '' !== $kitten['price'] ) {
		update_field( 'price', (float) $kitten['price'], $post->ID );
	}

	if ( ! empty( $kitten['sex'] ) ) {
		update_field( 'sex', $kitten['sex'], $post->ID );
	}

	if ( ! empty( $kitten['color'] ) ) {
		update_field( 'color', $kitten['color'], $post->ID );
	}

	if ( ! empty( $kitten['age_hint'] ) ) {
		update_field( 'age_hint', $kitten['age_hint'], $post->ID );
	}

	if ( ! empty( $kitten['temperament_tags'] ) ) {
		update_field( 'temperament_tags', $kitten['temperament_tags'], $post->ID );
	}

	if ( ! empty( $kitten['short_desc_en'] ) ) {
		update_field( 'short_description', $kitten['short_desc_en'], $post->ID );
		wp_update_post(
			[
				'ID'           => $post->ID,
				'post_excerpt' => $kitten['short_desc_en'],
			]
		);
	}

	if ( ! empty( $kitten['thumbnail'] ) ) {
		update_field( 'cover_image', $kitten['thumbnail'], $post->ID );
	}

	$gallery_rows = [];
	if ( ! empty( $kitten['gallery_refs'] ) ) {
		foreach ( preg_split( '/[,;\\s]+/', (string) $kitten['gallery_refs'] ) as $ref ) {
			$ref = trim( $ref );
			if ( '' !== $ref ) {
				if ( 0 !== strpos( $ref, 'http' ) && 0 !== strpos( $ref, '/' ) ) {
					$ref = '/' . ltrim( $ref, '/' );
				}
				$gallery_rows[] = [ 'image_url' => $ref ];
			}
		}
	}

	if ( $gallery_rows ) {
		update_field( 'gallery', $gallery_rows, $post->ID );
	}

	$apply_url = add_query_arg( 'kitten', rawurlencode( $kitten_id ), home_url( '/apply' ) );
	update_field( 'apply_url', $apply_url, $post->ID );
	update_field( 'apply_text', __( 'Apply for this kitten', 'mm-sphynx' ), $post->ID );
}

echo 'Updated ' . count( $entries ) . ' kitten records.' . PHP_EOL;
