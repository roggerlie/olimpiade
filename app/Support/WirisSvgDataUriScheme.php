<?php

namespace App\Support;

use HTMLPurifier_URIScheme_data;

/**
 * HTMLPurifier's built-in `data:` URI scheme validator (the vendor class
 * this extends) only recognizes image/jpeg, image/gif and image/png — see
 * vendor/ezyang/htmlpurifier/library/HTMLPurifier/URIScheme/data.php. WIRIS
 * MathType (see resources/js/soal-editor.js) saves formulas as an inline
 * `<img src="data:image/svg+xml;base64,...">`, and its default output format
 * is SVG — changing that to PNG needs a self-hosted MathType Integration
 * Services backend (a configuration.ini setting), which this app doesn't run
 * (the WIRIS cloud service is used directly, per the simpler setup chosen
 * for this project). So: SVG support has to be added here instead.
 *
 * Registered onto HTMLPurifier's URI scheme registry by
 * App\Support\SoalContentPurifier::bersihkan() on every purify call (cheap,
 * idempotent — see HTMLPurifier_URISchemeRegistry::register()).
 */
class WirisSvgDataUriScheme extends HTMLPurifier_URIScheme_data
{
    /**
     * A formula image is a few KB at most; this is a generous ceiling
     * against someone smuggling an oversized payload into a soal field.
     */
    private const MAX_BYTES = 200 * 1024;

    /**
     * @param  \HTMLPurifier_URI  $uri
     * @param  \HTMLPurifier_Config  $config
     * @param  \HTMLPurifier_Context  $context
     */
    public function doValidate(&$uri, $config, $context): bool
    {
        $parts = explode(',', $uri->path, 2);

        if (count($parts) === 2 && str_starts_with($parts[0], 'image/svg+xml')) {
            return $this->validateSvg($uri, $parts[0], $parts[1]);
        }

        // Anything else (image/png, image/jpeg, image/gif — MathType's own
        // fallback formats, or a raster image pasted some other way) is
        // exactly what the vendor validator already handles correctly.
        return parent::doValidate($uri, $config, $context);
    }

    /**
     * @param  \HTMLPurifier_URI  $uri
     */
    private function validateSvg(&$uri, string $metadata, string $data): bool
    {
        $isBase64 = str_contains($metadata, 'base64');
        $decoded = rawurldecode($data);
        $raw = $isBase64 ? base64_decode($decoded, true) : $decoded;

        if ($raw === false || strlen($raw) < 12 || strlen($raw) > self::MAX_BYTES || ! $this->looksLikeSafeSvg($raw)) {
            return false;
        }

        $uri->userinfo = null;
        $uri->host = null;
        $uri->port = null;
        $uri->fragment = null;
        $uri->query = null;
        $uri->path = 'image/svg+xml;base64,'.base64_encode($raw);

        return true;
    }

    /**
     * Defense in depth only — a browser never executes script embedded in
     * an SVG used as an `<img>` source (scripting is disabled for images,
     * unlike `<object>`/`<iframe>`/inline `<svg>`, none of which the 'soal'
     * HTMLPurifier preset allows anyway). Still worth rejecting an
     * obviously hostile payload outright rather than leaning on that.
     */
    private function looksLikeSafeSvg(string $svg): bool
    {
        if (! str_contains($svg, '<svg')) {
            return false;
        }

        $lower = strtolower($svg);

        foreach (['<script', 'onload=', 'onerror=', 'onclick=', '<foreignobject', 'javascript:'] as $needle) {
            if (str_contains($lower, $needle)) {
                return false;
            }
        }

        return true;
    }
}
