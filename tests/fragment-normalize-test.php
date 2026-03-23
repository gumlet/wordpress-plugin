<?php
/**
 * CLI regression: gumlet_normalize_html_fragment_for_dom (no WordPress).
 * Run: php tests/fragment-normalize-test.php
 */

if (!function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        return stripslashes($value);
    }
}

require_once dirname(__DIR__) . '/includes/gumlet-html-fragment.php';

$escaped = '<img fetchpriority="high" src="http:\/\/wordpress.turingiq.com\/wp-content\/uploads\/2025\/03\/screenshot-7-1024x545.png" srcset="https:\/\/wordpress.turingiq.com\/wp-content\/uploads\/2025\/03\/screenshot-7.png 1440w" />';

$normalized = gumlet_normalize_html_fragment_for_dom($escaped);

$ok = strpos($normalized, 'http://wordpress.turingiq.com/wp-content') !== false
    && strpos($normalized, 'https://wordpress.turingiq.com/wp-content') !== false
    && strpos($normalized, '\\/') === false;

if (!$ok) {
    fwrite(STDERR, "FAIL: expected unescaped URLs in fragment.\nGot: $normalized\n");
    exit(1);
}

echo "OK: fragment normalize\n";
