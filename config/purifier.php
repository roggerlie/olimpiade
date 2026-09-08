<?php

/**
 * Ok, glad you are here
 * first we get a config instance, and set the settings
 * $config = HTMLPurifier_Config::createDefault();
 * $config->set('Core.Encoding', $this->config->get('purifier.encoding'));
 * $config->set('Cache.SerializerPath', $this->config->get('purifier.cachePath'));
 * if ( ! $this->config->get('purifier.finalize')) {
 *     $config->autoFinalize = false;
 * }
 * $config->loadArray($this->getConfig());
 *
 * You must NOT delete the default settings
 * anything in settings should be compacted with params that needed to instance HTMLPurifier_Config.
 *
 * @link http://htmlpurifier.org/live/configdoc/plain.html
 */

return [
    'encoding' => 'UTF-8',
    'finalize' => true,
    'ignoreNonStrings' => false,
    'cachePath' => storage_path('app/purifier'),
    'cacheFileMode' => 0755,
    'settings' => [
        'default' => [
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.Allowed' => 'div,b,strong,i,em,u,a[href|title],ul,ol,li,p[style],br,span[style],img[width|height|alt|src]',
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.RemoveEmpty' => true,
        ],
        'test' => [
            'Attr.EnableID' => 'true',
        ],
        // Matches exactly what resources/js/soal-editor.js's TinyMCE toolbar
        // can produce (lists, table, image, superscript/subscript, WIRIS
        // MathType formulas) — see App\Support\SoalContentPurifier. The
        // default preset above doesn't even allow <table>, which the
        // "table" plugin needs.
        //
        // The extra img[class|style|align|data-mathml|role] + data: URI
        // scheme are for WIRIS MathType formulas specifically: it saves a
        // formula as e.g. <img class="Wirisformula" src="data:image/svg+
        // xml;base64,..." data-mathml="..." role="math" ...> — an inline
        // SVG data URI, not a link to an uploaded file. `data-mathml` and
        // `role` aren't standard HTMLPurifier-recognized attributes, so
        // they also need registering below via 'custom_attributes'
        // ($definition->addAttribute(...)) — listing them in HTML.Allowed's
        // bracket alone isn't enough; HTMLPurifier only lets HTML.Allowed
        // toggle attributes its schema already knows how to validate.
        // `data-mathml` lets MathType re-open the formula for editing
        // later; without it the image would still display fine, just not
        // be re-editable.
        // CSS.AllowedProperties is deliberately just the two properties
        // MathType's own inline style uses (vertical-align, max-width) —
        // no url()-capable property is on this list, so `style` can't be
        // used to smuggle in e.g. `background: url(javascript:...)`.
        // data: is safe to allow here because it can only land on img[src]
        // in this preset (no <a>, no CSS url() properties are allowed) —
        // and a data:image/svg+xml used as an <img> src never executes
        // script embedded in the SVG (that only happens for <object>/
        // <iframe>/inline <svg>, none of which this preset allows either).
        // Note: HTMLPurifier's *built-in* data: scheme validator only
        // understands image/png|jpeg|gif, not image/svg+xml — which is what
        // MathType actually emits by default. App\Support\SoalContentPurifier
        // registers a custom scheme handler (WirisSvgDataUriScheme) that adds
        // SVG support (with its own content checks) on every purify call;
        // 'URI.AllowedSchemes' here is what makes HTMLPurifier consult a
        // `data` scheme handler at all in the first place.
        'soal' => [
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.Allowed' => 'p,br,strong,b,em,i,u,sup,sub,ul,ol,li,table,thead,tbody,tr,td[colspan|rowspan],th[colspan|rowspan],img[src|alt|width|height|class|style|align|data-mathml|role]',
            'CSS.AllowedProperties' => 'vertical-align,max-width',
            'URI.AllowedSchemes' => 'http,https,data',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.RemoveEmpty' => true,
        ],
        'youtube' => [
            'HTML.SafeIframe' => 'true',
            'URI.SafeIframeRegexp' => '%^(http://|https://|//)(www.youtube.com/embed/|player.vimeo.com/video/)%',
        ],
        'custom_definition' => [
            'id' => 'html5-definitions',
            'rev' => 1,
            'debug' => false,
            'elements' => [
                // http://developers.whatwg.org/sections.html
                ['section', 'Block', 'Flow', 'Common'],
                ['nav',     'Block', 'Flow', 'Common'],
                ['article', 'Block', 'Flow', 'Common'],
                ['aside',   'Block', 'Flow', 'Common'],
                ['header',  'Block', 'Flow', 'Common'],
                ['footer',  'Block', 'Flow', 'Common'],

                // Content model actually excludes several tags, not modelled here
                ['address', 'Block', 'Flow', 'Common'],
                ['hgroup', 'Block', 'Required: h1 | h2 | h3 | h4 | h5 | h6', 'Common'],

                // http://developers.whatwg.org/grouping-content.html
                ['figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common'],
                ['figcaption', 'Inline', 'Flow', 'Common'],

                // http://developers.whatwg.org/the-video-element.html#the-video-element
                ['video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
                    'src' => 'URI',
                    'type' => 'Text',
                    'width' => 'Length',
                    'height' => 'Length',
                    'poster' => 'URI',
                    'preload' => 'Enum#auto,metadata,none',
                    'controls' => 'Bool',
                ]],
                ['source', 'Block', 'Flow', 'Common', [
                    'src' => 'URI',
                    'type' => 'Text',
                ]],

                // http://developers.whatwg.org/text-level-semantics.html
                ['s',    'Inline', 'Inline', 'Common'],
                ['var',  'Inline', 'Inline', 'Common'],
                ['sub',  'Inline', 'Inline', 'Common'],
                ['sup',  'Inline', 'Inline', 'Common'],
                ['mark', 'Inline', 'Inline', 'Common'],
                ['wbr',  'Inline', 'Empty', 'Core'],

                // http://developers.whatwg.org/edits.html
                ['ins', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
                ['del', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
            ],
            'attributes' => [
                ['iframe', 'allowfullscreen', 'Bool'],
                ['table', 'height', 'Text'],
                ['td', 'border', 'Text'],
                ['th', 'border', 'Text'],
                ['tr', 'width', 'Text'],
                ['tr', 'height', 'Text'],
                ['tr', 'border', 'Text'],
            ],
        ],
        'custom_attributes' => [
            ['a', 'target', 'Enum#_blank,_self,_target,_top'],
            // WIRIS MathType formula images — see the 'soal' preset above.
            ['img', 'data-mathml', 'Text'],
            ['img', 'role', 'Text'],
        ],
        'custom_elements' => [
            ['u', 'Inline', 'Inline', 'Common'],
        ],
    ],

];
