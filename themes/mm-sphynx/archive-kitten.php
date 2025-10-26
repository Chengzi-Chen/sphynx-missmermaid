<?php
/**
 * Archive template for Kitten listings.
 *
 * @package mm-sphynx
 */

get_header();

$dataset          = mm_sphynx_get_kitten_dataset();
$available_kittens = $dataset['available'] ?? [];
$reserve_kittens   = $dataset['reserve'] ?? [];
$litters           = mm_sphynx_get_litter_timeline();

$waitlist_open = (bool) array_filter(
    $litters,
    static function ( $litter ) {
        return in_array( $litter['status'], [ 'confirmed', 'born' ], true );
    }
);

?>

<main class="mm-kittens-archive" id="primary">
    <header class="mm-kittens-hero">
        <h1><?php esc_html_e( 'Our Kittens', 'mm-sphynx' ); ?></h1>
        <p><?php esc_html_e( 'Explore available companions and reserve upcoming Miss Mermaid Sphynx kittens.', 'mm-sphynx' ); ?></p>
    </header>

    <section class="mm-kittens-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Kitten availability tabs', 'mm-sphynx' ); ?>">
        <button class="mm-kittens-tab is-active" role="tab" aria-selected="true" aria-controls="mm-tab-available" id="mm-tab-btn-available" data-tab-target="available">
            <?php esc_html_e( 'Available Now', 'mm-sphynx' ); ?>
        </button>
        <button class="mm-kittens-tab" role="tab" aria-selected="false" aria-controls="mm-tab-reserve" id="mm-tab-btn-reserve" data-tab-target="reserve">
            <?php esc_html_e( 'Reserve & Waitlist', 'mm-sphynx' ); ?>
        </button>
    </section>

    <section id="mm-tab-available" class="mm-kittens-grid is-active" role="tabpanel" aria-labelledby="mm-tab-btn-available">
        <?php if ( ! empty( $available_kittens ) ) : ?>
            <?php foreach ( $available_kittens as $kitten ) : ?>
                <article class="mm-kitten-card" data-kitten-card data-kitten-id="<?php echo esc_attr( $kitten['id'] ); ?>">
                    <figure class="mm-kitten-card__media">
                        <?php if ( $kitten['thumbnail'] ) : ?>
                            <img src="<?php echo esc_url( $kitten['thumbnail'] ); ?>" alt="" loading="lazy" />
                        <?php elseif ( ! empty( $kitten['gallery'] ) ) : ?>
                            <img src="<?php echo esc_url( $kitten['gallery'][0] ); ?>" alt="" loading="lazy" />
                        <?php else : ?>
                            <div class="mm-kitten-card__placeholder" aria-hidden="true">🐾</div>
                        <?php endif; ?>
                    </figure>
                    <div class="mm-kitten-card__body">
                        <h2 class="mm-kitten-card__title"><?php echo esc_html( $kitten['kitten_id'] ?: $kitten['title'] ); ?></h2>
                        <p class="mm-kitten-card__meta">
                            <?php
                            $meta_parts = array_filter(
                                [
                                    $kitten['sex'] ? ucfirst( $kitten['sex'] ) : '',
                                    ! empty( $kitten['color'] ) ? implode( ', ', array_map( 'ucfirst', $kitten['color'] ) ) : '',
                                    $kitten['age'],
                                ]
                            );
                            echo esc_html( implode( ' • ', $meta_parts ) );
                            ?>
                        </p>
                        <p class="mm-kitten-card__price">
                            <?php
                            if ( null !== $kitten['price'] ) {
                                printf(
                                    /* translators: %s price */
                                    esc_html__( '$%s', 'mm-sphynx' ),
                                    esc_html( number_format_i18n( $kitten['price'], 0 ) )
                                );
                            } else {
                                esc_html_e( 'Contact for pricing', 'mm-sphynx' );
                            }
                            ?>
                        </p>
                        <button class="mm-kitten-card__cta" type="button" data-kitten-open>
                            <?php esc_html_e( 'View Details', 'mm-sphynx' ); ?>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else : ?>
            <p class="mm-kittens-empty"><?php esc_html_e( 'No kittens are currently available. Join the waitlist to reserve upcoming companions.', 'mm-sphynx' ); ?></p>
        <?php endif; ?>
    </section>

    <section id="mm-tab-reserve" class="mm-kittens-grid" role="tabpanel" aria-labelledby="mm-tab-btn-reserve" hidden>
        <?php if ( ! empty( $reserve_kittens ) ) : ?>
            <?php foreach ( $reserve_kittens as $kitten ) : ?>
                <article class="mm-kitten-card mm-kitten-card--reserve" data-kitten-card data-kitten-id="<?php echo esc_attr( $kitten['id'] ); ?>">
                    <figure class="mm-kitten-card__media">
                        <?php if ( $kitten['thumbnail'] ) : ?>
                            <img src="<?php echo esc_url( $kitten['thumbnail'] ); ?>" alt="" loading="lazy" />
                        <?php elseif ( ! empty( $kitten['gallery'] ) ) : ?>
                            <img src="<?php echo esc_url( $kitten['gallery'][0] ); ?>" alt="" loading="lazy" />
                        <?php else : ?>
                            <div class="mm-kitten-card__placeholder" aria-hidden="true">🐾</div>
                        <?php endif; ?>
                    </figure>
                    <div class="mm-kitten-card__body">
                        <span class="mm-kitten-card__status badge-status-<?php echo esc_attr( $kitten['status'] ); ?>">
                            <?php echo esc_html( $kitten['status_label'] ); ?>
                        </span>
                        <h2 class="mm-kitten-card__title"><?php echo esc_html( $kitten['kitten_id'] ?: $kitten['title'] ); ?></h2>
                        <p class="mm-kitten-card__meta">
                            <?php
                            $meta_parts = array_filter(
                                [
                                    $kitten['sex'] ? ucfirst( $kitten['sex'] ) : '',
                                    ! empty( $kitten['color'] ) ? implode( ', ', array_map( 'ucfirst', $kitten['color'] ) ) : '',
                                    $kitten['age'],
                                ]
                            );
                            echo esc_html( implode( ' • ', $meta_parts ) );
                            ?>
                        </p>
                        <button class="mm-kitten-card__cta" type="button" data-kitten-open>
                            <?php esc_html_e( 'View Details', 'mm-sphynx' ); ?>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else : ?>
            <p class="mm-kittens-empty"><?php esc_html_e( 'All upcoming kittens are currently matched. Join the waitlist to receive early notifications.', 'mm-sphynx' ); ?></p>
        <?php endif; ?>
    </section>

    <section class="mm-kittens-reserve" id="reserve">
        <header>
            <h2><?php esc_html_e( 'Reserve & Waitlist', 'mm-sphynx' ); ?></h2>
            <p><?php esc_html_e( 'Follow upcoming litters and secure your place on the Miss Mermaid waitlist when reservations open.', 'mm-sphynx' ); ?></p>
        </header>

        <?php if ( ! empty( $litters ) ) : ?>
            <div class="mm-litter-timeline" role="list">
                <?php foreach ( $litters as $litter ) : ?>
                    <article class="mm-litter-card status-<?php echo esc_attr( $litter['status'] ); ?>" role="listitem">
                        <header>
                            <h3><?php echo esc_html( $litter['title'] ); ?></h3>
                            <span class="mm-litter-status"><?php echo esc_html( $litter['status_label'] ); ?></span>
                        </header>
                        <dl class="mm-litter-meta">
                            <?php if ( $litter['queen'] ) : ?>
                                <div>
                                    <dt><?php esc_html_e( 'Queen', 'mm-sphynx' ); ?></dt>
                                    <dd><?php echo esc_html( $litter['queen'] ); ?></dd>
                                </div>
                            <?php endif; ?>
                            <?php if ( $litter['sire'] ) : ?>
                                <div>
                                    <dt><?php esc_html_e( 'Sire', 'mm-sphynx' ); ?></dt>
                                    <dd><?php echo esc_html( $litter['sire'] ); ?></dd>
                                </div>
                            <?php endif; ?>
                            <?php if ( $litter['due_date'] ) : ?>
                                <div>
                                    <dt><?php esc_html_e( 'Estimated', 'mm-sphynx' ); ?></dt>
                                    <dd><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $litter['due_date'] ) ) ); ?></dd>
                                </div>
                            <?php endif; ?>
                            <?php if ( $litter['born_date'] ) : ?>
                                <div>
                                    <dt><?php esc_html_e( 'Born', 'mm-sphynx' ); ?></dt>
                                    <dd><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $litter['born_date'] ) ) ); ?></dd>
                                </div>
                            <?php endif; ?>
                        </dl>
                        <?php if ( $litter['note'] ) : ?>
                            <p class="mm-litter-note"><?php echo esc_html( $litter['note'] ); ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p class="mm-kittens-empty"><?php esc_html_e( 'Upcoming litters will be announced soon. Join the waitlist to stay informed.', 'mm-sphynx' ); ?></p>
        <?php endif; ?>

        <?php if ( $waitlist_open ) : ?>
            <div class="mm-reserve-cta">
                <button type="button" class="mm-waitlist-button" data-waitlist-open>
                    <?php esc_html_e( 'Join Waitlist', 'mm-sphynx' ); ?>
                </button>
            </div>
        <?php endif; ?>
    </section>
</main>

<div class="mm-kitten-modal" id="mm-kitten-modal" role="dialog" aria-modal="true" aria-hidden="true" hidden>
    <div class="mm-kitten-modal__overlay" data-modal-close></div>
    <div class="mm-kitten-modal__content" role="document">
        <button class="mm-kitten-modal__close" type="button" data-modal-close aria-label="<?php esc_attr_e( 'Close modal', 'mm-sphynx' ); ?>">×</button>
        <div class="mm-kitten-modal__media">
            <div class="mm-kitten-modal__gallery" data-modal-gallery></div>
            <div class="mm-kitten-modal__videos" data-modal-videos></div>
        </div>
        <div class="mm-kitten-modal__details">
            <h2 data-modal-title></h2>
            <p class="mm-kitten-modal__meta" data-modal-meta></p>
            <p class="mm-kitten-modal__price" data-modal-price></p>
            <div class="mm-kitten-modal__badges" data-modal-personality></div>
            <dl class="mm-kitten-modal__fields" data-modal-fields></dl>
            <div class="mm-kitten-modal__cta">
                <button type="button" class="mm-button mm-button--primary" data-modal-apply>
                    <?php esc_html_e( 'Apply for This Kitten', 'mm-sphynx' ); ?>
                </button>
                <button type="button" class="mm-button mm-button--secondary" data-modal-waitlist>
                    <?php esc_html_e( 'Join Waitlist', 'mm-sphynx' ); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php
get_footer();
