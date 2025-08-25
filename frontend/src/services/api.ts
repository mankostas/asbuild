// @ts-nocheck
import axios from 'axios';
import { toast } from '@/plugins/toast';

// Resolve API URL from available environment variables.
// When both a base domain (API_URL) and a path (VITE_API_URL) are provided,
// concatenate them so requests are routed through the intended API prefix.
// Vite inlines missing variables as the string "undefined", so explicitly
// guard against that case before falling back to the default `/api` path.
function cleanEnv(key) {
  const value = import.meta.env[key];
  return value && value !== 'undefined' ? value : undefined;
}

const apiDomain = cleanEnv('API_URL');
const apiPath = cleanEnv('VITE_API_URL');

const envApiUrl = apiDomain
  ? apiDomain.replace(/\/$/, '') + (apiPath ? (apiPath.startsWith('/') ? apiPath : '/' + apiPath) : '')
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
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status >= 500) {
      toast.show('An unexpected server error occurred.');
    }
    return Promise.reject(error);
  }
);

export default api;
