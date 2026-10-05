<?php
/**
 * Sign Gumlet image URLs.
 *
 * The signature is MD5 of the secure token, the image path, and the query string.
 * Gumlet rejects a request when `s` is missing or the path or query is changed.
 *
 * @package gumlet-wordpress
 */

if (!function_exists('gumlet_sign_image_url')) {
    /**
     * Append a Gumlet signature to an image URL.
     *
     * Payload is `token/path`, plus `?query` when the URL has a query. The hex
     * digest is appended as `s`. Query parameter order is preserved. An existing
     * `s` is replaced. A positive `$expires` unix timestamp is written into the
     * query before signing.
     *
     * @param string   $url
     * @param string   $token   Secure token from the image source.
     * @param int|null $expires Unix timestamp, or null to leave expiry unset.
     * @return string
     */
    function gumlet_sign_image_url($url, $token, $expires = null)
    {
        $token = trim((string) $token);
        if ($token === '' || !is_string($url) || $url === '') {
            return $url;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['path'])) {
            return $url;
        }

        $query = isset($parts['query']) ? gumlet_sign_strip_query_param($parts['query'], 's') : '';
        if ($expires !== null && (int) $expires > 0) {
            $query = gumlet_sign_upsert_query_param($query, 'expires', (string) (int) $expires);
        }

        $payload = $token . '/' . ltrim($parts['path'], '/');
        if ($query !== '') {
            $payload .= '?' . $query;
        }

        $parts['query'] = ($query === '' ? '' : $query . '&') . 's=' . md5($payload);

        return http_build_url($parts);
    }
}

if (!function_exists('gumlet_sign_strip_query_param')) {
    /**
     * Remove one query parameter without reordering the rest.
     *
     * @param string $query
     * @param string $name
     * @return string
     */
    function gumlet_sign_strip_query_param($query, $name)
    {
        if ($query === '') {
            return '';
        }

        $kept = array();
        foreach (explode('&', $query) as $part) {
            if ($part === '') {
                continue;
            }
            $key = $part;
            $eq = strpos($part, '=');
            if ($eq !== false) {
                $key = substr($part, 0, $eq);
            }
            if (rawurldecode($key) === $name) {
                continue;
            }
            $kept[] = $part;
        }

        return implode('&', $kept);
    }
}

if (!function_exists('gumlet_sign_upsert_query_param')) {
    /**
     * Set a query parameter, replacing it in place when it already exists.
     *
     * @param string $query
     * @param string $name
     * @param string $value
     * @return string
     */
    function gumlet_sign_upsert_query_param($query, $name, $value)
    {
        $parts = ($query === '') ? array() : explode('&', $query);
        $assignment = $name . '=' . $value;
        $replaced = false;

        foreach ($parts as $index => $part) {
            if ($part === '') {
                continue;
            }
            $key = $part;
            $eq = strpos($part, '=');
            if ($eq !== false) {
                $key = substr($part, 0, $eq);
            }
            if (rawurldecode($key) === $name) {
                $parts[$index] = $assignment;
                $replaced = true;
            }
        }

        if (!$replaced) {
            $parts[] = $assignment;
        }

        $kept = array();
        foreach ($parts as $part) {
            if ($part !== '') {
                $kept[] = $part;
            }
        }

        return implode('&', $kept);
    }
}
