// Barangay Management System - Frontend Application
const app = (() => {
  const API_BASE = 'api/';

  // ==================== STATE ====================
  
  let currentUser = null;
  let officials = [];

  // ==================== DOM ELEMENTS ====================
  
  const loginPage = document.getElementById('login-page');
  const registerPage = document.getElementById('register-page');
  const dashboard = document.getElementById('dashboard');
  const body = document.body;

  const switchToRegister = document.getElementById('switch-to-register');
  const switchToLogin = document.getElementById('switch-to-login');

  const loginForm = document.getElementById('login-form');
  const registerForm = document.getElementById('register-form');
  const loginBtn = document.getElementById('login-btn');
  const registerBtn = document.getElementById('register-btn');
  const logoutBtn = document.getElementById('logout-btn');
  const userName = document.getElementById('user-name');

  const loginError = document.getElementById('login-error');
  const registerError = document.getElementById('register-error');

  const hamburger = document.getElementById('hamburger');
  const burgerMenu = document.getElementById('burger-menu');
  const panelTitle = document.getElementById('panel-title');
  const panelBody = document.getElementById('panel-body');
  const pageTitle = document.getElementById('page-title');
  const officialsList = document.getElementById('officials-list');

  // ==================== API CALLS ====================
  
  async function apiCall(endpoint, method = 'GET', data = null) {
    const options = {
      method,
      headers: {
        'Content-Type': 'application/json',
      },
    };

    if (data) {
      options.body = JSON.stringify(data);
    }

    try {
      const response = await fetch(API_BASE + endpoint, options);
      const result = await response.json();

      if (!response.ok && response.status === 401) {
        logout();
        return null;
      }

      return result;
    } catch (error) {
      console.error('API Error:', error);
      return { success: false, error: error.message };
    }
  }

  // ==================== AUTHENTICATION ====================
  
  async function register() {
    const name = document.getElementById('register-name').value.trim();
    const email = document.getElementById('register-email').value.trim().toLowerCase();
    const password = document.getElementById('register-password').value;
    const confirm = document.getElementById('register-confirm').value;

    registerError.classList.add('hidden');

    if (!name || !email || !password || !confirm) {
      showError(registerError, 'All fields are required');
      return;
    }

    if (password !== confirm) {
      showError(registerError, 'Passwords do not match');
      return;
    }

    if (password.length < 8) {
      showError(registerError, 'Password must be at least 8 characters');
      return;
    }

    const result = await apiCall('auth.php?action=register', 'POST', {
      name, email, password, confirm
    });

    if (!result.success) {
      showError(registerError, result.error || 'Registration failed');
      return;
    }

    alert('✅ Account created successfully! Please login.');
    registerForm.reset();
    showLogin();
  }

  async function login() {
    const email = document.getElementById('login-email').value.trim().toLowerCase();
    const password = document.getElementById('login-password').value;

    loginError.classList.add('hidden');

    if (!email || !password) {
      showError(loginError, 'Email and password are required');
      return;
    }

    const result = await apiCall('auth.php?action=login', 'POST', {
      email, password
    });

    if (!result.success) {
      showError(loginError, result.error || 'Login failed');
      return;
    }

    currentUser = result.user;
    localStorage.setItem('user', JSON.stringify(currentUser));
    renderApp();
    loginForm.reset();
  }

  async function logout() {
    await apiCall('auth.php?action=logout', 'POST');
    currentUser = null;
    localStorage.removeItem('user');
    renderApp();
    loginForm.reset();
    registerForm.reset();
  }

  async function checkSession() {
    const result = await apiCall('auth.php?action=current-user', 'GET');
    
    if (result.success) {
      currentUser = result.user;
      localStorage.setItem('user', JSON.stringify(currentUser));
    } else {
      currentUser = JSON.parse(localStorage.getItem('user') || 'null');
    }
  }

  // ==================== API FUNCTIONS ====================
  
  async function fetchOfficials() {
    const result = await apiCall('officials.php', 'GET');
    if (result.success) {
      officials = result.data;
      renderOfficials();
    }
  }

  async function requestClearance(purpose) {
    const result = await apiCall('clearance.php', 'POST', { purpose });
    
    if (result.success) {
      alert('✅ Clearance request submitted!');
      showView('my-requests');
    } else {
      alert('❌ ' + (result.error || 'Failed to submit clearance'));
    }
  }

  async function fetchClearances() {
    const result = await apiCall('clearance.php', 'GET');
    if (result.success) {
      return result.data;
    }
    return [];
  }

  // ==================== UI HELPERS ====================
  
  function showError(element, message) {
    element.textContent = message;
    element.classList.remove('hidden');
  }

  function getInitials(name) {
    return name.split(' ').slice(0, 2).map(s => s[0]).join('').toUpperCase();
  }

  function renderOfficials() {
    officialsList.innerHTML = '';
    
    if (officials.length === 0) {
      officialsList.innerHTML = '<p>No officials found</p>';
      return;
    }

    officials.forEach(official => {
      const el = document.createElement('div');
      el.className = 'official';
      el.innerHTML = `
        <div class="avatar">${getInitials(official.full_name)}</div>
        <div class="info">
          <h3>${official.full_name}</h3>
          <p>${official.position}</p>
        </div>
      `;
      officialsList.appendChild(el);
    });
  }

  // ==================== NAVIGATION ====================
  
  function showLogin() {
    loginPage.classList.add('active');
    registerPage.classList.remove('active');
  }

  function showRegister() {
    registerPage.classList.add('active');
    loginPage.classList.remove('active');
  }

  switchToRegister.addEventListener('click', (e) => {
    e.preventDefault();
    showRegister();
  });

  switchToLogin.addEventListener('click', (e) => {
    e.preventDefault();
    showLogin();
  });

  // ==================== VIEW RENDERING ====================
  
  async function showView(view) {
    switch (view) {
      case 'officials':
        pageTitle.textContent = 'Barangay Officials';
        panelTitle.textContent = 'Leadership Team';
        await fetchOfficials();
        panelBody.innerHTML = renderOfficialsList();
        break;

      case 'population':
        pageTitle.textContent = 'Population';
        panelTitle.textContent = 'Barangay Population Statistics';
        panelBody.innerHTML = `
          <div class="stats-grid">
            <div class="stat-card">
              <h3>Total Population</h3>
              <p class="stat-number">15,250</p>
            </div>
            <div class="stat-card">
              <h3>Households</h3>
              <p class="stat-number">3,200</p>
            </div>
            <div class="stat-card">
              <h3>PWD Residents</h3>
              <p class="stat-number">450</p>
            </div>
            <div class="stat-card">
              <h3>Solo Parents</h3>
              <p class="stat-number">320</p>
            </div>
          </div>
        `;
        break;

      case 'clearance':
        pageTitle.textContent = 'Services';
        panelTitle.textContent = 'Barangay Clearance';
        panelBody.innerHTML = `
          <div class="service-info">
            <h3>Request Barangay Clearance</h3>
            <p><strong>Validity:</strong> 6 months from date of issuance</p>
            <p><strong>Requirements:</strong></p>
            <ul>
              <li>Valid ID (any government-issued ID)</li>
              <li>Certificate of Residency</li>
              <li>Cedula</li>
            </ul>
            <p><strong>Processing Time:</strong> 1-2 days</p>
            <p><strong>Fee:</strong> ₱100</p>
            
            <form id="clearance-form" style="margin-top: 20px; padding: 20px; background: #f3f4f6; border-radius: 8px;">
              <div class="form-group">
                <label for="clearance-purpose">Purpose of Clearance</label>
                <input type="text" id="clearance-purpose" placeholder="e.g., Employment, School enrollment" required />
              </div>
              <button type="submit" class="btn-submit">📋 Request Clearance</button>
            </form>
          </div>
        `;

        // Add event listener for form
        const clearanceForm = document.getElementById('clearance-form');
        if (clearanceForm) {
          clearanceForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const purpose = document.getElementById('clearance-purpose').value;
            requestClearance(purpose);
          });
        }
        break;

      case 'my-requests':
        pageTitle.textContent = 'My Requests';
        panelTitle.textContent = 'My Service Requests';
        const clearances = await fetchClearances();
        panelBody.innerHTML = renderMyRequests(clearances);
        break;

      default:
        pageTitle.textContent = 'Barangay Officials';
        panelTitle.textContent = 'Welcome';
        panelBody.innerHTML = `<p>Welcome to Barangay Bonbon Portal. Select a menu item to explore our services.</p>`;
    }
  }

  function renderOfficialsList() {
    let html = '<div class="officials-detail">';
    if (officials.length === 0) {
      html += '<p>No officials found</p>';
    } else {
      officials.forEach(official => {
        html += `
          <div class="official-card">
            <div class="official-avatar">${getInitials(official.full_name)}</div>
            <div class="official-details">
              <h3>${official.full_name}</h3>
              <p>${official.position}</p>
            </div>
          </div>
        `;
      });
    }
    html += '</div>';
    return html;
  }

  function renderMyRequests(clearances) {
    let html = '<div class="requests-table">';
    
    if (clearances.length === 0) {
      html += '<p>No requests yet.</p>';
    } else {
      html += '<table><thead><tr><th>Purpose</th><th>Status</th><th>Submitted</th><th>Expires</th></tr></thead><tbody>';
      clearances.forEach(req => {
        const statusBadge = `<span class="badge badge-${req.status}">${req.status.toUpperCase()}</span>`;
        html += `
          <tr>
            <td>${req.purpose}</td>
            <td>${statusBadge}</td>
            <td>${new Date(req.created_at).toLocaleDateString()}</td>
            <td>${new Date(req.expires_at).toLocaleDateString()}</td>
          </tr>
        `;
      });
      html += '</tbody></table>';
    }
    
    html += '</div>';
    return html;
  }

  // ==================== MENU ====================
  
  hamburger.addEventListener('click', () => {
    burgerMenu.classList.toggle('hidden');
  });

  burgerMenu.addEventListener('click', (e) => {
    const li = e.target.closest('li');
    if (!li) return;
    const view = li.dataset.view;
    showView(view);
    burgerMenu.classList.add('hidden');
  });

  document.addEventListener('click', (e) => {
    if (!hamburger.contains(e.target) && !burgerMenu.contains(e.target)) {
      burgerMenu.classList.add('hidden');
    }
  });

  // ==================== EVENT LISTENERS ====================
  
  loginBtn.addEventListener('click', (e) => {
    e.preventDefault();
    login();
  });

  registerBtn.addEventListener('click', (e) => {
    e.preventDefault();
    register();
  });

  logoutBtn.addEventListener('click', () => {
    logout();
  });

  // ==================== APP INITIALIZATION ====================
  
  async function renderApp() {
    if (currentUser) {
      body.classList.remove('auth-bg');
      loginPage.classList.remove('active');
      registerPage.classList.remove('active');
      dashboard.classList.remove('hidden');

      userName.textContent = currentUser.name;
      await fetchOfficials();
      showView('officials');
    } else {
      body.classList.add('auth-bg');
      dashboard.classList.add('hidden');
      showLogin();
    }
  }

  async function init() {
    await checkSession();
    renderApp();
  }

  // ==================== PUBLIC API ====================
  
  return {
    init
  };
})();

// Initialize app on page load
document.addEventListener('DOMContentLoaded', () => {
  app.init();
});
