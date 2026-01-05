# KP WP Starter Framework

A PHP framework for creating WordPress Options Pages, Meta Boxes, and Gutenberg Blocks with repeatable field groups.

## Requirements

- PHP 8.2+
- WordPress 6.8+

## Installation

```bash
composer require kevinpirnie/kp-wp-starter-framework
```

## Quick Start

### In a Plugin

```php
<?php
// In your main plugin file.
use KP\WPStarterFramework\Loader;

// Bootstrap the framework.
$framework = Loader::bootstrapPlugin(__FILE__);

// Create an options page.
$framework->addOptionsPage([
    'page_title' => 'My Plugin Settings',
    'menu_title' => 'My Plugin',
    'menu_slug'  => 'my-plugin-settings',
    'option_key' => 'my_plugin_options',
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
use KP\WPStarterFramework\Loader;

add_action('after_setup_theme', function() {
    $framework = Loader::bootstrapTheme();

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
    'option_key'  => 'my_theme_options',
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

### Options Page Configuration

| Option | Type | Description |
|--------|------|-------------|
| `page_title` | string | Title shown in browser tab |
| `menu_title` | string | Title shown in admin menu |
| `menu_slug` | string | URL slug for the page |
| `option_key` | string | Database option name (defaults to menu_slug with underscores) |
| `capability` | string | Required user capability (default: `manage_options`) |
| `parent_slug` | string | Parent menu slug for submenus |
| `icon_url` | string | Dashicon or URL for menu icon |
| `position` | int | Menu position |

### Tabbed Options Page

```php
$framework->addOptionsPage([
    'page_title' => 'Theme Options',
    'menu_title' => 'Theme Options',
    'menu_slug'  => 'theme-options',
    'option_key' => 'my_theme_options',
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
    'option_key'  => 'my_plugin_settings',
    'parent_slug' => 'options-general.php', // Under Settings menu.
    'sections'    => [
        // ...
    ],
]);
```

### Retrieving Options

```php
use KP\WPStarterFramework\Framework;

// Get all options using your custom option key.
$options = get_option('my_theme_options');

// Get specific option.
$logo_id = $options['logo'] ?? '';

// Using the Storage class.
$storage = Framework::getInstance()->getStorage();
$value = $storage->getOptionKey('my_theme_options', 'logo', '');
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

### Link Field

The link field provides a WordPress link selector dialog with URL, title, and target options.

```php
// Link selector
[
    'id'    => 'cta_link',
    'type'  => 'link',
    'label' => 'Call to Action Link',
]
```

**Retrieving Link Data:**

```php
$link = get_post_meta($post_id, 'cta_link', true);

$url    = $link['url'] ?? '';
$title  = $link['title'] ?? '';
$target = $link['target'] ?? ''; // '_blank' or ''

// Output as HTML link
if ($url) {
    printf(
        '<a href="%s"%s>%s</a>',
        esc_url($url),
        $target === '_blank' ? ' target="_blank" rel="noopener"' : '',
        esc_html($title ?: $url)
    );
}
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

### Repeater with Link Fields

```php
[
    'id'           => 'buttons',
    'type'         => 'repeater',
    'label'        => 'Buttons',
    'button_label' => 'Add Button',
    'max_rows'     => 5,
    'fields'       => [
        [
            'id'    => 'button_link',
            'type'  => 'link',
            'label' => 'Button Link',
        ],
        [
            'id'      => 'button_style',
            'type'    => 'select',
            'label'   => 'Style',
            'options' => [
                'primary'   => 'Primary',
                'secondary' => 'Secondary',
                'outline'   => 'Outline',
            ],
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
use KP\WPStarterFramework\Repeater;

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

### Retrieving Group Data

```php
$address = get_post_meta($post_id, 'address', true);

$street = $address['street'] ?? '';
$city   = $address['city'] ?? '';
$state  = $address['state'] ?? '';
$zip    = $address['zip'] ?? '';
```

## Field Configuration Options

All fields support these common options:

| Option | Type | Description |
|--------|------|-------------|
| `id` | string | Unique field identifier (required) |
| `type` | string | Field type (required) |
| `label` | string | Field label |
| `description` | string | Help text displayed below field |
| `default` | mixed | Default value |
| `placeholder` | string | Placeholder text |
| `required` | bool | Whether field is required |
| `disabled` | bool | Whether field is disabled |
| `readonly` | bool | Whether field is read-only |
| `class` | string | Additional CSS class(es) |
| `attributes` | array | Additional HTML attributes |
| `sanitize` | callable | Custom sanitization callback |
| `validate` | callable | Custom validation callback |

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

## Available Field Types Reference

| Type | Description |
|------|-------------|
| `text` | Single line text input |
| `email` | Email address input |
| `url` | URL input |
| `password` | Password input |
| `number` | Numeric input with min/max/step |
| `tel` | Telephone number input |
| `hidden` | Hidden input field |
| `date` | Date picker |
| `datetime` | Date and time picker |
| `time` | Time picker |
| `week` | Week picker |
| `month` | Month picker |
| `select` | Dropdown select |
| `multiselect` | Multiple selection dropdown |
| `checkbox` | Single checkbox |
| `checkboxes` | Multiple checkboxes |
| `radio` | Radio button group |
| `textarea` | Multi-line text area |
| `wysiwyg` | WordPress visual editor |
| `code` | Code editor with syntax highlighting |
| `image` | Image upload with preview |
| `file` | File upload |
| `gallery` | Multiple image gallery |
| `link` | WordPress link selector with URL, title, and target |
| `color` | Color picker |
| `range` | Range slider |
| `post_select` | Post selection dropdown |
| `page_select` | Page selection dropdown |
| `term_select` | Taxonomy term dropdown |
| `user_select` | User selection dropdown |
| `heading` | Section heading |
| `separator` | Horizontal separator line |
| `html` | Raw HTML content |
| `message` | Notice/message box |
| `repeater` | Repeatable field group |
| `group` | Field group |

## Storage API

The Storage class provides a unified interface for all WordPress data storage:

```php
use KP\WPStarterFramework\Framework;

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

## Advanced Usage

### Manual Initialization

```php
use KP\WPStarterFramework\Framework;

$framework = Framework::getInstance();

$framework->init(
    'https://example.com/wp-content/plugins/my-plugin/assets',
    '/var/www/html/wp-content/plugins/my-plugin/assets'
);
```

### Requirements Check

```php
use KP\WPStarterFramework\Loader;

$requirements = Loader::checkRequirements('6.8', '8.2');

if (!$requirements['valid']) {
    Loader::displayRequirementErrors($requirements['errors']);
    return;
}

$framework = Loader::bootstrap();
```

### Without Composer Autoloader

```php
// Manually include and register the autoloader.
require_once 'path/to/kp-wp-starter-framework/src/Loader.php';

KP\WPStarterFramework\Loader::register();

$framework = KP\WPStarterFramework\Loader::bootstrap();
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
KpWsfAdmin.initLinkSelector();

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

If you encounter any issues or have questions, please [open an issue](https://github.com/developer-developer/kp-wp-starter-framework/issues) on GitHub.
