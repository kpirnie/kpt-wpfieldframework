<?php

/**
 * ExportImport - Settings export and import handler
 *
 * Provides functionality to export and import settings
 * created with the framework's options pages.
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
if (! class_exists('\KP\WPFieldFramework\ExportImport')) {

    /**
     * Class ExportImport
     *
     * Handles export and import of framework settings.
     *
     * @since 1.0.0
     */
    class ExportImport
    {
        /**
         * Storage instance.
         *
         * @since 1.0.0
         * @var Storage
         */
        private Storage $storage;

        /**
         * Constructor.
         *
         * @since 1.0.0
         * @param Storage $storage Storage instance.
         */
        public function __construct(Storage $storage)
        {
            $this->storage = $storage;
        }

        /**
         * Export settings to JSON with defaults.
         *
         * @since  1.0.0
         * @param  array $options_pages Array of OptionsPage instances.
         * @return string               JSON encoded settings.
         */
        public function exportWithDefaults(array $options_pages): string
        {
            $export_data = [
                'version'    => Framework::VERSION,
                'exported'   => current_time('c'),
                'site_url'   => get_site_url(),
                'settings'   => [],
            ];

            foreach ($options_pages as $page) {
                $option_key = $page->getOptionKey();
                $stored_values = $this->storage->getOption($option_key, []);
                $all_fields = $page->getAllFields();

                // Merge stored values with defaults.
                $merged_values = [];
                foreach ($all_fields as $field) {
                    $field_id = $field['id'];
                    if (isset($stored_values[$field_id])) {
                        $merged_values[$field_id] = $stored_values[$field_id];
                    } elseif (isset($field['default'])) {
                        $merged_values[$field_id] = $field['default'];
                    }
                }

                // Also include any stored values not in field definitions (legacy/custom).
                foreach ($stored_values as $key => $value) {
                    if (!isset($merged_values[$key])) {
                        $merged_values[$key] = $value;
                    }
                }

                if (!empty($merged_values)) {
                    $export_data['settings'][$option_key] = $merged_values;
                }
            }

            return wp_json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        /**
         * Export settings to JSON.
         *
         * @since  1.0.0
         * @param  array $option_keys Array of option keys to export.
         * @return string             JSON encoded settings.
         */
        public function export(array $option_keys): string
        {
            $export_data = [
                'version'    => Framework::VERSION,
                'exported'   => current_time('c'),
                'site_url'   => get_site_url(),
                'settings'   => [],
            ];

            foreach ($option_keys as $option_key) {
                $value = $this->storage->getOption($option_key, []);
                if (!empty($value)) {
                    $export_data['settings'][$option_key] = $value;
                }
            }

            return wp_json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        /**
         * Export settings and trigger download.
         *
         * @since  1.0.0
         * @param  array  $option_keys Array of option keys to export.
         * @param  string $filename    Optional filename for download.
         * @return void
         */
        public function exportDownload(array $option_keys, string $filename = ''): void
        {
            if (empty($filename)) {
                $filename = 'settings-export-' . date('Y-m-d-His') . '.json';
            }

            $json = $this->export($option_keys);

            nocache_headers();
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($json));

            echo $json;
            exit;
        }

        /**
         * Import settings from JSON.
         *
         * @since  1.0.0
         * @param  string $json            JSON encoded settings.
         * @param  array  $allowed_options Optional whitelist of option keys to import.
         * @return array                   Result with 'success', 'imported', and 'errors'.
         */
        public function import(string $json, array $allowed_options = []): array
        {
            $result = [
                'success'  => false,
                'imported' => [],
                'errors'   => [],
            ];

            // Decode JSON.
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $result['errors'][] = __('Invalid JSON format.', 'kp-wsf');
                return $result;
            }

            // Validate structure.
            if (!isset($data['settings']) || !is_array($data['settings'])) {
                $result['errors'][] = __('Invalid export file structure.', 'kp-wsf');
                return $result;
            }

            // Import each setting.
            foreach ($data['settings'] as $option_key => $option_value) {
                // Check whitelist if provided.
                if (!empty($allowed_options) && !in_array($option_key, $allowed_options, true)) {
                    $result['errors'][] = sprintf(
                        __('Option "%s" is not allowed for import.', 'kp-wsf'),
                        $option_key
                    );
                    continue;
                }

                // Sanitize option key.
                $option_key = sanitize_key($option_key);
                if (empty($option_key)) {
                    continue;
                }

                // Update option.
                $updated = $this->storage->updateOption($option_key, $option_value);
                if ($updated) {
                    $result['imported'][] = $option_key;
                } else {
                    // Check if value is the same (no update needed).
                    $current = $this->storage->getOption($option_key, []);
                    if ($current === $option_value) {
                        $result['imported'][] = $option_key;
                    } else {
                        $result['errors'][] = sprintf(
                            __('Failed to import option "%s".', 'kp-wsf'),
                            $option_key
                        );
                    }
                }
            }

            $result['success'] = !empty($result['imported']);
            return $result;
        }

        /**
         * Import settings from uploaded file.
         *
         * @since  1.0.0
         * @param  array $file            $_FILES array element.
         * @param  array $allowed_options Optional whitelist of option keys.
         * @return array                  Result array.
         */
        public function importFromFile(array $file, array $allowed_options = []): array
        {
            $result = [
                'success'  => false,
                'imported' => [],
                'errors'   => [],
            ];

            // Validate file upload.
            if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
                $result['errors'][] = __('No file uploaded.', 'kp-wsf');
                return $result;
            }

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $result['errors'][] = $this->getUploadErrorMessage($file['error']);
                return $result;
            }

            // Validate file type.
            $file_type = wp_check_filetype($file['name'], ['json' => 'application/json']);
            if ($file_type['ext'] !== 'json') {
                $result['errors'][] = __('Invalid file type. Please upload a JSON file.', 'kp-wsf');
                return $result;
            }

            // Read file contents.
            $json = file_get_contents($file['tmp_name']);
            if ($json === false) {
                $result['errors'][] = __('Failed to read uploaded file.', 'kp-wsf');
                return $result;
            }

            return $this->import($json, $allowed_options);
        }

        /**
         * Get upload error message.
         *
         * @since  1.0.0
         * @param  int $error_code PHP upload error code.
         * @return string          Error message.
         */
        private function getUploadErrorMessage(int $error_code): string
        {
            return match ($error_code) {
                UPLOAD_ERR_INI_SIZE   => __('File exceeds upload_max_filesize.', 'kp-wsf'),
                UPLOAD_ERR_FORM_SIZE  => __('File exceeds MAX_FILE_SIZE.', 'kp-wsf'),
                UPLOAD_ERR_PARTIAL    => __('File was only partially uploaded.', 'kp-wsf'),
                UPLOAD_ERR_NO_FILE    => __('No file was uploaded.', 'kp-wsf'),
                UPLOAD_ERR_NO_TMP_DIR => __('Missing temporary folder.', 'kp-wsf'),
                UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk.', 'kp-wsf'),
                UPLOAD_ERR_EXTENSION  => __('Upload stopped by extension.', 'kp-wsf'),
                default               => __('Unknown upload error.', 'kp-wsf'),
            };
        }

        /**
         * Validate import data without importing.
         *
         * @since  1.0.0
         * @param  string $json JSON encoded settings.
         * @return array        Validation result with 'valid', 'options', and 'errors'.
         */
        public function validate(string $json): array
        {
            $result = [
                'valid'   => false,
                'options' => [],
                'meta'    => [],
                'errors'  => [],
            ];

            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $result['errors'][] = __('Invalid JSON format.', 'kp-wsf');
                return $result;
            }

            if (!isset($data['settings']) || !is_array($data['settings'])) {
                $result['errors'][] = __('Invalid export file structure.', 'kp-wsf');
                return $result;
            }

            $result['valid'] = true;
            $result['options'] = array_keys($data['settings']);
            $result['meta'] = [
                'version'  => $data['version'] ?? 'unknown',
                'exported' => $data['exported'] ?? 'unknown',
                'site_url' => $data['site_url'] ?? 'unknown',
            ];

            return $result;
        }
    }
}
