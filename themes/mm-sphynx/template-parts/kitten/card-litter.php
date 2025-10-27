<?php
/**
 * Card for litter / waitlist entry.
 *
 * @var array $args Template arguments.
 */

$litter          = mm_arr_get( $args, 'litter', [] );
$litter_id       = mm_arr_get( $litter, 'id', 0 );
$title           = mm_arr_get( $litter, 'title', __( 'Upcoming Litter', 'mm-sphynx' ) );
$queen           = mm_arr_get( $litter, 'queen', '' );
$sire            = mm_arr_get( $litter, 'sire', '' );
$due_window      = mm_arr_get( $litter, 'due_window', '' );
$due_date        = mm_arr_get( $litter, 'due_date', '' );
$status_label    = mm_arr_get( $litter, 'status_label', '' );
$expected_colors = mm_arr_get( $litter, 'expected_colors', [] );

if ( ! is_array( $expected_colors ) ) {
    $expected_colors = mm_sphynx_parse_text_list( (string) $expected_colors );
}

$slots_total = mm_arr_get( $litter, 'slots_total', '' );
$join_text   = mm_arr_get( $litter, 'join_text', __( 'Join waitlist', 'mm-sphynx' ) );
$join_url    = mm_arr_get( $litter, 'join_url', home_url( '/waitlist/' ) );
$note        = mm_arr_get( $litter, 'note', '' );

$image = mm_sphynx_placeholder_image();
?>

<article class="mm-litter-card" data-litter-card data-litter-id="<?php echo esc_attr( (string) $litter_id ); ?>">
    <figure class="mm-litter-card__media">
        <img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" decoding="async" class="mm-litter-card__image skip-lazy" data-no-lazy="1" width="640" height="480" />
    </figure>
    <div class="mm-litter-card__body">
        <header class="mm-litter-card__header">
            <h3 class="mm-litter-card__title"><?php echo esc_html( $title ); ?></h3>
            <?php if ( $status_label ) : ?>
                <span class="mm-litter-card__status"><?php echo esc_html( strtoupper( $status_label ) ); ?></span>
            <?php endif; ?>
        </header>

        <dl class="mm-litter-card__specs">
            <?php if ( $queen ) : ?>
                <div>
                    <dt><?php esc_html_e( 'Queen', 'mm-sphynx' ); ?></dt>
                    <dd><?php echo esc_html( $queen ); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ( $sire ) : ?>
                <div>
                    <dt><?php esc_html_e( 'Sire', 'mm-sphynx' ); ?></dt>
                    <dd><?php echo esc_html( $sire ); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ( $due_window || $due_date ) : ?>
                <div>
                    <dt><?php esc_html_e( 'Expected', 'mm-sphynx' ); ?></dt>
                    <dd><?php echo esc_html( $due_window ?: $due_date ); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ( $expected_colors ) : ?>
                <div>
                    <dt><?php esc_html_e( 'Color Palette', 'mm-sphynx' ); ?></dt>
                    <dd><?php echo esc_html( implode( ', ', array_map( static function ( $color ) {
                        return ucwords( str_replace( '-', ' ', trim( (string) $color ) ) );
                    }, $expected_colors ) ) ); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ( $slots_total ) : ?>
                <div>
                    <dt><?php esc_html_e( 'Projected Seats', 'mm-sphynx' ); ?></dt>
                    <dd><?php echo esc_html( (string) $slots_total ); ?></dd>
                </div>
            <?php endif; ?>
        </dl>

        <?php if ( $note ) : ?>
            <p class="mm-litter-card__note"><?php echo esc_html( $note ); ?></p>
        <?php endif; ?>

        <div class="mm-litter-card__actions">
            <a class="mm-button mm-button--primary" href="<?php echo esc_url( $join_url ); ?>"><?php echo esc_html( strtoupper( $join_text ) ); ?></a>
        </div>
    </div>
</article>
