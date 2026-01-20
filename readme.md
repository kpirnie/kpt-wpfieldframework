# KPT WP Field Framework

A PHP framework for creating WordPress Options Pages, Meta Boxes, and Gutenberg Blocks with repeatable field groups.

## Requirements

- PHP 8.2+
- WordPress 6.8+

## Installation

```bash
composer require kevinpirnie/kpt-wpfieldframework
```

## Quick Start

### In a Plugin

```php
<?php
// In your main plugin file.
use KP\WPFieldFramework\Loader;

// Bootstrap the framework.
$framework = Loader::init();

// Create an options page.
$framework->addOptionsPage([
    'page_title' => 'My Plugin Settings',
    'menu_title' => 'My Plugin',
    'menu_slug'  => 'my-plugin-settings',
    'sections'   => [
        'general' => [
            'title'  => 'General Settings',
            'fields' => [
                [
                    'id'          => 'site_logo',
                    'type'        => 'image',
                    'label'       => 'Site Logo',
                    'description' => 'Upload your site logo.',
                ],
                [
                    'id'      => 'primary_color',
                    'type'    => 'color',
                    'label'   => 'Primary Color',
                    'default' => '#0073aa',
                ],
            ],
        ],
    ],
]);

// Create a meta box.
$framework->addMetaBox([
    'id'         => 'page_settings',
    'title'      => 'Page Settings',
    'post_types' => ['page'],
    'fields'     => [
        [
            'id'    => 'subtitle',
            'type'  => 'text',
            'label' => 'Subtitle',
        ],
        [
            'id'             => 'show_sidebar',
            'type'           => 'checkbox',
            'label'          => 'Layout',
            'checkbox_label' => 'Show sidebar on this page',
        ],
    ],
]);
```

### In a Theme

```php
<?php
// In your theme's functions.php.
use KP\WPFieldFramework\Loader;

add_action('after_setup_theme', function() {
    $framework = Loader::init();

    // Add your options pages and meta boxes here.
});
```

## Options Pages

### Basic Options Page

```php
$framework->addOptionsPage([
    'page_title'  => 'Theme Options',
    'menu_title'  => 'Theme Options',
    'capability'  => 'manage_options',
    'menu_slug'   => 'theme-options',
    'icon_url'    => 'dashicons-admin-customizer',
    'position'    => 60,
    'sections'    => [
        'general' => [
            'title'       => 'General',
            'description' => 'General theme settings.',
            'fields'      => [
                // Fields go here.
            ],
        ],
    ],
]);
```

### Tabbed Options Page

Tabbed options pages allow you to organize settings into multiple tabs. Each tab's settings are preserved when saving - only the current tab's fields are updated while other tabs' data remains intact.

```php
$framework->addOptionsPage([
    'page_title' => 'Theme Options',
    'menu_title' => 'Theme Options',
    'menu_slug'  => 'theme-options',
    'tabs'       => [
        'general' => [
            'title'    => 'General',
            'sections' => [
                'branding' => [
                    'title'  => 'Branding',
                    'fields' => [
                        [
                            'id'    => 'logo',
                            'type'  => 'image',
                            'label' => 'Logo',
                        ],
                    ],
                ],
            ],
        ],
        'advanced' => [
            'title'    => 'Advanced',
            'sections' => [
                'code' => [
                    'title'  => 'Custom Code',
                    'fields' => [
                        [
                            'id'        => 'custom_css',
                            'type'      => 'code',
                            'label'     => 'Custom CSS',
                            'code_type' => 'text/css',
                        ],
                    ],
                ],
            ],
        ],
    ],
]);
```

### Submenu Options Page

```php
$framework->addOptionsPage([
    'page_title'  => 'Plugin Settings',
    'menu_title'  => 'Settings',
    'menu_slug'   => 'my-plugin-settings',
    'parent_slug' => 'options-general.php', // Under Settings menu.
    'sections'    => [
        // ...
    ],
]);
```

### Retrieving Options

```php
use KP\WPFieldFramework\Framework;

// Get all options.
$options = get_option('theme_options');

// Get specific option.
$logo_id = $options['logo'] ?? '';

// Using the Storage class.
$storage = Framework::getInstance()->getStorage();
$value = $storage->getOptionKey('theme_options', 'logo', '');
```

## Meta Boxes

### Post/Page Meta Box

```php
$framework->addMetaBox([
    'id'         => 'post_settings',
    'title'      => 'Post Settings',
    'post_types' => ['post', 'page'],
    'context'    => 'normal',  // normal, side, advanced
    'priority'   => 'high',    // high, core, default, low
    'fields'     => [
        [
            'id'    => 'featured_video',
            'type'  => 'url',
            'label' => 'Featured Video URL',
        ],
    ],
]);
```

### Custom Post Type Meta Box

```php
$framework->addMetaBox([
    'id'         => 'product_details',
    'title'      => 'Product Details',
    'post_types' => ['product'],
    'fields'     => [
        [
            'id'    => 'price',
            'type'  => 'number',
            'label' => 'Price',
            'min'   => 0,
            'step'  => 0.01,
        ],
        [
            'id'    => 'sku',
            'type'  => 'text',
            'label' => 'SKU',
        ],
    ],
]);
```

### User Profile Meta Box

```php
$framework->addMetaBox([
    'id'        => 'user_social',
    'title'     => 'Social Profiles',
    'user_meta' => true,
    'fields'    => [
        [
            'id'          => 'twitter',
            'type'        => 'url',
            'label'       => 'Twitter URL',
            'placeholder' => 'https://twitter.com/username',
        ],
        [
            'id'          => 'linkedin',
            'type'        => 'url',
            'label'       => 'LinkedIn URL',
            'placeholder' => 'https://linkedin.com/in/username',
        ],
    ],
]);
```

### Nav Menu Item Meta Box

```php
$framework->addMetaBox([
    'id'       => 'menu_item_options',
    'title'    => 'Menu Item Options',
    'nav_menu' => true,
    'fields'   => [
        [
            'id'    => 'icon_class',
            'type'  => 'text',
            'label' => 'Icon Class',
        ],
        [
            'id'             => 'hide_on_mobile',
            'type'           => 'checkbox',
            'label'          => 'Visibility',
            'checkbox_label' => 'Hide on mobile devices',
        ],
    ],
]);
```

### Retrieving Meta Values

```php
// Standard WordPress function.
$subtitle = get_post_meta($post_id, 'subtitle', true);

// Using the Storage class.
$storage = Framework::getInstance()->getStorage();
$subtitle = $storage->getMeta($post_id, 'subtitle', '');

// User meta.
$twitter = $storage->getUserMeta($user_id, 'twitter', '');
```

## Gutenberg Blocks

### Creating a Block from a Meta Box

```php
$framework->addMetaBox([
    'id'           => 'cta_block',
    'title'        => 'Call to Action',
    'post_types'   => ['post', 'page'],
    'create_block' => true,
    'block_config' => [
        'name'        => 'my-plugin/cta',
        'title'       => 'Call to Action',
        'description' => 'A call to action block.',
        'category'    => 'common',
        'icon'        => 'megaphone',
        'keywords'    => ['cta', 'button', 'action'],
    ],
    'fields' => [
        [
            'id'    => 'heading',
            'type'  => 'text',
            'label' => 'Heading',
        ],
        [
            'id'    => 'content',
            'type'  => 'textarea',
            'label' => 'Content',
        ],
        [
            'id'    => 'button_text',
            'type'  => 'text',
            'label' => 'Button Text',
        ],
        [
            'id'    => 'button_url',
            'type'  => 'url',
            'label' => 'Button URL',
        ],
    ],
]);
```

### Custom Block Rendering

```php
$framework->addMetaBox([
    'id'           => 'testimonial_block',
    'title'        => 'Testimonial',
    'post_types'   => ['post'],
    'create_block' => true,
    'block_config' => [
        'name'            => 'my-plugin/testimonial',
        'title'           => 'Testimonial',
        'icon'            => 'format-quote',
        'render_template' => get_template_directory() . '/blocks/testimonial.php',
        // Or use a callback:
        // 'render_callback' => 'my_render_testimonial_block',
    ],
    'fields' => [
        [
            'id'    => 'quote',
            'type'  => 'textarea',
            'label' => 'Quote',
        ],
        [
            'id'    => 'author',
            'type'  => 'text',
            'label' => 'Author',
        ],
        [
            'id'    => 'photo',
            'type'  => 'image',
            'label' => 'Photo',
        ],
    ],
]);
```

Block template file (`blocks/testimonial.php`):

```php
<?php
/**
 * Testimonial block template.
 *
 * @var array $attributes Block attributes.
 * @var array $fields     Field configurations.
 */

$quote  = $attributes['quote'] ?? '';
$author = $attributes['author'] ?? '';
$photo  = $attributes['photo'] ?? 0;
?>

<blockquote class="testimonial-block">
    <?php if ($photo): ?>
        <div class="testimonial-photo">
            <?php echo wp_get_attachment_image($photo, 'thumbnail'); ?>
        </div>
    <?php endif; ?>
    
    <p class="testimonial-quote"><?php echo esc_html($quote); ?></p>
    
    <?php if ($author): ?>
        <cite class="testimonial-author"><?php echo esc_html($author); ?></cite>
    <?php endif; ?>
</blockquote>
```

## Field Types

### Text-Based Fields

```php
// Text
[
    'id'          => 'title',
    'type'        => 'text',
    'label'       => 'Title',
    'sublabel'    => 'Enter the main title for this section.',
    'placeholder' => 'Enter title...',
    'default'     => '',
    'required'    => true,
]

// Email
[
    'id'    => 'email',
    'type'  => 'email',
    'label' => 'Email Address',
]

// URL
[
    'id'    => 'website',
    'type'  => 'url',
    'label' => 'Website URL',
]

// Password
[
    'id'    => 'api_key',
    'type'  => 'password',
    'label' => 'API Key',
]

// Number
[
    'id'    => 'quantity',
    'type'  => 'number',
    'label' => 'Quantity',
    'min'   => 0,
    'max'   => 100,
    'step'  => 1,
]

// Telephone
[
    'id'    => 'phone',
    'type'  => 'tel',
    'label' => 'Phone Number',
]

// Hidden
[
    'id'      => 'hidden_value',
    'type'    => 'hidden',
    'default' => 'some-value',
]
```

### Date/Time Fields

```php
// Date
[
    'id'          => 'event_date',
    'type'        => 'date',
    'label'       => 'Event Date',
    'date_format' => 'yy-mm-dd',
]

// Datetime
[
    'id'    => 'start_time',
    'type'  => 'datetime',
    'label' => 'Start Date & Time',
]

// Time
[
    'id'    => 'opening_time',
    'type'  => 'time',
    'label' => 'Opening Time',
]

// Week
[
    'id'    => 'target_week',
    'type'  => 'week',
    'label' => 'Target Week',
]

// Month
[
    'id'    => 'birth_month',
    'type'  => 'month',
    'label' => 'Birth Month',
]
```

### Selection Fields

```php
// Select dropdown
[
    'id'          => 'country',
    'type'        => 'select',
    'label'       => 'Country',
    'placeholder' => 'Select a country...',
    'options'     => [
        'us' => 'United States',
        'ca' => 'Canada',
        'uk' => 'United Kingdom',
    ],
]

// Select with optgroups
[
    'id'      => 'city',
    'type'    => 'select',
    'label'   => 'City',
    'options' => [
        'United States' => [
            'nyc' => 'New York',
            'la'  => 'Los Angeles',
        ],
        'Canada' => [
            'tor' => 'Toronto',
            'van' => 'Vancouver',
        ],
    ],
]

// Multi-select
[
    'id'      => 'categories',
    'type'    => 'multiselect',
    'label'   => 'Categories',
    'options' => [
        'tech'   => 'Technology',
        'health' => 'Health',
        'sports' => 'Sports',
    ],
    'size' => 5,
]

// Single checkbox
[
    'id'             => 'featured',
    'type'           => 'checkbox',
    'label'          => 'Featured',
    'checkbox_label' => 'Mark this item as featured',
]

// Multiple checkboxes
[
    'id'      => 'features',
    'type'    => 'checkboxes',
    'label'   => 'Features',
    'options' => [
        'wifi'    => 'Free WiFi',
        'parking' => 'Free Parking',
        'pool'    => 'Swimming Pool',
    ],
]

// Radio buttons
[
    'id'      => 'size',
    'type'    => 'radio',
    'label'   => 'Size',
    'options' => [
        'sm' => 'Small',
        'md' => 'Medium',
        'lg' => 'Large',
    ],
    'default' => 'md',
]

// Switch toggle
[
    'id'        => 'enable_feature',
    'type'      => 'switch',
    'label'     => 'Enable Feature',
    'sublabel'  => 'Turn this feature on or off.',
    'on_label'  => 'Enabled',
    'off_label' => 'Disabled',
    'default'   => false,
]
```

### Text Areas & Editors

```php
// Textarea
[
    'id'    => 'description',
    'type'  => 'textarea',
    'label' => 'Description',
    'rows'  => 5,
    'cols'  => 50,
]

// WYSIWYG editor
[
    'id'            => 'content',
    'type'          => 'wysiwyg',
    'label'         => 'Content',
    'rows'          => 15,
    'media_buttons' => true,
    'teeny'         => false,
    'quicktags'     => true,
]

// Code editor
[
    'id'        => 'custom_css',
    'type'      => 'code',
    'label'     => 'Custom CSS',
    'code_type' => 'text/css', // text/html, application/javascript, etc.
    'rows'      => 10,
]
```

### Media Fields

```php
// Image upload
[
    'id'    => 'featured_image',
    'type'  => 'image',
    'label' => 'Featured Image',
]

// File upload
[
    'id'    => 'download_file',
    'type'  => 'file',
    'label' => 'Download File',
]

// Gallery
[
    'id'    => 'gallery',
    'type'  => 'gallery',
    'label' => 'Image Gallery',
]
```

### Special Fields

```php
// Color picker
[
    'id'      => 'accent_color',
    'type'    => 'color',
    'label'   => 'Accent Color',
    'default' => '#ff6600',
]

// Range slider
[
    'id'      => 'opacity',
    'type'    => 'range',
    'label'   => 'Opacity',
    'min'     => 0,
    'max'     => 100,
    'step'    => 5,
    'default' => 100,
]

// Link selector
[
    'id'    => 'cta_link',
    'type'  => 'link',
    'label' => 'Call to Action Link',
]

// Post select
[
    'id'             => 'related_post',
    'type'           => 'post_select',
    'label'          => 'Related Post',
    'post_type'      => 'post',
    'posts_per_page' => -1,
]

// Page select
[
    'id'    => 'parent_page',
    'type'  => 'page_select',
    'label' => 'Parent Page',
]

// Term select
[
    'id'         => 'category',
    'type'       => 'term_select',
    'label'      => 'Category',
    'taxonomy'   => 'category',
    'hide_empty' => false,
]

// User select
[
    'id'    => 'author',
    'type'  => 'user_select',
    'label' => 'Author',
    'role'  => 'author', // Optional: filter by role.
]
```

### Layout Fields

```php
// Heading
[
    'id'    => 'section_heading',
    'type'  => 'heading',
    'label' => 'Advanced Settings',
    'tag'   => 'h3', // h1-h6
]

// Separator
[
    'id'   => 'separator_1',
    'type' => 'separator',
]

// Raw HTML
[
    'id'      => 'custom_html',
    'type'    => 'html',
    'content' => '<p class="custom-notice">This is custom HTML content.</p>',
]

// Message/Notice
[
    'id'           => 'warning_message',
    'type'         => 'message',
    'message_type' => 'warning', // info, success, warning, error
    'content'      => 'This is a warning message.',
]
```

## Conditional Fields

Fields can be shown or hidden based on the values of other fields in the same section. Conditionals are evaluated in real-time using JavaScript.

### Single Condition

```php
[
    'id'      => 'enable_feature',
    'type'    => 'switch',
    'label'   => 'Enable Feature',
    'default' => false,
],
[
    'id'          => 'feature_option',
    'type'        => 'text',
    'label'       => 'Feature Option',
    'conditional' => [
        'field'     => 'enable_feature',
        'value'     => true,
        'condition' => '==',
    ],
]
```

### Multiple Conditions (AND)

All conditions must be true for the field to be visible.

```php
[
    'id'          => 'advanced_option',
    'type'        => 'text',
    'label'       => 'Advanced Option',
    'conditional' => [
        'AND' => [
            [
                'field'     => 'enable_feature',
                'value'     => true,
                'condition' => '==',
            ],
            [
                'field'     => 'feature_level',
                'value'     => 'advanced',
                'condition' => '==',
            ],
        ],
    ],
]
```

### Multiple Conditions (OR)

At least one condition must be true for the field to be visible.

```php
[
    'id'          => 'show_either',
    'type'        => 'text',
    'label'       => 'Show Either',
    'conditional' => [
        'OR' => [
            [
                'field'     => 'status',
                'value'     => 'active',
                'condition' => '==',
            ],
            [
                'field'     => 'override',
                'value'     => true,
                'condition' => '==',
            ],
        ],
    ],
]
```

### Available Condition Operators

| Operator | Description |
|----------|-------------|
| `==` | Equal to |
| `!=` | Not equal to |
| `>` | Greater than |
| `<` | Less than |
| `>=` | Greater than or equal to |
| `<=` | Less than or equal to |
| `IN` | Value is in array/comma-separated list |
| `NOT_IN` | Value is not in array/comma-separated list |
| `CONTAINS` | Value contains substring or array contains value |
| `NOT_CONTAINS` | Value does not contain substring |
| `EMPTY` | Value is empty |
| `NOT_EMPTY` | Value is not empty |

### Conditional with Radio Buttons

```php
[
    'id'      => 'display_mode',
    'type'    => 'radio',
    'label'   => 'Display Mode',
    'options' => [
        'simple'   => 'Simple',
        'advanced' => 'Advanced',
        'custom'   => 'Custom',
    ],
    'default' => 'simple',
],
[
    'id'          => 'custom_template',
    'type'        => 'text',
    'label'       => 'Custom Template Path',
    'conditional' => [
        'field'     => 'display_mode',
        'value'     => 'custom',
        'condition' => '==',
    ],
],
[
    'id'          => 'advanced_options',
    'type'        => 'textarea',
    'label'       => 'Advanced Options',
    'conditional' => [
        'field'     => 'display_mode',
        'value'     => ['advanced', 'custom'],
        'condition' => 'IN',
    ],
]
```

### Conditionals on Groups and Accordions

Groups and accordions fully support conditional logic. You can show/hide entire groups or accordions based on other field values.

```php
[
    'id'      => 'enable_social',
    'type'    => 'switch',
    'label'   => 'Enable Social Links',
    'default' => false,
],
[
    'id'          => 'social_links',
    'type'        => 'group',
    'label'       => 'Social Links',
    'conditional' => [
        'field'     => 'enable_social',
        'value'     => true,
        'condition' => '==',
    ],
    'fields' => [
        [
            'id'    => 'facebook',
            'type'  => 'url',
            'label' => 'Facebook URL',
        ],
        [
            'id'    => 'twitter',
            'type'  => 'url',
            'label' => 'Twitter URL',
        ],
    ],
]
```

### Nested Conditionals (Inside Accordions/Groups)

When using conditionals on fields inside an accordion or group, the field IDs are automatically prefixed with the parent's ID. **You must use the prefixed field ID when referencing sibling fields within the same parent.**

For example, if you have an accordion with ID `my_accordion` containing a switch with ID `enable_option`, the actual field ID becomes `my_accordion_enable_option`. Any conditional referencing this field must use the full prefixed ID:

```php
[
    'id'    => 'my_settings',
    'type'  => 'accordion',
    'label' => 'My Settings',
    'fields' => [
        [
            'id'      => 'show_advanced',
            'type'    => 'switch',
            'label'   => 'Show Advanced Options',
            'default' => false,
        ],
        [
            'id'          => 'advanced_settings',
            'type'        => 'group',
            'label'       => 'Advanced Settings',
            // Reference the FULL prefixed ID of the sibling field
            'conditional' => [
                'field'     => 'my_settings_show_advanced',
                'value'     => true,
                'condition' => '==',
            ],
            'fields' => [
                [
                    'id'    => 'option_a',
                    'type'  => 'text',
                    'label' => 'Option A',
                ],
                [
                    'id'    => 'option_b',
                    'type'  => 'text',
                    'label' => 'Option B',
                ],
            ],
        ],
    ],
]
```

**Important:** When a conditional references a field *outside* the accordion/group (at the root level), use the original field ID without any prefix:

```php
[
    'id'      => 'master_toggle',
    'type'    => 'switch',
    'label'   => 'Enable All Features',
    'default' => false,
],
[
    'id'    => 'feature_settings',
    'type'  => 'accordion',
    'label' => 'Feature Settings',
    // References root-level field - no prefix needed
    'conditional' => [
        'field'     => 'master_toggle',
        'value'     => true,
        'condition' => '==',
    ],
    'fields' => [
        // ...
    ],
]
```

## Repeater Fields

### Basic Repeater

```php
[
    'id'           => 'team_members',
    'type'         => 'repeater',
    'label'        => 'Team Members',
    'button_label' => 'Add Team Member',
    'min_rows'     => 1,
    'max_rows'     => 10,
    'collapsed'    => true,
    'sortable'     => true,
    'row_label'    => 'Member',
    'fields'       => [
        [
            'id'       => 'name',
            'type'     => 'text',
            'label'    => 'Name',
            'required' => true,
        ],
        [
            'id'    => 'title',
            'type'  => 'text',
            'label' => 'Job Title',
        ],
        [
            'id'    => 'photo',
            'type'  => 'image',
            'label' => 'Photo',
        ],
        [
            'id'    => 'bio',
            'type'  => 'textarea',
            'label' => 'Biography',
        ],
    ],
]
```

### Repeater with Multiple Field Types

```php
[
    'id'           => 'faq_items',
    'type'         => 'repeater',
    'label'        => 'FAQ Items',
    'button_label' => 'Add FAQ',
    'fields'       => [
        [
            'id'       => 'question',
            'type'     => 'text',
            'label'    => 'Question',
            'required' => true,
        ],
        [
            'id'    => 'answer',
            'type'  => 'wysiwyg',
            'label' => 'Answer',
            'rows'  => 5,
            'teeny' => true,
        ],
        [
            'id'      => 'category',
            'type'    => 'select',
            'label'   => 'Category',
            'options' => [
                'general'  => 'General',
                'billing'  => 'Billing',
                'shipping' => 'Shipping',
            ],
        ],
    ],
]
```

### Retrieving Repeater Data

```php
use KP\WPFieldFramework\Repeater;

// Get repeater data.
$team_members = get_post_meta($post_id, 'team_members', true);

if (Repeater::hasRows($team_members)) {
    foreach ($team_members as $index => $member) {
        $name  = $member['name'] ?? '';
        $title = $member['title'] ?? '';
        $photo = $member['photo'] ?? 0;
        $bio   = $member['bio'] ?? '';
        
        // Output member...
    }
}

// Get specific row value.
$first_member_name = Repeater::getValue($team_members, 0, 'name', 'Default');

// Get all values for a specific field across all rows.
$all_names = Repeater::getColumnValues($team_members, 'name');

// Get row count.
$count = Repeater::getRowCount($team_members);
```

## Field Groups

### Basic Group

```php
[
    'id'     => 'address',
    'type'   => 'group',
    'label'  => 'Address',
    'fields' => [
        [
            'id'    => 'street',
            'type'  => 'text',
            'label' => 'Street Address',
        ],
        [
            'id'    => 'city',
            'type'  => 'text',
            'label' => 'City',
        ],
        [
            'id'    => 'state',
            'type'  => 'text',
            'label' => 'State',
        ],
        [
            'id'    => 'zip',
            'type'  => 'text',
            'label' => 'ZIP Code',
        ],
    ],
]
```

### Inline Group Fields

Use the `inline` option to display fields side-by-side within a group:

```php
[
    'id'     => 'dimensions',
    'type'   => 'group',
    'label'  => 'Dimensions',
    'fields' => [
        [
            'id'     => 'width',
            'type'   => 'number',
            'label'  => 'Width',
            'inline' => true,
        ],
        [
            'id'     => 'height',
            'type'   => 'number',
            'label'  => 'Height',
            'inline' => true,
        ],
        [
            'id'     => 'depth',
            'type'   => 'number',
            'label'  => 'Depth',
            'inline' => true,
        ],
    ],
]
```

### Retrieving Group Data

```php
$address = get_post_meta($post_id, 'address', true);

$street = $address['address_street'] ?? '';
$city   = $address['address_city'] ?? '';
$state  = $address['address_state'] ?? '';
$zip    = $address['address_zip'] ?? '';
```

**Note:** Group sub-field values are stored with the group ID as a prefix (e.g., `address_street` for a field with ID `street` inside a group with ID `address`).

## Accordion Fields

Accordions provide a collapsible container for organizing related fields:

```php
[
    'id'          => 'advanced_settings',
    'type'        => 'accordion',
    'label'       => 'Advanced Settings',
    'description' => 'Click to expand advanced options.',
    'open'        => false, // Start collapsed
    'fields'      => [
        [
            'id'    => 'cache_duration',
            'type'  => 'number',
            'label' => 'Cache Duration (seconds)',
        ],
        [
            'id'    => 'debug_mode',
            'type'  => 'switch',
            'label' => 'Debug Mode',
        ],
    ],
]
```

## Field Configuration Options

All fields support these common options:

| Option | Type | Description |
|--------|------|-------------|
| `id` | string | Unique field identifier (required) |
| `type` | string | Field type (required) |
| `label` | string | Field label |
| `sublabel` | string | Secondary label displayed below the main label |
| `description` | string | Help text displayed below field |
| `default` | mixed | Default value |
| `placeholder` | string | Placeholder text |
| `required` | bool | Whether field is required |
| `disabled` | bool | Whether field is disabled |
| `readonly` | bool | Whether field is read-only |
| `class` | string | Additional CSS class(es) |
| `inline` | bool | Display field inline (for groups/repeaters) |
| `attributes` | array | Additional HTML attributes |
| `sanitize` | callable | Custom sanitization callback |
| `validate` | callable | Custom validation callback |
| `conditional` | array | Conditional display rules |

### Custom Sanitization

```php
[
    'id'       => 'custom_field',
    'type'     => 'text',
    'label'    => 'Custom Field',
    'sanitize' => function($value, $field) {
        // Custom sanitization logic.
        return strtoupper(sanitize_text_field($value));
    },
]
```

### Custom Validation

```php
[
    'id'       => 'custom_field',
    'type'     => 'text',
    'label'    => 'Custom Field',
    'validate' => function($value, $field) {
        if (strlen($value) < 5) {
            return 'Value must be at least 5 characters.';
        }
        return true;
    },
]
```

## Storage API

The Storage class provides a unified interface for all WordPress data storage:

```php
use KP\WPFieldFramework\Framework;

$storage = Framework::getInstance()->getStorage();

// Options
$storage->getOption('option_name', $default);
$storage->updateOption('option_name', $value);
$storage->deleteOption('option_name');
$storage->getOptionKey('option_name', 'key', $default);
$storage->updateOptionKey('option_name', 'key', $value);

// Post Meta
$storage->getMeta($post_id, 'meta_key', $default);
$storage->updateMeta($post_id, 'meta_key', $value);
$storage->deleteMeta($post_id, 'meta_key');
$storage->getAllMeta($post_id, ['key1', 'key2']);

// User Meta
$storage->getUserMeta($user_id, 'meta_key', $default);
$storage->updateUserMeta($user_id, 'meta_key', $value);
$storage->deleteUserMeta($user_id, 'meta_key');

// Term Meta
$storage->getTermMeta($term_id, 'meta_key', $default);
$storage->updateTermMeta($term_id, 'meta_key', $value);
$storage->deleteTermMeta($term_id, 'meta_key');

// Comment Meta
$storage->getCommentMeta($comment_id, 'meta_key', $default);
$storage->updateCommentMeta($comment_id, 'meta_key', $value);
$storage->deleteCommentMeta($comment_id, 'meta_key');

// Generic (auto-detect type)
$storage->get('post', $object_id, 'meta_key', $default);
$storage->update('user', $object_id, 'meta_key', $value);
$storage->delete('term', $object_id, 'meta_key');

// Transients
$storage->getTransient('transient_name', $default);
$storage->setTransient('transient_name', $value, $expiration);
$storage->deleteTransient('transient_name');

// Cache Management
$storage->clearCache();
$storage->clearCache('post_meta_'); // Clear by prefix
$storage->setUseCache(false);
```

## Export / Import Settings

The framework includes built-in functionality to export and import settings, making it easy to backup configurations or migrate settings between environments.

### Enabling Export/Import UI

Add `show_export_import => true` to your options page configuration:

```php
$framework->addOptionsPage([
    'page_title'         => 'My Plugin Settings',
    'menu_title'         => 'My Plugin',
    'menu_slug'          => 'my-plugin-settings',
    'show_export_import' => true,  // Enables the Export/Import UI
    'tabs'               => [
        // All tabs will be included in export/import
        'general'  => [
            'title'    => 'General',
            'sections' => [ /* ... */ ],
        ],
        'advanced' => [
            'title'    => 'Advanced',
            'sections' => [ /* ... */ ],
        ],
    ],
]);
```

This adds an "Export / Import Settings" panel at the bottom of your options page that allows users to:

- **Export**: Download all current settings (including defaults for unsaved fields) as a JSON file
- **Import**: Upload a previously exported JSON file to restore settings

### Programmatic Export/Import

You can also export and import settings programmatically:

```php
use KP\WPFieldFramework\Framework;

$framework = Framework::getInstance();
$export_import = $framework->getExportImport();

// Export specific option keys
$json = $export_import->export(['my_plugin_settings']);

// Export with defaults (includes default values for fields not yet saved)
$options_page = $framework->getOptionsPageBySlug('my-plugin-settings');
$json = $export_import->exportWithDefaults([$options_page]);

// Validate import data before importing
$validation = $export_import->validate($json);
if ($validation['valid']) {
    echo 'Options to import: ' . implode(', ', $validation['options']);
    echo 'Exported from: ' . $validation['meta']['site_url'];
    echo 'Export date: ' . $validation['meta']['exported'];
}

// Import settings (with optional whitelist)
$allowed_options = ['my_plugin_settings'];
$result = $export_import->import($json, $allowed_options);

if ($result['success']) {
    echo 'Imported: ' . implode(', ', $result['imported']);
} else {
    echo 'Errors: ' . implode(', ', $result['errors']);
}

// Import from uploaded file
if (isset($_FILES['import_file'])) {
    $result = $export_import->importFromFile(
        $_FILES['import_file'],
        ['my_plugin_settings']
    );
}

// Trigger direct download
$export_import->exportDownload(
    ['my_plugin_settings'],
    'my-plugin-backup.json'
);
```

### Export File Format

Exported JSON files include metadata and all settings:

```json
{
    "version": "1.0.0",
    "exported": "2025-01-17T12:00:00+00:00",
    "site_url": "https://example.com",
    "settings": {
        "my_plugin_settings": {
            "logo": 123,
            "primary_color": "#0073aa",
            "enable_feature": true,
            "custom_css": ".my-class { color: red; }"
        }
    }
}
```

### Security Considerations

- Export/Import requires the `manage_options` capability
- Imports are validated against registered option keys (whitelist)
- All values pass through the framework's sanitization on import
- AJAX endpoints are protected with nonce verification

## Advanced Usage

### Manual Initialization

```php
use KP\WPFieldFramework\Framework;

$framework = Framework::getInstance();

$framework->init(
    'https://example.com/wp-content/plugins/my-plugin/assets',
    '/var/www/html/wp-content/plugins/my-plugin/assets'
);
```

### Requirements Check

The framework automatically checks requirements when using `Loader::init()`. If requirements are not met, an admin notice is displayed.

### Without Composer Autoloader

```php
// Manually include and initialize.
require_once 'path/to/kpt-wpfieldframework/src/Loader.php';

$framework = KP\WPFieldFramework\Loader::init();
```

## JavaScript Events

The framework triggers custom jQuery events for extensibility:

```javascript
// Repeater row added.
$(document).on('kp-wsf-repeater-row-added', function(event, $newRow, $repeater) {
    console.log('New row added:', $newRow);
});

// Repeater row before removal.
$(document).on('kp-wsf-repeater-row-before-remove', function(event, $row, $repeater) {
    console.log('Row about to be removed:', $row);
});

// Repeater row removed.
$(document).on('kp-wsf-repeater-row-removed', function(event, $repeater) {
    console.log('Row removed from:', $repeater);
});
```

## Global JavaScript Object

The framework exposes a global `KpWsfAdmin` object:

```javascript
// Manually initialize components.
KpWsfAdmin.initColorPickers();
KpWsfAdmin.initDatePickers();
KpWsfAdmin.initCodeEditors();
KpWsfAdmin.initRangeSliders();
KpWsfAdmin.initRepeaterSortable();
KpWsfAdmin.initGallerySortable();
KpWsfAdmin.initConditionals();
KpWsfAdmin.initAccordions();

// Add repeater row programmatically.
KpWsfAdmin.repeaterAddRow($('.kp-wsf-repeater'));
```

## Hooks & Filters

The framework integrates with standard WordPress hooks. Meta boxes use these hooks:

- `add_meta_boxes` - Register meta boxes
- `save_post` - Save post meta
- `personal_options_update` - Save user meta (own profile)
- `edit_user_profile_update` - Save user meta (other profiles)
- `show_user_profile` - Display user fields (own profile)
- `edit_user_profile` - Display user fields (other profiles)
- `wp_nav_menu_item_custom_fields` - Display nav menu fields
- `wp_update_nav_menu_item` - Save nav menu meta

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Author

Kevin Pirnie - [iam@kevinpirnie.com](mailto:iam@kevinpirnie.com)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

If you encounter any issues or have questions, please [open an issue](https://github.com/kpirnie/kpt-wpfieldframework/issues) on GitHub.