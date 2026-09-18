(function (window, document) {
  'use strict';

  if (window.__IDAS_CONNECTION_STATUS_INSTALLED__) return;
  window.__IDAS_CONNECTION_STATUS_INSTALLED__ = true;

  function routeController() {
    try {
      var route = new URLSearchParams(window.location.search).get('url') || '';
      return String(route).replace(/^\/+/, '').split('/')[0].toLowerCase();
    } catch (e) {
      return '';
    }
  }

  // Login must stay quiet. It may be displayed while the controller is still
  // booting, so controller/WebSocket status banners are intentionally disabled.
  var controllerName = routeController();
  if (window.IDAS_IS_LOGIN_PAGE === true || controllerName === 'login' || controllerName === 'logins') return;

  var COPY = {
    'zh-tw': {
      controllerOffline: '控制器離線 — 正在重新連線…',
      controllerConnected: '控制器已重新連線',
      websocketOffline: 'WebSocket 連線中斷 — 正在重新連線…',
      websocketConnected: 'WebSocket 已重新連線'
    },
    'zh-cn': {
      controllerOffline: '控制器离线 — 正在重新连接…',
      controllerConnected: '控制器已重新连接',
      websocketOffline: 'WebSocket 连接中断 — 正在重新连接…',
      websocketConnected: 'WebSocket 已重新连接'
    },
    'en-us': {
      controllerOffline: 'Controller Offline — Reconnecting…',
      controllerConnected: 'Controller Connected',
      websocketOffline: 'WebSocket Disconnected — Reconnecting…',
      websocketConnected: 'WebSocket Connected'
    }
  };

  var controllerOnline = null;
  var controllerMisses = 0;
  var websocketOnline = null;
  var websocketEverTracked = false;
  var websocketOpenCount = 0;
  var transient = null;
  var transientTimer = null;

  function language() {
    var value = 'en-us';
    try {
      var match = document.cookie.match(/(?:^|;\s*)language=([^;]+)/);
      if (match) value = decodeURIComponent(match[1]);
    } catch (e) {}
    value = String(value || 'en-us').toLowerCase().replace('_', '-');
    if (value === 'zh-cn' || value === 'zh-sg' || value.indexOf('hans') !== -1) return 'zh-cn';
    if (value === 'zh-tw' || value === 'zh-hk' || value === 'zh-mo' || value.indexOf('hant') !== -1) return 'zh-tw';
    return 'en-us';
  }

  function copy() {
    return COPY[language()] || COPY['en-us'];
  }

  function ensureBanner() {
    var banner = document.getElementById('idasConnectionStatusBanner');
    if (banner) return banner;
    if (!document.body) return null;

    banner = document.createElement('div');
    banner.id = 'idasConnectionStatusBanner';
    banner.className = 'idas-connection-banner';
    banner.setAttribute('role', 'status');
    banner.setAttribute('aria-live', 'assertive');
    banner.setAttribute('aria-atomic', 'true');
    banner.innerHTML = '<span class="idas-connection-dot" aria-hidden="true"></span>'
      + '<span class="idas-connection-message"></span>';
    document.body.appendChild(banner);
    return banner;
  }

  function setBanner(kind, message) {
    var banner = ensureBanner();
    if (!banner) return;
    banner.classList.remove('is-offline', 'is-warning', 'is-connected', 'is-visible');
    banner.classList.add(kind, 'is-visible');
    var label = banner.querySelector('.idas-connection-message');
    if (label) label.textContent = message;
  }

  function hideBanner() {
    var banner = document.getElementById('idasConnectionStatusBanner');
    if (banner) banner.classList.remove('is-visible');
  }

  function showTransient(kind, message, duration) {
    transient = { kind: kind, message: message };
    if (transientTimer) window.clearTimeout(transientTimer);
    transientTimer = window.setTimeout(function () {
      transient = null;
      transientTimer = null;
      render();
    }, duration || 1600);
    render();
  }

  function render() {
    // Device-ID / protocol changes already have their own reboot banner. Never stack two banners.
    if (document.getElementById('rebootBanner')) {
      hideBanner();
      return;
    }

    var t = copy();
    if (controllerOnline === false) {
      setBanner('is-offline', t.controllerOffline);
      return;
    }
    if (websocketEverTracked && websocketOnline === false) {
      setBanner('is-warning', t.websocketOffline);
      return;
    }
    if (transient) {
      setBanner(transient.kind, transient.message);
      return;
    }
    hideBanner();
  }

  function setControllerOnline(value) {
    if (value === true) {
      var recovered = controllerOnline === false;
      controllerMisses = 0;
      controllerOnline = true;
      if (recovered) showTransient('is-connected', copy().controllerConnected, 1700);
      else render();
      return;
    }

    if (value !== false) return;
    controllerMisses += 1;
    // Two consecutive misses avoid flashing a red banner for a single slow Modbus request.
    if (controllerMisses < 2 && controllerOnline !== false) return;
    controllerOnline = false;
    render();
  }

  function setWebSocketOnline(value) {
    if (value === true) {
      var recovered = websocketOnline === false;
      websocketOnline = true;
      if (recovered && controllerOnline !== false) {
        showTransient('is-connected', copy().websocketConnected, 1500);
      } else {
        render();
      }
      return;
    }
    if (value === false) {
      websocketOnline = false;
      render();
    }
  }

  function installWebSocketObserver() {
    var NativeWebSocket = window.WebSocket;
    if (typeof NativeWebSocket !== 'function' || NativeWebSocket.__idasConnectionWrapped) return;

    function WrappedWebSocket(url, protocols) {
      var socket = arguments.length > 1
        ? new NativeWebSocket(url, protocols)
        : new NativeWebSocket(url);

      websocketEverTracked = true;
      var opened = false;
      var closed = false;

      socket.addEventListener('open', function () {
        if (closed) return;
        opened = true;
        websocketOpenCount += 1;
        setWebSocketOnline(true);
      });

      function disconnected() {
        if (closed) return;
        closed = true;
        if (opened && websocketOpenCount > 0) websocketOpenCount -= 1;
        if (websocketOpenCount <= 0) setWebSocketOnline(false);
      }

      socket.addEventListener('close', disconnected);
      socket.addEventListener('error', function () {
        // Error is followed by close in normal browsers, but update immediately if no socket remains open.
        if (websocketOpenCount <= 0) setWebSocketOnline(false);
      });

      return socket;
    }

    WrappedWebSocket.prototype = NativeWebSocket.prototype;
    ['CONNECTING', 'OPEN', 'CLOSING', 'CLOSED'].forEach(function (name) {
      try {
        Object.defineProperty(WrappedWebSocket, name, {
          value: NativeWebSocket[name], enumerable: true, configurable: false, writable: false
        });
      } catch (e) {
        try { WrappedWebSocket[name] = NativeWebSocket[name]; } catch (ignore) {}
      }
    });
    WrappedWebSocket.__idasConnectionWrapped = true;
    WrappedWebSocket.__idasNativeWebSocket = NativeWebSocket;
    window.WebSocket = WrappedWebSocket;
  }

  installWebSocketObserver();

  window.IdasConnectionStatus = {
    setControllerOnline: setControllerOnline,
    setWebSocketOnline: setWebSocketOnline,
    refresh: render,
    state: function () {
      return {
        controllerOnline: controllerOnline,
        websocketOnline: websocketEverTracked ? websocketOnline : null
      };
    }
  };

  // If another global banner is created/removed, reevaluate stacking quickly.
  if (window.MutationObserver) {
    var bannerObserver = new MutationObserver(function (mutations) {
      for (var i = 0; i < mutations.length; i += 1) {
        var nodes = mutations[i].addedNodes || [];
        for (var j = 0; j < nodes.length; j += 1) {
          if (nodes[j] && nodes[j].id === 'rebootBanner') {
            render();
            return;
          }
        }
        var removed = mutations[i].removedNodes || [];
        for (var k = 0; k < removed.length; k += 1) {
          if (removed[k] && removed[k].id === 'rebootBanner') {
            window.setTimeout(render, 0);
            return;
          }
        }
      }
    });
    if (document.documentElement) bannerObserver.observe(document.documentElement, { childList: true, subtree: true });
  }
}(window, document));
