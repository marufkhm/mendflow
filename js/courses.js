/**
 * Mendflow Courses — list, editor, learn, rate
 */
(function () {
  const $ = (id) => document.getElementById(id);
  const esc = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

  const CRS_CATEGORIES = ['Дизайн', 'Код', 'Бизнес', 'Маркетинг', 'AI', 'Образование', 'Другое'];
  const CRS_LEVELS = { beginner: 'Начинающий', intermediate: 'Средний', advanced: 'Продвинутый' };
  const LESSON_TEMPLATE = '## Зачем это нужно\n\n\n## Объяснение\n\n\n## Пример\n\n';

  const crsState = {
    tab: 'popular',
    courses: [],
    current: null,
    lessonIdx: 0,
  };

  const courseEditorState = {
    section: 'meta',
    courseId: null,
    status: 'draft',
    dirty: false,
    saving: false,
    title: '',
    outcome: '',
    level: 'beginner',
    category: 'Дизайн',
    tags: [],
    coverImage: '',
    lessons: [],
    activeLessonIdx: 0,
    quizLessonIdx: 0,
  };

  const CRS_EDITOR_SECTIONS = [
    { key: 'meta', label: 'О курсе', hint: 'Название и описание' },
    { key: 'structure', label: 'Структура', hint: 'Список уроков' },
    { key: 'content', label: 'Уроки', hint: 'Текст и картинки' },
    { key: 'quiz', label: 'Квизы', hint: 'Вопросы к урокам' },
    { key: 'publish', label: 'Публикация', hint: 'Предпросмотр и выход в каталог' },
  ];

  const MF_CRS_ICON_EDIT = '<svg class="mf-crs-btn__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
  const MF_CRS_ICON_DELETE = '<svg class="mf-crs-btn__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>';

  function crsActionBtn(kind, label, extraClass = '', id = '') {
    const isDanger = kind === 'danger';
    const cls = `mf-crs-btn ${isDanger ? 'mf-crs-btn--danger' : 'mf-crs-btn--edit'}${extraClass ? ' ' + extraClass : ''}`;
    const idAttr = id ? ` id="${id}"` : '';
    return `<button type="button" class="${cls}"${idAttr}>${isDanger ? MF_CRS_ICON_DELETE : MF_CRS_ICON_EDIT}<span>${esc(label)}</span></button>`;
  }

  function crsFormatDuration(mins) {
    const m = Math.max(1, +mins || 30);
    if (m < 60) return `~${m} мин`;
    const h = Math.floor(m / 60);
    const r = m % 60;
    return r ? `~${h} ч ${r} мин` : `~${h} ч`;
  }

  function crsStars(avg) {
    const n = Math.round((+avg || 0) * 2) / 2;
    let s = '';
    for (let i = 1; i <= 5; i++) s += i <= n ? '★' : (i - 0.5 === n ? '⯨' : '☆');
    return s;
  }

  function crsStarSizePx(size) {
    if (size === 'lg') return 20;
    if (size === 'sm') return 13;
    return 16;
  }

  function crsStarSvg(sizePx) {
    const px = sizePx || 16;
    return `<svg class="crs-star-svg" width="${px}" height="${px}" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2.5l2.85 5.77 6.37.93-4.61 4.49 1.09 6.35L12 17.77l-5.7 2.99 1.09-6.35-4.61-4.49 6.37-.93L12 2.5z"/></svg>`;
  }

  function crsStarsHtml(rating, opts = {}) {
    const { interactive = false, active = 0, size = 'md' } = opts;
    const val = interactive ? active : Math.round(+rating || 0);
    const px = crsStarSizePx(size);
    return [1, 2, 3, 4, 5].map((n) => {
      const on = n <= val;
      if (interactive) {
        return `<button type="button" class="crs-star-btn${on ? ' on' : ''}" data-n="${n}" aria-label="${n} из 5"><span class="crs-star-icon crs-star-icon--${size}">${crsStarSvg(px)}</span></button>`;
      }
      return `<span class="crs-star-icon crs-star-icon--${size}${on ? ' is-on' : ''}">${crsStarSvg(px)}</span>`;
    }).join('');
  }

  function crsReviewDate(iso) {
    if (!iso) return '';
    if (typeof window.timeAgo === 'function') return window.timeAgo(iso);
    try {
      return new Date(iso).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch (_) {
      return '';
    }
  }

  function crsReviewAvatar(r) {
    const name = r.author_name || '?';
    const initials = name.split(/\s+/).filter(Boolean).map((p) => p[0]).join('').slice(0, 2).toUpperCase() || '?';
    const av = r.avatar ? crsMediaUrl(r.avatar) : '';
    if (av) {
      return `<div class="crs-review-av has-photo"><img src="${esc(av)}" alt=""></div>`;
    }
    return `<div class="crs-review-av" aria-hidden="true">${esc(initials)}</div>`;
  }

  function crsReviewCardHtml(r, opts = {}) {
    const courseLine = opts.courseTitle
      ? `<div class="crs-review-course">«${esc(opts.courseTitle)}»</div>`
      : '';
    const text = (r.review_text || '').trim();
    return `
      <article class="crs-review-card">
        <div class="crs-review-card-glow" aria-hidden="true"></div>
        <div class="crs-review-card-head">
          ${crsReviewAvatar(r)}
          <div class="crs-review-card-meta">
            <div class="crs-review-author">${esc(r.author_name || 'Студент')}</div>
            ${courseLine}
            <div class="crs-review-stars-row">${crsStarsHtml(r.rating)}</div>
          </div>
          <time class="crs-review-date">${esc(crsReviewDate(r.created_at))}</time>
        </div>
        ${text ? `<p class="crs-review-text">${esc(text)}</p>` : '<p class="crs-review-text is-empty">Без комментария</p>'}
      </article>`;
  }

  function crsBuildReviewsSectionHtml(c) {
    const reviews = c.reviews || [];
    const avg = +c.rating_avg || 0;
    const count = +c.rating_count || reviews.length || 0;
    const myR = c.my_rating?.rating || 0;

    let formHtml = '';
    if (c.can_rate) {
      formHtml = `
        <div class="crs-rating-box">
          <div class="crs-rating-box-glow" aria-hidden="true"></div>
          <div class="crs-rating-box-head">
            <span class="crs-rating-box-badge">Ваш голос</span>
            <h3 class="crs-rating-box-title">Оцените курс</h3>
            <p class="crs-rating-box-sub">Поделитесь впечатлением — это поможет другим студентам</p>
          </div>
          <div class="crs-star-picker" data-val="${myR}" role="group" aria-label="Оценка от 1 до 5">
            ${crsStarsHtml(0, { interactive: true, active: myR, size: 'lg' })}
          </div>
          <label class="crs-rating-label" for="crsReviewText">Комментарий</label>
          <textarea class="crs-rating-textarea" id="crsReviewText" maxlength="200" placeholder="Что понравилось, что можно улучшить…" rows="3">${esc(c.my_rating?.review_text || '')}</textarea>
          <div class="crs-rating-foot">
            <span class="crs-rating-hint">До 200 символов</span>
            <button class="primary-button crs-rating-submit" id="crsReviewSubmit" type="button">Отправить отзыв</button>
          </div>
        </div>`;
    }

    const listHtml = reviews.length
      ? `<div class="crs-reviews-grid">${reviews.map((r) => crsReviewCardHtml(r)).join('')}</div>`
      : `<div class="crs-reviews-empty">
          <div class="crs-reviews-empty-icon" aria-hidden="true">${crsStarSvg(32)}</div>
          <p>Отзывов пока нет${c.can_rate ? ' — будьте первым' : ''}</p>
        </div>`;

    return `
      <section class="crs-reviews-section" aria-labelledby="crsReviewsTitle">
        <div class="crs-reviews-hero">
          <div class="crs-reviews-hero-orb crs-reviews-hero-orb--a" aria-hidden="true"></div>
          <div class="crs-reviews-hero-orb crs-reviews-hero-orb--b" aria-hidden="true"></div>
          <div class="crs-reviews-hero-inner">
            <div class="crs-reviews-hero-copy">
              <span class="crs-reviews-kicker">Сообщество</span>
              <h2 class="crs-reviews-title" id="crsReviewsTitle">Отзывы</h2>
              <p class="crs-reviews-sub">Мнения студентов, прошедших курс</p>
            </div>
            <div class="crs-reviews-summary">
              <div class="crs-reviews-avg-wrap">
                <span class="crs-reviews-avg">${avg > 0 ? avg.toFixed(1) : '—'}</span>
                <span class="crs-reviews-avg-max">/ 5</span>
              </div>
              <div class="crs-reviews-summary-stars">${crsStarsHtml(avg, { size: 'lg' })}</div>
              <span class="crs-reviews-count">${count} ${count === 1 ? 'отзыв' : count >= 2 && count <= 4 ? 'отзыва' : 'отзывов'}</span>
            </div>
          </div>
        </div>
        ${formHtml}
        <div class="crs-reviews-list" id="crsReviewsList">${listHtml}</div>
      </section>`;
  }

  function crsBindReviewsHandlers(root, c) {
    let pickRating = c.my_rating?.rating || 0;
    root.querySelectorAll('.crs-star-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        pickRating = +btn.dataset.n;
        root.querySelectorAll('.crs-star-btn').forEach((b) => b.classList.toggle('on', +b.dataset.n <= pickRating));
      });
    });
    $('crsReviewSubmit')?.addEventListener('click', async () => {
      try {
        await api('/courses.php', {
          method: 'POST',
          body: JSON.stringify({
            action: 'review',
            course_id: c.id,
            rating: pickRating || 5,
            review_text: $('crsReviewText')?.value?.trim() || '',
          }),
        });
        if (typeof mfToast === 'function') mfToast('Спасибо за отзыв!', 'success');
        crsOpenDetail(c.id);
      } catch (e) {
        alert(e.message);
      }
    });
  }

  function crsWordCount(text) {
    return (text || '').trim().split(/\s+/).filter(Boolean).length;
  }

  function crsMediaUrl(url) {
    return typeof window.resolveMediaUrl === 'function' ? window.resolveMediaUrl(url) : (url || '');
  }

  function debounce(fn, ms) {
    let t;
    return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
  }

  /* ── Modal helpers ── */
  function crsShowModal(id) {
    const el = $(id);
    if (!el) return;
    el.classList.remove('is-hidden');
    el.classList.add('is-open');
    if (typeof window.openModal === 'function') window.openModal(id);
  }

  function crsHideModal(id) {
    const el = $(id);
    if (!el) return;
    el.classList.add('is-hidden');
    el.classList.remove('is-open');
    if (typeof window.closeModal === 'function') window.closeModal(id);
  }

  function crsRequireAuth() {
    const token = typeof window.resolveAuthToken === 'function'
      ? window.resolveAuthToken()
      : localStorage.getItem('mendflow_token');
    if (token) return true;
    const msg = 'Войдите в аккаунт, чтобы создавать курсы';
    if (typeof window.mfToast === 'function') window.mfToast(msg, 'info');
    else alert(msg);
    return false;
  }

  /* ── Markdown ↔ blocks ── */
  function crsMarkdownToBlocks(text, imageUrl) {
    const blocks = [];
    const lines = (text || '').split('\n');
    let buf = [];
    const flushText = () => {
      const t = buf.join('\n').trim();
      if (t) blocks.push({ type: 'text', text: t });
      buf = [];
    };
    for (const line of lines) {
      if (/^##\s+/.test(line)) {
        flushText();
        blocks.push({ type: 'heading', text: line.replace(/^##\s+/, '').trim() });
      } else {
        buf.push(line);
      }
    }
    flushText();
    if (imageUrl) blocks.push({ type: 'image', url: imageUrl });
    if (!blocks.length) blocks.push({ type: 'text', text: '' });
    return blocks;
  }

  function crsBlocksToMarkdown(blocks) {
    const parts = [];
    (blocks || []).forEach(b => {
      if (b.type === 'heading') parts.push('## ' + (b.text || ''));
      else if (b.type === 'text') parts.push(b.text || '');
    });
    const md = parts.join('\n\n').trim();
    return md || LESSON_TEMPLATE;
  }

  function crsLessonImageFromBlocks(blocks) {
    const img = (blocks || []).find(b => b.type === 'image' && b.url);
    return img ? img.url : '';
  }

  /* ── Shared lesson render (student view) ── */
  function crsRenderLessonBlocksHtml(lesson, opts = {}) {
    const { showQuiz = false, enrolled = true, preview = false } = opts;
    const blocks = (lesson.blocks || []).map(b => {
      if (b.type === 'heading') return `<h3 class="crs-block-heading">${esc(b.text)}</h3>`;
      if (b.type === 'text') return `<p class="crs-block-text">${esc(b.text).replace(/\n/g, '<br>')}</p>`;
      if (b.type === 'image' && b.url) return `<img class="crs-block-img" src="${esc(crsMediaUrl(b.url))}" alt="">`;
      if (b.type === 'divider') return `<hr class="crs-block-divider">`;
      return '';
    }).join('');

    let quizHtml = '';
    const quiz = lesson.quiz;
    if (showQuiz && quiz?.question && enrolled && !lesson.completed) {
      const optsHtml = (quiz.options || []).filter(Boolean).map((o, i) =>
        `<label class="crs-quiz-opt"><input type="radio" name="crsQuiz" value="${i}"> ${esc(o)}</label>`
      ).join('');
      quizHtml = `
        <div class="crs-quiz-box">
          <strong>Квиз</strong>
          <p>${esc(quiz.question)}</p>
          <div class="crs-quiz-opts">${optsHtml}</div>
          ${preview ? '' : '<button class="primary-button crs-quiz-submit" type="button">Проверить ответ</button><p class="crs-quiz-msg is-hidden"></p>'}
        </div>`;
    } else if (lesson.completed) {
      quizHtml = '<p class="crs-lesson-done">✓ Урок пройден</p>';
    } else if (showQuiz && enrolled && !quiz?.question) {
      quizHtml = preview ? '' : '<button class="primary-button crs-complete-lesson" type="button">Завершить урок</button>';
    }

    return `
      <div class="crs-lesson-content">
        <h2>${esc(lesson.title || 'Урок')}</h2>
        ${blocks || '<p class="crs-empty">Контент урока пока пуст</p>'}
        ${quizHtml}
      </div>`;
  }

  function crsCurrentUserId() {
    const u = window.currentUser;
    if (u?.id) return +u.id;
    try {
      const stored = JSON.parse(localStorage.getItem('mendflow_user') || '{}');
      return +stored.id || 0;
    } catch (_) {
      return 0;
    }
  }

  function crsIsCourseAuthor(c) {
    if (!c) return false;
    if (c.is_author === true) return true;
    if (typeof c.is_author === 'number' && c.is_author > 0) return true;
    const uid = crsCurrentUserId();
    if (!uid) return false;
    return uid === +c.author_id || uid === +(c.author?.id || 0);
  }

  /* ── Cards & list ── */
  function crsRenderCard(c, options = {}) {
    const isAuthor = crsIsCourseAuthor(c);
    const showActions = options.showActions !== false && (options.showActions === true || isAuthor);
    const card = document.createElement('article');
    card.className = 'mf-course-card';
    card.dataset.id = c.id;
    const cover = c.cover_image
      ? `<img src="${esc(crsMediaUrl(c.cover_image))}" alt="" class="mf-course-cover-img">`
      : `<div class="mf-course-cover-placeholder">📚</div>`;
    const av = c.author?.avatar
      ? `<img src="${esc(crsMediaUrl(c.author.avatar))}" alt="">`
      : esc((c.author?.name || 'A')[0]);
    const statusBadge = c.status === 'draft'
      ? '<span class="mf-course-draft-badge">Черновик</span>'
      : (showActions || crsState.tab === 'my' ? '<span class="mf-course-published-badge">Опубликован</span>' : '');
    card.innerHTML = `
      <div class="mf-course-cover">${cover}</div>
      <div class="mf-course-body">
        <div class="mf-course-meta-row">
          <span class="mf-course-level">${esc(c.level_label || CRS_LEVELS[c.level] || '')}</span>
          <span class="mf-course-time">${esc(crsFormatDuration(c.estimated_minutes))}</span>
        </div>
        <h3 class="mf-course-title">${esc(c.title)}</h3>
        <div class="mf-course-author">
          <span class="mf-course-author-av">${av}</span>
          <span>${esc(c.author?.name || 'Автор')}</span>
        </div>
        <div class="mf-course-stats">
          <span class="mf-course-rating">${crsStars(c.rating_avg)} ${c.rating_avg > 0 ? c.rating_avg.toFixed(1) : '—'}</span>
          <span>${c.enrollments_count || 0} студ.</span>
        </div>
        ${c.tags?.length ? `<div class="mf-course-tags">${c.tags.slice(0, 3).map(t => `<span>#${esc(t)}</span>`).join('')}</div>` : ''}
        ${statusBadge}
        ${c.enrolled ? `<div class="mf-course-progress"><div style="width:${c.progress_pct || 0}%"></div></div>` : ''}
        ${showActions ? `
          <div class="mf-course-card-actions">
            ${crsActionBtn('edit', 'Редактировать', 'mf-course-card-edit')}
            ${crsActionBtn('danger', 'Удалить', 'mf-course-card-del')}
          </div>` : ''}
      </div>`;
    card.addEventListener('click', () => {
      if (c.status === 'draft' && isAuthor) crsOpenEditor(c.id);
      else crsOpenDetail(c.id);
    });
    card.querySelector('.mf-course-card-edit')?.addEventListener('click', e => {
      e.stopPropagation();
      crsOpenEditor(c.id);
    });
    card.querySelector('.mf-course-card-del')?.addEventListener('click', e => {
      e.stopPropagation();
      crsDeleteCourse(c.id, c.title, c.status);
    });
    return card;
  }

  async function crsLoadList() {
    const grid = $('crsGrid');
    const filterBar = document.querySelector('.crs-filter-bar');
    if (!grid) return;

    if (filterBar) {
      filterBar.classList.toggle('is-hidden', crsState.tab === 'drafts' || crsState.tab === 'my');
    }

    if (crsState.tab === 'drafts' || crsState.tab === 'my') {
      const token = typeof window.resolveAuthToken === 'function'
        ? window.resolveAuthToken()
        : localStorage.getItem('mendflow_token');
      if (!token) {
        grid.innerHTML = `
          <div class="crs-drafts-empty">
            <p class="crs-empty">Войдите в аккаунт, чтобы видеть ${crsState.tab === 'drafts' ? 'черновики' : 'свои курсы'}</p>
          </div>`;
        return;
      }
    }

    grid.innerHTML = '<p class="crs-loading">Загрузка...</p>';
    try {
      const q = $('crsSearchInput')?.value?.trim() || '';
      const tag = $('crsTagInput')?.value?.trim() || '';
      const cat = $('crsCategoryInput')?.value || '';
      const level = $('crsLevelInput')?.value || '';
      const params = new URLSearchParams({ action: 'list', tab: crsState.tab, _: Date.now() });
      if (q) params.set('q', q);
      if (tag) params.set('tag', tag);
      if (cat) params.set('category', cat);
      if (level) params.set('level', level);
      const d = await api('/courses.php?' + params);
      crsState.courses = d.courses || [];
      grid.innerHTML = '';
      if (!crsState.courses.length) {
        if (crsState.tab === 'drafts') {
          grid.innerHTML = `
            <div class="crs-drafts-empty">
              <p class="crs-empty">Черновиков пока нет</p>
              <p class="crs-drafts-hint">Создайте курс — он сохранится как черновик, пока вы не опубликуете его</p>
              <button class="primary-button" id="crsDraftsCreateBtn" type="button">+ Создать курс</button>
            </div>`;
          $('crsDraftsCreateBtn')?.addEventListener('click', () => crsOpenCreateModal());
        } else if (crsState.tab === 'my') {
          grid.innerHTML = `
            <div class="crs-drafts-empty">
              <p class="crs-empty">У вас пока нет курсов</p>
              <p class="crs-drafts-hint">Создайте первый курс — черновики и опубликованные курсы появятся здесь</p>
              <button class="primary-button" id="crsMyCreateBtn" type="button">+ Создать курс</button>
            </div>`;
          $('crsMyCreateBtn')?.addEventListener('click', () => crsOpenCreateModal());
        } else {
          grid.innerHTML = '<p class="crs-empty">Курсов пока нет</p>';
        }
        return;
      }
      crsState.courses.forEach(c => grid.appendChild(crsRenderCard(c)));
    } catch (e) {
      grid.innerHTML = `<p class="crs-empty">Ошибка: ${esc(e.message)}</p>`;
    }
  }

  async function crsOpenDetail(id) {
    try {
      const d = await api(`/courses.php?action=detail&id=${id}`);
      if (d.course?.status === 'draft' && crsIsCourseAuthor(d.course)) {
        crsOpenEditor(id);
        return;
      }
      crsState.current = d.course;
      crsState.lessonIdx = 0;
      crsRenderDetail();
      if (typeof showView === 'function') showView('courseDetailView');
    } catch (e) {
      alert(e.message);
    }
  }

  function crsRenderDetail() {
    const c = crsState.current;
    if (!c) return;
    const root = $('crsDetailRoot');
    if (!root) return;

    const coverStyle = c.cover_image
      ? `background-image:url('${esc(crsMediaUrl(c.cover_image))}')`
      : 'background:linear-gradient(135deg,#7c3aed,#a78bfa)';

    const lesson = c.lessons?.[crsState.lessonIdx];
    const lessonHtml = lesson
      ? crsRenderLessonBlocksHtml(lesson, { showQuiz: true, enrolled: c.enrolled, preview: false })
      : '';

    const sidebar = (c.lessons || []).map((l, i) =>
      `<button type="button" class="crs-lesson-nav${i === crsState.lessonIdx ? ' is-active' : ''}${l.completed ? ' is-done' : ''}" data-idx="${i}">
        ${l.completed ? '✓' : i + 1}. ${esc(l.title)}
      </button>`
    ).join('');

    const isAuthor = crsIsCourseAuthor(c);

    let actions = '';
    if (isAuthor) {
      actions = `
        ${crsActionBtn('edit', 'Редактировать', 'crs-detail-btn', 'crsEditBtn')}
        ${crsActionBtn('danger', 'Удалить курс', 'crs-detail-btn', 'crsDeleteBtn')}`;
    } else if (!c.enrolled && !isAuthor) {
      actions = `<button class="primary-button" id="crsEnrollBtn" type="button">Записаться на курс</button>`;
    } else if (c.enrolled && c.completed) {
      actions = `
        <button class="primary-button" id="crsCertBtn" type="button">🎓 Сертификат</button>
        <button class="ghost-button" id="crsShareBtn" type="button">Поделиться в чат</button>`;
    }

    root.innerHTML = `
      <button class="ghost-button crs-back-btn" id="crsBackBtn" type="button">← К курсам</button>
      ${isAuthor ? `
        <div class="crs-detail-author-bar">
          ${crsActionBtn('edit', 'Редактировать', '', 'crsEditBtnTop')}
          ${crsActionBtn('danger', 'Удалить курс', 'mf-crs-btn--push', 'crsDeleteBtnTop')}
        </div>` : ''}
      <div class="crs-detail-hero" style="${coverStyle}">
        <div class="crs-detail-hero-overlay">
          <span class="mf-course-level">${esc(c.level_label)}</span>
          <h1>${esc(c.title)}</h1>
          <p>${esc(c.description || '')}</p>
          <div class="crs-detail-meta">
            <span>${crsStars(c.rating_avg)} ${c.rating_avg > 0 ? c.rating_avg.toFixed(1) : '—'} (${c.rating_count})</span>
            <span>${c.enrollments_count} студентов</span>
            <span>${esc(crsFormatDuration(c.estimated_minutes))}</span>
          </div>
          <div class="crs-detail-author"><span>${esc(c.author?.name || '')}</span></div>
          <div class="crs-detail-actions">${actions}</div>
          ${c.enrolled ? `<div class="crs-progress-bar"><div class="crs-progress-track"><div style="width:${c.progress_pct}%"></div></div><span>${c.progress_pct}%</span></div>` : ''}
        </div>
      </div>
      <div class="crs-detail-layout">
        <aside class="crs-lesson-sidebar">${sidebar || '<p>Нет уроков</p>'}</aside>
        <main class="crs-lesson-main">${lessonHtml || '<p>Выберите урок</p>'}</main>
      </div>
      ${crsBuildReviewsSectionHtml(c)}`;

    root.querySelectorAll('.crs-lesson-nav').forEach(btn => {
      btn.addEventListener('click', () => { crsState.lessonIdx = +btn.dataset.idx; crsRenderDetail(); });
    });
    $('crsBackBtn')?.addEventListener('click', () => showView('coursesView'));
    $('crsEditBtn')?.addEventListener('click', () => crsOpenEditor(c.id));
    $('crsDeleteBtn')?.addEventListener('click', () => crsDeleteCourse(c.id, c.title, c.status));
    $('crsEditBtnTop')?.addEventListener('click', () => crsOpenEditor(c.id));
    $('crsDeleteBtnTop')?.addEventListener('click', () => crsDeleteCourse(c.id, c.title, c.status));
    $('crsEnrollBtn')?.addEventListener('click', crsEnroll);
    $('crsCertBtn')?.addEventListener('click', crsShowCertificate);
    $('crsShareBtn')?.addEventListener('click', () => crsShareToChat(c));
    root.querySelector('.crs-quiz-submit')?.addEventListener('click', crsSubmitQuiz);
    root.querySelector('.crs-complete-lesson')?.addEventListener('click', crsCompleteLessonNoQuiz);

    crsBindReviewsHandlers(root, c);
  }

  function crsActivateCoursesTab(tab) {
    crsState.tab = tab;
    document.querySelectorAll('[data-crs-tab]').forEach(b => {
      b.classList.toggle('is-active', b.dataset.crsTab === tab);
    });
  }

  async function crsDeleteCourse(id, title, status) {
    const name = title || 'этот курс';
    const isPublished = status === 'published';
    let msg = `Удалить курс «${name}»?\n\nВсе уроки, отзывы и прогресс студентов будут удалены без возможности восстановления.`;
    if (isPublished) {
      msg += '\n\nКурс опубликован — его увидят в каталоге до удаления.';
    }
    if (!confirm(msg)) return;
    try {
      await api('/courses.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'delete', id }),
      });
      courseEditorState.dirty = false;
      if (typeof mfToast === 'function') mfToast('Курс удалён', 'success');
      if (crsState.tab !== 'drafts' && crsState.tab !== 'my') {
        crsActivateCoursesTab(isPublished ? 'my' : 'drafts');
      }
      crsLoadList();
      if (typeof showView === 'function') showView('coursesView');
    } catch (e) {
      alert(e.message || 'Не удалось удалить курс');
    }
  }

  async function crsEnroll() {
    const c = crsState.current;
    if (!c) return;
    try {
      await api('/courses.php', { method: 'POST', body: JSON.stringify({ action: 'enroll', course_id: c.id }) });
      await crsOpenDetail(c.id);
    } catch (e) { alert(e.message); }
  }

  async function crsCompleteLessonNoQuiz() {
    const c = crsState.current;
    const lesson = c?.lessons?.[crsState.lessonIdx];
    if (!lesson) return;
    try {
      const d = await api('/courses.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'complete_lesson', course_id: c.id, lesson_id: lesson.id }),
      });
      await crsOpenDetail(c.id);
      if (d.completed) crsShowCertificate(true);
    } catch (e) { alert(e.message); }
  }

  async function crsSubmitQuiz() {
    const c = crsState.current;
    const lesson = c?.lessons?.[crsState.lessonIdx];
    if (!lesson) return;
    const sel = document.querySelector('input[name="crsQuiz"]:checked');
    const msg = document.querySelector('.crs-quiz-msg');
    if (!sel) {
      if (msg) { msg.textContent = 'Выберите ответ'; msg.classList.remove('is-hidden'); }
      return;
    }
    try {
      const d = await api('/courses.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'complete_lesson', course_id: c.id, lesson_id: lesson.id, quiz_answer: +sel.value }),
      });
      if (msg) { msg.textContent = 'Верно! Урок пройден.'; msg.classList.remove('is-hidden'); }
      await crsOpenDetail(c.id);
      if (d.completed) crsShowCertificate(true);
    } catch (e) {
      if (msg) { msg.textContent = e.message || 'Неверный ответ'; msg.classList.remove('is-hidden'); }
    }
  }

  function crsShowCertificate(autoShareOffer) {
    const c = crsState.current;
    if (!c) return;
    const name = window.currentUser ? `${window.currentUser.first_name || ''} ${window.currentUser.last_name || ''}`.trim() : 'Студент';
    const html = `
      <div class="crs-cert-card"><div class="crs-cert-inner">
        <div class="crs-cert-badge">🎓</div>
        <h2>Сертификат</h2>
        <p class="crs-cert-name">${esc(name)}</p>
        <p>успешно завершил(а) курс</p>
        <h3>«${esc(c.title)}»</h3>
        <p class="crs-cert-date">${new Date().toLocaleDateString('ru-RU')}</p>
      </div></div>
      <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap">
        <button class="primary-button" id="crsCertShareFeed" type="button">Поделиться в ленте</button>
        <button class="ghost-button" id="crsCertShareChat" type="button">Отправить в чат</button>
        <button class="ghost-button" id="crsCertClose" type="button">Закрыть</button>
      </div>`;
    const modal = $('crsCertModal');
    if (modal) {
      modal.querySelector('.crs-cert-body').innerHTML = html;
      crsShowModal('crsCertModal');
      $('crsCertClose')?.addEventListener('click', () => crsHideModal('crsCertModal'));
      $('crsCertShareFeed')?.addEventListener('click', crsShareCertificateToFeed);
      $('crsCertShareChat')?.addEventListener('click', () => { crsHideModal('crsCertModal'); crsShareToChat(c); });
    } else if (autoShareOffer && confirm('Курс завершён! Поделиться сертификатом в ленте?')) {
      crsShareCertificateToFeed();
    }
  }

  async function crsShareCertificateToFeed() {
    const c = crsState.current;
    if (!c) return;
    try {
      await api('/courses.php', { method: 'POST', body: JSON.stringify({ action: 'certificate_post', course_id: c.id }) });
      if (typeof mfToast === 'function') mfToast('Опубликовано в ленте!', 'success');
      crsHideModal('crsCertModal');
    } catch (e) { alert(e.message); }
  }

  function crsShareToChat(c) {
    if (typeof window.openShareToChat !== 'function') return;
    window.openShareToChat({
      id: c.id,
      content: `📚 Курс «${c.title}»\n%%COURSE%%${JSON.stringify({ id: c.id, title: c.title, cover_image: c.cover_image, author_name: c.author?.name })}%%COURSE_END%%`,
      author_name: c.author?.name || '',
      avatar: c.author?.avatar || null,
    });
  }

  /* ═══════════════════════════════════════════════════════════
     COURSE EDITOR — черновик, поэтапное редактирование
  ═══════════════════════════════════════════════════════════ */

  function crsEditorLoadFromCourse(course) {
    Object.assign(courseEditorState, {
      courseId: course.id,
      status: course.status || 'draft',
      title: course.title || '',
      outcome: course.description || '',
      level: course.level || 'beginner',
      category: course.category || 'Дизайн',
      tags: course.tags || [],
      coverImage: course.cover_image || '',
      dirty: false,
      saving: false,
      lessons: (course.lessons || []).map(l => ({
        id: l.id,
        title: l.title,
        content: crsBlocksToMarkdown(l.blocks),
        imageUrl: crsLessonImageFromBlocks(l.blocks),
        quiz: l.quiz || null,
        blocks: l.blocks || [],
      })),
      activeLessonIdx: 0,
      quizLessonIdx: 0,
    });
  }

  function crsEditorSetStatus(text) {
    const el = $('crsEditorSaveStatus');
    if (el) el.textContent = text;
  }

  function crsEditorCollectMeta() {
    courseEditorState.title = $('crsEdTitle')?.value?.trim() || '';
    courseEditorState.outcome = $('crsEdOutcome')?.value?.trim() || '';
    courseEditorState.level = $('crsEdLevel')?.value || 'beginner';
    courseEditorState.category = $('crsEdCategory')?.value || 'Дизайн';
    courseEditorState.tags = ($('crsEdTags')?.value || '').split(',').map(t => t.trim()).filter(Boolean);
  }

  function crsEditorCollectStructure() {
    const rows = [...document.querySelectorAll('#crsEdLessonList .crs-wz-lesson-row')];
    courseEditorState.lessons = rows.map((row, i) => {
      const prev = courseEditorState.lessons[i] || {};
      return {
        id: row.dataset.id ? +row.dataset.id : prev.id || null,
        title: row.querySelector('.crs-wz-lesson-title')?.value?.trim() || '',
        content: prev.content || LESSON_TEMPLATE,
        imageUrl: prev.imageUrl || '',
        quiz: prev.quiz || null,
        blocks: prev.blocks || [],
      };
    }).filter(l => l.title);
  }

  function crsEditorCollectContent() {
    const idx = courseEditorState.activeLessonIdx;
    const lesson = courseEditorState.lessons[idx];
    if (!lesson) return;
    lesson.content = $('crsEdLessonContent')?.value || '';
  }

  function crsEditorCollectQuiz() {
    const idx = courseEditorState.quizLessonIdx;
    const lesson = courseEditorState.lessons[idx];
    if (!lesson) return;
    const q = $('crsEdQuizQuestion')?.value?.trim() || '';
    if (!q) {
      lesson.quiz = null;
      return;
    }
    const opts = [];
    document.querySelectorAll('.crs-ed-quiz-opt').forEach(inp => opts[+inp.dataset.i] = inp.value.trim());
    const correct = +(document.querySelector('input[name="crsEdQuizCorrect"]:checked')?.value ?? 0);
    lesson.quiz = { question: q, options: opts, correct };
  }

  function crsEditorCanPublish() {
    const w = courseEditorState;
    if (w.status === 'published') return false;
    if (!w.lessons.length) return false;
    return w.lessons.every(l => crsWordCount(l.content) >= 10);
  }

  async function crsEditorUploadImage(file) {
    const token = typeof window.resolveAuthToken === 'function' ? window.resolveAuthToken() : localStorage.getItem('mendflow_token');
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
    try { data = JSON.parse(raw); } catch (_) { throw new Error('Ошибка загрузки изображения'); }
    if (!res.ok) throw new Error(data.error || 'Ошибка загрузки');
    return crsMediaUrl(data.url);
  }

  async function crsEditorSaveMeta() {
    crsEditorCollectMeta();
    if (courseEditorState.title.length < 3) throw new Error('Название слишком короткое');
    await api('/courses.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'update',
        id: courseEditorState.courseId,
        title: courseEditorState.title,
        description: courseEditorState.outcome,
        category: courseEditorState.category,
        level: courseEditorState.level,
        tags: courseEditorState.tags,
        cover_image: courseEditorState.coverImage || null,
      }),
    });
    courseEditorState.dirty = false;
    crsEditorSetStatus('Сохранено');
    if (typeof mfToast === 'function') mfToast('Информация о курсе сохранена', 'success');
  }

  async function crsEditorSaveStructure() {
    crsEditorCollectStructure();
    if (!courseEditorState.lessons.length) throw new Error('Добавьте хотя бы один урок');
    const d = await api('/courses.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'create_lesson',
        course_id: courseEditorState.courseId,
        lessons: courseEditorState.lessons.map((l, i) => ({ id: l.id, title: l.title, sort_order: i })),
      }),
    });
    courseEditorState.lessons = (d.lessons || []).map((srv, i) => ({
      id: srv.id,
      title: srv.title,
      content: courseEditorState.lessons[i]?.content || LESSON_TEMPLATE,
      imageUrl: courseEditorState.lessons[i]?.imageUrl || '',
      quiz: courseEditorState.lessons[i]?.quiz || null,
      blocks: courseEditorState.lessons[i]?.blocks || [],
    }));
    courseEditorState.dirty = false;
    crsEditorSetStatus('Структура сохранена');
    if (typeof mfToast === 'function') mfToast('Структура сохранена', 'success');
  }

  async function crsEditorSaveCurrentLesson() {
    crsEditorCollectContent();
    const lesson = courseEditorState.lessons[courseEditorState.activeLessonIdx];
    if (!lesson?.id) throw new Error('Сначала сохраните структуру курса');
    const blocks = crsMarkdownToBlocks(lesson.content, lesson.imageUrl);
    await api('/courses.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'update_lesson',
        course_id: courseEditorState.courseId,
        lesson_id: lesson.id,
        blocks,
      }),
    });
    lesson.blocks = blocks;
    courseEditorState.dirty = false;
    crsEditorSetStatus('Урок сохранён');
  }

  async function crsEditorSaveCurrentQuiz() {
    crsEditorCollectQuiz();
    const lesson = courseEditorState.lessons[courseEditorState.quizLessonIdx];
    if (!lesson?.id) return;
    await api('/courses.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'update_lesson',
        course_id: courseEditorState.courseId,
        lesson_id: lesson.id,
        quiz: lesson.quiz,
      }),
    });
    courseEditorState.dirty = false;
    crsEditorSetStatus('Квиз сохранён');
  }

  async function crsEditorPublish() {
    if (!crsEditorCanPublish()) {
      alert('Заполните все уроки (минимум 10 слов) и сохраните структуру перед публикацией');
      return;
    }
    await api('/courses.php', {
      method: 'POST',
      body: JSON.stringify({ action: 'publish', id: courseEditorState.courseId }),
    });
    const id = courseEditorState.courseId;
    courseEditorState.status = 'published';
    courseEditorState.dirty = false;
    if (typeof mfToast === 'function') mfToast('🎉 Курс опубликован!', 'success');
    await crsOpenDetail(id);
  }

  async function crsEditorTryLeaveSection() {
    if (!courseEditorState.dirty) return true;
    if (confirm('Есть несохранённые изменения. Сохранить?')) {
      try {
        await crsEditorSaveCurrentSection();
        return true;
      } catch (e) {
        alert(e.message);
        return false;
      }
    }
    courseEditorState.dirty = false;
    return true;
  }

  async function crsEditorSaveCurrentSection() {
    const s = courseEditorState.section;
    if (s === 'meta') await crsEditorSaveMeta();
    else if (s === 'structure') await crsEditorSaveStructure();
    else if (s === 'content') await crsEditorSaveCurrentLesson();
    else if (s === 'quiz') await crsEditorSaveCurrentQuiz();
  }

  async function crsEditorSwitchSection(section) {
    if (section === courseEditorState.section) return;
    const ok = await crsEditorTryLeaveSection();
    if (!ok) return;
    courseEditorState.section = section;
    crsEditorRender();
  }

  function crsEditorUpdateContentHints() {
    const text = $('crsEdLessonContent')?.value || '';
    const wc = crsWordCount(text);
    const idx = courseEditorState.activeLessonIdx;
    const imgUrl = courseEditorState.lessons[idx]?.imageUrl || '';
    const hintEl = $('crsEdContentHint');
    if (!hintEl) return;
    let hint = '';
    if (wc > 150 && !imgUrl) hint = '💡 Добавить иллюстрацию? Курс с картинками лучше запоминается.';
    if (wc > 800) hint = (hint ? hint + ' ' : '') + '💡 Рассмотрите разделение на два урока — этот получился длинным.';
    hintEl.textContent = hint;
    hintEl.classList.toggle('is-hidden', !hint);
  }

  function crsEditorBindStructureDnD() {
    const list = $('crsEdLessonList');
    if (!list) return;
    let dragIdx = null;
    list.querySelectorAll('.crs-wz-lesson-row').forEach(row => {
      row.addEventListener('dragstart', () => { dragIdx = +row.dataset.i; row.classList.add('is-dragging'); });
      row.addEventListener('dragend', () => row.classList.remove('is-dragging'));
      row.addEventListener('dragover', e => { e.preventDefault(); row.classList.add('is-over'); });
      row.addEventListener('dragleave', () => row.classList.remove('is-over'));
      row.addEventListener('drop', e => {
        e.preventDefault();
        row.classList.remove('is-over');
        const toIdx = +row.dataset.i;
        if (dragIdx === null || dragIdx === toIdx) return;
        crsEditorCollectStructure();
        const moved = courseEditorState.lessons.splice(dragIdx, 1)[0];
        courseEditorState.lessons.splice(toIdx, 0, moved);
        crsEditorRenderPanel();
      });
      row.querySelector('.crs-wz-lesson-title')?.addEventListener('input', () => { courseEditorState.dirty = true; crsEditorSetStatus('Есть изменения'); });
      row.querySelector('.crs-wz-lesson-del')?.addEventListener('click', () => {
        crsEditorCollectStructure();
        const i = +row.dataset.i;
        if (courseEditorState.lessons.length <= 1) return;
        courseEditorState.lessons.splice(i, 1);
        courseEditorState.dirty = true;
        crsEditorRenderPanel();
      });
    });
    $('crsEdAddLesson')?.addEventListener('click', () => {
      crsEditorCollectStructure();
      courseEditorState.lessons.push({
        title: `Урок ${courseEditorState.lessons.length + 1}`,
        content: LESSON_TEMPLATE,
        imageUrl: '',
        quiz: null,
        id: null,
      });
      courseEditorState.dirty = true;
      crsEditorRenderPanel();
    });
  }

  function crsEditorUpdateCoverPreview() {
    const el = $('crsEdCoverPreview');
    if (!el) return;
    const url = courseEditorState.coverImage;
    if (url) {
      el.style.backgroundImage = `linear-gradient(180deg,rgba(15,10,30,.1),rgba(15,10,30,.35)),url('${crsMediaUrl(url).replace(/'/g, "\\'")}')`;
      el.classList.add('has-image');
      el.innerHTML = '';
    } else {
      el.style.backgroundImage = '';
      el.classList.remove('has-image');
      el.innerHTML = '<span class="crs-cover-placeholder">📚</span><span class="crs-cover-placeholder-text">Добавьте обложку</span>';
    }
    const hint = $('crsEdCoverHint');
    if (hint) hint.textContent = url ? 'Обложка загружена — нажмите «Сохранить»' : 'JPG, PNG или WebP · рекомендуется 1200×630';
    $('crsEdCoverRemoveBtn')?.classList.toggle('is-hidden', !url);
  }

  function crsEditorRenderPanelMeta() {
    const w = courseEditorState;
    const coverStyle = w.coverImage
      ? `background-image:linear-gradient(180deg,rgba(15,10,30,.1),rgba(15,10,30,.35)),url('${esc(crsMediaUrl(w.coverImage))}')`
      : '';
    return `
      <div class="crs-ed-panel">
        <h2>О курсе</h2>
        <p class="crs-wz-step-hint">Основная информация — можно менять в любой момент</p>
        <label class="crs-wz-label">Обложка курса</label>
        <div class="crs-cover-block">
          <div class="crs-cover-preview${w.coverImage ? ' has-image' : ''}" id="crsEdCoverPreview" style="${coverStyle}">
            ${w.coverImage ? '' : '<span class="crs-cover-placeholder">📚</span><span class="crs-cover-placeholder-text">Добавьте обложку</span>'}
          </div>
          <div class="crs-cover-actions">
            <input type="file" id="crsEdCoverInput" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
            <button type="button" class="ghost-button crs-cover-pick-btn" id="crsEdCoverPickBtn">📷 Загрузить обложку</button>
            <button type="button" class="ghost-button crs-cover-remove-btn${w.coverImage ? '' : ' is-hidden'}" id="crsEdCoverRemoveBtn">Удалить</button>
            <span class="crs-cover-hint" id="crsEdCoverHint">${w.coverImage ? 'Обложка загружена — нажмите «Сохранить»' : 'JPG, PNG или WebP · рекомендуется 1200×630'}</span>
          </div>
        </div>
        <label class="crs-wz-label">Название *</label>
        <input class="filter-input" id="crsEdTitle" type="text" maxlength="200" value="${esc(w.title)}">
        <label class="crs-wz-label">Для кого курс</label>
        <select class="filter-input" id="crsEdLevel">
          ${Object.entries(CRS_LEVELS).map(([k, v]) => `<option value="${k}"${k === w.level ? ' selected' : ''}>${v}</option>`).join('')}
        </select>
        <label class="crs-wz-label">Чему научится студент</label>
        <input class="filter-input" id="crsEdOutcome" type="text" maxlength="300" value="${esc(w.outcome)}">
        <label class="crs-wz-label">Категория</label>
        <select class="filter-input" id="crsEdCategory">
          ${CRS_CATEGORIES.map(c => `<option${c === w.category ? ' selected' : ''}>${c}</option>`).join('')}
        </select>
        <label class="crs-wz-label">Теги</label>
        <input class="filter-input" id="crsEdTags" type="text" value="${esc(w.tags.join(', '))}">
        <div class="crs-ed-panel-foot">
          <button class="primary-button" id="crsEdSaveMetaBtn" type="button">Сохранить</button>
        </div>
      </div>`;
  }

  function crsEditorRenderPanelStructure() {
    const items = courseEditorState.lessons.map((l, i) => `
      <li class="crs-wz-lesson-row" draggable="true" data-i="${i}" data-id="${l.id || ''}">
        <span class="crs-wz-drag" title="Перетащите">⠿</span>
        <input class="filter-input crs-wz-lesson-title" type="text" value="${esc(l.title)}" placeholder="Название урока">
        <button type="button" class="ghost-button crs-wz-lesson-del" data-i="${i}" title="Удалить">✕</button>
      </li>`).join('');
    return `
      <div class="crs-ed-panel">
        <h2>Структура курса</h2>
        <p class="crs-wz-step-hint">Добавьте уроки и расставьте порядок. Сохраните, прежде чем писать контент.</p>
        <ul class="crs-wz-lesson-list" id="crsEdLessonList">${items || '<li class="crs-empty">Пока нет уроков</li>'}</ul>
        <button type="button" class="ghost-button" id="crsEdAddLesson">+ Добавить урок</button>
        <div class="crs-ed-panel-foot">
          <button class="primary-button" id="crsEdSaveStructureBtn" type="button">Сохранить структуру</button>
        </div>
      </div>`;
  }

  function crsEditorRenderPanelContent() {
    const w = courseEditorState;
    const idx = w.activeLessonIdx;
    const lesson = w.lessons[idx];
    if (!w.lessons.length) {
      return `<div class="crs-ed-panel"><p class="crs-empty">Сначала добавьте уроки в разделе «Структура»</p></div>`;
    }
    const tabs = w.lessons.map((l, i) =>
      `<button type="button" class="crs-wz-lesson-tab${i === idx ? ' is-active' : ''}" data-i="${i}">${esc(l.title || 'Урок ' + (i + 1))}</button>`
    ).join('');
    return `
      <div class="crs-ed-panel crs-wz-step-split">
        <h2>Заполнение уроков</h2>
        <div class="crs-wz-split">
          <aside class="crs-wz-lesson-tabs">${tabs}</aside>
          <div class="crs-wz-lesson-editor">
            <label class="crs-wz-label">Контент урока</label>
            <textarea class="filter-input crs-wz-textarea" id="crsEdLessonContent" rows="14">${esc(lesson?.content || LESSON_TEMPLATE)}</textarea>
            <p class="crs-wz-hint is-hidden" id="crsEdContentHint"></p>
            <div class="crs-wz-image-row">
              <input type="file" id="crsEdImageInput" accept="image/*" hidden>
              <button type="button" class="ghost-button" id="crsEdImageBtn">📷 Загрузить картинку</button>
              <span class="crs-wz-image-name">${lesson?.imageUrl ? '✓ Картинка добавлена' : 'Без иллюстрации'}</span>
            </div>
            ${lesson?.imageUrl ? `<img class="crs-wz-image-preview" src="${esc(crsMediaUrl(lesson.imageUrl))}" alt="">` : ''}
            <div class="crs-ed-panel-foot">
              <button class="primary-button" id="crsEdSaveLessonBtn" type="button">Сохранить урок</button>
            </div>
          </div>
        </div>
      </div>`;
  }

  function crsEditorRenderPanelQuiz() {
    const w = courseEditorState;
    if (!w.lessons.length) {
      return `<div class="crs-ed-panel"><p class="crs-empty">Сначала добавьте уроки</p></div>`;
    }
    const idx = w.quizLessonIdx;
    const lesson = w.lessons[idx] || w.lessons[0];
    const q = lesson?.quiz || { question: '', options: ['', '', '', ''], correct: 0 };
    const tabs = w.lessons.map((l, i) =>
      `<button type="button" class="crs-wz-lesson-tab${i === idx ? ' is-active' : ''}" data-i="${i}">${esc(l.title)}</button>`
    ).join('');
    return `
      <div class="crs-ed-panel crs-wz-step-split">
        <h2>Квизы к урокам</h2>
        <p class="crs-wz-step-hint">Необязательно — можно пропустить для любого урока</p>
        <div class="crs-wz-split">
          <aside class="crs-wz-lesson-tabs">${tabs}</aside>
          <div class="crs-wz-quiz-form">
            <label class="crs-wz-label">Вопрос</label>
            <input class="filter-input" id="crsEdQuizQuestion" type="text" value="${esc(q.question)}" placeholder="Что такое...?">
            <label class="crs-wz-label">Варианты ответа</label>
            ${[0, 1, 2, 3].map(i => `
              <label class="crs-wz-quiz-row">
                <input type="radio" name="crsEdQuizCorrect" value="${i}"${+q.correct === i ? ' checked' : ''}>
                <input class="filter-input crs-ed-quiz-opt" data-i="${i}" type="text" value="${esc(q.options[i] || '')}" placeholder="Вариант ${i + 1}">
              </label>`).join('')}
            <div class="crs-ed-panel-foot">
              <button type="button" class="ghost-button" id="crsEdSkipQuiz">Пропустить квиз</button>
              <button class="primary-button" id="crsEdSaveQuizBtn" type="button">Сохранить квиз</button>
            </div>
          </div>
        </div>
      </div>`;
  }

  function crsEditorRenderPanelPublish() {
    const w = courseEditorState;
    const ready = crsEditorCanPublish();
    const isPublished = w.status === 'published';
    return `
      <div class="crs-ed-panel">
        <h2>Публикация</h2>
        ${isPublished
          ? '<p class="crs-ed-published-note">✓ Курс уже опубликован и виден в каталоге. Продолжайте редактировать — изменения сохраняются автоматически при сохранении разделов.</p>'
          : '<p class="crs-wz-step-hint">Опубликуйте курс, когда всё готово. До публикации он виден только вам как черновик.</p>'}
        <div class="crs-wz-summary panel">
          ${w.coverImage ? `<div class="crs-wz-summary-cover" style="background-image:url('${esc(crsMediaUrl(w.coverImage))}')"></div>` : ''}
          <h3>${esc(w.title)}</h3>
          <p>${esc(w.outcome)}</p>
          <div class="crs-wz-summary-meta">
            <span>${esc(CRS_LEVELS[w.level])}</span>
            <span>${esc(w.category)}</span>
            <span>${w.lessons.length} урок(ов)</span>
          </div>
          ${w.tags.length ? `<div class="mf-course-tags">${w.tags.map(t => `<span>#${esc(t)}</span>`).join('')}</div>` : ''}
          <ol class="crs-wz-summary-lessons">${w.lessons.map(l =>
            `<li>${esc(l.title)}${crsWordCount(l.content) >= 10 ? ' ✓' : ' — не заполнен'}${l.quiz?.question ? ' · квиз' : ''}</li>`
          ).join('')}</ol>
        </div>
        <div class="crs-ed-panel-foot crs-ed-publish-foot">
          <button class="ghost-button" id="crsEdPreviewBtn" type="button">Просмотреть как студент</button>
          ${isPublished
            ? `<button class="primary-button" id="crsEdViewPublishedBtn" type="button">Открыть опубликованный курс</button>`
            : `<button class="primary-button" id="crsEdPublishBtn" type="button"${ready ? '' : ' disabled'}>Опубликовать курс</button>`}
        </div>
        <div class="crs-ed-danger-zone">
          <strong>Опасная зона</strong>
          <p>Удаление курса необратимо — исчезнут все уроки, отзывы и прогресс студентов.</p>
          ${crsActionBtn('danger', 'Удалить курс навсегда', 'mf-crs-btn--wide', 'crsEdDeleteBtn')}
        </div>
        ${!ready && !isPublished ? '<p class="crs-wz-hint">Заполните и сохраните все уроки перед публикацией</p>' : ''}
      </div>`;
  }

  function crsEditorBindPanelEvents() {
    const s = courseEditorState.section;

    if (s === 'meta') {
      ['crsEdTitle', 'crsEdOutcome', 'crsEdTags'].forEach(id => {
        $(id)?.addEventListener('input', () => { courseEditorState.dirty = true; crsEditorSetStatus('Есть изменения'); });
      });
      $('crsEdLevel')?.addEventListener('change', () => { courseEditorState.dirty = true; });
      $('crsEdCategory')?.addEventListener('change', () => { courseEditorState.dirty = true; });
      $('crsEdCoverPickBtn')?.addEventListener('click', () => $('crsEdCoverInput')?.click());
      $('crsEdCoverInput')?.addEventListener('change', async e => {
        const file = e.target.files?.[0];
        if (!file) return;
        const btn = $('crsEdCoverPickBtn');
        if (btn) btn.classList.add('is-loading');
        try {
          courseEditorState.coverImage = await crsEditorUploadImage(file);
          courseEditorState.dirty = true;
          crsEditorUpdateCoverPreview();
          crsEditorSetStatus('Обложка загружена — сохраните изменения');
          if (typeof mfToast === 'function') mfToast('Обложка загружена', 'success');
        } catch (err) {
          alert(err.message || 'Ошибка загрузки обложки');
        } finally {
          if (btn) btn.classList.remove('is-loading');
          e.target.value = '';
        }
      });
      $('crsEdCoverRemoveBtn')?.addEventListener('click', () => {
        courseEditorState.coverImage = '';
        courseEditorState.dirty = true;
        crsEditorUpdateCoverPreview();
        crsEditorSetStatus('Обложка удалена — сохраните изменения');
      });
      $('crsEdSaveMetaBtn')?.addEventListener('click', async () => {
        try { await crsEditorSaveMeta(); crsEditorRenderShell(); } catch (e) { alert(e.message); }
      });
    }

    if (s === 'structure') {
      crsEditorBindStructureDnD();
      $('crsEdSaveStructureBtn')?.addEventListener('click', async () => {
        try { await crsEditorSaveStructure(); crsEditorRenderShell(); } catch (e) { alert(e.message); }
      });
    }

    if (s === 'content') {
      $('crsEdLessonContent')?.addEventListener('input', () => {
        courseEditorState.dirty = true;
        crsEditorUpdateContentHints();
        crsEditorSetStatus('Есть изменения');
      });
      crsEditorUpdateContentHints();
      document.querySelectorAll('.crs-wz-lesson-tab').forEach(btn => {
        btn.addEventListener('click', async () => {
          const newIdx = +btn.dataset.i;
          if (newIdx === courseEditorState.activeLessonIdx) return;
          if (courseEditorState.dirty) {
            try { await crsEditorSaveCurrentLesson(); } catch (_) {}
          }
          courseEditorState.activeLessonIdx = newIdx;
          crsEditorRenderPanel();
        });
      });
      $('crsEdSaveLessonBtn')?.addEventListener('click', async () => {
        try { await crsEditorSaveCurrentLesson(); if (typeof mfToast === 'function') mfToast('Урок сохранён', 'success'); } catch (e) { alert(e.message); }
      });
      $('crsEdImageBtn')?.addEventListener('click', () => $('crsEdImageInput')?.click());
      $('crsEdImageInput')?.addEventListener('change', async e => {
        const file = e.target.files?.[0];
        if (!file) return;
        try {
          const url = await crsEditorUploadImage(file);
          courseEditorState.lessons[courseEditorState.activeLessonIdx].imageUrl = url;
          courseEditorState.dirty = true;
          crsEditorRenderPanel();
        } catch (err) { alert(err.message); }
      });
    }

    if (s === 'quiz') {
      document.querySelectorAll('.crs-wz-lesson-tab').forEach(btn => {
        btn.addEventListener('click', async () => {
          const newIdx = +btn.dataset.i;
          if (newIdx === courseEditorState.quizLessonIdx) return;
          if (courseEditorState.dirty) {
            try { await crsEditorSaveCurrentQuiz(); } catch (_) {}
          }
          courseEditorState.quizLessonIdx = newIdx;
          crsEditorRenderPanel();
        });
      });
      $('crsEdSkipQuiz')?.addEventListener('click', async () => {
        const lesson = courseEditorState.lessons[courseEditorState.quizLessonIdx];
        if (!lesson?.id) return;
        lesson.quiz = null;
        try {
          await api('/courses.php', {
            method: 'POST',
            body: JSON.stringify({
              action: 'update_lesson',
              course_id: courseEditorState.courseId,
              lesson_id: lesson.id,
              quiz: null,
            }),
          });
          courseEditorState.dirty = false;
          crsEditorSetStatus('Квиз пропущен');
          if (typeof mfToast === 'function') mfToast('Квиз пропущен', 'info');
        } catch (e) { alert(e.message); }
      });
      $('crsEdSaveQuizBtn')?.addEventListener('click', async () => {
        try {
          await crsEditorSaveCurrentQuiz();
          if (typeof mfToast === 'function') mfToast('Квиз сохранён', 'success');
        } catch (e) { alert(e.message); }
      });
      document.querySelectorAll('.crs-ed-quiz-opt, #crsEdQuizQuestion').forEach(el => {
        el?.addEventListener('input', () => { courseEditorState.dirty = true; crsEditorSetStatus('Есть изменения'); });
      });
    }

    if (s === 'publish') {
      $('crsEdPreviewBtn')?.addEventListener('click', () => crsEditorPreview());
      $('crsEdPublishBtn')?.addEventListener('click', () => crsEditorPublish());
      $('crsEdViewPublishedBtn')?.addEventListener('click', () => crsOpenDetail(courseEditorState.courseId));
      $('crsEdDeleteBtn')?.addEventListener('click', () => crsDeleteCourse(courseEditorState.courseId, courseEditorState.title, courseEditorState.status));
    }
  }

  function crsEditorRenderPanel() {
    const panel = $('crsEditorPanel');
    if (!panel) return;
    const s = courseEditorState.section;
    if (s === 'meta') panel.innerHTML = crsEditorRenderPanelMeta();
    else if (s === 'structure') panel.innerHTML = crsEditorRenderPanelStructure();
    else if (s === 'content') panel.innerHTML = crsEditorRenderPanelContent();
    else if (s === 'quiz') panel.innerHTML = crsEditorRenderPanelQuiz();
    else panel.innerHTML = crsEditorRenderPanelPublish();
    crsEditorBindPanelEvents();
  }

  function crsEditorRenderShell() {
    const root = $('crsEditorRoot');
    if (!root) return;
    const w = courseEditorState;
    const nav = CRS_EDITOR_SECTIONS.map(sec => `
      <button type="button" class="crs-editor-nav-btn${sec.key === w.section ? ' is-active' : ''}" data-sec="${sec.key}">
        <span class="crs-editor-nav-label">${esc(sec.label)}</span>
        <span class="crs-editor-nav-hint">${esc(sec.hint)}</span>
      </button>`).join('');

    root.innerHTML = `
      <div class="crs-editor">
        <header class="crs-editor-header">
          <button class="ghost-button" id="crsEditorBackBtn" type="button">← К курсам</button>
          <div class="crs-editor-header-main">
            <h1>${esc(w.title || 'Новый курс')}</h1>
            <span class="mf-course-draft-badge">${w.status === 'published' ? 'Опубликован' : 'Черновик'}</span>
            <span class="crs-editor-save-status" id="crsEditorSaveStatus">Готово к редактированию</span>
          </div>
          ${crsActionBtn('danger', 'Удалить курс', 'crs-editor-delete-btn', 'crsEditorDeleteBtn')}
        </header>
        <div class="crs-editor-layout">
          <nav class="crs-editor-nav">${nav}</nav>
          <main class="crs-editor-panel" id="crsEditorPanel"></main>
        </div>
      </div>`;

    $('crsEditorBackBtn')?.addEventListener('click', () => crsEditorLeave());
    $('crsEditorDeleteBtn')?.addEventListener('click', () => crsDeleteCourse(w.courseId, w.title, w.status));
    root.querySelectorAll('.crs-editor-nav-btn').forEach(btn => {
      btn.addEventListener('click', () => crsEditorSwitchSection(btn.dataset.sec));
    });
    crsEditorRenderPanel();
  }

  function crsEditorRender() {
    crsEditorRenderShell();
  }

  function crsEditorPreview() {
    crsEditorCollectContent();
    const w = courseEditorState;
    let previewIdx = w.activeLessonIdx || 0;

    function renderPreviewLesson(idx) {
      previewIdx = idx;
      const lesson = w.lessons[idx];
      if (!lesson) return;
      const blocks = crsMarkdownToBlocks(lesson.content, lesson.imageUrl);
      const previewLesson = { title: lesson.title, blocks, quiz: lesson.quiz, completed: false };
      const root = $('crsEditorPreviewRoot');
      if (!root) return;
      const heroStyle = w.coverImage
        ? `background-image:linear-gradient(180deg,rgba(15,10,30,.35),rgba(15,10,30,.75)),url('${esc(crsMediaUrl(w.coverImage))}');background-size:cover;background-position:center;`
        : '';
      root.innerHTML = `
        <div class="crs-wz-preview-course">
          <div class="crs-wz-preview-hero"${heroStyle ? ` style="${heroStyle}"` : ''}>
            <span class="mf-course-level">${esc(CRS_LEVELS[w.level])}</span>
            <h1>${esc(w.title)}</h1>
            <p>${esc(w.outcome)}</p>
          </div>
          <div class="crs-detail-layout">
            <aside class="crs-lesson-sidebar">${w.lessons.map((l, i) =>
              `<button type="button" class="crs-lesson-nav${i === idx ? ' is-active' : ''}" data-prev-i="${i}">${i + 1}. ${esc(l.title)}</button>`
            ).join('')}</aside>
            <main class="crs-lesson-main">${crsRenderLessonBlocksHtml(previewLesson, { showQuiz: true, enrolled: true, preview: true })}</main>
          </div>
        </div>`;
      root.querySelectorAll('[data-prev-i]').forEach(btn => {
        btn.addEventListener('click', () => renderPreviewLesson(+btn.dataset.prevI));
      });
    }

    renderPreviewLesson(previewIdx);
    $('crsEditorPreviewOverlay')?.classList.remove('is-hidden');
  }

  async function crsEditorLeave() {
    if (courseEditorState.dirty) {
      if (!confirm('Есть несохранённые изменения. Выйти без сохранения?')) return;
    }
    courseEditorState.dirty = false;
    if (typeof showView === 'function') showView('coursesView');
  }

  async function crsOpenEditor(id, section) {
    if (!crsRequireAuth()) return;
    try {
      const d = await api(`/courses.php?action=detail&id=${id}`);
      if (!d.course?.is_author && !crsIsCourseAuthor(d.course)) {
        alert('Нет доступа к редактированию');
        return;
      }
      crsEditorLoadFromCourse(d.course);
      if (section) courseEditorState.section = section;
      crsEditorRender();
      if (typeof showView === 'function') showView('courseEditorView');
    } catch (e) {
      alert(e.message);
    }
  }

  function crsOpenCreateModal() {
    if (!crsRequireAuth()) return;
    $('crsCreateTitle').value = '';
    $('crsCreateOutcome').value = '';
    $('crsCreateLevel').value = 'beginner';
    $('crsCreateCategory').value = 'Дизайн';
    $('crsCreateTags').value = '';
    crsShowModal('crsCreateModal');
    setTimeout(() => $('crsCreateTitle')?.focus(), 50);
  }

  function crsCloseCreateModal() {
    crsHideModal('crsCreateModal');
  }

  async function crsCreateDraft() {
    const title = $('crsCreateTitle')?.value?.trim() || '';
    const outcome = $('crsCreateOutcome')?.value?.trim() || '';
    if (title.length < 3) { alert('Введите название (минимум 3 символа)'); return; }
    if (outcome.length < 5) { alert('Опишите результат обучения (минимум 5 символов)'); return; }
    const btn = $('crsCreateSubmitBtn');
    if (btn) btn.disabled = true;
    try {
      const d = await api('/courses.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'create',
          title,
          description: outcome,
          level: $('crsCreateLevel')?.value || 'beginner',
          category: $('crsCreateCategory')?.value || 'Дизайн',
          tags: ($('crsCreateTags')?.value || '').split(',').map(t => t.trim()).filter(Boolean),
        }),
      });
      crsCloseCreateModal();
      if (typeof mfToast === 'function') mfToast('Черновик создан. Добавьте уроки и наполните курс.', 'success');
      await crsOpenEditor(d.course.id, 'structure');
    } catch (e) {
      alert(e.message);
    } finally {
      if (btn) btn.disabled = false;
    }
  }
  async function crsLoadProfileCourses(type, containerId) {
    const el = $(containerId);
    if (!el) return;
    if (!localStorage.getItem('mendflow_token')) {
      el.innerHTML = '<p class="crs-empty">Войдите, чтобы видеть курсы</p>';
      return;
    }
    el.innerHTML = '<p class="crs-loading">Загрузка...</p>';
    try {
      const action = type === 'created' ? 'my_created' : type === 'completed' ? 'my_completed' : 'my_enrolled';
      const d = await api(`/courses.php?action=${action}`);
      el.innerHTML = '';
      const list = d.courses || [];
      if (!list.length) { el.innerHTML = '<p class="crs-empty">Пока пусто</p>'; return; }
      list.forEach(c => el.appendChild(crsRenderCard(c, { showActions: type === 'created' })));
    } catch (e) { el.innerHTML = `<p class="crs-empty">${esc(e.message)}</p>`; }
  }

  async function crsLoadAuthorReviews(userId, containerId) {
    const el = $(containerId);
    if (!el) return;
    try {
      const d = await api(`/courses.php?action=author_reviews&user_id=${userId}`);
      if (!d.reviews?.length) { el.innerHTML = ''; return; }
      el.innerHTML = `
        <div class="crs-reviews-section crs-reviews-section--compact">
          <div class="crs-reviews-hero crs-reviews-hero--compact">
            <div class="crs-reviews-hero-inner">
              <div class="crs-reviews-hero-copy">
                <span class="crs-reviews-kicker">Автор</span>
                <h4 class="crs-reviews-title">Отзывы на курсы</h4>
              </div>
              <span class="crs-reviews-count">${d.reviews.length} отзывов</span>
            </div>
          </div>
          <div class="crs-reviews-grid">${d.reviews.map((r) => crsReviewCardHtml(r, { courseTitle: r.course_title })).join('')}</div>
        </div>`;
    } catch (e) {}
  }

  function crsInit() {
    document.querySelectorAll('[data-crs-tab]').forEach(btn => {
      btn.addEventListener('click', () => {
        crsActivateCoursesTab(btn.dataset.crsTab || 'popular');
        crsLoadList();
      });
    });

    $('crsSearchInput')?.addEventListener('input', debounce(crsLoadList, 350));
    $('crsTagInput')?.addEventListener('change', crsLoadList);
    $('crsCategoryInput')?.addEventListener('change', crsLoadList);
    $('crsLevelInput')?.addEventListener('change', crsLoadList);

    $('crsCreateBtn')?.addEventListener('click', e => { e.preventDefault(); crsOpenCreateModal(); });
    $('crsCreateCloseBtn')?.addEventListener('click', () => crsCloseCreateModal());
    $('crsCreateCancelBtn')?.addEventListener('click', () => crsCloseCreateModal());
    $('crsCreateSubmitBtn')?.addEventListener('click', () => crsCreateDraft());
    $('crsEditorPreviewClose')?.addEventListener('click', () => $('crsEditorPreviewOverlay')?.classList.add('is-hidden'));

    $('crsCreateModal')?.addEventListener('click', e => {
      if (e.target.id === 'crsCreateModal') crsCloseCreateModal();
    });
    $('crsCertModal')?.addEventListener('click', e => {
      if (e.target.id === 'crsCertModal') crsHideModal('crsCertModal');
    });

    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        if (!$('crsEditorPreviewOverlay')?.classList.contains('is-hidden')) {
          $('crsEditorPreviewOverlay')?.classList.add('is-hidden');
          return;
        }
        if (!$('crsCreateModal')?.classList.contains('is-hidden')) {
          crsCloseCreateModal();
        }
        crsHideModal('crsCertModal');
      }
    });

    document.querySelectorAll('[data-profile-tab="my_courses"]').forEach(b =>
      b.addEventListener('click', () => crsLoadProfileCourses('created', 'profileMyCoursesList')));
    document.querySelectorAll('[data-profile-tab="completed_courses"]').forEach(b =>
      b.addEventListener('click', () => crsLoadProfileCourses('completed', 'profileCompletedCoursesList')));
  }

  window.crsLoadList = crsLoadList;
  window.crsOpenDetail = crsOpenDetail;
  window.crsLoadProfileCourses = crsLoadProfileCourses;
  window.crsLoadAuthorReviews = crsLoadAuthorReviews;
  window.crsInit = crsInit;
  window.crsOpenEditor = crsOpenEditor;
  window.crsOpenCreateModal = crsOpenCreateModal;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', crsInit);
  } else {
    crsInit();
  }
})();
