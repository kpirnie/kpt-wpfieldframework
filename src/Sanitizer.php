<?php

/**
 * Sanitizer - Sanitization and validation utilities
 *
 * Provides comprehensive sanitization and validation for all
 * field types, ensuring data integrity and security.
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
 * Class Sanitizer
 *
 * Handles sanitization and validation of field values
 * based on their type and configuration.
 *
 * @since 1.0.0
 */
class Sanitizer
{
    /**
     * Sanitize a value based on field configuration.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @param  array $field The field configuration.
     * @return mixed        The sanitized value.
     */
    public function sanitize(mixed $value, array $field): mixed
    {
        $type = $field['type'] ?? 'text';
        // Check for custom sanitize callback.
        if (! empty($field['sanitize']) && is_callable($field['sanitize'])) {
            return call_user_func($field['sanitize'], $value, $field);
        }

        // Type-specific sanitization.
        return match ($type) {
            'text', 'hidden'            => $this->sanitizeText($value),
            'textarea'                  => $this->sanitizeTextarea($value),
            'email'                     => $this->sanitizeEmail($value),
            'url'                       => $this->sanitizeUrl($value),
            'number', 'range'           => $this->sanitizeNumber($value, $field),
            'tel'                       => $this->sanitizeTel($value),
            'password'                  => $this->sanitizePassword($value),
            'date'                      => $this->sanitizeDate($value),
            'datetime'                  => $this->sanitizeDatetime($value),
            'time'                      => $this->sanitizeTime($value),
            'week'                      => $this->sanitizeWeek($value),
            'month'                     => $this->sanitizeMonth($value),
            'link'                      => $this->sanitizeLink($value),
            'select', 'radio'           => $this->sanitizeSelect($value, $field),
            'multiselect', 'checkboxes' => $this->sanitizeMultiSelect($value, $field),
            'checkbox'                  => $this->sanitizeCheckbox($value),
            'wysiwyg'                   => $this->sanitizeWysiwyg($value),
            'code'                      => $this->sanitizeCode($value),
            'color'                     => $this->sanitizeColor($value),
            'image', 'file'             => $this->sanitizeAttachment($value),
            'gallery'                   => $this->sanitizeGallery($value),
            'post_select', 'page_select' => $this->sanitizePostSelect($value),
            'term_select'               => $this->sanitizeTermSelect($value),
            'user_select'               => $this->sanitizeUserSelect($value),
            'repeater'                  => $this->sanitizeRepeater($value, $field),
            'group'                     => $this->sanitizeGroup($value, $field),
            default                     => $this->sanitizeText($value),
        };
    }

    /**
     * Sanitize unknown/untyped values.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return mixed        The sanitized value.
     */
    public function sanitizeUnknown(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(array( $this, 'sanitizeUnknown' ), $value);
        }

        if (is_string($value)) {
            return sanitize_text_field($value);
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value;
        }

        return '';
    }

    /**
     * Sanitize link field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return array        The sanitized link array.
     */
    private function sanitizeLink(mixed $value): array
    {
        if (!is_array($value)) {
            return ['url' => '', 'title' => '', 'target' => ''];
        }

        return [
            'url'    => esc_url_raw($value['url'] ?? ''),
            'title'  => sanitize_text_field($value['title'] ?? ''),
            'target' => ($value['target'] ?? '') === '_blank' ? '_blank' : '',
        ];
    }

    /**
     * Sanitize text field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return string       The sanitized string.
     */
    private function sanitizeText(mixed $value): string
    {
        if (! is_string($value)) {
            $value = (string) $value;
        }

        return sanitize_text_field($value);
    }

    /**
     * Sanitize textarea field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return string       The sanitized string.
     */
    private function sanitizeTextarea(mixed $value): string
    {
        if (! is_string($value)) {
            $value = (string) $value;
        }

        return sanitize_textarea_field($value);
    }

    /**
     * Sanitize email field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return string       The sanitized email.
     */
    private function sanitizeEmail(mixed $value): string
    {
        if (! is_string($value)) {
            $value = (string) $value;
        }

        return sanitize_email($value);
    }

    /**
     * Sanitize URL field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return string       The sanitized URL.
     */
    private function sanitizeUrl(mixed $value): string
    {
        if (! is_string($value)) {
            $value = (string) $value;
        }

        return esc_url_raw($value);
    }

    /**
     * Sanitize number field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @param  array $field The field configuration.
     * @return int|float    The sanitized number.
     */
    private function sanitizeNumber(mixed $value, array $field): int|float
    {
        // Handle empty string.
        if ($value === '' || $value === null) {
            return $field['default'] ?? 0;
        }

        // Determine if float or int.
        $step = $field['step'] ?? 1;
        $is_float = is_float($step) || strpos((string) $step, '.') !== false;
        if ($is_float) {
            $value = (float) $value;
        } else {
            $value = (int) $value;
        }

        // Apply min constraint.
        if (isset($field['min']) && $value < $field['min']) {
            $value = $field['min'];
        }

        // Apply max constraint.
        if (isset($field['max']) && $value > $field['max']) {
            $value = $field['max'];
        }

        return $value;
    }

    /**
     * Sanitize telephone field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return string       The sanitized telephone number.
     */
    private function sanitizeTel(mixed $value): string
    {
        if (! is_string($value)) {
            $value = (string) $value;
        }

        // Remove all characters except digits, plus, dash, parentheses, and spaces.
        return preg_replace('/[^0-9+\-\(\)\s]/', '', $value);
    }

    /**
     * Sanitize password field value.
     *
     * Passwords are not heavily sanitized to allow special characters.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return string       The sanitized password.
     */
    private function sanitizePassword(mixed $value): string
    {
        if (! is_string($value)) {
            $value = (string) $value;
        }

        // Only trim whitespace from ends.
        return trim($value);
    }

    /**
     * Sanitize date field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return string       The sanitized date string.
     */
    private function sanitizeDate(mixed $value): string
    {
        if (! is_string($value)) {
            $value = (string) $value;
        }

        $value = sanitize_text_field($value);
        // Validate date format (Y-m-d).
        if (! empty($value) && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            // Try to parse and reformat.
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
            return '';
        }

        return $value;
    }

    /**
     * Sanitize datetime field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return string       The sanitized datetime string.
     */
    private function sanitizeDatetime(mixed $value): string
    {
        if (! is_string($value)) {
            $value = (string) $value;
        }

        $value = sanitize_text_field($value);
        // Validate datetime-local format (Y-m-d\TH:i).
        if (! empty($value) && ! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $value)) {
            // Try to parse and reformat.
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return date('Y-m-d\TH:i', $timestamp);
            }
            return '';
        }

        return $value;
    }

    /**
     * Sanitize time field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return string       The sanitized time string.
     */
    private function sanitizeTime(mixed $value): string
    {
        if (! is_string($value)) {
            $value = (string) $value;
        }

        $value = sanitize_text_field($value);
        // Validate time format (H:i or H:i:s).
        if (! empty($value) && ! preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
            return '';
        }

        return $value;
    }

    /**
     * Sanitize week field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return string       The sanitized week string.
     */
    private function sanitizeWeek(mixed $value): string
    {
        if (! is_string($value)) {
            $value = (string) $value;
        }

        $value = sanitize_text_field($value);
        // Validate week format (Y-Www).
        if (! empty($value) && ! preg_match('/^\d{4}-W\d{2}$/', $value)) {
            return '';
        }

        return $value;
    }

    /**
     * Sanitize month field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return string       The sanitized month string.
     */
    private function sanitizeMonth(mixed $value): string
    {
        if (! is_string($value)) {
            $value = (string) $value;
        }

        $value = sanitize_text_field($value);
        // Validate month format (Y-m).
        if (! empty($value) && ! preg_match('/^\d{4}-\d{2}$/', $value)) {
            return '';
        }

        return $value;
    }

    /**
     * Sanitize select/radio field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @param  array $field The field configuration.
     * @return string       The sanitized value.
     */
    private function sanitizeSelect(mixed $value, array $field): string
    {
        $value = sanitize_text_field((string) $value);
        // Validate against available options.
        $options = $field['options'] ?? array();
        if (! empty($options)) {
            // Flatten optgroups if present.
            $valid_values = array();
            foreach ($options as $opt_value => $opt_label) {
                if (is_array($opt_label)) {
                    // Optgroup.
                    $valid_values = array_merge($valid_values, array_keys($opt_label));
                } else {
                    $valid_values[] = $opt_value;
                }
            }

            // Check if value is valid.
            if (! in_array($value, array_map('strval', $valid_values), true) && $value !== '') {
                return $field['default'] ?? '';
            }
        }

        return $value;
    }

    /**
     * Sanitize multiselect/checkboxes field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @param  array $field The field configuration.
     * @return array        The sanitized values array.
     */
    private function sanitizeMultiSelect(mixed $value, array $field): array
    {
        if (! is_array($value)) {
            return array();
        }

        $options = $field['options'] ?? array();
        $valid_values = array_map('strval', array_keys($options));
        $sanitized = array();
        foreach ($value as $item) {
            $item = sanitize_text_field((string) $item);
            // Validate against available options if provided.
            if (empty($options) || in_array($item, $valid_values, true)) {
                $sanitized[] = $item;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize checkbox field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return bool         The sanitized boolean.
     */
    private function sanitizeCheckbox(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Sanitize WYSIWYG field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return string       The sanitized HTML.
     */
    private function sanitizeWysiwyg(mixed $value): string
    {
        if (! is_string($value)) {
            $value = (string) $value;
        }

        return wp_kses_post($value);
    }

    /**
     * Sanitize code field value.
     *
     * Code fields allow more permissive content but still sanitize.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return string       The sanitized code.
     */
    private function sanitizeCode(mixed $value): string
    {
        if (! is_string($value)) {
            $value = (string) $value;
        }

        // Use wp_unslash to handle escaped quotes, then preserve the content.
        return wp_unslash($value);
    }

    /**
     * Sanitize color field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return string       The sanitized color value.
     */
    private function sanitizeColor(mixed $value): string
    {
        if (! is_string($value)) {
            $value = (string) $value;
        }

        $value = sanitize_text_field($value);
        // Validate hex color.
        if (! empty($value) && ! preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value)) {
            // Try rgba format.
            if (! preg_match('/^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*(,\s*[\d.]+\s*)?\)$/', $value)) {
                return '';
            }
        }

        return $value;
    }

    /**
     * Sanitize attachment (image/file) field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return int          The sanitized attachment ID.
     */
    private function sanitizeAttachment(mixed $value): int
    {
        $id = absint($value);
        // Verify attachment exists.
        if ($id > 0 && get_post_type($id) !== 'attachment') {
            return 0;
        }

        return $id;
    }

    /**
     * Sanitize gallery field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return string       The sanitized comma-separated IDs.
     */
    private function sanitizeGallery(mixed $value): string
    {
        if (is_array($value)) {
            $ids = array_map('absint', $value);
        } else {
            $ids = array_map('absint', explode(',', (string) $value));
        }

        // Filter out invalid IDs and verify attachments exist.
        $valid_ids = array();
        foreach ($ids as $id) {
            if ($id > 0 && get_post_type($id) === 'attachment') {
                $valid_ids[] = $id;
            }
        }

        return implode(',', $valid_ids);
    }

    /**
     * Sanitize post/page select field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return int          The sanitized post ID.
     */
    private function sanitizePostSelect(mixed $value): int
    {
        $id = absint($value);
        // Verify post exists.
        if ($id > 0 && ! get_post($id)) {
            return 0;
        }

        return $id;
    }

    /**
     * Sanitize term select field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return int          The sanitized term ID.
     */
    private function sanitizeTermSelect(mixed $value): int
    {
        $id = absint($value);
        // Verify term exists.
        if ($id > 0) {
            $term = get_term($id);
            if (! $term || is_wp_error($term)) {
                return 0;
            }
        }

        return $id;
    }

    /**
     * Sanitize user select field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @return int          The sanitized user ID.
     */
    private function sanitizeUserSelect(mixed $value): int
    {
        $id = absint($value);
        // Verify user exists.
        if ($id > 0 && ! get_user_by('ID', $id)) {
            return 0;
        }

        return $id;
    }

    /**
     * Sanitize repeater field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @param  array $field The field configuration.
     * @return array        The sanitized repeater data.
     */
    private function sanitizeRepeater(mixed $value, array $field): array
    {
        if (! is_array($value)) {
            return array();
        }

        $sub_fields = $field['fields'] ?? array();
        $sanitized = array();
        foreach ($value as $row_index => $row_data) {
            if (! is_array($row_data)) {
                continue;
            }

            $sanitized_row = array();
            foreach ($sub_fields as $sub_field) {
                $sub_field_id = $sub_field['id'];
                if (isset($row_data[ $sub_field_id ])) {
                    $sanitized_row[ $sub_field_id ] = $this->sanitize($row_data[ $sub_field_id ], $sub_field);
                }
            }

            // Only add non-empty rows.
            if (! empty($sanitized_row)) {
                $sanitized[] = $sanitized_row;
            }
        }

        // Re-index array.
        return array_values($sanitized);
    }

    /**
     * Sanitize group field value.
     *
     * @since  1.0.0
     * @param  mixed $value The value to sanitize.
     * @param  array $field The field configuration.
     * @return array        The sanitized group data.
     */
    private function sanitizeGroup(mixed $value, array $field): array
    {
        if (! is_array($value)) {
            return array();
        }

        $sub_fields = $field['fields'] ?? array();
        $sanitized = array();
        foreach ($sub_fields as $sub_field) {
            $sub_field_id = $sub_field['id'];
            if (isset($value[ $sub_field_id ])) {
                $sanitized[ $sub_field_id ] = $this->sanitize($value[ $sub_field_id ], $sub_field);
            }
        }

        return $sanitized;
    }

    /**
     * Validate a value against field rules.
     *
     * @since  1.0.0
     * @param  mixed $value The value to validate.
     * @param  array $field The field configuration.
     * @return array        Array with 'valid' bool and 'errors' array.
     */
    public function validate(mixed $value, array $field): array
    {
        $errors = array();
        // Check for custom validate callback.
        if (! empty($field['validate']) && is_callable($field['validate'])) {
            $result = call_user_func($field['validate'], $value, $field);
            if ($result !== true) {
                $errors[] = is_string($result) ? $result : __('Validation failed.', 'kp-wsf');
            }

            return array(
                'valid'  => empty($errors),
                'errors' => $errors,
            );
        }

        // Required check.
        if (! empty($field['required'])) {
            if ($this->isEmpty($value)) {
                $errors[] = sprintf(__('%s is required.', 'kp-wsf'), $field['label'] ?? $field['id']);
            }
        }

        // Type-specific validation.
        $type = $field['type'] ?? 'text';
        switch ($type) {
            case 'email':
                if (! empty($value) && ! is_email($value)) {
                    $errors[] = __('Please enter a valid email address.', 'kp-wsf');
                }

                break;
            case 'url':
                if (! empty($value) && ! filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors[] = __('Please enter a valid URL.', 'kp-wsf');
                }

                break;
            case 'number':
            case 'range':
                if (! empty($value) || $value === 0 || $value === '0') {
                    if (isset($field['min']) && $value < $field['min']) {
                        $errors[] = sprintf(__('Value must be at least %s.', 'kp-wsf'), $field['min']);
                    }
                    if (isset($field['max']) && $value > $field['max']) {
                            $errors[] = sprintf(__('Value must be no more than %s.', 'kp-wsf'), $field['max']);
                    }
                }

                break;
        }

        // Pattern validation.
        if (! empty($field['pattern']) && ! empty($value)) {
            if (! preg_match('/' . $field['pattern'] . '/', $value)) {
                $errors[] = $field['pattern_message'] ?? __('Value does not match the required format.', 'kp-wsf');
            }
        }

        // Min/max length validation.
        if (! empty($field['minlength']) && strlen((string) $value) < $field['minlength']) {
            $errors[] = sprintf(__('Value must be at least %d characters.', 'kp-wsf'), $field['minlength']);
        }

        if (! empty($field['maxlength']) && strlen((string) $value) > $field['maxlength']) {
            $errors[] = sprintf(__('Value must be no more than %d characters.', 'kp-wsf'), $field['maxlength']);
        }

        return array(
            'valid'  => empty($errors),
            'errors' => $errors,
        );
    }

    /**
     * Check if a value is empty.
     *
     * @since  1.0.0
     * @param  mixed $value The value to check.
     * @return bool         True if empty.
     */
    private function isEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return empty($value);
        }

        if (is_bool($value)) {
            return false;
            // Booleans are never "empty" for required purposes.
        }

        return empty($value);
    }
}
