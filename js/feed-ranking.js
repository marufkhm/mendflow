/**
 * feed-ranking.js — сбор сигналов ленты для smart feed
 * impression, read time (IntersectionObserver), watch time (video)
 */
(function (global) {
  'use strict';

  const FLUSH_MS = 12000;
  const MIN_READ_TICK_MS = 800;
  const IMPRESSION_RATIO = 0.35;

  const queue = new Map();
  const impressed = new Set();
  const readState = new Map();
  let flushTimer = null;
  let observer = null;

  function authHeaders() {
    const token = localStorage.getItem('token') || localStorage.getItem('authToken');
    const h = { 'Content-Type': 'application/json' };
    if (token) h.Authorization = 'Bearer ' + token;
    return h;
  }

  function postIdFromEl(el) {
    if (!el) return 0;
    const id = el.dataset.postId || el.getAttribute('data-post-id');
    return id ? parseInt(id, 10) : 0;
  }

  function bump(postId, field, amount) {
    if (!postId || !amount) return;
    const cur = queue.get(postId) || { post_id: postId, impression: 0, click: 0, read_ms: 0, watch_ms: 0 };
    cur[field] = (cur[field] || 0) + amount;
    queue.set(postId, cur);
    scheduleFlush();
  }

  function scheduleFlush() {
    if (flushTimer) return;
    flushTimer = setTimeout(flush, FLUSH_MS);
  }

  function flush() {
    flushTimer = null;
    if (!queue.size) return;
    const token = localStorage.getItem('token') || localStorage.getItem('authToken');
    if (!token) {
      queue.clear();
      return;
    }
    const signals = Array.from(queue.values());
    queue.clear();
    const body = JSON.stringify({ action: 'batch', signals });
    const url = (global.MF_API_BASE || './api') + '/post_signals.php';
    fetch(url, { method: 'POST', headers: authHeaders(), body, keepalive: true }).catch(() => {});
  }

  function onIntersect(entries) {
    const now = Date.now();
    entries.forEach((entry) => {
      const el = entry.target;
      const postId = postIdFromEl(el);
      if (!postId) return;

      if (entry.isIntersecting && entry.intersectionRatio >= IMPRESSION_RATIO) {
        const key = 'imp:' + postId;
        if (!impressed.has(key)) {
          impressed.add(key);
          bump(postId, 'impression', 1);
        }
        const st = readState.get(postId) || { visibleSince: now, lastTick: now, accMs: 0 };
        st.visibleSince = st.visibleSince || now;
        readState.set(postId, st);
      } else {
        const st = readState.get(postId);
        if (st) st.visibleSince = 0;
      }
    });
  }

  function tickReadTime() {
    const now = Date.now();
    readState.forEach((st, postId) => {
      if (!st.visibleSince) return;
      const delta = now - (st.lastTick || st.visibleSince);
      if (delta >= MIN_READ_TICK_MS) {
        st.accMs += delta;
        st.lastTick = now;
        if (st.accMs >= MIN_READ_TICK_MS) {
          const chunk = st.accMs;
          st.accMs = 0;
          bump(postId, 'read_ms', chunk);
        }
      }
    });
  }

  function bindVideo(el) {
    if (!el || el.dataset.frVideoBound) return;
    el.dataset.frVideoBound = '1';
    const postEl = el.closest('.social-post');
    const postId = postIdFromEl(postEl);
    if (!postId) return;

    let lastT = 0;
    el.addEventListener('timeupdate', () => {
      const t = el.currentTime || 0;
      if (t > lastT) {
        bump(postId, 'watch_ms', Math.round((t - lastT) * 1000));
      }
      lastT = t;
    });
  }

  function observeRoot(root) {
    if (!root || typeof IntersectionObserver === 'undefined') return;
    if (!observer) {
      observer = new IntersectionObserver(onIntersect, { threshold: [0, 0.25, 0.5, 0.75, 1], rootMargin: '0px' });
      setInterval(tickReadTime, 1000);
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') flush();
      });
      window.addEventListener('pagehide', () => flush());
    }

    root.querySelectorAll('.social-post[data-post-id]').forEach((el) => {
      if (el.dataset.frObserved) return;
      el.dataset.frObserved = '1';
      observer.observe(el);
      el.querySelectorAll('video').forEach(bindVideo);
    });
  }

  function recordClick(postId) {
    bump(postId, 'click', 1);
    flush();
  }

  function recordDmShare(postId, toUserId) {
    const token = localStorage.getItem('token') || localStorage.getItem('authToken');
    if (!token || !postId || !toUserId) return;
    const url = (global.MF_API_BASE || './api') + '/post_signals.php';
    fetch(url, {
      method: 'POST',
      headers: authHeaders(),
      body: JSON.stringify({ action: 'dm_share', post_id: postId, to_user_id: toUserId }),
    }).catch(() => {});
  }

  global.MF_FEED_RANKING = {
    observe: observeRoot,
    recordClick,
    recordDmShare,
    flush,
  };
})(typeof window !== 'undefined' ? window : globalThis);
