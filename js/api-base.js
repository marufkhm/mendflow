/**
 * Mendflow API base — нормализация URL и проверка ping.
 * Подключать в <head> до pwa.js и основного приложения.
 */
(function () {
  'use strict';

  function normalizeAppDirectory() {
    const loc = window.location;
    const u = new URL(loc.href);
    let p = u.pathname;

    if (p.endsWith('/')) {
      // ok
    } else {
      const last = p.split('/').pop() || '';
      if (/\.[a-z0-9]+$/i.test(last)) {
        p = p.replace(/\/[^/]+$/, '/');
      } else {
        p = p + '/';
      }
    }

    if (p !== u.pathname) {
      history.replaceState(null, '', p + loc.search + loc.hash);
      u.pathname = p;
    }

    return u.origin + u.pathname;
  }

  function buildApiBase(dirUrl) {
    return new URL('api/', dirUrl.endsWith('/') ? dirUrl : dirUrl + '/').href.replace(/\/$/, '');
  }

  function apiBaseCandidates(dirUrl) {
    const primary = buildApiBase(dirUrl);
    const list = [primary];

    const path = new URL(dirUrl).pathname.replace(/\/$/, '');
    if (path) list.push(window.location.origin + path + '/api');

    list.push(window.location.origin + '/api');

    const legacy = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '') + '/api';
    list.push(legacy);

    return [...new Set(list.map((s) => s.replace(/\/$/, '')))];
  }

  async function probeApiBase(base) {
    const url = base.replace(/\/$/, '') + '/ping.php?_=' + Date.now();
    const res = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
    const raw = await res.text();
    const data = JSON.parse(raw);
    return res.ok && data && data.ok === true;
  }

  window.__MF_DIR__ = normalizeAppDirectory();
  window.API_BASE = buildApiBase(window.__MF_DIR__);
  window.__MF_APP_BASE__ = new URL(window.__MF_DIR__).pathname.replace(/\/$/, '');

  /** Путь /uploads/... из строки URL или относительного пути. */
  function mfExtractUploadsPath(url) {
    if (!url || typeof url !== 'string') return '';
    const m = String(url).trim().match(/\/uploads\/[^/?#\s]+/i);
    if (m) return m[0];
    const rel = String(url).trim().replace(/^\.\//, '');
    if (/^uploads\/[^/?#\s]+/i.test(rel)) return '/' + rel;
    return '';
  }

  /** Имя файла из /uploads/... или api/media.php?f=... */
  function mfUploadsFilename(url) {
    if (!url || typeof url !== 'string') return '';
    const s = String(url).trim();
    if (/^(img|video|avatar|cover)_[A-Za-z0-9._-]+\.(jpe?g|png|gif|webp|mp4|webm|mov)$/i.test(s)) {
      return s;
    }
    const uploadsPath = mfExtractUploadsPath(url);
    if (uploadsPath) return uploadsPath.split('/').pop();
    const m = String(url).trim().match(/(?:^|[?&])f=([^&]+)/i);
    if (m) {
      try { return decodeURIComponent(m[1]); } catch (_) { return m[1]; }
    }
    try {
      const parsed = new URL(url, window.location.origin);
      if (parsed.pathname.includes('/media.php')) {
        const q = parsed.searchParams.get('f');
        if (q) return q;
      }
      const fromPath = mfExtractUploadsPath(parsed.pathname);
      if (fromPath) return fromPath.split('/').pop();
    } catch (_) {}
    return '';
  }

  window.mfUploadsFilename = mfUploadsFilename;

  /** URL отдачи файла через api/media.php (обход блокировок uploads/.htaccess). */
  function mfMediaServeUrl(filename) {
    if (!filename) return '';
    const root = (window.__MF_APP_BASE__ || '').replace(/\/$/, '');
    return `${root}/api/media.php?f=${encodeURIComponent(filename)}`;
  }

  /** Прямой путь к файлу в uploads/ (запасной вариант). */
  function mfUploadsDirectUrl(filename) {
    if (!filename) return '';
    const root = (window.__MF_APP_BASE__ || '').replace(/\/$/, '');
    return `${root}/uploads/${filename}`;
  }

  /** Основной и запасной URL для изображения. */
  window.mfMediaUrls = function mfMediaUrls(url) {
    if (!url || typeof url !== 'string') return { primary: '', fallback: '' };
    url = url.trim();
    if (!url) return { primary: '', fallback: '' };
    if (/^(data:|blob:)/i.test(url)) return { primary: url, fallback: '' };

    const filename = mfUploadsFilename(url);
    if (filename) {
      return {
        primary: mfMediaServeUrl(filename),
        fallback: mfUploadsDirectUrl(filename),
      };
    }

    return { primary: window.resolveMediaUrl(url), fallback: '' };
  };

  window.mfImgFallback = function mfImgFallback(img) {
    if (!img || img.dataset.mfRetried === '1') {
      const fig = img?.closest?.('.mf-md-figure');
      if (fig && !fig.classList.contains('is-broken')) {
        fig.classList.add('is-broken');
        const alt = img?.alt || 'Изображение';
        fig.innerHTML = `<div class="mf-md-figure-ph">${alt}</div>`;
      }
      return;
    }
    const fb = img.dataset.fallback;
    if (fb && fb !== img.src) {
      img.dataset.mfRetried = '1';
      img.src = fb;
      return;
    }
    img.dataset.mfRetried = '1';
    const fig = img.closest('.mf-md-figure');
    if (fig) {
      fig.classList.add('is-broken');
      fig.innerHTML = `<div class="mf-md-figure-ph">${img.alt || 'Изображение'}</div>`;
    }
  };

  window.resolveMediaUrl = function resolveMediaUrl(url) {
    if (!url || typeof url !== 'string') return '';
    url = url.trim();
    if (!url) return '';
    if (/^(data:|blob:)/i.test(url)) return url;

    url = url.replace(/\/api\/uploads\//g, '/uploads/');

    const filename = mfUploadsFilename(url);
    if (filename) {
      return mfMediaServeUrl(filename);
    }

    let appOrigin;
    let appBasePath = '';
    try {
      const base = new URL(window.__MF_DIR__ || '/', window.location.origin);
      appOrigin = base.origin;
      appBasePath = base.pathname.replace(/\/$/, '');
    } catch (_) {
      appOrigin = window.location.origin;
      appBasePath = window.__MF_APP_BASE__ || '';
    }

    if (url.startsWith('/') && !url.startsWith('//')) {
      const path = url.startsWith(appBasePath + '/') ? url : (appBasePath + url);
      if (window.location.protocol === 'https:') {
        return appOrigin.replace(/^http:/, 'https:') + path;
      }
      return appOrigin + path;
    }

    if (!/^https?:\/\//i.test(url)) {
      const rel = url.replace(/^\.\//, '');
      return appOrigin + appBasePath + '/' + rel;
    }

    if (window.location.protocol === 'https:' && url.startsWith('http://')) {
      url = 'https://' + url.slice(7);
    }

    return url;
  };

  window.mfEnsureApi = async function mfEnsureApi() {
    if (window.__MF_API_RESOLVED) return window.API_BASE;

    const candidates = apiBaseCandidates(window.__MF_DIR__);
    for (const base of candidates) {
      try {
        if (await probeApiBase(base)) {
          window.API_BASE = base.replace(/\/$/, '');
          window.__MF_API_RESOLVED = true;
          return window.API_BASE;
        }
      } catch (_) {}
    }

    window.__MF_API_RESOLVED = true;
    return window.API_BASE;
  };

  /** Принудительная верификация email после register* — работает даже со старым index.html на сервере */
  function installRegisterVerificationPatch() {
    if (window.__MF_REGISTER_VERIFY_PATCH) return;

    const isRegisterEndpoint = (endpoint) => {
      const ep = String(endpoint || '').split('?')[0];
      return /^\/register(?:-company|-university)?\.php$/i.test(ep);
    };

    const patchApi = () => {
      if (typeof window.api !== 'function' || window.__MF_REGISTER_VERIFY_PATCH) return !!window.__MF_REGISTER_VERIFY_PATCH;
      const origApi = window.api;
      window.api = async function mfApiWithRegisterVerify(endpoint, opts = {}) {
        const data = await origApi.call(this, endpoint, opts);
        if (!data || typeof data !== 'object' || !isRegisterEndpoint(endpoint)) return data;

        const needsVerify = data.requires_verification
          || data.email_verified === false
          || (data.success && data.email_verified !== true);

        if (!needsVerify) return data;

        return {
          ...data,
          success: true,
          requires_verification: true,
          email_verified: false,
          token: undefined,
          user: undefined,
        };
      };
      window.__MF_REGISTER_VERIFY_PATCH = true;
      return true;
    };

    if (patchApi()) return;

    const timer = setInterval(() => {
      if (patchApi()) clearInterval(timer);
    }, 10);

    document.addEventListener('DOMContentLoaded', patchApi, { once: true });
  }

  installRegisterVerificationPatch();
})();
