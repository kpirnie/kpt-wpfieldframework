<?php

/**
 * MetaBox - Meta box builder
 *
 * Handles creation and registration of WordPress meta boxes
 * for posts, pages, custom post types, users, and nav menu items.
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
 * Class MetaBox
 *
 * Builds and manages WordPress meta boxes with
 * support for all field types and repeaters.
 *
 * @since 1.0.0
 */
class MetaBox
{
    /**
     * Meta box configuration.
     *
     * @since 1.0.0
     * @var array
     */
    private array $config;
    /**
     * Field types instance.
     *
     * @since 1.0.0
     * @var FieldTypes
     */
    private FieldTypes $field_types;
    /**
     * Storage instance.
     *
     * @since 1.0.0
     * @var Storage
     */
    private Storage $storage;
    /**
     * Registered fields.
     *
     * @since 1.0.0
     * @var array
     */
    private array $fields = array();
    /**
     * Default configuration values.
     *
     * @since 1.0.0
     * @var array
     */
    private array $defaults = array(
        'id'           => '',
        'title'        => 'Meta Box',
        'post_types'   => array( 'post' ),
        'context'      => 'normal',
        'priority'     => 'default',
        'fields'       => array(),
        'user_meta'    => false,
        'nav_menu'     => false,
        'create_block' => false,
        'block_config' => array(),
    );
    /**
     * Constructor.
     *
     * @since 1.0.0
     * @param array      $config      Meta box configuration array.
     * @param FieldTypes $field_types Field types instance.
     * @param Storage    $storage     Storage instance.
     */
    public function __construct(array $config, FieldTypes $field_types, Storage $storage)
    {
        $this->config = wp_parse_args($config, $this->defaults);
        $this->field_types = $field_types;
        $this->storage = $storage;
        // Ensure ID is set.
        if (empty($this->config['id'])) {
            $this->config['id'] = sanitize_key($this->config['title']);
        }

        // Process fields from config.
        if (! empty($this->config['fields'])) {
            $this->fields = $this->config['fields'];
        }
    }

    /**
     * Get the meta box ID.
     *
     * @since  1.0.0
     * @return string
     */
    public function getId(): string
    {
        return $this->config['id'];
    }

    /**
     * Get the meta box title.
     *
     * @since  1.0.0
     * @return string
     */
    public function getTitle(): string
    {
        return $this->config['title'];
    }

    /**
     * Get the post types this meta box applies to.
     *
     * @since  1.0.0
     * @return array
     */
    public function getPostTypes(): array
    {
        return (array) $this->config['post_types'];
    }

    /**
     * Get the registered fields.
     *
     * @since  1.0.0
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Get the block configuration.
     *
     * @since  1.0.0
     * @return array
     */
    public function getBlockConfig(): array
    {
        return $this->config['block_config'];
    }

    /**
     * Check if this is a user meta box.
     *
     * @since  1.0.0
     * @return bool
     */
    public function isUserMeta(): bool
    {
        return (bool) $this->config['user_meta'];
    }

    /**
     * Check if this is a nav menu meta box.
     *
     * @since  1.0.0
     * @return bool
     */
    public function isNavMenu(): bool
    {
        return (bool) $this->config['nav_menu'];
    }

    /**
     * Check if block creation is enabled.
     *
     * @since  1.0.0
     * @return bool
     */
    public function shouldCreateBlock(): bool
    {
        return (bool) $this->config['create_block'];
    }

    /**
     * Add a field to the meta box.
     *
     * @since  1.0.0
     * @param  array $field Field configuration.
     * @return self
     */
    public function addField(array $field): self
    {
        $this->fields[] = $field;
        return $this;
    }

    /**
     * Register the meta box with WordPress.
     *
     * @since  1.0.0
     * @return void
     */
    public function register(): void
    {
        foreach ($this->getPostTypes() as $post_type) {
            add_meta_box($this->config['id'], $this->config['title'], array( $this, 'render' ), $post_type, $this->config['context'], $this->config['priority']);
        }
    }

    /**
     * Render the meta box content.
     *
     * @since  1.0.0
     * @param  \WP_Post $post The current post object.
     * @return void
     */
    public function render(\WP_Post $post): void
    {
        // Output nonce field.
        wp_nonce_field($this->config['id'] . '_nonce_action', $this->config['id'] . '_nonce');
        echo '<div class="kp-wsf-meta-box">';
        foreach ($this->fields as $field) {
            // Get current value.
            $value = $this->storage->getMeta($post->ID, $field['id']);
            // Prefix field name for meta storage.
            $field['name'] = $field['id'];
            // Render field row.
            echo $this->field_types->renderRow($field, $value, 'meta');
        }

        echo '</div>';
        // Add inline styles for meta box layout.
        $this->outputInlineStyles();
    }

    /**
     * Save meta box data.
     *
     * @since  1.0.0
     * @param  int $post_id The post ID.
     * @return void
     */
    public function save(int $post_id): void
    {
        // Verify nonce.
        $nonce_name = $this->config['id'] . '_nonce';
        $nonce_action = $this->config['id'] . '_nonce_action';
        if (! isset($_POST[ $nonce_name ]) || ! wp_verify_nonce($_POST[ $nonce_name ], $nonce_action)) {
            return;
        }

        // Check for autosave.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check user permissions.
        $post_type = get_post_type($post_id);
        $post_type_obj = get_post_type_object($post_type);
        if (! current_user_can($post_type_obj->cap->edit_post, $post_id)) {
            return;
        }

        $sanitizer = new Sanitizer();
        // Save each field.
        foreach ($this->fields as $field) {
            $field_id = $field['id'];
            // Skip layout-only fields.
            $layout_types = array( 'heading', 'separator', 'html', 'message' );
            if (in_array($field['type'] ?? 'text', $layout_types, true)) {
                continue;
            }

            // Get submitted value.
            $value = $_POST[ $field_id ] ?? null;
            // Sanitize value.
            $sanitized_value = $sanitizer->sanitize($value, $field);
            // Update or delete meta.
            if ($sanitized_value !== null && $sanitized_value !== '' && $sanitized_value !== array()) {
                $this->storage->updateMeta($post_id, $field_id, $sanitized_value);
            } else {
                $this->storage->deleteMeta($post_id, $field_id);
            }
        }
    }

    /**
     * Render user profile fields.
     *
     * @since  1.0.0
     * @param  \WP_User $user The user object.
     * @return void
     */
    public function renderUserFields(\WP_User $user): void
    {
        // Output nonce field.
        wp_nonce_field($this->config['id'] . '_nonce_action', $this->config['id'] . '_nonce');
        echo '<h2>' . esc_html($this->config['title']) . '</h2>';
        echo '<table class="form-table kp-wsf-user-meta">';
        foreach ($this->fields as $field) {
            // Get current value from user meta.
            $value = $this->storage->getUserMeta($user->ID, $field['id']);
            // Prefix field name.
            $field['name'] = $field['id'];
            // Render field row in options context (table format).
            echo $this->field_types->renderRow($field, $value, 'options');
        }

        echo '</table>';
    }

    /**
     * Save user meta fields.
     *
     * @since  1.0.0
     * @param  int $user_id The user ID.
     * @return void
     */
    public function saveUserMeta(int $user_id): void
    {
        // Verify nonce.
        $nonce_name = $this->config['id'] . '_nonce';
        $nonce_action = $this->config['id'] . '_nonce_action';
        if (! isset($_POST[ $nonce_name ]) || ! wp_verify_nonce($_POST[ $nonce_name ], $nonce_action)) {
            return;
        }

        // Check user permissions.
        if (! current_user_can('edit_user', $user_id)) {
            return;
        }

        $sanitizer = new Sanitizer();
        // Save each field.
        foreach ($this->fields as $field) {
            $field_id = $field['id'];
            // Skip layout-only fields.
            $layout_types = array( 'heading', 'separator', 'html', 'message' );
            if (in_array($field['type'] ?? 'text', $layout_types, true)) {
                continue;
            }

            // Get submitted value.
            $value = $_POST[ $field_id ] ?? null;
            // Sanitize value.
            $sanitized_value = $sanitizer->sanitize($value, $field);
            // Update or delete user meta.
            if ($sanitized_value !== null && $sanitized_value !== '' && $sanitized_value !== array()) {
                $this->storage->updateUserMeta($user_id, $field_id, $sanitized_value);
            } else {
                $this->storage->deleteUserMeta($user_id, $field_id);
            }
        }
    }

    /**
     * Render nav menu item fields.
     *
     * @since  1.0.0
     * @param  int      $item_id The menu item ID.
     * @param  \WP_Post $item    The menu item post object.
     * @return void
     */
    public function renderNavMenuFields(int $item_id, \WP_Post $item): void
    {
        // Output nonce field (only once per menu).
        static $nonce_output = false;
        if (! $nonce_output) {
            wp_nonce_field($this->config['id'] . '_nonce_action', $this->config['id'] . '_nonce');
            $nonce_output = true;
        }

        echo '<div class="kp-wsf-nav-menu-fields">';
        echo '<p class="description kp-wsf-nav-menu-title">' . esc_html($this->config['title']) . '</p>';
        foreach ($this->fields as $field) {
            // Get current value from post meta (nav menu items are posts).
            $value = $this->storage->getMeta($item_id, $field['id']);
            // Prefix field name and ID with item ID for uniqueness.
            $field['id'] = $field['id'] . '_' . $item_id;
            $field['name'] = $field['id'];
            // Render field.
            echo '<div class="kp-wsf-nav-menu-field">';
            if (! empty($field['label'])) {
                echo '<label for="' . esc_attr($field['id']) . '">' . esc_html($field['label']) . '</label>';
            }
            echo $this->field_types->render($field, $value);
            if (! empty($field['description'])) {
                echo '<span class="description">' . esc_html($field['description']) . '</span>';
            }
            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * Save nav menu item fields.
     *
     * @since  1.0.0
     * @param  int $menu_item_id The menu item ID.
     * @return void
     */
    public function saveNavMenuMeta(int $menu_item_id): void
    {
        // Verify nonce.
        $nonce_name = $this->config['id'] . '_nonce';
        $nonce_action = $this->config['id'] . '_nonce_action';
        if (! isset($_POST[ $nonce_name ]) || ! wp_verify_nonce($_POST[ $nonce_name ], $nonce_action)) {
            return;
        }

        $sanitizer = new Sanitizer();
        // Save each field.
        foreach ($this->fields as $field) {
            $field_id = $field['id'] . '_' . $menu_item_id;
            $meta_key = $field['id'];
            // Skip layout-only fields.
            $layout_types = array( 'heading', 'separator', 'html', 'message' );
            if (in_array($field['type'] ?? 'text', $layout_types, true)) {
                continue;
            }

            // Get submitted value.
            $value = $_POST[ $field_id ] ?? null;
            // Sanitize value.
            $sanitized_value = $sanitizer->sanitize($value, $field);
            // Update or delete meta.
            if ($sanitized_value !== null && $sanitized_value !== '' && $sanitized_value !== array()) {
                $this->storage->updateMeta($menu_item_id, $meta_key, $sanitized_value);
            } else {
                $this->storage->deleteMeta($menu_item_id, $meta_key);
            }
        }
    }

    /**
     * Get meta value for a post.
     *
     * @since  1.0.0
     * @param  int    $post_id  The post ID.
     * @param  string $field_id The field ID.
     * @param  mixed  $default  Default value if not set.
     * @return mixed
     */
    public function getMeta(int $post_id, string $field_id, mixed $default = null): mixed
    {
        $value = $this->storage->getMeta($post_id, $field_id);
        return $value !== '' ? $value : $default;
    }

    /**
     * Update meta value for a post.
     *
     * @since  1.0.0
     * @param  int    $post_id  The post ID.
     * @param  string $field_id The field ID.
     * @param  mixed  $value    The value to set.
     * @return bool
     */
    public function updateMeta(int $post_id, string $field_id, mixed $value): bool
    {
        return $this->storage->updateMeta($post_id, $field_id, $value);
    }

    /**
     * Delete meta value for a post.
     *
     * @since  1.0.0
     * @param  int    $post_id  The post ID.
     * @param  string $field_id The field ID.
     * @return bool
     */
    public function deleteMeta(int $post_id, string $field_id): bool
    {
        return $this->storage->deleteMeta($post_id, $field_id);
    }

    /**
     * Output inline styles for meta box layout.
     *
     * @since  1.0.0
     * @return void
     */
    private function outputInlineStyles(): void
    {
        static $styles_output = false;
        if ($styles_output) {
            return;
        }

        $styles_output = true;

        ?>
        <style>
            .kp-wsf-meta-box {
                padding: 12px 0;
            }
            .kp-wsf-field {
                margin-bottom: 15px;
                display: flex;
                flex-wrap: wrap;
                align-items: flex-start;
            }
            .kp-wsf-field__label {
                flex: 0 0 200px;
                padding-right: 10px;
                padding-top: 5px;
                font-weight: 600;
            }
            .kp-wsf-field__label label {
                display: block;
            }
            .kp-wsf-field__input {
                flex: 1;
                min-width: 300px;
            }
            .kp-wsf-field__input .description {
                margin-top: 5px;
                color: #646970;
            }
            .kp-wsf-field .required {
                color: #d63638;
            }
            .kp-wsf-heading {
                margin: 20px 0 10px;
                padding-bottom: 5px;
                border-bottom: 1px solid #c3c4c7;
            }
            .kp-wsf-separator {
                margin: 20px 0;
                border: 0;
                border-top: 1px solid #c3c4c7;
            }
            .kp-wsf-image-preview img,
            .kp-wsf-gallery-item img {
                max-width: 150px;
                height: auto;
                border: 1px solid #c3c4c7;
                padding: 3px;
                background: #fff;
            }
            .kp-wsf-gallery-preview {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 10px;
            }
            .kp-wsf-gallery-item {
                position: relative;
            }
            .kp-wsf-gallery-remove {
                position: absolute;
                top: -5px;
                right: -5px;
                width: 20px;
                height: 20px;
                border-radius: 50%;
                background: #d63638;
                color: #fff;
                border: none;
                cursor: pointer;
                font-size: 14px;
                line-height: 1;
                padding: 0;
            }
            .kp-wsf-range-field {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .kp-wsf-range-value {
                min-width: 40px;
                text-align: center;
                font-weight: 600;
            }
            .kp-wsf-nav-menu-fields {
                padding: 10px 0;
                border-top: 1px solid #eee;
                margin-top: 10px;
            }
            .kp-wsf-nav-menu-title {
                font-weight: 600;
                margin-bottom: 10px;
            }
            .kp-wsf-nav-menu-field {
                margin-bottom: 10px;
            }
            .kp-wsf-nav-menu-field label {
                display: block;
                margin-bottom: 3px;
            }
            .kp-wsf-nav-menu-field .description {
                display: block;
                margin-top: 3px;
                font-size: 12px;
                color: #646970;
            }
            .kp-wsf-group {
                border: 1px solid #c3c4c7;
                padding: 15px;
                margin-bottom: 15px;
                background: #f6f7f7;
            }
            .kp-wsf-group-title {
                margin: 0 0 15px;
                padding-bottom: 10px;
                border-bottom: 1px solid #c3c4c7;
            }
            .kp-wsf-error {
                color: #d63638;
                font-style: italic;
            }
            .kp-wsf-link-field input.kp-wsf-link-url,
            .kp-wsf-link-field input.kp-wsf-link-title {
                display: block;
                margin-bottom: 8px;
            }
            .kp-wsf-link-field .button {
                margin-bottom: 8px;
            }
            .kp-wsf-link-target-label {
                display: block;
                margin-top: 5px;
            }
        </style>
        <?php
    }
}
