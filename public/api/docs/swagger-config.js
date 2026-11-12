window.addEventListener('DOMContentLoaded', () => {
  window.ui = SwaggerUIBundle({
    url: '/api/docs/openapi.yaml',
    dom_id: '#swagger-ui',
    deepLinking: true,
    filter: true,
    docExpansion: 'list',
    defaultModelsExpandDepth: -1,
    validatorUrl: null,
    withCredentials: true,
    supportedSubmitMethods: ['get', 'post', 'put', 'patch', 'delete'],
    requestInterceptor: async (request) => {
      const url = new URL(request.url, window.location.origin);
      const method = (request.method || 'GET').toUpperCase();

      if (url.origin !== window.location.origin) {
        throw new Error('A documentação só executa requisições para o mesmo host.');
      }

      request.credentials = 'same-origin';
      request.headers.Accept = 'application/json';
      request.headers['X-Requested-With'] = 'XMLHttpRequest';

      if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) {
        const response = await fetch('/sanctum/csrf-cookie', {
          credentials: 'same-origin',
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!response.ok) {
          throw new Error('Não foi possível iniciar a proteção CSRF.');
        }

        const cookie = document.cookie.split('; ').find((item) => item.startsWith('XSRF-TOKEN='));

        if (!cookie) {
          throw new Error('Cookie CSRF ausente. Verifique as configurações de sessão.');
        }

        request.headers['X-XSRF-TOKEN'] = decodeURIComponent(cookie.slice('XSRF-TOKEN='.length));

        if (method === 'PATCH' && request.body instanceof FormData) {
          request.body.set('_method', 'PATCH');
          request.method = 'POST';
        }
      }

      return request;
    },
  });
});
