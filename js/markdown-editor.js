/**
 * Mendflow — shared markdown editor (Docs engine)
 */
(function () {
  'use strict';

  const esc = (s) => {
    if (typeof window.esc === 'function') return window.esc(s);
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  };

  const SLASH_COMMANDS = [
    { label: 'Заголовок 1', icon: 'H1', cmd: 'h1' },
    { label: 'Заголовок 2', icon: 'H2', cmd: 'h2' },
    { label: 'Заголовок 3', icon: 'H3', cmd: 'h3' },
    { label: 'Список', icon: '≡', cmd: 'ul' },
    { label: 'Нумерованный', icon: '1.', cmd: 'ol' },
    { label: 'Цитата', icon: '"', cmd: 'quote' },
    { label: 'Блок кода', icon: '⌨', cmd: 'codeblock' },
    { label: 'Разделитель', icon: '—', cmd: 'hr' },
    { label: 'Wiki-ссылка', icon: '[[', cmd: 'wikilink' },
    { label: 'Изображение', icon: '🖼', cmd: 'image' },
    { label: 'Жирный', icon: 'B', cmd: 'bold' },
    { label: 'Курсив', icon: 'I', cmd: 'italic' },
  ];

  const FORMAT_MAP = {
    bold: { wrap: '**', both: true },
    italic: { wrap: '_', both: true },
    h1: { prefix: '# ', line: true },
    h2: { prefix: '## ', line: true },
    h3: { prefix: '### ', line: true },
    ul: { prefix: '- ', line: true },
    ol: { prefix: '1. ', line: true },
    quote: { prefix: '> ', line: true },
    hr: { block: '\n---\n' },
    link: { template: '[текст](url)', cursor: 1 },
    image: { template: '![описание](url)', cursor: 2 },
    wikilink: { template: '[[Название]]', cursor: 2 },
    code: { wrap: '`', both: true },
    codeblock: { block: '\n```\nкод\n```\n', cursor: 5 },
  };

  function parseWikiLinks(text) {
    const links = [];
    const re = /\[\[([^\]|]+)(?:\|([^\]]+))?\]\]/g;
    let m;
    while ((m = re.exec(text || ''))) {
      links.push({ target: m[1].trim(), alias: (m[2] || m[1]).trim() });
    }
    return links;
  }

  function parseTags(text) {
    const tags = new Set();
    const re = /(?:^|\s)#([a-zA-Z0-9_\u0400-\u04FF-]+)/g;
    let m;
    while ((m = re.exec(text || ''))) tags.add(m[1]);
    return [...tags];
  }

  function mdToHtml(md, opts = {}) {
    if (!md) return '<p class="mf-md-empty">Нет содержимого</p>';
    const resolveWiki = typeof opts.resolveWiki === 'function' ? opts.resolveWiki : null;
    const resolveMedia = typeof opts.resolveMedia === 'function' ? opts.resolveMedia : (u) => u;
    let src = md;

    src = src.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, (_, alt, url) => {
      const raw = String(url || '').trim();
      const isPlaceholder = !raw || raw === 'url' || raw === 'описание';
      const urls = !isPlaceholder && typeof window.mfMediaUrls === 'function'
        ? window.mfMediaUrls(raw)
        : { primary: isPlaceholder ? '' : resolveMedia(raw), fallback: '' };
      const resolved = urls.primary;
      if (!resolved) {
        const hint = isPlaceholder
          ? 'Загрузите фото через кнопку «Изображение» в редакторе'
          : (alt || 'Изображение');
        return `<figure class="mf-md-figure is-broken"><div class="mf-md-figure-ph">${esc(hint)}</div></figure>`;
      }
      const fb = urls.fallback && urls.fallback !== resolved
        ? ` data-fallback="${esc(urls.fallback)}"`
        : '';
      return `<figure class="mf-md-figure"><img src="${esc(resolved)}" alt="${esc(alt)}" loading="lazy" decoding="async"${fb} onerror="window.mfImgFallback&&window.mfImgFallback(this)"></figure>`;
    });

    src = src.replace(/\[\[([^\]|]+)(?:\|([^\]]+))?\]\]/g, (_, target, alias) => {
      const display = esc(alias || target);
      const t = target.trim();
      if (resolveWiki) {
        const found = resolveWiki(t);
        if (found?.id) {
          return `<a href="#" class="obs-wiki-link is-resolved" data-wiki-id="${esc(String(found.id))}">${display}</a>`;
        }
      }
      return `<a href="#" class="obs-wiki-link is-unresolved" data-wiki-title="${esc(t)}">${display}</a>`;
    });

    src = src.replace(/(?:^|\s)(#[a-zA-Z0-9_\u0400-\u04FF-]+)/g, (m, tag) => {
      const name = tag.slice(1);
      const el = `<span class="obs-tag" data-tag="${esc(name)}">${esc(tag)}</span>`;
      return m.startsWith(' ') ? ` ${el}` : el;
    });

    let html = src
      .replace(/```([\s\S]*?)```/gm, (_, c) =>
        `<pre><code>${c.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</code></pre>`)
      .replace(/^### (.+)$/gm, '<h3>$1</h3>')
      .replace(/^## (.+)$/gm, '<h2>$1</h2>')
      .replace(/^# (.+)$/gm, '<h1>$1</h1>')
      .replace(/^> (.+)$/gm, '<blockquote>$1</blockquote>')
      .replace(/^---$/gm, '<hr>')
      .replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>')
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      .replace(/\*(.+?)\*/g, '<em>$1</em>')
      .replace(/_(.+?)_/g, '<em>$1</em>')
      .replace(/`([^`]+)`/g, '<code>$1</code>')
      .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="obs-ext-link" target="_blank" rel="noopener">$1</a>')
      .replace(/^- (.+)$/gm, '<li>$1</li>')
      .replace(/(<li>.*<\/li>\n?)+/g, (s) => `<ul>${s}</ul>`)
      .replace(/^\d+\. (.+)$/gm, '<li>$1</li>')
      .split('\n')
      .map((line) => {
        const trimmed = line.trim();
        if (!trimmed) return '';
        if (/^<(h[1-6]|ul|ol|li|pre|blockquote|hr|div|span|a)/.test(trimmed)) return trimmed;
        return `<p>${trimmed}</p>`;
      })
      .join('\n');

    return html;
  }

  function applyFormat(ta, fmt, onChange) {
    if (!ta) return;
    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    const sel = ta.value.slice(start, end);
    const before = ta.value.slice(0, start);
    const after = ta.value.slice(end);
    const lineStart = before.lastIndexOf('\n') + 1;
    const lineText = ta.value.slice(lineStart, end || ta.value.length);

    let insert = '';
    let cursorOffset = 0;
    const rule = FORMAT_MAP[fmt];
    if (!rule) return;

    if (rule.both) {
      insert = rule.wrap + (sel || 'текст') + rule.wrap;
      cursorOffset = sel ? insert.length : rule.wrap.length;
    } else if (rule.line) {
      const newLine = rule.prefix + (sel || lineText.replace(/^[#>\-\d\.\s]+/, '') || 'текст');
      ta.value = ta.value.slice(0, lineStart) + newLine + ta.value.slice(lineStart + lineText.length);
      ta.selectionStart = ta.selectionEnd = lineStart + newLine.length;
      onChange?.();
      ta.focus();
      return;
    } else if (rule.block) {
      insert = rule.block.replace('код', sel || 'код');
      cursorOffset = rule.cursor || insert.length;
    } else if (rule.template) {
      insert = rule.template;
      cursorOffset = rule.cursor || 0;
    }

    ta.value = before + insert + after;
    ta.selectionStart = ta.selectionEnd = start + (cursorOffset || insert.length);
    onChange?.();
    ta.focus();
  }

  function bindTagClicks(container, onTagClick) {
    container?.querySelectorAll('.obs-tag[data-tag]').forEach((el) => {
      el.addEventListener('click', (e) => {
        e.preventDefault();
        onTagClick?.(el.dataset.tag);
      });
    });
  }

  function attachEditor(opts = {}) {
    const ta = typeof opts.textarea === 'string' ? document.getElementById(opts.textarea) : opts.textarea;
    const preview = typeof opts.preview === 'string' ? document.getElementById(opts.preview) : opts.preview;
    const toolbar = typeof opts.toolbar === 'string' ? document.getElementById(opts.toolbar) : opts.toolbar;
    if (!ta) return { destroy() {} };

    const state = { slashOpen: false, slashIdx: 0, menuId: opts.menuId || 'mfSlashMenu' };

    const renderPreview = () => {
      if (!preview) return;
      preview.innerHTML = mdToHtml(ta.value, {
        resolveWiki: opts.resolveWiki,
        resolveMedia: opts.resolveMedia,
      });
      bindTagClicks(preview, opts.onTagClick);
    };

    const onChange = () => {
      renderPreview();
      opts.onChange?.(ta.value);
    };

    const hideSlash = () => {
      document.getElementById(state.menuId)?.remove();
      state.slashOpen = false;
    };

    const applySlash = (cmd) => {
      hideSlash();
      const pos = ta.selectionStart;
      const before = ta.value.slice(0, pos);
      const slashPos = before.lastIndexOf('/');
      if (slashPos !== -1) {
        ta.value = ta.value.slice(0, slashPos) + ta.value.slice(pos);
        ta.selectionStart = ta.selectionEnd = slashPos;
      }
      applyFormat(ta, cmd, onChange);
    };

    const showSlash = () => {
      hideSlash();
      state.slashOpen = true;
      state.slashIdx = 0;
      const menu = document.createElement('div');
      menu.id = state.menuId;
      menu.className = 'prj-slash-hint mf-slash-menu';
      SLASH_COMMANDS.forEach((cmd, i) => {
        const item = document.createElement('div');
        item.className = `prj-slash-item ${i === 0 ? 'focused' : ''}`;
        item.innerHTML = `<span class="prj-slash-item-icon">${cmd.icon}</span>${esc(cmd.label)}`;
        item.addEventListener('mousedown', (e) => {
          e.preventDefault();
          applySlash(cmd.cmd);
        });
        menu.appendChild(item);
      });
      const rect = ta.getBoundingClientRect();
      menu.style.position = 'fixed';
      menu.style.left = `${rect.left}px`;
      menu.style.top = `${Math.max(8, rect.bottom - 180)}px`;
      menu.style.zIndex = '9999';
      document.body.appendChild(menu);
    };

    const onKeyDown = (e) => {
      if (state.slashOpen) {
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          const items = document.querySelectorAll(`#${state.menuId} .prj-slash-item`);
          items[state.slashIdx]?.classList.remove('focused');
          state.slashIdx = (state.slashIdx + 1) % items.length;
          items[state.slashIdx]?.classList.add('focused');
          return;
        }
        if (e.key === 'ArrowUp') {
          e.preventDefault();
          const items = document.querySelectorAll(`#${state.menuId} .prj-slash-item`);
          items[state.slashIdx]?.classList.remove('focused');
          state.slashIdx = (state.slashIdx - 1 + items.length) % items.length;
          items[state.slashIdx]?.classList.add('focused');
          return;
        }
        if (e.key === 'Enter') {
          e.preventDefault();
          const cmd = SLASH_COMMANDS[state.slashIdx];
          if (cmd) applySlash(cmd.cmd);
          return;
        }
        if (e.key === 'Escape') {
          hideSlash();
          return;
        }
      }
      if (e.key === '/') {
        const pos = ta.selectionStart;
        const before = ta.value.slice(0, pos);
        if (before.endsWith('\n') || before === '' || before.endsWith(' ')) {
          setTimeout(showSlash, 0);
        }
      }
    };

    const onInput = () => {
      if (state.slashOpen && !ta.value.slice(0, ta.selectionStart).includes('/')) hideSlash();
      onChange();
    };

    ta.addEventListener('keydown', onKeyDown);
    ta.addEventListener('input', onInput);

    toolbar?.querySelectorAll('[data-md-fmt]').forEach((btn) => {
      btn.addEventListener('click', () => applyFormat(ta, btn.dataset.mdFmt, onChange));
    });

    renderPreview();

    return {
      renderPreview,
      getValue: () => ta.value,
      setValue: (v) => {
        ta.value = v || '';
        onChange();
      },
      destroy() {
        hideSlash();
        ta.removeEventListener('keydown', onKeyDown);
        ta.removeEventListener('input', onInput);
      },
    };
  }

  window.MF_MD = {
    SLASH_COMMANDS,
    mdToHtml,
    parseWikiLinks,
    parseTags,
    applyFormat,
    attachEditor,
    bindTagClicks,
  };
})();
