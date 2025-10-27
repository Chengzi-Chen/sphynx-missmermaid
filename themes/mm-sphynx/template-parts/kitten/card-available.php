<?php
/**
 * Card for available kitten.
 *
 * @var array $args Template arguments.
 */

$kitten    = mm_arr_get( $args, 'kitten', [] );
$post_id   = (int) mm_arr_get( $kitten, 'id', 0 );
$kitten_id = trim( (string) mm_arr_get( $kitten, 'kitten_id', '' ) );
$title     = trim( (string) mm_arr_get( $kitten, 'title', $kitten_id ) );

$image = mm_arr_get( $kitten, 'cover_image', '' );

if ( ! $image && $post_id ) {
    $image = get_the_post_thumbnail_url( $post_id, 'mm-card' );
}

if ( ! $image ) {
    $image = mm_sphynx_placeholder_image();
}

$sex = mm_arr_get( $kitten, 'sex', '' );

$colors = mm_arr_get( $kitten, 'color', [] );
if ( ! is_array( $colors ) ) {
    $colors = mm_sphynx_parse_text_list( (string) $colors );
}

$age_hint    = mm_arr_get( $kitten, 'age_hint', '' );
$short_desc  = mm_arr_get( $kitten, 'short_description', '' );
$status      = mm_arr_get( $kitten, 'status', 'available' );
$status_label = mm_arr_get( $kitten, 'status_label', ucfirst( $status ) );
$temperament = mm_arr_get( $kitten, 'temperament', [] );
if ( ! is_array( $temperament ) ) {
    $temperament = mm_sphynx_parse_text_list( (string) $temperament );
}

$apply_url  = mm_arr_get( $kitten, 'apply_url', home_url( '/apply' ) );
$price      = mm_arr_get( $kitten, 'price', null );
$permalink  = mm_arr_get( $kitten, 'permalink', $post_id ? get_permalink( $post_id ) : '' );
$display_id = $kitten_id ?: $title;

$meta_parts = array_filter(
    [
        $sex ? ucfirst( $sex ) : '',
        $colors ? implode( ', ', array_map( static function ( $color ) {
            return ucwords( str_replace( '-', ' ', trim( (string) $color ) ) );
        }, $colors ) ) : '',
        $age_hint,
    ]
);

$price_text = __( 'Contact for pricing', 'mm-sphynx' );
if ( null !== $price && '' !== $price ) {
    $price_text = sprintf( '$%s', number_format_i18n( (float) $price, 0 ) );
}

$tag_items = array_slice( array_map( static function ( $tag ) {
    return ucwords( str_replace( '-', ' ', trim( (string) $tag ) ) );
}, $temperament ), 0, 3 );

$button_id = $kitten_id ?: ( $post_id ? 'kitten-' . $post_id : uniqid( 'kitten', false ) );
?>

<article class="mm-kitten-card" data-kitten-card data-kitten-id="<?php echo esc_attr( $kitten_id ?: (string) $post_id ); ?>" data-kitten-status="<?php echo esc_attr( $status ); ?>">
    <figure class="mm-kitten-card__media">
        <a href="<?php echo esc_url( $permalink ); ?>" class="mm-kitten-card__link" aria-label="<?php echo esc_attr( $title ?: $display_id ); ?>">
            <img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ?: $display_id ); ?>" loading="lazy" decoding="async" class="mm-kitten-card__image skip-lazy" data-no-lazy="1" width="640" height="480" />
        </a>
    </figure>
    <div class="mm-kitten-card__body">
        <div class="mm-kitten-card__header">
            <h3 class="mm-kitten-card__title"><?php echo esc_html( $display_id ); ?></h3>
            <p class="mm-kitten-card__status"><?php echo esc_html( strtoupper( $status_label ) ); ?></p>
        </div>
        <?php if ( $meta_parts ) : ?>
            <p class="mm-kitten-card__meta"><?php echo esc_html( implode( ' • ', $meta_parts ) ); ?></p>
        <?php endif; ?>

        <p class="mm-kitten-card__price"><?php echo esc_html( $price_text ); ?></p>

        <?php if ( $short_desc ) : ?>
            <p class="mm-kitten-card__excerpt"><?php echo esc_html( $short_desc ); ?></p>
        <?php endif; ?>

        <?php if ( $tag_items ) : ?>
            <ul class="mm-kitten-card__tags">
                <?php foreach ( $tag_items as $tag ) : ?>
                    <li><?php echo esc_html( $tag ); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="mm-kitten-card__actions">
            <button class="mm-button mm-button--ghost" type="button" data-kitten-open data-kitten-target="<?php echo esc_attr( $kitten_id ?: (string) $post_id ); ?>" data-kitten-button-id="<?php echo esc_attr( $button_id ); ?>"><?php esc_html_e( 'VIEW DETAILS', 'mm-sphynx' ); ?></button>
            <a class="mm-button mm-button--primary" href="<?php echo esc_url( $apply_url ); ?>"><?php esc_html_e( 'APPLY', 'mm-sphynx' ); ?></a>
        </div>
    </div>
</article>
