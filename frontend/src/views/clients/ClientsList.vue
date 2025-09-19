<template>
  <div class="p-4 space-y-4">
    <ClientsTable
      v-if="!showSkeleton"
      :rows="tableRows"
      :loading="loading"
      :total="pagination.total"
      :page="pagination.page"
      :per-page="pagination.perPage"
      :search="searchTerm"
      :sort="sort"
      :direction="direction"
      :selectable="canBulkManage"
      :show-tenant="auth.isSuperAdmin"
      :toggling-status-ids="togglingStatusIds"
      @update:search="handleSearch"
      @update:page="handlePageChange"
      @update:per-page="handlePerPageChange"
      @update:sort="handleSortChange"
      @selection-change="handleSelectionChange"
      @view="viewClient"
      @edit="editClient"
      @archive="confirmArchive"
      @toggle-status="handleToggleStatus"
      @restore="handleRestore"
      @delete="confirmDelete"
      @archive-selected="confirmArchiveSelected"
      @delete-selected="confirmDeleteSelected"
    >
      <template #header-actions>
        <Button
          v-if="canCreate"
          :link="{ name: 'clients.create' }"
          btnClass="btn-primary btn-sm min-w-[120px] !h-8 !py-0"
          icon="heroicons-outline:plus"
          iconClass="w-4 h-4"
          :text="t('clients.addClient')"
          :aria-label="t('clients.addClient')"
        />
      </template>

      <template #filters>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
          <ClientTenantSelect
            v-if="auth.isSuperAdmin"
            v-model="tenantFilter"
            :options="tenantOptions"
            :placeholder="t('clients.filters.tenant')"
            :aria-label="t('clients.filters.tenant')"
          />
          <ClientSortSelect
            v-model="sortSelection"
            :options="sortOptions"
            :placeholder="t('clients.filters.sort.label')"
            :aria-label="t('clients.filters.sort.label')"
          />
        </div>
        <ClientsStatusFilters
          v-model:include-archived="includeArchived"
          v-model:archived-only="archivedOnly"
          v-model:include-trashed="includeTrashed"
          v-model:trashed-only="trashedOnly"
        />
      </template>
    </ClientsTable>

    <div v-else class="p-4">
      <SkeletonTable :count="10" />
    </div>

  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import Swal from 'sweetalert2';
import ClientsTable from '@/components/clients/ClientsTable.vue';
import ClientTenantSelect from '@/components/clients/ClientTenantSelect.vue';
import ClientSortSelect from '@/components/clients/ClientSortSelect.vue';
import ClientsStatusFilters from '@/components/clients/ClientsStatusFilters.vue';
import SkeletonTable from '@/components/ui/Skeleton/Table.vue';
import Button from '@/components/ui/Button';
import { useClientsStore } from '@/stores/clients';
import { useTenantStore } from '@/stores/tenant';
import { useAuthStore, can } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import { useNotify } from '@/plugins/notify';
import type { Client, ClientListParams } from '@/services/api/clients';

interface ClientTableRow {
  id: number | string;
  name: string;
  email: string | null;
  phone: string | null;
  tenantId: number | string | null;
  tenantName: string | null;
  status: 'active' | 'inactive' | 'archived' | 'trashed';
  archivedAt: string | null;
  deletedAt: string | null;
}

const router = useRouter();
const clientsStore = useClientsStore();
const tenantStore = useTenantStore();
const auth = useAuthStore();
const notify = useNotify();
const { t } = useI18n();

const {
  clients,
  loading,
  pagination,
  search,
  sort,
  direction,
  archiveFilter,
  trashFilter,
} = storeToRefs(clientsStore);

const searchTerm = ref(search.value || '');
const selectedIds = ref<Array<number | string>>([]);
const togglingStatusIds = ref<Array<number | string>>([]);
const tenantFilter = ref<string>(
  auth.isSuperAdmin && clientsStore.filters.tenantId !== null && clientsStore.filters.tenantId !== undefined
    ? String(clientsStore.filters.tenantId)
    : auth.isSuperAdmin
    ? String(tenantStore.currentTenantId || '')
    : '',
);
const sortSelection = ref(`${sort.value}:${direction.value}`);
const hasInitialized = ref(false);
const initialLoading = ref(true);
const showSkeleton = computed(() => initialLoading.value || loading.value);
let searchDebounce: ReturnType<typeof setTimeout> | null = null;

const canCreate = computed(() => can('clients.create') || can('clients.manage'));
const canManage = computed(() => can('clients.manage'));
const canDelete = computed(() => can('clients.delete') || canManage.value);
const canBulkManage = computed(() => canDelete.value);

function normalizeSelection(ids: Array<number | string>): number[] {
  return ids
    .map((value) => (typeof value === 'number' ? value : Number(value)))
    .filter((value): value is number => Number.isFinite(value));
}

const includeArchived = computed({
  get: () => archiveFilter.value.includeArchived,
  set: (value: boolean) => {
    clientsStore.setArchiveFilter({ include: value });
    if (hasInitialized.value) {
      clientsStore.setPage(1);
      reloadClients({ page: 1 });
    }
  },
});

const archivedOnly = computed({
  get: () => archiveFilter.value.archivedOnly,
  set: (value: boolean) => {
    clientsStore.setArchiveFilter({ only: value });
    if (hasInitialized.value) {
      clientsStore.setPage(1);
      reloadClients({ page: 1 });
    }
  },
});

const includeTrashed = computed({
  get: () => trashFilter.value.includeTrashed,
  set: (value: boolean) => {
    clientsStore.setTrashedFilter({ include: value });
    if (hasInitialized.value) {
      clientsStore.setPage(1);
      reloadClients({ page: 1 });
    }
  },
});

const trashedOnly = computed({
  get: () => trashFilter.value.trashedOnly,
  set: (value: boolean) => {
    clientsStore.setTrashedFilter({ only: value });
    if (hasInitialized.value) {
      clientsStore.setPage(1);
      reloadClients({ page: 1 });
    }
  },
});

const tenantOptions = computed(() => {
  const base = auth.isSuperAdmin
    ? [{ value: '', label: t('allTenants') }]
    : [];
  const options = tenantStore.tenants.map((tenant: any) => ({
    value: String(tenant.id),
    label: tenant.name,
  }));
  return base.concat(options);
});

const tenantMap = computed(() => {
  const map = new Map<string, any>();
  tenantStore.tenants.forEach((tenant: any) => {
    map.set(String(tenant.id), tenant);
  });
  return map;
});

const tableRows = computed<ClientTableRow[]>(() =>
  clients.value.map((client: Client) => {
    const baseStatus: ClientTableRow['status'] = client.status === 'inactive' ? 'inactive' : 'active';
    const status: ClientTableRow['status'] = client.deleted_at
      ? 'trashed'
      : client.archived_at
      ? 'archived'
      : baseStatus;
    const tenantId = client.tenant_id ?? null;
    const tenant = tenantId !== null ? tenantMap.value.get(String(tenantId)) : undefined;
    return {
      id: client.id,
      name: client.name,
      email: client.email ?? null,
      phone: client.phone ?? null,
      tenantId,
      tenantName: tenant?.name ?? null,
      status,
      archivedAt: client.archived_at ?? null,
      deletedAt: client.deleted_at ?? null,
    };
  }),
);

const sortOptions = computed(() => [
  { value: 'name:asc', label: t('clients.sort.nameAsc') },
  { value: 'name:desc', label: t('clients.sort.nameDesc') },
  { value: 'created_at:desc', label: t('clients.sort.newest') },
  { value: 'created_at:asc', label: t('clients.sort.oldest') },
]);

watch(search, (value) => {
  if ((value || '') !== searchTerm.value) {
    searchTerm.value = value || '';
  }
});

watch(
  () => [sort.value, direction.value],
  ([nextSort, nextDirection]) => {
    sortSelection.value = `${nextSort}:${nextDirection}`;
  },
  { immediate: true },
);

watch(
  () => clientsStore.filters.tenantId,
  (value) => {
    if (!auth.isSuperAdmin) return;
    tenantFilter.value = value !== null && value !== undefined ? String(value) : '';
  },
);

watch(
  () => auth.isSuperAdmin,
  (isSuperAdmin) => {
    if (!isSuperAdmin) {
      tenantFilter.value = '';
      clientsStore.setTenantFilter(null);
    }
  },
  { immediate: true },
);

watch(
  () => tenantStore.currentTenantId,
  () => {
    if (auth.isSuperAdmin) return;
    if (hasInitialized.value) {
      clientsStore.setPage(1);
      reloadClients({ page: 1 });
    }
  },
);

watch(tenantFilter, (value, oldValue) => {
  if (!hasInitialized.value || value === oldValue) return;
  clientsStore.setTenantFilter(value === '' ? null : value);
  clientsStore.setPage(1);
  reloadClients({ page: 1 });
});

watch(sortSelection, (value, oldValue) => {
  if (!hasInitialized.value || value === oldValue) return;
  const [field, dir] = value.split(':');
  const directionValue = dir === 'desc' ? 'desc' : 'asc';
  clientsStore.setSort(field as NonNullable<ClientListParams['sort']>, directionValue);
  clientsStore.setPage(1);
  reloadClients({ page: 1, sort: field as ClientListParams['sort'], dir: directionValue });
});

async function reloadClients(overrides: Partial<ClientListParams> = {}) {
  try {
    await clientsStore.fetch(overrides);
    selectedIds.value = [];
    togglingStatusIds.value = [];
  } catch (error: any) {
    notify.error(error?.message || t('clients.list.loadError'));
  } finally {
    if (initialLoading.value) {
      initialLoading.value = false;
    }
  }
}

function handleSelectionChange(ids: Array<number | string>) {
  selectedIds.value = ids;
}

async function handleToggleStatus(payload: { id: number | string; active: boolean }) {
  const numericId = Number(payload.id);
  const target = clients.value.find((clientItem) => clientItem.id === numericId);
  if (!target) {
    return;
  }

  if (target.deleted_at || target.archived_at) {
    return;
  }

  const previousStatus: Client['status'] = target.status === 'inactive' ? 'inactive' : 'active';
  const nextStatus: Client['status'] = payload.active ? 'active' : 'inactive';
  if (previousStatus === nextStatus) {
    return;
  }

  const snapshot: Client = { ...target };
  togglingStatusIds.value = [...togglingStatusIds.value.filter((id) => id !== payload.id), payload.id];

  clientsStore.upsertClientInState({ ...snapshot, status: nextStatus });

  try {
    await clientsStore.toggleStatus(payload.id);
  } catch (error: any) {
    clientsStore.upsertClientInState({ ...snapshot, status: previousStatus });
    notify.error(error?.message || t('common.error'));
  } finally {
    togglingStatusIds.value = togglingStatusIds.value.filter((id) => id !== payload.id);
  }
}

function handleSearch(value: string) {
  searchTerm.value = value;
  clientsStore.setSearch(value);
  clientsStore.setPage(1);
  if (searchDebounce) {
    clearTimeout(searchDebounce);
  }
  searchDebounce = setTimeout(() => {
    reloadClients({ page: 1 });
  }, 300);
}

function handlePageChange(page: number) {
  if (page === pagination.value.page) return;
  clientsStore.setPage(page);
  reloadClients({ page });
}

function handlePerPageChange(perPage: number) {
  clientsStore.setPerPage(perPage);
  clientsStore.setPage(1);
  reloadClients({ page: 1, per_page: perPage });
}

function handleSortChange(payload: { sort: string; direction: 'asc' | 'desc' }) {
  const field = payload.sort as ClientListParams['sort'];
  const directionValue = payload.direction;
  clientsStore.setSort(field, directionValue);
  sortSelection.value = `${field}:${directionValue}`;
  reloadClients({ sort: field, dir: directionValue });
}

function viewClient(id: number | string) {
  router.push({ name: 'clients.edit', params: { id } });
}

function editClient(id: number | string) {
  router.push({ name: 'clients.edit', params: { id } });
}

async function confirmArchive(id: number | string) {
  if (!canManage.value) {
    notify.forbidden();
    return;
  }
  const result = await Swal.fire({
    title: t('clients.confirmArchive.title'),
    text: t('clients.confirmArchive.message'),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: t('clients.confirmArchive.confirm'),
    cancelButtonText: t('actions.cancel'),
  });
  if (!result.isConfirmed) return;
  try {
    await clientsStore.archive(id);
    await reloadClients();
  } catch (error: any) {
    notify.error(error?.message || t('common.error'));
  }
}

async function confirmArchiveSelected(ids: Array<number | string>) {
  if (!ids.length) return;
  if (!canManage.value) {
    notify.forbidden();
    return;
  }

  const numericIds = normalizeSelection(ids);
  if (!numericIds.length) {
    return;
  }

  const idSet = new Set(numericIds);
  const archivableIds = clients.value
    .filter(
      (clientItem) =>
        idSet.has(clientItem.id) && !clientItem.deleted_at && !clientItem.archived_at,
    )
    .map((clientItem) => clientItem.id);

  if (!archivableIds.length) {
    return;
  }

  const result = await Swal.fire({
    title: t('clients.confirmArchiveSelected.title'),
    text: t('clients.confirmArchiveSelected.message', { count: archivableIds.length }),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: t('clients.confirmArchiveSelected.confirm'),
    cancelButtonText: t('actions.cancel'),
  });

  if (!result.isConfirmed) return;

  try {
    await clientsStore.archiveMany(archivableIds);
    await reloadClients();
  } catch (error: any) {
    notify.error(error?.message || t('common.error'));
  }
}

async function handleRestore(payload: { id: number | string; type: 'archive' | 'trash' }) {
  if (!canDelete.value && !canManage.value) {
    notify.forbidden();
    return;
  }
  try {
    if (payload.type === 'trash') {
      await clientsStore.restore(payload.id);
    } else {
      await clientsStore.unarchive(payload.id);
    }
    await reloadClients();
  } catch (error: any) {
    notify.error(error?.message || t('common.error'));
  }
}

async function confirmDelete(id: number | string) {
  if (!canDelete.value) {
    notify.forbidden();
    return;
  }
  const result = await Swal.fire({
    title: t('clients.confirmDelete.title'),
    text: t('clients.confirmDelete.message'),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: t('clients.confirmDelete.confirm'),
    cancelButtonText: t('actions.cancel'),
  });
  if (!result.isConfirmed) return;
  try {
    await clientsStore.remove(id);
    await reloadClients();
  } catch (error: any) {
    notify.error(error?.message || t('common.error'));
  }
}

async function confirmDeleteSelected(ids: Array<number | string>) {
  if (!ids.length) return;
  if (!canDelete.value) {
    notify.forbidden();
    return;
  }
  const numericIds = normalizeSelection(ids);
  if (!numericIds.length) {
    return;
  }
  const result = await Swal.fire({
    title: t('clients.confirmDeleteSelected.title'),
    text: t('clients.confirmDeleteSelected.message', { count: numericIds.length }),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: t('clients.confirmDeleteSelected.confirm'),
    cancelButtonText: t('actions.cancel'),
  });
  if (!result.isConfirmed) return;
  try {
    await clientsStore.removeMany(numericIds);
    await reloadClients();
  } catch (error: any) {
    notify.error(error?.message || t('common.error'));
  }
}

onMounted(async () => {
  try {
    if (auth.isSuperAdmin) {
      await tenantStore.loadTenants({ per_page: 100 });
    }
    await reloadClients();
  } finally {
    hasInitialized.value = true;
  }
});
</script>
