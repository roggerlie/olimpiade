/**
 * TinyMCE, bundled as its own Vite entry (see vite.config.js) rather than
 * folded into admin.js — the editor (core + skin + plugins) is sizeable, and
 * admin.js loads on every admin page. This only loads where it's actually
 * used: resources/views/admin/bank-soal/soal.blade.php pushes it onto the
 * `head` stack (see components/layouts/admin.blade.php), so no other admin
 * page pays for it.
 *
 * Exposes window.createSoalRichEditor(...) rather than window.tinymce
 * directly — resources/views/components/admin/soal/rich-editor.blade.php
 * (an Alpine x-data string in a Blade attribute, never itself compiled by
 * Vite) can't `import` anything; a plain function on `window` is the only
 * thing a runtime-evaluated Alpine expression can call into.
 */
import tinymce from 'tinymce/tinymce';
import 'tinymce/icons/default';
import 'tinymce/themes/silver';
import 'tinymce/models/dom';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/image';
import 'tinymce/plugins/table';
import 'tinymce/skins/ui/oxide/skin.css';
import contentCss from 'tinymce/skins/content/default/content.css?url';
import contentUiCss from 'tinymce/skins/ui/oxide/content.css?url';
// WIRIS MathType — self-registers as the `tiny_mce_wiris` external plugin
// (calls tinymce.PluginManager.add(...) itself; it isn't a real ES module,
// so it's loaded via TinyMCE's own external_plugins mechanism below rather
// than a side-effect `import`). The `?url` suffix is WIRIS's own documented
// workaround for Vite (github.com/wiris/html-integrations, packages/
// tinymce7/README.md, "Known Issues") — without it Vite would try to parse
// the file as a JS module and fail, since it isn't one.
import wirisPlugin from '@wiris/mathtype-tinymce7/plugin.min.js?url';

/**
 * TinyMCE treats *any* data:/blob: image it finds in the editor — including
 * a WIRIS MathType formula, inserted as `data:image/svg+xml;base64,...` —
 * as something to run through `images_upload_handler` (that's what
 * `automatic_uploads`/`paste_data_images` below actually cover; there's no
 * "skip these images" option left to configure instead — TinyMCE 6 dropped
 * `images_dataimg_filter`, the option that used to do that, with no direct
 * replacement). Left unhandled, a formula image would get POSTed to our own
 * upload endpoint, which validates as a real raster image (jpg/png/gif) and
 * rejects it with an HTTP 422 the admin sees as a confusing "upload
 * failed" — even though the formula itself was inserted successfully and
 * needs no upload at all.
 *
 * So this handler intercepts SVG blobs specifically and resolves with the
 * same data: URI right back (via blobInfo.base64(), no network round trip)
 * instead of calling uploadImage() — MathType's own images are meant to
 * stay inline; only genuine pasted/dropped/browsed photos should ever reach
 * the server. See config/purifier.php's 'soal' preset for how that inline
 * SVG survives HTML sanitization on save.
 *
 * @param {string | undefined} uploadUrl
 * @param {{ blob: () => Blob, base64: () => string, filename: () => string }} blobInfo
 * @returns {Promise<string>}
 */
function handleImageUpload(uploadUrl, blobInfo) {
    const mimeType = blobInfo.blob().type;

    if (mimeType === 'image/svg+xml') {
        return Promise.resolve(`data:${mimeType};base64,${blobInfo.base64()}`);
    }

    if (!uploadUrl) {
        return Promise.reject(new Error('Tidak ada URL upload gambar.'));
    }

    return uploadImage(uploadUrl, blobInfo);
}

/**
 * Uploads one pasted/dropped/browsed image to the Laravel endpoint and
 * resolves with its public URL — the exact contract TinyMCE's
 * `images_upload_handler` expects (a Promise<string>, not the `{location}`
 * object the *different* `images_upload_url` auto-POST mechanism expects).
 * Written as a plain `fetch()` rather than routed through Livewire: TinyMCE
 * owns this request/response cycle itself, and the CSRF token is attached
 * by hand from the <meta name="csrf-token"> tag every admin page already
 * renders — admin.js/soal-editor.js don't load resources/js/bootstrap.js
 * (that's the app.js/guest-pages bundle), so `window.axios` isn't available
 * here to do it automatically.
 *
 * @param {string} uploadUrl
 * @param {{ blob: () => Blob, filename: () => string }} blobInfo
 * @returns {Promise<string>}
 */
function uploadImage(uploadUrl, blobInfo) {
    const formData = new FormData();
    formData.append('gambar', blobInfo.blob(), blobInfo.filename());

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    return fetch(uploadUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
        body: formData,
    }).then((response) => {
        if (!response.ok) {
            throw new Error(`Upload gambar gagal (HTTP ${response.status}).`);
        }

        return response.json().then((data) => data.location);
    });
}

/**
 * @param {HTMLTextAreaElement} target
 * @param {{ initialContent?: string, height?: number, uploadUrl?: string, onChange?: (html: string) => void }} options
 * @returns {Promise<import('tinymce').Editor>}
 */
window.createSoalRichEditor = async function createSoalRichEditor(target, { initialContent = '', height = 220, uploadUrl, onChange } = {}) {
    const [editor] = await tinymce.init({
        target,
        license_key: 'gpl',
        skin: false,
        content_css: [contentCss, contentUiCss],
        height,
        menubar: false,
        branding: false,
        promotion: false,
        statusbar: false,
        plugins: 'lists image table',
        toolbar: 'bold italic underline | superscript subscript | bullist numlist | table image tiny_mce_wiris_formulaEditor | undo redo',
        // WIRIS MathType (formula editor) — self-hosted frontend plugin,
        // free/MIT, but it calls WIRIS's cloud service live to open the
        // formula editor UI, so authoring a formula needs internet access
        // (once saved, formulas are inline SVG data: URIs — no ongoing
        // internet dependency to just *display* a saved soal). See
        // App\Support\SoalContentPurifier / config/purifier.php's 'soal'
        // preset for the extra img attributes MathType's output needs.
        external_plugins: { tiny_mce_wiris: wirisPlugin },
        // Without this, MathType's own formula-editor dialog renders behind
        // this app's x-ui.modal in the same way the plain "Insert Image"
        // dialog did before the .tox-tinymce-aux z-index fix below — WIRIS's
        // own recommended setting to avoid exactly that.
        draggable_modal: true,
        // Lets an image pasted straight from the clipboard (e.g. a
        // screenshot) upload the same way a browsed/dropped one does,
        // instead of embedding it as a giant base64 data: URI.
        paste_data_images: true,
        automatic_uploads: true,
        images_upload_handler: (blobInfo) => handleImageUpload(uploadUrl, blobInfo),
        setup: (ed) => {
            ed.on('init', () => ed.setContent(initialContent || ''));
            ed.on('change input undo redo', () => onChange?.(ed.getContent()));
        },
    });

    return editor;
};
