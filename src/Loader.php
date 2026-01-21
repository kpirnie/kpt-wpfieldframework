<?php

/**
 * Loader - Autoloader and initialization helper
 *
 * Provides PSR-4 compatible autoloading for the framework classes
 * and helper methods for bootstrapping the framework in themes or plugins.
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
if (! class_exists('\KP\WPFieldFramework\Loader')) {

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
        private const NAMESPACE_PREFIX = 'KP\\WPFieldFramework\\';

        /**
         * Get the framework's base directory path.
         *
         * @since 1.0.0
         */
        public static function basePath(): string
        {
            return dirname(__DIR__);
        }

        /**
         * Get the framework's src directory path.
         *
         * @since 1.0.0
         */
        public static function srcPath(): string
        {
            return __DIR__;
        }

        /**
         * Get the framework's base URL.
         *
         * @since 1.0.0
         */
        public static function baseUrl(): string
        {
            return self::pathToUrl(self::basePath());
        }

        /**
         * Get the framework's source URL.
         *
         * @since 1.0.0
         */
        public static function srcUrl(): string
        {
            return self::pathToUrl(self::srcPath());
        }

        /**
         * Initialize the framework with automatic configuration.
         *
         * This is a convenience method that handles common initialization tasks:
         * - Registers the autoloader if needed
         * - Detects asset paths based on context (plugin/theme)
         * - Initializes the Framework singleton
         *
         * @since  1.0.0
         * @return Framework      The initialized Framework instance.
         */
        public static function init(): Framework
        {

        // make sure our requirements are met
            $requirements = self::checkRequirements();
            if (!$requirements['valid']) {
                self::displayRequirementErrors($requirements['errors']);
                return null;
            }

            // Register autoloader if not using Composer.
            if (!self::$autoloader_registered && !class_exists(Framework::class, false)) {
                self::register();
            }

            // Get the Framework singleton.
            $framework = Framework::getInstance();

            // Initialize if not already done.
            if (!$framework->isInitialized()) {
                $assets_path = self::basePath() . '/assets';
                $assets_url = self::baseUrl() . '/assets';

                $framework->init($assets_url, $assets_path);
            }

            return $framework;
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
            $file = self::srcPath() . '/' . str_replace('\\', '/', $relative_class) . '.php';

            // If the file exists, require it.
            if (file_exists($file)) {
                require_once $file;
            }
        }

        /**
         * Register the PSR-4 autoloader.
         *
         * This method allows the framework to be used without Composer's autoloader.
         * It registers a custom autoloader that maps the namespace to the src directory.
         *
         * @since  1.0.0
         * @return void
         */
        private static function register(): void
        {
            // Prevent multiple registrations.
            if (self::$autoloader_registered) {
                return;
            }

            spl_autoload_register(array( self::class, 'autoload' ));
            self::$autoloader_registered = true;
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
        private static function checkRequirements(string $min_wp_version = '6.8', string $min_php_version = '8.4'): array
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
        private static function displayRequirementErrors(array $errors): void
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
         * Convert a filesystem path to a URL.
         *
         * @since  1.0.0
         * @param  string $path The filesystem path.
         * @return string       The URL.
         */
        private static function pathToUrl(string $path): string
        {
            // Normalize path separators.
            $path = wp_normalize_path($path);
            $content_dir = wp_normalize_path(WP_CONTENT_DIR);
            $content_url = content_url();

            // Replace content dir with content URL.
            if (strpos($path, $content_dir) === 0) {
                return str_replace($content_dir, $content_url, $path);
            }

            // Fallback: try ABSPATH.
            $abspath = wp_normalize_path(ABSPATH);
            $site_url = site_url();

            if (strpos($path, $abspath) === 0) {
                return str_replace($abspath, $site_url . '/', $path);
            }

            return $path;
        }
    }
}
