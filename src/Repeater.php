<?php

/**
 * Repeater - Repeater field group handler
 *
 * Handles rendering and processing of repeatable field groups,
 * allowing users to add, remove, and reorder multiple instances
 * of a set of fields.
 *
 * @package     KP\WPFieldFramework
 * @author      Kevin Pirnie <iam@kevinpirnie.com>
 * @copyright   2025 Kevin Pirnie
 * @license     MIT
 * @since       1.0.0
 */

declare(strict_types=1);

namespace KP\WPFieldFramework;

// We don't want to allow direct access to this
defined('ABSPATH') || die('No direct script access allowed');

// make sure the class is not already defined
if (! class_exists('\KP\WPFieldFramework\Repeater')) {

    /**
     * Class Repeater
     *
     * Manages repeatable field groups with support for
     * nested fields, sorting, and dynamic row management.
     *
     * @since 1.0.0
     */
    class Repeater
    {
        /**
         * Field types instance.
         *
         * @since 1.0.0
         * @var FieldTypes
         */
        private FieldTypes $field_types;
        /**
         * Constructor.
         *
         * @since 1.0.0
         * @param FieldTypes $field_types Field types instance.
         */
        public function __construct(FieldTypes $field_types)
        {
            $this->field_types = $field_types;
        }

        /**
         * Render the repeater field.
         *
         * @since  1.0.0
         * @param  array $field The repeater field configuration.
         * @param  mixed $value The current value (array of rows).
         * @return string       The rendered repeater HTML.
         */
        public function render(array $field, mixed $value): string
        {
            // Ensure value is array.
            $value = is_array($value) ? $value : array();
            // Get sub-fields configuration.
            $sub_fields = $field['fields'] ?? array();
            if (empty($sub_fields)) {
                return '<p class="kp-wsf-error">' . esc_html__('No sub-fields defined for repeater.', 'kp-wsf') . '</p>';
            }

            // Get repeater options.
            $min_rows = $field['min_rows'] ?? 0;
            $max_rows = $field['max_rows'] ?? 0;
            $button_label = $field['button_label'] ?? __('Add Row', 'kp-wsf');
            $collapsed = $field['collapsed'] ?? false;
            $sortable = $field['sortable'] ?? true;
            $row_label = $field['row_label'] ?? __('Row', 'kp-wsf');

            // Build HTML.
            $html = sprintf('<div class="kp-wsf-repeater" data-min-rows="%d" data-max-rows="%d" data-field-id="%s">', $min_rows, $max_rows, esc_attr($field['id']));

            // Repeater header.
            if (!empty($field['label']) || !empty($field['description'])) {
                $html .= '<div class="kp-wsf-repeater__header">';
                if (!empty($field['label'])) {
                    $html .= sprintf('<h4>%s</h4>', esc_html($field['label']));
                }
                if (!empty($field['description'])) {
                    $html .= sprintf('<p class="description">%s</p>', wp_kses_post($field['description']));
                }
                $html .= '</div>';
            }

            // Rows container.
            $html .= '<div class="kp-wsf-repeater__rows">';

            // Render existing rows.
            if (! empty($value)) {
                foreach ($value as $row_index => $row_data) {
                    $html .= $this->renderRow($field, $sub_fields, $row_index, $row_data, $collapsed, $sortable, $row_label);
                }
            }

            // Ensure minimum rows.
            $current_count = count($value);
            if ($min_rows > 0 && $current_count < $min_rows) {
                for ($i = $current_count; $i < $min_rows; $i++) {
                    $html .= $this->renderRow($field, $sub_fields, $i, array(), $collapsed, $sortable, $row_label);
                }
            }

            $html .= '</div>';
            // End rows container.

            // Add row button.
            $html .= sprintf(
                '<div class="kp-wsf-repeater__footer">
                    <button type="button" class="button kp-wsf-repeater__add">%s</button>
                </div>',
                esc_html($button_label)
            );

            // Row template for JavaScript cloning.
            $html .= '<script type="text/html" class="kp-wsf-repeater__template">';
            $html .= $this->renderRow($field, $sub_fields, '{{INDEX}}', array(), $collapsed, $sortable, $row_label, true);
            $html .= '</script>';
            $html .= '</div>';
            // End repeater.

            return $html;
        }

        /**
         * Render a single repeater row.
         *
         * @since  1.0.0
         * @param  array      $field       The repeater field configuration.
         * @param  array      $sub_fields  The sub-field configurations.
         * @param  int|string $row_index   The row index.
         * @param  array      $row_data    The row data values.
         * @param  bool       $collapsed   Whether the row should be collapsed.
         * @param  bool       $sortable    Whether the row is sortable.
         * @param  string     $row_label   The row label text.
         * @param  bool       $is_template Whether this is the template row.
         * @return string                  The rendered row HTML.
         */
        private function renderRow(array $field, array $sub_fields, int|string $row_index, array $row_data, bool $collapsed, bool $sortable, string $row_label, bool $is_template = false): string
        {
            $collapsed_class = $collapsed && ! $is_template ? ' kp-wsf-repeater__row--collapsed' : '';
            $template_class = $is_template ? ' kp-wsf-repeater__row--template' : '';
            $html = sprintf('<div class="kp-wsf-repeater__row%s%s" data-row-index="%s">', $collapsed_class, $template_class, esc_attr((string) $row_index));

            // Row header with controls.
            $html .= '<div class="kp-wsf-repeater__row-header">';

            // Drag handle for sorting.
            if ($sortable) {
                $html .= '<span class="kp-wsf-repeater__drag dashicons dashicons-menu" title="' . esc_attr__('Drag to reorder', 'kp-wsf') . '"></span>';
            }

            // Row label/title.
            $display_index = is_numeric($row_index) ? (int) $row_index + 1 : $row_index;
            $html .= sprintf('<span class="kp-wsf-repeater__row-title">%s <span class="kp-wsf-repeater__row-number">%s</span></span>', esc_html($row_label), esc_html((string) $display_index));

            // Row controls.
            $html .= '<div class="kp-wsf-repeater__row-controls">';

            // Toggle button for collapse.
            if ($collapsed || true) {
                // Always show toggle.
                $html .= '<button type="button" class="kp-wsf-repeater__toggle" title="' . esc_attr__('Toggle', 'kp-wsf') . '">';
                $html .= '<span class="dashicons dashicons-arrow-down-alt2"></span>';
                $html .= '</button>';
            }

            // Remove button.
            $html .= '<button type="button" class="kp-wsf-repeater__remove" title="' . esc_attr__('Remove', 'kp-wsf') . '">';
            $html .= '<span class="dashicons dashicons-trash"></span>';
            $html .= '</button>';
            $html .= '</div>';

            // End controls.
            $html .= '</div>';
            // End header.

            // Row content with fields.
            $html .= '<div class="kp-wsf-repeater__row-content">';
            foreach ($sub_fields as $sub_field) {
                // Build unique field ID and name for this row.
                $sub_field_id = $field['id'] . '_' . $row_index . '_' . $sub_field['id'];
                $sub_field_name = $field['name'] . '[' . $row_index . '][' . $sub_field['id'] . ']';

                // Get value for this sub-field.
                $sub_value = $row_data[ $sub_field['id'] ] ?? ( $sub_field['default'] ?? null );

                // Clone sub-field config with updated ID and name.
                $sub_field_config = array_merge(
                    $sub_field,
                    array(
                        'id'   => $sub_field_id,
                        'name' => $sub_field_name,
                    )
                );

                // Render the sub-field.
                $html .= $this->renderSubField($sub_field_config, $sub_value);
            }

            $html .= '</div>';

            // End content.
            $html .= '</div>';
            // End row.

            return $html;
        }

        /**
         * Render a sub-field within a repeater row.
         *
         * @since  1.0.0
         * @param  array $field The sub-field configuration.
         * @param  mixed $value The sub-field value.
         * @return string       The rendered sub-field HTML.
         */
        private function renderSubField(array $field, mixed $value): string
        {
            $type = $field['type'] ?? 'text';

            // Skip rendering repeaters within repeaters (prevent infinite nesting).
            if ($type === 'repeater') {
                return '<p class="kp-wsf-error">' . esc_html__('Nested repeaters are not supported.', 'kp-wsf') . '</p>';
            }

            // Layout-only fields.
            $layout_types = array('heading', 'separator', 'html', 'message');
            if (in_array($type, $layout_types, true)) {
                return $this->field_types->render($field, $value);
            }

            // Check for inline
            $is_inline = !empty($field['inline']) && filter_var($field['inline'], FILTER_VALIDATE_BOOLEAN);
            $inline_class = $is_inline ? ' kp-wsf-repeater__field--inline' : '';

            // Standard field with label and sublabel.
            $html = '<div class="kp-wsf-repeater__field kp-wsf-repeater__field--' . esc_attr($type) . $inline_class . '">';
            if (!empty($field['label'])) {
                $required = !empty($field['required']) ? ' <span class="required">*</span>' : '';
                $html .= sprintf('<label for="%s">%s%s</label>', esc_attr($field['id']), esc_html($field['label']), $required);
            }
            if (!empty($field['sublabel'])) {
                $html .= sprintf('<span class="kp-wsf-sublabel">%s</span>', wp_kses_post($field['sublabel']));
            }

            $html .= '<div class="kp-wsf-repeater__field-input">';
            $html .= $this->field_types->render($field, $value);
            if (!empty($field['description'])) {
                $html .= sprintf('<p class="description">%s</p>', wp_kses_post($field['description']));
            }

            $html .= '</div>';
            $html .= '</div>';
            return $html;
        }

        /**
         * Sanitize repeater data.
         *
         * @since  1.0.0
         * @param  mixed     $value      The submitted repeater value.
         * @param  array     $field      The repeater field configuration.
         * @param  Sanitizer $sanitizer The sanitizer instance.
         * @return array             The sanitized repeater data.
         */
        public function sanitize(mixed $value, array $field, Sanitizer $sanitizer): array
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
                        $sanitized_row[ $sub_field_id ] = $sanitizer->sanitize($row_data[ $sub_field_id ], $sub_field);
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
         * Get the value of a specific sub-field from repeater data.
         *
         * @since  1.0.0
         * @param  array  $repeater_data The full repeater data array.
         * @param  int    $row_index     The row index.
         * @param  string $field_id      The sub-field ID.
         * @param  mixed  $default       Default value if not found.
         * @return mixed                 The sub-field value.
         */
        public static function getValue(array $repeater_data, int $row_index, string $field_id, mixed $default = null): mixed
        {
            return $repeater_data[ $row_index ][ $field_id ] ?? $default;
        }

        /**
         * Get all values for a specific sub-field across all rows.
         *
         * @since  1.0.0
         * @param  array  $repeater_data The full repeater data array.
         * @param  string $field_id      The sub-field ID.
         * @return array                 Array of values from all rows.
         */
        public static function getColumnValues(array $repeater_data, string $field_id): array
        {
            $values = array();
            foreach ($repeater_data as $row) {
                if (isset($row[ $field_id ])) {
                    $values[] = $row[ $field_id ];
                }
            }

            return $values;
        }

        /**
         * Get the number of rows in the repeater data.
         *
         * @since  1.0.0
         * @param  array $repeater_data The full repeater data array.
         * @return int                  The number of rows.
         */
        public static function getRowCount(array $repeater_data): int
        {
            return count($repeater_data);
        }

        /**
         * Check if the repeater has any data.
         *
         * @since  1.0.0
         * @param  array $repeater_data The full repeater data array.
         * @return bool                 True if repeater has data.
         */
        public static function hasRows(array $repeater_data): bool
        {
            return ! empty($repeater_data);
        }
    }
}
