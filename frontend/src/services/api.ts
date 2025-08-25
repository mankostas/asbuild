// @ts-nocheck
import axios from 'axios';
import { toast } from '@/plugins/toast';

// Resolve API URL from available environment variables.
// Vite inlines missing variables as the string "undefined", so explicitly
// guard against that case before falling back to the default `/api` path.
const envApiUrl =
  (import.meta.env.API_URL && import.meta.env.API_URL !== 'undefined'
    ? import.meta.env.API_URL
    : undefined) ||
  (import.meta.env.VITE_API_URL && import.meta.env.VITE_API_URL !== 'undefined'
    ? import.meta.env.VITE_API_URL
    : undefined) ||
  '/api';

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
