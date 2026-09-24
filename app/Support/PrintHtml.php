<?php

namespace App\Support;

/**
 * Helper for rendering admin-authored rich text inside print (DomPDF)
 * templates. The gallery admin writes cert_intro / footers etc. through a
 * WYSIWYG that emits '<p>...</p>', '<strong>', etc. — feeding that
 * through `e()` was leaking literal '</p>' onto the certificate.
 * Rendering it raw was the fix, but we still want a safety net for the
 * case where someone pastes plain text with hard newlines (nl2br).
 */
class PrintHtml
{
    /**
     * If the string already contains any HTML block/line tag, trust it as
     * HTML. Otherwise treat it as plain text: escape and convert newlines
     * to <br>. Either way returns a string safe to echo raw into a print
     * template with {!! ... !!}.
     */
    public static function render(?string $raw): string
    {
        $raw = (string) $raw;
        if ($raw === '') {
            return '';
        }
        if (preg_match('/<(p|br|div|strong|em|ul|ol|li|h[1-6]|blockquote)\b/i', $raw)) {
            return $raw;
        }

        return nl2br(e($raw), false);
    }
}
