/**
 * Mendflow — раздел «Статьи»
 */
(function () {
  'use strict';

  const $ = (id) => document.getElementById(id);
  const esc = (s) => (typeof window.esc === 'function' ? window.esc(s) : String(s ?? ''));
  const api = (...args) => window.api(...args);
  const toast = (msg, type) => {
    if (typeof window.mfToast === 'function') window.mfToast(msg, type);
    else alert(msg);
  };

  function artAuthToken() {
    if (typeof window.resolveAuthToken === 'function') {
      const t = window.resolveAuthToken();
      if (t) return t;
    }
    try {
      return localStorage.getItem('mendflow_token') || null;
    } catch (e) {
      return null;
    }
  }

  function artIsLoggedIn() {
    return !!artAuthToken();
  }

  const artState = {
    tab: 'popular',
    tagFilter: null,
    currentId: null,
    editorId: null,
    editorApi: null,
    autosaveTimer: null,
    saving: false,
    lastSavedAt: null,
    coverImage: '',
  };

  function artIcon(id, size = 18) {
    if (window.MF_EMOJI?.icon) return window.MF_EMOJI.icon(id, size);
    return '';
  }

  function artStatPill(iconId, text) {
    return `<span class="art-stat-pill"><span class="art-stat-ico">${artIcon(iconId, 14)}</span><span>${text}</span></span>`;
  }

  function artStorageMediaPath(url) {
    if (!url) return '';
    const s = String(url).trim();
    if (/^(img|video|avatar|cover)_[A-Za-z0-9._-]+\.(jpe?g|png|gif|webp|mp4|webm|mov)$/i.test(s)) {
      return '/uploads/' + s;
    }
    const m = s.match(/\/uploads\/[^/?#\s]+/i);
    if (m) return m[0];
    if (/^uploads\/[^/?#\s]+/i.test(s.replace(/^\.\//, ''))) {
      return '/' + s.replace(/^\.\//, '');
    }
    if (typeof window.mfUploadsFilename === 'function') {
      const fn = window.mfUploadsFilename(s);
      if (fn) return '/uploads/' + fn;
    }
    const q = s.match(/(?:^|[?&])f=([^&]+)/i);
    if (q) {
      try { return '/uploads/' + decodeURIComponent(q[1]); } catch (_) { return '/uploads/' + q[1]; }
    }
    return s;
  }

  function artNormalizeContentForStorage(content) {
    return String(content || '').replace(/!\[([^\]]*)\]\(([^)]+)\)/g, (_, alt, url) => {
      const path = artStorageMediaPath(String(url || '').trim());
      return `![${alt}](${path || url})`;
    });
  }

  async function artVerifyUploadedFile(storedPath) {
    const serveUrl = artMediaUrl(storedPath);
    if (!serveUrl) throw new Error('Не удалось построить URL для проверки файла');
    const res = await fetch(serveUrl, { method: 'GET', cache: 'no-store', credentials: 'same-origin' });
    if (!res.ok) {
      throw new Error(`Файл не найден на сервере (${res.status}). Проверьте права папки uploads/ (755).`);
    }
    const len = Number(res.headers.get('Content-Length') || 0);
    if (len > 0 && len < 32) {
      throw new Error('Файл на сервере повреждён или пуст. Попробуйте загрузить снова.');
    }
    return storedPath;
  }

  async function artUploadImage(file) {
    if (!file) throw new Error('Файл не выбран');
    const maxBytes = 25 * 1024 * 1024;
    if (file.size > maxBytes) {
      throw new Error('Файл слишком большой. Максимум 25 МБ.');
    }
    const token = artAuthToken();
    const fd = new FormData();
    fd.append('image', file);
    const headers = {};
    if (token) {
      headers.Authorization = `Bearer ${token}`;
      headers['X-Auth-Token'] = token;
    }
    const base = typeof window.API_BASE !== 'undefined' ? window.API_BASE : '/api';
    let url = `${base.replace(/\/$/, '')}/upload.php`;
    if (token) url += `?token=${encodeURIComponent(token)}`;
    const res = await fetch(url, { method: 'POST', headers, body: fd, credentials: 'same-origin' });
    const raw = await res.text();
    let data;
    try { data = JSON.parse(raw); } catch (_) {
      throw new Error(raw?.trim() || 'Ошибка загрузки изображения');
    }
    if (!res.ok) throw new Error(data.error || 'Ошибка загрузки');
    const stored = artStorageMediaPath(data.path || data.filename || data.url);
    if (!stored) throw new Error('Сервер не вернул путь к файлу');
    if (data.bytes != null && Number(data.bytes) <= 0) {
      throw new Error('Сервер сохранил пустой файл');
    }
    return artVerifyUploadedFile(stored);
  }

  function artUpdateCoverPreview() {
    const preview = $('artCoverPreview');
    const removeBtn = $('artCoverRemoveBtn');
    const hint = $('artCoverHint');
    const hidden = $('artEditorCover');
    const url = artState.coverImage;
    if (hidden) hidden.value = url || '';
    if (!preview) return;
    if (url) {
      preview.classList.remove('is-empty');
      preview.style.backgroundImage = `url('${esc(artMediaUrl(url))}')`;
      preview.innerHTML = '';
      if (removeBtn) removeBtn.classList.remove('is-hidden');
      if (hint) hint.textContent = 'Обложка загружена';
    } else {
      preview.classList.add('is-empty');
      preview.style.backgroundImage = '';
      preview.innerHTML = `<span class="art-cover-ph-ico">${artIcon('camera', 32)}</span><span class="art-cover-ph-text">Добавьте обложку</span>`;
      if (removeBtn) removeBtn.classList.add('is-hidden');
      if (hint) hint.textContent = 'JPG, PNG или WebP · до 25 МБ';
    }
    window.MF_EMOJI?.attachIcons?.(preview);
  }

  function artInsertMarkdownImage(url, alt = 'Изображение') {
    const ta = $('artEditorTextarea');
    if (!ta) return;
    const stored = artStorageMediaPath(url) || url;
    if (!stored || stored === 'url' || !stored.includes('/uploads/')) {
      toast('Не удалось получить путь к изображению. Попробуйте загрузить снова.', 'error');
      return;
    }
    const snippet = `\n![${alt}](${stored})\n`;
    const pos = ta.selectionStart ?? ta.value.length;
    ta.value = ta.value.slice(0, pos) + snippet + ta.value.slice(pos);
    ta.selectionStart = ta.selectionEnd = pos + snippet.length;
    artState.editorApi?.renderPreview?.();
    artScheduleAutosave();
  }

  function artMediaUrl(url) {
    if (!url) return '';
    if (typeof window.resolveMediaUrl === 'function') return window.resolveMediaUrl(url);
    return url;
  }

  function artAvatarHtml(author, size = 36) {
    const name = author?.name || '?';
    const init = ((author?.first_name || '?')[0] + (author?.last_name || '?')[0]).toUpperCase();
    const av = author?.avatar;
    const resolved = av ? artMediaUrl(av) : '';
    if (resolved) {
      return `<div class="art-avatar has-photo" style="width:${size}px;height:${size}px"><img src="${esc(resolved)}" alt=""></div>`;
    }
    return `<div class="art-avatar" style="width:${size}px;height:${size}px">${esc(init)}</div>`;
  }

  function artReadingTime(article) {
    const m = Number(article?.reading_time_min) || 0;
    if (m > 0) return m;
    const words = String(article?.content || '').trim().split(/\s+/).filter(Boolean).length;
    return Math.max(1, Math.ceil(words / 200));
  }

  function artTagsHtml(tags, clickable = false) {
    if (!tags?.length) return '';
    return `<div class="art-tags">${tags.map((t) =>
      clickable
        ? `<button type="button" class="chip art-tag-chip" data-art-tag="${esc(t)}">#${esc(t)}</button>`
        : `<span class="chip art-tag-chip">#${esc(t)}</span>`
    ).join('')}</div>`;
  }

  function artCoverHtml(cover, className = 'art-card-cover') {
    const url = artMediaUrl(cover);
    if (!url) {
      return `<div class="${className} is-placeholder" aria-hidden="true"></div>`;
    }
    return `<div class="${className}"><img src="${esc(url)}" alt="" loading="lazy"></div>`;
  }

  function renderArticleCard(a) {
    const card = document.createElement('article');
    card.className = 'art-card panel';
    card.dataset.artId = a.id;
    const isDraft = a.status === 'draft';
    const draftBadge = isDraft ? '<span class="art-draft-badge">Черновик</span>' : '';
    const metaExtra = isDraft
      ? artStatPill('read', `Изменено ${esc(a.updated_ago || a.time_ago || '')}`)
      : `${artStatPill('read', `${artReadingTime(a)} мин`)}${artStatPill('useful', String(a.useful_count || 0))}${artStatPill('bookmark', String(a.bookmarks_count || 0))}`;
    card.innerHTML = `
      <div class="art-card-glow" aria-hidden="true"></div>
      <div class="art-card-inner">
        ${artCoverHtml(a.cover_image)}
        <div class="art-card-body">
          <div class="art-card-top">${draftBadge || artStatPill('article', 'Статья')}</div>
          <h3 class="art-card-title">${esc(a.title || 'Без названия')}</h3>
          <p class="art-card-preview">${esc(a.preview || '')}</p>
          ${artTagsHtml(a.tags)}
          <div class="art-card-meta">
            <span class="art-card-author">${artAvatarHtml(a.author, 28)} ${esc(a.author_name || 'Автор')}</span>
            <div class="art-card-stats">${metaExtra}</div>
          </div>
        </div>
      </div>`;
    card.addEventListener('click', () => {
      if (isDraft && a.is_author) openArticleEditor(a.id);
      else openArticle(a.id);
    });
    window.MF_EMOJI?.attachIcons?.(card);
    return card;
  }

  function artRenderEmptyState() {
    if (artState.tab === 'drafts') {
      return `
        <div class="art-drafts-empty">
          <p class="disc-empty">Черновиков пока нет</p>
          <p class="art-drafts-hint">Начните писать — статья сохранится как черновик, пока вы не опубликуете её</p>
          <button type="button" class="btn-primary" id="artDraftsWriteBtn">+ Написать статью</button>
        </div>`;
    }
    return `
      <div class="art-empty-state panel">
        <div class="art-empty-icon">${artIcon('article', 36)}</div>
        <p>${esc(artEmptyMessage())}</p>
      </div>`;
  }

  function artBindEmptyActions() {
    $('artDraftsWriteBtn')?.addEventListener('click', () => openArticleEditor());
  }

  async function loadArticles(tab, tag) {
    if (tab) artState.tab = tab;
    if (tag !== undefined) artState.tagFilter = tag || null;

    document.querySelectorAll('[data-art-tab]').forEach((btn) => {
      btn.classList.toggle('is-active', btn.dataset.artTab === artState.tab);
    });

    const list = $('articlesList');
    if (!list) return;

    list.innerHTML = '<div class="disc-loading panel">Загрузка...</div>';

    try {
      let url = `/articles.php?action=list&tab=${encodeURIComponent(artState.tab)}`;
      if (artState.tagFilter) url += `&tag=${encodeURIComponent(artState.tagFilter)}`;
      const d = await api(url);
      const items = d.articles || [];
      list.innerHTML = '';
      if (!items.length) {
        list.innerHTML = artRenderEmptyState();
        artBindEmptyActions();
        window.MF_EMOJI?.attachIcons?.(list);
        return;
      }
      items.forEach((item) => list.appendChild(renderArticleCard(item)));
    } catch (e) {
      list.innerHTML = `<p class="disc-empty panel" style="color:#dc2626">Ошибка: ${esc(e.message)}</p>`;
    }
  }

  function artEmptyMessage() {
    if (artState.tagFilter) return `Нет статей с тегом #${artState.tagFilter}.`;
    if (artState.tab === 'drafts') return 'Черновиков пока нет.';
    if (artState.tab === 'my') return 'У вас пока нет опубликованных статей.';
    if (artState.tab === 'bookmarks') return 'Сохраните статьи в закладки — они появятся здесь.';
    return 'Пока нет статей. Напишите первую!';
  }

  function artBindReadingProgress() {
    const fill = $('articleProgressFill');
    const scroller = $('articleDetailScroll');
    if (!fill || !scroller) return;

    const update = () => {
      const max = scroller.scrollHeight - scroller.clientHeight;
      const pct = max > 0 ? Math.min(100, (scroller.scrollTop / max) * 100) : 0;
      fill.style.width = `${pct}%`;
    };

    scroller.removeEventListener('scroll', scroller._artScrollFn);
    scroller._artScrollFn = update;
    scroller.addEventListener('scroll', update, { passive: true });
    update();
  }

  async function openArticle(id) {
    artState.currentId = id;
    const root = $('articleDetailRoot');
    if (!root) return;

    window.artReturnView = 'workspaceView';
    if (typeof window.showView === 'function') window.showView('articleDetailView');

    root.innerHTML = '<div class="disc-loading panel" style="padding:3rem">Загрузка...</div>';

    try {
      const d = await api(`/articles.php?action=get&id=${id}&track_view=1`);
      renderArticleDetail(d.article);
    } catch (e) {
      root.innerHTML = `<p class="disc-empty panel" style="color:#dc2626">Ошибка: ${esc(e.message)}</p>`;
    }
  }

  function renderArticleDetail(a) {
    const root = $('articleDetailRoot');
    if (!root || !a) return;

    const readMin = artReadingTime(a);
    const dateLabel = a.published_at
      ? new Date(a.published_at).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' })
      : '';
    const coverUrls = a.cover_image && typeof window.mfMediaUrls === 'function'
      ? window.mfMediaUrls(a.cover_image)
      : { primary: a.cover_image ? artMediaUrl(a.cover_image) : '', fallback: '' };
    const coverUrl = coverUrls.primary || '';
    const coverFallback = coverUrls.fallback && coverUrls.fallback !== coverUrl ? coverUrls.fallback : '';

    root.innerHTML = `
      <div class="art-page">
        <div class="art-hero${coverUrl ? ' has-cover' : ''}">
          ${coverUrl
            ? `<div class="art-hero-cover"><img src="${esc(coverUrl)}" alt=""${coverFallback ? ` data-fallback="${esc(coverFallback)}"` : ''} onerror="window.mfImgFallback&&window.mfImgFallback(this)"></div><div class="art-hero-overlay"></div>`
            : '<div class="art-hero-glow" aria-hidden="true"></div>'}
          <div class="art-hero-body">
            <div class="art-hero-top">
              <span class="art-hero-badge">${artIcon('article', 16)} Статья</span>
              ${artStatPill('read', `${readMin} мин чтения`)}
            </div>
            <h1 class="art-hero-title">${esc(a.title)}</h1>
            <div class="art-hero-meta">
              ${artAvatarHtml(a.author, 48)}
              <div>
                <div class="art-detail-author">${esc(a.author_name || 'Автор')}</div>
                <div class="art-detail-sub">
                  ${dateLabel ? `<span>${esc(dateLabel)}</span>` : ''}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="art-detail-actions">
          <button type="button" class="art-action-btn btn-ghost article-useful-btn${a.user_useful ? ' active' : ''}" id="artUsefulBtn">
            <span class="art-action-ico">${artIcon('useful', 18)}</span>
            <span class="art-action-label">Полезно · <span id="artUsefulCount">${a.useful_count || 0}</span></span>
          </button>
          <button type="button" class="art-action-btn btn-ghost article-bookmark-btn${a.is_bookmarked ? ' active' : ''}" id="artBookmarkBtn">
            <span class="art-action-ico">${artIcon('bookmark', 18)}</span>
            <span class="art-action-label" id="artBookmarkLabel">${a.is_bookmarked ? 'В закладках' : 'Сохранить'}</span>
          </button>
          ${a.is_author ? `<button type="button" class="art-action-btn btn-ghost" id="artEditBtn">
            <span class="art-action-ico">${artIcon('edit', 18)}</span>
            <span class="art-action-label">Редактировать</span>
          </button>
          <button type="button" class="art-action-btn btn-ghost art-btn-delete" id="artDetailDeleteBtn">
            <span class="art-action-ico">${artIcon('trash', 18)}</span>
            <span class="art-action-label">Удалить</span>
          </button>` : ''}
        </div>

        <div class="art-content-panel panel">
          <div class="art-detail-content mf-md-body" id="artDetailContent"></div>
        </div>

        ${a.tags?.length ? `<div class="art-detail-tags">${artTagsHtml(a.tags, true)}</div>` : ''}
      </div>`;

    const contentEl = $('artDetailContent');
    if (contentEl && window.MF_MD?.mdToHtml) {
      contentEl.innerHTML = window.MF_MD.mdToHtml(a.content || '', { resolveMedia: artMediaUrl });
      window.MF_MD.bindTagClicks(contentEl, (tag) => {
        if (typeof window.showView === 'function') window.showView('workspaceView');
        if (typeof window.switchFeedSection === 'function') window.switchFeedSection('articles');
        loadArticles('popular', tag);
      });
    }

    window.MF_EMOJI?.attachIcons?.(root);

    $('artUsefulBtn')?.addEventListener('click', () => reactUseful(a.id));
    $('artBookmarkBtn')?.addEventListener('click', () => toggleBookmark(a.id));
    $('artEditBtn')?.addEventListener('click', () => openArticleEditor(a.id));
    $('artDetailDeleteBtn')?.addEventListener('click', () => deleteArticle(a.id));

    requestAnimationFrame(artBindReadingProgress);
  }

  async function toggleBookmark(articleId) {
    if (!artIsLoggedIn()) {
      toast('Войдите, чтобы сохранять статьи', 'error');
      return;
    }
    try {
      const d = await api('/articles.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'bookmark', article_id: articleId }),
      });
      $('artBookmarkBtn')?.classList.toggle('active', d.bookmarked);
      const label = $('artBookmarkLabel');
      if (label) label.textContent = d.bookmarked ? 'В закладках' : 'Сохранить';
      toast(d.bookmarked ? 'Добавлено в закладки' : 'Убрано из закладок', 'success');
    } catch (e) {
      toast(e.message || 'Ошибка закладки', 'error');
    }
  }

  async function reactUseful(articleId) {
    if (!artIsLoggedIn()) {
      toast('Войдите, чтобы отметить статью', 'error');
      return;
    }
    try {
      const d = await api('/articles.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'react_useful', article_id: articleId }),
      });
      $('artUsefulBtn')?.classList.toggle('active', d.user_useful);
      const countEl = $('artUsefulCount');
      if (countEl) countEl.textContent = d.useful_count ?? 0;
    } catch (e) {
      toast(e.message || 'Ошибка реакции', 'error');
    }
  }

  function artScheduleAutosave() {
    clearTimeout(artState.autosaveTimer);
    artState.autosaveTimer = setTimeout(() => saveArticleDraft(true), 5000);
  }

  function artSetSaveStatus(text) {
    const el = $('artEditorSaveStatus');
    if (el) el.textContent = text;
  }

  async function saveArticleDraft(silent = false) {
    if (!artIsLoggedIn()) return;
    if (artState.saving) return;

    const title = $('artEditorTitle')?.value?.trim() || '';
    const cover = artStorageMediaPath(artState.coverImage || $('artEditorCover')?.value?.trim() || '');
    const tags = $('artEditorTags')?.value?.trim() || '';
    const rawContent = artState.editorApi?.getValue?.() || $('artEditorTextarea')?.value || '';
    const content = artNormalizeContentForStorage(rawContent);

    artState.saving = true;
    if (!silent) artSetSaveStatus('Сохранение...');

    try {
      const payload = {
        action: 'save_draft',
        id: artState.editorId || undefined,
        title,
        content,
        cover_image: cover,
        tags,
      };
      const d = await api('/articles.php', { method: 'POST', body: JSON.stringify(payload) });
      if (d.article?.id) artState.editorId = d.article.id;
      artState.lastSavedAt = Date.now();
      artUpdateEditorDeleteBtn();
      artSetSaveStatus('Черновик сохранён · ' + new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' }));
      if (!silent) toast('Черновик сохранён', 'success');
    } catch (e) {
      artSetSaveStatus('Ошибка сохранения');
      if (!silent) toast(e.message || 'Не удалось сохранить', 'error');
    } finally {
      artState.saving = false;
    }
  }

  async function publishArticle(id) {
    const articleId = id || artState.editorId;
    if (!articleId) {
      toast('Сначала сохраните черновик', 'error');
      return;
    }
    await saveArticleDraft(true);
    try {
      const d = await api('/articles.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'publish', id: articleId }),
      });
      toast('Статья опубликована', 'success');

      if (d.first_publish && confirm(`Опубликовать анонс в Ленте?\n\n«${d.article?.title || 'Статья'}»`)) {
        try {
          await api('/posts.php', {
            method: 'POST',
            body: JSON.stringify({
              content: 'Опубликовал новую статью',
              post_type: 'post',
              article_id: articleId,
            }),
          });
          toast('Анонс опубликован в ленте', 'success');
        } catch (e) {
          toast(e.message || 'Не удалось опубликовать анонс', 'error');
        }
      }

      openArticle(articleId);
    } catch (e) {
      toast(e.message || 'Не удалось опубликовать', 'error');
    }
  }

  async function openArticleEditor(id) {
    if (!artIsLoggedIn()) {
      toast('Войдите, чтобы писать статьи', 'error');
      return;
    }

    artState.editorId = id || null;
    artState.coverImage = '';
    window.artReturnView = 'workspaceView';
    if (typeof window.showView === 'function') window.showView('articleEditorView');

    $('artEditorTitle').value = '';
    $('artEditorTags').value = '';
    artSetSaveStatus('');
    artUpdateCoverPreview();
    artUpdateEditorDeleteBtn();

    artState.editorApi?.destroy?.();
    artState.editorApi = window.MF_MD?.attachEditor({
      textarea: 'artEditorTextarea',
      preview: 'artEditorPreview',
      toolbar: 'artEditorToolbar',
      menuId: 'artSlashMenu',
      resolveMedia: artMediaUrl,
      onChange: () => artScheduleAutosave(),
      onTagClick: (tag) => {
        $('artEditorTags').value = tag;
      },
    });
    artState.editorApi?.setValue('');
    window.MF_EMOJI?.attachIcons?.($('articleEditorView'));

    if (id) {
      try {
        const d = await api(`/articles.php?action=get&id=${id}`);
        const a = d.article;
        if (!a?.is_author) {
          toast('Нет доступа к редактированию', 'error');
          return;
        }
        $('artEditorTitle').value = a.title || '';
        artState.coverImage = a.cover_image ? artMediaUrl(a.cover_image) : '';
        artUpdateCoverPreview();
        $('artEditorTags').value = a.tags_raw || (a.tags || []).join(', ');
        artState.editorApi?.setValue(a.content || '');
        artState.editorId = a.id;
        artSetSaveStatus(a.status === 'draft' ? 'Черновик' : 'Опубликовано · можно обновить');
        artUpdateEditorDeleteBtn();
      } catch (e) {
        toast(e.message || 'Не удалось загрузить статью', 'error');
      }
    }
  }

  function artUpdateEditorDeleteBtn() {
    const btn = $('artEditorDeleteBtn');
    if (btn) btn.classList.toggle('is-hidden', !artState.editorId);
  }

  async function deleteArticle(id) {
    if (!confirm('Удалить статью безвозвратно?')) return;
    try {
      await api('/articles.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'delete', id }),
      });
      toast('Статья удалена', 'success');
      if (typeof window.showView === 'function') {
        window.showView('workspaceView');
        if (typeof window.switchFeedSection === 'function') window.switchFeedSection('articles');
      }
      loadArticles();
    } catch (e) {
      toast(e.message || 'Не удалось удалить', 'error');
    }
  }

  function attachArticlesEvents() {
    window.MF_EMOJI?.attachIcons?.($('articlesView'));
    $('artWriteBtn')?.addEventListener('click', () => openArticleEditor());
    $('artEditorSaveBtn')?.addEventListener('click', () => saveArticleDraft(false));
    $('artEditorPublishBtn')?.addEventListener('click', () => publishArticle());
    $('artEditorDeleteBtn')?.addEventListener('click', () => {
      if (artState.editorId) deleteArticle(artState.editorId);
    });
    $('artEditorBackBtn')?.addEventListener('click', () => {
      const back = window.artReturnView || 'workspaceView';
      if (typeof window.showView === 'function') {
        window.showView(back);
        if (back === 'workspaceView' && typeof window.switchFeedSection === 'function') {
          window.switchFeedSection('articles');
        }
      }
    });

    $('articleDetailBackBtn')?.addEventListener('click', () => {
      const back = window.artReturnView || 'workspaceView';
      if (typeof window.showView === 'function') {
        window.showView(back);
        if (back === 'workspaceView' && typeof window.switchFeedSection === 'function') {
          window.switchFeedSection('articles');
        }
      }
    });

    document.querySelectorAll('[data-art-tab]').forEach((btn) => {
      btn.addEventListener('click', () => {
        if ((btn.dataset.artTab === 'my' || btn.dataset.artTab === 'bookmarks' || btn.dataset.artTab === 'drafts') && !artIsLoggedIn()) {
          toast('Войдите для доступа к этому разделу', 'error');
          return;
        }
        artState.tagFilter = null;
        loadArticles(btn.dataset.artTab);
      });
    });

    ['artEditorTitle', 'artEditorTags'].forEach((id) => {
      $(id)?.addEventListener('input', () => artScheduleAutosave());
    });

    $('artCoverPickBtn')?.addEventListener('click', () => $('artCoverInput')?.click());
    $('artCoverInput')?.addEventListener('change', async (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      const btn = $('artCoverPickBtn');
      btn?.classList.add('is-loading');
      try {
        artState.coverImage = await artUploadImage(file);
        artUpdateCoverPreview();
        artScheduleAutosave();
        toast('Обложка загружена', 'success');
      } catch (err) {
        toast(err.message || 'Не удалось загрузить обложку', 'error');
      } finally {
        btn?.classList.remove('is-loading');
        e.target.value = '';
      }
    });
    $('artCoverRemoveBtn')?.addEventListener('click', () => {
      artState.coverImage = '';
      artUpdateCoverPreview();
      artScheduleAutosave();
    });

    $('artEditorImageBtn')?.addEventListener('click', () => $('artEditorImageInput')?.click());
    $('artEditorImageInput')?.addEventListener('change', async (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      const btn = $('artEditorImageBtn');
      btn?.classList.add('is-loading');
      try {
        const url = await artUploadImage(file);
        artInsertMarkdownImage(url);
        toast('Изображение добавлено в текст', 'success');
      } catch (err) {
        toast(err.message || 'Не удалось загрузить изображение', 'error');
      } finally {
        btn?.classList.remove('is-loading');
        e.target.value = '';
      }
    });
  }

  window.deleteArticle = deleteArticle;
  window.loadArticles = loadArticles;
  window.openArticle = openArticle;
  window.openArticleEditor = openArticleEditor;
  window.saveArticleDraft = saveArticleDraft;
  window.publishArticle = publishArticle;
  window.toggleBookmark = toggleBookmark;
  window.reactUseful = reactUseful;
  window.artState = artState;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachArticlesEvents);
  } else {
    attachArticlesEvents();
  }
})();
