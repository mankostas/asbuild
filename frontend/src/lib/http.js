import axios from 'axios';
import {
  API_BASE_URL,
  AUTH_HEADER,
  TENANT_HEADER,
} from '../config/app';
import { useAuthStore } from '../store/auth';
import { useTenantStore } from '../store/tenant';

const http = axios.create({
  baseURL: API_BASE_URL,
});

http.interceptors.request.use((config) => {
  const auth = useAuthStore();
  const tenant = useTenantStore();
  if (auth.token) {
    config.headers[AUTH_HEADER] = `Bearer ${auth.token}`;
  }
  if (tenant.tenantId) {
    config.headers[TENANT_HEADER] = tenant.tenantId;
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
      status: response?.status,
      message: response?.data?.message || error.message,
    };
    return Promise.reject(normalized);
  }
);

export default http;
