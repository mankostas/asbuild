import { defineStore } from 'pinia';
import clientsApi, {
  type Client,
  type ClientListParams,
  type CreateClientPayload,
  type ListMeta,
  type UpdateClientPayload,
} from '@/services/api/clients';
import { useTenantStore } from '@/stores/tenant';
import { useAuthStore } from '@/stores/auth';

const SUPER_ADMIN_TENANT_ID = 'super_admin';

interface PaginationState {
  page: number;
  perPage: number;
  total: number;
  lastPage: number;
}

interface ArchiveFilterState {
  includeArchived: boolean;
  archivedOnly: boolean;
}

interface TrashFilterState {
  includeTrashed: boolean;
  trashedOnly: boolean;
}

function normalizeNumeric(value: string | number | null | undefined): number | undefined {
  if (value === null || value === undefined || value === '') {
    return undefined;
  }
  const numeric = typeof value === 'number' ? value : Number(value);
  return Number.isFinite(numeric) ? numeric : undefined;
}

function normalizeTenantId(value: string | number | null | undefined): number | undefined {
  if (value === SUPER_ADMIN_TENANT_ID) {
    return undefined;
  }
  return normalizeNumeric(value);
}

export const useClientsStore = defineStore('clients', {
  state: () => ({
    clients: [] as Client[],
    loading: false,
    pagination: {
      page: 1,
      perPage: 20,
      total: 0,
      lastPage: 1,
    } as PaginationState,
    search: '',
    sort: 'name' as ClientListParams['sort'],
    direction: 'asc' as ClientListParams['dir'],
    filters: {
      tenantId: null as string | number | null,
    },
    archiveFilter: {
      includeArchived: false,
      archivedOnly: false,
    } as ArchiveFilterState,
    trashFilter: {
      includeTrashed: false,
      trashedOnly: false,
    } as TrashFilterState,
  }),
  actions: {
    setPage(page: number) {
      this.pagination.page = Math.max(1, page || 1);
    },
    setPerPage(perPage: number) {
      this.pagination.perPage = Math.max(1, perPage || 1);
    },
    setSearch(search: string) {
      this.search = search;
    },
    setSort(sort: NonNullable<ClientListParams['sort']>, dir: NonNullable<ClientListParams['dir']> = this.direction) {
      this.sort = sort;
      this.direction = dir;
    },
    setTenantFilter(tenantId: string | number | null) {
      const auth = useAuthStore();
      if (!auth.isSuperAdmin) {
        this.filters.tenantId = null;
        return;
      }
      this.filters.tenantId = tenantId;
    },
    setArchiveFilter(options: { include?: boolean; only?: boolean }) {
      if (typeof options.include === 'boolean') {
        this.archiveFilter.includeArchived = options.include;
        if (!options.include) {
          this.archiveFilter.archivedOnly = false;
        }
      }
      if (typeof options.only === 'boolean') {
        this.archiveFilter.archivedOnly = options.only;
        if (options.only) {
          this.archiveFilter.includeArchived = true;
        }
      }
    },
    setTrashedFilter(options: { include?: boolean; only?: boolean }) {
      if (typeof options.include === 'boolean') {
        this.trashFilter.includeTrashed = options.include;
        if (!options.include) {
          this.trashFilter.trashedOnly = false;
        }
      }
      if (typeof options.only === 'boolean') {
        this.trashFilter.trashedOnly = options.only;
        if (options.only) {
          this.trashFilter.includeTrashed = true;
        }
      }
    },
    resolveArchivedParam(): ClientListParams['archived'] {
      if (this.archiveFilter.archivedOnly) {
        return 'only';
      }
      if (this.archiveFilter.includeArchived) {
        return 'all';
      }
      return undefined;
    },
    resolveTrashedParam(): ClientListParams['trashed'] {
      if (this.trashFilter.trashedOnly) {
        return 'only';
      }
      if (this.trashFilter.includeTrashed) {
        return 'with';
      }
      return undefined;
    },
    buildListParams(overrides: Partial<ClientListParams> = {}): ClientListParams {
      const auth = useAuthStore();
      const tenantStore = useTenantStore();

      const page = normalizeNumeric(overrides.page) ?? this.pagination.page;
      const perPage = normalizeNumeric(overrides.per_page) ?? this.pagination.perPage;

      const params: ClientListParams = {
        page,
        per_page: perPage,
        search: overrides.search ?? (this.search || undefined),
        sort: overrides.sort ?? this.sort,
        dir: overrides.dir ?? this.direction,
      };

      const tenantCandidate =
        overrides.tenant_id ??
        (auth.isSuperAdmin
          ? this.filters.tenantId ?? tenantStore.currentTenantId
          : tenantStore.currentTenantId);
      const tenantId = normalizeTenantId(tenantCandidate);
      if (tenantId !== undefined) {
        params.tenant_id = tenantId;
      }

      const archivedParam = overrides.archived ?? this.resolveArchivedParam();
      if (archivedParam) {
        params.archived = archivedParam;
      }

      const trashedParam = overrides.trashed ?? this.resolveTrashedParam();
      if (trashedParam) {
        params.trashed = trashedParam;
      }

      return params;
    },
    applyMeta(meta: ListMeta | undefined, params: ClientListParams) {
      const total = normalizeNumeric(meta?.total) ?? this.pagination.total;
      const perPage = normalizeNumeric(meta?.per_page) ?? params.per_page ?? this.pagination.perPage;
      const currentPage = normalizeNumeric(meta?.current_page) ?? params.page ?? this.pagination.page;
      const lastPageCandidate = normalizeNumeric((meta as any)?.last_page);
      const computedLastPage = perPage ? Math.max(1, Math.ceil((total || 0) / perPage)) : 1;

      this.pagination.total = total;
      this.pagination.perPage = perPage || this.pagination.perPage;
      this.pagination.page = currentPage || this.pagination.page;
      this.pagination.lastPage = lastPageCandidate || computedLastPage || this.pagination.lastPage;
    },
    upsertClientInState(client: Client) {
      const index = this.clients.findIndex((item) => item.id === client.id);
      if (index >= 0) {
        this.clients.splice(index, 1, client);
      } else {
        this.clients.push(client);
      }
    },
    coerceCreatePayload(payload: CreateClientPayload): CreateClientPayload {
      const auth = useAuthStore();
      const tenantStore = useTenantStore();
      const data: CreateClientPayload = { ...payload };

      if (!auth.isSuperAdmin) {
        const tenantId = normalizeTenantId(tenantStore.currentTenantId);
        if (tenantId !== undefined) {
          data.tenant_id = tenantId;
        }
      } else if (data.tenant_id === undefined || data.tenant_id === null) {
        const fallbackTenant = normalizeTenantId(this.filters.tenantId ?? tenantStore.currentTenantId);
        if (fallbackTenant !== undefined) {
          data.tenant_id = fallbackTenant;
        }
      } else {
        const normalized = normalizeTenantId(data.tenant_id);
        data.tenant_id = normalized === undefined ? null : normalized;
      }

      return data;
    },
    coerceUpdatePayload(payload: UpdateClientPayload): UpdateClientPayload {
      const auth = useAuthStore();
      const data: UpdateClientPayload = { ...payload };

      if (!auth.isSuperAdmin) {
        delete data.tenant_id;
      } else if (data.tenant_id !== undefined) {
        const normalizedTenant = normalizeTenantId(data.tenant_id);
        data.tenant_id = normalizedTenant === undefined ? null : normalizedTenant;
      }

      return data;
    },
    async fetch(overrides: Partial<ClientListParams> = {}) {
      this.loading = true;
      try {
        const params = this.buildListParams(overrides);
        const { data } = await clientsApi.list(params);
        this.clients = data.data || [];
        this.applyMeta(data.meta, params);
        return data.meta;
      } finally {
        this.loading = false;
      }
    },
    async get(id: number | string) {
      const numericId = normalizeNumeric(id) ?? Number(id);
      let client = this.clients.find((item) => item.id === numericId);
      if (!client) {
        const { data } = await clientsApi.get(id);
        client = data;
        this.upsertClientInState(data);
      }
      return client;
    },
    async create(payload: CreateClientPayload) {
      const body = this.coerceCreatePayload(payload);
      const { data } = await clientsApi.create(body);
      this.upsertClientInState(data);
      return data;
    },
    async update(id: number | string, payload: UpdateClientPayload) {
      const body = this.coerceUpdatePayload(payload);
      const { data } = await clientsApi.update(id, body);
      this.upsertClientInState(data);
      return data;
    },
    async remove(id: number | string) {
      await clientsApi.remove(id);
      const numericId = normalizeNumeric(id) ?? Number(id);
      this.clients = this.clients.filter((client) => client.id !== numericId);
    },
    async restore(id: number | string) {
      const { data } = await clientsApi.restore(id);
      this.upsertClientInState(data);
      return data;
    },
    async archive(id: number | string) {
      const { data } = await clientsApi.archive(id);
      this.upsertClientInState(data);
      return data;
    },
    async unarchive(id: number | string) {
      const { data } = await clientsApi.unarchive(id);
      this.upsertClientInState(data);
      return data;
    },
  },
});
