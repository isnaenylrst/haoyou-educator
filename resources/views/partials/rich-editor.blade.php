{{--
    Editor ala Word (TinyMCE 6.8.2, sama dengan editor Invoice di Admin).
    Cara pakai: beri class "rt-field" pada <textarea>; partial ini otomatis mengubahnya menjadi editor.
    Baris baru lewat JS: RT.add(textarea) / RT.remove(elemenInduk).
--}}
<style>
    .rt-wrap .tox-tinymce { width: 100% !important; border-radius: 6px; }
    .rt-view p { margin: 0 0 4px; }
    .rt-view ul, .rt-view ol { margin: 2px 0 4px; padding-left: 20px; }
    textarea.rt-field { width: 100%; min-height: 140px; }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
window.RT = (function () {
    const options = {
        menubar: false,
        statusbar: false,
        branding: false,
        promotion: false,
        height: 190,
        min_height: 120,
        plugins: 'lists',
        toolbar: 'undo redo | fontsize | bold italic underline forecolor | alignleft aligncenter alignright alignjustify | bullist numlist | removeformat',
        font_size_formats: '10px 12px 14px 16px 18px 24px 32px',
        forced_root_block: 'p',
        entity_encoding: 'raw',
        browser_spellcheck: true,
        paste_data_images: false,
        content_style: "body { font-family: 'Noto Sans SC','Microsoft YaHei',Arial,sans-serif; font-size: 13px; line-height: 1.45; margin: 8px; } p { margin: 0 0 4px; }",
        setup: function (editor) {
            const sync = function () {
                editor.save();
                document.dispatchEvent(new CustomEvent('rt-change'));
            };
            editor.on('input change keyup undo redo', sync);
        }
    };

    function init(selectorOrEl) {
        if (typeof tinymce === 'undefined') { return; } // CDN gagal: textarea biasa tetap bisa dipakai
        tinymce.init(Object.assign({}, options, typeof selectorOrEl === 'string' ? { selector: selectorOrEl } : { target: selectorOrEl }));
    }

    return {
        start: function () { init('textarea.rt-field'); },
        add: function (textarea) { init(textarea); },
        remove: function (container) {
            if (typeof tinymce === 'undefined') { return; }
            container.querySelectorAll('textarea.rt-field').forEach(function (t) {
                const ed = t.id ? tinymce.get(t.id) : null;
                if (ed) { ed.remove(); }
            });
        },
                save: function () { if (typeof tinymce !== 'undefined') { tinymce.triggerSave(); } },
        setContent: function (textarea, html) {
            const ed = (typeof tinymce !== 'undefined' && textarea.id) ? tinymce.get(textarea.id) : null;
            if (ed) { ed.setContent(html); ed.save(); } else { textarea.value = html; }
        },
        isEmpty: function (html) { return String(html || '').replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim() === ''; }
    };
})();
document.addEventListener('DOMContentLoaded', function () {
    window.RT.start();
    document.querySelectorAll('form').forEach(function (f) { f.addEventListener('submit', window.RT.save); });
});
</script>