<?php
/**
 * Shared modal for kitten detail display.
 */

$placeholder = mm_sphynx_placeholder_image();
?>

<div class="mm-kitten-modal" id="mm-kitten-modal" role="dialog" aria-modal="true" aria-hidden="true" hidden data-price-fallback="<?php esc_attr_e( 'Contact for pricing', 'mm-sphynx' ); ?>">
    <div class="mm-kitten-modal__backdrop" data-modal-close tabindex="-1"></div>
    <div class="mm-kitten-modal__content" role="document">
        <button type="button" class="mm-kitten-modal__close" data-modal-close aria-label="<?php esc_attr_e( 'Close modal', 'mm-sphynx' ); ?>">&times;</button>
        <div class="mm-kitten-modal__gallery" data-modal-gallery>
            <div class="mm-carousel" data-carousel>
                <img src="<?php echo esc_url( $placeholder ); ?>" alt="" class="mm-kitten-modal__placeholder" data-modal-placeholder loading="lazy" decoding="async" />
            </div>
        </div>
        <div class="mm-kitten-modal__body">
            <header class="mm-kitten-modal__header">
                <h2 class="mm-kitten-modal__title" data-modal-title><?php esc_html_e( 'Kitten detail', 'mm-sphynx' ); ?></h2>
                <p class="mm-kitten-modal__subtitle" data-modal-status></p>
            </header>
            <p class="mm-kitten-modal__summary" data-modal-summary></p>
            <dl class="mm-kitten-modal__specs" data-modal-specs></dl>
            <div class="mm-kitten-modal__tags" data-modal-tags></div>
            <div class="mm-kitten-modal__notes" data-modal-notes></div>
            <div class="mm-kitten-modal__cta">
                <a href="#" class="mm-button mm-button--primary" data-modal-primary><?php esc_html_e( 'Apply', 'mm-sphynx' ); ?></a>
            </div>
        </div>
    </div>
</div>
