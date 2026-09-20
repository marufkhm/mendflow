/**
 * Mendflow mobile — bottom nav, swipe gestures.
 * Service Worker и push отключены (конфликтовали с API на мобильных).
 */
(function () {
  'use strict';

  const PROFILE_TABS = ['posts', 'friends', 'projects', 'subscriptions', 'reposts'];

  function $(id) {
    return document.getElementById(id);
  }

  function isMobile() {
    return window.matchMedia('(max-width: 768px)').matches;
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

  function syncBottomNav(viewId) {
    document.querySelectorAll('.mobile-nav-item[data-view-target]').forEach((btn) => {
      btn.classList.toggle('is-active', btn.dataset.viewTarget === viewId);
    });
    const profileBtn = document.querySelector('.mobile-nav-item[data-mf-nav="profile"]');
    if (profileBtn) {
      profileBtn.classList.toggle('is-active', viewId === 'clientProfileView');
    }
  }

  function setAppChrome(active) {
    document.body.classList.toggle('mf-app-active', active);
    const nav = $('mobileBottomNav');
    if (nav) nav.classList.toggle('is-hidden', !active);
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
        if (!isMobile()) return;
        if (!$('chatsView')?.classList.contains('has-active-chat')) return;
        $('chatMobileBackBtn')?.click();
      },
    });

    const profileRoot = $('clientProfileView') || document.querySelector('.upf-root');
    bindSwipe(profileRoot, {
      onSwipeLeft() {
        if (!isMobile()) return;
        if (!$('clientProfileView')?.classList.contains('app-view-active')) return;
        switchProfileTab(1);
      },
      onSwipeRight() {
        if (!isMobile()) return;
        if (!$('clientProfileView')?.classList.contains('app-view-active')) return;
        switchProfileTab(-1);
      },
    });
  }

  function attachBottomNav() {
    const nav = $('mobileBottomNav');
    if (!nav) return;

    nav.querySelectorAll('[data-view-target]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const target = btn.dataset.viewTarget;
        if (!target) return;
        if (typeof window.showView === 'function') {
          window.showView(target);
        } else {
          document.querySelectorAll('[data-view-target="' + target + '"]').forEach((b) => b.click());
        }
        syncBottomNav(target);
      });
    });

    nav.querySelector('[data-mf-nav="profile"]')?.addEventListener('click', () => {
      $('userPill')?.click();
      syncBottomNav('clientProfileView');
    });

    const observer = new MutationObserver(() => {
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
      if (active) {
        const view = document.querySelector('.app-view.app-view-active');
        if (view) syncBottomNav(view.id);
      }
    };

    update();
    new MutationObserver(update).observe(shell, { attributes: true, attributeFilter: ['class'] });
  }

  async function init() {
    await purgeLegacyServiceWorkers();
    attachBottomNav();
    attachSwipeGestures();
    watchAppShell();
  }

  window.MF_PWA = {
    init,
    syncBottomNav,
    PROFILE_TABS,
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
