<?php

namespace App\Support;

use HTMLPurifier_URISchemeRegistry;
use Mews\Purifier\Facades\Purifier;

/**
 * Sanitizes and inspects the rich-text HTML TinyMCE (see resources/js/
 * soal-editor.js) produces for a soal's pertanyaan/pilihan fields, before it
 * reaches the database and — rendered via x-html rather than x-text once the
 * CBT exam page catches up — a student's browser.
 */
class SoalContentPurifier
{
    /**
     * Strips anything TinyMCE's own toolbar couldn't have produced (script
     * tags, event handler attributes, etc.), using the 'soal' HTMLPurifier
     * preset in config/purifier.php — which is scoped to exactly what that
     * toolbar can output (lists, table, image, superscript/subscript), not
     * HTMLPurifier's much broader "default" preset.
     */
    public static function bersihkan(?string $html): string
    {
        return Purifier::clean($html ?? '', 'soal', function ($config): void {
            // WIRIS MathType formulas are saved as data:image/svg+xml —
            // see WirisSvgDataUriScheme's docblock for why this can't just
            // be a config array entry the way the rest of the 'soal' preset
            // (config/purifier.php) is.
            HTMLPurifier_URISchemeRegistry::instance()->register('data', new WirisSvgDataUriScheme);
        });
    }

    /**
     * True when $html has no real content — no visible text and no <img> —
     * even though it might not be a literally empty string. TinyMCE leaves
     * behind markup like `<p><br></p>` for an editor a user typed into and
     * then fully deleted, which Laravel's blank() doesn't catch since it's
     * a non-empty string.
     */
    public static function kosong(?string $html): bool
    {
        if (blank($html)) {
            return true;
        }

        return blank(trim(strip_tags($html))) && ! str_contains($html, '<img');
    }
}
