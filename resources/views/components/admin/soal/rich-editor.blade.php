{{--
    TinyMCE (self-hosted, GPL) bound to a Livewire property via @entangle,
    following the same wire-model convention as x-ui.modal.

    The actual tinymce.init() call lives in resources/js/soal-editor.js
    (window.createSoalRichEditor) — NOT inline here. This file is a Blade
    attribute string; Alpine evaluates it at runtime via `new Function(...)`,
    which can never resolve a bare module specifier like `import('tinymce')`
    the way a real Vite-compiled .js file can (confirmed: an `npm run build`
    with imports written directly in this file produced no tinymce chunk at
    all — Vite never saw them, since it only compiles files that are actual
    module-graph entries/imports, not arbitrary text inside .blade.php).
    soal-editor.js is loaded separately (see resources/views/admin/bank-soal/
    soal.blade.php's @push('head')) and only needs to exist on `window` by
    the time this component's x-init runs.

    wire:ignore on the editor's own wrapper is load-bearing: TinyMCE replaces
    the target <textarea> with a whole `<div class="tox-tinymce">` UI tree of
    its own (unlike flatpickr, which just writes back into the same <input>).
    If Livewire tried to morph that against the plain <textarea> the server
    renders, it would fight TinyMCE for control of the DOM on every request.
    wire:ignore opts this whole subtree out of morphing; @entangle is what
    keeps the Livewire property in sync instead — both directions:
    - server → editor: the $watch below calls setContent() whenever the
      entangled property changes from a *server* action (edit()/create()
      prefilling the form), since wire:ignore means Livewire's own morph
      will never push that value into the DOM for us.
    - editor → server: the onChange callback writes the editor's current
      HTML back into the entangled Alpine property, which Alpine then syncs
      to the Livewire property (deferred — no request per keystroke; it's
      only actually sent on the next request the page makes anyway, e.g.
      clicking Simpan).

    IMPORTANT — always pass a stable, explicit `id` (same reasoning as
    x-form.date-picker): this component's `x-init` runs once per DOM node,
    so a regenerated id on re-render would orphan the mounted editor instance.
--}}
@props([
    'id',
    'wireModel',
    'uploadUrl',
    'label' => null,
    'height' => 220,
])

<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ $label }}
        </label>
    @endif

    <div wire:ignore
        x-data="{
            editor: null,
            content: @entangle($wireModel),
            async init() {
                this.editor = await window.createSoalRichEditor(this.$refs.textarea, {
                    initialContent: this.content,
                    height: @js($height),
                    uploadUrl: @js($uploadUrl),
                    onChange: (html) => { this.content = html; },
                });

                this.$watch('content', (value) => {
                    if (this.editor && this.editor.getContent() !== value) {
                        this.editor.setContent(value || '');
                    }
                });
            },
        }"
        x-init="init()" x-destroy="editor?.remove()">
        <textarea x-ref="textarea" id="{{ $id }}"></textarea>
    </div>
</div>

{{--
    This editor always lives inside x-ui.modal (z-99999). TinyMCE appends its
    own dialogs/menus/tooltips straight to <body> in a `.tox-tinymce-aux`
    sink — a sibling of the modal, not a descendant — with its own z-index
    (1100, confirmed by inspecting the live DOM). The modal's much higher
    z-index buries it completely: the "Insert Image" dialog opens, is
    visible and clickable per computed styles, but is rendered fully behind
    the modal — invisible and unreachable. TinyMCE 8 dropped the old
    `z_index` init option that used to fix exactly this, so it has to be a
    CSS override instead. Duplicate <style> tags across each rich-editor
    instance are harmless (same precedent as x-ui.modal's own trailing
    [x-cloak] <style> block).
--}}
<style>.tox-tinymce-aux { z-index: 100000 !important; }</style>
