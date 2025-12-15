<?php

/**
 * Repeater - Repeater field group handler
 *
 * Handles rendering and processing of repeatable field groups,
 * allowing users to add, remove, and reorder multiple instances
 * of a set of fields.
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
        if (! empty($field['label'])) {
            $html .= sprintf('<div class="kp-wsf-repeater__header"><h4>%s</h4></div>', esc_html($field['label']));
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

        // Output inline styles (once).
        $html .= $this->getInlineStyles();
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
            $sub_field_name = sprintf('%s[%s][%s]', $field['name'] ?? $field['id'], $row_index, $sub_field['id']);
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
        $layout_types = array( 'heading', 'separator', 'html', 'message' );
        if (in_array($type, $layout_types, true)) {
            return $this->field_types->render($field, $value);
        }

        // Standard field with label.
        $html = '<div class="kp-wsf-repeater__field kp-wsf-repeater__field--' . esc_attr($type) . '">';
        if (! empty($field['label'])) {
            $required = ! empty($field['required']) ? ' <span class="required">*</span>' : '';
            $html .= sprintf('<label for="%s">%s%s</label>', esc_attr($field['id']), esc_html($field['label']), $required);
        }

        $html .= '<div class="kp-wsf-repeater__field-input">';
        $html .= $this->field_types->render($field, $value);
        if (! empty($field['description'])) {
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
     * Get inline styles for the repeater.
     *
     * @since  1.0.0
     * @return string The style tag with CSS.
     */
    private function getInlineStyles(): string
    {
        static $styles_output = false;
        if ($styles_output) {
            return '';
        }

        $styles_output = true;
        return '
        <style>
            .kp-wsf-repeater {
                border: 1px solid #c3c4c7;
                background: #f6f7f7;
                padding: 0;
                margin-bottom: 15px;
            }
            .kp-wsf-repeater__header {
                padding: 10px 15px;
                border-bottom: 1px solid #c3c4c7;
                background: #fff;
            }
            .kp-wsf-repeater__header h4 {
                margin: 0;
                font-size: 14px;
            }
            .kp-wsf-repeater__rows {
                padding: 10px;
            }
            .kp-wsf-repeater__row {
                background: #fff;
                border: 1px solid #c3c4c7;
                margin-bottom: 10px;
            }
            .kp-wsf-repeater__row:last-child {
                margin-bottom: 0;
            }
            .kp-wsf-repeater__row--template {
                display: none;
            }
            .kp-wsf-repeater__row-header {
                display: flex;
                align-items: center;
                padding: 10px;
                background: #f6f7f7;
                border-bottom: 1px solid #c3c4c7;
                cursor: pointer;
            }
            .kp-wsf-repeater__row--collapsed .kp-wsf-repeater__row-header {
                border-bottom: none;
            }
            .kp-wsf-repeater__drag {
                cursor: move;
                color: #c3c4c7;
                margin-right: 10px;
            }
            .kp-wsf-repeater__drag:hover {
                color: #2271b1;
            }
            .kp-wsf-repeater__row-title {
                flex: 1;
                font-weight: 600;
            }
            .kp-wsf-repeater__row-number {
                font-weight: normal;
                color: #646970;
            }
            .kp-wsf-repeater__row-controls {
                display: flex;
                gap: 5px;
            }
            .kp-wsf-repeater__row-controls button {
                background: none;
                border: none;
                cursor: pointer;
                padding: 0;
                color: #646970;
            }
            .kp-wsf-repeater__row-controls button:hover {
                color: #2271b1;
            }
            .kp-wsf-repeater__remove:hover {
                color: #d63638 !important;
            }
            .kp-wsf-repeater__toggle .dashicons {
                transition: transform 0.2s;
            }
            .kp-wsf-repeater__row--collapsed .kp-wsf-repeater__toggle .dashicons {
                transform: rotate(-90deg);
            }
            .kp-wsf-repeater__row-content {
                padding: 15px;
            }
            .kp-wsf-repeater__row--collapsed .kp-wsf-repeater__row-content {
                display: none;
            }
            .kp-wsf-repeater__field {
                margin-bottom: 15px;
            }
            .kp-wsf-repeater__field:last-child {
                margin-bottom: 0;
            }
            .kp-wsf-repeater__field > label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
            }
            .kp-wsf-repeater__field .required {
                color: #d63638;
            }
            .kp-wsf-repeater__field .description {
                margin-top: 5px;
                color: #646970;
            }
            .kp-wsf-repeater__footer {
                padding: 10px 15px;
                border-top: 1px solid #c3c4c7;
                background: #fff;
            }
            .kp-wsf-repeater__add {
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            .kp-wsf-repeater.ui-sortable-helper {
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
            }
            .kp-wsf-repeater__row.ui-sortable-placeholder {
                visibility: visible !important;
                background: #f0f6fc;
                border: 2px dashed #2271b1;
            }
        </style>';
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
