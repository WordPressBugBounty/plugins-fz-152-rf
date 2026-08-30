(function ($) {
  'use strict';

  var config = window.F152ClassicCheckoutConsent || {};
  if (!config.enabled || !config.html) {
    return;
  }

  var INPUT_SELECTOR = 'input[name="f152_consent_checkout"]';
  var ROOT_SELECTOR = '[data-f152-classic-checkout-consent="1"]';
  var scheduled = false;

  function checkoutForm() {
    return document.querySelector('form.checkout, form.woocommerce-checkout');
  }

  function isVisible(element) {
    if (!element) {
      return false;
    }
    var style = window.getComputedStyle ? window.getComputedStyle(element) : null;
    return (!style || (style.display !== 'none' && style.visibility !== 'hidden')) && element.getClientRects().length > 0;
  }

  function findSubmit(form) {
    var selectors = [
      '#wpowp-quote-only',
      '#place_order',
      'button[name="woocommerce_checkout_place_order"]',
      'input[name="woocommerce_checkout_place_order"]',
      'button[type="submit"]'
    ];

    for (var i = 0; i < selectors.length; i++) {
      var candidates = form.querySelectorAll(selectors[i]);
      for (var j = 0; j < candidates.length; j++) {
        if (isVisible(candidates[j])) {
          return candidates[j];
        }
      }
    }

    for (var k = 0; k < selectors.length; k++) {
      var fallback = form.querySelector(selectors[k]);
      if (fallback) {
        return fallback;
      }
    }

    return null;
  }

  function removeDuplicates(form) {
    var inputs = Array.prototype.slice.call(form.querySelectorAll(INPUT_SELECTOR));
    if (inputs.length < 2) {
      return;
    }

    var keeper = inputs[inputs.length - 1];
    var checked = inputs.some(function (input) { return input.checked; });
    keeper.checked = checked;

    inputs.slice(0, -1).forEach(function (input) {
      var root = input.closest(ROOT_SELECTOR) || input.closest('.f152-checkout-consent');
      if (root && root !== keeper.closest(ROOT_SELECTOR)) {
        root.remove();
      }
    });
  }

  function createNode() {
    var template = document.createElement('template');
    template.innerHTML = config.html.trim();
    return template.content.firstElementChild;
  }

  function mountFallback(form) {
    var node = createNode();
    if (!node) {
      return;
    }

    var submit = findSubmit(form);
    if (submit) {
      var placeOrder = submit.closest('.place-order');
      if (placeOrder) {
        placeOrder.insertBefore(node, placeOrder.firstChild);
        return;
      }

      if (submit.parentNode) {
        submit.parentNode.insertBefore(node, submit);
        return;
      }
    }

    var review = form.querySelector('#order_review, .woocommerce-checkout-review-order');
    if (review) {
      review.appendChild(node);
    } else {
      form.appendChild(node);
    }
  }

  function ensureConsent() {
    if (document.querySelector('.wc-block-checkout, .wp-block-woocommerce-checkout')) {
      return;
    }

    var form = checkoutForm();
    if (!form) {
      return;
    }

    removeDuplicates(form);
    if (form.querySelector(INPUT_SELECTOR)) {
      return;
    }

    mountFallback(form);
  }

  function scheduleEnsure() {
    if (scheduled) {
      return;
    }
    scheduled = true;
    window.requestAnimationFrame(function () {
      scheduled = false;
      ensureConsent();
    });
  }

  function start() {
    ensureConsent();

    if ($ && document.body) {
      $(document.body).on('updated_checkout', scheduleEnsure);
    }

    if (typeof MutationObserver !== 'undefined' && document.body) {
      var observer = new MutationObserver(scheduleEnsure);
      observer.observe(document.body, { childList: true, subtree: true });
    }

    [100, 350, 900, 1800].forEach(function (delay) {
      window.setTimeout(ensureConsent, delay);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, { once: true });
  } else {
    start();
  }
})(window.jQuery);
