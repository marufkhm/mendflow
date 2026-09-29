/**
 * Mendflow mobile — оверлеи (шторки, модалки) и кнопка «Назад».
 * На ширине ≤ 1024px каждый открытый оверлей кладёт запись в history,
 * поэтому системный «Назад» / свайп закрывает оверлей, а не уходит из раздела.
 * На десктопе openOverlay ничего не делает.
 */
(function () {
  'use strict';

  const mq = window.matchMedia ? window.matchMedia('(max-width: 1024px)') : null;
  const stack = [];
  let swallow = 0;
  let pendingResolvers = [];

  function isMobile() {
    return !!(mq && mq.matches);
  }

  function isOpen(id) {
    return stack.some((o) => o.id === id);
  }

  function runOnClose(entry) {
    try { entry.onClose?.(); } catch (err) { console.error('[mf-mobile] overlay onClose', err); }
  }

  function flushResolvers() {
    const list = pendingResolvers;
    pendingResolvers = [];
    list.forEach((fn) => fn(true));
  }

  function openOverlay(id, onClose) {
    if (!id || !isMobile() || isOpen(id)) return false;
    try {
      const base = history.state && typeof history.state === 'object' ? history.state : {};
      history.pushState({ ...base, mfOverlay: id }, '', location.href);
    } catch (_) {
      return false;
    }
    stack.push({ id, onClose });
    return true;
  }

  /**
   * Закрыть оверлей из UI (крестик, фон, Esc).
   * Возвращает Promise, который резолвится, когда history откатилась,
   * — после него безопасно делать showView / pushState.
   */
  function closeOverlay(id) {
    const idx = stack.findIndex((o) => o.id === id);
    if (idx < 0) return Promise.resolve(false);
    const removed = stack.splice(idx);
    removed.slice(1).reverse().forEach(runOnClose);

    const topId = removed[removed.length - 1].id;
    if (history.state?.mfOverlay !== topId) return Promise.resolve(true);

    return new Promise((resolve) => {
      swallow += 1;
      pendingResolvers.push(resolve);
      setTimeout(() => {
        if (!pendingResolvers.includes(resolve)) return;
        pendingResolvers = pendingResolvers.filter((fn) => fn !== resolve);
        swallow = Math.max(0, swallow - 1);
        resolve(true);
      }, 400);
      history.go(-removed.length);
    });
  }

  /** Вызывается первым в popstate index.html. true — событие обработано. */
  function handlePopState(e) {
    if (swallow > 0) {
      swallow -= 1;
      flushResolvers();
      return true;
    }
    if (!stack.length) return false;
    const target = e?.state?.mfOverlay || null;
    while (stack.length && stack[stack.length - 1].id !== target) {
      runOnClose(stack.pop());
    }
    return true;
  }

  function topOverlay() {
    return stack.length ? stack[stack.length - 1].id : null;
  }

  window.MF_MOBILE = {
    isMobile,
    isOpen,
    openOverlay,
    closeOverlay,
    handlePopState,
    topOverlay,
  };
})();
