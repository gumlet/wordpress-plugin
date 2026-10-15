<?php
/**
 * Normalize HTML snippets before DOMDocument::loadHTML (JSON-escaped quotes/slashes).
 *
 * @package gumlet-wordpress
 * @param string $fragment Raw matched tag from output buffer.
 * @return string
 */
function gumlet_normalize_html_fragment_for_dom($fragment)
{
    if (!is_string($fragment) || $fragment === '') {
        return $fragment;
    }
    if (function_exists('wp_unslash')) {
        $fragment = wp_unslash($fragment);
    } else {
        $fragment = stripslashes($fragment);
    }
    // JSON encodes forward slashes in URLs as \/ — libxml needs real slashes.
    $fragment = str_replace('\\/', '/', $fragment);
    // loadHTML() assumes ISO-8859-1 unless told otherwise — declare UTF-8 so
    // non-ASCII attribute text (alt, title) isn't double-encoded on saveHTML.
    return '<?xml encoding="UTF-8">' . $fragment;
}
