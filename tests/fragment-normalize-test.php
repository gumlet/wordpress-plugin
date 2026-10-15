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

// Regression: loadHTML() defaults to ISO-8859-1, so UTF-8 attribute text
// (alt/title with accents, ñ, CJK, …) was double-encoded on saveHTML.
$utf8_tag = '<img src="x.webp" alt="Tomates, pak choi y melón" title="Fotografía — ¡Vaya! ñoña">';

$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML(gumlet_normalize_html_fragment_for_dom($utf8_tag), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
libxml_clear_errors();
$roundtrip = $doc->saveHTML($doc->getElementsByTagName('img')[0]);

$ok = strpos($roundtrip, 'alt="Tomates, pak choi y melón"') !== false
    && strpos($roundtrip, 'title="Fotografía — ¡Vaya! ñoña"') !== false;

if (!$ok) {
    fwrite(STDERR, "FAIL: non-ASCII attributes corrupted on loadHTML/saveHTML.\nGot: $roundtrip\n");
    exit(1);
}

echo "OK: utf8 attribute round-trip\n";
