<?php

/**
 * OptionsPage - Options page builder
 *
 * Handles creation and registration of WordPress admin options pages
 * with support for sections, tabs, and all field types.
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
if (! class_exists('\KP\WPFieldFramework\OptionsPage')) {

    /**
     * Class OptionsPage
     *
     * Builds and manages WordPress admin options pages with
     * full support for the Settings API.
     *
     * @since 1.0.0
     */
    class OptionsPage
    {
        /**
         * Page configuration.
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
         * Registered sections.
         *
         * @since 1.0.0
         * @var array
         */
        private array $sections = array();
        /**
         * Registered fields organized by section.
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
            'page_title'            => 'Options',
            'menu_title'            => 'Options',
            'capability'            => 'manage_options',
            'menu_slug'             => 'kp-wsf-options',
            'parent_slug'           => '',
            'icon_url'              => 'dashicons-admin-generic',
            'position'              => null,
            'option_name'           => '',
            'option_key'            => '',
            'show_export_import'    => false,
            'autoload'              => null,
            'tabs'                  => array(),
            'sections'              => array(),
        );
        /**
         * Constructor.
         *
         * @since 1.0.0
         * @param array      $config      Page configuration array.
         * @param FieldTypes $field_types Field types instance.
         * @param Storage    $storage     Storage instance.
         */
        public function __construct(array $config, FieldTypes $field_types, Storage $storage)
        {
            $this->config = wp_parse_args($config, $this->defaults);
            $this->field_types = $field_types;
            $this->storage = $storage;

            // Set option name from menu slug if not provided.
            if (empty($this->config['option_name'])) {
                $this->config['option_name'] = str_replace('-', '_', $this->config['menu_slug']);
            }

            // Set option key from option name if not provided.
            if (empty($this->config['option_key'])) {
                $this->config['option_key'] = $this->config['option_name'];
            }

            // Process sections and fields from config.
            $this->processSections();
        }

        /**
         * Process sections from configuration.
         *
         * @since  1.0.0
         * @return void
         */
        private function processSections(): void
        {
            // Handle tabbed interface.
            if (! empty($this->config['tabs'])) {
                foreach ($this->config['tabs'] as $tab_id => $tab) {
                    if (! empty($tab['sections'])) {
                        foreach ($tab['sections'] as $section_id => $section) {
                                $section['tab'] = $tab_id;
                                $this->addSection($section_id, $section);
                        }
                    }
                }
            }

            // Handle non-tabbed sections.
            if (! empty($this->config['sections'])) {
                foreach ($this->config['sections'] as $section_id => $section) {
                    $this->addSection($section_id, $section);
                }
            }
        }

        /**
         * Add a section to the options page.
         *
         * @since  1.0.0
         * @param  string $section_id Unique section identifier.
         * @param  array  $section    Section configuration.
         * @return self
         */
        public function addSection(string $section_id, array $section): self
        {
            $this->sections[ $section_id ] = wp_parse_args(
                $section,
                array(
                    'title'       => '',
                    'description' => '',
                    'tab'         => '',
                    'fields'      => array(),
                )
            );

            // Process fields for this section.
            if (! empty($section['fields'])) {
                foreach ($section['fields'] as $field) {
                    $this->addField($section_id, $field);
                }
            }

            return $this;
        }

        /**
         * Add a field to a section.
         *
         * @since  1.0.0
         * @param  string $section_id Section to add field to.
         * @param  array  $field      Field configuration.
         * @return self
         */
        public function addField(string $section_id, array $field): self
        {
            if (! isset($this->fields[ $section_id ])) {
                $this->fields[ $section_id ] = array();
            }

            $this->fields[ $section_id ][] = $field;
            return $this;
        }

        /**
         * Get the menu slug.
         *
         * @since  1.0.0
         * @return string
         */
        public function getMenuSlug(): string
        {
            return $this->config['menu_slug'];
        }

        /**
         * Get the option name.
         *
         * @since  1.0.0
         * @return string
         */
        public function getOptionName(): string
        {
            return $this->config['option_name'];
        }

        /**
         * Get the option key (used for storing in database).
         *
         * @since  1.0.0
         * @return string
         */
        public function getOptionKey(): string
        {
            return $this->config['option_key'];
        }

        /**
         * Register the options page with WordPress.
         *
         * @since  1.0.0
         * @return void
         */
        public function register(): void
        {
            if (! empty($this->config['parent_slug'])) {
                // Add as submenu page.
                add_submenu_page($this->config['parent_slug'], $this->config['page_title'], $this->config['menu_title'], $this->config['capability'], $this->config['menu_slug'], array( $this, 'renderPage' ));
            } else {
                // Add as top-level menu page.
                add_menu_page($this->config['page_title'], $this->config['menu_title'], $this->config['capability'], $this->config['menu_slug'], array( $this, 'renderPage' ), $this->config['icon_url'], $this->config['position']);
            }
        }

        /**
         * Register settings with WordPress Settings API.
         *
         * @since  1.0.0
         * @return void
         */
        public function registerSettings(): void
        {
            // Determine autoload value: true = 'on', false = 'off', null = 'auto'
            $autoload = $this->config['autoload'];

            register_setting(
                $this->config['menu_slug'],
                $this->config['option_key'],
                array(
                    'type'              => 'array',
                    'sanitize_callback' => array( $this, 'sanitizeOptions' ),
                    'default'           => array(),
                    'show_in_rest'      => false,
                )
            );

            // Handle autoload separately if specified
            if ($autoload !== null) {
                add_filter(
                    "pre_update_option_{$this->config['option_key']}",
                    function ($value, $old_value) use ($autoload) {
                        global $wpdb;
                        $wpdb->query($wpdb->prepare(
                            "UPDATE {$wpdb->options} SET autoload = %s WHERE option_name = %s",
                            $autoload ? 'on' : 'off',
                            $this->config['option_key']
                        ));
                        return $value;
                    },
                    10,
                    2
                );
            }

            // Register sections.
            foreach ($this->sections as $section_id => $section) {
                add_settings_section(
                    $section_id,
                    $section['title'],
                    function () use ($section) {

                        $this->renderSectionDescription($section);
                    },
                    $this->config['menu_slug']
                );

                // Register fields for this section.
                if (! empty($this->fields[ $section_id ])) {
                    foreach ($this->fields[ $section_id ] as $field) {
                        $this->registerField($section_id, $field);
                    }
                }
            }
        }

        /**
         * Register a single field with the Settings API.
         *
         * @since  1.0.0
         * @param  string $section_id Section identifier.
         * @param  array  $field      Field configuration.
         * @return void
         */
        private function registerField(string $section_id, array $field): void
        {

            $label = $field['label'] ?? '';
            if (! empty($field['sublabel'])) {
                $label .= sprintf('<br /><span class="kp-wsf-sublabel">%s</span>', wp_kses_post($field['sublabel']));
            }

            $row_class = 'kp-wsf-field-row kp-wsf-field-row--' . ( $field['type'] ?? 'text' );
            if (! empty($field['conditional'])) {
                $row_class .= ' kp-wsf-conditional-field';
            }

            add_settings_field(
                $field['id'],
                $label,
                function () use ($field) {

                    $this->renderField($field);
                },
                $this->config['menu_slug'],
                $section_id,
                array(
                    'label_for'  => $field['id'],
                    'class'      => $row_class,
                    'conditional' => ! empty($field['conditional']) ? $field['conditional'] : null,
                )
            );
        }

        /**
         * Render the options page.
         *
         * @since  1.0.0
         * @return void
         */
        public function renderPage(): void
        {
            // Check user capability.
            if (! current_user_can($this->config['capability'])) {
                return;
            }

            // Show settings saved message.
            if (isset($_GET['settings-updated'])) {
                add_settings_error($this->config['menu_slug'] . '_messages', $this->config['menu_slug'] . '_message', __('Settings Saved', 'kp-wsf'), 'updated');
            }

            ?>
            <div class="wrap kp-wsf-options-page">
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

                <?php settings_errors($this->config['menu_slug'] . '_messages'); ?>

                <?php
                if (! empty($this->config['tabs'])) :
                    ?>
                    <?php $this->renderTabs(); ?>
                    <?php
                else :
                    ?>
                    <?php $this->renderForm(); ?>
                    <?php
                endif;
                ?>
            </div>
            <?php
        }

        /**
         * Render tabbed interface.
         *
         * @since  1.0.0
         * @return void
         */
        private function renderTabs(): void
        {
            $tabs = $this->config['tabs'];
            $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : array_key_first($tabs);

            // Render tab navigation.
            echo '<nav class="nav-tab-wrapper wp-clearfix">';
            foreach ($tabs as $tab_id => $tab) {
                $active = ( $current_tab === $tab_id ) ? ' nav-tab-active' : '';
                printf('<a href="%s" class="nav-tab%s">%s</a>', esc_url(add_query_arg('tab', $tab_id)), esc_attr($active), esc_html($tab['title'] ?? $tab_id));
            }
            echo '</nav>';

            // Render tab description if present.
            if (!empty($tabs[$current_tab]['description'])) {
                printf(
                    '<p class="kp-wsf-tab-description">%s</p>',
                    wp_kses_post($tabs[$current_tab]['description'])
                );
            }

            // Render form with only current tab's sections.
            $this->renderForm($current_tab);
        }

        /**
         * Render the settings form.
         *
         * @since  1.0.0
         * @param  string $current_tab Current tab ID (optional).
         * @return void
         */
        private function renderForm(string $current_tab = ''): void
        {
            ?>
            <form action="options.php" method="post" class="kp-wsf-options-form">
                <?php
                settings_fields($this->config['menu_slug']);

                // If tabbed, only show sections for current tab.
                if (! empty($current_tab)) {
                    $this->renderTabSections($current_tab);
                } else {
                    do_settings_sections($this->config['menu_slug']);
                }

                submit_button($this->config['save_button'] ?? __('Save Settings', 'kp-wsf'));
                $this->renderExportImport();
                ?>
            </form>
            <?php

            // render the footer test, if it's set.. allows some html
            if ($this->config['footer_text']) :
                echo wp_kses($this->config['footer_text'], array(
                    'a' => array(
                        'href' => true,
                        'title' => true,
                        'target' => true,
                        'class' => true,
                    ),
                    'br' => array('class' => true,),
                    'em' => array('class' => true,),
                    'strong' => array('class' => true,),
                    'p' => array('class' => true,),
                    'div' => array('class' => true,),
                ));
            endif;
        }

        /**
         * Render sections for a specific tab.
         *
         * @since  1.0.0
         * @param  string $tab_id Tab identifier.
         * @return void
         */
        private function renderTabSections(string $tab_id): void
        {
            global $wp_settings_sections, $wp_settings_fields;
            $page = $this->config['menu_slug'];
            if (! isset($wp_settings_sections[ $page ])) {
                return;
            }

            foreach ($wp_settings_sections[ $page ] as $section_id => $section) {
                // Check if section belongs to current tab.
                if (! isset($this->sections[ $section_id ]) || $this->sections[ $section_id ]['tab'] !== $tab_id) {
                    continue;
                }

                // Render section.
                if ($section['title']) {
                    echo '<h2>' . esc_html($section['title']) . '</h2>';
                }

                // Render section.
                if (!empty($section['description'])) {
                    $this->renderSectionDescription($section);
                }

                if ($section['callback']) {
                    call_user_func($section['callback'], $section);
                }

                if (! isset($wp_settings_fields[ $page ][ $section_id ])) {
                    continue;
                }

                echo '<table class="form-table" role="presentation">';
                do_settings_fields($page, $section_id);
                echo '</table>';
            }
        }

        /**
         * Render section description.
         *
         * @since  1.0.0
         * @param  array $section Section configuration.
         * @return void
         */
        private function renderSectionDescription(array $section): void
        {
            if (! empty($section['description'])) {
                printf('<p class="description kp-wsf-description--section">%s</p>', wp_kses_post($section['description']));
            }
        }

        /**
         * Render a single field.
         *
         * @since  1.0.0
         * @param  array $field Field configuration.
         * @return void
         */
        private function renderField(array $field): void
        {

            // Output conditional data attribute if present.
            if (! empty($field['conditional'])) {
                printf('<span class="kp-wsf-conditional-data" data-kp-wsf-conditional="%s"></span>', esc_attr(wp_json_encode($field['conditional'])));
            }

            // Get current value from options.
            $options = $this->storage->getOption($this->config['option_key'], array());
            $value = $options[ $field['id'] ] ?? ( $field['default'] ?? null );

            // Set the field name to use array notation for the option.
            $field['name'] = sprintf('%s[%s]', $this->config['option_key'], $field['id']);

            // Render the field.
            echo $this->field_types->render($field, $value);

            // Render description if present.
            if (! empty($field['description']) && ! in_array($field['type'], ['group', 'accordion', 'repeater'])) {
                printf('<p class="description kp-wsf-field-description--%s">%s</p>', $field['type'], wp_kses_post($field['description']));
            }
        }

        /**
         * Sanitize all options on save.
         *
         * Merges incoming values with existing stored values to preserve
         * data from tabs that weren't submitted (tabbed interface support).
         *
         * @since  1.0.0
         * @param  mixed $input The input values to sanitize.
         * @return array        The sanitized values.
         */
        public function sanitizeOptions(mixed $input): array
        {
            if (! is_array($input)) {
                return array();
            }

            $sanitizer = new Sanitizer();

            $all_fields = array();
            foreach ($this->fields as $section_fields) {
                foreach ($section_fields as $field) {
                    $all_fields[ $field['id'] ] = $field;
                }
            }

            $current_tab_fields = array();

            if (!empty($this->config['tabs'])) {
                $existing = $this->storage->getOption($this->config['option_key'], array());
                $sanitized = is_array($existing) ? $existing : array();

                $current_tab = '';
                if (isset($_POST['_wp_http_referer'])) {
                    $referer = wp_unslash($_POST['_wp_http_referer']);
                    $query_string = wp_parse_url($referer, PHP_URL_QUERY);
                    if ($query_string) {
                        parse_str($query_string, $query_vars);
                        $current_tab = $query_vars['tab'] ?? '';
                    }
                }
                if (empty($current_tab)) {
                    $current_tab = array_key_first($this->config['tabs']);
                }

                foreach ($this->sections as $section_id => $section) {
                    if (($section['tab'] ?? '') === $current_tab) {
                        if (isset($this->fields[$section_id])) {
                            foreach ($this->fields[$section_id] as $field) {
                                $current_tab_fields[$field['id']] = true;
                            }
                        }
                    }
                }
            } else {
                $sanitized = array();
            }

            foreach ($input as $key => $value) {
                if (isset($all_fields[$key])) {
                    $sanitized[$key] = $sanitizer->sanitize($value, $all_fields[$key]);
                } else {
                    $sanitized[$key] = $sanitizer->sanitizeUnknown($value);
                }
            }

            $fields_to_check = !empty($current_tab_fields) ? $current_tab_fields : $all_fields;
            foreach ($fields_to_check as $field_id => $field_data) {
                $field = $all_fields[$field_id] ?? null;
                if ($field) {
                    $type = $field['type'] ?? 'text';
                    if (in_array($type, ['checkbox'], true) && !isset($input[$field_id])) {
                        $sanitized[$field_id] = false;
                    }
                }
            }
            return $sanitized;
        }

        /**
         * Get all options for this page.
         *
         * @since  1.0.0
         * @return array
         */
        public function getOptions(): array
        {
            return $this->storage->getOption($this->config['option_key'], array());
        }

        /**
         * Get a single option value.
         *
         * @since  1.0.0
         * @param  string $key     Option key.
         * @param  mixed  $default Default value if not set.
         * @return mixed
         */
        public function getOption(string $key, mixed $default = null): mixed
        {
            $options = $this->getOptions();
            return $options[ $key ] ?? $default;
        }

        /**
         * Update a single option value.
         *
         * @since  1.0.0
         * @param  string $key   Option key.
         * @param  mixed  $value Value to set.
         * @return bool
         */
        public function updateOption(string $key, mixed $value): bool
        {
            $options = $this->getOptions();
            $options[ $key ] = $value;
            return $this->storage->updateOption($this->config['option_key'], $options);
        }

        /**
         * Delete a single option value.
         *
         * @since  1.0.0
         * @param  string $key Option key.
         * @return bool
         */
        public function deleteOption(string $key): bool
        {
            $options = $this->getOptions();
            unset($options[ $key ]);
            return $this->storage->updateOption($this->config['option_key'], $options);
        }

        /**
         * Render export/import section.
         *
         * @since  1.0.0
         * @return void
         */
        private function renderExportImport(): void
        {
            if (empty($this->config['show_export_import'])) {
                return;
            }
            ?>
            <div class="kp-wsf-export-import">
                <h2><?php esc_html_e('Export / Import Settings', 'kp-wsf'); ?></h2>
                <p class="description"><?php esc_html_e('Export or import all settings for this options page, including all tabs.', 'kp-wsf'); ?></p>
                
                <div class="kp-wsf-export-import-columns">
                    <div class="kp-wsf-export-section">
                        <h3><?php esc_html_e('Export', 'kp-wsf'); ?></h3>
                        <p class="description"><?php esc_html_e('Download all current settings (including defaults) as a JSON file.', 'kp-wsf'); ?></p>
                        <button type="button" class="button button-secondary kp-wsf-export-btn" data-menu-slug="<?php echo esc_attr($this->config['menu_slug']); ?>">
                            <?php esc_html_e('Export All Settings', 'kp-wsf'); ?>
                        </button>
                    </div>

                    <div class="kp-wsf-import-section">
                        <h3><?php esc_html_e('Import', 'kp-wsf'); ?></h3>
                        <p class="description"><?php esc_html_e('Upload a previously exported JSON file to restore all settings.', 'kp-wsf'); ?></p>
                        <input type="file" class="kp-wsf-import-file" accept=".json" />
                        <button type="button" class="button button-secondary kp-wsf-import-btn" data-menu-slug="<?php echo esc_attr($this->config['menu_slug']); ?>" disabled>
                            <?php esc_html_e('Import All Settings', 'kp-wsf'); ?>
                        </button>
                        <p class="kp-wsf-import-status"></p>
                    </div>
                </div>
            </div>
            <?php
        }

        /**
         * Get all registered fields across all sections and tabs.
         *
         * @since  1.0.0
         * @return array Array of all field configurations.
         */
        public function getAllFields(): array
        {
            $all_fields = [];

            foreach ($this->fields as $section_id => $section_fields) {
                foreach ($section_fields as $field) {
                    // Skip layout-only fields.
                    $layout_types = ['heading', 'separator', 'html', 'message'];
                    if (!in_array($field['type'] ?? 'text', $layout_types, true)) {
                        $all_fields[$field['id']] = $field;
                    }

                    // Handle repeater sub-fields.
                    if (($field['type'] ?? '') === 'repeater' && !empty($field['fields'])) {
                        // Store repeater field info for reference.
                        $all_fields[$field['id']]['_is_repeater'] = true;
                    }

                    // Handle group sub-fields.
                    if (($field['type'] ?? '') === 'group' && !empty($field['fields'])) {
                        $all_fields[$field['id']]['_is_group'] = true;
                    }

                    // Handle accordion sub-fields.
                    if (($field['type'] ?? '') === 'accordion' && !empty($field['fields'])) {
                        $all_fields[$field['id']]['_is_accordion'] = true;
                    }
                }
            }

            return $all_fields;
        }
    }
}