<?php

/**
 * Storage - Unified storage API
 *
 * Provides a unified interface for storing and retrieving data
 * from WordPress options and post/user/term meta tables.
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
 * Class Storage
 *
 * Handles all data storage operations for the framework,
 * abstracting WordPress options and meta APIs.
 *
 * @since 1.0.0
 */
class Storage
{
    /**
     * Cache for retrieved values.
     *
     * @since 1.0.0
     * @var array
     */
    private array $cache = array();
    /**
     * Whether to use caching.
     *
     * @since 1.0.0
     * @var bool
     */
    private bool $use_cache = true;
    /**
     * Constructor.
     *
     * @since 1.0.0
     * @param bool $use_cache Whether to enable caching.
     */
    public function __construct(bool $use_cache = true)
    {
        $this->use_cache = $use_cache;
    }

    // =========================================================================
    // Options API
    // =========================================================================

    /**
     * Get an option value.
     *
     * @since  1.0.0
     * @param  string $option  The option name.
     * @param  mixed  $default Default value if option doesn't exist.
     * @return mixed           The option value.
     */
    public function getOption(string $option, mixed $default = null): mixed
    {
        $cache_key = 'option_' . $option;
        if ($this->use_cache && isset($this->cache[ $cache_key ])) {
            return $this->cache[ $cache_key ];
        }

        $value = get_option($option, $default);
        if ($this->use_cache) {
            $this->cache[ $cache_key ] = $value;
        }

        return $value;
    }

    /**
     * Update an option value.
     *
     * @since  1.0.0
     * @param  string $option   The option name.
     * @param  mixed  $value    The value to set.
     * @param  bool   $autoload Whether to autoload the option.
     * @return bool             True on success, false on failure.
     */
    public function updateOption(string $option, mixed $value, bool $autoload = true): bool
    {
        $result = update_option($option, $value, $autoload);
        // Update cache.
        if ($this->use_cache) {
            $this->cache[ 'option_' . $option ] = $value;
        }

        return $result;
    }

    /**
     * Delete an option.
     *
     * @since  1.0.0
     * @param  string $option The option name.
     * @return bool           True on success, false on failure.
     */
    public function deleteOption(string $option): bool
    {
        $result = delete_option($option);
        // Clear cache.
        if ($this->use_cache) {
            unset($this->cache[ 'option_' . $option ]);
        }

        return $result;
    }

    /**
     * Get a specific key from an option array.
     *
     * @since  1.0.0
     * @param  string $option  The option name.
     * @param  string $key     The array key to retrieve.
     * @param  mixed  $default Default value if key doesn't exist.
     * @return mixed           The value.
     */
    public function getOptionKey(string $option, string $key, mixed $default = null): mixed
    {
        $options = $this->getOption($option, array());
        if (! is_array($options)) {
            return $default;
        }

        return $options[ $key ] ?? $default;
    }

    /**
     * Update a specific key in an option array.
     *
     * @since  1.0.0
     * @param  string $option The option name.
     * @param  string $key    The array key to update.
     * @param  mixed  $value  The value to set.
     * @return bool           True on success, false on failure.
     */
    public function updateOptionKey(string $option, string $key, mixed $value): bool
    {
        $options = $this->getOption($option, array());
        if (! is_array($options)) {
            $options = array();
        }

        $options[ $key ] = $value;
        return $this->updateOption($option, $options);
    }

    /**
     * Delete a specific key from an option array.
     *
     * @since  1.0.0
     * @param  string $option The option name.
     * @param  string $key    The array key to delete.
     * @return bool           True on success, false on failure.
     */
    public function deleteOptionKey(string $option, string $key): bool
    {
        $options = $this->getOption($option, array());
        if (! is_array($options) || ! isset($options[ $key ])) {
            return false;
        }

        unset($options[ $key ]);
        return $this->updateOption($option, $options);
    }

    // =========================================================================
    // Post Meta API
    // =========================================================================

    /**
     * Get post meta value.
     *
     * @since  1.0.0
     * @param  int    $post_id  The post ID.
     * @param  string $meta_key The meta key.
     * @param  mixed  $default  Default value if meta doesn't exist.
     * @return mixed            The meta value.
     */
    public function getMeta(int $post_id, string $meta_key, mixed $default = null): mixed
    {
        $cache_key = 'post_meta_' . $post_id . '_' . $meta_key;
        if ($this->use_cache && isset($this->cache[ $cache_key ])) {
            return $this->cache[ $cache_key ];
        }

        $value = get_post_meta($post_id, $meta_key, true);
        // Return default if empty.
        if ($value === '' || $value === false) {
            $value = $default;
        }

        if ($this->use_cache) {
            $this->cache[ $cache_key ] = $value;
        }

        return $value;
    }

    /**
     * Update post meta value.
     *
     * @since  1.0.0
     * @param  int    $post_id  The post ID.
     * @param  string $meta_key The meta key.
     * @param  mixed  $value    The value to set.
     * @return bool             True on success, false on failure.
     */
    public function updateMeta(int $post_id, string $meta_key, mixed $value): bool
    {
        $result = update_post_meta($post_id, $meta_key, $value);
        // Update cache.
        if ($this->use_cache) {
            $this->cache[ 'post_meta_' . $post_id . '_' . $meta_key ] = $value;
        }

        return $result !== false;
    }

    /**
     * Delete post meta value.
     *
     * @since  1.0.0
     * @param  int    $post_id  The post ID.
     * @param  string $meta_key The meta key.
     * @return bool             True on success, false on failure.
     */
    public function deleteMeta(int $post_id, string $meta_key): bool
    {
        $result = delete_post_meta($post_id, $meta_key);
        // Clear cache.
        if ($this->use_cache) {
            unset($this->cache[ 'post_meta_' . $post_id . '_' . $meta_key ]);
        }

        return $result;
    }

    /**
     * Get all meta values for a post.
     *
     * @since  1.0.0
     * @param  int   $post_id   The post ID.
     * @param  array $meta_keys Optional array of specific keys to retrieve.
     * @return array            Associative array of meta values.
     */
    public function getAllMeta(int $post_id, array $meta_keys = array()): array
    {
        $all_meta = get_post_meta($post_id);
        $result = array();
        foreach ($all_meta as $key => $values) {
            // Skip if specific keys requested and this isn't one.
            if (! empty($meta_keys) && ! in_array($key, $meta_keys, true)) {
                continue;
            }

            // get_post_meta returns array of values, we want single.
            $result[ $key ] = maybe_unserialize($values[0] ?? '');
        }

        return $result;
    }

    // =========================================================================
    // User Meta API
    // =========================================================================

    /**
     * Get user meta value.
     *
     * @since  1.0.0
     * @param  int    $user_id  The user ID.
     * @param  string $meta_key The meta key.
     * @param  mixed  $default  Default value if meta doesn't exist.
     * @return mixed            The meta value.
     */
    public function getUserMeta(int $user_id, string $meta_key, mixed $default = null): mixed
    {
        $cache_key = 'user_meta_' . $user_id . '_' . $meta_key;
        if ($this->use_cache && isset($this->cache[ $cache_key ])) {
            return $this->cache[ $cache_key ];
        }

        $value = get_user_meta($user_id, $meta_key, true);
        // Return default if empty.
        if ($value === '' || $value === false) {
            $value = $default;
        }

        if ($this->use_cache) {
            $this->cache[ $cache_key ] = $value;
        }

        return $value;
    }

    /**
     * Update user meta value.
     *
     * @since  1.0.0
     * @param  int    $user_id  The user ID.
     * @param  string $meta_key The meta key.
     * @param  mixed  $value    The value to set.
     * @return bool             True on success, false on failure.
     */
    public function updateUserMeta(int $user_id, string $meta_key, mixed $value): bool
    {
        $result = update_user_meta($user_id, $meta_key, $value);
        // Update cache.
        if ($this->use_cache) {
            $this->cache[ 'user_meta_' . $user_id . '_' . $meta_key ] = $value;
        }

        return $result !== false;
    }

    /**
     * Delete user meta value.
     *
     * @since  1.0.0
     * @param  int    $user_id  The user ID.
     * @param  string $meta_key The meta key.
     * @return bool             True on success, false on failure.
     */
    public function deleteUserMeta(int $user_id, string $meta_key): bool
    {
        $result = delete_user_meta($user_id, $meta_key);
        // Clear cache.
        if ($this->use_cache) {
            unset($this->cache[ 'user_meta_' . $user_id . '_' . $meta_key ]);
        }

        return $result;
    }

    /**
     * Get all meta values for a user.
     *
     * @since  1.0.0
     * @param  int   $user_id   The user ID.
     * @param  array $meta_keys Optional array of specific keys to retrieve.
     * @return array            Associative array of meta values.
     */
    public function getAllUserMeta(int $user_id, array $meta_keys = array()): array
    {
        $all_meta = get_user_meta($user_id);
        $result = array();
        foreach ($all_meta as $key => $values) {
            // Skip if specific keys requested and this isn't one.
            if (! empty($meta_keys) && ! in_array($key, $meta_keys, true)) {
                continue;
            }

            // get_user_meta returns array of values, we want single.
            $result[ $key ] = maybe_unserialize($values[0] ?? '');
        }

        return $result;
    }

    // =========================================================================
    // Term Meta API
    // =========================================================================

    /**
     * Get term meta value.
     *
     * @since  1.0.0
     * @param  int    $term_id  The term ID.
     * @param  string $meta_key The meta key.
     * @param  mixed  $default  Default value if meta doesn't exist.
     * @return mixed            The meta value.
     */
    public function getTermMeta(int $term_id, string $meta_key, mixed $default = null): mixed
    {
        $cache_key = 'term_meta_' . $term_id . '_' . $meta_key;
        if ($this->use_cache && isset($this->cache[ $cache_key ])) {
            return $this->cache[ $cache_key ];
        }

        $value = get_term_meta($term_id, $meta_key, true);
        // Return default if empty.
        if ($value === '' || $value === false) {
            $value = $default;
        }

        if ($this->use_cache) {
            $this->cache[ $cache_key ] = $value;
        }

        return $value;
    }

    /**
     * Update term meta value.
     *
     * @since  1.0.0
     * @param  int    $term_id  The term ID.
     * @param  string $meta_key The meta key.
     * @param  mixed  $value    The value to set.
     * @return bool             True on success, false on failure.
     */
    public function updateTermMeta(int $term_id, string $meta_key, mixed $value): bool
    {
        $result = update_term_meta($term_id, $meta_key, $value);
        // Update cache.
        if ($this->use_cache) {
            $this->cache[ 'term_meta_' . $term_id . '_' . $meta_key ] = $value;
        }

        return $result !== false;
    }

    /**
     * Delete term meta value.
     *
     * @since  1.0.0
     * @param  int    $term_id  The term ID.
     * @param  string $meta_key The meta key.
     * @return bool             True on success, false on failure.
     */
    public function deleteTermMeta(int $term_id, string $meta_key): bool
    {
        $result = delete_term_meta($term_id, $meta_key);
        // Clear cache.
        if ($this->use_cache) {
            unset($this->cache[ 'term_meta_' . $term_id . '_' . $meta_key ]);
        }

        return $result;
    }

    // =========================================================================
    // Comment Meta API
    // =========================================================================

    /**
     * Get comment meta value.
     *
     * @since  1.0.0
     * @param  int    $comment_id The comment ID.
     * @param  string $meta_key   The meta key.
     * @param  mixed  $default    Default value if meta doesn't exist.
     * @return mixed              The meta value.
     */
    public function getCommentMeta(int $comment_id, string $meta_key, mixed $default = null): mixed
    {
        $cache_key = 'comment_meta_' . $comment_id . '_' . $meta_key;
        if ($this->use_cache && isset($this->cache[ $cache_key ])) {
            return $this->cache[ $cache_key ];
        }

        $value = get_comment_meta($comment_id, $meta_key, true);
        // Return default if empty.
        if ($value === '' || $value === false) {
            $value = $default;
        }

        if ($this->use_cache) {
            $this->cache[ $cache_key ] = $value;
        }

        return $value;
    }

    /**
     * Update comment meta value.
     *
     * @since  1.0.0
     * @param  int    $comment_id The comment ID.
     * @param  string $meta_key   The meta key.
     * @param  mixed  $value      The value to set.
     * @return bool               True on success, false on failure.
     */
    public function updateCommentMeta(int $comment_id, string $meta_key, mixed $value): bool
    {
        $result = update_comment_meta($comment_id, $meta_key, $value);
        // Update cache.
        if ($this->use_cache) {
            $this->cache[ 'comment_meta_' . $comment_id . '_' . $meta_key ] = $value;
        }

        return $result !== false;
    }

    /**
     * Delete comment meta value.
     *
     * @since  1.0.0
     * @param  int    $comment_id The comment ID.
     * @param  string $meta_key   The meta key.
     * @return bool               True on success, false on failure.
     */
    public function deleteCommentMeta(int $comment_id, string $meta_key): bool
    {
        $result = delete_comment_meta($comment_id, $meta_key);
        // Clear cache.
        if ($this->use_cache) {
            unset($this->cache[ 'comment_meta_' . $comment_id . '_' . $meta_key ]);
        }

        return $result;
    }

    // =========================================================================
    // Cache Management
    // =========================================================================

    /**
     * Clear the internal cache.
     *
     * @since  1.0.0
     * @param  string|null $prefix Optional prefix to clear specific cache entries.
     * @return void
     */
    public function clearCache(?string $prefix = null): void
    {
        if ($prefix === null) {
            $this->cache = array();
            return;
        }

        foreach ($this->cache as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                unset($this->cache[ $key ]);
            }
        }
    }

    /**
     * Enable or disable caching.
     *
     * @since  1.0.0
     * @param  bool $enable Whether to enable caching.
     * @return void
     */
    public function setUseCache(bool $enable): void
    {
        $this->use_cache = $enable;
        if (! $enable) {
            $this->clearCache();
        }
    }

    /**
     * Check if caching is enabled.
     *
     * @since  1.0.0
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return $this->use_cache;
    }

    // =========================================================================
    // Transients API
    // =========================================================================

    /**
     * Get a transient value.
     *
     * @since  1.0.0
     * @param  string $transient The transient name.
     * @param  mixed  $default   Default value if transient doesn't exist.
     * @return mixed             The transient value.
     */
    public function getTransient(string $transient, mixed $default = null): mixed
    {
        $value = get_transient($transient);
        if ($value === false) {
            return $default;
        }

        return $value;
    }

    /**
     * Set a transient value.
     *
     * @since  1.0.0
     * @param  string $transient  The transient name.
     * @param  mixed  $value      The value to set.
     * @param  int    $expiration Time until expiration in seconds.
     * @return bool               True on success, false on failure.
     */
    public function setTransient(string $transient, mixed $value, int $expiration = 0): bool
    {
        return set_transient($transient, $value, $expiration);
    }

    /**
     * Delete a transient.
     *
     * @since  1.0.0
     * @param  string $transient The transient name.
     * @return bool              True on success, false on failure.
     */
    public function deleteTransient(string $transient): bool
    {
        return delete_transient($transient);
    }

    // =========================================================================
    // Utility Methods
    // =========================================================================

    /**
     * Get storage type for an object.
     *
     * @since  1.0.0
     * @param  string $object_type The object type ('post', 'user', 'term', 'comment', 'option').
     * @param  int    $object_id   The object ID.
     * @param  string $meta_key    The meta key.
     * @param  mixed  $default     Default value.
     * @return mixed               The stored value.
     */
    public function get(string $object_type, int $object_id, string $meta_key, mixed $default = null): mixed
    {
        return match ($object_type) {
            'post'    => $this->getMeta($object_id, $meta_key, $default),
            'user'    => $this->getUserMeta($object_id, $meta_key, $default),
            'term'    => $this->getTermMeta($object_id, $meta_key, $default),
            'comment' => $this->getCommentMeta($object_id, $meta_key, $default),
            default   => $default,
        };
    }

    /**
     * Update storage for an object.
     *
     * @since  1.0.0
     * @param  string $object_type The object type ('post', 'user', 'term', 'comment').
     * @param  int    $object_id   The object ID.
     * @param  string $meta_key    The meta key.
     * @param  mixed  $value       The value to set.
     * @return bool                True on success, false on failure.
     */
    public function update(string $object_type, int $object_id, string $meta_key, mixed $value): bool
    {
        return match ($object_type) {
            'post'    => $this->updateMeta($object_id, $meta_key, $value),
            'user'    => $this->updateUserMeta($object_id, $meta_key, $value),
            'term'    => $this->updateTermMeta($object_id, $meta_key, $value),
            'comment' => $this->updateCommentMeta($object_id, $meta_key, $value),
            default   => false,
        };
    }

    /**
     * Delete storage for an object.
     *
     * @since  1.0.0
     * @param  string $object_type The object type ('post', 'user', 'term', 'comment').
     * @param  int    $object_id   The object ID.
     * @param  string $meta_key    The meta key.
     * @return bool                True on success, false on failure.
     */
    public function delete(string $object_type, int $object_id, string $meta_key): bool
    {
        return match ($object_type) {
            'post'    => $this->deleteMeta($object_id, $meta_key),
            'user'    => $this->deleteUserMeta($object_id, $meta_key),
            'term'    => $this->deleteTermMeta($object_id, $meta_key),
            'comment' => $this->deleteCommentMeta($object_id, $meta_key),
            default   => false,
        };
    }
}
