/**
 * Mendflow — раздел «Обсуждения»
 */
(function () {
  'use strict';

  const $ = (id) => document.getElementById(id);
  const esc = (s) => {
    if (typeof window.esc === 'function') return window.esc(s);
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  };
  const api = (...args) => window.api(...args);
  const toast = (msg, type) => {
    if (typeof window.mfToast === 'function') window.mfToast(msg, type);
    else alert(msg);
  };

  function discAuthToken() {
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

  function discIsLoggedIn() {
    return !!discAuthToken();
  }

  const DISC_TITLE_MAX = 80;

  const discState = {
    tab: 'new',
    currentId: null,
    discussion: null,
    loading: false,
  };

  const TAB_LABELS = {
    new: 'Новые',
    active: 'Активные',
    unanswered: 'Без ответа',
    my: 'Мои',
  };

  function discAvatarHtml(author, size = 40) {
    const name = author?.name || '?';
    const init = ((author?.first_name || '?')[0] + (author?.last_name || '?')[0]).toUpperCase();
    const av = author?.avatar;
    const resolved = av && typeof window.resolveMediaUrl === 'function' ? window.resolveMediaUrl(av) : av;
    if (resolved) {
      return `<div class="disc-avatar has-photo" style="width:${size}px;height:${size}px"><img src="${esc(resolved)}" alt=""></div>`;
    }
    return `<div class="disc-avatar" style="width:${size}px;height:${size}px">${esc(init)}</div>`;
  }

  function discTagsHtml(tags, futuristic = false) {
    if (!tags?.length) return '';
    if (futuristic) {
      return `<div class="disc-tags-futuristic">${tags.map(t => `<span class="disc-tag-glow">#${esc(t)}</span>`).join('')}</div>`;
    }
    return `<div class="disc-tags">${tags.map(t => `<span class="chip disc-tag">${esc(t)}</span>`).join('')}</div>`;
  }

  function discBodyHtml(body) {
    if (!body) return '';
    return esc(String(body)).replace(/\n/g, '<br>');
  }

  function discOpenAuthor(author) {
    if (!author?.id || typeof window.openUserProfile !== 'function') return;
    window.openUserProfile({
      id: author.id,
      first_name: author.first_name,
      last_name: author.last_name,
      avatar: author.avatar,
      name: author.name,
    });
  }

  function discIcon(id, size = 16) {
    if (window.MF_EMOJI?.icon) return window.MF_EMOJI.icon(id, size);
    return '';
  }

  function discStatPill(iconId, text) {
    return `<span class="disc-stat-pill"><span class="disc-stat-ico">${discIcon(iconId, 16)}</span><span>${text}</span></span>`;
  }

  function discTitleLen(str) {
    return Array.from(String(str || '')).length;
  }

  function discClampTitleValue(str) {
    return Array.from(String(str || '')).slice(0, DISC_TITLE_MAX).join('');
  }

  function discSyncTitleLimit() {
    const input = $('discCreateTitle');
    const counter = $('discCreateTitleCount');
    if (!input) return;
    const clamped = discClampTitleValue(input.value);
    if (input.value !== clamped) input.value = clamped;
    const len = discTitleLen(input.value);
    if (counter) {
      counter.textContent = `${len}/${DISC_TITLE_MAX}`;
      counter.classList.toggle('is-limit', len >= DISC_TITLE_MAX);
    }
  }

  function discBindTitleLimit() {
    const input = $('discCreateTitle');
    if (!input || input.dataset.discTitleBound) return;
    input.dataset.discTitleBound = '1';
    input.maxLength = DISC_TITLE_MAX;
    input.setAttribute('maxlength', String(DISC_TITLE_MAX));

    input.addEventListener('beforeinput', (e) => {
      if (e.inputType?.startsWith('delete') || e.inputType === 'insertFromPaste') return;
      const incoming = e.data;
      if (incoming == null) return;
      const start = input.selectionStart ?? input.value.length;
      const end = input.selectionEnd ?? start;
      const next = discClampTitleValue(input.value.slice(0, start) + incoming + input.value.slice(end));
      if (discTitleLen(next) > DISC_TITLE_MAX) {
        e.preventDefault();
      }
    });

    input.addEventListener('input', discSyncTitleLimit);

    input.addEventListener('paste', (e) => {
      e.preventDefault();
      const paste = (e.clipboardData || window.clipboardData).getData('text');
      const start = input.selectionStart ?? input.value.length;
      const end = input.selectionEnd ?? start;
      input.value = discClampTitleValue(input.value.slice(0, start) + paste + input.value.slice(end));
      const pos = Math.min(discTitleLen(input.value), start + discTitleLen(paste));
      input.setSelectionRange(pos, pos);
      discSyncTitleLimit();
    });

    discSyncTitleLimit();
  }

  function discStatusHtml(status, compact = false) {
    const cls = compact ? ' disc-status-compact' : '';
    if (status === 'resolved') {
      return `<span class="disc-status disc-status-resolved${cls}"><span class="disc-status-ico">${discIcon('resolved', compact ? 12 : 14)}</span>${compact ? 'Закрыт' : 'Решён'}</span>`;
    }
    return `<span class="disc-status disc-status-open${cls}"><span class="disc-status-ico">${discIcon('pulse', compact ? 12 : 14)}</span>Открыт</span>`;
  }

  function discTileTagHtml(tags) {
    const tag = Array.isArray(tags) && tags.length ? tags[0] : null;
    if (!tag) return '<span class="disc-tile-tag is-empty">#общее</span>';
    return `<span class="disc-tile-tag">#${esc(tag)}</span>`;
  }

  function discTileTitleHtml(title) {
    const t = String(title || '').trim();
    const chars = Array.from(t);
    if (chars.length <= DISC_TITLE_MAX) return esc(t);
    return esc(chars.slice(0, DISC_TITLE_MAX).join('')) + '…';
  }

  function renderDiscussionCard(d) {
    const card = document.createElement('article');
    card.className = 'disc-tile panel disc-thread-card';
    card.dataset.discId = d.id;
    const answers = Number(d.answers_count) || 0;
    card.innerHTML = `
      <div class="disc-tile-glow" aria-hidden="true"></div>
      <div class="disc-tile-inner">
        <div class="disc-tile-top">
          <h3 class="disc-tile-title" title="${esc(String(d.title || ''))}">${discTileTitleHtml(d.title)}</h3>
          ${discStatusHtml(d.status, true)}
        </div>
        <div class="disc-tile-foot">
          ${discTileTagHtml(d.tags)}
          <span class="disc-tile-answers">
            <span class="disc-tile-answers-ico">${discIcon('chat', 14)}</span>
            ${answers} ${discAnswersLabel(answers)}
          </span>
        </div>
      </div>`;
    card.addEventListener('click', () => openDiscussion(d.id));
    window.MF_EMOJI?.attachIcons?.(card);
    return card;
  }

  function discAnswersLabel(n) {
    const x = Number(n) || 0;
    if (x % 10 === 1 && x % 100 !== 11) return 'ответ';
    if (x % 10 >= 2 && x % 10 <= 4 && (x % 100 < 10 || x % 100 >= 20)) return 'ответа';
    return 'ответов';
  }

  function discSortAnswerTree(items) {
    if (!Array.isArray(items) || !items.length) return items;
    items.sort((a, b) => {
      if (a.is_best && !b.is_best) return -1;
      if (!a.is_best && b.is_best) return 1;
      const voteDiff = (b.votes || 0) - (a.votes || 0);
      if (voteDiff !== 0) return voteDiff;
      return String(a.created_at || '').localeCompare(String(b.created_at || ''));
    });
    items.forEach(item => {
      if (item.replies?.length) discSortAnswerTree(item.replies);
    });
    return items;
  }

  function discExtractBestAnswer(items) {
    if (!Array.isArray(items)) return null;
    for (let i = 0; i < items.length; i++) {
      if (items[i].is_best) return items.splice(i, 1)[0];
      if (items[i].replies?.length) {
        const nested = discExtractBestAnswer(items[i].replies);
        if (nested) return nested;
      }
    }
    return null;
  }

  function discCommentsLabel(n) {
    const x = Number(n) || 0;
    if (x % 10 === 1 && x % 100 !== 11) return 'комментарий';
    if (x % 10 >= 2 && x % 10 <= 4 && (x % 100 < 10 || x % 100 >= 20)) return 'комментария';
    return 'комментариев';
  }

  function discCountComments(answers) {
    let n = 0;
    const walk = (items) => {
      (items || []).forEach(a => {
        n++;
        if (a.replies?.length) walk(a.replies);
      });
    };
    walk(answers);
    return n;
  }

  function renderAnswer(a, opts = {}) {
    const {
      isAuthor = false,
      canReply = false,
      discussionId = null,
      depth = 0,
    } = opts;
    const isRoot = depth === 0;
    const avatarSize = isRoot ? 40 : 32;
    const div = document.createElement('article');
    div.className = 'disc-answer panel' + (a.is_best ? ' is-best' : '') + (isRoot ? '' : ' is-nested');
    div.dataset.answerId = a.id;
    div.dataset.depth = String(depth);
    const upActive = a.user_vote === 1 ? ' active' : '';
    const downActive = a.user_vote === -1 ? ' active' : '';
    const replyToName = a.author?.name || 'Пользователь';
    div.innerHTML = `
      <div class="disc-answer-layout">
        <div class="disc-vote-col">
          <button type="button" class="disc-vote-btn disc-vote-up${upActive}" data-vote="1" title="Полезно" aria-label="Голос за">▲</button>
          <span class="disc-vote-count">${a.votes || 0}</span>
          <button type="button" class="disc-vote-btn disc-vote-down${downActive}" data-vote="-1" title="Не полезно" aria-label="Голос против">▼</button>
        </div>
        <div class="disc-answer-body">
          ${a.is_best ? `<div class="disc-best-badge"><span class="disc-stat-ico">${discIcon('resolved', 14)}</span> Лучший ответ</div>` : ''}
          <div class="disc-answer-head">
            ${discAvatarHtml(a.author, avatarSize)}
            <div>
              <button type="button" class="disc-author-name disc-answer-author">${esc(a.author?.name || 'Пользователь')}</button>
              <div class="disc-author-role">${esc(a.time_ago || '')}</div>
            </div>
          </div>
          <p class="disc-answer-text">${discBodyHtml(a.body || '')}</p>
          <div class="disc-answer-actions">
            ${canReply ? `<button type="button" class="disc-reply-to-btn" data-parent-id="${a.id}"><span class="disc-btn-ico">${discIcon('reply', 14)}</span> Ответить</button>` : ''}
            ${isAuthor && isRoot && !a.is_best && a.discussion_status !== 'resolved' ? `<button type="button" class="disc-mark-best-btn"><span class="disc-btn-ico">${discIcon('star', 14)}</span> Отметить лучшим</button>` : ''}
          </div>
          ${canReply ? `
          <div class="disc-inline-reply is-hidden" data-inline-reply="${a.id}">
            <textarea class="disc-inline-reply-input" rows="3" placeholder="Ответ для ${esc(replyToName)}..."></textarea>
            <div class="disc-inline-reply-actions">
              <button type="button" class="disc-inline-cancel-btn">Отмена</button>
              <button type="button" class="disc-inline-submit-btn">Отправить</button>
            </div>
          </div>` : ''}
          <div class="disc-replies"></div>
        </div>
      </div>`;

    div.querySelector('.disc-vote-up')?.addEventListener('click', (e) => {
      e.stopPropagation();
      voteAnswer(a.id, 1, div);
    });
    div.querySelector('.disc-vote-down')?.addEventListener('click', (e) => {
      e.stopPropagation();
      voteAnswer(a.id, -1, div);
    });
    div.querySelector('.disc-mark-best-btn')?.addEventListener('click', (e) => {
      e.stopPropagation();
      markBestAnswer(a.id);
    });
    div.querySelector('.disc-answer-author')?.addEventListener('click', (e) => {
      e.stopPropagation();
      discOpenAuthor(a.author);
    });

    const replyBtn = div.querySelector('.disc-reply-to-btn');
    const inlineReply = div.querySelector('.disc-inline-reply');
    replyBtn?.addEventListener('click', (e) => {
      e.stopPropagation();
      document.querySelectorAll('.disc-inline-reply:not(.is-hidden)').forEach(el => {
        if (el !== inlineReply) el.classList.add('is-hidden');
      });
      inlineReply?.classList.toggle('is-hidden');
      inlineReply?.querySelector('.disc-inline-reply-input')?.focus();
    });
    div.querySelector('.disc-inline-cancel-btn')?.addEventListener('click', (e) => {
      e.stopPropagation();
      inlineReply?.classList.add('is-hidden');
      const input = inlineReply?.querySelector('.disc-inline-reply-input');
      if (input) input.value = '';
    });
    div.querySelector('.disc-inline-submit-btn')?.addEventListener('click', (e) => {
      e.stopPropagation();
      const body = inlineReply?.querySelector('.disc-inline-reply-input')?.value?.trim();
      if (!body) {
        toast('Введите текст ответа', 'error');
        return;
      }
      if (discussionId) submitAnswer(discussionId, body, a.id);
    });

    const repliesEl = div.querySelector('.disc-replies');
    if (a.replies?.length && repliesEl) {
      a.replies.forEach(r => {
        r.discussion_status = a.discussion_status;
        repliesEl.appendChild(renderAnswer(r, {
          isAuthor,
          canReply,
          discussionId,
          depth: depth + 1,
        }));
      });
    }

    return div;
  }

  async function loadDiscussions(tab) {
    if (tab) discState.tab = tab;
    const list = $('discussionsList');
    if (!list) return;

    document.querySelectorAll('[data-disc-tab]').forEach(btn => {
      btn.classList.toggle('is-active', btn.dataset.discTab === discState.tab);
    });

    list.innerHTML = '<div class="disc-loading panel">Загрузка...</div>';

    try {
      const d = await api(`/discussions.php?action=list&tab=${encodeURIComponent(discState.tab)}`);
      const items = d.discussions || [];
      list.innerHTML = '';
      if (!items.length) {
        list.innerHTML = `
          <div class="disc-empty-state panel">
            <div class="disc-empty-icon">${discIcon('thread', 36)}</div>
            <p>${esc(discEmptyMessage())}</p>
          </div>`;
        window.MF_EMOJI?.attachIcons?.(list);
        return;
      }
      items.forEach(item => list.appendChild(renderDiscussionCard(item)));
    } catch (e) {
      list.innerHTML = `<p class="disc-empty panel" style="color:#dc2626">Ошибка: ${esc(e.message)}</p>`;
    }
  }

  function discEmptyMessage() {
    if (discState.tab === 'my') return 'У вас пока нет вопросов.';
    if (discState.tab === 'unanswered') return 'Все вопросы уже получили ответы.';
    return 'Пока нет обсуждений. Задайте первый вопрос!';
  }

  async function openDiscussion(id) {
    discState.currentId = id;
    const root = $('discussionDetailRoot');
    if (!root) return;

    if (typeof window.showView === 'function') {
      window.discReturnView = 'workspaceView';
      window.showView('discussionDetailView');
    }

    root.innerHTML = '<div class="disc-loading panel" style="border-radius:20px;padding:3rem">Загрузка обсуждения...</div>';

    try {
      const d = await api(`/discussions.php?action=get&id=${id}`);
      discState.discussion = d.discussion;
      renderDiscussionDetail(d.discussion);
    } catch (e) {
      root.innerHTML = `<p class="disc-empty panel" style="color:#dc2626">Ошибка: ${esc(e.message)}</p>`;
    }
  }

  function renderDiscussionDetail(d) {
    const root = $('discussionDetailRoot');
    if (!root || !d) return;

    const isAuthor = !!d.is_author;
    const canAnswer = discIsLoggedIn() && d.status !== 'resolved';
    const answers = Array.isArray(d.answers) ? d.answers : [];
    const commentCount = d.comments_count ?? discCountComments(answers);
    const rootCount = answers.length || d.answers_count || 0;
    const tagsBlock = discTagsHtml(d.tags, true);
    const createdLabel = d.created_at ? new Date(d.created_at).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' }) : '';

    root.innerHTML = `
      <div class="disc-page">
        <header class="disc-hero panel">
          <div class="disc-hero-glow" aria-hidden="true"></div>
          <div class="disc-hero-top">
            <span class="disc-hero-id">Thread #${d.id}</span>
            ${discStatusHtml(d.status)}
          </div>
          <h1 class="disc-hero-title">${esc(d.title)}</h1>
          <div class="disc-hero-stats">
            ${discStatPill('chat', `${rootCount} ${discAnswersLabel(rootCount)}`)}
            ${commentCount > rootCount ? discStatPill('thread', `${commentCount} ${discCommentsLabel(commentCount)}`) : ''}
            ${discStatPill('bolt', esc(d.time_ago || 'недавно'))}
            ${d.status === 'resolved'
              ? discStatPill('resolved', 'Вопрос решён')
              : discStatPill('pulse', 'Принимаются ответы')}
          </div>
        </header>

        <div class="disc-layout">
          <div class="disc-main">
            <article class="disc-question panel">
              <div class="disc-author-row">
                ${discAvatarHtml(d.author, 48)}
                <div class="disc-author-info">
                  <button type="button" class="disc-author-name" id="discDetailAuthorBtn">${esc(d.author_name || 'Пользователь')}</button>
                  <div class="disc-author-role">Автор вопроса · ${esc(d.time_ago || '')}</div>
                </div>
              </div>
              ${d.body ? `<div class="disc-detail-body">${discBodyHtml(d.body)}</div>` : '<p class="disc-detail-body" style="color:#9ca3af">Без описания</p>'}
              ${tagsBlock}
            </article>

            <section class="disc-answers-block">
              <div class="disc-answers-header">
                <h2 class="disc-answers-title">
                  Ответы
                  <span class="disc-answers-count">${commentCount || rootCount}</span>
                </h2>
                <span class="disc-sort-hint">лучший · по голосам · вложенные</span>
              </div>
              <div id="discAnswersList" class="disc-answers-list"></div>
            </section>

            ${canAnswer ? `
            <section class="disc-reply panel">
              <h3 class="disc-reply-title">Ваш ответ</h3>
              <p class="disc-reply-hint">Поделитесь опытом — сообщество оценит полезные советы</p>
              <textarea class="disc-answer-input" id="discAnswerInput" rows="5" placeholder="Напишите развёрнутый ответ..."></textarea>
              <button type="button" class="disc-submit-btn" id="discAnswerSubmitBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Отправить ответ
              </button>
            </section>` : ''}

            ${!canAnswer && d.status === 'resolved' ? `
            <div class="disc-closed-notice panel"><span class="disc-notice-ico">${discIcon('resolved', 18)}</span> Обсуждение закрыто — лучший ответ выбран</div>` : ''}

            ${!canAnswer && d.status !== 'resolved' && !discIsLoggedIn() ? `
            <div class="disc-closed-notice panel" style="color:#6d28d9;background:rgba(124,58,237,.06);border-color:rgba(124,58,237,.2)">
              Войдите, чтобы оставить ответ
            </div>` : ''}
          </div>

          <aside class="disc-sidebar">
            <div class="disc-side-card panel">
              <h4 class="disc-side-title">О треде</h4>
              <div class="disc-side-row"><span>Статус</span><strong>${d.status === 'resolved' ? 'Решён' : 'Открыт'}</strong></div>
              <div class="disc-side-row"><span>Ответов</span><strong>${rootCount}</strong></div>
              ${commentCount > rootCount ? `<div class="disc-side-row"><span>Всего</span><strong>${commentCount} ${discCommentsLabel(commentCount)}</strong></div>` : ''}
              <div class="disc-side-row"><span>Активность</span><strong>${esc(d.time_ago || '—')}</strong></div>
              ${createdLabel ? `<div class="disc-side-row"><span>Создан</span><strong>${esc(createdLabel)}</strong></div>` : ''}
              ${isAuthor && d.status !== 'resolved' ? `<button type="button" class="disc-btn-resolve" id="discResolveBtn"><span class="disc-btn-ico">${discIcon('check', 14)}</span> Отметить решённым</button>` : ''}
              ${isAuthor ? `<button type="button" class="disc-btn-delete" id="discDeleteBtn"><span class="disc-btn-ico">${discIcon('trash', 14)}</span> Удалить вопрос</button>` : ''}
            </div>
            <div class="disc-side-card panel">
              <h4 class="disc-side-title">Совет</h4>
              <p class="disc-tip">Голосуйте ▲ за полезные ответы. Можно отвечать на любой комментарий — ветки уходят вглубь. Автор отмечает лучший основной ответ.</p>
            </div>
          </aside>
        </div>
      </div>`;

    $('discDetailAuthorBtn')?.addEventListener('click', () => discOpenAuthor(d.author));

    const answersList = $('discAnswersList');

    if (!answers.length) {
      answersList.innerHTML = `
        <div class="disc-empty-state panel">
          <div class="disc-empty-icon">${discIcon('thought', 40)}</div>
          <p>Пока нет ответов — будьте первым, кто поможет автору</p>
        </div>`;
    } else {
      answersList.innerHTML = '';
      const sortedAnswers = discSortAnswerTree(JSON.parse(JSON.stringify(answers)));
      const bestAnswer = discExtractBestAnswer(sortedAnswers);
      const renderOpts = {
        isAuthor,
        canReply: canAnswer,
        discussionId: d.id,
      };

      if (bestAnswer) {
        bestAnswer.discussion_status = d.status;
        const pinned = document.createElement('div');
        pinned.className = 'disc-best-pinned';
        pinned.innerHTML = `<div class="disc-best-pinned-label"><span class="disc-stat-ico">${discIcon('resolved', 16)}</span> Лучший ответ — закреплён</div>`;
        pinned.appendChild(renderAnswer(bestAnswer, { ...renderOpts, depth: 0 }));
        answersList.appendChild(pinned);
      }

      sortedAnswers.forEach(a => {
        a.discussion_status = d.status;
        answersList.appendChild(renderAnswer(a, { ...renderOpts, depth: 0 }));
      });
    }

    $('discResolveBtn')?.addEventListener('click', () => resolveDiscussion(d.id));
    $('discDeleteBtn')?.addEventListener('click', () => deleteDiscussion(d.id, { returnToList: true }));
    $('discAnswerSubmitBtn')?.addEventListener('click', () => {
      const body = $('discAnswerInput')?.value?.trim();
      if (!body) {
        toast('Введите текст ответа', 'error');
        return;
      }
      submitAnswer(d.id, body);
    });
  }

  async function deleteDiscussion(discussionId, opts = {}) {
    if (!discIsLoggedIn()) {
      toast('Войдите, чтобы удалить вопрос', 'error');
      return;
    }
    if (!confirm('Удалить этот вопрос? Все ответы тоже будут удалены.')) {
      return;
    }

    try {
      await api('/discussions.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'delete', discussion_id: discussionId }),
      });
      toast('Вопрос удалён', 'success');
      if (opts.returnToList) {
        if (typeof window.showView === 'function') {
          window.showView(window.discReturnView || 'workspaceView');
          if (typeof window.switchFeedSection === 'function') {
            window.switchFeedSection('discussions');
          }
        }
        loadDiscussions();
      }
    } catch (e) {
      toast(e.message || 'Не удалось удалить вопрос', 'error');
    }
  }

  async function createDiscussion() {
    const title = $('discCreateTitle')?.value?.trim();
    const body = $('discCreateBody')?.value?.trim();
    const tags = $('discCreateTags')?.value?.trim();
    if (!title) {
      toast('Укажите заголовок', 'error');
      return;
    }
    if (discTitleLen(title) > DISC_TITLE_MAX) {
      toast(`Заголовок — не более ${DISC_TITLE_MAX} символов`, 'error');
      return;
    }
    if (!discIsLoggedIn()) {
      toast('Войдите, чтобы задать вопрос', 'error');
      return;
    }

    const btn = $('discCreateSubmitBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Публикуем...'; }

    try {
      const d = await api('/discussions.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'create', title, body, tags }),
      });
      discHideModal('discCreateModal');
      $('discCreateTitle').value = '';
      $('discCreateBody').value = '';
      $('discCreateTags').value = '';
      discSyncTitleLimit();
      toast('Вопрос опубликован', 'success');
      if (d.discussion?.id) {
        openDiscussion(d.discussion.id);
      } else {
        loadDiscussions('new');
      }
    } catch (e) {
      toast(e.message || 'Ошибка публикации', 'error');
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = 'Опубликовать'; }
    }
  }

  async function submitAnswer(discussionId, body, parentId = null) {
    const rootBtn = $('discAnswerSubmitBtn');
    const inlineBtn = parentId
      ? document.querySelector(`.disc-inline-reply[data-inline-reply="${parentId}"] .disc-inline-submit-btn`)
      : null;
    const btn = inlineBtn || rootBtn;
    if (btn) btn.disabled = true;
    try {
      const payload = { action: 'answer', discussion_id: discussionId, body };
      if (parentId) payload.parent_id = parentId;
      await api('/discussions.php', {
        method: 'POST',
        body: JSON.stringify(payload),
      });
      toast(parentId ? 'Ответ добавлен' : 'Ответ опубликован', 'success');
      await openDiscussion(discussionId);
    } catch (e) {
      toast(e.message || 'Не удалось отправить ответ', 'error');
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  async function voteAnswer(answerId, value, rowEl) {
    if (!discIsLoggedIn()) {
      toast('Войдите, чтобы голосовать', 'error');
      return;
    }
    try {
      const d = await api('/discussions.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'vote', answer_id: answerId, value }),
      });
      const countEl = rowEl?.querySelector('.disc-vote-count');
      if (countEl) countEl.textContent = d.votes ?? 0;
      rowEl?.querySelector('.disc-vote-up')?.classList.toggle('active', d.user_vote === 1);
      rowEl?.querySelector('.disc-vote-down')?.classList.toggle('active', d.user_vote === -1);
    } catch (e) {
      toast(e.message || 'Ошибка голосования', 'error');
    }
  }

  async function markBestAnswer(answerId) {
    try {
      await api('/discussions.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'mark_best', answer_id: answerId }),
      });
      toast('Лучший ответ отмечен', 'success');
      if (discState.currentId) await openDiscussion(discState.currentId);
    } catch (e) {
      toast(e.message || 'Не удалось отметить ответ', 'error');
    }
  }

  async function resolveDiscussion(discussionId) {
    try {
      await api('/discussions.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'resolve', discussion_id: discussionId }),
      });
      toast('Обсуждение закрыто', 'success');
      await openDiscussion(discussionId);
    } catch (e) {
      toast(e.message || 'Не удалось закрыть обсуждение', 'error');
    }
  }

  function discShowModal(id) {
    const el = $(id);
    if (!el) return;
    el.classList.remove('is-hidden');
    el.classList.add('is-open');
    if (typeof window.openModal === 'function') window.openModal(id);
  }

  function discHideModal(id) {
    const el = $(id);
    if (!el) return;
    el.classList.add('is-hidden');
    el.classList.remove('is-open');
    if (typeof window.closeModal === 'function') window.closeModal(id);
  }

  function openCreateModal() {
    if (!discIsLoggedIn()) {
      toast('Войдите, чтобы задать вопрос', 'error');
      return;
    }
    discShowModal('discCreateModal');
    discBindTitleLimit();
    discSyncTitleLimit();
  }

  function attachDiscussionsEvents() {
    discBindTitleLimit();
    window.MF_EMOJI?.attachIcons?.($('discussionsView'));
    $('discAskBtn')?.addEventListener('click', openCreateModal);
    $('discCreateSubmitBtn')?.addEventListener('click', createDiscussion);
    $('discCreateCancelBtn')?.addEventListener('click', () => discHideModal('discCreateModal'));
    $('discCreateCloseBtn')?.addEventListener('click', () => discHideModal('discCreateModal'));
    $('discCreateModal')?.addEventListener('click', e => {
      if (e.target.id === 'discCreateModal') discHideModal('discCreateModal');
    });

    document.querySelectorAll('[data-disc-tab]').forEach(btn => {
      btn.addEventListener('click', () => {
        if (btn.dataset.discTab === 'my' && !discIsLoggedIn()) {
          toast('Войдите, чтобы видеть свои вопросы', 'error');
          return;
        }
        loadDiscussions(btn.dataset.discTab);
      });
    });

    $('discussionDetailBackBtn')?.addEventListener('click', () => {
      const back = window.discReturnView || 'workspaceView';
      if (typeof window.showView === 'function') {
        window.showView(back);
        if (back === 'workspaceView' && typeof window.switchFeedSection === 'function') {
          window.switchFeedSection('discussions');
        }
      }
    });
  }

  window.loadDiscussions = loadDiscussions;
  window.openDiscussion = openDiscussion;
  window.createDiscussion = createDiscussion;
  window.submitAnswer = submitAnswer;
  window.voteAnswer = voteAnswer;
  window.markBestAnswer = markBestAnswer;
  window.deleteDiscussion = deleteDiscussion;
  window.discState = discState;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachDiscussionsEvents);
  } else {
    attachDiscussionsEvents();
  }
})();
