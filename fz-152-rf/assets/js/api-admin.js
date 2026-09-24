(function () {
  'use strict';

  var cfg = window.F152DeveloperAPIAdmin || {};
  var createForm = document.querySelector('[data-f152-api-create-form]');
  var tokenResult = document.querySelector('[data-f152-api-token-result]');
  var tokenValue = document.querySelector('[data-f152-api-token-value]');
  var message = document.querySelector('[data-f152-api-message]');

  function setMessage(text, isError) {
    if (!message) return;
    message.textContent = text || '';
    message.className = isError ? 'notice notice-error inline' : 'notice notice-success inline';
    message.hidden = !text;
  }

  function post(data) {
    var body = new URLSearchParams();
    Object.keys(data).forEach(function (key) {
      body.append(key, data[key]);
    });
    return fetch(cfg.ajaxUrl || window.ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString()
    }).then(function (response) {
      return response.json().then(function (json) {
        if (!response.ok || !json || !json.success) {
          var text = json && json.data && json.data.message ? json.data.message : 'Не удалось выполнить запрос.';
          throw new Error(text);
        }
        return json.data || {};
      });
    });
  }

  if (createForm) {
    createForm.addEventListener('submit', function (event) {
      event.preventDefault();
      setMessage('', false);
      if (tokenResult) tokenResult.hidden = true;

      var name = createForm.querySelector('[name="token_name"]');
      var consents = createForm.querySelector('[name="read_consents"]');
      var technical = createForm.querySelector('[name="read_technical"]');
      var submit = createForm.querySelector('[type="submit"]');
      if (submit) submit.disabled = true;

      post({
        action: 'f152_api_create_token',
        _ajax_nonce: cfg.nonce || '',
        name: name ? name.value : '',
        read_consents: consents && consents.checked ? '1' : '0',
        read_technical: technical && technical.checked ? '1' : '0'
      }).then(function (data) {
        if (tokenValue) tokenValue.textContent = data.token || '';
        if (tokenResult) tokenResult.hidden = false;
        setMessage('Токен создан. Скопируйте его сейчас: после перезагрузки страницы секрет больше не будет показан.', false);
        if (name) name.value = '';
      }).catch(function (error) {
        setMessage(error.message || 'Не удалось создать токен.', true);
      }).finally(function () {
        if (submit) submit.disabled = false;
      });
    });
  }

  document.addEventListener('change', function (event) {
    var target = event.target;
    if (!target || target.name !== 'read_consents') return;
    var technical = document.querySelector('[name="read_technical"]');
    if (!technical) return;
    technical.disabled = !target.checked;
    if (!target.checked) technical.checked = false;
  });

  document.addEventListener('click', function (event) {
    var clickTarget = event.target;
    if (!clickTarget || typeof clickTarget.closest !== 'function') return;
    var copyButton = clickTarget.closest('[data-f152-api-copy]');
    if (copyButton) {
      event.preventDefault();
      var value = tokenValue ? tokenValue.textContent : '';
      if (!value) return;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(value).then(function () {
          copyButton.textContent = 'Скопировано';
          window.setTimeout(function () { copyButton.textContent = 'Копировать'; }, 1200);
        });
      }
      return;
    }

    var revokeButton = clickTarget.closest('[data-f152-api-revoke]');
    if (!revokeButton) return;
    event.preventDefault();
    var tokenId = revokeButton.getAttribute('data-token-id') || '';
    if (!tokenId || !window.confirm('Отозвать этот API-токен? Подключённый сервис сразу потеряет доступ.')) return;
    revokeButton.disabled = true;
    post({
      action: 'f152_api_revoke_token',
      _ajax_nonce: cfg.nonce || '',
      token_id: tokenId
    }).then(function () {
      var row = revokeButton.closest('tr');
      if (row) row.remove();
      setMessage('Токен отозван.', false);
    }).catch(function (error) {
      revokeButton.disabled = false;
      setMessage(error.message || 'Не удалось отозвать токен.', true);
    });
  });
})();
