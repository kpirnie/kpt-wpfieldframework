<?php

/**
 * BlockGenerator - Gutenberg block generator from meta boxes
 *
 * Automatically generates Gutenberg blocks from meta box configurations,
 * allowing meta box fields to be used as block attributes in the editor.
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
if (! class_exists('\KP\WPFieldFramework\BlockGenerator')) {

    /**
     * Class BlockGenerator
     *
     * Generates and registers Gutenberg blocks based on meta box configurations.
     * Uses WordPress's built-in block registration and renders blocks server-side.
     *
     * @since 1.0.0
     */
    class BlockGenerator
    {
        /**
         * Field types instance.
         *
         * @since 1.0.0
         * @var FieldTypes
         */
        private FieldTypes $field_types;
        /**
         * Registered blocks.
         *
         * @since 1.0.0
         * @var array<string, array>
         */
        private array $blocks = array();
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
         * Register a block from a meta box configuration.
         *
         * @since  1.0.0
         * @param  MetaBox $meta_box The meta box instance.
         * @return void
         */
        public function registerFromMetaBox(MetaBox $meta_box): void
        {
            $block_config = $meta_box->getBlockConfig();
            $fields = $meta_box->getFields();
            // Build block configuration.
            $block_id = $block_config['name'] ?? 'kp-wsf/' . $meta_box->getId();
            $this->blocks[$block_id] = array(
                'meta_box'   => $meta_box,
                'fields'     => $fields,
                'config'     => wp_parse_args(
                    $block_config,
                    array(
                        'name'        => $block_id,
                        'title'       => $meta_box->getTitle(),
                        'description' => '',
                        'category'    => 'common',
                        'icon'        => 'admin-generic',
                        'keywords'    => array(),
                        'supports'    => array(
                            'html'   => false,
                            'anchor' => true,
                        ),
                        'render_callback' => null,
                        'render_template' => null,
                    )
                ),
            );
        }

        /**
         * Register all blocks with WordPress.
         *
         * @since  1.0.0
         * @return void
         */
        public function registerAll(): void
        {
            foreach ($this->blocks as $block_id => $block_data) {
                $this->registerBlock($block_id, $block_data);
            }

            // Register editor assets.
            add_action('enqueue_block_editor_assets', array($this, 'enqueueEditorAssets'));
        }

        /**
         * Register a single block with WordPress.
         *
         * @since  1.0.0
         * @param  string $block_id   The block identifier.
         * @param  array  $block_data The block configuration data.
         * @return void
         */
        private function registerBlock(string $block_id, array $block_data): void
        {
            $config = $block_data['config'];
            $fields = $block_data['fields'];
            // Build attributes from fields.
            $attributes = $this->buildAttributes($fields);
            // Register the block type.
            register_block_type(
                $block_id,
                array(
                    'api_version'     => 3,
                    'title'           => $config['title'],
                    'description'     => $config['description'],
                    'category'        => $config['category'],
                    'icon'            => $config['icon'],
                    'keywords'        => $config['keywords'],
                    'supports'        => $config['supports'],
                    'attributes'      => $attributes,
                    'render_callback' => function ($attributes, $content) use ($block_id, $block_data) {

                        return $this->renderBlock($block_id, $attributes, $content, $block_data);
                    },
                    'editor_script_handles' => array('kp-wsf-block-editor'),
                )
            );
        }

        /**
         * Build block attributes from field configurations.
         *
         * @since  1.0.0
         * @param  array $fields The field configurations.
         * @return array         The block attributes.
         */
        private function buildAttributes(array $fields): array
        {
            $attributes = array();
            foreach ($fields as $field) {
                // Skip layout-only fields.
                $layout_types = array('heading', 'separator', 'html', 'message');
                if (in_array($field['type'] ?? 'text', $layout_types, true)) {
                    continue;
                }

                $attr_type = $this->getAttributeType($field['type'] ?? 'text');
                $attributes[$field['id']] = array(
                    'type'    => $attr_type,
                    'default' => $this->getAttributeDefault($field),
                );
            }

            return $attributes;
        }

        /**
         * Get the block attribute type for a field type.
         *
         * @since  1.0.0
         * @param  string $field_type The field type.
         * @return string             The attribute type.
         */
        private function getAttributeType(string $field_type): string
        {
            $type_map = array(
                // String types.
                'text'        => 'string',
                'email'       => 'string',
                'url'         => 'string',
                'password'    => 'string',
                'tel'         => 'string',
                'hidden'      => 'string',
                'date'        => 'string',
                'datetime'    => 'string',
                'time'        => 'string',
                'week'        => 'string',
                'month'       => 'string',
                'textarea'    => 'string',
                'wysiwyg'     => 'string',
                'code'        => 'string',
                'color'       => 'string',
                'select'      => 'string',
                'radio'       => 'string',
                'post_select' => 'string',
                'term_select' => 'string',
                'user_select' => 'string',
                'page_select' => 'string',
                'gallery'     => 'string',
                // Number types.
                'number'      => 'number',
                'range'       => 'number',
                'image'       => 'number',
                'file'        => 'number',
                // Boolean types.
                'checkbox'    => 'boolean',
                // Array types.
                'multiselect' => 'array',
                'checkboxes'  => 'array',
                'repeater'    => 'array',
                'group'       => 'object',
            );
            return $type_map[$field_type] ?? 'string';
        }

        /**
         * Get the default value for an attribute based on field configuration.
         *
         * @since  1.0.0
         * @param  array $field The field configuration.
         * @return mixed        The default value.
         */
        private function getAttributeDefault(array $field): mixed
        {
            if (isset($field['default'])) {
                return $field['default'];
            }

            $type = $field['type'] ?? 'text';
            $attr_type = $this->getAttributeType($type);
            return match ($attr_type) {
                'number'  => 0,
                'boolean' => false,
                'array'   => array(),
                'object'  => new \stdClass(),
                default   => '',
            };
        }

        /**
         * Render a block on the frontend.
         *
         * @since  1.0.0
         * @param  string $block_id   The block identifier.
         * @param  array  $attributes The block attributes.
         * @param  string $content    The block inner content.
         * @param  array  $block_data The block configuration data.
         * @return string             The rendered block HTML.
         */
        private function renderBlock(string $block_id, array $attributes, string $content, array $block_data): string
        {
            $config = $block_data['config'];
            // Use custom render callback if provided.
            if (! empty($config['render_callback']) && is_callable($config['render_callback'])) {
                return call_user_func($config['render_callback'], $attributes, $content, $block_data);
            }

            // Use render template if provided.
            if (! empty($config['render_template']) && file_exists($config['render_template'])) {
                return $this->renderTemplate($config['render_template'], $attributes, $content, $block_data);
            }

            // Default rendering.
            return $this->renderDefaultBlock($block_id, $attributes, $block_data);
        }

        /**
         * Render a block using a template file.
         *
         * @since  1.0.0
         * @param  string $template   The template file path.
         * @param  array  $attributes The block attributes.
         * @param  string $content    The block inner content.
         * @param  array  $block_data The block configuration data.
         * @return string             The rendered block HTML.
         */
        private function renderTemplate(string $template, array $attributes, string $content, array $block_data): string
        {
            // Extract variables for template.
            $fields = $block_data['fields'];
            $config = $block_data['config'];
            ob_start();
            include $template;
            return ob_get_clean();
        }

        /**
         * Render the default block output.
         *
         * @since  1.0.0
         * @param  string $block_id   The block identifier.
         * @param  array  $attributes The block attributes.
         * @param  array  $block_data The block configuration data.
         * @return string             The rendered block HTML.
         */
        private function renderDefaultBlock(string $block_id, array $attributes, array $block_data): string
        {
            $fields = $block_data['fields'];
            $block_class = 'wp-block-' . str_replace('/', '-', $block_id);
            $html = '<div class="' . esc_attr($block_class) . '">';
            foreach ($fields as $field) {
                $field_id = $field['id'];
                // Skip layout-only fields.
                $layout_types = array('heading', 'separator', 'html', 'message');
                if (in_array($field['type'] ?? 'text', $layout_types, true)) {
                    continue;
                }

                if (! isset($attributes[$field_id])) {
                    continue;
                }

                $value = $attributes[$field_id];
                // Skip empty values.
                if ($value === '' || $value === null || $value === array()) {
                    continue;
                }

                $html .= $this->renderFieldOutput($field, $value);
            }

            $html .= '</div>';
            return $html;
        }

        /**
         * Render a single field value for block output.
         *
         * @since  1.0.0
         * @param  array $field The field configuration.
         * @param  mixed $value The field value.
         * @return string       The rendered field HTML.
         */
        private function renderFieldOutput(array $field, mixed $value): string
        {
            $type = $field['type'] ?? 'text';
            $label = $field['label'] ?? '';
            $html = '<div class="kp-wsf-block-field kp-wsf-block-field--' . esc_attr($type) . '">';
            switch ($type) {
                case 'image':
                    $image_url = wp_get_attachment_image_url((int) $value, 'large');
                    if ($image_url) {
                        $html .= sprintf('<img src="%s" alt="%s" class="kp-wsf-block-image" />', esc_url($image_url), esc_attr($label));
                    }

                    break;
                case 'file':
                    $file_url = wp_get_attachment_url((int) $value);
                    if ($file_url) {
                        $html .= sprintf('<a href="%s" class="kp-wsf-block-file" target="_blank">%s</a>', esc_url($file_url), esc_html($label ?: basename($file_url)));
                    }

                    break;
                case 'gallery':
                    $ids = is_array($value) ? $value : explode(',', (string) $value);
                    $ids = array_filter(array_map('absint', $ids));
                    if (! empty($ids)) {
                        $html .= '<div class="kp-wsf-block-gallery">';
                        foreach ($ids as $id) {
                            $image_url = wp_get_attachment_image_url($id, 'medium');
                            if ($image_url) {
                                $html .= sprintf('<img src="%s" alt="" class="kp-wsf-block-gallery-image" />', esc_url($image_url));
                            }
                        }
                        $html .= '</div>';
                    }

                    break;
                case 'wysiwyg':
                    $html .= '<div class="kp-wsf-block-content">' . wp_kses_post($value) . '</div>';

                    break;
                case 'url':
                    $html .= sprintf('<a href="%s" class="kp-wsf-block-link">%s</a>', esc_url($value), esc_html($label ?: $value));

                    break;
                case 'email':
                    $html .= sprintf('<a href="mailto:%s" class="kp-wsf-block-email">%s</a>', esc_attr($value), esc_html($value));

                    break;
                case 'checkbox':
                    $html .= sprintf('<span class="kp-wsf-block-checkbox">%s</span>', $value ? esc_html__('Yes', 'kp-wsf') : esc_html__('No', 'kp-wsf'));

                    break;
                case 'color':
                    $html .= sprintf('<span class="kp-wsf-block-color" style="background-color: %s;"></span>', esc_attr($value));

                    break;
                case 'multiselect':
                case 'checkboxes':
                    if (is_array($value) && ! empty($value)) {
                        $html .= '<ul class="kp-wsf-block-list">';
                        foreach ($value as $item) {
                            $html .= '<li>' . esc_html($item) . '</li>';
                        }
                        $html .= '</ul>';
                    }

                    break;
                case 'repeater':
                    if (is_array($value) && ! empty($value)) {
                        $html .= '<div class="kp-wsf-block-repeater">';
                        foreach ($value as $row) {
                            $html .= '<div class="kp-wsf-block-repeater-row">';
                            if (is_array($row)) {
                                foreach ($row as $sub_key => $sub_value) {
                                    $html .= sprintf('<span class="kp-wsf-block-repeater-item">%s</span>', esc_html(is_array($sub_value) ? implode(', ', $sub_value) : $sub_value));
                                }
                            }
                            $html .= '</div>';
                        }
                        $html .= '</div>';
                    }

                    break;
                case 'post_select':
                case 'page_select':
                    $post = get_post((int) $value);
                    if ($post) {
                        $html .= sprintf('<a href="%s" class="kp-wsf-block-post-link">%s</a>', esc_url(get_permalink($post)), esc_html($post->post_title));
                    }

                    break;
                case 'term_select':
                    $term = get_term((int) $value);
                    if ($term && ! is_wp_error($term)) {
                        $html .= sprintf('<a href="%s" class="kp-wsf-block-term-link">%s</a>', esc_url(get_term_link($term)), esc_html($term->name));
                    }

                    break;
                case 'user_select':
                    $user = get_user_by('ID', (int) $value);
                    if ($user) {
                        $html .= sprintf('<span class="kp-wsf-block-user">%s</span>', esc_html($user->display_name));
                    }

                    break;
                default:
                    // Text-based fields.

                    $html .= sprintf('<span class="kp-wsf-block-text">%s</span>', esc_html((string) $value));

                    break;
            }

            $html .= '</div>';
            return $html;
        }

        /**
         * Enqueue block editor assets.
         *
         * @since  1.0.0
         * @return void
         */
        public function enqueueEditorAssets(): void
        {
            if (empty($this->blocks)) {
                return;
            }

            // Generate inline script for block registration.
            $script = $this->generateEditorScript();
            wp_register_script('kp-wsf-block-editor', '', array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'), Framework::VERSION, true);
            wp_add_inline_script('kp-wsf-block-editor', $script);
            wp_enqueue_script('kp-wsf-block-editor');
        }

        /**
         * Generate the editor script for block registration.
         *
         * @since  1.0.0
         * @return string The JavaScript code.
         */
        private function generateEditorScript(): string
        {
            $script = '(function(wp) {';
            $script .= 'const { registerBlockType } = wp.blocks;';
            $script .= 'const { createElement: el, Fragment } = wp.element;';
            $script .= 'const { InspectorControls, useBlockProps } = wp.blockEditor;';
            $script .= 'const { PanelBody, TextControl, TextareaControl, ToggleControl, SelectControl, RangeControl, ColorPicker } = wp.components;';
            $script .= 'const { __ } = wp.i18n;';
            foreach ($this->blocks as $block_id => $block_data) {
                $script .= $this->generateBlockRegistration($block_id, $block_data);
            }

            $script .= '})(window.wp);';
            return $script;
        }

        /**
         * Generate JavaScript for a single block registration.
         *
         * @since  1.0.0
         * @param  string $block_id   The block identifier.
         * @param  array  $block_data The block configuration data.
         * @return string             The JavaScript code.
         */
        private function generateBlockRegistration(string $block_id, array $block_data): string
        {
            $config = $block_data['config'];
            $fields = $block_data['fields'];
            // Build attributes JSON.
            $attributes = $this->buildAttributes($fields);
            $attributes_json = wp_json_encode($attributes);
            // Build edit function.
            $edit_function = $this->generateEditFunction($fields);
            $script = sprintf(
                'registerBlockType("%s", {
                    apiVersion: 3,
                    title: %s,
                    description: %s,
                    category: %s,
                    icon: %s,
                    keywords: %s,
                    supports: %s,
                    attributes: %s,
                    edit: %s,
                    save: function() { return null; }
                });',
                esc_js($block_id),
                wp_json_encode($config['title']),
                wp_json_encode($config['description']),
                wp_json_encode($config['category']),
                wp_json_encode($config['icon']),
                wp_json_encode($config['keywords']),
                wp_json_encode($config['supports']),
                $attributes_json,
                $edit_function
            );
            return $script;
        }

        /**
         * Generate the edit function for a block.
         *
         * @since  1.0.0
         * @param  array $fields The field configurations.
         * @return string        The JavaScript edit function.
         */
        private function generateEditFunction(array $fields): string
        {
            $controls = $this->generateInspectorControls($fields);
            $preview = $this->generateBlockPreview($fields);
            return sprintf(
                'function(props) {
                    const { attributes, setAttributes } = props;
                    const blockProps = useBlockProps();
                    
                    return el(Fragment, {},
                        el(InspectorControls, {},
                            el(PanelBody, { title: __("Settings", "kp-wsf"), initialOpen: true },
                                %s
                            )
                        ),
                        el("div", blockProps,
                            %s
                        )
                    );
                }',
                $controls,
                $preview
            );
        }

        /**
         * Generate inspector controls for fields.
         *
         * @since  1.0.0
         * @param  array $fields The field configurations.
         * @return string        The JavaScript controls array.
         */
        private function generateInspectorControls(array $fields): string
        {
            $controls = array();
            foreach ($fields as $field) {
                $control = $this->generateFieldControl($field);
                if ($control) {
                    $controls[] = $control;
                }
            }

            return implode(",\n", $controls);
        }

        /**
         * Generate a single field control.
         *
         * @since  1.0.0
         * @param  array $field The field configuration.
         * @return string|null  The JavaScript control or null.
         */
        private function generateFieldControl(array $field): ?string
        {
            $type = $field['type'] ?? 'text';
            $id = $field['id'];
            $label = $field['label'] ?? '';
            $description = $field['description'] ?? '';
            // Skip layout-only fields.
            $layout_types = array('heading', 'separator', 'html', 'message');
            if (in_array($type, $layout_types, true)) {
                return null;
            }

            $label_json = wp_json_encode($label);
            $help_json = wp_json_encode($description);
            switch ($type) {
                case 'text':
                case 'email':
                case 'url':
                case 'tel':
                case 'password':
                case 'date':
                case 'datetime':
                case 'time':
                    return sprintf(
                        'el(TextControl, {
                            label: %s,
                            help: %s,
                            value: attributes.%s || "",
                            onChange: function(value) { setAttributes({ %s: value }); }
                        })',
                        $label_json,
                        $help_json,
                        $id,
                        $id
                    );
                case 'textarea':
                case 'code':
                    return sprintf(
                        'el(TextareaControl, {
                            label: %s,
                            help: %s,
                            value: attributes.%s || "",
                            onChange: function(value) { setAttributes({ %s: value }); }
                        })',
                        $label_json,
                        $help_json,
                        $id,
                        $id
                    );
                case 'number':
                    $min = $field['min'] ?? 0;
                    $max = $field['max'] ?? 100;
                    $step = $field['step'] ?? 1;

                    return sprintf(
                        'el(TextControl, {
                            label: %s,
                            help: %s,
                            type: "number",
                            min: %d,
                            max: %d,
                            step: %s,
                            value: attributes.%s || 0,
                            onChange: function(value) { setAttributes({ %s: parseInt(value, 10) || 0 }); }
                        })',
                        $label_json,
                        $help_json,
                        $min,
                        $max,
                        $step,
                        $id,
                        $id
                    );
                case 'range':
                    $min = $field['min'] ?? 0;
                    $max = $field['max'] ?? 100;
                    $step = $field['step'] ?? 1;

                    return sprintf(
                        'el(RangeControl, {
                            label: %s,
                            help: %s,
                            value: attributes.%s || 0,
                            min: %d,
                            max: %d,
                            step: %s,
                            onChange: function(value) { setAttributes({ %s: value }); }
                        })',
                        $label_json,
                        $help_json,
                        $id,
                        $min,
                        $max,
                        $step,
                        $id
                    );
                case 'checkbox':
                    return sprintf(
                        'el(ToggleControl, {
                            label: %s,
                            help: %s,
                            checked: !!attributes.%s,
                            onChange: function(value) { setAttributes({ %s: value }); }
                        })',
                        $label_json,
                        $help_json,
                        $id,
                        $id
                    );
                case 'select':
                case 'radio':
                case 'post_select':
                case 'page_select':
                case 'term_select':
                case 'user_select':
                    $options = $this->getFieldOptionsForJs($field);

                    return sprintf(
                        'el(SelectControl, {
                            label: %s,
                            help: %s,
                            value: attributes.%s || "",
                            options: %s,
                            onChange: function(value) { setAttributes({ %s: value }); }
                        })',
                        $label_json,
                        $help_json,
                        $id,
                        $options,
                        $id
                    );
                case 'color':
                    return sprintf(
                        'el("div", { className: "kp-wsf-color-control" },
                            el("label", {}, %s),
                            el(ColorPicker, {
                                color: attributes.%s || "",
                                onChangeComplete: function(value) { setAttributes({ %s: value.hex }); }
                            })
                        )',
                        $label_json,
                        $id,
                        $id
                    );
                default:
                    // Fallback to text control.

                    return sprintf(
                        'el(TextControl, {
                            label: %s,
                            help: %s,
                            value: attributes.%s || "",
                            onChange: function(value) { setAttributes({ %s: value }); }
                        })',
                        $label_json,
                        $help_json,
                        $id,
                        $id
                    );
            }
        }

        /**
         * Get field options formatted for JavaScript.
         *
         * @since  1.0.0
         * @param  array $field The field configuration.
         * @return string       JSON-encoded options array.
         */
        private function getFieldOptionsForJs(array $field): string
        {
            $options = array(
                array(
                    'label' => __('— Select —', 'kp-wsf'),
                    'value' => '',
                ),
            );
            if (! empty($field['options'])) {
                foreach ($field['options'] as $value => $label) {
                    $options[] = array(
                        'label' => $label,
                        'value' => (string) $value,
                    );
                }
            }

            return wp_json_encode($options);
        }

        /**
         * Generate block preview for the editor.
         *
         * @since  1.0.0
         * @param  array $fields The field configurations.
         * @return string        The JavaScript preview elements.
         */
        private function generateBlockPreview(array $fields): string
        {
            $previews = array();
            foreach ($fields as $field) {
                $type = $field['type'] ?? 'text';
                $id = $field['id'];
                $label = $field['label'] ?? $id;
                // Skip layout-only fields.
                $layout_types = array('heading', 'separator', 'html', 'message');
                if (in_array($type, $layout_types, true)) {
                    continue;
                }

                $label_json = wp_json_encode($label . ': ');
                switch ($type) {
                    case 'checkbox':
                        $previews[] = sprintf('el("p", { key: "%s" }, %s, attributes.%s ? __("Yes", "kp-wsf") : __("No", "kp-wsf"))', $id, $label_json, $id);

                        break;
                    case 'color':
                        $previews[] = sprintf('el("p", { key: "%s" }, %s, el("span", { style: { backgroundColor: attributes.%s, display: "inline-block", width: "20px", height: "20px", verticalAlign: "middle" } }))', $id, $label_json, $id);

                        break;
                    default:
                        $previews[] = sprintf('el("p", { key: "%s" }, %s, attributes.%s || "")', $id, $label_json, $id);

                        break;
                }
            }

            if (empty($previews)) {
                return 'el("p", {}, __("No fields configured", "kp-wsf"))';
            }

            return implode(",\n", $previews);
        }
    }
}
