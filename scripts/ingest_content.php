<?php
/**
 * Ingest content matrices (kittens, litters, adoption flow, forms, emails) into WordPress.
 *
 * Usage:
 *   wp eval-file /opt/sphynx-scripts/ingest_content.php [/path/to/payload.json]
 */

use FluentForm\App\Models\Form;
use FluentForm\App\Models\FormMeta;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( Form::class ) ) {
    require_once WP_CONTENT_DIR . '/plugins/fluentform/app/Models/Form.php';
    require_once WP_CONTENT_DIR . '/plugins/fluentform/app/Models/FormMeta.php';
}

$payload_path = $argv[1] ?? '/opt/sphynx-content/mm_ingest_payload.json';
if ( ! file_exists( $payload_path ) ) {
    throw new RuntimeException( sprintf( 'Payload file not found: %s', $payload_path ) );
}

$payload = json_decode( file_get_contents( $payload_path ), true );
if ( ! is_array( $payload ) ) {
    throw new RuntimeException( 'Invalid payload JSON.' );
}

$summary = [
    'kittens_processed' => 0,
    'litters_processed' => 0,
    'forms_processed'   => [],
];

/**
 * Helper to ensure array value.
 */
function mm_ingest_normalize_list( $value ): array {
    if ( is_array( $value ) ) {
        return array_values(
            array_filter(
                array_map(
                    static function ( $item ) {
                        return is_string( $item ) ? trim( $item ) : $item;
                    },
                    $value
                ),
                static function ( $item ) {
                    return '' !== $item && null !== $item;
                }
            )
        );
    }

    if ( ! is_string( $value ) || '' === trim( $value ) ) {
        return [];
    }

    $parts = preg_split( '/[;\\n,]+/', $value );
    if ( ! is_array( $parts ) ) {
        return [];
    }

    return mm_ingest_normalize_list( $parts );
}

/**
 * Upsert kitten post with ACF fields.
 */
function mm_ingest_upsert_kitten( array $kitten ): int {
    $kitten_id = $kitten['kitten_id'] ?? '';
    if ( ! $kitten_id ) {
        return 0;
    }

    $existing = get_posts(
        [
            'post_type'      => 'kitten',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'meta_key'       => 'kitten_id',
            'meta_value'     => $kitten_id,
        ]
    );

    if ( ! empty( $existing ) ) {
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
            throw new RuntimeException( sprintf( 'Unable to create kitten %s: %s', $kitten_id, $post_id->get_error_message() ) );
        }
    }

    $status  = $kitten['status'] ?? 'available';
    $allowed = [ 'available', 'upcoming', 'reserved', 'adopted' ];
    if ( ! in_array( $status, $allowed, true ) ) {
        $status = 'available';
    }

    $detail = $kitten['detail'] ?? [];

    update_field( 'kitten_id', $kitten_id, $post_id );
    update_field( 'status', $status, $post_id );
    if ( isset( $kitten['price'] ) ) {
        update_field( 'price', (float) $kitten['price'], $post_id );
    }
    if ( ! empty( $kitten['sex'] ) ) {
        update_field( 'sex', strtolower( (string) $kitten['sex'] ), $post_id );
    }
    if ( ! empty( $kitten['color'] ) ) {
        update_field( 'color', (string) $kitten['color'], $post_id );
    }
    if ( ! empty( $kitten['birthday'] ) ) {
        update_field( 'birthday', (string) $kitten['birthday'], $post_id );
    }
    if ( ! empty( $kitten['age_hint'] ) ) {
        update_field( 'age_hint', (string) $kitten['age_hint'], $post_id );
    }
    if ( ! empty( $kitten['temperament_tags'] ) ) {
        update_field( 'temperament_tags', (string) $kitten['temperament_tags'], $post_id );
    }
    if ( ! empty( $kitten['short_desc'] ) ) {
        update_field( 'short_description', (string) $kitten['short_desc'], $post_id );
        wp_update_post(
            [
                'ID'           => $post_id,
                'post_excerpt' => wp_trim_words( wp_strip_all_tags( (string) $kitten['short_desc'] ), 80 ),
            ]
        );
    }
    if ( ! empty( $kitten['cover_image'] ) ) {
        update_field( 'cover_image', (string) $kitten['cover_image'], $post_id );
    }

    $gallery_rows = [];
    foreach ( mm_ingest_normalize_list( $kitten['gallery'] ?? [] ) as $url ) {
        $gallery_rows[] = [ 'image_url' => $url ];
    }
    update_field( 'gallery', $gallery_rows, $post_id );

    $video_rows = [];
    foreach ( mm_ingest_normalize_list( $detail['videos'] ?? [] ) as $url ) {
        $video_rows[] = [ 'video_url' => $url ];
    }
    update_field( 'videos', $video_rows, $post_id );

    update_field( 'parent_sire', (string) ( $detail['sire'] ?? '' ), $post_id );
    update_field( 'parent_dam', (string) ( $detail['dam'] ?? '' ), $post_id );
    update_field( 'health_notes', (string) ( $detail['health_notes'] ?? '' ), $post_id );
    update_field( 'value_points', (string) ( $detail['value_points'] ?? '' ), $post_id );
    update_field( 'care_profile', (string) ( $detail['care_profile'] ?? '' ), $post_id );
    update_field( 'apply_text', (string) ( $detail['apply_text'] ?? '' ), $post_id );
    update_field( 'apply_url', (string) ( $detail['apply_url'] ?? '' ), $post_id );
    update_field( 'featured', 0, $post_id );

    return $post_id;
}

/**
 * Upsert litter post with ACF fields.
 */
function mm_ingest_upsert_litter( array $litter ): int {
    $litter_id = $litter['litter_id'] ?? '';
    if ( ! $litter_id ) {
        return 0;
    }

    $existing = get_posts(
        [
            'post_type'      => 'litter',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'meta_key'       => 'litter_id',
            'meta_value'     => $litter_id,
        ]
    );

    if ( ! empty( $existing ) ) {
        $post_id = (int) $existing[0]->ID;
    } else {
        $post_id = wp_insert_post(
            [
                'post_type'   => 'litter',
                'post_status' => 'publish',
                'post_title'  => $litter_id,
                'post_name'   => sanitize_title( $litter_id ),
            ],
            true
        );

        if ( is_wp_error( $post_id ) ) {
            throw new RuntimeException( sprintf( 'Unable to create litter %s: %s', $litter_id, $post_id->get_error_message() ) );
        }
    }

    update_field( 'litter_id', $litter_id, $post_id );
    update_field( 'queen', (string) ( $litter['queen'] ?? '' ), $post_id );
    update_field( 'sire', (string) ( $litter['sire'] ?? '' ), $post_id );
    update_field( 'due_window', (string) ( $litter['due_window'] ?? '' ), $post_id );
    update_field( 'expected_colors', (string) ( $litter['expected_colors'] ?? '' ), $post_id );
    update_field( 'slots_total', (int) ( $litter['slots_total'] ?? 0 ), $post_id );
    update_field( 'policy_highlight', (string) ( $litter['policy_highlight'] ?? '' ), $post_id );
    update_field( 'join_text', (string) ( $litter['join_text'] ?? '' ), $post_id );
    update_field( 'join_url', (string) ( $litter['join_url'] ?? '' ), $post_id );
    update_field( 'note', (string) ( $litter['note'] ?? '' ), $post_id );
    if ( ! get_field( 'status', $post_id ) ) {
        update_field( 'status', 'confirmed', $post_id );
    }

    return $post_id;
}

/**
 * Build Fluent Forms field from sheet spec.
 */
function mm_ingest_build_form_field( int $index, array $spec, string $form_key ): array {
    $type      = $spec['type'] ?? 'text';
    $label     = $spec['label'] ?? '';
    $required  = ! empty( $spec['required'] );
    $help      = $spec['help_text'] ?? '';

    $name = $spec['field_key'] ?? ( 'field_' . $index );
    $map  = [
        'apply' => [
            'location'   => 'city_state',
            'kitten_id'  => 'kitten_interest',
            'preferences'=> 'household_overview',
        ],
    ];

    if ( isset( $map[ $form_key ][ $name ] ) ) {
        $name = $map[ $form_key ][ $name ];
    } elseif ( 'guardian_name' === $name ) {
        $name = 'guardian_name';
    }

    $field = [
        'index'      => $index,
        'element'    => 'input_text',
        'attributes' => [
            'type'        => 'text',
            'name'        => $name,
            'value'       => '',
            'placeholder' => '',
        ],
        'settings'   => [
            'container_class'   => '',
            'label'             => $label,
            'admin_field_label' => '',
            'label_placement'   => '',
            'help_message'      => $help ?: '',
            'validation_rules'  => [
                'required' => [
                    'value'   => $required,
                    'message' => sprintf( __( '%s is required', 'miss-mermaid' ), $label ),
                    'global'  => true,
                ],
            ],
        ],
        'editor_options' => [
            'title'     => $label,
            'icon_class'=> 'ff-edit-text',
            'template'  => 'inputText',
        ],
        'uniqElKey' => 'el_' . str_replace( '.', '', uniqid( '', true ) ),
    ];

    switch ( $type ) {
        case 'email':
            $field['element'] = 'input_email';
            $field['attributes']['type'] = 'email';
            $field['settings']['validation_rules']['email'] = [
                'value'   => true,
                'message' => __( 'Please provide a valid email address.', 'miss-mermaid' ),
                'global'  => true,
            ];
            $field['editor_options']['icon_class'] = 'ff-edit-email';
            break;
        case 'textarea':
            $field['element']             = 'textarea';
            $field['attributes']['rows']  = 5;
            $field['attributes']['cols']  = 2;
            $field['attributes']['type']  = 'textarea';
            $field['editor_options']['icon_class'] = 'ff-edit-textarea';
            $field['editor_options']['template']   = 'inputTextarea';
            break;
        case 'select':
            $options = [];
            foreach ( mm_ingest_normalize_list( $spec['options'] ?? [] ) as $opt ) {
                $options[] = [
                    'label'      => $opt,
                    'value'      => $opt,
                    'calc_value' => '',
                ];
            }
            $field['element'] = 'input_select';
            $field['settings']['advanced_options'] = $options;
            $field['settings']['placeholder']      = __( 'Select an option', 'miss-mermaid' );
            $field['editor_options']['icon_class'] = 'ff-edit-select';
            $field['editor_options']['template']   = 'select';
            break;
        case 'text':
        default:
            // already configured.
            break;
    }

    return $field;
}

/**
 * Build Fluent Forms definition for given form key.
 */
function mm_ingest_build_form( string $form_key, array $fields ): array {
    $index  = 1;
    $output = [];
    foreach ( $fields as $field ) {
        $output[] = mm_ingest_build_form_field( $index++, $field, $form_key );
    }
    return $output;
}

/**
 * Create or update Fluent Form with provided payload.
 */
function mm_ingest_upsert_form( string $slug, string $title, array $fields, string $submit_text, string $confirmation ): int {
    $form_fields = [
        'fields'       => $fields,
        'submitButton' => [
            'uniqElKey' => 'el_' . str_replace( '.', '', uniqid( '', true ) ),
            'element'   => 'button',
            'attributes'=> [
                'type'  => 'submit',
                'class' => '',
            ],
            'settings'  => [
                'button_style'    => 'default',
                'align'           => 'center',
                'button_ui'       => [
                    'type' => 'default',
                    'text' => $submit_text,
                ],
            ],
        ],
    ];

    $encoded = wp_json_encode( $form_fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

    $form = Form::where( 'title', $title )->first();

    if ( ! $form ) {
        $form = Form::create(
            Form::prepare(
                [
                    'title'       => $title,
                    'status'      => 'published',
                    'type'        => 'form',
                    'form_fields' => $encoded,
                ]
            )
        );
    } else {
        $form->fill(
            [
                'status'      => 'published',
                'form_fields' => $encoded,
                'updated_at'  => current_time( 'mysql' ),
            ]
        )->save();
    }

    FormMeta::persist(
        $form->id,
        'formSettings',
        array_merge(
            Form::getFormsDefaultSettings(),
            [
                'confirmation' => [
                    'confirmation_type' => 'message',
                    'messageToShow'     => $confirmation,
                ],
                'form_settings' => [
                    'ajaxSubmit'     => 'yes',
                    'labelPlacement' => 'top',
                ],
            ]
        )
    );

    FormMeta::persist(
        $form->id,
        'notifications',
        [
            [
                'name'   => 'Admin Notification',
                'sendTo' => [
                    'type'  => 'email',
                    'email' => 'admin@example.com',
                ],
                'fromName'       => '{site_title}',
                'fromEmail'      => '{wp.admin_email}',
                'replyTo'        => '{inputs.email}',
                'subject'        => sprintf( 'New submission: %s', $title ),
                'message'        => '<p>{all_data}</p>',
                'conditionals'   => [
                    'status'     => false,
                    'type'       => 'all',
                    'conditions' => [],
                ],
                'enabled'        => true,
            ],
        ]
    );

    FormMeta::persist( $form->id, 'template_name', 'mm_ingest' );

    return (int) $form->id;
}

// Ingest kittens.
foreach ( $payload['kittens'] ?? [] as $kitten ) {
    mm_ingest_upsert_kitten( $kitten );
    $summary['kittens_processed']++;
}

// Ingest litters.
foreach ( $payload['litters'] ?? [] as $litter ) {
    mm_ingest_upsert_litter( $litter );
    $summary['litters_processed']++;
}

// Adoption flow.
if ( ! empty( $payload['adoption_flow'] ) ) {
    $flow = array_map(
        static function ( $item ) {
            return [
                'key'        => $item['key'] ?? '',
                'order'      => (int) ( $item['order'] ?? 0 ),
                'title'      => $item['title'] ?? '',
                'copy'       => $item['copy'] ?? '',
                'cta_text'   => $item['cta_text'] ?? '',
                'cta_target' => $item['cta_target'] ?? '',
            ];
        },
        $payload['adoption_flow']
    );
    update_option( 'mm_sphynx_adoption_flow', $flow );
}

// Forms.
$forms_payload = $payload['forms'] ?? [];
$forms_registry = get_option( 'mm_sphynx_forms', [] );
if ( ! is_array( $forms_registry ) ) {
    $forms_registry = [];
}

if ( ! empty( $forms_payload['apply'] ) ) {
    $fields  = mm_ingest_build_form( 'apply', $forms_payload['apply'] );
    $form_id = mm_ingest_upsert_form(
        'adoption_apply',
        'Adoption Application',
        $fields,
        __( 'Submit Application', 'miss-mermaid' ),
        __( 'Thank you for your application. Watch your inbox for a secure magic link to track progress.', 'miss-mermaid' )
    );
    $forms_registry['adoption_apply'] = $form_id;
    $summary['forms_processed']['adoption_apply'] = $form_id;
}

if ( ! empty( $forms_payload['waitlist'] ) ) {
    $fields  = mm_ingest_build_form( 'waitlist', $forms_payload['waitlist'] );
    $form_id = mm_ingest_upsert_form(
        'waitlist',
        'Waitlist Request',
        $fields,
        __( 'Join Waitlist', 'miss-mermaid' ),
        __( 'Thank you. Our guardianship team will confirm your queue within one business day.', 'miss-mermaid' )
    );
    $forms_registry['waitlist'] = $form_id;
    $summary['forms_processed']['waitlist'] = $form_id;
}

update_option( 'mm_sphynx_forms', $forms_registry );

// Global Fluent Forms settings (honeypot + captcha placeholder toggles).
$global_settings = get_option( '_fluentform_global_form_settings', [] );
if ( ! is_array( $global_settings ) ) {
    $global_settings = [];
}
$global_settings['misc']['honeypotStatus']   = 'yes';
$global_settings['misc']['autoload_captcha'] = false;
$global_settings['misc']['captcha_type']     = 'recaptcha';
update_option( '_fluentform_global_form_settings', $global_settings );

// Email templates.
if ( ! empty( $payload['emails'] ) && is_array( $payload['emails'] ) ) {
    update_option( 'mm_sphynx_email_templates', $payload['emails'] );
}

echo wp_json_encode( $summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
