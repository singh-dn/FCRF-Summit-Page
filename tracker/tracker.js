/**
 * ============================================================
 * tracker.js  —  Universal Visitor Tracking Script
 * ============================================================
 * HOW TO USE: Add ONE line to every page of your website,
 * just before </body>:
 *
 *   <script src="https://yourdomain.com/tracker/tracker.js"></script>
 *
 * That's it. Everything is automatic.
 * ============================================================
 */

(function () {
  'use strict';

  // ── CONFIG ── Update these two values ────────────────────
  const TRACK_URL = 'https://summit.futurecrime.org/tracker/track.php';
  const SECRET_KEY = 'x9Kf2LmQ7vNp4RsT8yZa1BcDeFgH6Jk'; // must match config.php
  // ─────────────────────────────────────────────────────────

  const HEARTBEAT_INTERVAL = 15000;  // 15 seconds
  const CLICK_DEBOUNCE     = 300;    // ms

  // ── IDs & Storage ────────────────────────────────────────

  function genId(len) {
    return Array.from(crypto.getRandomValues(new Uint8Array(len)))
      .map(b => b.toString(16).padStart(2, '0')).join('');
  }

  function getVisitorId() {
    let id = localStorage.getItem('_vtid');
    if (!id) { id = genId(16); localStorage.setItem('_vtid', id); }
    return id;
  }

  function getSessionId() {
    let id = sessionStorage.getItem('_vsid');
    if (!id) { id = genId(16); sessionStorage.setItem('_vsid', id); }
    return id;
  }

  const visitorId = getVisitorId();
  const sessionId = getSessionId();
  let sessionStarted = sessionStorage.getItem('_vss') === '1';

  // ── Send helper ───────────────────────────────────────────

  function send(payload, useBeacon) {
    payload.session_id = sessionId;
    payload.visitor_id = visitorId;
    const body = JSON.stringify(payload);

    if (useBeacon && navigator.sendBeacon) {
      const blob = new Blob([body], { type: 'application/json' });
      // sendBeacon can't set headers; use fetch fallback for beacon-like behavior
    }

    fetch(TRACK_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Tracker-Key': SECRET_KEY,
      },
      body,
      keepalive: !!useBeacon,
    }).catch(() => {}); // silent fail
  }

  // ── Device Detection ─────────────────────────────────────

  function getDeviceType() {
    const ua = navigator.userAgent;
    if (/bot|crawl|spider|slurp|teoma|ia_archiver/i.test(ua)) return 'bot';
    if (/tablet|ipad|playbook|silk/i.test(ua)) return 'tablet';
    if (/mobile|android|iphone|ipod|blackberry|opera mini|iemobile|wpdesktop/i.test(ua)) return 'mobile';
    return 'desktop';
  }

  function getBrowserInfo() {
    const ua = navigator.userAgent;
    const browsers = [
      [/Edg\/([0-9.]+)/, 'Edge'],
      [/OPR\/([0-9.]+)/, 'Opera'],
      [/SamsungBrowser\/([0-9.]+)/, 'Samsung'],
      [/Firefox\/([0-9.]+)/, 'Firefox'],
      [/Chrome\/([0-9.]+)/, 'Chrome'],
      [/Version\/([0-9.]+).*Safari/, 'Safari'],
      [/MSIE ([0-9.]+)/, 'IE'],
      [/Trident.*rv:([0-9.]+)/, 'IE'],
    ];
    for (const [re, name] of browsers) {
      const m = ua.match(re);
      if (m) return { name, version: m[1].split('.')[0] };
    }
    return { name: 'Unknown', version: '' };
  }

  function getOS() {
    const ua = navigator.userAgent;
    if (/Windows NT 10/.test(ua)) return 'Windows 10/11';
    if (/Windows NT 6.3/.test(ua)) return 'Windows 8.1';
    if (/Windows NT 6.1/.test(ua)) return 'Windows 7';
    if (/Windows/.test(ua))        return 'Windows';
    if (/iPhone OS/.test(ua))      return 'iOS';
    if (/iPad.*OS/.test(ua))       return 'iPadOS';
    if (/Mac OS X/.test(ua))       return 'macOS';
    if (/Android ([0-9.]+)/.test(ua)) return 'Android ' + ua.match(/Android ([0-9.]+)/)[1].split('.')[0];
    if (/Linux/.test(ua))          return 'Linux';
    if (/CrOS/.test(ua))           return 'ChromeOS';
    return 'Unknown';
  }

  // ── UTM Params ───────────────────────────────────────────

  function getUTM() {
    const p = new URLSearchParams(location.search);
    return {
      utm_source:   p.get('utm_source')   || '',
      utm_medium:   p.get('utm_medium')   || '',
      utm_campaign: p.get('utm_campaign') || '',
    };
  }

  // ── Session Start ────────────────────────────────────────

  function startSession() {
    if (sessionStarted) return;
    sessionStorage.setItem('_vss', '1');
    sessionStarted = true;

    const browser = getBrowserInfo();
    const utm = getUTM();

    send({
      action:          'session_start',
      device_type:     getDeviceType(),
      browser:         browser.name,
      browser_version: browser.version,
      os:              getOS(),
      screen_width:    screen.width,
      screen_height:   screen.height,
      language:        navigator.language || '',
      timezone:        Intl?.DateTimeFormat()?.resolvedOptions()?.timeZone || '',
      referrer:        document.referrer || '',
      landing_page:    location.href,
      ...utm,
    });
  }

  // ── Page View Tracking ───────────────────────────────────

  let pageEnterTime = Date.now();
  let currentUrl    = location.href;
  let maxScroll     = 0;

  function trackPageview(url, title) {
    send({
      action: 'pageview',
      url:    url,
      title:  title || document.title,
    });
    pageEnterTime = Date.now();
    maxScroll = 0;
  }

  function trackPageLeave(url) {
    const timeOnPage = Math.round((Date.now() - pageEnterTime) / 1000);
    send({
      action:       'page_leave',
      url:          url,
      time_on_page: timeOnPage,
      scroll_depth: maxScroll,
    }, true); // use keepalive
  }

  // ── Scroll Depth ─────────────────────────────────────────

  let scrollTimer;
  window.addEventListener('scroll', function () {
    clearTimeout(scrollTimer);
    scrollTimer = setTimeout(function () {
      const scrolled = window.scrollY + window.innerHeight;
      const total    = document.documentElement.scrollHeight;
      const pct      = Math.min(100, Math.round((scrolled / total) * 100));
      if (pct > maxScroll) maxScroll = pct;
    }, 100);
  }, { passive: true });

  // ── Click Tracking ───────────────────────────────────────

  let lastClickTime = 0;
  document.addEventListener('click', function (e) {
    const now = Date.now();
    if (now - lastClickTime < CLICK_DEBOUNCE) return;
    lastClickTime = now;

    const el  = e.target.closest('a, button, [data-track]') || e.target;
    const tag  = el.tagName.toLowerCase();

    // Skip purely decorative / noise
    if (['html','body','div','span','section','main','header','footer','nav'].includes(tag) &&
        !el.dataset.track) return;

    send({
      action: 'click',
      url:    location.href,
      tag:    tag,
      text:   (el.innerText || el.value || el.alt || '').trim().slice(0, 100),
      id:     el.id || '',
      class:  (el.className && typeof el.className === 'string') ? el.className.slice(0, 200) : '',
      href:   el.href || '',
      value:  el.value || '',
      x:      Math.round(e.clientX),
      y:      Math.round(e.clientY),
    });
  }, { passive: true });

  // ── Search Keyword Detection ─────────────────────────────
  // Auto-detects common search input patterns. Also exposes
  // window.VT.trackSearch(keyword, resultsCount) for manual use.

  function watchSearchInputs() {
    const selectors = [
      'input[type="search"]',
      'input[name="s"]',
      'input[name="q"]',
      'input[name="search"]',
      'input[name="query"]',
      'input[id*="search"]',
      'input[class*="search"]',
      'input[placeholder*="search" i]',
    ];

    let searchTimer;
    document.querySelectorAll(selectors.join(',')).forEach(function (input) {
      input.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const kw = input.value.trim();
        if (kw.length < 2) return;
        searchTimer = setTimeout(function () {
          VT.trackSearch(kw, 0);
        }, 800);
      });

      // Also capture on form submit
      const form = input.closest('form');
      if (form) {
        form.addEventListener('submit', function () {
          const kw = input.value.trim();
          if (kw.length >= 2) VT.trackSearch(kw, 0);
        });
      }
    });

    // Also detect search queries from URL (?s=... ?q=...)
    const params = new URLSearchParams(location.search);
    const q = params.get('q') || params.get('s') || params.get('search') || params.get('query');
    if (q && q.trim().length >= 2) {
      VT.trackSearch(q.trim(), 0);
    }
  }

  // ── Heartbeat (Real-time active users) ───────────────────

  let heartbeatTimer;
  function startHeartbeat() {
    function ping() {
      send({ action: 'heartbeat', url: location.href });
    }
    ping();
    heartbeatTimer = setInterval(ping, HEARTBEAT_INTERVAL);
  }

  // ── SPA / History navigation support ────────────────────

  function onNavigate(newUrl) {
    if (newUrl === currentUrl) return;
    trackPageLeave(currentUrl);
    currentUrl = newUrl;
    setTimeout(function () {
      trackPageview(currentUrl, document.title);
    }, 50);
  }

  // Patch pushState / replaceState
  ['pushState', 'replaceState'].forEach(function (method) {
    const orig = history[method];
    history[method] = function () {
      orig.apply(this, arguments);
      setTimeout(function () { onNavigate(location.href); }, 0);
    };
  });
  window.addEventListener('popstate', function () { onNavigate(location.href); });

  // ── Page Visibility (pause/resume) ───────────────────────

  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') {
      trackPageLeave(currentUrl);
      clearInterval(heartbeatTimer);
    } else {
      pageEnterTime = Date.now();
      trackPageview(currentUrl, document.title);
      startHeartbeat();
    }
  });

  // ── Unload ────────────────────────────────────────────────

  window.addEventListener('beforeunload', function () {
    trackPageLeave(currentUrl);
    clearInterval(heartbeatTimer);
  });
  window.addEventListener('pagehide', function () {
    trackPageLeave(currentUrl);
  });

  // ── Public API ────────────────────────────────────────────

  window.VT = {
    /**
     * Manually track a search keyword
     * Usage: VT.trackSearch('running shoes', 42)
     */
    trackSearch: function (keyword, resultsCount) {
      send({
        action:  'search',
        keyword: keyword,
        url:     location.href,
        results: resultsCount || 0,
      });
    },

    /**
     * Track a custom event
     * Usage: VT.track('video_play', { video_id: '123', title: 'Intro' })
     */
    track: function (eventName, meta) {
      send({
        action:     'event',
        event_name: eventName,
        url:        location.href,
        meta:       meta || {},
      });
    },

    /**
     * Get session/visitor IDs
     */
    getIds: function () {
      return { sessionId, visitorId };
    },
  };

  // ── Boot ──────────────────────────────────────────────────

  function boot() {
    startSession();
    trackPageview(currentUrl, document.title);
    startHeartbeat();
    // Delay search watcher slightly so dynamic inputs are rendered
    setTimeout(watchSearchInputs, 500);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

})();
