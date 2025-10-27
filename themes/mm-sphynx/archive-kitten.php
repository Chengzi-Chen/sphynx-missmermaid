<?php
/**
 * Archive template for kitten listings.
 *
 * @package mm-sphynx
 */

get_header();

$dataset            = mm_sphynx_get_kitten_dataset();
$available_kittens  = mm_arr_get( $dataset, 'available', [] );
$litters            = mm_sphynx_get_litter_timeline();
$adoption_flow      = mm_sphynx_get_adoption_flow();
$waitlist_permalink = home_url( '/waitlist/' );

?>

<main id="primary" class="mm-kittens-archive mm-container">
    <header class="mm-kittens-hero">
        <p class="mm-kittens-hero__eyebrow"><?php esc_html_e( 'Miss Mermaid · Sphynx', 'mm-sphynx' ); ?></p>
        <h1><?php esc_html_e( 'Our Kittens', 'mm-sphynx' ); ?></h1>
        <p><?php esc_html_e( 'Meet the companions currently ready for adoption and preview upcoming litters entering the reservation queue.', 'mm-sphynx' ); ?></p>
    </header>

    <section class="mm-kittens-section mm-kittens-section--available" aria-labelledby="mm-kittens-available">
        <div class="mm-kittens-section__header">
            <h2 id="mm-kittens-available"><?php esc_html_e( 'Available Now', 'mm-sphynx' ); ?></h2>
            <p><?php esc_html_e( 'Each kitten below is fully socialised, health screened, and ready to be matched with a guardian family.', 'mm-sphynx' ); ?></p>
        </div>

        <?php if ( ! empty( $available_kittens ) ) : ?>
            <div class="kitten-grid" data-grid="available">
                <?php foreach ( $available_kittens as $kitten ) : ?>
                    <?php get_template_part( 'template-parts/kitten/card-available', null, [ 'kitten' => $kitten ] ); ?>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p class="mm-kittens-empty"><?php esc_html_e( 'No kittens are currently available. Join the waitlist to be notified first when our next litter graduates.', 'mm-sphynx' ); ?></p>
        <?php endif; ?>
    </section>

    <section class="mm-kittens-section mm-kittens-section--waitlist" aria-labelledby="mm-kittens-waitlist">
        <div class="mm-kittens-section__header">
            <h2 id="mm-kittens-waitlist"><?php esc_html_e( 'Reserve & Waitlist', 'mm-sphynx' ); ?></h2>
            <p><?php esc_html_e( 'Track upcoming litters and secure your place. Guardians on our waitlist receive nursery updates, selection timelines, and bespoke guidance.', 'mm-sphynx' ); ?></p>
        </div>

        <?php if ( ! empty( $litters ) ) : ?>
            <div class="kitten-grid kitten-grid--litter" data-grid="litter">
                <?php foreach ( $litters as $litter ) : ?>
                    <?php get_template_part( 'template-parts/kitten/card-litter', null, [ 'litter' => $litter ] ); ?>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p class="mm-kittens-empty"><?php esc_html_e( 'Upcoming litters are being finalised. Join our waitlist to receive private announcements before public release.', 'mm-sphynx' ); ?></p>
        <?php endif; ?>
    </section>

    <?php if ( ! empty( $adoption_flow ) ) : ?>
        <section class="mm-adoption-flow" aria-labelledby="mm-adoption-journey">
            <h2 id="mm-adoption-journey"><?php esc_html_e( 'Reserve & Adoption Journey', 'mm-sphynx' ); ?></h2>
            <ol class="mm-adoption-flow__steps">
                <?php foreach ( $adoption_flow as $index => $step ) :
                    $title = mm_arr_get( $step, 'title', sprintf( /* translators: %d: step number */ __( 'Step %d', 'mm-sphynx' ), $index + 1 ) );
                    $copy  = mm_arr_get( $step, 'copy', '' );
                    $cta_text = mm_arr_get( $step, 'cta_text', '' );
                    $cta_target = mm_arr_get( $step, 'cta_target', '' );
                    ?>
                    <li class="mm-adoption-flow__step">
                        <span class="mm-adoption-flow__number"><?php echo esc_html( $index + 1 ); ?></span>
                        <div class="mm-adoption-flow__body">
                            <h3><?php echo esc_html( $title ); ?></h3>
                            <?php if ( $copy ) : ?>
                                <p><?php echo esc_html( $copy ); ?></p>
                            <?php endif; ?>
                            <?php if ( $cta_text && $cta_target ) : ?>
                                <a class="mm-adoption-flow__cta" href="<?php echo esc_url( $cta_target ); ?>"><?php echo esc_html( $cta_text ); ?></a>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
            <div class="mm-adoption-flow__footer">
                <a class="mm-button mm-button--primary" href="<?php echo esc_url( home_url( '/apply/' ) ); ?>"><?php esc_html_e( 'Apply Now', 'mm-sphynx' ); ?></a>
                <a class="mm-button mm-button--ghost" href="<?php echo esc_url( $waitlist_permalink ); ?>"><?php esc_html_e( 'Join Waitlist', 'mm-sphynx' ); ?></a>
            </div>
        </section>
    <?php endif; ?>

    <?php get_template_part( 'template-parts/kitten/modal-detail' ); ?>
</main>

<?php
get_footer();
