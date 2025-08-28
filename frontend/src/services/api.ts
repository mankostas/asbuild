import axios, { AxiosError, AxiosRequestConfig } from 'axios';
import notify from '@/plugins/notify';
import { TENANT_HEADER } from '@/config/app';
import { useTenantStore } from '@/stores/tenant';

let authGetter: (() => any) | null = null;
export function registerAuthStore(getter: () => any) {
  authGetter = getter;
}

export interface ApiError {
  message: string;
  status?: number;
}

// Resolve API URL from available environment variables.
// When both a base domain (API_URL) and a path (VITE_API_URL) are provided,
// concatenate them so requests are routed through the intended API prefix.
// Vite inlines missing variables as the string "undefined", so explicitly
// guard against that case before falling back to the default `/api` path.
function cleanEnv(key: string): string | undefined {
  const value = import.meta.env[key];
  return value && value !== 'undefined' ? value : undefined;
}

const apiDomain = cleanEnv('API_URL');
const apiPath = cleanEnv('VITE_API_URL');

const envApiUrl = apiDomain
  ? apiDomain.replace(/\/$/, '') +
    (apiPath ? (apiPath.startsWith('/') ? apiPath : '/' + apiPath) : '')
  : apiPath || '/api';

const api = axios.create({
  baseURL: envApiUrl,
  withCredentials: true,
});

let csrfFetched = false;

api.interceptors.request.use(async (config) => {
  if (
    !csrfFetched &&
    config.method &&
    ['post', 'put', 'patch', 'delete'].includes(config.method.toLowerCase())
  ) {
    csrfFetched = true;
    await api.get('/sanctum/csrf-cookie', { baseURL: '/' });
  }
  const tenant = useTenantStore();
  config.headers = config.headers || {};
  if (tenant.currentTenantId) {
    config.headers[TENANT_HEADER] = tenant.currentTenantId;
  }
  if (authGetter) {
    const auth = authGetter();
    if (auth?.accessToken) {
      config.headers['Authorization'] = `Bearer ${auth.accessToken}`;
    }
  }
  return config;
});

const MAX_RETRIES = 2;
api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const config = error.config as AxiosRequestConfig & {
      __retryCount?: number;
      _retry?: boolean;
    };
    const status = error.response?.status;

    // Handle network errors with simple retries.
    if (!error.response) {
      config.__retryCount = config.__retryCount || 0;
      if (config.__retryCount < MAX_RETRIES) {
        config.__retryCount++;
        const delay = 200 * config.__retryCount;
        await new Promise((r) => setTimeout(r, delay));
        return api(config);
      }
    }

    if (status === 401) {
      if (!config._retry && authGetter) {
        config._retry = true;
        const auth = authGetter();
        if (auth.refreshToken) {
          try {
            await auth.refresh();
            config.headers = config.headers || {};
            config.headers['Authorization'] = `Bearer ${auth.accessToken}`;
            return api(config);
          } catch (e) {}
        }
      }
      notify.unauthorized();
      const auth = authGetter ? authGetter() : null;
      await auth?.logout?.(true);
      if (window.location.pathname !== '/auth/login') {
        const intent =
          window.location.pathname +
          window.location.search +
          window.location.hash;
        const redirect = intent && intent !== '/auth/login'
          ? `?redirect=${encodeURIComponent(intent)}`
          : '';
        window.location.href = `/auth/login${redirect}`;
      }
    } else if (status === 403) {
      notify.forbidden();
    } else if (status === 422) {
      return Promise.reject(error.response?.data);
    } else if (status && status >= 500) {
      notify.serverError();
    }
    const apiError: ApiError = {
      message: error.message,
      status,
    };
    return Promise.reject(apiError);
  },
);

export function extractFormErrors(error: any): Record<string, string[]> {
  return (error && (error.errors as Record<string, string[]>)) || {};
}

export default api;
