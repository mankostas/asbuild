import api from '@/services/api';

export interface Client {
  id: string;
  tenant_id: string | null;
  name: string;
  email?: string | null;
  phone?: string | null;
  notes?: string | null;
  status?: 'active' | 'inactive';
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
  tenant_id?: string | null;
}

export interface CreateClientPayload {
  name: string;
  email?: string | null;
  phone?: string | null;
  notes?: string | null;
  tenant_id?: string | null;
  notify_client?: boolean;
  status?: 'active' | 'inactive';
}

export interface UpdateClientPayload {
  name?: string;
  email?: string | null;
  phone?: string | null;
  notes?: string | null;
  tenant_id?: string | null;
  status?: 'active' | 'inactive';
}

export interface ClientListResponse {
  data?: Client[];
  meta?: ListMeta;
}

const clientsApi = {
  list(params: ClientListParams = {}) {
    return api.get<ClientListResponse>('/clients', { params });
  },
  get(id: string) {
    return api.get<Client>(`/clients/${id}`);
  },
  create(payload: CreateClientPayload) {
    return api.post<Client>('/clients', payload);
  },
  update(id: string, payload: UpdateClientPayload) {
    return api.patch<Client>(`/clients/${id}`, payload);
  },
  remove(id: string) {
    return api.delete(`/clients/${id}`);
  },
  restore(id: string) {
    return api.post<Client>(`/clients/${id}/restore`);
  },
  archive(id: string) {
    return api.post<Client>(`/clients/${id}/archive`);
  },
  unarchive(id: string) {
    return api.delete<Client>(`/clients/${id}/archive`);
  },
  bulkArchive(ids: string[]) {
    return api.post<{ data: Client[] }>('/clients/bulk-archive', { ids });
  },
  bulkDelete(ids: string[]) {
    return api.post<{ message: string }>('/clients/bulk-delete', { ids });
  },
  toggleStatus(id: string) {
    return api.patch<Client>(`/clients/${id}/toggle-status`);
  },
};

export default clientsApi;
