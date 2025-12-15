<?php

/**
 * Loader - Autoloader and initialization helper
 *
 * Provides PSR-4 compatible autoloading for the framework classes
 * and helper methods for bootstrapping the framework in themes or plugins.
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
 * Class Loader
 *
 * Handles autoloading and provides static helper methods for
 * initializing the framework without Composer's autoloader.
 *
 * @since 1.0.0
 */
final class Loader
{
    /**
     * Whether the autoloader has been registered.
     *
     * @since 1.0.0
     * @var bool
     */
    private static bool $autoloader_registered = false;
    /**
     * The namespace prefix for framework classes.
     *
     * @since 1.0.0
     * @var string
     */
    private const NAMESPACE_PREFIX = 'KP\\WPStarterFramework\\';
    /**
     * Register the PSR-4 autoloader.
     *
     * This method allows the framework to be used without Composer's autoloader.
     * It registers a custom autoloader that maps the namespace to the src directory.
     *
     * @since  1.0.0
     * @return void
     */
    public static function register(): void
    {
        // Prevent multiple registrations.
        if (self::$autoloader_registered) {
            return;
        }

        spl_autoload_register(array( self::class, 'autoload' ));
        self::$autoloader_registered = true;
    }

    /**
     * Autoload callback for SPL autoloader.
     *
     * Maps fully qualified class names to file paths following PSR-4 conventions.
     *
     * @since  1.0.0
     * @param  string $class The fully qualified class name.
     * @return void
     */
    public static function autoload(string $class): void
    {
        // Check if the class uses our namespace prefix.
        $prefix_length = strlen(self::NAMESPACE_PREFIX);
        if (strncmp(self::NAMESPACE_PREFIX, $class, $prefix_length) !== 0) {
            // Not our namespace, bail out.
            return;
        }

        // Get the relative class name (without namespace prefix).
        $relative_class = substr($class, $prefix_length);
        // Build the file path.
        // Replace namespace separators with directory separators.
        $file = dirname(__DIR__) . '/src/' . str_replace('\\', '/', $relative_class) . '.php';
        // If the file exists, require it.
        if (file_exists($file)) {
            require_once $file;
        }
    }

    /**
     * Bootstrap the framework with automatic configuration.
     *
     * This is a convenience method that handles common initialization tasks:
     * - Registers the autoloader if needed
     * - Detects asset paths based on context (plugin/theme)
     * - Initializes the Framework singleton
     *
     * @since  1.0.0
     * @param  array $options Optional configuration options.
     *                        - 'assets_url': Override the auto-detected assets URL.
     *                        - 'assets_path': Override the auto-detected assets path.
     * @return Framework      The initialized Framework instance.
     */
    public static function bootstrap(array $options = array()): Framework
    {
        // Register autoloader if not using Composer.
        if (! self::$autoloader_registered && ! class_exists(Framework::class, false)) {
            self::register();
        }

        // Get the Framework singleton.
        $framework = Framework::getInstance();
        // Initialize if not already done.
        if (! $framework->isInitialized()) {
            $assets_url = $options['assets_url'] ?? '';
            $assets_path = $options['assets_path'] ?? '';
            $framework->init($assets_url, $assets_path);
        }

        return $framework;
    }

    /**
     * Bootstrap the framework for use in a plugin.
     *
     * Automatically configures asset paths relative to the calling plugin's directory.
     *
     * @since  1.0.0
     * @param  string $plugin_file The main plugin file path (__FILE__ from the plugin).
     * @return Framework           The initialized Framework instance.
     */
    public static function bootstrapPlugin(string $plugin_file): Framework
    {
        $plugin_dir = plugin_dir_path($plugin_file);
        $plugin_url = plugin_dir_url($plugin_file);
        // Check if assets exist in the plugin root.
        if (is_dir($plugin_dir . 'assets')) {
            $assets_path = $plugin_dir . 'assets';
            $assets_url = $plugin_url . 'assets';
        } else {
            // Fall back to vendor directory.
            $assets_path = $plugin_dir . 'vendor/kevinpirnie/kp-wp-starter-framework/assets';
            $assets_url = $plugin_url . 'vendor/kevinpirnie/kp-wp-starter-framework/assets';
        }

        return self::bootstrap(
            array(
                'assets_url'  => $assets_url,
                'assets_path' => $assets_path,
            )
        );
    }

    /**
     * Bootstrap the framework for use in a theme.
     *
     * Automatically configures asset paths relative to the current theme directory.
     *
     * @since  1.0.0
     * @param  string $subfolder Optional subfolder within the theme for assets.
     *                           Defaults to checking common locations.
     * @return Framework         The initialized Framework instance.
     */
    public static function bootstrapTheme(string $subfolder = ''): Framework
    {
        $theme_dir = get_stylesheet_directory();
        $theme_url = get_stylesheet_directory_uri();
        // Determine assets location.
        if (! empty($subfolder)) {
            // Use specified subfolder.
            $assets_path = $theme_dir . '/' . trim($subfolder, '/') . '/assets';
            $assets_url = $theme_url . '/' . trim($subfolder, '/') . '/assets';
        } elseif (is_dir($theme_dir . '/assets/kp-wsf')) {
            // Check for dedicated framework assets folder.
            $assets_path = $theme_dir . '/assets/kp-wsf';
            $assets_url = $theme_url . '/assets/kp-wsf';
        } elseif (is_dir($theme_dir . '/vendor/kevinpirnie/kp-wp-starter-framework/assets')) {
            // Fall back to vendor directory.
            $assets_path = $theme_dir . '/vendor/kevinpirnie/kp-wp-starter-framework/assets';
            $assets_url = $theme_url . '/vendor/kevinpirnie/kp-wp-starter-framework/assets';
        } else {
            // Last resort: use theme's main assets folder.
            $assets_path = $theme_dir . '/assets';
            $assets_url = $theme_url . '/assets';
        }

        return self::bootstrap(
            array(
                'assets_url'  => $assets_url,
                'assets_path' => $assets_path,
            )
        );
    }

    /**
     * Get the framework's base directory path.
     *
     * @since  1.0.0
     * @return string The base directory path (without trailing slash).
     */
    public static function getBasePath(): string
    {
        return dirname(__DIR__);
    }

    /**
     * Get the framework's src directory path.
     *
     * @since  1.0.0
     * @return string The src directory path (without trailing slash).
     */
    public static function getSrcPath(): string
    {
        return __DIR__;
    }

    /**
     * Check if all required dependencies are available.
     *
     * Verifies that WordPress is loaded and meets minimum version requirements.
     *
     * @since  1.0.0
     * @param  string $min_wp_version  Minimum WordPress version required.
     * @param  string $min_php_version Minimum PHP version required.
     * @return array                   Array with 'valid' bool and 'errors' array.
     */
    public static function checkRequirements(string $min_wp_version = '6.8', string $min_php_version = '8.2'): array
    {
        $errors = array();
        // Check PHP version.
        if (version_compare(PHP_VERSION, $min_php_version, '<')) {
            $errors[] = sprintf('KP WP Starter Framework requires PHP %s or higher. You are running PHP %s.', $min_php_version, PHP_VERSION);
        }

        // Check if WordPress is loaded.
        if (! function_exists('get_bloginfo')) {
            $errors[] = 'KP WP Starter Framework requires WordPress to be loaded.';
        } else {
            // Check WordPress version.
            $wp_version = get_bloginfo('version');
            if (version_compare($wp_version, $min_wp_version, '<')) {
                $errors[] = sprintf('KP WP Starter Framework requires WordPress %s or higher. You are running WordPress %s.', $min_wp_version, $wp_version);
            }
        }

        return array(
            'valid'  => empty($errors),
            'errors' => $errors,
        );
    }

    /**
     * Display admin notice for requirement errors.
     *
     * Call this method if checkRequirements() returns errors to show
     * a helpful message in the WordPress admin.
     *
     * @since  1.0.0
     * @param  array $errors Array of error messages to display.
     * @return void
     */
    public static function displayRequirementErrors(array $errors): void
    {
        add_action(
            'admin_notices',
            function () use ($errors) {

                echo '<div class="notice notice-error">';
                echo '<p><strong>KP WP Starter Framework Error:</strong></p>';
                echo '<ul>';
                foreach ($errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
        );
    }

    /**
     * Quick initialization with requirements check.
     *
     * Combines requirements checking with bootstrap for a simple one-liner initialization.
     * Returns null if requirements are not met (and displays admin notice).
     *
     * @since  1.0.0
     * @param  array $options Optional configuration options passed to bootstrap().
     * @return Framework|null The Framework instance or null if requirements not met.
     */
    public static function init(array $options = array()): ?Framework
    {
        $requirements = self::checkRequirements();
        if (! $requirements['valid']) {
            self::displayRequirementErrors($requirements['errors']);
            return null;
        }

        return self::bootstrap($options);
    }
}
