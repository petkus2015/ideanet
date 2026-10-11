/* ═══════════════════════════════════════════════════════════
   IDEANET — admin obsahu (Nástroje → IDEANET obsah)
   Vanilla JS, žiadna závislosť na Fusion Builderi — funguje
   úplne nezávisle od Avada Builder JS vrstvy.
   ═══════════════════════════════════════════════════════════ */
(() => {
  'use strict';

  function reindex(list, section) {
    const re = new RegExp('ideanet_rows_' + section + '\\[[^\\]]*\\]');
    Array.from(list.querySelectorAll('[data-row]')).forEach((row, i) => {
      row.querySelectorAll('[name]').forEach((el) => {
        el.name = el.name.replace(re, 'ideanet_rows_' + section + '[' + i + ']');
      });
      row.querySelectorAll('[id]').forEach((el) => {
        el.id = el.id.replace(/-\d+-/, '-' + i + '-').replace(/-__INDEX__-/, '-' + i + '-');
      });
      row.querySelectorAll('label[for]').forEach((el) => {
        el.htmlFor = el.htmlFor.replace(/-\d+-/, '-' + i + '-').replace(/-__INDEX__-/, '-' + i + '-');
      });
    });
  }

  function initAddButtons() {
    document.querySelectorAll('.ideanet-add-row').forEach((btn) => {
      btn.addEventListener('click', () => {
        const section = btn.dataset.section;
        const list = document.querySelector('.ideanet-rows[data-section="' + section + '"]');
        const tmpl = document.getElementById('ideanet-tmpl-' + section);
        if (!list || !tmpl) return;
        const index = list.querySelectorAll('[data-row]').length;
        const html = tmpl.innerHTML.replace(/__INDEX__/g, String(index));
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        const row = wrap.firstElementChild;
        list.appendChild(row);
        reindex(list, section);
      });
    });
  }

  function initRemoveButtons() {
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.ideanet-row__remove');
      if (!btn) return;
      const list = btn.closest('.ideanet-rows');
      const section = list ? list.dataset.section : null;
      btn.closest('[data-row]').remove();
      if (list && section) reindex(list, section);
    });
  }

  function initMediaPickers() {
    document.addEventListener('click', (e) => {
      const pick = e.target.closest('.ideanet-media-pick');
      if (pick) {
        e.preventDefault();
        const field = pick.closest('.ideanet-media-field');
        const input = field.querySelector('.ideanet-media-url');
        const type = pick.dataset.type === 'video' ? 'video' : 'image';
        if (!window.wp || !window.wp.media) return;
        const frame = window.wp.media({
          title: type === 'video' ? 'Vybrať video' : 'Vybrať obrázok',
          library: { type: type },
          multiple: false,
        });
        frame.on('select', () => {
          const att = frame.state().get('selection').first().toJSON();
          input.value = att.url;
          const preview = field.querySelector('.ideanet-media-preview');
          if (preview) {
            const img = preview.querySelector('img');
            if (img) img.src = att.url;
            preview.hidden = false;
          }
        });
        frame.open();
        return;
      }
      const clear = e.target.closest('.ideanet-media-clear');
      if (clear) {
        e.preventDefault();
        const field = clear.closest('.ideanet-media-field');
        const input = field.querySelector('.ideanet-media-url');
        input.value = '';
        const preview = field.querySelector('.ideanet-media-preview');
        if (preview) preview.hidden = true;
      }
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    initAddButtons();
    initRemoveButtons();
    initMediaPickers();
  });
})();
