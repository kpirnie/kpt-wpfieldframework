<?php

/**
 * Framework - Main bootstrap and facade class
 *
 * Provides the primary entry point for initializing and configuring
 * the WP Starter Framework. Handles asset registration, component
 * initialization, and serves as a facade for creating options pages,
 * meta boxes, and blocks.
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
 * Class Framework
 *
 * Main entry point for the KP WP Starter Framework.
 * Implements singleton pattern to ensure single initialization.
 *
 * @since 1.0.0
 */
final class Framework
{
    /**
     * Singleton instance.
     *
     * @since 1.0.0
     * @var Framework|null
     */
    private static ?Framework $instance = null;
    /**
     * Framework version.
     *
     * @since 1.0.0
     * @var string
     */
    public const VERSION = '1.0.0';
    /**
     * Registered options pages.
     *
     * @since 1.0.0
     * @var array<string, OptionsPage>
     */
    private array $options_pages = array();
    /**
     * Registered meta boxes.
     *
     * @since 1.0.0
     * @var array<string, MetaBox>
     */
    private array $meta_boxes = array();
    /**
     * Field types registry instance.
     *
     * @since 1.0.0
     * @var FieldTypes|null
     */
    private ?FieldTypes $field_types = null;
    /**
     * Storage handler instance.
     *
     * @since 1.0.0
     * @var Storage|null
     */
    private ?Storage $storage = null;
    /**
     * Block generator instance.
     *
     * @since 1.0.0
     * @var BlockGenerator|null
     */
    private ?BlockGenerator $block_generator = null;
    /**
     * Base URL for framework assets.
     *
     * @since 1.0.0
     * @var string
     */
    private string $assets_url = '';
    /**
     * Base path for framework assets.
     *
     * @since 1.0.0
     * @var string
     */
    private string $assets_path = '';
    /**
     * Whether the framework has been initialized.
     *
     * @since 1.0.0
     * @var bool
     */
    private bool $initialized = false;
    /**
     * Private constructor to enforce singleton.
     *
     * @since 1.0.0
     */
    private function __construct()
    {
        // Intentionally empty - initialization happens in init().
    }

    /**
     * Prevent cloning of the singleton.
     *
     * @since 1.0.0
     * @return void
     */
    private function __clone(): void
    {
        // Prevent cloning.
    }

    /**
     * Prevent unserialization of the singleton.
     *
     * @since  1.0.0
     * @throws \Exception Always throws to prevent unserialization.
     * @return void
     */
    public function __wakeup(): void
    {
        throw new \Exception('Cannot unserialize singleton.');
    }

    /**
     * Get the singleton instance.
     *
     * @since  1.0.0
     * @return Framework The singleton instance.
     */
    public static function getInstance(): Framework
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize the framework.
     *
     * Must be called after WordPress has loaded, typically on 'plugins_loaded'
     * or 'after_setup_theme' hooks. Sets up assets URL/path and registers
     * necessary WordPress hooks.
     *
     * @since  1.0.0
     * @param  string $assets_url  Base URL to the assets directory.
     * @param  string $assets_path Base filesystem path to the assets directory.
     * @return Framework           The framework instance for chaining.
     */
    public function init(string $assets_url = '', string $assets_path = ''): Framework
    {
        // Prevent multiple initializations.
        if ($this->initialized) {
            return $this;
        }

        // Set assets paths, with fallback to package location.
        $this->assets_url = $assets_url ?: $this->detectAssetsUrl();
        $this->assets_path = $assets_path ?: $this->detectAssetsPath();
        // Initialize core components.
        $this->field_types = new FieldTypes();
        $this->storage = new Storage();
        $this->block_generator = new BlockGenerator($this->field_types);
        // Register WordPress hooks.
        $this->registerHooks();
        $this->initialized = true;
        return $this;
    }

    /**
     * Register WordPress hooks for the framework.
     *
     * @since  1.0.0
     * @return void
     */
    private function registerHooks(): void
    {
        // Enqueue admin assets.
        add_action('admin_enqueue_scripts', array( $this, 'enqueueAdminAssets' ));
        // Register meta boxes.
        add_action('add_meta_boxes', array( $this, 'registerMetaBoxes' ));
        // Save meta box data.
        add_action('save_post', array( $this, 'saveMetaBoxes' ), 10, 2);
        // Save user meta.
        add_action('personal_options_update', array( $this, 'saveUserMeta' ));
        add_action('edit_user_profile_update', array( $this, 'saveUserMeta' ));
        // Register options pages.
        add_action('admin_menu', array( $this, 'registerOptionsPages' ));
        // Register settings.
        add_action('admin_init', array( $this, 'registerSettings' ));
        // Initialize blocks.
        add_action('init', array( $this, 'registerBlocks' ));
        // Add user profile fields.
        add_action('show_user_profile', array( $this, 'renderUserMetaFields' ));
        add_action('edit_user_profile', array( $this, 'renderUserMetaFields' ));
        // Nav menu item custom fields.
        add_action('wp_nav_menu_item_custom_fields', array( $this, 'renderNavMenuFields' ), 10, 5);
        add_action('wp_update_nav_menu_item', array( $this, 'saveNavMenuFields' ), 10, 3);
    }

    /**
     * Detect the assets URL based on package location.
     *
     * Attempts to determine whether the package is in a plugin or theme
     * and constructs the appropriate URL.
     *
     * @since  1.0.0
     * @return string The detected assets URL.
     */
    private function detectAssetsUrl(): string
    {
        $package_dir = dirname(__DIR__);
        // Check if we're in a plugin.
        if (strpos($package_dir, WP_PLUGIN_DIR) !== false) {
            $relative = str_replace(WP_PLUGIN_DIR, '', $package_dir);
            return plugins_url($relative . '/assets');
        }

        // Check if we're in a theme.
        if (strpos($package_dir, get_theme_root()) !== false) {
            $relative = str_replace(get_theme_root(), '', $package_dir);
            return get_theme_root_uri() . $relative . '/assets';
        }

        // Fallback: assume vendor directory in plugin/theme.
        return plugins_url('vendor/kevinpirnie/kp-wp-starter-framework/assets');
    }

    /**
     * Detect the assets path based on package location.
     *
     * @since  1.0.0
     * @return string The detected assets filesystem path.
     */
    private function detectAssetsPath(): string
    {
        return dirname(__DIR__) . '/assets';
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @since  1.0.0
     * @param  string $hook_suffix The current admin page hook suffix.
     * @return void
     */
    public function enqueueAdminAssets(string $hook_suffix): void
    {
        // Only load on relevant admin pages.
        $dominated_screens = array( 'post.php', 'post-new.php', 'user-edit.php', 'profile.php', 'nav-menus.php' );
        $is_options_page = $this->isFrameworkOptionsPage($hook_suffix);
        if (! in_array($hook_suffix, $dominated_screens, true) && ! $is_options_page) {
            return;
        }

        // Enqueue WordPress media scripts for file/image uploads.
        wp_enqueue_media();
        // Enqueue WordPress link dialog.
        wp_enqueue_script('wplink');
        wp_enqueue_style('editor-buttons');
        // Enqueue WordPress color picker.
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        // Enqueue WordPress date picker.
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-datepicker-style', '//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css', array(), '1.13.2');
        // Enqueue code editor if available (WP 4.9+).
        if (function_exists('wp_enqueue_code_editor')) {
            wp_enqueue_code_editor(array( 'type' => 'text/html' ));
        }

        // Framework admin script.
        $script_path = $this->assets_path . '/js/wsf-admin.js';
        if (file_exists($script_path)) {
            wp_enqueue_script('kp-wsf-admin', $this->assets_url . '/js/wsf-admin.js', array( 'jquery', 'wp-color-picker', 'jquery-ui-datepicker', 'jquery-ui-sortable' ), self::VERSION, true);
            // Localize script with framework data.
            wp_localize_script(
                'kp-wsf-admin',
                'kpWsfAdmin',
                array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce'   => wp_create_nonce('kp_wsf_nonce'),
                    'i18n'    => array(
                        'confirmDelete' => __('Are you sure you want to remove this item?', 'kp-wsf'),
                        'mediaTitle'    => __('Select or Upload', 'kp-wsf'),
                        'mediaButton'   => __('Use this file', 'kp-wsf'),
                    ),
                )
            );
        }
    }

    /**
     * Check if current page is a framework options page.
     *
     * @since  1.0.0
     * @param  string $hook_suffix The current admin page hook suffix.
     * @return bool                True if on a framework options page.
     */
    private function isFrameworkOptionsPage(string $hook_suffix): bool
    {
        foreach ($this->options_pages as $page) {
            if (strpos($hook_suffix, $page->getMenuSlug()) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create and register an options page.
     *
     * @since  1.0.0
     * @param  array $config Configuration array for the options page.
     * @return OptionsPage   The created options page instance.
     */
    public function addOptionsPage(array $config): OptionsPage
    {
        $page = new OptionsPage($config, $this->field_types, $this->storage);
        $this->options_pages[ $page->getMenuSlug() ] = $page;
        return $page;
    }

    /**
     * Create and register a meta box.
     *
     * @since  1.0.0
     * @param  array $config Configuration array for the meta box.
     * @return MetaBox       The created meta box instance.
     */
    public function addMetaBox(array $config): MetaBox
    {
        $meta_box = new MetaBox($config, $this->field_types, $this->storage);
        $this->meta_boxes[ $meta_box->getId() ] = $meta_box;
        // Register block if configured.
        if (! empty($config['create_block']) && $config['create_block'] === true) {
            $this->block_generator->registerFromMetaBox($meta_box);
        }

        return $meta_box;
    }

    /**
     * Register all options pages with WordPress.
     *
     * Callback for 'admin_menu' hook.
     *
     * @since  1.0.0
     * @return void
     */
    public function registerOptionsPages(): void
    {
        foreach ($this->options_pages as $page) {
            $page->register();
        }
    }

    /**
     * Register settings for all options pages.
     *
     * Callback for 'admin_init' hook.
     *
     * @since  1.0.0
     * @return void
     */
    public function registerSettings(): void
    {
        foreach ($this->options_pages as $page) {
            $page->registerSettings();
        }
    }

    /**
     * Register all meta boxes with WordPress.
     *
     * Callback for 'add_meta_boxes' hook.
     *
     * @since  1.0.0
     * @return void
     */
    public function registerMetaBoxes(): void
    {
        foreach ($this->meta_boxes as $meta_box) {
            // Skip user meta boxes - they're handled separately.
            if ($meta_box->isUserMeta()) {
                continue;
            }

            // Skip nav menu meta boxes - they're handled separately.
            if ($meta_box->isNavMenu()) {
                continue;
            }

            $meta_box->register();
        }
    }

    /**
     * Save meta box data on post save.
     *
     * Callback for 'save_post' hook.
     *
     * @since  1.0.0
     * @param  int      $post_id The post ID being saved.
     * @param  \WP_Post $post    The post object.
     * @return void
     */
    public function saveMetaBoxes(int $post_id, \WP_Post $post): void
    {
        // Skip autosaves.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verify user can edit this post.
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        foreach ($this->meta_boxes as $meta_box) {
            if ($meta_box->isUserMeta() || $meta_box->isNavMenu()) {
                continue;
            }

            if (in_array($post->post_type, $meta_box->getPostTypes(), true)) {
                $meta_box->save($post_id);
            }
        }
    }

    /**
     * Save user meta fields.
     *
     * Callback for 'personal_options_update' and 'edit_user_profile_update' hooks.
     *
     * @since  1.0.0
     * @param  int $user_id The user ID being saved.
     * @return void
     */
    public function saveUserMeta(int $user_id): void
    {
        if (! current_user_can('edit_user', $user_id)) {
            return;
        }

        foreach ($this->meta_boxes as $meta_box) {
            if ($meta_box->isUserMeta()) {
                $meta_box->saveUserMeta($user_id);
            }
        }
    }

    /**
     * Render user meta fields on profile page.
     *
     * Callback for 'show_user_profile' and 'edit_user_profile' hooks.
     *
     * @since  1.0.0
     * @param  \WP_User $user The user object being edited.
     * @return void
     */
    public function renderUserMetaFields(\WP_User $user): void
    {
        foreach ($this->meta_boxes as $meta_box) {
            if ($meta_box->isUserMeta()) {
                $meta_box->renderUserFields($user);
            }
        }
    }

    /**
     * Render nav menu item custom fields.
     *
     * Callback for 'wp_nav_menu_item_custom_fields' hook.
     *
     * @since  1.0.0
     * @param  int       $item_id Menu item ID.
     * @param  \WP_Post  $item    Menu item data object.
     * @param  int       $depth   Depth of menu item.
     * @param  \stdClass $args    Menu arguments.
     * @param  int       $id      Nav menu ID.
     * @return void
     */
    public function renderNavMenuFields(int $item_id, \WP_Post $item, int $depth, \stdClass $args, int $id): void
    {
        foreach ($this->meta_boxes as $meta_box) {
            if ($meta_box->isNavMenu()) {
                $meta_box->renderNavMenuFields($item_id, $item);
            }
        }
    }

    /**
     * Save nav menu item custom fields.
     *
     * Callback for 'wp_update_nav_menu_item' hook.
     *
     * @since  1.0.0
     * @param  int   $menu_id         Nav menu ID.
     * @param  int   $menu_item_db_id Menu item database ID.
     * @param  array $args            Menu item arguments.
     * @return void
     */
    public function saveNavMenuFields(int $menu_id, int $menu_item_db_id, array $args): void
    {
        foreach ($this->meta_boxes as $meta_box) {
            if ($meta_box->isNavMenu()) {
                $meta_box->saveNavMenuMeta($menu_item_db_id);
            }
        }
    }

    /**
     * Register Gutenberg blocks.
     *
     * Callback for 'init' hook.
     *
     * @since  1.0.0
     * @return void
     */
    public function registerBlocks(): void
    {
        if ($this->block_generator !== null) {
            $this->block_generator->registerAll();
        }
    }

    /**
     * Get the field types registry.
     *
     * @since  1.0.0
     * @return FieldTypes The field types instance.
     */
    public function getFieldTypes(): FieldTypes
    {
        return $this->field_types;
    }

    /**
     * Get the storage handler.
     *
     * @since  1.0.0
     * @return Storage The storage instance.
     */
    public function getStorage(): Storage
    {
        return $this->storage;
    }

    /**
     * Get the assets URL.
     *
     * @since  1.0.0
     * @return string The assets URL.
     */
    public function getAssetsUrl(): string
    {
        return $this->assets_url;
    }

    /**
     * Get the assets path.
     *
     * @since  1.0.0
     * @return string The assets filesystem path.
     */
    public function getAssetsPath(): string
    {
        return $this->assets_path;
    }

    /**
     * Check if the framework has been initialized.
     *
     * @since  1.0.0
     * @return bool True if initialized.
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }
}
