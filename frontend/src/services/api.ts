// @ts-nocheck
import axios from 'axios';
import { toast } from '@/plugins/toast';

const api = axios.create({
  baseURL: import.meta.env.API_URL || '/api',
  withCredentials: true,
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
