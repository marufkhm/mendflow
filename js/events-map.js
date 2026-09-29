/**
 * Mendflow — карта мероприятий (Leaflet + геокодирование через /api/events.php)
 */
(function () {
  'use strict';

  const TILE_URL = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
  const TILE_ATTR = '&copy; OpenStreetMap &copy; CARTO';
  const DEFAULT_CENTER = [43.238949, 76.889709]; // Алматы
  const DEFAULT_ZOOM = 11;

  const state = {
    exploreMap: null,
    exploreLayer: null,
    exploreMarkers: [],
    exploreReady: false,
    popupBound: false,
    exploreCity: '',
    cityCenterCache: {},
    createMap: null,
    createMarker: null,
    createReady: false,
    locationVerified: false,
    geocodeTimer: null,
    geocodeBusy: false,
    lastSuggest: [],
    events: [],
    activeFormat: '',
  };

  function esc(s) {
    if (typeof window.esc === 'function') return window.esc(s);
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function api(path) {
    if (typeof window.api === 'function') return window.api(path);
    throw new Error('API недоступен');
  }

  function waitLeaflet() {
    return new Promise((resolve, reject) => {
      if (window.L) return resolve(window.L);
      let n = 0;
      const t = setInterval(() => {
        if (window.L) { clearInterval(t); resolve(window.L); }
        else if (++n > 80) { clearInterval(t); reject(new Error('Leaflet не загрузился')); }
      }, 50);
    });
  }

  function cityValue() {
    return document.getElementById('eventCityField')?.value?.trim() || '';
  }

  function streetValue() {
    return document.getElementById('eventLocationField')?.value?.trim() || '';
  }

  function makePulseIcon(L, label) {
    return L.divIcon({
      className: 'ev-map-marker-wrap',
      html: `<div class="ev-map-marker"><span class="ev-map-marker-ring"></span><span class="ev-map-marker-core"></span><span class="ev-map-marker-label">${esc(label || '')}</span></div>`,
      iconSize: [36, 36],
      iconAnchor: [18, 18],
      popupAnchor: [0, -20],
    });
  }

  function makePinIcon(L) {
    return L.divIcon({
      className: 'ev-map-pin-wrap',
      html: '<div class="ev-map-pin"><span class="ev-map-pin-beam"></span><span class="ev-map-pin-dot"></span></div>',
      iconSize: [32, 32],
      iconAnchor: [16, 28],
    });
  }

  function baseMapOptions() {
    return { zoomControl: false, attributionControl: true };
  }

  function addFuturisticControls(L, map, container) {
    const wrap = document.createElement('div');
    wrap.className = 'ev-map-controls';
    wrap.innerHTML = `
      <button type="button" class="ev-map-ctrl" data-ev-zoom="in" title="Приблизить">+</button>
      <button type="button" class="ev-map-ctrl" data-ev-zoom="out" title="Отдалить">−</button>
      <button type="button" class="ev-map-ctrl ev-map-ctrl-gps" data-ev-locate title="Моё местоположение">◎</button>
    `;
    container.appendChild(wrap);
    wrap.querySelector('[data-ev-zoom="in"]')?.addEventListener('click', () => map.zoomIn());
    wrap.querySelector('[data-ev-zoom="out"]')?.addEventListener('click', () => map.zoomOut());
    wrap.querySelector('[data-ev-locate]')?.addEventListener('click', () => {
      if (!navigator.geolocation) return;
      navigator.geolocation.getCurrentPosition(
        (pos) => map.flyTo([pos.coords.latitude, pos.coords.longitude], 13, { duration: 0.8 }),
        () => {},
        { enableHighAccuracy: true, timeout: 8000 }
      );
    });
  }

  function offlineEvents(list) {
    const seen = new Set();
    const out = [];
    (list || []).forEach((ev) => {
      if (!ev || ev.event_format !== 'offline') return;
      const lat = parseFloat(ev.latitude);
      const lng = parseFloat(ev.longitude);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
      const key = `${ev.id}:${lat}:${lng}`;
      if (seen.has(key)) return;
      seen.add(key);
      out.push(ev);
    });
    return out;
  }

  function popupHtml(ev) {
    const date = ev.start_datetime || ev.start_iso || '';
    const city = ev.city || 'Офлайн';
    return `
      <div class="ev-map-popup">
        <strong>${esc(ev.title)}</strong>
        <span>${esc(city)}</span>
        <span class="ev-map-popup-meta">${esc(date)} · ${Number(ev.participants_count || 0)} уч.</span>
        <button type="button" class="ev-map-popup-btn" data-ev-open="${esc(ev.id)}">Открыть →</button>
      </div>`;
  }

  async function initExplore() {
    const el = document.getElementById('eventsExploreMap');
    if (!el || state.exploreReady) return;
    const L = await waitLeaflet();
    state.exploreMap = L.map(el, baseMapOptions()).setView(DEFAULT_CENTER, DEFAULT_ZOOM);
    L.tileLayer(TILE_URL, { attribution: TILE_ATTR, subdomains: 'abcd', maxZoom: 19 }).addTo(state.exploreMap);
    state.exploreLayer = L.layerGroup().addTo(state.exploreMap);
    const wrap = el.closest('.ev-map-canvas') || el.parentElement;
    if (wrap) addFuturisticControls(L, state.exploreMap, wrap);
    state.exploreReady = true;
    if (!state.popupBound) {
      document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-ev-open]');
        if (!btn) return;
        const id = btn.dataset.evOpen;
        if (id && typeof window.openEventDetail === 'function') window.openEventDetail(id);
      });
      state.popupBound = true;
    }
    refreshExplore();
    setTimeout(() => state.exploreMap?.invalidateSize(), 120);
  }

  function eventMatchesCity(ev, city) {
    if (!city) return true;
    const c = city.toLowerCase();
    return String(ev?.city || '').toLowerCase().includes(c);
  }

  async function flyExploreToCity(city) {
    if (!state.exploreMap || !city || city.length < 2) return;
    const key = city.toLowerCase();
    let coords = state.cityCenterCache[key];
    if (!coords) {
      try {
        const results = await geocodeSearch(city, '');
        if (results[0]) {
          coords = [results[0].lat, results[0].lng];
          state.cityCenterCache[key] = coords;
        }
      } catch (_) {
        return;
      }
    }
    if (coords) state.exploreMap.flyTo(coords, 11, { duration: 0.75 });
  }

  function refreshExplore() {
    if (!state.exploreReady || !state.exploreMap || !state.exploreLayer) return;
    const L = window.L;
    state.exploreLayer.clearLayers();
    state.exploreMarkers = [];

    const fmt = state.activeFormat;
    const cityFilter = state.exploreCity;
    const countEl = document.getElementById('eventsMapCount');
    const emptyEl = document.getElementById('eventsMapEmpty');

    if (fmt === 'online') {
      if (countEl) countEl.textContent = '0';
      if (emptyEl) {
        emptyEl.classList.remove('is-hidden');
        emptyEl.textContent = 'На карте только офлайн-мероприятия. Переключите фильтр «Все» или «Офлайн».';
      }
      if (cityFilter.length >= 2) flyExploreToCity(cityFilter);
      return;
    }

    let items = offlineEvents(state.events);
    if (cityFilter) items = items.filter((ev) => eventMatchesCity(ev, cityFilter));

    if (countEl) countEl.textContent = String(items.length);
    if (emptyEl) {
      const noInCity = cityFilter && !items.length;
      emptyEl.classList.toggle('is-hidden', items.length > 0);
      emptyEl.textContent = items.length
        ? ''
        : (noInCity
          ? `В ${cityFilter} пока нет офлайн-событий на карте. Карта показывает выбранный город.`
          : 'Офлайн-события с адресом на карте появятся здесь. Создайте мероприятие и укажите точку.');
    }

    const bounds = [];
    items.forEach((ev) => {
      const lat = parseFloat(ev.latitude);
      const lng = parseFloat(ev.longitude);
      const marker = L.marker([lat, lng], { icon: makePulseIcon(L, ev.category || 'EV') });
      marker.bindPopup(popupHtml(ev), { className: 'ev-map-popup-shell', maxWidth: 260 });
      marker.on('click', () => marker.openPopup());
      marker.addTo(state.exploreLayer);
      state.exploreMarkers.push(marker);
      bounds.push([lat, lng]);
    });

    if (bounds.length === 1) state.exploreMap.setView(bounds[0], 13);
    else if (bounds.length > 1) state.exploreMap.fitBounds(bounds, { padding: [48, 48], maxZoom: 14 });
    else if (cityFilter.length >= 2) flyExploreToCity(cityFilter);
    else state.exploreMap.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
  }

  function setExploreEvents(events, activeFormat, city) {
    state.events = events || [];
    state.activeFormat = activeFormat || '';
    state.exploreCity = (city || '').trim();
    refreshExplore();
  }

  function onExploreViewShow() {
    initExplore().then(() => {
      state.exploreMap?.invalidateSize();
      refreshExplore();
    }).catch(() => {});
  }

  /* ── Create form map + geocode ─────────────────────────────── */

  function setAddressStatus(msg, ok) {
    const el = document.getElementById('eventAddressStatus');
    if (!el) return;
    el.textContent = msg;
    el.classList.toggle('is-ok', ok === true);
    el.classList.toggle('is-error', ok === false);
  }

  function applyLocation(loc, opts = {}) {
    if (!loc) return;
    const latIn = document.getElementById('eventLatInput');
    const lngIn = document.getElementById('eventLngInput');
    const cityIn = document.getElementById('eventCityField');
    const locIn = document.getElementById('eventLocationField');

    if (latIn) latIn.value = String(loc.lat);
    if (lngIn) lngIn.value = String(loc.lng);

    if (!opts.keepFields) {
      if (cityIn && loc.city) cityIn.value = loc.city;
      if (locIn && loc.location) locIn.value = loc.location;
    }

    state.locationVerified = true;
    setAddressStatus('✓ Адрес найден на карте', true);
    placeCreateMarker(loc.lat, loc.lng);
  }

  async function geocodeSearch(city, street) {
    const params = new URLSearchParams({ action: 'geocode' });
    if (city) params.set('city', city);
    if (street) params.set('street', street);
    params.set('q', [street, city, 'Kazakhstan'].filter(Boolean).join(', '));
    const data = await api('/events.php?' + params.toString());
    return data.results || [];
  }

  async function reverseGeocode(lat, lng) {
    const data = await api('/events.php?action=reverse&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng));
    return data.result || null;
  }

  function renderSuggestions(items) {
    const box = document.getElementById('eventAddressSuggestions');
    if (!box) return;
    state.lastSuggest = items;
    if (!items.length) {
      box.innerHTML = '<p class="ev-addr-empty">Адрес не найден — уточните город и улицу или кликните на карте</p>';
      box.classList.add('is-open');
      return;
    }
    box.innerHTML = items.map((item, i) =>
      `<button type="button" class="ev-addr-item" data-addr-idx="${i}">${esc(item.display_name)}</button>`
    ).join('');
    box.classList.add('is-open');
    box.querySelectorAll('[data-addr-idx]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const item = items[+btn.dataset.addrIdx];
        if (item) applyLocation(item, { keepFields: true });
        box.classList.remove('is-open');
      });
    });
  }

  function placeCreateMarker(lat, lng) {
    if (!state.createMap || !window.L) return;
    const L = window.L;
    if (state.createMarker) state.createMap.removeLayer(state.createMarker);
    state.createMarker = L.marker([lat, lng], { icon: makePinIcon(L), draggable: true }).addTo(state.createMap);
    state.createMarker.on('dragend', async () => {
      const pos = state.createMarker.getLatLng();
      state.locationVerified = false;
      setAddressStatus('Обновляем точку…', null);
      try {
        const result = await reverseGeocode(pos.lat, pos.lng);
        if (result) {
          document.getElementById('eventLatInput').value = String(result.lat);
          document.getElementById('eventLngInput').value = String(result.lng);
          state.locationVerified = true;
          setAddressStatus('✓ Точка на карте обновлена', true);
        } else {
          document.getElementById('eventLatInput').value = String(pos.lat);
          document.getElementById('eventLngInput').value = String(pos.lng);
          state.locationVerified = true;
          setAddressStatus('✓ Координаты сохранены (уточните адрес при необходимости)', true);
        }
      } catch (e) {
        setAddressStatus(e.message || 'Ошибка геокодирования', false);
      }
    });
    state.createMap.flyTo([lat, lng], Math.max(state.createMap.getZoom(), 15), { duration: 0.55 });
    setTimeout(() => state.createMap?.invalidateSize(), 80);
  }

  async function runGeocode(manual) {
    const city = cityValue();
    const street = streetValue();
    if (city.length < 2) {
      if (manual) setAddressStatus('Сначала укажите город', false);
      return;
    }
    if (street.length < 2) {
      if (manual) setAddressStatus('Укажите адрес (улица, дом)', false);
      return;
    }
    if (state.geocodeBusy) return;
    state.geocodeBusy = true;
    state.locationVerified = false;
    setAddressStatus('Ищем на карте…', null);
    const btn = document.getElementById('eventGeocodeBtn');
    if (btn) btn.disabled = true;

    try {
      await initCreateMap();
      const results = await geocodeSearch(city, street);
      if (!results.length) {
        setAddressStatus('Адрес не найден — проверьте город и адрес', false);
        renderSuggestions([]);
        return;
      }
      applyLocation(results[0], { keepFields: true });
      if (results.length > 1) renderSuggestions(results);
      else document.getElementById('eventAddressSuggestions')?.classList.remove('is-open');
    } catch (e) {
      setAddressStatus(e.message || 'Ошибка поиска адреса', false);
    } finally {
      state.geocodeBusy = false;
      if (btn) btn.disabled = false;
    }
  }

  function scheduleGeocode() {
    clearTimeout(state.geocodeTimer);
    state.locationVerified = false;
    const city = cityValue();
    const street = streetValue();
    if (city.length < 2 || street.length < 3) {
      setAddressStatus('Введите город и адрес — точка появится на карте автоматически', null);
      return;
    }
    setAddressStatus('Ищем на карте…', null);
    state.geocodeTimer = setTimeout(() => runGeocode(false), 700);
  }

  async function initCreateMap() {
    const el = document.getElementById('eventCreateMap');
    if (!el) return;
    const L = await waitLeaflet();
    if (!state.createMap) {
      state.createMap = L.map(el, { ...baseMapOptions(), scrollWheelZoom: true }).setView(DEFAULT_CENTER, 12);
      L.tileLayer(TILE_URL, { attribution: TILE_ATTR, subdomains: 'abcd', maxZoom: 19 }).addTo(state.createMap);
      state.createMap.on('click', async (e) => {
        state.locationVerified = false;
        setAddressStatus('Определяем адрес…', null);
        try {
          const result = await reverseGeocode(e.latlng.lat, e.latlng.lng);
          if (result) {
            const cityIn = document.getElementById('eventCityField');
            const locIn = document.getElementById('eventLocationField');
            if (cityIn && !cityIn.value.trim() && result.city) cityIn.value = result.city;
            if (locIn && !locIn.value.trim()) locIn.value = result.location || result.display_name || '';
            applyLocation(result, { keepFields: true });
          } else {
            document.getElementById('eventLatInput').value = String(e.latlng.lat);
            document.getElementById('eventLngInput').value = String(e.latlng.lng);
            placeCreateMarker(e.latlng.lat, e.latlng.lng);
            state.locationVerified = true;
            setAddressStatus('✓ Точка отмечена на карте', true);
          }
        } catch (err) {
          setAddressStatus(err.message || 'Ошибка', false);
        }
      });
      state.createReady = true;
    }
    setTimeout(() => state.createMap?.invalidateSize(), 50);
    setTimeout(() => state.createMap?.invalidateSize(), 300);
  }

  function bindCreateForm() {
    const cityIn = document.getElementById('eventCityField');
    const streetIn = document.getElementById('eventLocationField');
    const suggest = document.getElementById('eventAddressSuggestions');

    cityIn?.addEventListener('input', scheduleGeocode);
    streetIn?.addEventListener('input', scheduleGeocode);
    cityIn?.addEventListener('blur', () => { if (streetValue().length >= 2) scheduleGeocode(); });
    streetIn?.addEventListener('blur', () => {
      setTimeout(() => suggest?.classList.remove('is-open'), 200);
      if (cityValue().length >= 2 && streetValue().length >= 2) runGeocode(false);
    });

    document.getElementById('eventGeocodeBtn')?.addEventListener('click', () => runGeocode(true));
    document.getElementById('eventFormatInput')?.addEventListener('change', syncFormatBlocks);
  }

  function syncFormatBlocks() {
    const fmt = document.getElementById('eventFormatInput')?.value || 'offline';
    const offline = document.getElementById('eventOfflineBlock');
    const online = document.getElementById('eventOnlineBlock');
    offline?.classList.toggle('is-hidden', fmt === 'online');
    online?.classList.toggle('is-hidden', fmt !== 'online');
    if (fmt === 'offline') initCreateMap();
  }

  function resetCreate() {
    state.locationVerified = false;
    clearTimeout(state.geocodeTimer);
    const latIn = document.getElementById('eventLatInput');
    const lngIn = document.getElementById('eventLngInput');
    if (latIn) latIn.value = '';
    if (lngIn) lngIn.value = '';
    const suggest = document.getElementById('eventAddressSuggestions');
    if (suggest) { suggest.innerHTML = ''; suggest.classList.remove('is-open'); }
    setAddressStatus('Введите город и адрес — точка появится на карте автоматически', null);
    if (state.createMarker && state.createMap) {
      state.createMap.removeLayer(state.createMarker);
      state.createMarker = null;
    }
    syncFormatBlocks();
  }

  function validateCreate() {
    const fmt = document.getElementById('eventFormatInput')?.value || 'offline';
    if (fmt !== 'offline') return { ok: true };
    const lat = document.getElementById('eventLatInput')?.value;
    const lng = document.getElementById('eventLngInput')?.value;
    const city = cityValue();
    const street = streetValue();
    if (!city || !street) {
      return { ok: false, message: 'Укажите город и адрес офлайн-мероприятия' };
    }
    if (!lat || !lng || !state.locationVerified) {
      return { ok: false, message: 'Нажмите «Найти на карте» или дождитесь появления маркера' };
    }
    return { ok: true };
  }

  function onCreateModalOpen() {
    resetCreate();
    setTimeout(() => initCreateMap(), 250);
  }

  window.MF_EVENTS_MAP = {
    onExploreViewShow,
    setExploreEvents,
    onCreateModalOpen,
    resetCreate,
    validateCreate,
    syncFormatBlocks,
    bindCreateForm,
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindCreateForm);
  } else {
    bindCreateForm();
  }
})();
