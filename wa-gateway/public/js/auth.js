// Auth Helper for WA Gateway

const API_BASE = '/api';

function getToken() {
  return localStorage.getItem('wa_token');
}

function getUser() {
  const user = localStorage.getItem('wa_user');
  return user ? JSON.parse(user) : null;
}

function authHeaders() {
  const token = getToken();
  return {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  };
}

function checkAuthGuard() {
  const token = getToken();
  const isLoginPage = window.location.pathname.endsWith('index.html') || window.location.pathname === '/' || window.location.pathname === '';

  if (!token && !isLoginPage) {
    window.location.href = '/index.html';
  } else if (token && isLoginPage) {
    window.location.href = '/dashboard.html';
  }
}

function logout() {
  localStorage.removeItem('wa_token');
  localStorage.removeItem('wa_user');
  window.location.href = '/index.html';
}

// Login form handler
document.addEventListener('DOMContentLoaded', () => {
  checkAuthGuard();

  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const email = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value;
      const alertBox = document.getElementById('alertBox');
      const btnSubmit = document.getElementById('btnSubmit');

      alertBox.classList.add('hidden');
      btnSubmit.disabled = true;
      btnSubmit.classList.add('opacity-70');

      try {
        const res = await fetch(`${API_BASE}/auth/login`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, password })
        });

        const data = await res.json();

        if (res.ok && data.success) {
          localStorage.setItem('wa_token', data.token);
          localStorage.setItem('wa_user', JSON.stringify(data.user));
          window.location.href = '/dashboard.html';
        } else {
          alertBox.className = 'mb-5 p-3.5 rounded-xl text-xs font-medium border bg-red-50 text-red-700 border-red-200';
          alertBox.textContent = data.message || 'Login gagal.';
          alertBox.classList.remove('hidden');
        }
      } catch (err) {
        alertBox.className = 'mb-5 p-3.5 rounded-xl text-xs font-medium border bg-red-50 text-red-700 border-red-200';
        alertBox.textContent = 'Gagal menghubungi server.';
        alertBox.classList.remove('hidden');
      } finally {
        btnSubmit.disabled = false;
        btnSubmit.classList.remove('opacity-70');
      }
    });
  }

  // Set user profile in dashboard
  const user = getUser();
  if (user) {
    const nameEl = document.getElementById('userName');
    const emailEl = document.getElementById('userEmail');
    if (nameEl) nameEl.textContent = user.name || 'User';
    if (emailEl) emailEl.textContent = user.email || '';
  }
});
