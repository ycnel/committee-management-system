/**
 * assets/js/profile.js
 * ------------------------------------------------------------------
 * Powers pages/profile.php's Change Password form.
 * ------------------------------------------------------------------
 */

(function () {
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
