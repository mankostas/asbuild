import api from '@/services/api';

export interface Client {
  id: number;
  tenant_id: number | null;
  name: string;
  email?: string | null;
  phone?: string | null;
  notes?: string | null;
  archived_at?: string | null;
  deleted_at?: string | null;
}

export interface ListMeta {
  total?: number;
  per_page?: number;
  current_page?: number;
  last_page?: number;
}

export interface ClientListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort?: 'name' | 'created_at';
  dir?: 'asc' | 'desc';
  archived?: 'all' | 'only';
  trashed?: 'with' | 'only';
  tenant_id?: number | string | null;
}

export interface CreateClientPayload {
  name: string;
  email?: string | null;
  phone?: string | null;
  notes?: string | null;
  tenant_id?: number | string | null;
}

export interface UpdateClientPayload {
  name?: string;
  email?: string | null;
  phone?: string | null;
  notes?: string | null;
  tenant_id?: number | string | null;
}

export interface ClientListResponse {
  data?: Client[];
  meta?: ListMeta;
}

const clientsApi = {
  list(params: ClientListParams = {}) {
    return api.get<ClientListResponse>('/clients', { params });
  },
  get(id: number | string) {
    return api.get<Client>(`/clients/${id}`);
  },
  create(payload: CreateClientPayload) {
    return api.post<Client>('/clients', payload);
  },
  update(id: number | string, payload: UpdateClientPayload) {
    return api.patch<Client>(`/clients/${id}`, payload);
  },
  remove(id: number | string) {
    return api.delete(`/clients/${id}`);
  },
  restore(id: number | string) {
    return api.post<Client>(`/clients/${id}/restore`);
  },
  archive(id: number | string) {
    return api.post<Client>(`/clients/${id}/archive`);
  },
  unarchive(id: number | string) {
    return api.delete<Client>(`/clients/${id}/archive`);
  },
};

export default clientsApi;
