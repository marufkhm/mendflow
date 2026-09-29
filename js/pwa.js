/**
 * Mendflow mobile — bottom nav, swipe gestures.
 * Service Worker и push отключены (конфликтовали с API на мобильных).
 */
(function () {
  'use strict';

  const PROFILE_TABS = ['posts', 'friends', 'projects', 'subscriptions', 'reposts'];
  const MORE_VIEWS = new Set([
    'internshipsView',
    'coursesView',
    'eventsView',
    'connectView',
    'ideasView',
  ]);

  let moreMenuOpen = false;

  function $(id) {
    return document.getElementById(id);
  }

  async function purgeLegacyServiceWorkers() {
    if (!('serviceWorker' in navigator)) return;
    try {
      const regs = await navigator.serviceWorker.getRegistrations();
      await Promise.all(regs.map((r) => r.unregister()));
      if ('caches' in window) {
        const keys = await caches.keys();
        await Promise.all(keys.filter((k) => k.startsWith('mendflow')).map((k) => caches.delete(k)));
      }
    } catch (_) {}
  }

  function hideMoreMenuUi() {
    moreMenuOpen = false;
    document.body.classList.remove('mf-more-open');
    $('mobileMoreBackdrop')?.classList.add('is-hidden');
    $('mobileMoreSheet')?.classList.add('is-hidden');
    $('mobileMoreBackdrop')?.setAttribute('aria-hidden', 'true');
    const btn = $('mobileNavMoreBtn');
    if (btn) btn.setAttribute('aria-expanded', 'false');
    const active = document.querySelector('.app-view.app-view-active');
    if (active) syncBottomNav(active.id);
  }

  function closeMoreMenu() {
    const wasOpen = moreMenuOpen;
    hideMoreMenuUi();
    if (!wasOpen || !window.MF_MOBILE) return Promise.resolve(false);
    return window.MF_MOBILE.closeOverlay('more');
  }

  function openMoreMenu() {
    if (moreMenuOpen) return;
    window.MF_MOBILE?.openOverlay('more', hideMoreMenuUi);
    moreMenuOpen = true;
    document.body.classList.add('mf-more-open');
    $('mobileMoreBackdrop')?.classList.remove('is-hidden');
    $('mobileMoreSheet')?.classList.remove('is-hidden');
    $('mobileMoreBackdrop')?.setAttribute('aria-hidden', 'false');
    const btn = $('mobileNavMoreBtn');
    if (btn) {
      btn.setAttribute('aria-expanded', 'true');
      btn.classList.add('is-active');
    }
  }

  function toggleMoreMenu(e) {
    e?.preventDefault?.();
    e?.stopPropagation?.();
    if (moreMenuOpen) closeMoreMenu();
    else openMoreMenu();
  }

  function syncBottomNav(viewId) {
    document.querySelectorAll('.mobile-nav-item[data-view-target]').forEach((btn) => {
      btn.classList.toggle('is-active', btn.dataset.viewTarget === viewId);
    });
    const moreBtn = $('mobileNavMoreBtn');
    if (moreBtn) {
      moreBtn.classList.toggle('is-active', MORE_VIEWS.has(viewId) || moreMenuOpen);
    }
    const profileBtn = document.querySelector('.mobile-nav-item[data-mf-nav="profile"]');
    if (profileBtn) {
      profileBtn.classList.toggle('is-active', viewId === 'clientProfileView');
    }
    document.querySelectorAll('.mobile-more-item[data-view-target]').forEach((btn) => {
      btn.classList.toggle('is-active', btn.dataset.viewTarget === viewId);
    });
  }

  function navigateToView(target) {
    if (target) syncBottomNav(target);
  }

  let replayingClick = false;

  /**
   * Пока шторка «Ещё» открыта, переход откладывается до отката её записи
   * в history — иначе pushState раздела окажется перед history.back().
   */
  function interceptNavWhileMoreOpen(e) {
    if (replayingClick || !moreMenuOpen) return;
    const btn = e.target.closest?.('#mobileBottomNav [data-view-target], #mobileBottomNav [data-mf-nav], #mobileMoreSheet [data-view-target]');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    closeMoreMenu().then(() => {
      replayingClick = true;
      try { btn.click(); } finally { replayingClick = false; }
    });
  }

  function setAppChrome(active) {
    document.body.classList.toggle('mf-app-active', active);
    const nav = $('mobileBottomNav');
    if (nav) nav.classList.toggle('is-hidden', !active);
    if (!active) closeMoreMenu();
  }

  function bindSwipe(el, handlers) {
    if (!el) return;
    let sx = 0;
    let sy = 0;
    let st = 0;

    el.addEventListener('touchstart', (e) => {
      if (e.touches.length !== 1) return;
      sx = e.touches[0].clientX;
      sy = e.touches[0].clientY;
      st = Date.now();
    }, { passive: true });

    el.addEventListener('touchend', (e) => {
      const t = e.changedTouches[0];
      const dx = t.clientX - sx;
      const dy = t.clientY - sy;
      const dt = Date.now() - st;
      if (dt > 600 || Math.abs(dx) < 56 || Math.abs(dx) < Math.abs(dy) * 1.15) return;
      if (dx > 0) handlers.onSwipeRight?.();
      else handlers.onSwipeLeft?.();
    }, { passive: true });
  }

  function switchProfileTab(direction) {
    const tabs = [...document.querySelectorAll('[data-profile-tab]')];
    const idx = tabs.findIndex((t) => t.classList.contains('is-active'));
    if (idx < 0) return;
    const next = tabs[idx + (direction > 0 ? 1 : -1)];
    if (next) next.click();
  }

  function attachSwipeGestures() {
    bindSwipe($('chatActiveThread') || document.querySelector('.msg-thread-shell'), {
      onSwipeRight() {
        if (!$('chatsView')?.classList.contains('has-active-chat')) return;
        $('chatMobileBackBtn')?.click();
      },
    });

    const profileRoot = $('clientProfileView') || document.querySelector('.upf-root');
    bindSwipe(profileRoot, {
      onSwipeLeft() {
        if (!$('clientProfileView')?.classList.contains('app-view-active')) return;
        switchProfileTab(1);
      },
      onSwipeRight() {
        if (!$('clientProfileView')?.classList.contains('app-view-active')) return;
        switchProfileTab(-1);
      },
    });
  }

  function attachMoreMenu() {
    const moreBtn = $('mobileNavMoreBtn');
    if (moreBtn) {
      moreBtn.addEventListener('click', toggleMoreMenu);
    }

    $('mobileMoreClose')?.addEventListener('click', () => closeMoreMenu());
    $('mobileMoreBackdrop')?.addEventListener('click', () => closeMoreMenu());

    document.addEventListener('click', interceptNavWhileMoreOpen, true);

    document.querySelectorAll('.mobile-more-item[data-view-target]').forEach((btn) => {
      btn.addEventListener('click', () => navigateToView(btn.dataset.viewTarget));
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && moreMenuOpen) closeMoreMenu();
    });
  }

  function attachBottomNav() {
    const nav = $('mobileBottomNav');
    if (!nav) return;

    nav.querySelectorAll('[data-view-target]').forEach((btn) => {
      btn.addEventListener('click', () => navigateToView(btn.dataset.viewTarget));
    });

    nav.querySelector('[data-mf-nav="profile"]')?.addEventListener('click', () => {
      $('userPill')?.click();
      syncBottomNav('clientProfileView');
    });

    const observer = new MutationObserver(() => {
      if (moreMenuOpen) return;
      const active = document.querySelector('.app-view.app-view-active');
      if (active) syncBottomNav(active.id);
    });
    const content = document.querySelector('.app-content');
    if (content) observer.observe(content, { attributes: true, subtree: true, attributeFilter: ['class'] });
  }

  function watchAppShell() {
    const shell = $('appShell');
    if (!shell) return;

    const update = () => {
      const active = !shell.classList.contains('is-hidden');
      setAppChrome(active);
      if (active && !moreMenuOpen) {
        const view = document.querySelector('.app-view.app-view-active');
        if (view) syncBottomNav(view.id);
      }
    };

    update();
    new MutationObserver(update).observe(shell, { attributes: true, attributeFilter: ['class'] });
  }

  async function init() {
    await purgeLegacyServiceWorkers();
    attachMoreMenu();
    attachBottomNav();
    attachSwipeGestures();
    watchAppShell();
  }

  window.MF_PWA = {
    init,
    syncBottomNav,
    closeMoreMenu,
    openMoreMenu,
    toggleMoreMenu,
    PROFILE_TABS,
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
