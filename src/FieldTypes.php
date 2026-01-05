<?php

/**
 * FieldTypes - Field type registry and rendering
 *
 * Provides rendering for all standard WordPress form field types.
 * Handles field HTML output for options pages and meta boxes.
 *
 * @package     KP\WPStarterFramework
 * @author      Kevin Pirnie <iam@kevinpirnie.com>
 * @copyright   2025 Kevin Pirnie
 * @license     MIT
 * @since       1.0.0
 */

declare(strict_types=1);

namespace KP\WPStarterFramework;

// Prevent direct access.
defined('ABSPATH') || exit;
/**
 * Class FieldTypes
 *
 * Renderer for all supported field types.
 * Each field type has a corresponding render method that outputs
 * properly formatted HTML using WordPress conventions.
 *
 * @since 1.0.0
 */
class FieldTypes
{
    /**
     * Supported field types.
     *
     * @since 1.0.0
     * @var array<string>
     */
    private const SUPPORTED_TYPES = array(
        // Text-based.
        'text',
        'email',
        'url',
        'password',
        'number',
        'tel',
        'hidden',
        // Date/time.
        'date',
        'datetime',
        'time',
        'week',
        'month',
        // Selection.
        'select',
        'multiselect',
        'checkbox',
        'checkboxes',
        'radio',
        'link',
        // Text areas/editors.
        'textarea',
        'wysiwyg',
        'code',
        // Media.
        'image',
        'file',
        'gallery',
        // Special.
        'color',
        'range',
        'post_select',
        'term_select',
        'user_select',
        'page_select',
        // Layout.
        'heading',
        'separator',
        'html',
        'message',
        // Complex.
        'repeater',
        'group',
    );
    /**
     * Default field configuration.
     *
     * @since 1.0.0
     * @var array<string, mixed>
     */
    private array $field_defaults = array(
        'id'          => '',
        'name'        => '',
        'type'        => 'text',
        'label'       => '',
        'description' => '',
        'default'     => '',
        'class'       => '',
        'placeholder' => '',
        'required'    => false,
        'disabled'    => false,
        'readonly'    => false,
        'options'     => array(),
        'attributes'  => array(),
    );
    /**
     * Check if a field type is supported.
     *
     * @since  1.0.0
     * @param  string $type The field type to check.
     * @return bool         True if supported.
     */
    public function isSupported(string $type): bool
    {
        return in_array($type, self::SUPPORTED_TYPES, true);
    }

    /**
     * Get all supported field types.
     *
     * @since  1.0.0
     * @return array<string> List of supported type identifiers.
     */
    public function getSupportedTypes(): array
    {
        return self::SUPPORTED_TYPES;
    }

    /**
     * Render a field based on its configuration.
     *
     * @since  1.0.0
     * @param  array $field The field configuration array.
     * @param  mixed $value The current field value.
     * @return string       The rendered field HTML.
     */
    public function render(array $field, mixed $value = null): string
    {
        // Merge with defaults.
        $field = wp_parse_args($field, $this->field_defaults);
        // Ensure name is set from id if not provided.
        if (empty($field['name'])) {
            $field['name'] = $field['id'];
        }

        // Use default value if current value is null.
        if ($value === null && $field['default'] !== '') {
            $value = $field['default'];
        }

        // Check if type is supported.
        if (! $this->isSupported($field['type'])) {
            return $this->renderUnsupported($field);
        }

        // Build method name and call it.
        $method = 'render' . str_replace('_', '', ucwords($field['type'], '_'));
        if (method_exists($this, $method)) {
            return $this->$method($field, $value);
        }

        return $this->renderUnsupported($field);
    }

    /**
     * Render a field row with label and description.
     *
     * @since  1.0.0
     * @param  array  $field   The field configuration array.
     * @param  mixed  $value   The current field value.
     * @param  string $context The rendering context ('options', 'meta', 'user').
     * @return string          The rendered field row HTML.
     */
    public function renderRow(array $field, mixed $value = null, string $context = 'meta'): string
    {
        $field = wp_parse_args($field, $this->field_defaults);
        // Skip row wrapper for certain types.
        $no_wrapper_types = array( 'hidden', 'heading', 'separator', 'html' );
        if (in_array($field['type'], $no_wrapper_types, true)) {
            return $this->render($field, $value);
        }

        $html = '';
        if ($context === 'options') {
            // Options page table row format.
            $html .= '<tr>';
            $html .= '<th scope="row">';
            $html .= $this->renderLabel($field);
            $html .= '</th>';
            $html .= '<td>';
            $html .= $this->render($field, $value);
            $html .= $this->renderDescription($field);
            $html .= '</td>';
            $html .= '</tr>';
        } else {
            // Meta box / user profile format.
            $html .= '<div class="kp-wsf-field kp-wsf-field--' . esc_attr($field['type']) . '">';
            $html .= '<div class="kp-wsf-field__label">';
            $html .= $this->renderLabel($field);
            $html .= '</div>';
            $html .= '<div class="kp-wsf-field__input">';
            $html .= $this->render($field, $value);
            $html .= $this->renderDescription($field);
            $html .= '</div>';
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Render a field label.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @return string       The label HTML.
     */
    public function renderLabel(array $field): string
    {
        if (empty($field['label'])) {
            return '';
        }

        $required = ! empty($field['required']) ? ' <span class="required">*</span>' : '';
        return sprintf('<label for="%s">%s%s</label>', esc_attr($field['id']), esc_html($field['label']), $required);
    }

    /**
     * Render a field description.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @return string       The description HTML.
     */
    public function renderDescription(array $field): string
    {
        if (empty($field['description'])) {
            return '';
        }

        return sprintf('<p class="description">%s</p>', wp_kses_post($field['description']));
    }

    /**
     * Build common HTML attributes for input fields.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @return string       The attributes string.
     */
    private function buildAttributes(array $field): string
    {
        $attrs = array();
        // Standard attributes.
        $attrs['id'] = $field['id'];
        $attrs['name'] = $field['name'];
        if (! empty($field['class'])) {
            $attrs['class'] = $field['class'];
        }

        if (! empty($field['placeholder'])) {
            $attrs['placeholder'] = $field['placeholder'];
        }

        if (! empty($field['required'])) {
            $attrs['required'] = 'required';
        }

        if (! empty($field['disabled'])) {
            $attrs['disabled'] = 'disabled';
        }

        if (! empty($field['readonly'])) {
            $attrs['readonly'] = 'readonly';
        }

        // Merge custom attributes.
        if (! empty($field['attributes']) && is_array($field['attributes'])) {
            $attrs = array_merge($attrs, $field['attributes']);
        }

        // Build attribute string.
        $attr_string = '';
        foreach ($attrs as $key => $val) {
            if ($val === true) {
                $attr_string .= ' ' . esc_attr($key);
            } elseif ($val !== false && $val !== null) {
                $attr_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($val));
            }
        }

        return $attr_string;
    }

    /**
     * Render unsupported field type message.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @return string       The error message HTML.
     */
    private function renderUnsupported(array $field): string
    {
        return sprintf('<p class="kp-wsf-error">%s</p>', esc_html(sprintf('Unsupported field type: %s', $field['type'])));
    }

    // =========================================================================
    // Text-based Input Renderers
    // =========================================================================

    /**
     * Render a text input field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderText(array $field, mixed $value): string
    {
        $field['class'] = 'regular-text' . ( ! empty($field['class']) ? ' ' . $field['class'] : '' );
        return sprintf('<input type="text" value="%s"%s />', esc_attr((string) $value), $this->buildAttributes($field));
    }

    /**
     * Render an email input field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderEmail(array $field, mixed $value): string
    {
        $field['class'] = 'regular-text' . ( ! empty($field['class']) ? ' ' . $field['class'] : '' );
        return sprintf('<input type="email" value="%s"%s />', esc_attr((string) $value), $this->buildAttributes($field));
    }

    /**
     * Render a URL input field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderUrl(array $field, mixed $value): string
    {
        $field['class'] = 'regular-text code' . ( ! empty($field['class']) ? ' ' . $field['class'] : '' );
        return sprintf('<input type="url" value="%s"%s />', esc_attr((string) $value), $this->buildAttributes($field));
    }

    /**
     * Render a password input field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderPassword(array $field, mixed $value): string
    {
        $field['class'] = 'regular-text' . ( ! empty($field['class']) ? ' ' . $field['class'] : '' );
        return sprintf('<input type="password" value="%s"%s autocomplete="new-password" />', esc_attr((string) $value), $this->buildAttributes($field));
    }

    /**
     * Render a number input field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderNumber(array $field, mixed $value): string
    {
        $field['class'] = 'small-text' . ( ! empty($field['class']) ? ' ' . $field['class'] : '' );
        // Add min/max/step if provided.
        if (isset($field['min'])) {
            $field['attributes']['min'] = $field['min'];
        }
        if (isset($field['max'])) {
            $field['attributes']['max'] = $field['max'];
        }
        if (isset($field['step'])) {
            $field['attributes']['step'] = $field['step'];
        }

        return sprintf('<input type="number" value="%s"%s />', esc_attr((string) $value), $this->buildAttributes($field));
    }

    /**
     * Render a telephone input field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderTel(array $field, mixed $value): string
    {
        $field['class'] = 'regular-text' . ( ! empty($field['class']) ? ' ' . $field['class'] : '' );
        return sprintf('<input type="tel" value="%s"%s />', esc_attr((string) $value), $this->buildAttributes($field));
    }

    /**
     * Render a hidden input field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderHidden(array $field, mixed $value): string
    {
        return sprintf('<input type="hidden" value="%s"%s />', esc_attr((string) $value), $this->buildAttributes($field));
    }

    // =========================================================================
    // Date/Time Input Renderers
    // =========================================================================

    /**
     * Render a date input field with jQuery UI datepicker.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderDate(array $field, mixed $value): string
    {
        $field['class'] = 'regular-text kp-wsf-datepicker' . ( ! empty($field['class']) ? ' ' . $field['class'] : '' );
        $field['attributes']['data-date-format'] = $field['date_format'] ?? 'yy-mm-dd';
        return sprintf('<input type="text" value="%s"%s />', esc_attr((string) $value), $this->buildAttributes($field));
    }

    /**
     * Render a datetime-local input field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderDatetime(array $field, mixed $value): string
    {
        $field['class'] = 'regular-text' . ( ! empty($field['class']) ? ' ' . $field['class'] : '' );
        return sprintf('<input type="datetime-local" value="%s"%s />', esc_attr((string) $value), $this->buildAttributes($field));
    }

    /**
     * Render a time input field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderTime(array $field, mixed $value): string
    {
        $field['class'] = 'small-text' . ( ! empty($field['class']) ? ' ' . $field['class'] : '' );
        return sprintf('<input type="time" value="%s"%s />', esc_attr((string) $value), $this->buildAttributes($field));
    }

    /**
     * Render a week input field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderWeek(array $field, mixed $value): string
    {
        $field['class'] = 'small-text' . ( ! empty($field['class']) ? ' ' . $field['class'] : '' );
        return sprintf('<input type="week" value="%s"%s />', esc_attr((string) $value), $this->buildAttributes($field));
    }

    /**
     * Render a month input field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderMonth(array $field, mixed $value): string
    {
        $field['class'] = 'small-text' . ( ! empty($field['class']) ? ' ' . $field['class'] : '' );
        return sprintf('<input type="month" value="%s"%s />', esc_attr((string) $value), $this->buildAttributes($field));
    }

    // =========================================================================
    // Selection Input Renderers
    // =========================================================================

    /**
     * Render a link selector field.
     *
     * Uses WordPress's built-in wpLink dialog.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value (array with url, title, target).
     * @return string       The field HTML.
     */
    private function renderLink(array $field, mixed $value): string
    {
        // Ensure value is array.
        $value = is_array($value) ? $value : [];
        $url = $value['url'] ?? '';
        $title = $value['title'] ?? '';
        $target = $value['target'] ?? '';

        $html = '<div class="kp-wsf-link-field">';

        // URL input.
        $html .= sprintf(
            '<input type="text" id="%s_url" name="%s[url]" value="%s" class="regular-text kp-wsf-link-url" placeholder="%s" />',
            esc_attr($field['id']),
            esc_attr($field['name']),
            esc_url($url),
            esc_attr__('URL', 'kp-wsf')
        );

        // Link button.
        $html .= sprintf(
            ' <button type="button" class="button kp-wsf-link-select" data-target="%s">%s</button>',
            esc_attr($field['id']),
            esc_html__('Select Link', 'kp-wsf')
        );

        // Title input.
        $html .= sprintf(
            '<input type="text" id="%s_title" name="%s[title]" value="%s" class="regular-text kp-wsf-link-title" placeholder="%s" />',
            esc_attr($field['id']),
            esc_attr($field['name']),
            esc_attr($title),
            esc_attr__('Link Text', 'kp-wsf')
        );

        // Target checkbox.
        $checked = $target === '_blank' ? ' checked="checked"' : '';
        $html .= sprintf(
            '<label class="kp-wsf-link-target-label"><input type="checkbox" id="%s_target" name="%s[target]" value="_blank" class="kp-wsf-link-target"%s /> %s</label>',
            esc_attr($field['id']),
            esc_attr($field['name']),
            $checked,
            esc_html__('Open in new tab', 'kp-wsf')
        );

        $html .= '</div>';

        return $html;
    }

    /**
     * Render a select dropdown field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderSelect(array $field, mixed $value): string
    {
        $html = sprintf('<select%s>', $this->buildAttributes($field));
        // Add placeholder option if set.
        if (! empty($field['placeholder'])) {
            $html .= sprintf('<option value="">%s</option>', esc_html($field['placeholder']));
        }

        // Render options.
        foreach ($field['options'] as $opt_value => $opt_label) {
            // Support optgroups.
            if (is_array($opt_label)) {
                $html .= sprintf('<optgroup label="%s">', esc_attr($opt_value));
                foreach ($opt_label as $sub_value => $sub_label) {
                    $selected = selected($value, $sub_value, false);
                    $html .= sprintf('<option value="%s"%s>%s</option>', esc_attr($sub_value), $selected, esc_html($sub_label));
                }
                $html .= '</optgroup>';
            } else {
                $selected = selected($value, $opt_value, false);
                $html .= sprintf('<option value="%s"%s>%s</option>', esc_attr($opt_value), $selected, esc_html($opt_label));
            }
        }

        $html .= '</select>';
        return $html;
    }

    /**
     * Render a multi-select dropdown field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value (array).
     * @return string       The field HTML.
     */
    private function renderMultiselect(array $field, mixed $value): string
    {
        // Ensure value is array.
        $value = is_array($value) ? $value : array();
        // Modify field for multiple selection.
        $field['name'] = $field['name'] . '[]';
        $field['attributes']['multiple'] = 'multiple';
        $field['attributes']['size'] = $field['size'] ?? 5;
        $html = sprintf('<select%s>', $this->buildAttributes($field));
        foreach ($field['options'] as $opt_value => $opt_label) {
            $selected = in_array($opt_value, $value, true) ? ' selected="selected"' : '';
            $html .= sprintf('<option value="%s"%s>%s</option>', esc_attr($opt_value), $selected, esc_html($opt_label));
        }

        $html .= '</select>';
        return $html;
    }

    /**
     * Render a single checkbox field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderCheckbox(array $field, mixed $value): string
    {
        $checked = checked($value, true, false);
        $checkbox_label = $field['checkbox_label'] ?? '';
        return sprintf('<label for="%s"><input type="checkbox" value="1"%s%s /> %s</label>', esc_attr($field['id']), $this->buildAttributes($field), $checked, esc_html($checkbox_label));
    }

    /**
     * Render a group of checkboxes.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value (array).
     * @return string       The field HTML.
     */
    private function renderCheckboxes(array $field, mixed $value): string
    {
        $value = is_array($value) ? $value : array();
        $html = '<fieldset class="kp-wsf-checkboxes">';
        foreach ($field['options'] as $opt_value => $opt_label) {
            $checked = in_array((string) $opt_value, array_map('strval', $value), true) ? ' checked="checked"' : '';
            $opt_id = $field['id'] . '_' . sanitize_key($opt_value);
            $html .= sprintf('<label for="%s"><input type="checkbox" id="%s" name="%s[]" value="%s"%s /> %s</label><br />', esc_attr($opt_id), esc_attr($opt_id), esc_attr($field['name']), esc_attr($opt_value), $checked, esc_html($opt_label));
        }

        $html .= '</fieldset>';
        return $html;
    }

    /**
     * Render a group of radio buttons.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderRadio(array $field, mixed $value): string
    {
        $html = '<fieldset class="kp-wsf-radios">';
        foreach ($field['options'] as $opt_value => $opt_label) {
            $checked = checked($value, $opt_value, false);
            $opt_id = $field['id'] . '_' . sanitize_key($opt_value);
            $html .= sprintf('<label for="%s"><input type="radio" id="%s" name="%s" value="%s"%s /> %s</label><br />', esc_attr($opt_id), esc_attr($opt_id), esc_attr($field['name']), esc_attr($opt_value), $checked, esc_html($opt_label));
        }

        $html .= '</fieldset>';
        return $html;
    }

    // =========================================================================
    // Text Area and Editor Renderers
    // =========================================================================

    /**
     * Render a textarea field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderTextarea(array $field, mixed $value): string
    {
        $field['class'] = 'large-text' . ( ! empty($field['class']) ? ' ' . $field['class'] : '' );
        $field['attributes']['rows'] = $field['rows'] ?? 5;
        $field['attributes']['cols'] = $field['cols'] ?? 50;
        return sprintf('<textarea%s>%s</textarea>', $this->buildAttributes($field), esc_textarea((string) $value));
    }

    /**
     * Render a WYSIWYG editor field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderWysiwyg(array $field, mixed $value): string
    {
        $settings = wp_parse_args(
            $field['editor_settings'] ?? array(),
            array(
                'textarea_name' => $field['name'],
                'textarea_rows' => $field['rows'] ?? 10,
                'media_buttons' => $field['media_buttons'] ?? true,
                'teeny'         => $field['teeny'] ?? false,
                'quicktags'     => $field['quicktags'] ?? true,
            )
        );
        ob_start();
        wp_editor((string) $value, $field['id'], $settings);
        return ob_get_clean();
    }

    /**
     * Render a code editor field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderCode(array $field, mixed $value): string
    {
        $field['class'] = 'large-text code kp-wsf-code-editor' . ( ! empty($field['class']) ? ' ' . $field['class'] : '' );
        $field['attributes']['rows'] = $field['rows'] ?? 10;
        $field['attributes']['data-code-type'] = $field['code_type'] ?? 'text/html';
        return sprintf('<textarea%s>%s</textarea>', $this->buildAttributes($field), esc_textarea((string) $value));
    }

    // =========================================================================
    // Media Field Renderers
    // =========================================================================

    /**
     * Render an image upload field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value (attachment ID).
     * @return string       The field HTML.
     */
    private function renderImage(array $field, mixed $value): string
    {
        $value = absint($value);
        $image_url = $value ? wp_get_attachment_image_url($value, 'thumbnail') : '';
        $html = '<div class="kp-wsf-image-field">';
        // Preview image.
        $html .= sprintf('<div class="kp-wsf-image-preview"%s>', $image_url ? '' : ' style="display:none;"');
        if ($image_url) {
            $html .= sprintf('<img src="%s" alt="" />', esc_url($image_url));
        }
        $html .= '</div>';
        // Hidden input for attachment ID.
        $html .= sprintf('<input type="hidden" id="%s" name="%s" value="%s" class="kp-wsf-image-id" />', esc_attr($field['id']), esc_attr($field['name']), esc_attr($value ?: ''));
        // Buttons.
        $html .= '<p class="kp-wsf-image-buttons">';
        $html .= sprintf('<button type="button" class="button kp-wsf-upload-image">%s</button> ', esc_html__('Select Image', 'kp-wsf'));
        $html .= sprintf('<button type="button" class="button kp-wsf-remove-image"%s>%s</button>', $image_url ? '' : ' style="display:none;"', esc_html__('Remove Image', 'kp-wsf'));
        $html .= '</p>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Render a file upload field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value (attachment ID).
     * @return string       The field HTML.
     */
    private function renderFile(array $field, mixed $value): string
    {
        $attachment_id = absint($value);
        $file_url = $attachment_id ? wp_get_attachment_url($attachment_id) : '';
        $filename = $file_url ? basename($file_url) : '';
        $html = '<div class="kp-wsf-file-field">';
        // File info display.
        $html .= sprintf('<div class="kp-wsf-file-info"%s>', $file_url ? '' : ' style="display:none;"');
        $html .= sprintf('<span class="kp-wsf-filename">%s</span>', esc_html($filename));
        if ($file_url) {
            $html .= sprintf(' <a href="%s" target="_blank" class="kp-wsf-file-link">%s</a>', esc_url($file_url), esc_html__('View', 'kp-wsf'));
        }
        $html .= '</div>';
        // Hidden input for attachment ID.
        $html .= sprintf('<input type="hidden" id="%s" name="%s" value="%s" class="kp-wsf-file-id" />', esc_attr($field['id']), esc_attr($field['name']), esc_attr($attachment_id ?: ''));
        // Buttons.
        $html .= '<p class="kp-wsf-file-buttons">';
        $html .= sprintf('<button type="button" class="button kp-wsf-upload-file">%s</button> ', esc_html__('Select File', 'kp-wsf'));
        $html .= sprintf('<button type="button" class="button kp-wsf-remove-file"%s>%s</button>', $file_url ? '' : ' style="display:none;"', esc_html__('Remove File', 'kp-wsf'));
        $html .= '</p>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Render a gallery field for multiple images.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value (comma-separated IDs or array).
     * @return string       The field HTML.
     */
    private function renderGallery(array $field, mixed $value): string
    {
        // Parse IDs from value.
        $ids = array();
        if (! empty($value)) {
            if (is_array($value)) {
                $ids = array_map('absint', $value);
            } else {
                $ids = array_map('absint', explode(',', (string) $value));
            }
            $ids = array_filter($ids);
        }

        $html = '<div class="kp-wsf-gallery-field">';
        // Gallery preview container.
        $html .= '<div class="kp-wsf-gallery-preview">';
        foreach ($ids as $id) {
            $thumb_url = wp_get_attachment_image_url($id, 'thumbnail');
            if ($thumb_url) {
                $html .= sprintf(
                    '<div class="kp-wsf-gallery-item" data-id="%d">' .
                    '<img src="%s" alt="" />' .
                    '<button type="button" class="kp-wsf-gallery-remove">&times;</button>' .
                    '</div>',
                    $id,
                    esc_url($thumb_url)
                );
            }
        }
        $html .= '</div>';
        // Hidden input for IDs.
        $html .= sprintf('<input type="hidden" id="%s" name="%s" value="%s" class="kp-wsf-gallery-ids" />', esc_attr($field['id']), esc_attr($field['name']), esc_attr(implode(',', $ids)));
        // Buttons.
        $html .= '<p class="kp-wsf-gallery-buttons">';
        $html .= sprintf('<button type="button" class="button kp-wsf-add-gallery">%s</button> ', esc_html__('Add Images', 'kp-wsf'));
        $html .= sprintf('<button type="button" class="button kp-wsf-clear-gallery"%s>%s</button>', empty($ids) ? ' style="display:none;"' : '', esc_html__('Clear Gallery', 'kp-wsf'));
        $html .= '</p>';
        $html .= '</div>';
        return $html;
    }

    // =========================================================================
    // Special Field Renderers
    // =========================================================================

    /**
     * Render a color picker field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderColor(array $field, mixed $value): string
    {
        $field['class'] = 'kp-wsf-color-picker' . ( ! empty($field['class']) ? ' ' . $field['class'] : '' );
        if (! empty($field['default'])) {
            $field['attributes']['data-default-color'] = $field['default'];
        }

        return sprintf('<input type="text" value="%s"%s />', esc_attr((string) $value), $this->buildAttributes($field));
    }

    /**
     * Render a range slider field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value.
     * @return string       The field HTML.
     */
    private function renderRange(array $field, mixed $value): string
    {
        $field['attributes']['min'] = $field['min'] ?? 0;
        $field['attributes']['max'] = $field['max'] ?? 100;
        $field['attributes']['step'] = $field['step'] ?? 1;
        $html = '<div class="kp-wsf-range-field">';
        $html .= sprintf('<input type="range" value="%s"%s />', esc_attr((string) $value), $this->buildAttributes($field));
        $html .= sprintf('<span class="kp-wsf-range-value">%s</span>', esc_html((string) $value));
        $html .= '</div>';
        return $html;
    }

    /**
     * Render a post select dropdown.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value (post ID).
     * @return string       The field HTML.
     */
    private function renderPostSelect(array $field, mixed $value): string
    {
        $post_type = $field['post_type'] ?? 'post';
        $posts_per_page = $field['posts_per_page'] ?? -1;
        $posts = get_posts(
            array(
                'post_type'      => $post_type,
                'posts_per_page' => $posts_per_page,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );
        $field['options'] = array();
        foreach ($posts as $post) {
            $field['options'][ $post->ID ] = $post->post_title;
        }

        return $this->renderSelect($field, $value);
    }

    /**
     * Render a term select dropdown.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value (term ID).
     * @return string       The field HTML.
     */
    private function renderTermSelect(array $field, mixed $value): string
    {
        $taxonomy = $field['taxonomy'] ?? 'category';
        $terms = get_terms(
            array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => $field['hide_empty'] ?? false,
            )
        );
        $field['options'] = array();
        if (! is_wp_error($terms)) {
            foreach ($terms as $term) {
                $field['options'][ $term->term_id ] = $term->name;
            }
        }

        return $this->renderSelect($field, $value);
    }

    /**
     * Render a user select dropdown.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value (user ID).
     * @return string       The field HTML.
     */
    private function renderUserSelect(array $field, mixed $value): string
    {
        $role = $field['role'] ?? '';
        $args = array(
            'orderby' => 'display_name',
            'order' => 'ASC',
        );
        if (! empty($role)) {
            $args['role'] = $role;
        }

        $users = get_users($args);
        $field['options'] = array();
        foreach ($users as $user) {
            $field['options'][ $user->ID ] = $user->display_name;
        }

        return $this->renderSelect($field, $value);
    }

    /**
     * Render a page select dropdown.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value (page ID).
     * @return string       The field HTML.
     */
    private function renderPageSelect(array $field, mixed $value): string
    {
        $field['post_type'] = 'page';
        return $this->renderPostSelect($field, $value);
    }

    // =========================================================================
    // Layout Field Renderers
    // =========================================================================

    /**
     * Render a heading field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value Unused.
     * @return string       The field HTML.
     */
    private function renderHeading(array $field, mixed $value): string
    {
        $tag = $field['tag'] ?? 'h3';
        $allowed_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
        $tag = in_array($tag, $allowed_tags, true) ? $tag : 'h3';
        return sprintf('<%s class="kp-wsf-heading">%s</%s>', $tag, esc_html($field['label']), $tag);
    }

    /**
     * Render a separator field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value Unused.
     * @return string       The field HTML.
     */
    private function renderSeparator(array $field, mixed $value): string
    {
        return '<hr class="kp-wsf-separator" />';
    }

    /**
     * Render raw HTML field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value Unused.
     * @return string       The field HTML.
     */
    private function renderHtml(array $field, mixed $value): string
    {
        return wp_kses_post($field['content'] ?? '');
    }

    /**
     * Render a message/notice field.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value Unused.
     * @return string       The field HTML.
     */
    private function renderMessage(array $field, mixed $value): string
    {
        $type = $field['message_type'] ?? 'info';
        $allowed_types = array( 'info', 'success', 'warning', 'error' );
        $type = in_array($type, $allowed_types, true) ? $type : 'info';
        return sprintf('<div class="notice notice-%s inline"><p>%s</p></div>', esc_attr($type), wp_kses_post($field['content'] ?? ''));
    }

    // =========================================================================
    // Complex Field Renderers
    // =========================================================================

    /**
     * Render a repeater field.
     *
     * Delegates to the Repeater class for full functionality.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current value (array of rows).
     * @return string       The field HTML.
     */
    private function renderRepeater(array $field, mixed $value): string
    {
        $repeater = new Repeater($this);
        return $repeater->render($field, $value);
    }

    /**
     * Render a field group.
     *
     * Groups multiple fields together visually.
     *
     * @since  1.0.0
     * @param  array $field The field configuration.
     * @param  mixed $value The current values (associative array).
     * @return string       The field HTML.
     */
    private function renderGroup(array $field, mixed $value): string
    {
        $value = is_array($value) ? $value : array();
        $sub_fields = $field['fields'] ?? array();
        $html = '<div class="kp-wsf-group">';
        if (! empty($field['label'])) {
            $html .= sprintf('<h4 class="kp-wsf-group-title">%s</h4>', esc_html($field['label']));
        }

        $html .= '<div class="kp-wsf-group-fields">';
        foreach ($sub_fields as $sub_field) {
            // Prefix subfield IDs/names with group ID.
            $sub_field['id'] = $field['id'] . '_' . $sub_field['id'];
            $sub_field['name'] = $field['name'] . '[' . $sub_field['id'] . ']';
            $sub_value = $value[ $sub_field['id'] ] ?? null;
            $html .= $this->renderRow($sub_field, $sub_value, 'meta');
        }

        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }
}
