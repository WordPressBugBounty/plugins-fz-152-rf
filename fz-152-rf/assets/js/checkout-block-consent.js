(function () {
  'use strict';

  var config = window.F152BlockCheckoutConsent || {};
  if (!config.enabled || !config.html) {
    return;
  }

  var INPUT_SELECTOR = 'input[data-f152-checkout-consent="1"]';
  var scheduled = false;

  function findLabelNode(input) {
    var label = input.closest('label');
    if (!label) {
      return null;
    }

    var node = label.querySelector('.wc-block-components-checkbox__label');
    if (node) {
      return node;
    }

    var spans = label.querySelectorAll('span');
    for (var i = spans.length - 1; i >= 0; i--) {
      var span = spans[i];
      if (
        !span.classList.contains('wc-block-components-checkbox__mark') &&
        !span.closest('svg')
      ) {
        return span;
      }
    }

    return null;
  }

  function protectLinks(node) {
    var links = node.querySelectorAll('a[href]');
    links.forEach(function (link) {
      if (link.dataset.f152CheckoutLinkBound === '1') {
        return;
      }

      link.dataset.f152CheckoutLinkBound = '1';

      link.addEventListener('click', function (event) {
        event.stopPropagation();
      });
      link.addEventListener('pointerdown', function (event) {
        event.stopPropagation();
      });
    });
  }

  function applyHtmlLabel() {
    document.querySelectorAll(INPUT_SELECTOR).forEach(function (input) {
      var node = findLabelNode(input);
      if (!node) {
        return;
      }

      if (node.dataset.f152CheckoutHtml === config.hash) {
        protectLinks(node);
        return;
      }

      node.innerHTML = config.html;
      node.dataset.f152CheckoutHtml = config.hash || '1';
      protectLinks(node);
    });
  }

  function scheduleApply() {
    if (scheduled) {
      return;
    }

    scheduled = true;
    window.requestAnimationFrame(function () {
      scheduled = false;
      applyHtmlLabel();
    });
  }

  function start() {
    applyHtmlLabel();

    if (!document.body || typeof MutationObserver === 'undefined') {
      return;
    }

    var observer = new MutationObserver(scheduleApply);
    observer.observe(document.body, {
      childList: true,
      subtree: true
    });

    [100, 350, 1000, 2500].forEach(function (delay) {
      window.setTimeout(applyHtmlLabel, delay);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, { once: true });
  } else {
    start();
  }
})();
