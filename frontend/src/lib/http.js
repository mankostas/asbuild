import axios from 'axios';
import {
  API_BASE_URL,
  AUTH_HEADER,
  TENANT_HEADER,
} from '../config/app';
import { useAuthStore } from '../store/auth';
import { useTenantStore } from '../stores/tenant';
import notify from '@/plugins/notify';

const http = axios.create({
  baseURL: API_BASE_URL,
});

http.interceptors.request.use((config) => {
  const auth = useAuthStore();
  const tenant = useTenantStore();
  if (auth.token) {
    config.headers[AUTH_HEADER] = `Bearer ${auth.token}`;
  }
  if (tenant.currentTenantId) {
    config.headers[TENANT_HEADER] = tenant.currentTenantId;
  }
  return config;
});

http.interceptors.response.use(
  (response) => response,
  async (error) => {
    const { response, config } = error;
    const auth = useAuthStore();
    if (response && response.status === 401 && !config._retry) {
      config._retry = true;
      try {
        await auth.refresh();
        return http(config);
      } catch (err) {
        await auth.logout();
      }
    }
    const normalized = {
      status: response?.status ?? 0,
      code: response?.data?.code,
      message: response
        ? response.data?.message || error.message
        : 'Network error. Please try again.',
      details: response?.data?.details,
    };
    notify.error(normalized.message);
    return Promise.reject(normalized);
  }
);

export default http;
