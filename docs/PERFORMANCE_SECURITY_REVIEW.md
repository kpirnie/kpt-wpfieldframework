# Performance & Security Review

## Implemented in this changeset

- Scoped AJAX export/import authorization to each options page capability instead of always requiring `manage_options`.
- Added a maximum JSON payload size guard for AJAX imports via filter `kp_wsf_max_import_bytes` (default: 1 MB).
- Hardened file import validation with `is_uploaded_file()`, upload size checking against `wp_max_upload_size()`, and `wp_check_filetype_and_ext()`.

## Additional performance recommendations

1. **Limit Select2 initialization scope**
   - Current script initializes Select2 on every `select[multiple]` in admin pages where framework assets load.
   - Use a narrower selector (e.g. `.kp-wsf-field select[multiple]`) to reduce DOM scans and avoid touching non-framework inputs.

2. **Debounce conditional logic evaluations**
   - Repeater-heavy pages can trigger many change handlers.
   - Debounce conditional evaluation callbacks to avoid redundant layout work.

3. **Use per-field lazy initialization for heavy widgets**
   - Editors/date pickers can be initialized when a repeater row expands or becomes visible.
   - This improves initial render time on large settings screens.

4. **Avoid exporting defaults unless requested**
   - `exportWithDefaults()` can produce much larger payloads.
   - Add UI toggle: "Export only changed values" for faster transfer and smaller files.

## Additional security recommendations

1. **Validate imported option shapes per page schema**
   - Import currently writes whitelisted option keys, but nested values are not schema-validated.
   - Validate against field definitions from `OptionsPage::getAllFields()` before persisting.

2. **Rate-limit import attempts**
   - Add transient-based throttling per user/session for import endpoint to reduce abuse potential.

3. **Audit HTML-capable field rendering defaults**
   - Ensure rich text/code/html fields always use explicit escaping or strict `wp_kses` allowlists at output time.

4. **Log import/export admin events**
   - Track user ID, page slug, timestamp, and result to aid incident response and change auditing.
