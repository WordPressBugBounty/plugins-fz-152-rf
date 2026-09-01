(function(){
  if (window.__F152_JS_INIT__) return; 
  window.__F152_JS_INIT__ = true;

  var CFG = window.F152 || {};

  function setCookie(name, value, days, domain, sameSite, secure) {
    var d   = days ? new Date(Date.now() + days*24*60*60*1000) : null;
    var str = name + "=" + encodeURIComponent(value) + "; path=/";
    if (d)          str += "; expires=" + d.toUTCString();
    if (domain)     str += "; domain=" + domain;
    if (sameSite)   str += "; SameSite=" + sameSite;
    if (secure)     str += "; Secure";
    document.cookie = str;
  }

  function readCookie(name) {
    var m = document.cookie.match(new RegExp("(?:^|; )" + name.replace(/([.$?*|{}()[\]\\/+^])/g, "\\$1") + "=([^;]*)"));
    return m ? decodeURIComponent(m[1]) : null;
  }

  function delCookie(name, domain) {
    document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/"
      + (domain ? "; domain=" + domain : "");
  }

  var COOKIE_NAME  = (CFG.cookie && CFG.cookie.name) || 'f152_consent';
  var COOKIE_DAYS  = (CFG.cookie && CFG.cookie.days) || 365;
  var COOKIE_DOM   = (CFG.cookie && CFG.cookie.domain) || "";
  var COOKIE_SS    = (CFG.cookie && CFG.cookie.sameSite) || "Lax";
  var COOKIE_SEC   = !!(CFG.cookie && CFG.cookie.secure);
  var COOKIE_VER   = (CFG.cookie && Object.prototype.hasOwnProperty.call(CFG.cookie, 'version')) ? String(CFG.cookie.version) : '';
  var COOKIE_LEGACY_VER = (CFG.cookie && Object.prototype.hasOwnProperty.call(CFG.cookie, 'legacyVersion')) ? String(CFG.cookie.legacyVersion) : COOKIE_VER;

  function parseConsent() {
	var raw = readCookie(COOKIE_NAME);
    if (!raw) return null;
    try {
      var obj = JSON.parse(raw);
      if (obj && typeof obj === 'object') {
        return obj;
      }
      console.warn('F152: consent cookie has unexpected format, showing banner again.');
      return null;
    } catch(e) {
      console.warn('F152: failed to parse consent cookie, showing banner again.', e);
      return null;
    }
  }

  function isConsentCurrent(consent) {
    if (!consent || typeof consent !== 'object') return false;
    if (Object.prototype.hasOwnProperty.call(consent, 'v')) {
      return String(consent.v) === COOKIE_VER;
    }
    return COOKIE_VER === COOKIE_LEGACY_VER;
  }

  function analyticsAllowed() {
    var c = parseConsent();
    if (!c) return true;
    if (Object.prototype.hasOwnProperty.call(c, 'analytics') && c.analytics === false) return false;
    return true;
  }

  function getConsentState() {
    var c = parseConsent();
    var exists = !!c;
    var hasVersion = !!(c && Object.prototype.hasOwnProperty.call(c, 'v'));

    return {
      exists: exists,
      current: exists && isConsentCurrent(c),
      legacy: exists && !hasVersion,
      currentVersion: COOKIE_VER,
      consentVersion: hasVersion ? String(c.v) : null,
      analytics: (c && Object.prototype.hasOwnProperty.call(c, 'analytics')) ? !!c.analytics : null,
      marketing: (c && Object.prototype.hasOwnProperty.call(c, 'marketing')) ? !!c.marketing : null
    };
  }

  function isCategoryAllowed(category) {
    var state = getConsentState();
    if (!state.current) return false;
    if (category !== 'analytics' && category !== 'marketing') return false;
    return state[category] === true;
  }

  function allowCategory(category) {
    if (category !== 'analytics' && category !== 'marketing') return false;

    var state = getConsentState();
    var groups = {
      analytics: state.current && state.analytics === true,
      marketing: state.current && state.marketing === true
    };
    groups[category] = true;

    saveConsent(groups);
    return true;
  }

  window.F152Consent = {
    getState: getConsentState,
    isAllowed: isCategoryAllowed,
    allowCategory: allowCategory,
    hasCurrentConsent: function() { return getConsentState().current; },
    getVersion: function() { return COOKIE_VER; }
  };

  function dispatchConsentChanged(payload) {
    var detail = {
      version: String(payload.v),
      analytics: !!payload.analytics,
      marketing: !!payload.marketing
    };

    try {
      if (typeof window.CustomEvent === 'function') {
        document.dispatchEvent(new CustomEvent('f152:consent-changed', { detail: detail }));
        return;
      }

      var event = document.createEvent('CustomEvent');
      event.initCustomEvent('f152:consent-changed', false, false, detail);
      document.dispatchEvent(event);
    } catch (e) {}
  }

  function saveConsent(groups) {
    var payload = {
      v: COOKIE_VER,
      ts: Math.floor(Date.now()/1000),
      analytics: !!groups.analytics,
      marketing: !!groups.marketing
    };
    setCookie(COOKIE_NAME, JSON.stringify(payload), COOKIE_DAYS, COOKIE_DOM, COOKIE_SS, COOKIE_SEC);
    dispatchConsentChanged(payload);
    hideBanner();
    closePopup();
  }

  function qs(sel) { return document.querySelector(sel); }
  function qsa(sel) { return document.querySelectorAll(sel); }

  function show(el) {
    if (!el) return;
    el.hidden = false;
    el.setAttribute('aria-hidden', 'false');
  }
  function hide(el) {
    if (!el) return;
    el.hidden = true;
    el.setAttribute('aria-hidden', 'true');
  }

  function getBanner() { return qs('[data-f152-banner]'); }
  function getPopup()  { return qs('[data-f152-popup]'); }

  function showBanner() { show(getBanner()); }
  function hideBanner() { hide(getBanner()); }
  function openPopup()  { show(getPopup()); focusFirstInput(); }
  function closePopup() { hide(getPopup()); }

  function focusFirstInput(){
    var w = getPopup();
    if (!w) return;
    var input = w.querySelector('input,button,textarea,select');
    if (input && typeof input.focus === 'function') input.focus();
  }

  function syncPopupFromCookie() {
    var c = parseConsent();
    if (!c) return;
    var a = getPopup() && getPopup().querySelector('[data-f152-cat="analytics"]');
    var m = getPopup() && getPopup().querySelector('[data-f152-cat="marketing"]');
    if (a) a.checked = !!c.analytics;
    if (m) m.checked = !!c.marketing;
  }

  function readGroupsFromPopup() {
    var a = getPopup() && getPopup().querySelector('[data-f152-cat="analytics"]');
    var m = getPopup() && getPopup().querySelector('[data-f152-cat="marketing"]');
    return {
      analytics: !!(a && a.checked),
      marketing: !!(m && m.checked)
    };
  }

  function decodeEmbedSrc(encoded) {
    if (!encoded) return '';
    try { return window.atob(encoded); } catch (e) { return ''; }
  }

  function normalizeDimension(value) {
    value = String(value || '').trim();
    if (!value) return '';
    if (/^\d+(?:\.\d+)?$/.test(value)) return value + 'px';
    if (/^\d+(?:\.\d+)?(?:px|%|vw|vh|rem|em)$/.test(value)) return value;
    return '';
  }

  function getEmbedToken(node) {
    return node ? String(node.getAttribute('data-f152-embed-token') || '') : '';
  }

  function findEmbedPlaceholder(node) {
    if (!node || !node.parentNode) return null;
    var token = getEmbedToken(node);
    var placeholders = node.parentNode.querySelectorAll
      ? node.parentNode.querySelectorAll('[data-f152-embed-placeholder]')
      : [];

    for (var i = 0; i < placeholders.length; i++) {
      if (!token || placeholders[i].getAttribute('data-f152-embed-token') === token) {
        return placeholders[i];
      }
    }
    return null;
  }

  function applyEmbedDimensions(placeholder, frame) {
    if (!placeholder || !frame) return;
    var width = (frame.style && frame.style.width) || normalizeDimension(frame.getAttribute('width'));
    var height = (frame.style && frame.style.height) || normalizeDimension(frame.getAttribute('height'));
    if (width) placeholder.style.width = width;
    if (height) placeholder.style.minHeight = height;
  }

  function getOrCreateIframePlaceholder(frame) {
    if (!frame || !frame.parentNode) return null;
    var placeholder = findEmbedPlaceholder(frame);
    if (placeholder) return placeholder;

    placeholder = document.createElement('div');
    placeholder.className = 'f152-embed-placeholder';
    placeholder.setAttribute('data-f152-embed-placeholder', frame.getAttribute('data-f152-embed-type') || 'content');
    placeholder.setAttribute('data-f152-embed-token', getEmbedToken(frame));
    placeholder.setAttribute('aria-hidden', 'false');

    var type = frame.getAttribute('data-f152-embed-type') || 'content';
    var category = frame.getAttribute('data-f152-embed-category') || 'analytics';
    var marketing = category === 'marketing';
    var text = document.createElement('div');
    text.className = 'f152-embed-placeholder__text';
    if (type === 'map') {
      text.textContent = marketing
        ? ((CFG.i18n && CFG.i18n.embed_map_blocked_marketing) || 'Карта скрыта до разрешения маркетинговых cookie.')
        : ((CFG.i18n && CFG.i18n.embed_map_blocked) || 'Карта скрыта до разрешения аналитических cookie.');
    } else if (type === 'video') {
      text.textContent = marketing
        ? ((CFG.i18n && CFG.i18n.embed_video_blocked_marketing) || 'Видео скрыто до разрешения маркетинговых cookie.')
        : ((CFG.i18n && CFG.i18n.embed_video_blocked) || 'Видео скрыто до разрешения аналитических cookie.');
    } else {
      text.textContent = marketing
        ? ((CFG.i18n && CFG.i18n.embed_content_blocked_marketing) || 'Внешнее содержимое скрыто до разрешения маркетинговых cookie.')
        : ((CFG.i18n && CFG.i18n.embed_content_blocked) || 'Внешнее содержимое скрыто до разрешения аналитических cookie.');
    }

    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'f152-btn f152-btn--accept f152-embed-placeholder__button';
    button.setAttribute('data-f152-embed-allow', frame.getAttribute('data-f152-embed-category') || 'analytics');
    if (type === 'map') {
      button.textContent = (CFG.i18n && CFG.i18n.embed_allow_map) || 'Разрешить и показать карту';
    } else if (type === 'video') {
      button.textContent = (CFG.i18n && CFG.i18n.embed_allow_video) || 'Разрешить и показать видео';
    } else {
      button.textContent = (CFG.i18n && CFG.i18n.embed_allow) || 'Разрешить и показать';
    }

    placeholder.appendChild(text);
    placeholder.appendChild(button);
    applyEmbedDimensions(placeholder, frame);
    frame.parentNode.insertBefore(placeholder, frame);
    return placeholder;
  }

  function shouldActivateEmbed(node) {
    if (!node) return false;
    var category = node.getAttribute('data-f152-embed-category') || 'analytics';
    var mode = node.getAttribute('data-f152-embed-mode') || 'require_accept';
    var state = getConsentState();

    if (mode === 'always') return true;

    if (mode === 'disable_on_reject') {
      return state[category] !== false;
    }

    return state.current && state[category] === true;
  }

  function activateIframeEmbed(frame) {
    if (!frame) return;
    var src = decodeEmbedSrc(frame.getAttribute('data-f152-embed-src') || '');
    if (!src) return;

    var placeholder = getOrCreateIframePlaceholder(frame);
    if (frame.getAttribute('src') !== src) frame.setAttribute('src', src);
    frame.hidden = false;
    frame.setAttribute('aria-hidden', 'false');
    if (placeholder) hide(placeholder);
  }

  function blockIframeEmbed(frame) {
    if (!frame) return;
    var placeholder = getOrCreateIframePlaceholder(frame);
    var sourceAttrs = ['src', 'data-src', 'data-lazy-src', 'data-original', 'data-lazyload-src'];
    for (var i = 0; i < sourceAttrs.length; i++) {
      if (frame.hasAttribute(sourceAttrs[i])) frame.removeAttribute(sourceAttrs[i]);
    }
    if (frame.hasAttribute('srcdoc')) frame.removeAttribute('srcdoc');
    frame.hidden = true;
    frame.setAttribute('aria-hidden', 'true');
    if (placeholder) show(placeholder);
  }

  function isYandexMapFrame(frame) {
    if (!frame || String(frame.tagName || '').toLowerCase() !== 'iframe') return false;
    var src = String(frame.getAttribute('src') || '');
    return /^(?:https?:)?\/\/(?:www\.)?yandex\.(?:ru|com)\/map-widget\//i.test(src)
      || /^(?:https?:)?\/\/api-maps\.yandex\.(?:ru|com)\/frame\/v1\//i.test(src);
  }

  function tagRuntimeFrames(marker, addedNodes) {
    var token = getEmbedToken(marker);
    if (!token || !addedNodes) return;

    for (var i = 0; i < addedNodes.length; i++) {
      var node = addedNodes[i];
      if (isYandexMapFrame(node)) {
        node.setAttribute('data-f152-runtime-token', token);
      }
    }
  }

  function findRuntimeScript(marker) {
    if (!marker || !marker.parentNode) return null;
    var token = getEmbedToken(marker);
    if (!token) return null;
    var scripts = marker.parentNode.querySelectorAll
      ? marker.parentNode.querySelectorAll('script[data-f152-runtime-token]')
      : [];
    for (var i = 0; i < scripts.length; i++) {
      if (scripts[i].getAttribute('data-f152-runtime-token') === token) return scripts[i];
    }
    return null;
  }

  function removeRuntimeMapFrames(marker) {
    if (!marker || !marker.parentNode) return;
    var token = getEmbedToken(marker);
    var frames = marker.parentNode.querySelectorAll ? marker.parentNode.querySelectorAll('iframe') : [];
    var untaggedYandex = [];

    for (var i = 0; i < frames.length; i++) {
      if (token && frames[i].getAttribute('data-f152-runtime-token') === token) {
        frames[i].remove();
      } else if (isYandexMapFrame(frames[i])) {
        untaggedYandex.push(frames[i]);
      }
    }

    if (untaggedYandex.length === 1) untaggedYandex[0].remove();
  }

  function removeRuntimeScript(marker) {
    var runtime = findRuntimeScript(marker);
    if (runtime) runtime.remove();
  }

  function activateScriptEmbed(marker) {
    if (!marker || !marker.parentNode) return;
    var placeholder = findEmbedPlaceholder(marker);

    if (marker.getAttribute('data-f152-embed-active') === '1') {
      if (placeholder) hide(placeholder);
      return;
    }

    var currentRuntime = findRuntimeScript(marker);
    if (marker.getAttribute('data-f152-embed-loading') === '1' && currentRuntime) {
      if (placeholder) hide(placeholder);
      return;
    }
    if (currentRuntime) currentRuntime.remove();

    var src = decodeEmbedSrc(marker.getAttribute('data-f152-embed-src') || '');
    var token = getEmbedToken(marker);
    if (!src || !token) return;

    var parent = marker.parentNode;
    var observer = null;
    if (typeof MutationObserver === 'function') {
      observer = new MutationObserver(function(mutations) {
        for (var i = 0; i < mutations.length; i++) {
          tagRuntimeFrames(marker, mutations[i].addedNodes || []);
        }
      });
      try { observer.observe(parent, { childList: true }); } catch (e) { observer = null; }
    }

    var runtime = document.createElement('script');
    runtime.src = src;
    runtime.async = true;
    runtime.charset = 'utf-8';
    runtime.setAttribute('data-f152-runtime-token', token);
    runtime.setAttribute('data-nowprocket', '');
    runtime.setAttribute('data-no-optimize', '1');

    marker.setAttribute('data-f152-embed-loading', '1');
    marker.setAttribute('data-f152-embed-active', '0');

    runtime.onload = function() {
      if (findRuntimeScript(marker) !== runtime) return;
      marker.setAttribute('data-f152-embed-loading', '0');
      marker.setAttribute('data-f152-embed-active', '1');
      if (placeholder) hide(placeholder);
    };

    runtime.onerror = function() {
      if (findRuntimeScript(marker) === runtime) runtime.remove();
      marker.setAttribute('data-f152-embed-loading', '0');
      marker.setAttribute('data-f152-embed-active', '0');
      if (observer) observer.disconnect();
      if (placeholder) show(placeholder);
    };

    if (placeholder) hide(placeholder);
    parent.insertBefore(runtime, marker);

    setTimeout(function() {
      if (observer) observer.disconnect();
    }, 1500);
  }

  function blockScriptEmbed(marker) {
    if (!marker) return;
    removeRuntimeMapFrames(marker);
    removeRuntimeScript(marker);
    marker.setAttribute('data-f152-embed-loading', '0');
    marker.setAttribute('data-f152-embed-active', '0');

    var placeholder = findEmbedPlaceholder(marker);
    if (placeholder) show(placeholder);
  }

  function syncAllEmbeds() {
    var frames = qsa('iframe[data-f152-embed][data-f152-embed-src]');
    for (var i = 0; i < frames.length; i++) {
      if (shouldActivateEmbed(frames[i])) activateIframeEmbed(frames[i]);
      else blockIframeEmbed(frames[i]);
    }

    var scripts = qsa('script[data-f152-embed-script][data-f152-embed-src]');
    for (var j = 0; j < scripts.length; j++) {
      if (shouldActivateEmbed(scripts[j])) activateScriptEmbed(scripts[j]);
      else blockScriptEmbed(scripts[j]);
    }
  }

  function initEmbeds() {
    syncAllEmbeds();
    document.addEventListener('f152:consent-changed', syncAllEmbeds);
  }

  var BannerStats = (function() {
    var cfg = (CFG.bannerStats) || null;
    var showSentOnPage = false;

    function isEnabled() {
      if (!cfg) return false;
      if (cfg.enabled === false) return false;
      if (!cfg.endpointUrl) return false;
      try {
        if (navigator.webdriver === true) return false;
      } catch (e) {}
      return true;
    }

    function getStorage() {
      try {
        return window.sessionStorage;
      } catch (e) {
        return null;
      }
    }

    function uuid() {
      try {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
          return window.crypto.randomUUID();
        }
      } catch (e) {}
      try {
        if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
          var b = new Uint8Array(16);
          window.crypto.getRandomValues(b);
          var hex = [];
          for (var i = 0; i < b.length; i++) {
            hex.push(b[i].toString(16).padStart(2, '0'));
          }
          return hex.join('');
        }
      } catch (e) {}
      var s = '';
      for (var k = 0; k < 32; k++) {
        s += Math.floor(Math.random() * 16).toString(16);
      }
      return s;
    }

    function getToken() {
      var storage = getStorage();
      if (!storage) return '';
      var key = cfg.sessionStorageKey || 'f152_bs_token';
      var token = '';
      try { token = storage.getItem(key) || ''; } catch (e) {}
      if (!token) {
        token = uuid();
        try { storage.setItem(key, token); } catch (e) {}
      }
      return token;
    }

    function send(action) {
      if (!isEnabled()) return;
      var token = getToken();
      if (!token) return;

      try {
        var fd = new FormData();
        fd.append('action', action);
        fd.append('session_token', token);
        fetch(cfg.endpointUrl, {
          method: 'POST',
          body: fd,
          credentials: 'same-origin',
          keepalive: true
        })['catch'](function () {});
      } catch (e) {}
    }

    function maybeSendShow() {
      if (!isEnabled()) return;
      if (showSentOnPage) return;
      showSentOnPage = true;
      send('show');
    }

    return {
      send: send,
      maybeSendShow: maybeSendShow
    };
  })();

  function applyTheme() {
    var mode = (CFG.theme && CFG.theme.mode) === 'dark' ? 'dark' : 'light';
    try {
      document.body.setAttribute('data-f152-theme', mode);
      if (document.body.classList) {
        document.body.classList.remove('f152-theme-light', 'f152-theme-dark');
        document.body.classList.add('f152-theme-' + mode);
      }
    } catch(e){}
  }

  function handleClick(e) {
    var t = e.target;

    if (t && t.closest('[data-f152-embed-allow]')) {
      e.preventDefault();
      var embedButton = t.closest('[data-f152-embed-allow]');
      var embedCategory = embedButton.getAttribute('data-f152-embed-allow') || 'analytics';
      var bannerForEmbed = getBanner();
      if (bannerForEmbed && !bannerForEmbed.hidden) BannerStats.send('custom');
      allowCategory(embedCategory);
      return;
    }

    if (t && t.closest('[data-f152-open]')) {
      e.preventDefault();
      syncPopupFromCookie();
      openPopup();
      return;
    }

    if (t && t.closest('[data-f152-close]')) {
      e.preventDefault();
      closePopup();
      return;
    }

    if (t && t.closest('[data-f152-accept]')) {
      e.preventDefault();
      BannerStats.send('accept');
      saveConsent({ analytics:true, marketing:true });
      return;
    }

    if (t && t.closest('[data-f152-reject]')) {
      e.preventDefault();
      BannerStats.send('reject');
      saveConsent({ analytics:false, marketing:false });
      return;
    }

    if (t && t.closest('[data-f152-save]')) {
      e.preventDefault();
      var groups = readGroupsFromPopup();
      BannerStats.send('custom');
      saveConsent(groups);
      return;
    }
  }

  function handleKey(e) {
    if (e.key === 'Escape') {
      closePopup();
    }
  }

  function init() {
    var enabled = CFG.hasOwnProperty('enabled') ? !!CFG.enabled : true;
    if (enabled === false) return;

    applyTheme();
    initEmbeds();

    var consent = parseConsent();
    if (!consent || !isConsentCurrent(consent)) {
      showBanner();
      BannerStats.maybeSendShow();
    } else {
      hideBanner();
    }

    document.addEventListener('click', handleClick);
    document.addEventListener('keydown', handleKey);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
