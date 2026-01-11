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

// Prevent direct access.
defined('ABSPATH') || exit;
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
        'page_title'  => 'Options',
        'menu_title'  => 'Options',
        'capability'  => 'manage_options',
        'menu_slug'   => 'kp-wsf-options',
        'parent_slug' => '',
        'icon_url'    => 'dashicons-admin-generic',
        'position'    => null,
        'option_name' => '',
        'option_key'  => '',
        'tabs'        => array(),
        'sections'    => array(),
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
        // Register the main option.
        register_setting(
            $this->config['menu_slug'],
            $this->config['option_key'],
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitizeOptions' ),
                'default'           => array(),
            )
        );
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
        // Skip layout-only fields.
        $layout_types = array( 'heading', 'separator', 'html', 'message' );
        if (in_array($field['type'] ?? 'text', $layout_types, true)) {
            add_settings_field(
                $field['id'],
                '',
                function () use ($field) {

                    echo $this->field_types->render($field, null);
                },
                $this->config['menu_slug'],
                $section_id
            );
            return;
        }

        add_settings_field(
            $field['id'],
            $field['label'] ?? '',
            function () use ($field) {

                $this->renderField($field);
            },
            $this->config['menu_slug'],
            $section_id,
            array(
                'label_for' => $field['id'],
                'class'     => 'kp-wsf-field-row kp-wsf-field-row--' . ( $field['type'] ?? 'text' ),
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

            submit_button(__('Save Settings', 'kp-wsf'));
            ?>
        </form>
        <?php
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
            printf('<p class="description">%s</p>', wp_kses_post($section['description']));
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
        // Get current value from options.
        $options = $this->storage->getOption($this->config['option_key'], array());
        $value = $options[ $field['id'] ] ?? ( $field['default'] ?? null );
        // Set the field name to use array notation for the option.
        $field['name'] = sprintf('%s[%s]', $this->config['option_key'], $field['id']);
        // Render the field.
        echo $this->field_types->render($field, $value);
        // Render description if present.
        if (! empty($field['description'])) {
            printf('<p class="description">%s</p>', wp_kses_post($field['description']));
        }
    }

    /**
     * Sanitize all options on save.
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

        $sanitized = array();
        $sanitizer = new Sanitizer();
        // Get all registered fields.
        $all_fields = array();
        foreach ($this->fields as $section_fields) {
            foreach ($section_fields as $field) {
                $all_fields[ $field['id'] ] = $field;
            }
        }

        // Sanitize each field value.
        foreach ($input as $key => $value) {
            if (isset($all_fields[ $key ])) {
                $field = $all_fields[ $key ];
                $sanitized[ $key ] = $sanitizer->sanitize($value, $field);
            } else {
                // Unknown field, apply basic sanitization.
                $sanitized[ $key ] = $sanitizer->sanitizeUnknown($value);
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
}
