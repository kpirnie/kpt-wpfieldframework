/**
 * KP WP Starter Framework - Admin JavaScript
 *
 * Handles all admin-side functionality including:
 * - Repeater field management (add, remove, sort rows)
 * - Media uploads (image, file, gallery)
 * - Color picker initialization
 * - Date picker initialization
 * - Code editor initialization
 * - Range slider value display
 * - Conditional field logic
 *
 * @package     KP\WPFieldFramework
 * @author      Kevin Pirnie <iam@kevinpirnie.com>
 * @copyright   2025 Kevin Pirnie
 * @license     MIT
 * @since       1.0.0
 */

(function ($) {
    'use strict';

    /**
     * Main framework admin object.
     *
     * @since 1.0.0
     * @type {Object}
     */
    const KpWsfAdmin = {

        /**
         * Initialize all framework functionality.
         *
         * @since 1.0.0
         * @return {void}
         */
        init: function () {
            this.initRepeaters();
            this.initMediaUploads();
            this.initColorPickers();
            this.initDatePickers();
            this.initCodeEditors();
            this.initRangeSliders();
            this.initGallery();
            this.initLinkSelector();
            this.initMultiSelector();
            this.initConditionals();
            this.initExportImport();
            this.initAccordions();
        },

        /**
         * Initialize Select2 functionality.
         *
         * @since 1.0.0
         * @return {void}
         */
        initMultiSelector: function () {
            $("select[multiple='multiple']").select2({ width: '100%', placeholder: "Select an option..." });
        },

        /**
         * Initialize accordion functionality.
         *
         * @since 1.0.0
         * @return {void}
         */
        initAccordions: function () {
            $(document).on('click', '.kp-wsf-accordion__header', function (e) {
                e.preventDefault();
                $(this).closest('.kp-wsf-accordion').toggleClass('kp-wsf-accordion--open');
            });
        },

        // =====================================================================
        // Link Selector
        // =====================================================================

        /**
         * Initialize link selector functionality.
         *
         * @since 1.0.0
         * @return {void}
         */
        initLinkSelector: function () {
            // Ensure wpLink is available.
            if (typeof wpLink === 'undefined') {
                return;
            }

            $(document).on('click', '.kp-wsf-link-select', function (e) {
                e.preventDefault();

                const $button = $(this);
                const targetId = $button.data('target');
                const $container = $button.closest('.kp-wsf-link-field');
                const $urlInput = $container.find('.kp-wsf-link-url');
                const $titleInput = $container.find('.kp-wsf-link-title');

                // Store reference to current field.
                window.kpWsfCurrentLinkField = {
                    container: $container,
                    urlInput: $urlInput,
                    titleInput: $titleInput
                };

                // Set current values in wpLink.
                $('#wp-link-url').val($urlInput.val());
                $('#wp-link-text').val($titleInput.val());

                // Open wpLink dialog.
                wpLink.open(targetId + '_url');
            });

            // Handle wpLink submit.
            $(document).on('wplink-close', function () {
                if (!window.kpWsfCurrentLinkField) {
                    return;
                }

                const linkAtts = wpLink.getAttrs();
                const $field = window.kpWsfCurrentLinkField;

                if (linkAtts.href) {
                    $field.urlInput.val(linkAtts.href);
                }

                const linkText = $('#wp-link-text').val();
                if (linkText) {
                    $field.titleInput.val(linkText);
                }

                if (linkAtts.target === '_blank') {
                    $field.container.find('.kp-wsf-link-target').prop('checked', true);
                }

                window.kpWsfCurrentLinkField = null;
            });
        },

        // =====================================================================
        // Repeater Fields
        // =====================================================================

        /**
         * Initialize repeater field functionality.
         *
         * @since 1.0.0
         * @return {void}
         */
        initRepeaters: function () {
            const self = this;

            // Add row button.
            $(document).on('click', '.kp-wsf-repeater__add', function (e) {
                e.preventDefault();
                self.repeaterAddRow($(this).closest('.kp-wsf-repeater'));
            });

            // Remove row button.
            $(document).on('click', '.kp-wsf-repeater__remove', function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (confirm(kpWsfAdmin.i18n.confirmDelete || 'Are you sure you want to remove this item?')) {
                    self.repeaterRemoveRow($(this).closest('.kp-wsf-repeater__row'));
                }
            });

            // Toggle row collapse.
            $(document).on('click', '.kp-wsf-repeater__toggle', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).closest('.kp-wsf-repeater__row').toggleClass('kp-wsf-repeater__row--collapsed');
            });

            // Row header click to toggle.
            $(document).on('click', '.kp-wsf-repeater__row-header', function (e) {
                // Don't toggle if clicking on buttons.
                if ($(e.target).closest('button').length) {
                    return;
                }
                $(this).closest('.kp-wsf-repeater__row').toggleClass('kp-wsf-repeater__row--collapsed');
            });

            // Initialize sortable on existing repeaters.
            this.initRepeaterSortable();
        },

        /**
         * Initialize sortable functionality for repeater rows.
         *
         * @since 1.0.0
         * @return {void}
         */
        initRepeaterSortable: function () {
            const self = this;

            $('.kp-wsf-repeater__rows').each(function () {
                if ($(this).data('ui-sortable')) {
                    return; // Already initialized.
                }

                $(this).sortable({
                    handle: '.kp-wsf-repeater__drag',
                    placeholder: 'kp-wsf-repeater__row ui-sortable-placeholder',
                    forcePlaceholderSize: true,
                    opacity: 0.8,
                    tolerance: 'pointer',
                    start: function (event, ui) {
                        ui.placeholder.height(ui.item.height());
                    },
                    stop: function (event, ui) {
                        self.repeaterUpdateIndexes($(this).closest('.kp-wsf-repeater'));
                    }
                });
            });
        },

        /**
         * Add a new row to a repeater.
         *
         * @since 1.0.0
         * @param {jQuery} $repeater The repeater container element.
         * @return {void}
         */
        repeaterAddRow: function ($repeater) {
            const $rows = $repeater.find('.kp-wsf-repeater__rows');
            const $template = $repeater.find('.kp-wsf-repeater__template');
            const maxRows = parseInt($repeater.data('max-rows'), 10) || 0;
            const currentRows = $rows.find('.kp-wsf-repeater__row:not(.kp-wsf-repeater__row--template)').length;

            // Check max rows limit.
            if (maxRows > 0 && currentRows >= maxRows) {
                alert('Maximum number of rows reached.');
                return;
            }

            // Get template HTML and replace index placeholder.
            let template = $template.html();
            const newIndex = this.repeaterGetNextIndex($repeater);

            template = template.replace(/\{\{INDEX\}\}/g, newIndex);
            template = template.replace(/\_\_INDEX\_\_/g, newIndex);

            // Create new row.
            const $newRow = $(template);
            $newRow.removeClass('kp-wsf-repeater__row--template');

            // Append to rows container.
            $rows.append($newRow);

            // Update row numbers.
            this.repeaterUpdateIndexes($repeater);

            // Initialize any special fields in the new row.
            this.initRowFields($newRow);

            // Re-initialize sortable.
            this.initRepeaterSortable();

            // Trigger event.
            $(document).trigger('kp-wsf-repeater-row-added', [$newRow, $repeater]);
        },

        /**
         * Remove a row from a repeater.
         *
         * @since 1.0.0
         * @param {jQuery} $row The row element to remove.
         * @return {void}
         */
        repeaterRemoveRow: function ($row) {
            const $repeater = $row.closest('.kp-wsf-repeater');
            const minRows = parseInt($repeater.data('min-rows'), 10) || 0;
            const $rows = $repeater.find('.kp-wsf-repeater__rows');
            const currentRows = $rows.find('.kp-wsf-repeater__row:not(.kp-wsf-repeater__row--template)').length;

            // Check min rows limit.
            if (minRows > 0 && currentRows <= minRows) {
                alert('Minimum number of rows reached.');
                return;
            }

            // Trigger event before removal.
            $(document).trigger('kp-wsf-repeater-row-before-remove', [$row, $repeater]);

            // Remove the row with animation.
            $row.slideUp(200, function () {
                $(this).remove();

                // Update row numbers.
                KpWsfAdmin.repeaterUpdateIndexes($repeater);

                // Trigger event after removal.
                $(document).trigger('kp-wsf-repeater-row-removed', [$repeater]);
            });
        },

        /**
         * Get the next available index for a repeater.
         *
         * @since 1.0.0
         * @param {jQuery} $repeater The repeater container element.
         * @return {number} The next index.
         */
        repeaterGetNextIndex: function ($repeater) {
            let maxIndex = -1;

            $repeater.find('.kp-wsf-repeater__row:not(.kp-wsf-repeater__row--template)').each(function () {
                const index = parseInt($(this).data('row-index'), 10) || 0;
                if (index > maxIndex) {
                    maxIndex = index;
                }
            });

            return maxIndex + 1;
        },

        /**
         * Update row indexes and display numbers after reordering.
         *
         * @since 1.0.0
         * @param {jQuery} $repeater The repeater container element.
         * @return {void}
         */
        repeaterUpdateIndexes: function ($repeater) {
            const fieldId = $repeater.data('field-id');

            $repeater.find('.kp-wsf-repeater__row:not(.kp-wsf-repeater__row--template)').each(function (index) {
                const $row = $(this);
                const oldIndex = $row.data('row-index');

                // Update row index data attribute.
                $row.attr('data-row-index', index);
                $row.data('row-index', index);

                // Update display number.
                $row.find('.kp-wsf-repeater__row-number').first().text(index + 1);

                // Update field names and IDs within the row.
                $row.find('[name]').each(function () {
                    const $field = $(this);
                    let name = $field.attr('name');

                    // Replace old index with new index in field names.
                    if (name) {
                        // Pattern: fieldId[oldIndex][subfield] -> fieldId[newIndex][subfield]
                        const pattern = new RegExp('\\[' + oldIndex + '\\]', 'g');
                        name = name.replace(pattern, '[' + index + ']');
                        $field.attr('name', name);
                    }
                });

                // Update field IDs.
                $row.find('[id]').each(function () {
                    const $field = $(this);
                    let id = $field.attr('id');

                    if (id) {
                        // Pattern: fieldId_oldIndex_subfield -> fieldId_newIndex_subfield
                        const pattern = new RegExp('_' + oldIndex + '_', 'g');
                        id = id.replace(pattern, '_' + index + '_');
                        $field.attr('id', id);
                    }
                });

                // Update labels.
                $row.find('label[for]').each(function () {
                    const $label = $(this);
                    let forAttr = $label.attr('for');

                    if (forAttr) {
                        const pattern = new RegExp('_' + oldIndex + '_', 'g');
                        forAttr = forAttr.replace(pattern, '_' + index + '_');
                        $label.attr('for', forAttr);
                    }
                });
            });
        },

        /**
         * Initialize special fields within a row.
         *
         * @since 1.0.0
         * @param {jQuery} $row The row element.
         * @return {void}
         */
        initRowFields: function ($row) {
            // Initialize color pickers.
            $row.find('.kp-wsf-color-picker').each(function () {
                if (!$(this).hasClass('wp-color-picker')) {
                    $(this).wpColorPicker();
                }
            });

            // Initialize date pickers.
            $row.find('.kp-wsf-datepicker').each(function () {
                if (!$(this).hasClass('hasDatepicker')) {
                    $(this).datepicker({
                        dateFormat: $(this).data('date-format') || 'yy-mm-dd',
                        changeMonth: true,
                        changeYear: true
                    });
                }
            });

            // Initialize code editors.
            if (typeof wp !== 'undefined' && wp.codeEditor) {
                $row.find('.kp-wsf-code-editor').each(function () {
                    if (!$(this).data('code-editor-initialized')) {
                        const settings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
                        settings.codemirror = _.extend({}, settings.codemirror, {
                            mode: $(this).data('code-type') || 'text/html'
                        });
                        wp.codeEditor.initialize($(this), settings);
                        $(this).data('code-editor-initialized', true);
                    }
                });
            }

            // Initialize range sliders.
            $row.find('input[type="range"]').each(function () {
                const $range = $(this);
                const $value = $range.siblings('.kp-wsf-range-value');

                if ($value.length) {
                    $value.text($range.val());
                }
            });
        },

        // =====================================================================
        // Media Uploads
        // =====================================================================

        /**
         * Initialize media upload functionality.
         *
         * @since 1.0.0
         * @return {void}
         */
        initMediaUploads: function () {
            const self = this;

            // Image upload.
            $(document).on('click', '.kp-wsf-upload-image', function (e) {
                e.preventDefault();
                self.openMediaUploader($(this), 'image');
            });

            // Image remove.
            $(document).on('click', '.kp-wsf-remove-image', function (e) {
                e.preventDefault();
                self.removeMedia($(this), 'image');
            });

            // File upload.
            $(document).on('click', '.kp-wsf-upload-file', function (e) {
                e.preventDefault();
                self.openMediaUploader($(this), 'file');
            });

            // File remove.
            $(document).on('click', '.kp-wsf-remove-file', function (e) {
                e.preventDefault();
                self.removeMedia($(this), 'file');
            });
        },

        /**
         * Open the WordPress media uploader.
         *
         * @since 1.0.0
         * @param {jQuery} $button The button that was clicked.
         * @param {string} type    The type of upload ('image' or 'file').
         * @return {void}
         */
        openMediaUploader: function ($button, type) {
            const self = this;
            const $container = $button.closest('.kp-wsf-' + type + '-field');

            // Create media frame.
            const frame = wp.media({
                title: kpWsfAdmin.i18n.mediaTitle || 'Select or Upload',
                button: {
                    text: kpWsfAdmin.i18n.mediaButton || 'Use this file'
                },
                library: {
                    type: type === 'image' ? 'image' : ''
                },
                multiple: false
            });

            // Handle selection.
            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();

                if (type === 'image') {
                    self.setImage($container, attachment);
                } else {
                    self.setFile($container, attachment);
                }
            });

            frame.open();
        },

        /**
         * Set the selected image.
         *
         * @since 1.0.0
         * @param {jQuery} $container The image field container.
         * @param {Object} attachment The attachment object.
         * @return {void}
         */
        setImage: function ($container, attachment) {
            const $preview = $container.find('.kp-wsf-image-preview');
            const $input = $container.find('.kp-wsf-image-id');
            const $removeBtn = $container.find('.kp-wsf-remove-image');

            // Get thumbnail URL.
            let imageUrl = attachment.url;
            if (attachment.sizes && attachment.sizes.thumbnail) {
                imageUrl = attachment.sizes.thumbnail.url;
            }

            // Update preview.
            $preview.html('<img src="' + imageUrl + '" alt="" />').show();

            // Update hidden input.
            $input.val(attachment.id);

            // Show remove button.
            $removeBtn.show();
        },

        /**
         * Set the selected file.
         *
         * @since 1.0.0
         * @param {jQuery} $container The file field container.
         * @param {Object} attachment The attachment object.
         * @return {void}
         */
        setFile: function ($container, attachment) {
            const $info = $container.find('.kp-wsf-file-info');
            const $filename = $container.find('.kp-wsf-filename');
            const $link = $container.find('.kp-wsf-file-link');
            const $input = $container.find('.kp-wsf-file-id');
            const $removeBtn = $container.find('.kp-wsf-remove-file');

            // Update file info.
            $filename.text(attachment.filename);

            if ($link.length) {
                $link.attr('href', attachment.url);
            } else {
                $filename.after(' <a href="' + attachment.url + '" target="_blank" class="kp-wsf-file-link">View</a>');
            }

            $info.show();

            // Update hidden input.
            $input.val(attachment.id);

            // Show remove button.
            $removeBtn.show();
        },

        /**
         * Remove a media item.
         *
         * @since 1.0.0
         * @param {jQuery} $button The remove button.
         * @param {string} type    The type of media ('image' or 'file').
         * @return {void}
         */
        removeMedia: function ($button, type) {
            const $container = $button.closest('.kp-wsf-' + type + '-field');

            if (type === 'image') {
                $container.find('.kp-wsf-image-preview').empty().hide();
                $container.find('.kp-wsf-image-id').val('');
            } else {
                $container.find('.kp-wsf-file-info').hide();
                $container.find('.kp-wsf-filename').text('');
                $container.find('.kp-wsf-file-link').remove();
                $container.find('.kp-wsf-file-id').val('');
            }

            $button.hide();
        },

        // =====================================================================
        // Gallery Field
        // =====================================================================

        /**
         * Initialize gallery field functionality.
         *
         * @since 1.0.0
         * @return {void}
         */
        initGallery: function () {
            const self = this;

            // Add images to gallery.
            $(document).on('click', '.kp-wsf-add-gallery', function (e) {
                e.preventDefault();
                self.openGalleryUploader($(this));
            });

            // Remove single image from gallery.
            $(document).on('click', '.kp-wsf-gallery-remove', function (e) {
                e.preventDefault();
                e.stopPropagation();
                self.removeGalleryImage($(this));
            });

            // Clear entire gallery.
            $(document).on('click', '.kp-wsf-clear-gallery', function (e) {
                e.preventDefault();

                if (confirm(kpWsfAdmin.i18n.confirmDelete || 'Are you sure?')) {
                    self.clearGallery($(this));
                }
            });

            // Initialize sortable galleries.
            this.initGallerySortable();
        },

        /**
         * Initialize sortable functionality for gallery items.
         *
         * @since 1.0.0
         * @return {void}
         */
        initGallerySortable: function () {
            const self = this;

            $('.kp-wsf-gallery-preview').each(function () {
                if ($(this).data('ui-sortable')) {
                    return;
                }

                $(this).sortable({
                    items: '.kp-wsf-gallery-item',
                    placeholder: 'kp-wsf-gallery-placeholder',
                    tolerance: 'pointer',
                    stop: function () {
                        self.updateGalleryIds($(this).closest('.kp-wsf-gallery-field'));
                    }
                });
            });
        },

        /**
         * Open the gallery uploader.
         *
         * @since 1.0.0
         * @param {jQuery} $button The add button.
         * @return {void}
         */
        openGalleryUploader: function ($button) {
            const self = this;
            const $container = $button.closest('.kp-wsf-gallery-field');

            const frame = wp.media({
                title: kpWsfAdmin.i18n.mediaTitle || 'Select Images',
                button: {
                    text: kpWsfAdmin.i18n.mediaButton || 'Add to Gallery'
                },
                library: {
                    type: 'image'
                },
                multiple: true
            });

            frame.on('select', function () {
                const attachments = frame.state().get('selection').toJSON();
                self.addGalleryImages($container, attachments);
            });

            frame.open();
        },

        /**
         * Add images to the gallery.
         *
         * @since 1.0.0
         * @param {jQuery} $container   The gallery container.
         * @param {Array}  attachments  Array of attachment objects.
         * @return {void}
         */
        addGalleryImages: function ($container, attachments) {
            const $preview = $container.find('.kp-wsf-gallery-preview');
            const $clearBtn = $container.find('.kp-wsf-clear-gallery');

            attachments.forEach(function (attachment) {
                // Check if already in gallery.
                if ($preview.find('[data-id="' + attachment.id + '"]').length) {
                    return;
                }

                let imageUrl = attachment.url;
                if (attachment.sizes && attachment.sizes.thumbnail) {
                    imageUrl = attachment.sizes.thumbnail.url;
                }

                const $item = $('<div class="kp-wsf-gallery-item" data-id="' + attachment.id + '">' +
                    '<img src="' + imageUrl + '" alt="" />' +
                    '<button type="button" class="kp-wsf-gallery-remove">&times;</button>' +
                    '</div>');

                $preview.append($item);
            });

            // Update hidden input.
            this.updateGalleryIds($container);

            // Show clear button.
            $clearBtn.show();

            // Re-initialize sortable.
            this.initGallerySortable();
        },

        /**
         * Remove a single image from the gallery.
         *
         * @since 1.0.0
         * @param {jQuery} $button The remove button.
         * @return {void}
         */
        removeGalleryImage: function ($button) {
            const $container = $button.closest('.kp-wsf-gallery-field');
            const $item = $button.closest('.kp-wsf-gallery-item');

            $item.fadeOut(200, function () {
                $(this).remove();
                KpWsfAdmin.updateGalleryIds($container);

                // Hide clear button if no images left.
                if ($container.find('.kp-wsf-gallery-item').length === 0) {
                    $container.find('.kp-wsf-clear-gallery').hide();
                }
            });
        },

        /**
         * Clear all images from the gallery.
         *
         * @since 1.0.0
         * @param {jQuery} $button The clear button.
         * @return {void}
         */
        clearGallery: function ($button) {
            const $container = $button.closest('.kp-wsf-gallery-field');

            $container.find('.kp-wsf-gallery-preview').empty();
            $container.find('.kp-wsf-gallery-ids').val('');
            $button.hide();
        },

        /**
         * Update the hidden input with current gallery IDs.
         *
         * @since 1.0.0
         * @param {jQuery} $container The gallery container.
         * @return {void}
         */
        updateGalleryIds: function ($container) {
            const ids = [];

            $container.find('.kp-wsf-gallery-item').each(function () {
                ids.push($(this).data('id'));
            });

            $container.find('.kp-wsf-gallery-ids').val(ids.join(','));
        },

        // =====================================================================
        // Color Pickers
        // =====================================================================

        /**
         * Initialize WordPress color pickers.
         *
         * @since 1.0.0
         * @return {void}
         */
        initColorPickers: function () {
            $('.kp-wsf-color-picker').each(function () {
                if (!$(this).hasClass('wp-color-picker')) {
                    const defaultColor = $(this).data('default-color') || '';

                    $(this).wpColorPicker({
                        defaultColor: defaultColor,
                        change: function (event, ui) {
                            $(this).trigger('change');
                        },
                        clear: function () {
                            $(this).trigger('change');
                        }
                    });
                }
            });
        },

        // =====================================================================
        // Date Pickers
        // =====================================================================

        /**
         * Initialize jQuery UI date pickers.
         *
         * @since 1.0.0
         * @return {void}
         */
        initDatePickers: function () {
            $('.kp-wsf-datepicker').each(function () {
                if (!$(this).hasClass('hasDatepicker')) {
                    $(this).datepicker({
                        dateFormat: $(this).data('date-format') || 'yy-mm-dd',
                        changeMonth: true,
                        changeYear: true,
                        showButtonPanel: true
                    });
                }
            });
        },

        // =====================================================================
        // Code Editors
        // =====================================================================

        /**
         * Initialize WordPress code editors (CodeMirror).
         *
         * @since 1.0.0
         * @return {void}
         */
        initCodeEditors: function () {
            if (typeof wp === 'undefined' || !wp.codeEditor) {
                return;
            }

            $('.kp-wsf-code-editor').each(function () {
                if ($(this).data('code-editor-initialized')) {
                    return;
                }

                const $textarea = $(this);
                const codeType = $textarea.data('code-type') || 'text/html';

                const settings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};

                settings.codemirror = _.extend({}, settings.codemirror, {
                    mode: codeType,
                    lineNumbers: true,
                    lineWrapping: true,
                    indentUnit: 4,
                    tabSize: 4,
                    indentWithTabs: true
                });

                const editor = wp.codeEditor.initialize($textarea, settings);

                // Refresh editor when it becomes visible (for tabbed interfaces).
                if (editor && editor.codemirror) {
                    setTimeout(function () {
                        editor.codemirror.refresh();
                    }, 100);
                }

                $textarea.data('code-editor-initialized', true);
                $textarea.data('code-editor', editor);
            });
        },

        // =====================================================================
        // Range Sliders
        // =====================================================================

        /**
         * Initialize range slider value display.
         *
         * @since 1.0.0
         * @return {void}
         */
        initRangeSliders: function () {
            // Update value display on input.
            $(document).on('input', '.kp-wsf-range-field input[type="range"]', function () {
                const $range = $(this);
                const $value = $range.siblings('.kp-wsf-range-value');

                if ($value.length) {
                    $value.text($range.val());
                }
            });

            // Set initial values.
            $('.kp-wsf-range-field input[type="range"]').each(function () {
                const $range = $(this);
                const $value = $range.siblings('.kp-wsf-range-value');

                if ($value.length) {
                    $value.text($range.val());
                }
            });
        },
        // =====================================================================
        // Conditional Fields
        // =====================================================================

        /**
         * Initialize conditional field logic.
         *
         * @since 1.0.0
         * @return {void}
         */
        initConditionals: function () {
            const self = this;

            // Find all conditional fields and set up listeners.
            $('[data-kp-wsf-conditional]').each(function () {
                const $field = $(this).closest('.kp-wsf-field, .kp-wsf-field-row, tr');
                const conditional = $(this).data('kp-wsf-conditional');

                if (!conditional) {
                    return;
                }

                self.setupConditionalListeners($field, conditional);
                self.evaluateConditional($field, conditional);
            });
        },



        /**
         * Set up event listeners for conditional field dependencies.
         *
         * @since 1.0.0
         * @param {jQuery} $field      The conditional field element.
         * @param {Object} conditional The conditional configuration.
         * @return {void}
         */
        setupConditionalListeners: function ($field, conditional) {
            const self = this;
            const fieldIds = self.getConditionalFieldIds(conditional);

            fieldIds.forEach(function (fieldId) {
                // Find the controlling field by ID or name.
                const $controller = self.findControllerField(fieldId, $field);

                if ($controller.length) {
                    $controller.on('change input', function () {
                        self.evaluateConditional($field, conditional);
                    });
                }
            });
        },

        /**
         * Extract all field IDs from a conditional configuration.
         *
         * @since 1.0.0
         * @param {Object} conditional The conditional configuration.
         * @return {Array} Array of field IDs.
         */
        getConditionalFieldIds: function (conditional) {
            const ids = [];

            if (conditional.AND) {
                conditional.AND.forEach(function (condition) {
                    if (condition.field) {
                        ids.push(condition.field);
                    }
                });
            }

            if (conditional.OR) {
                conditional.OR.forEach(function (condition) {
                    if (condition.field) {
                        ids.push(condition.field);
                    }
                });
            }

            // Single condition without AND/OR.
            if (conditional.field) {
                ids.push(conditional.field);
            }

            return ids;
        },

        /**
         * Find the controller field element.
         *
         * @since 1.0.0
         * @param {string} fieldId The field ID to find.
         * @param {jQuery} $field  The conditional field for context.
         * @return {jQuery} The controller field element.
         */
        findControllerField: function (fieldId, $field) {
            // Try direct ID match.
            let $controller = $('#' + fieldId);

            if ($controller.length) {
                return $controller;
            }

            // Try finding by name attribute (for options pages with array notation).
            $controller = $('[name$="[' + fieldId + ']"]');

            if ($controller.length) {
                return $controller;
            }

            // Try finding within the same context (repeater, meta box, etc.).
            const $context = $field.closest('.kp-wsf-meta-box, .kp-wsf-repeater, .kp-wsf-options-form, form');
            $controller = $context.find('[id="' + fieldId + '"], [name$="[' + fieldId + ']"]');

            return $controller;
        },

        /**
         * Evaluate a conditional and show/hide the field.
         *
         * @since 1.0.0
         * @param {jQuery} $field      The conditional field element.
         * @param {Object} conditional The conditional configuration.
         * @return {void}
         */
        evaluateConditional: function ($field, conditional) {
            const self = this;
            let result = false;

            if (conditional.AND) {
                // All conditions must be true.
                result = conditional.AND.every(function (condition) {
                    return self.evaluateCondition(condition, $field);
                });
            } else if (conditional.OR) {
                // At least one condition must be true.
                result = conditional.OR.some(function (condition) {
                    return self.evaluateCondition(condition, $field);
                });
            } else if (conditional.field) {
                // Single condition.
                result = self.evaluateCondition(conditional, $field);
            }

            // Show or hide the field.
            if (result) {
                $field.slideDown(200).removeClass('kp-wsf-conditional-hidden');
            } else {
                $field.slideUp(200).addClass('kp-wsf-conditional-hidden');
            }
        },

        /**
         * Evaluate a single condition.
         *
         * @since 1.0.0
         * @param {Object} condition The condition to evaluate.
         * @param {jQuery} $field    The conditional field for context.
         * @return {boolean} Whether the condition is met.
         */
        evaluateCondition: function (condition, $field) {
            const self = this;
            const $controller = self.findControllerField(condition.field, $field);

            if (!$controller.length) {
                return false;
            }

            let controllerValue = self.getFieldValue($controller);
            const targetValue = condition.value;
            const operator = condition.condition || '==';

            return self.compareValues(controllerValue, targetValue, operator);
        },

        /**
         * Get the current value of a field.
         *
         * @since 1.0.0
         * @param {jQuery} $field The field element.
         * @return {mixed} The field value.
         */
        getFieldValue: function ($field) {
            const type = $field.attr('type');

            if (type === 'checkbox') {
                return $field.is(':checked');
            }

            if (type === 'radio') {
                return $field.filter(':checked').val();
            }

            if ($field.is('select[multiple]')) {
                return $field.val() || [];
            }

            return $field.val();
        },

        /**
         * Compare two values using an operator.
         *
         * @since 1.0.0
         * @param {mixed}  actual   The actual value.
         * @param {mixed}  expected The expected value.
         * @param {string} operator The comparison operator.
         * @return {boolean} The comparison result.
         */
        compareValues: function (actual, expected, operator) {
            // Handle boolean comparisons.
            if (typeof expected === 'boolean') {
                actual = !!actual;
            }

            switch (operator) {
                case '==':
                case '===':
                    return actual == expected;

                case '!=':
                case '!==':
                    return actual != expected;

                case '>':
                    return parseFloat(actual) > parseFloat(expected);

                case '<':
                    return parseFloat(actual) < parseFloat(expected);

                case '>=':
                    return parseFloat(actual) >= parseFloat(expected);

                case '<=':
                    return parseFloat(actual) <= parseFloat(expected);

                case 'IN':
                    if (Array.isArray(expected)) {
                        return expected.includes(actual);
                    }
                    return String(expected).split(',').map(s => s.trim()).includes(actual);

                case 'NOT_IN':
                    if (Array.isArray(expected)) {
                        return !expected.includes(actual);
                    }
                    return !String(expected).split(',').map(s => s.trim()).includes(actual);

                case 'CONTAINS':
                    if (Array.isArray(actual)) {
                        return actual.includes(expected);
                    }
                    return String(actual).indexOf(expected) !== -1;

                case 'NOT_CONTAINS':
                    if (Array.isArray(actual)) {
                        return !actual.includes(expected);
                    }
                    return String(actual).indexOf(expected) === -1;

                case 'EMPTY':
                    return !actual || actual === '' || (Array.isArray(actual) && actual.length === 0);

                case 'NOT_EMPTY':
                    return actual && actual !== '' && !(Array.isArray(actual) && actual.length === 0);

                default:
                    return actual == expected;
            }
        },

        // =====================================================================
        // Export / Import
        // =====================================================================

        /**
         * Initialize export/import functionality.
         *
         * @since 1.0.0
         * @return {void}
         */
        initExportImport: function () {
            const self = this;

            // Export button click.
            $(document).on('click', '.kp-wsf-export-btn', function (e) {
                e.preventDefault();
                self.exportSettings($(this));
            });

            // Import file selection.
            $(document).on('change', '.kp-wsf-import-file', function () {
                const $btn = $(this).siblings('.kp-wsf-import-btn');
                $btn.prop('disabled', !this.files.length);
            });

            // Import button click.
            $(document).on('click', '.kp-wsf-import-btn', function (e) {
                e.preventDefault();
                self.importSettings($(this));
            });
        },

        /**
         * Export settings via AJAX.
         *
         * @since 1.0.0
         * @param {jQuery} $button The export button.
         * @return {void}
         */
        exportSettings: function ($button) {
            const menuSlug = $button.data('menu-slug') || '';
            const originalText = $button.text();

            $button.prop('disabled', true).text(kpWsfAdmin.i18n.exporting || 'Exporting...');

            $.ajax({
                url: kpWsfAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kp_wsf_export_settings',
                    nonce: kpWsfAdmin.nonce,
                    menu_slug: menuSlug
                },
                success: function (response) {
                    if (response.success) {
                        // Create download.
                        const blob = new Blob([response.data.json], { type: 'application/json' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = response.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    } else {
                        alert(response.data.message || 'Export failed.');
                    }
                },
                error: function () {
                    alert(kpWsfAdmin.i18n.exportError || 'Export failed. Please try again.');
                },
                complete: function () {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Import settings via AJAX.
         *
         * @since 1.0.0
         * @param {jQuery} $button The import button.
         * @return {void}
         */
        importSettings: function ($button) {
            const $container = $button.closest('.kp-wsf-import-section');
            const $fileInput = $container.find('.kp-wsf-import-file');
            const $status = $container.find('.kp-wsf-import-status');
            const menuSlug = $button.data('menu-slug') || '';
            const file = $fileInput[0].files[0];

            if (!file) {
                $status.text(kpWsfAdmin.i18n.noFileSelected || 'Please select a file.');
                return;
            }

            if (!confirm(kpWsfAdmin.i18n.confirmImport || 'This will overwrite your current settings. Continue?')) {
                return;
            }

            const originalText = $button.text();
            $button.prop('disabled', true).text(kpWsfAdmin.i18n.importing || 'Importing...');
            $status.text('');

            const reader = new FileReader();
            reader.onload = function (e) {
                $.ajax({
                    url: kpWsfAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'kp_wsf_import_settings',
                        nonce: kpWsfAdmin.nonce,
                        menu_slug: menuSlug,
                        json: e.target.result
                    },
                    success: function (response) {
                        if (response.success) {
                            $status.html('<span style="color: green;">' + response.data.message + '</span>');
                            if (response.data.errors && response.data.errors.length) {
                                $status.append('<br><span style="color: orange;">Warnings: ' + response.data.errors.join(', ') + '</span>');
                            }
                            // Reload page after short delay to show updated settings.
                            setTimeout(function () {
                                location.reload();
                            }, 1500);
                        } else {
                            $status.html('<span style="color: red;">' + (response.data.message || 'Import failed.') + '</span>');
                            if (response.data.errors && response.data.errors.length) {
                                $status.append('<br><span style="color: red;">' + response.data.errors.join('<br>') + '</span>');
                            }
                        }
                    },
                    error: function () {
                        $status.html('<span style="color: red;">' + (kpWsfAdmin.i18n.importError || 'Import failed. Please try again.') + '</span>');
                    },
                    complete: function () {
                        $button.prop('disabled', false).text(originalText);
                        $fileInput.val('');
                    }
                });
            };

            reader.onerror = function () {
                $status.html('<span style="color: red;">' + (kpWsfAdmin.i18n.fileReadError || 'Failed to read file.') + '</span>');
                $button.prop('disabled', false).text(originalText);
            };

            reader.readAsText(file);
        },

    };

    /**
     * Initialize on document ready.
     */
    $(document).ready(function () {
        KpWsfAdmin.init();
    });

    /**
     * Re-initialize on Gutenberg/block editor panel changes.
     */
    if (typeof wp !== 'undefined' && wp.data && wp.data.subscribe) {
        let previousSelectedBlock = null;

        wp.data.subscribe(function () {
            const selectedBlock = wp.data.select('core/block-editor')?.getSelectedBlock?.();

            if (selectedBlock !== previousSelectedBlock) {
                previousSelectedBlock = selectedBlock;

                // Delay to allow panel to render.
                setTimeout(function () {
                    KpWsfAdmin.initColorPickers();
                    KpWsfAdmin.initDatePickers();
                    KpWsfAdmin.initCodeEditors();
                    KpWsfAdmin.initRangeSliders();
                }, 100);
            }
        });
    }

    // Expose to global scope for external access.
    window.KpWsfAdmin = KpWsfAdmin;

})(jQuery);
