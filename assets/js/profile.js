/**
 * assets/js/profile.js
 * ------------------------------------------------------------------
 * Powers pages/profile.php's Change Password form.
 * ------------------------------------------------------------------
 */

(function () {
  const backgroundForm = document.getElementById('backgroundForm');
  if (backgroundForm) {
    const backgroundFields = document.getElementById('backgroundFields');
    const editBackgroundButton = document.getElementById('editBackgroundButton');
    const saveBackgroundButton = document.getElementById('saveBackgroundButton');
    const backgroundSaveStatus = document.getElementById('backgroundSaveStatus');
    if (editBackgroundButton) {
      editBackgroundButton.addEventListener('click', function () {
        backgroundFields.disabled = false;
        editBackgroundButton.hidden = true;
        saveBackgroundButton.hidden = false;
        backgroundFields.querySelector('select, input, textarea')?.focus();
      });
    }
    backgroundForm.addEventListener('submit', function (e) {
      e.preventDefault();
      if (backgroundFields && backgroundFields.disabled) return;
      if (!backgroundForm.reportValidity()) return;
      if (saveBackgroundButton) saveBackgroundButton.disabled = true;
      if (backgroundSaveStatus) backgroundSaveStatus.textContent = 'Saving...';
      appPostForm(window.APP_URL + '/pages/ajax_save_background.php', backgroundForm)
        .then(data => {
          if (data.success) {
            if (backgroundSaveStatus) backgroundSaveStatus.textContent = data.message || 'Profile information saved.';
            window.setTimeout(() => window.location.reload(), 700);
          } else if (!data.session_expired) {
            if (saveBackgroundButton) saveBackgroundButton.disabled = false;
            if (backgroundSaveStatus) backgroundSaveStatus.textContent = data.message || 'Unable to save profile information.';
            Swal.fire('Error', data.message, 'error');
          }
        }).catch(() => {
          if (saveBackgroundButton) saveBackgroundButton.disabled = false;
          if (backgroundSaveStatus) backgroundSaveStatus.textContent = 'Unable to reach the server. Please try again.';
        });
    });
  }

  const form = document.getElementById('passwordForm');
  if (!form) return;

  initPasswordPolicyHint('pf_new_password');

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    appPost(window.APP_URL + '/pages/ajax_change_password.php', Object.fromEntries(new FormData(form)))
      .then(data => {
        if (data.success) {
          appToast('success', data.message);
          form.reset();
        } else if (!data.session_expired) {
          Swal.fire('Error', data.message, 'error');
        }
      });
  });
})();
