/**
 * Mendflow — персонализированный onboarding после первого входа.
 * Шаги: приветствие → интересы → проекты → люди → готово.
 */
(function () {
  'use strict';

  const STORAGE_KEY = 'mf_onboarding_done_v1';
  const MIN_INTERESTS = 1;
  const TARGET_PROJECTS = 3;
  const TARGET_FRIENDS = 2;

  const INTEREST_CHIPS = [
    { id: 'frontend', label: 'Frontend' },
    { id: 'design', label: 'Design' },
    { id: 'product', label: 'Product' },
    { id: 'ai', label: 'AI / ML' },
    { id: 'backend', label: 'Backend' },
    { id: 'data', label: 'Data' },
    { id: 'mobile', label: 'Mobile' },
    { id: 'marketing', label: 'Marketing' },
    { id: 'startup', label: 'Стартапы' },
    { id: 'gamedev', label: 'Game Dev' },
    { id: 'devops', label: 'DevOps' },
    { id: 'writing', label: 'Контент' },
  ];

  const state = {
    step: 0,
    interests: [],
    projects: new Set(),
    friends: new Set(),
    projectsLoaded: false,
    friendsLoaded: false,
  };

  function $(id) { return document.getElementById(id); }

  function esc(s) {
    if (typeof window.esc === 'function') return window.esc(s);
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function getInterestLabel(v) {
    if (typeof window.getInterestLabel === 'function') return window.getInterestLabel(v);
    const c = INTEREST_CHIPS.find((x) => x.id === v);
    return c ? c.label : v;
  }

  function loadProfile() {
    if (typeof window.loadProfile === 'function') return window.loadProfile();
    try { return JSON.parse(localStorage.getItem('mendflow_profile') || '{}'); } catch (_) { return {}; }
  }

  function saveProfileLocal(patch) {
    const existing = loadProfile();
    const merged = { ...existing, ...patch };
    if (typeof window.saveProfile === 'function') window.saveProfile(merged);
    else localStorage.setItem('mendflow_profile', JSON.stringify(merged));
    if (typeof window.applyProfileToUI === 'function') window.applyProfileToUI(merged);
    return merged;
  }

  function isStudentAccount() {
    if (typeof window.isUniversityAccount === 'function' && window.isUniversityAccount()) return false;
    if (typeof window.isCompanyAccount === 'function' && window.isCompanyAccount()) return false;
    return true;
  }

  function shouldShow() {
    if (!window.currentUser?.id || !window.currentToken) return false;
    if (!isStudentAccount()) return false;
    if (localStorage.getItem(STORAGE_KEY)) return false;
    const p = loadProfile();
    if (p.interest1) return false;
    return true;
  }

  function markDone(skipped) {
    localStorage.setItem(STORAGE_KEY, skipped ? 'skipped' : 'done');
  }

  function showBackdrop(show) {
    const el = $('onboardingBackdrop');
    if (!el) return;
    el.classList.toggle('is-hidden', !show);
    document.body.classList.toggle('onb-open', !!show);
  }

  function setProgress(step) {
    const fill = $('onbProgressFill');
    const label = $('onbProgressLabel');
    const total = 4;
    const pct = Math.round((step / total) * 100);
    if (fill) fill.style.width = pct + '%';
    if (label) label.textContent = `Шаг ${Math.min(step + 1, total)} из ${total}`;
    document.querySelectorAll('.onb-step').forEach((panel) => {
      panel.classList.toggle('is-active', panel.dataset.onbStep === String(step));
    });
    const skipBtn = $('onbSkipBtn');
    const backBtn = $('onbBackBtn');
    const nextBtn = $('onbNextBtn');
    if (skipBtn) skipBtn.classList.toggle('is-hidden', step >= 3);
    if (backBtn) backBtn.classList.toggle('is-hidden', step === 0);
    if (nextBtn) {
      nextBtn.textContent = step === 3 ? 'В ленту →' : 'Далее';
      nextBtn.disabled = step === 1 && state.interests.length < MIN_INTERESTS;
    }
  }

  function renderInterestChips() {
    const wrap = $('onbInterestChips');
    if (!wrap) return;
    wrap.innerHTML = INTEREST_CHIPS.map((c) => {
      const active = state.interests.includes(c.id);
      return `<button type="button" class="onb-chip${active ? ' is-active' : ''}" data-onb-interest="${esc(c.id)}">${esc(c.label)}</button>`;
    }).join('');
    wrap.querySelectorAll('[data-onb-interest]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.onbInterest;
        const idx = state.interests.indexOf(id);
        if (idx >= 0) state.interests.splice(idx, 1);
        else if (state.interests.length < 3) state.interests.push(id);
        renderInterestChips();
        const nextBtn = $('onbNextBtn');
        if (nextBtn) nextBtn.disabled = state.interests.length < MIN_INTERESTS;
      });
    });
    const hint = $('onbInterestHint');
    if (hint) {
      hint.textContent = state.interests.length
        ? `Выбрано: ${state.interests.length}/3`
        : 'Выберите минимум 1 интерес';
    }
  }

  async function loadProjects() {
    const list = $('onbProjectsList');
    if (!list || state.projectsLoaded) return;
    list.innerHTML = '<p class="onb-muted">Загружаем проекты...</p>';
    try {
      const d = await window.api('/projects.php?action=explore&sort=newest&limit=12');
      const projects = d.projects || [];
      state.projectsLoaded = true;
      if (!projects.length) {
        list.innerHTML = '<p class="onb-muted">Пока нет проектов — можно пропустить этот шаг.</p>';
        return;
      }
      list.innerHTML = projects.map((p) => {
        const active = state.projects.has(p.id);
        const meta = [p.member_count ? `${p.member_count} уч.` : '', p.category || ''].filter(Boolean).join(' · ');
        return `<button type="button" class="onb-pick-card${active ? ' is-active' : ''}" data-onb-project="${p.id}">
          <span class="onb-pick-title">${esc(p.title)}</span>
          <span class="onb-pick-meta">${esc(meta || 'Проект')}</span>
          <span class="onb-pick-check">${active ? '✓' : '+'}</span>
        </button>`;
      }).join('');
      list.querySelectorAll('[data-onb-project]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const id = +btn.dataset.onbProject;
          if (state.projects.has(id)) state.projects.delete(id);
          else if (state.projects.size < TARGET_PROJECTS) state.projects.add(id);
          updateProjectsUI();
        });
      });
      updateProjectsUI();
    } catch (e) {
      list.innerHTML = `<p class="onb-muted">Не удалось загрузить проекты. ${esc(e.message || '')}</p>`;
    }
  }

  function updateProjectsUI() {
    document.querySelectorAll('[data-onb-project]').forEach((btn) => {
      const id = +btn.dataset.onbProject;
      const active = state.projects.has(id);
      btn.classList.toggle('is-active', active);
      const check = btn.querySelector('.onb-pick-check');
      if (check) check.textContent = active ? '✓' : '+';
    });
    const hint = $('onbProjectsHint');
    if (hint) hint.textContent = `Подписки: ${state.projects.size}/${TARGET_PROJECTS} (необязательно)`;
  }

  async function loadFriends() {
    const list = $('onbFriendsList');
    if (!list || state.friendsLoaded) return;
    list.innerHTML = '<p class="onb-muted">Ищем людей рядом с вами...</p>';
    try {
      const d = await window.api('/friends.php?type=recommendations&limit=10');
      const people = d.recommendations || [];
      state.friendsLoaded = true;
      if (!people.length) {
        list.innerHTML = '<p class="onb-muted">Пока нет рекомендаций — загляните в раздел «Сеть» позже.</p>';
        return;
      }
      list.innerHTML = people.map((p) => {
        const active = state.friends.has(p.id);
        const name = [p.first_name, p.last_name].filter(Boolean).join(' ') || 'Участник';
        const sub = p.organization || p.city || p.primary_reason || '';
        const ini = name.charAt(0).toUpperCase();
        const av = p.avatar
          ? `<img src="${esc(p.avatar)}" alt="">`
          : `<span>${esc(ini)}</span>`;
        return `<button type="button" class="onb-person-card${active ? ' is-active' : ''}" data-onb-friend="${p.id}">
          <span class="onb-person-av">${av}</span>
          <span class="onb-person-info">
            <span class="onb-person-name">${esc(name)}</span>
            <span class="onb-person-sub">${esc(sub)}</span>
          </span>
          <span class="onb-pick-check">${active ? '✓' : '+'}</span>
        </button>`;
      }).join('');
      list.querySelectorAll('[data-onb-friend]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const id = +btn.dataset.onbFriend;
          if (state.friends.has(id)) state.friends.delete(id);
          else if (state.friends.size < TARGET_FRIENDS) state.friends.add(id);
          updateFriendsUI();
        });
      });
      updateFriendsUI();
    } catch (e) {
      list.innerHTML = `<p class="onb-muted">Не удалось загрузить рекомендации.</p>`;
    }
  }

  function updateFriendsUI() {
    document.querySelectorAll('[data-onb-friend]').forEach((btn) => {
      const id = +btn.dataset.onbFriend;
      const active = state.friends.has(id);
      btn.classList.toggle('is-active', active);
      const check = btn.querySelector('.onb-pick-check');
      if (check) check.textContent = active ? '✓' : '+';
    });
    const hint = $('onbFriendsHint');
    if (hint) hint.textContent = `Заявки: ${state.friends.size}/${TARGET_FRIENDS} (необязательно)`;
  }

  async function saveInterests() {
    const [i1, i2, i3] = state.interests;
    const specialty = i1 ? getInterestLabel(i1) : '';
    const patch = { interest1: i1 || '', interest2: i2 || '', interest3: i3 || '', specialty };
    saveProfileLocal(patch);
    if (window.currentUser) {
      window.currentUser.interest1 = patch.interest1;
      window.currentUser.interest2 = patch.interest2;
      window.currentUser.interest3 = patch.interest3;
      window.currentUser.specialty = patch.specialty;
      localStorage.setItem(window.USER_KEY || 'mendflow_user', JSON.stringify(window.currentUser));
    }
    try {
      await window.api('/profile.php', {
        method: 'PUT',
        body: JSON.stringify({
          interest1: patch.interest1,
          interest2: patch.interest2,
          interest3: patch.interest3,
          specialty: patch.specialty,
        }),
      });
    } catch (_) {}
  }

  async function followProjects() {
    for (const id of state.projects) {
      try {
        await window.api('/projects.php', { method: 'POST', body: JSON.stringify({ action: 'follow', id }) });
      } catch (_) {}
    }
  }

  async function sendFriendRequests() {
    for (const id of state.friends) {
      try {
        await window.api('/friends.php', { method: 'POST', body: JSON.stringify({ action: 'send', to_id: id }) });
      } catch (_) {}
    }
  }

  async function finishOnboarding() {
    const nextBtn = $('onbNextBtn');
    if (nextBtn) { nextBtn.disabled = true; nextBtn.textContent = 'Сохраняем...'; }
    try {
      if (state.step >= 1 && state.interests.length) await saveInterests();
      await followProjects();
      await sendFriendRequests();
      markDone(false);
      document.dispatchEvent(new CustomEvent('mf:onboarding-finished', {
        detail: {
          interests: [...state.interests],
          projectCount: state.projects.size,
          friendCount: state.friends.size,
        },
      }));
    } finally {
      showBackdrop(false);
      if (nextBtn) { nextBtn.disabled = false; nextBtn.textContent = 'В ленту →'; }
    }
  }

  function goStep(step) {
    state.step = step;
    setProgress(step);
    if (step === 1) renderInterestChips();
    if (step === 2) loadProjects();
    if (step === 3) loadFriends();
  }

  function open() {
    if (!shouldShow()) return;
    const name = window.currentUser?.first_name || loadProfile().firstName || 'друг';
    const welcome = $('onbWelcomeName');
    if (welcome) welcome.textContent = name;
    state.step = 0;
    state.interests = [];
    state.projects = new Set();
    state.friends = new Set();
    state.projectsLoaded = false;
    state.friendsLoaded = false;
    goStep(0);
    showBackdrop(true);
  }

  function bind() {
    $('onbSkipBtn')?.addEventListener('click', () => {
      markDone(true);
      showBackdrop(false);
    });
    $('onbBackBtn')?.addEventListener('click', () => {
      if (state.step > 0) goStep(state.step - 1);
    });
    $('onbNextBtn')?.addEventListener('click', async () => {
      if (state.step === 0) return goStep(1);
      if (state.step === 1) {
        if (state.interests.length < MIN_INTERESTS) return;
        await saveInterests();
        return goStep(2);
      }
      if (state.step === 2) return goStep(3);
      if (state.step === 3) return finishOnboarding();
    });
    $('onboardingBackdrop')?.addEventListener('click', (e) => {
      if (e.target.id === 'onboardingBackdrop') {
        markDone(true);
        showBackdrop(false);
      }
    });
  }

  let openScheduled = false;

  function maybeOpen() {
    if (!shouldShow() || openScheduled) return;
    openScheduled = true;
    setTimeout(() => {
      openScheduled = false;
      if (shouldShow()) open();
    }, 500);
  }

  function init() {
    bind();
    document.addEventListener('mf:auth-ready', maybeOpen);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.MF_ONBOARDING = { maybeOpen, open, shouldShow, markDone };
})();
