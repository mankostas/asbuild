<template>
  <div class="p-4 space-y-4">
    <TenantsTable
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
      @update:search="handleSearch"
      @update:page="handlePageChange"
      @update:per-page="handlePerPageChange"
      @update:sort="handleSortChange"
      @selection-change="handleSelectionChange"
      @view="view"
      @edit="edit"
      @archive="confirmArchive"
      @unarchive="confirmUnarchive"
      @restore="handleRestore"
      @delete="confirmDelete"
      @delete-selected="confirmDeleteSelected"
      @archive-selected="confirmArchiveSelected"
      @impersonate="impersonate"
      @owner-resend-invite="resendOwnerInvite"
      @owner-reset-email="resetOwnerEmail"
      @owner-password-reset="sendOwnerPasswordReset"
    >
      <template #header-actions>
        <Button
          v-if="canCreate"
          btnClass="btn-primary btn-sm min-w-[120px] !h-8 !py-0"
          icon="heroicons-outline:plus"
          iconClass="w-4 h-4"
          :text="t('tenants.list.addTenant')"
          :aria-label="t('tenants.list.addTenant')"
          type="button"
          @click="openCreateTenantModal"
        />
      </template>

      <template #filters>
        <ClientsStatusFilters
          v-model:include-archived="includeArchived"
          v-model:archived-only="archivedOnly"
          v-model:include-trashed="includeTrashed"
          v-model:trashed-only="trashedOnly"
          :include-archived-label="t('tenants.filters.includeArchived')"
          :archived-only-label="t('tenants.filters.archivedOnly')"
          :include-trashed-label="t('tenants.filters.includeTrashed')"
          :trashed-only-label="t('tenants.filters.trashedOnly')"
        />
      </template>
    </TenantsTable>

    <div v-else class="p-4">
      <SkeletonTable :count="10" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';
import TenantsTable from '@/components/tenants/TenantsTable.vue';
import ClientsStatusFilters from '@/components/clients/ClientsStatusFilters.vue';
import SkeletonTable from '@/components/ui/Skeleton/Table.vue';
import Button from '@/components/ui/Button';
import api, { extractData } from '@/services/api';
import { useNotify } from '@/plugins/notify';
import { useAuthStore, can } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import { useTenantModalStore } from '@/stores/tenantModal';
import { useI18n } from 'vue-i18n';

type SortDirection = 'asc' | 'desc';

type ArchiveFilterParam = 'all' | 'only';
type TrashFilterParam = 'with' | 'only';

interface TenantOwner {
  id: number | string;
  name?: string | null;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  last_login_at?: string | null;
}

interface TenantRow {
  id: number | string;
  name: string;
  slug?: string | null;
  domain?: string | null;
  phone?: string | null;
  address?: string | null;
  features?: string[] | null;
  feature_count?: number | null;
  features_count?: number | null;
  owner?: TenantOwner | null;
  archived_at?: string | null;
  deleted_at?: string | null;
}

interface TenantListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort?: string;
  dir?: SortDirection;
  archived?: ArchiveFilterParam;
  trashed?: TrashFilterParam;
}

const router = useRouter();
const notify = useNotify();
const auth = useAuthStore();
const tenantStore = useTenantStore();
const tenantModal = useTenantModalStore();
const { t } = useI18n();

const all = ref<TenantRow[]>([]);
const loading = ref(false);
const initialLoading = ref(true);
const hasInitialized = ref(false);

const pagination = reactive({
  page: 1,
  perPage: 10,
  total: 0,
});

const searchTerm = ref('');
const sort = ref('name');
const direction = ref<SortDirection>('asc');

const includeArchived = ref(false);
const archivedOnly = ref(false);
const includeTrashed = ref(false);
const trashedOnly = ref(false);

const selectedIds = ref<Array<number | string>>([]);

const showSkeleton = computed(() => initialLoading.value || loading.value);
const tableRows = computed(() => all.value);

const canCreate = computed(() => can('tenants.create'));
const canManage = computed(() => can('tenants.manage'));
const canUpdate = computed(() => can('tenants.update') || canManage.value);
const canDelete = computed(() => can('tenants.delete') || canManage.value);
const canBulkManage = computed(() => canUpdate.value || canDelete.value);
const canViewTenants = computed(
  () => can('tenants.view') || canManage.value || canUpdate.value || canDelete.value,
);

let searchDebounce: ReturnType<typeof setTimeout> | null = null;
let filterReloadTimeout: ReturnType<typeof setTimeout> | null = null;

function openCreateTenantModal(): void {
  const target = router.resolve({ name: 'tenants.create' });
  tenantModal.open(target);
}

function normalizeSelection(ids: Array<number | string>): number[] {
  return ids
    .map((value) => (typeof value === 'number' ? value : Number(value)))
    .filter((value): value is number => Number.isFinite(value));
}

function resolveArchivedParam(): ArchiveFilterParam | undefined {
  if (archivedOnly.value) {
    return 'only';
  }
  if (includeArchived.value) {
    return 'all';
  }
  return undefined;
}

function resolveTrashedParam(): TrashFilterParam | undefined {
  if (trashedOnly.value) {
    return 'only';
  }
  if (includeTrashed.value) {
    return 'with';
  }
  return undefined;
}

async function fetchTenantOwners(rows: TenantRow[]): Promise<Record<string, TenantOwner | null>> {
  if (!rows.length || !canManage.value) {
    return {};
  }

  const entries = await Promise.all(
    rows.map(async (tenant) => {
      const tenantId = tenant?.id;
      if (tenantId === undefined || tenantId === null) {
        return [null, null] as const;
      }

      try {
        const response = await api.get(`/tenants/${tenantId}/owner`);
        const owner = extractData<TenantOwner | null>(response.data) || null;
        return [String(tenantId), owner] as const;
      } catch {
        return [String(tenantId), tenant.owner ?? null] as const;
      }
    }),
  );

  return entries.reduce<Record<string, TenantOwner | null>>((acc, [id, owner]) => {
    if (id) {
      acc[id] = owner;
    }
    return acc;
  }, {});
}

async function refreshTenantCache() {
  try {
    await tenantStore.loadTenants({ per_page: 100 });
  } catch (error) {
    // Ignore tenant loading errors, which are handled globally
  }
}

async function reloadTenants(overrides: Partial<TenantListParams> = {}) {
  if (!canViewTenants.value) {
    all.value = [];
    pagination.page = 1;
    pagination.perPage = 10;
    pagination.total = 0;
    loading.value = false;
    if (initialLoading.value) {
      initialLoading.value = false;
    }
    return;
  }

  const pageParam = overrides.page ?? pagination.page ?? 1;
  const perPageParam = overrides.per_page ?? pagination.perPage ?? 10;
  const sortField = overrides.sort ?? sort.value ?? 'name';
  const sortDir = overrides.dir ?? direction.value ?? 'asc';
  const searchValue =
    overrides.search !== undefined ? overrides.search : searchTerm.value;
  const trimmedSearch =
    typeof searchValue === 'string' ? searchValue.trim() : '';

  const params: TenantListParams = {
    page: pageParam,
    per_page: perPageParam,
    sort: sortField,
    dir: sortDir === 'desc' ? 'desc' : 'asc',
  };

  searchTerm.value = typeof searchValue === 'string' ? searchValue : '';
  if (trimmedSearch) {
    params.search = trimmedSearch;
  }

  const archivedParam = overrides.archived ?? resolveArchivedParam();
  if (archivedParam) {
    params.archived = archivedParam;
  }

  const trashedParam = overrides.trashed ?? resolveTrashedParam();
  if (trashedParam) {
    params.trashed = trashedParam;
  }

  loading.value = true;
  try {
    const response = await api.get('/tenants', { params });
    const tenants = extractData<any[]>(response.data) || [];

    const mapped: TenantRow[] = tenants.map((tenant: any) => ({
      id: tenant.id,
      name: tenant.name,
      slug: tenant.slug ?? null,
      domain: tenant.domain ?? null,
      phone: tenant.phone ?? null,
      address: tenant.address ?? null,
      features: tenant.features ?? null,
      feature_count:
        typeof tenant.feature_count === 'number'
          ? tenant.feature_count
          : typeof tenant.features_count === 'number'
          ? tenant.features_count
          : Array.isArray(tenant.features)
          ? tenant.features.length
          : null,
      features_count:
        typeof tenant.features_count === 'number'
          ? tenant.features_count
          : undefined,
      owner: tenant.owner ?? null,
      archived_at: tenant.archived_at ?? null,
      deleted_at: tenant.deleted_at ?? null,
    }));

    const owners = await fetchTenantOwners(mapped);
    if (Object.keys(owners).length) {
      mapped.forEach((tenant) => {
        const owner = owners[String(tenant.id)];
        if (owner !== undefined) {
          tenant.owner = owner;
        }
      });
    }

    all.value = mapped;
    selectedIds.value = [];

    const meta = response.data?.meta ?? {};
    const totalValue = Number(meta.total);
    const perPageValue = Number(meta.per_page);
    const pageValue = Number(meta.page ?? meta.current_page);

    pagination.total = Number.isFinite(totalValue) ? totalValue : tenants.length;
    pagination.perPage =
      Number.isFinite(perPageValue) && perPageValue > 0
        ? perPageValue
        : perPageParam;
    pagination.page =
      Number.isFinite(pageValue) && pageValue > 0 ? pageValue : pageParam;

    sort.value = params.sort || 'name';
    direction.value = params.dir === 'desc' ? 'desc' : 'asc';
  } catch (error: any) {
    all.value = [];
    notify.error(error?.message || t('common.error'));
  } finally {
    loading.value = false;
    if (initialLoading.value) {
      initialLoading.value = false;
    }
  }
}

function queueFilterReload() {
  if (!hasInitialized.value) {
    return;
  }
  if (filterReloadTimeout) {
    clearTimeout(filterReloadTimeout);
  }
  filterReloadTimeout = setTimeout(() => {
    pagination.page = 1;
    selectedIds.value = [];
    reloadTenants({ page: 1 });
    filterReloadTimeout = null;
  }, 0);
}

watch(includeArchived, (value) => {
  if (!value && archivedOnly.value) {
    archivedOnly.value = false;
  }
  queueFilterReload();
});

watch(archivedOnly, (value) => {
  if (value && !includeArchived.value) {
    includeArchived.value = true;
  }
  queueFilterReload();
});

watch(includeTrashed, (value) => {
  if (!value && trashedOnly.value) {
    trashedOnly.value = false;
  }
  queueFilterReload();
});

watch(trashedOnly, (value) => {
  if (value && !includeTrashed.value) {
    includeTrashed.value = true;
  }
  queueFilterReload();
});

function handleSelectionChange(ids: Array<number | string>) {
  selectedIds.value = ids;
}

function handleSearch(value: string) {
  if (searchDebounce) {
    clearTimeout(searchDebounce);
  }
  searchTerm.value = value;
  pagination.page = 1;
  selectedIds.value = [];
  searchDebounce = setTimeout(() => {
    reloadTenants({ page: 1, search: value });
  }, 300);
}

function handlePageChange(page: number) {
  if (page === pagination.page) return;
  pagination.page = page;
  reloadTenants({ page });
}

function handlePerPageChange(perPage: number) {
  if (!Number.isFinite(perPage) || perPage <= 0) {
    return;
  }
  pagination.perPage = perPage;
  pagination.page = 1;
  selectedIds.value = [];
  reloadTenants({ page: 1, per_page: perPage });
}

function handleSortChange(payload: { sort: string; direction: SortDirection }) {
  const field = payload.sort || 'name';
  const nextDirection = payload.direction === 'desc' ? 'desc' : 'asc';
  sort.value = field;
  direction.value = nextDirection;
  selectedIds.value = [];
  reloadTenants({ sort: field, dir: nextDirection });
}

function view(id: number | string) {
  if (!canViewTenants.value) return;
  router.push({ name: 'tenants.view', params: { id } });
}

function edit(id: number | string) {
  if (!canUpdate.value) return;
  router.push({ name: 'tenants.edit', params: { id } });
}

async function impersonate(id: number | string) {
  if (!canManage.value) return;
  const tenantId = String(id);
  const tenant =
    all.value.find((t) => String(t.id) === tenantId) ||
    tenantStore.tenants.find((t: any) => String(t.id) === tenantId);
  try {
    await auth.impersonate(tenantId, tenant?.name || tenantId);
    window.location.reload();
  } catch (error) {
    notify.error(t('common.error'));
  }
}

async function refreshAfterMutation() {
  selectedIds.value = [];
  await Promise.all([refreshTenantCache(), reloadTenants()]);
}

async function confirmArchive(id: number | string) {
  if (!canUpdate.value) {
    notify.forbidden();
    return;
  }

  const result = await Swal.fire({
    title: t('tenants.confirmArchive.title'),
    text: t('tenants.confirmArchive.message'),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: t('tenants.confirmArchive.confirm'),
    cancelButtonText: t('actions.cancel'),
  });

  if (!result.isConfirmed) return;

  try {
    await api.post(`/tenants/${id}/archive`);
    notify.success(t('tenants.notifications.archived'));
    await refreshAfterMutation();
  } catch (error: any) {
    notify.error(error?.message || t('common.error'));
  }
}

async function confirmUnarchive(id: number | string) {
  if (!canUpdate.value) {
    notify.forbidden();
    return;
  }

  try {
    await api.delete(`/tenants/${id}/archive`);
    notify.success(t('tenants.notifications.unarchived'));
    await refreshAfterMutation();
  } catch (error: any) {
    notify.error(error?.message || t('common.error'));
  }
}

async function handleRestore(id: number | string) {
  if (!canUpdate.value) {
    notify.forbidden();
    return;
  }

  try {
    await api.post(`/tenants/${id}/restore`);
    notify.success(t('tenants.notifications.restored'));
    await refreshAfterMutation();
  } catch (error: any) {
    notify.error(error?.message || t('common.error'));
  }
}

async function confirmArchiveSelected(ids: Array<number | string>) {
  if (!ids.length) return;
  if (!canUpdate.value) {
    notify.forbidden();
    return;
  }

  const numericIds = normalizeSelection(ids);
  if (!numericIds.length) {
    return;
  }

  const idSet = new Set(numericIds.map((value) => String(value)));
  const archivableIds = all.value
    .filter(
      (tenant) =>
        idSet.has(String(tenant.id)) && !tenant.deleted_at && !tenant.archived_at,
    )
    .map((tenant) => Number(tenant.id))
    .filter((value) => Number.isFinite(value));

  if (!archivableIds.length) {
    return;
  }

  const result = await Swal.fire({
    title: t('tenants.confirmArchiveSelected.title'),
    text: t('tenants.confirmArchiveSelected.message', { count: archivableIds.length }),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: t('tenants.confirmArchiveSelected.confirm'),
    cancelButtonText: t('actions.cancel'),
  });

  if (!result.isConfirmed) return;

  try {
    await api.post('/tenants/bulk-archive', { ids: archivableIds });
    notify.success(
      t('tenants.notifications.bulkArchived', { count: archivableIds.length }),
    );
    await refreshAfterMutation();
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
    title: t('tenants.confirmDelete.title'),
    text: t('tenants.confirmDelete.message'),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: t('tenants.confirmDelete.confirm'),
    cancelButtonText: t('actions.cancel'),
  });

  if (!result.isConfirmed) return;

  try {
    await api.delete(`/tenants/${id}`);
    notify.success(t('tenants.notifications.deleted'));
    await refreshAfterMutation();
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
    title: t('tenants.confirmDeleteSelected.title'),
    text: t('tenants.confirmDeleteSelected.message', { count: numericIds.length }),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: t('tenants.confirmDeleteSelected.confirm'),
    cancelButtonText: t('actions.cancel'),
  });

  if (!result.isConfirmed) return;

  try {
    await api.post('/tenants/bulk-delete', { ids: numericIds });
    notify.success(
      t('tenants.notifications.bulkDeleted', { count: numericIds.length }),
    );
    await refreshAfterMutation();
  } catch (error: any) {
    notify.error(error?.message || t('common.error'));
  }
}

async function resendOwnerInvite(id: number | string) {
  if (!canManage.value) return;

  try {
    await api.post(`/tenants/${id}/owner/invite-resend`);
    notify.success(t('tenants.owner.inviteResent'));
    await refreshAfterMutation();
  } catch (error: any) {
    notify.error(error?.message || t('common.error'));
  }
}

async function sendOwnerPasswordReset(id: number | string) {
  if (!canManage.value) return;

  try {
    await api.post(`/tenants/${id}/owner/password-reset`);
    notify.success(t('tenants.owner.passwordReset.success'));
    await refreshAfterMutation();
  } catch (error: any) {
    notify.error(error?.message || t('common.error'));
  }
}

async function resetOwnerEmail(id: number | string) {
  if (!canManage.value) return;

  const tenantId = String(id);
  const tenant =
    all.value.find((t) => String(t.id) === tenantId) ||
    tenantStore.tenants.find((t: any) => String(t.id) === tenantId);

  const result = await Swal.fire({
    title: t('tenants.owner.resetEmail.title'),
    input: 'email',
    inputLabel: t('tenants.owner.resetEmail.label'),
    inputValue: tenant?.owner?.email || tenant?.email || '',
    inputPlaceholder: t('tenants.owner.resetEmail.placeholder'),
    showCancelButton: true,
    confirmButtonText: t('tenants.owner.resetEmail.confirm'),
    cancelButtonText: t('actions.cancel'),
    preConfirm: (value) => {
      if (!value) {
        Swal.showValidationMessage(t('tenants.owner.resetEmail.required'));
      }
      return value;
    },
  });

  const email = typeof result.value === 'string' ? result.value.trim() : '';

  if (!result.isConfirmed || !email) {
    return;
  }

  try {
    await api.post(`/tenants/${tenantId}/owner/email-reset`, { email });
    notify.success(t('tenants.owner.resetEmail.success'));
    await refreshAfterMutation();
  } catch (error: any) {
    notify.error(error?.message || t('common.error'));
  }
}

onMounted(async () => {
  try {
    await refreshTenantCache();
    await reloadTenants();
  } finally {
    hasInitialized.value = true;
  }
});

onUnmounted(() => {
  if (searchDebounce) {
    clearTimeout(searchDebounce);
  }
  if (filterReloadTimeout) {
    clearTimeout(filterReloadTimeout);
  }
});
</script>
