(function () {
  function showLoading() {
    const loading = document.getElementById('authLoading');
    if (loading) {
      loading.classList.add('is-visible');
      loading.setAttribute('aria-hidden', 'false');
      document.body.classList.add('auth-loading-active');
    }
  }

  function showAuthLoading(form) {
    if (form.dataset.submitting === 'true') return;

    form.dataset.submitting = 'true';
    showLoading();

    form.querySelectorAll('button[type="submit"]').forEach(function (button) {
      button.disabled = true;
    });
  }

  function showLoginTimeout(form) {
    const message = document.createElement('div');
    message.className = 'alert-custom alert-warning';
    message.setAttribute('role', 'alert');
    message.innerHTML = '<i class="bi bi-clock-history"></i><span>Login timed out. The server did not respond within 45 seconds. Please try again.</span>';
    form.parentNode.insertBefore(message, form);
  }

  function submitLoginWithTimeout(form) {
    const controller = new AbortController();
    const timeoutId = window.setTimeout(function () { controller.abort(); }, 45000);

    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      credentials: 'same-origin',
      signal: controller.signal
    }).then(function (response) {
      window.clearTimeout(timeoutId);
      if (response.redirected) {
        window.location.href = response.url;
        return;
      }
      return response.text().then(function (html) {
        document.open();
        document.write(html);
        document.close();
      });
    }).catch(function (error) {
      window.clearTimeout(timeoutId);
      if (error.name !== 'AbortError') {
        showLoginTimeout(form);
      } else {
        showLoginTimeout(form);
      }
      form.dataset.submitting = 'false';
      form.querySelectorAll('button[type="submit"]').forEach(function (button) {
        button.disabled = false;
      });
      const loading = document.getElementById('authLoading');
      if (loading) {
        loading.classList.remove('is-visible');
        loading.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('auth-loading-active');
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    const loading = document.getElementById('authLoading');
    document.querySelectorAll('[data-auth-loading-form]').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (form.hasAttribute('data-login-form')) {
          if (form.dataset.submitting === 'true') return;
          showAuthLoading(form);
          submitLoginWithTimeout(form);
          return;
        }
        showAuthLoading(form);
        window.setTimeout(function () {
          form.submit();
        }, 3000);
      });
    });

    document.querySelectorAll('[data-auth-loading-link]').forEach(function (link) {
      link.addEventListener('click', function (event) {
        if (link.dataset.navigating === 'true') return;

        event.preventDefault();
        link.dataset.navigating = 'true';
        showLoading();
        window.setTimeout(function () {
          window.location.href = link.href;
        }, 3000);
      });
    });

    if (loading) loading.setAttribute('aria-hidden', 'true');
  });
})();