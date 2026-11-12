const apiBaseUrl = import.meta.env.VITE_API_BASE_URL ?? (import.meta.env.DEV ? 'http://localhost:8000' : '');

function getXsrfToken() {
  const cookie = document.cookie
    .split('; ')
    .find((item) => item.startsWith('XSRF-TOKEN='));

  return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : null;
}

async function initializeCsrfCookie() {
  await fetch(`${apiBaseUrl}/sanctum/csrf-cookie`, {
    credentials: 'include',
    headers: { Accept: 'application/json' },
  });
}

export async function apiRequest(path, options = {}) {
  const method = (options.method || 'GET').toUpperCase();
  const headers = { Accept: 'application/json', ...options.headers };
  const request = { ...options, method, headers, credentials: 'include' };

  if (options.body instanceof FormData) {
    request.body = options.body;
  } else if (options.body && typeof options.body !== 'string') {
    headers['Content-Type'] = 'application/json';
    request.body = JSON.stringify(options.body);
  }

  if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) {
    await initializeCsrfCookie();
    const token = getXsrfToken();

    if (token) {
      headers['X-XSRF-TOKEN'] = token;
    }
  }

  const response = await fetch(`${apiBaseUrl}${path}`, request);
  const payload = response.status === 204 ? null : await response.json();

  if (!response.ok) {
    const error = new Error(payload?.message || 'Não foi possível concluir a solicitação.');
    error.status = response.status;
    error.errors = payload?.errors || {};
    throw error;
  }

  return payload;
}
