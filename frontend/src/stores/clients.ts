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

function resolveTenantId(value: unknown): string | undefined {
  if (value === null || value === undefined) {
    return undefined;
  }
  const normalized = String(value).trim();
  if (!normalized || normalized === SUPER_ADMIN_TENANT_ID) {
    return undefined;
  }
  return normalized;
}

function normalizeClient<T extends { id: string | number; tenant_id?: string | number | null; tenant?: { id?: string | number } | null }>(
  client: T,
): T {
  const normalized: T = {
    ...client,
    id: String(client.id),
  };
  if ('tenant_id' in normalized) {
    const tenantId = (normalized as any).tenant_id;
    (normalized as any).tenant_id = tenantId === null || tenantId === undefined ? null : String(tenantId);
  }
  if (normalized.tenant) {
    normalized.tenant = {
      ...normalized.tenant,
      id:
        normalized.tenant?.id === null || normalized.tenant?.id === undefined
          ? normalized.tenant?.id
          : String(normalized.tenant.id),
    } as any;
  }
  return normalized;
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
      tenantId: null as string | null,
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
    setTenantFilter(tenantId: string | null) {
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

      const page =
        typeof overrides.page === 'number' && Number.isFinite(overrides.page)
          ? overrides.page
          : this.pagination.page;
      const perPage =
        typeof overrides.per_page === 'number' && Number.isFinite(overrides.per_page)
          ? overrides.per_page
          : this.pagination.perPage;

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
      const tenantId = resolveTenantId(tenantCandidate);
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
      const total =
        typeof meta?.total === 'number' && Number.isFinite(meta.total)
          ? meta.total
          : this.pagination.total;
      const perPage =
        typeof meta?.per_page === 'number' && Number.isFinite(meta.per_page)
          ? meta.per_page
          : params.per_page ?? this.pagination.perPage;
      const currentPage =
        typeof meta?.current_page === 'number' && Number.isFinite(meta.current_page)
          ? meta.current_page
          : params.page ?? this.pagination.page;
      const lastPageCandidate =
        typeof (meta as any)?.last_page === 'number' && Number.isFinite((meta as any)?.last_page)
          ? (meta as any).last_page
          : undefined;
      const computedLastPage = perPage ? Math.max(1, Math.ceil((total || 0) / perPage)) : 1;

      this.pagination.total = total;
      this.pagination.perPage = perPage || this.pagination.perPage;
      this.pagination.page = currentPage || this.pagination.page;
      this.pagination.lastPage = lastPageCandidate || computedLastPage || this.pagination.lastPage;
    },
    upsertClientInState(client: Client) {
      const normalized = normalizeClient(client);
      const index = this.clients.findIndex((item) => item.id === normalized.id);
      if (index >= 0) {
        this.clients.splice(index, 1, normalized as Client);
      } else {
        this.clients.push(normalized as Client);
      }
    },
    coerceCreatePayload(payload: CreateClientPayload): CreateClientPayload {
      const auth = useAuthStore();
      const tenantStore = useTenantStore();
      const data: CreateClientPayload = { ...payload };

      if (!auth.isSuperAdmin) {
        const tenantId = resolveTenantId(tenantStore.currentTenantId);
        if (tenantId !== undefined) {
          data.tenant_id = tenantId;
        }
      } else if (data.tenant_id === undefined || data.tenant_id === null) {
        const fallbackTenant = resolveTenantId(this.filters.tenantId ?? tenantStore.currentTenantId);
        if (fallbackTenant !== undefined) {
          data.tenant_id = fallbackTenant;
        }
      } else {
        const normalized = resolveTenantId(data.tenant_id);
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
        const normalizedTenant = resolveTenantId(data.tenant_id);
        data.tenant_id = normalizedTenant === undefined ? null : normalizedTenant;
      }

      return data;
    },
    async fetch(overrides: Partial<ClientListParams> = {}) {
      this.loading = true;
      try {
        const params = this.buildListParams(overrides);
        const { data } = await clientsApi.list(params);
        this.clients = (data.data || []).map((entry) => normalizeClient(entry));
        this.applyMeta(data.meta, params);
        return data.meta;
      } finally {
        this.loading = false;
      }
    },
    async get(id: string) {
      const identifier = String(id);
      let client = this.clients.find((item) => item.id === identifier);
      if (!client) {
        const { data } = await clientsApi.get(identifier);
        client = normalizeClient(data);
        this.upsertClientInState(client as Client);
      }
      return client;
    },
    async create(payload: CreateClientPayload) {
      const body = this.coerceCreatePayload(payload);
      const { data } = await clientsApi.create(body);
      const normalized = normalizeClient(data);
      this.upsertClientInState(normalized as Client);
      return normalized as Client;
    },
    async update(id: string | number, payload: UpdateClientPayload) {
      const identifier = String(id);
      const body = this.coerceUpdatePayload(payload);
      const { data } = await clientsApi.update(identifier, body);
      const normalized = normalizeClient(data);
      this.upsertClientInState(normalized as Client);
      return normalized as Client;
    },
    async remove(id: string | number) {
      const identifier = String(id);
      await clientsApi.remove(identifier);
      this.clients = this.clients.filter((client) => client.id !== identifier);
    },
    async restore(id: string | number) {
      const identifier = String(id);
      const { data } = await clientsApi.restore(identifier);
      const normalized = normalizeClient(data);
      this.upsertClientInState(normalized as Client);
      return normalized as Client;
    },
    async archive(id: string | number) {
      const identifier = String(id);
      const { data } = await clientsApi.archive(identifier);
      const normalized = normalizeClient(data);
      this.upsertClientInState(normalized as Client);
      return normalized as Client;
    },
    async unarchive(id: string | number) {
      const identifier = String(id);
      const { data } = await clientsApi.unarchive(identifier);
      const normalized = normalizeClient(data);
      this.upsertClientInState(normalized as Client);
      return normalized as Client;
    },
    async archiveMany(ids: Array<string | number>) {
      const validIds = ids.map(String).filter((value) => Boolean(value));
      if (!validIds.length) {
        return [] as Client[];
      }
      const response = await clientsApi.bulkArchive(validIds);
      const archived = (response.data?.data ?? []).map((client: Client) =>
        normalizeClient(client) as Client,
      );
      archived.forEach((client) => {
        this.upsertClientInState(client);
      });
      return archived;
    },
    async toggleStatus(id: string | number) {
      const identifier = String(id);
      const { data } = await clientsApi.toggleStatus(identifier);
      const normalized = normalizeClient(data);
      this.upsertClientInState(normalized as Client);
      return normalized as Client;
    },
    async removeMany(ids: Array<string | number>) {
      const validIds = ids.map(String).filter((value) => Boolean(value));
      if (!validIds.length) {
        return;
      }
      await clientsApi.bulkDelete(validIds);
      this.clients = this.clients.filter((client) => !validIds.includes(client.id));
    },
  },
});
