const loginForm = document.getElementById('loginForm');
const signupForm = document.getElementById('signupForm');
const toggleLink = document.getElementById('toggleForm');
const title = document.getElementById('formTitle');

toggleLink.addEventListener('click', (e) => {
  e.preventDefault();

  const isLogin = !loginForm.classList.contains('d-none');

  loginForm.classList.toggle('d-none');
  signupForm.classList.toggle('d-none');

  title.textContent = isLogin ? 'Create Account' : 'Login';
  toggleLink.textContent = isLogin
    ? 'Already have an account?'
    : 'Create account';
});

document.addEventListener('DOMContentLoaded', () => {
  const forms = document.querySelectorAll('form');

  forms.forEach((form) => {
    form.addEventListener('submit', () => {
      const button = form.querySelector('.submit-btn');
      if (!button) return;

      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
      button.innerHTML = `
        <i class="fa fa-spinner fa-spin me-2" aria-hidden="true"></i>
        Please wait...
      `;
    });
  });
});
