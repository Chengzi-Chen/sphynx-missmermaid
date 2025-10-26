<?php
/**
 * Programmatically configure Fluent Forms for Miss Mermaid Sphynx.
 */

use FluentForm\App\Models\Form;
use FluentForm\App\Models\FormMeta;

if (!defined('ABSPATH')) {
    exit;
}

function mm_ff_unique_key()
{
    return 'el_' . str_replace('.', '', uniqid('', true));
}

function mm_ff_blank_condition()
{
    return [
        'type'       => 'any',
        'status'     => false,
        'conditions' => [
            [
                'field'    => '',
                'value'    => '',
                'operator' => '',
            ],
        ],
    ];
}

function mm_ff_text_field($index, $name, $label, $placeholder = '', $type = 'text', $required = true)
{
    return [
        'index'      => $index,
        'element'    => 'input_text',
        'attributes' => [
            'type'        => $type,
            'name'        => $name,
            'value'       => '',
            'id'          => '',
            'class'       => '',
            'placeholder' => $placeholder,
            'maxlength'   => '',
        ],
        'settings'   => [
            'container_class'   => '',
            'label'             => $label,
            'label_placement'   => '',
            'admin_field_label' => '',
            'help_message'      => '',
            'validation_rules'  => [
                'required' => [
                    'value'   => $required,
                    'message' => sprintf('%s field is required', $label),
                    'global'  => true,
                ],
            ],
            'conditional_logics' => mm_ff_blank_condition(),
            'prefix_label'       => '',
            'suffix_label'       => '',
        ],
        'editor_options' => [
            'title'     => $label,
            'icon_class'=> 'ff-edit-text',
            'template'  => 'inputText',
        ],
        'uniqElKey' => mm_ff_unique_key(),
    ];
}

function mm_ff_email_field($index, $name, $label, $placeholder = '')
{
    $field = mm_ff_text_field($index, $name, $label, $placeholder, 'email', true);
    $field['element'] = 'input_email';
    $field['editor_options']['icon_class'] = 'ff-edit-email';
    $field['settings']['validation_rules']['email'] = [
        'value'   => true,
        'message' => __('This field must contain a valid email', 'fluentform'),
        'global'  => true,
    ];
    return $field;
}

function mm_ff_textarea_field($index, $name, $label, $placeholder = '', $required = true)
{
    return [
        'index'      => $index,
        'element'    => 'textarea',
        'attributes' => [
            'name'        => $name,
            'value'       => '',
            'id'          => '',
            'class'       => '',
            'placeholder' => $placeholder,
            'rows'        => 5,
            'cols'        => 2,
        ],
        'settings'   => [
            'container_class'   => '',
            'label'             => $label,
            'admin_field_label' => '',
            'label_placement'   => '',
            'help_message'      => '',
            'validation_rules'  => [
                'required' => [
                    'value'   => $required,
                    'message' => sprintf('%s field is required', $label),
                    'global'  => true,
                ],
            ],
            'conditional_logics' => mm_ff_blank_condition(),
        ],
        'editor_options' => [
            'title'     => $label,
            'icon_class'=> 'ff-edit-textarea',
            'template'  => 'inputTextarea',
        ],
        'uniqElKey' => mm_ff_unique_key(),
    ];
}

function mm_ff_number_field($index, $name, $label, $placeholder = '', $required = false)
{
    $field = mm_ff_text_field($index, $name, $label, $placeholder, 'number', $required);
    $field['editor_options']['icon_class'] = 'ff-edit-number';
    return $field;
}

function mm_ff_date_field($index, $name, $label, $required = false)
{
    return [
        'index'      => $index,
        'element'    => 'input_date',
        'attributes' => [
            'type' => 'date',
            'name' => $name,
            'value'=> '',
        ],
        'settings'   => [
            'container_class'   => '',
            'label'             => $label,
            'admin_field_label' => '',
            'label_placement'   => '',
            'help_message'      => '',
            'validation_rules'  => [
                'required' => [
                    'value'   => $required,
                    'message' => sprintf('%s field is required', $label),
                    'global'  => true,
                ],
            ],
        ],
        'editor_options' => [
            'title'     => $label,
            'icon_class'=> 'ff-edit-date',
            'template'  => 'inputText',
        ],
        'uniqElKey' => mm_ff_unique_key(),
    ];
}

function mm_ff_select_field($index, $name, $label, array $choices, $required = true, $multiple = false, $placeholder = '')
{
    $options = [];
    foreach ($choices as $value => $text) {
        $options[] = [
            'label'      => $text,
            'value'      => $value,
            'calc_value' => '',
        ];
    }

    return [
        'index'      => $index,
        'element'    => 'input_select',
        'attributes' => [
            'name'        => $name,
            'value'       => '',
            'placeholder' => $placeholder,
        ],
        'settings'   => [
            'container_class'   => '',
            'label'             => $label,
            'admin_field_label' => '',
            'label_placement'   => '',
            'help_message'      => '',
            'placeholder'       => $placeholder,
            'validation_rules'  => [
                'required' => [
                    'value'   => $required,
                    'message' => sprintf('%s field is required', $label),
                    'global'  => true,
                ],
            ],
            'advanced_options'  => $options,
            'multiple'          => $multiple ? 'yes' : '',
        ],
        'editor_options' => [
            'title'     => $label,
            'icon_class'=> 'ff-edit-select',
            'template'  => 'select',
        ],
        'uniqElKey' => mm_ff_unique_key(),
    ];
}

function mm_ff_radio_field($index, $name, $label, array $choices, $required = true)
{
    $options = [];
    foreach ($choices as $value => $text) {
        $options[] = [
            'label'      => $text,
            'value'      => $value,
            'calc_value' => '',
        ];
    }

    return [
        'index'      => $index,
        'element'    => 'input_radio',
        'attributes' => [
            'type'  => 'radio',
            'name'  => $name,
            'value' => '',
        ],
        'settings'   => [
            'container_class'   => '',
            'label'             => $label,
            'admin_field_label' => '',
            'label_placement'   => '',
            'help_message'      => '',
            'options'           => $options,
            'display_type'      => '',
            'validation_rules'  => [
                'required' => [
                    'value'   => $required,
                    'message' => sprintf('%s field is required', $label),
                    'global'  => true,
                ],
            ],
        ],
        'editor_options' => [
            'title'     => $label,
            'icon_class'=> 'ff-edit-radio',
            'template'  => 'inputCheckable',
        ],
        'uniqElKey' => mm_ff_unique_key(),
    ];
}

function mm_ff_checkbox_field($index, $name, $label, array $choices, $required = false)
{
    $options = [];
    foreach ($choices as $value => $text) {
        $options[ $value ] = $text;
    }

    return [
        'index'      => $index,
        'element'    => 'input_checkbox',
        'attributes' => [
            'type'  => 'checkbox',
            'name'  => $name,
            'value' => [],
        ],
        'settings'   => [
            'container_class'   => '',
            'label'             => $label,
            'admin_field_label' => '',
            'label_placement'   => '',
            'help_message'      => '',
            'options'           => $options,
            'validation_rules'  => [
                'required' => [
                    'value'   => $required,
                    'message' => sprintf('%s field is required', $label),
                    'global'  => true,
                ],
            ],
        ],
        'editor_options' => [
            'title'     => $label,
            'icon_class'=> 'ff-edit-checkbox',
            'template'  => 'inputCheckable',
        ],
        'uniqElKey' => mm_ff_unique_key(),
    ];
}

function mm_ff_single_checkbox($index, $name, $label)
{
    return mm_ff_checkbox_field($index, $name, $label, [ 'yes' => $label ], true);
}

function mm_ff_file_field($index, $name, $label, $required = false)
{
    return [
        'index'      => $index,
        'element'    => 'input_file',
        'attributes' => [
            'type'  => 'file',
            'name'  => $name,
            'value' => '',
            'id'    => '',
            'class' => '',
        ],
        'settings'   => [
            'container_class'  => '',
            'label'            => $label,
            'admin_field_label'=> '',
            'label_placement'  => '',
            'btn_text'         => __('Choose File', 'fluentform'),
            'help_message'     => '',
            'validation_rules' => [
                'required'        => [
                    'value'   => $required,
                    'message' => __('Please attach an image', 'fluentform'),
                    'global'  => true,
                ],
                'max_file_size'   => [
                    'value'      => 5242880,
                    '_valueFrom' => 'MB',
                    'message'    => __('Maximum file size limit', 'fluentform'),
                    'global'     => true,
                ],
                'max_file_count'  => [
                    'value'   => 3,
                    'message' => __('Maximum file count exceeded', 'fluentform'),
                    'global'  => true,
                ],
                'allowed_file_types' => [
                    'value'   => ['jpg|jpeg|png', 'gif', 'heic'],
                    'message' => __('File type not allowed', 'fluentform'),
                    'global'  => true,
                ],
            ],
            'conditional_logics' => mm_ff_blank_condition(),
        ],
        'editor_options' => [
            'title'     => $label,
            'icon_class'=> 'ff-edit-file',
            'template'  => 'inputFile',
        ],
        'uniqElKey' => mm_ff_unique_key(),
    ];
}

function mm_ff_html_field($index, $html)
{
    return [
        'index'      => $index,
        'element'    => 'custom_html',
        'attributes' => [],
        'settings'   => [
            'html_codes'        => $html,
            'conditional_logics'=> mm_ff_blank_condition(),
            'container_class'   => 'ff-recaptcha-placeholder',
        ],
        'editor_options' => [
            'title'     => __('Custom HTML', 'fluentform'),
            'icon_class'=> 'ff-edit-html',
            'template'  => 'customHTML',
        ],
        'uniqElKey' => mm_ff_unique_key(),
    ];
}

function mm_ff_submit_button($text)
{
    return [
        'uniqElKey' => mm_ff_unique_key(),
        'element'   => 'button',
        'attributes'=> [
            'type'  => 'submit',
            'class' => '',
        ],
        'settings'  => [
            'align'           => 'center',
            'button_style'    => 'default',
            'container_class' => '',
            'help_message'    => '',
            'background_color'=> '#C6A15B',
            'button_size'     => 'md',
            'color'           => '#0B0B0F',
            'button_ui'       => [
                'type'    => 'default',
                'text'    => $text,
                'img_url' => '',
            ],
            'hover_styles' => [
                'backgroundColor' => '#8F6F3A',
                'color'           => '#EAEAEA',
            ],
        ],
        'editor_options' => [
            'title' => __('Submit Button', 'fluentform'),
        ],
    ];
}

function mm_ff_form_settings($message)
{
    $settings = Form::getFormsDefaultSettings();
    $settings['confirmation']['messageToShow'] = $message;
    $settings['form_settings']['ajaxSubmit'] = 'yes';
    $settings['form_settings']['labelPlacement'] = 'top';
    return $settings;
}

function mm_ff_step_start($index, $title, $description = '')
{
    return [
        'index'      => $index,
        'element'    => 'step_start',
        'attributes' => [],
        'settings'   => [
            'step_title'       => $title,
            'step_description' => $description,
            'conditional_logics'=> mm_ff_blank_condition(),
        ],
        'editor_options' => [
            'title'     => $title,
            'icon_class'=> 'ff-edit-step-start',
            'template'  => 'stepStart',
        ],
        'uniqElKey' => mm_ff_unique_key(),
    ];
}

function mm_ff_step_end($index)
{
    return [
        'index'      => $index,
        'element'    => 'step_end',
        'attributes' => [],
        'settings'   => [],
        'editor_options' => [
            'title'     => __('Step End', 'fluentform'),
            'icon_class'=> 'ff-edit-step-end',
            'template'  => 'stepEnd',
        ],
        'uniqElKey' => mm_ff_unique_key(),
    ];
}

function mm_ff_step_settings()
{
    return [
        'step_form'          => 'yes',
        'progress_indicator' => 'progress-bar',
        'show_step_titles'   => 'yes',
        'show_step_numbers'  => 'yes',
        'show_button_texts'  => 'yes',
        'button_texts'       => [
            'next_btn'   => __('Next', 'fluentform'),
            'prev_btn'   => __('Back', 'fluentform'),
            'finish_btn' => __('Submit Application', 'fluentform'),
        ],
    ];
}

function mm_ff_notifications_payload($subject)
{
    return [[
        'name'   => 'Admin Notification Email',
        'sendTo' => [
            'type'   => 'email',
            'email'  => 'admin@example.com',
            'field'  => 'email',
            'routing'=> [
                [
                    'email'    => null,
                    'field'    => null,
                    'operator' => '=',
                    'value'    => null,
                ],
            ],
        ],
        'fromName'       => '',
        'fromEmail'      => '{wp.admin_email}',
        'replyTo'        => '{inputs.email}',
        'bcc'            => '',
        'subject'        => $subject,
        'message'        => '<p>{all_data}</p>',
        'conditionals'   => [
            'status'     => false,
            'type'       => 'all',
            'conditions' => [
                [
                    'field'    => null,
                    'operator' => '=',
                    'value'    => null,
                ],
            ],
        ],
        'enabled'        => true,
        'email_template' => '',
        'attachments'    => [],
        'pdf_attachments'=> [],
    ]];
}

$forms = [
    'adoption_inquiry' => [
        'title'        => 'Adoption Inquiry',
        'button'       => 'Send Adoption Inquiry',
        'fields'       => [
            mm_ff_text_field(1, 'guardian_name', 'Name', 'Your full name'),
            mm_ff_email_field(2, 'email', 'Email', 'you@example.com'),
            mm_ff_text_field(3, 'phone', 'Phone', '+1 555 123 4567', 'tel', false),
            mm_ff_text_field(4, 'preferred_kitten', 'Preferred Kitten', 'Wanda line, Yoda line...', 'text', false),
            mm_ff_textarea_field(5, 'message', 'Household Overview', 'Share your household rhythm, travel cadence, and expectations.'),
        ],
        'confirmation'  => __('Thank you for your adoption inquiry. Our concierge team will reply within 48 hours.', 'miss-mermaid'),
        'subject'       => 'New Adoption Inquiry from {inputs.guardian_name}',
    ],
    'newsletter_subscribe' => [
        'title'        => 'Newsletter Subscribe',
        'button'       => 'Join Velvet Current',
        'fields'       => [
            mm_ff_text_field(1, 'subscriber_name', 'Name', 'Your name'),
            mm_ff_email_field(2, 'email', 'Email', 'you@example.com'),
        ],
        'confirmation'  => __('Thank you for joining the Velvet Current newsletter. Watch your inbox for seasonal rituals and adoption news.', 'miss-mermaid'),
        'subject'       => 'New Newsletter Subscriber: {inputs.subscriber_name}',
    ],
    'submit_story' => [
        'title'        => 'Submit Your Story',
        'button'       => 'Share My Story',
        'fields'       => [
            mm_ff_text_field(1, 'guardian_name', 'Name', 'Your name'),
            mm_ff_email_field(2, 'email', 'Email', 'you@example.com'),
            mm_ff_textarea_field(3, 'story', 'Your Story', 'Tell us about your Miss Mermaid companion.'),
            mm_ff_file_field(4, 'story_photos', 'Image Uploads', false),
        ],
        'confirmation'  => __('Thank you for sharing your story. Our team will review and follow up if we feature your companion.', 'miss-mermaid'),
        'subject'       => 'New Alumni Story from {inputs.guardian_name}',
    ],
    'general_contact' => [
        'title'        => 'General Contact',
        'button'       => 'Send Message',
        'fields'       => [
            mm_ff_text_field(1, 'guardian_name', 'Name', 'Your name'),
            mm_ff_email_field(2, 'email', 'Email', 'you@example.com'),
            mm_ff_text_field(3, 'cat_of_interest', 'Cat of Interest', 'Wanda lineage, Yoda lineage...', 'text', false),
            mm_ff_textarea_field(4, 'message', 'Message', 'Share how we can support you.'),
        ],
        'confirmation'  => __('Thank you for contacting Miss Mermaid Sphynx. We will respond within one business day.', 'miss-mermaid'),
        'subject'       => 'New Concierge Message from {inputs.guardian_name}',
    ],
    'adoption_apply' => [
        'title'        => 'Adoption Application',
        'button'       => 'Submit Application',
        'fields'       => [
            mm_ff_step_start(0, __('Guardian Details', 'miss-mermaid'), __('Introduce yourself to our guardianship team.', 'miss-mermaid')),
            mm_ff_text_field(1, 'guardian_name', 'Name', 'Your full name'),
            mm_ff_email_field(2, 'email', 'Email', 'you@example.com'),
            mm_ff_text_field(3, 'phone', 'Phone', '+1 555 123 4567', 'tel', true),
            mm_ff_text_field(4, 'city_state', 'City & State', 'Portland, OR', 'text', true),
            mm_ff_step_end(9),
            mm_ff_step_start(10, __('Lifestyle & Experience', 'miss-mermaid'), __('Share your daily rhythm and companion experience.', 'miss-mermaid')),
            mm_ff_textarea_field(11, 'household_overview', 'Household Overview', 'Describe your home, family members, daily rhythms.', true),
            mm_ff_textarea_field(12, 'current_pets', 'Current Pets', 'List existing pets and their temperaments.', false),
            mm_ff_textarea_field(13, 'experience', 'Experience with Hairless Breeds', 'Share grooming or wellness experience.', false),
            mm_ff_select_field(14, 'home_setting', 'Home Setting', [
                'loft'    => 'Loft / Condo',
                'house'   => 'Detached House',
                'townhome'=> 'Townhome / Duplex',
                'other'   => 'Other',
            ], true, false, 'Select your home type'),
            mm_ff_checkbox_field(15, 'lifestyle_tags', 'Lifestyle Tags', [
                'remote'  => 'Work from home',
                'travel'  => 'Frequent travel',
                'allergy' => 'Allergy-aware household',
                'kids'    => 'Children at home',
            ], false),
            mm_ff_step_end(19),
            mm_ff_step_start(20, __('Preferences & Agreements', 'miss-mermaid'), __('Finalize your dossier and acknowledgements.', 'miss-mermaid')),
            mm_ff_text_field(21, 'preferred_schedule', 'Preferred Adoption Window', 'e.g., Summer 2025', 'text', false),
            mm_ff_text_field(22, 'kitten_interest', 'Kitten ID (optional)', 'Enter a specific ID if known', 'text', false),
            mm_ff_single_checkbox(23, 'adoption_ack', __('I agree to concierge interviews, virtual studio tour, and wellness follow-ups.', 'miss-mermaid')),
            mm_ff_step_end(90),
        ],
        'confirmation'  => __('Thank you for your detailed application. Watch your inbox for a secure login link to follow your adoption journey.', 'miss-mermaid'),
        'subject'       => 'New Adoption Application: {inputs.guardian_name}',
        'step_settings' => mm_ff_step_settings(),
    ],
    'payment_proof' => [
        'title'        => 'Payment Proof Upload',
        'button'       => 'Submit Payment Proof',
        'fields'       => [
            mm_ff_select_field(1, 'payment_stage', 'Payment Stage', [
                'deposit' => 'Reservation / Deposit',
                'full'    => 'Full Adoption Payment',
            ], true, false, ''),
            mm_ff_select_field(2, 'payment_method', 'Payment Method', [
                'zelle'  => 'Zelle',
                'paypal' => 'PayPal Friends & Family',
                'wire'   => 'Wire Transfer',
                'other'  => 'Other',
            ], true, false, ''),
            mm_ff_number_field(3, 'amount', 'Amount (USD)', '3500', true),
            mm_ff_date_field(4, 'payment_date', 'Payment Date', true),
            mm_ff_text_field(5, 'transaction_reference', 'Transaction Reference', '', 'text', false),
            mm_ff_file_field(6, 'payment_receipt', 'Upload Receipt / Screenshot', true),
            mm_ff_textarea_field(7, 'notes', 'Notes (optional)', '', false),
        ],
        'confirmation'  => __('Thank you. Our guardianship team will review your receipt and update your status shortly.', 'miss-mermaid'),
        'subject'       => 'New Payment Proof submitted by {inputs.guardian_name}',
    ],
    'select_kitten' => [
        'title'        => 'Kitten Selection',
        'button'       => 'Send Selection',
        'fields'       => [
            mm_ff_text_field(1, 'kitten_id', 'Kitten ID', 'MM-2025-A1', 'text', true),
            mm_ff_textarea_field(2, 'motivation', 'Why this kitten?', 'Share alignment with your home & heart.', true),
            mm_ff_single_checkbox(3, 'selection_ack', __('I understand selections are confirmed after concierge approval.', 'miss-mermaid')),
        ],
        'confirmation'  => __('Selection received. Our concierge will confirm availability and next steps.', 'miss-mermaid'),
        'subject'       => 'Kitten Selection Request for {inputs.kitten_id}',
    ],
];

$placeholderHtml = '<div class="ff-embedded-recaptcha-placeholder"><span>reCAPTCHA placeholder — configure keys in Fluent Forms settings to activate.</span></div>';

$formIds = [];

foreach ($forms as $key => $config) {
    $fields = $config['fields'];
    $fields[] = mm_ff_html_field(99, $placeholderHtml);

    $formFields = [
        'fields'       => $fields,
        'submitButton' => mm_ff_submit_button($config['button']),
    ];
    if (!empty($config['step_settings'])) {
        $formFields['settings'] = [
            'step_start' => $config['step_settings'],
        ];
    }

    $encodedFields = wp_json_encode($formFields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $form = Form::where('title', $config['title'])->first();

    if (!$form) {
        $form = Form::create(Form::prepare([
            'title'       => $config['title'],
            'status'      => 'published',
            'type'        => 'form',
            'form_fields' => $encodedFields,
        ]));
    } else {
        $form->fill([
            'status'      => 'published',
            'form_fields' => $encodedFields,
            'updated_at'  => current_time('mysql'),
        ])->save();
    }

    FormMeta::persist($form->id, 'formSettings', mm_ff_form_settings($config['confirmation']));
    FormMeta::persist($form->id, 'notifications', mm_ff_notifications_payload($config['subject']));
    FormMeta::persist($form->id, 'template_name', 'mm_scripted');

    $formIds[$key] = $form->id;
}

$globalSettings = get_option('_fluentform_global_form_settings', []);
if (!is_array($globalSettings)) {
    $globalSettings = [];
}
if (!isset($globalSettings['misc']) || !is_array($globalSettings['misc'])) {
    $globalSettings['misc'] = [];
}
$globalSettings['misc']['honeypotStatus'] = 'yes';
$globalSettings['misc']['autoload_captcha'] = false;
$globalSettings['misc']['captcha_type'] = 'recaptcha';
update_option('_fluentform_global_form_settings', $globalSettings);
update_option('mm_sphynx_forms', $formIds);

echo wp_json_encode($formIds, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
