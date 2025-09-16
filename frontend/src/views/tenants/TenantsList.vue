<template>
  <div>
    <TenantsTable
      v-if="!loading"
      :rows="all"
      @view="view"
      @edit="edit"
      @delete="remove"
      @delete-selected="removeMany"
      @impersonate="impersonate"
    >
      <template #header-actions>
        <Select
          v-model="tenantFilter"
          :options="tenantOptions"
          class="w-40"
          classInput="text-xs !h-8 !min-h-0"
          :aria-label="t('tenants.label')"
        />
        <Button
          v-if="can('tenants.create')"
          :link="{ name: 'tenants.create' }"
          btnClass="btn-primary btn-sm min-w-[100px] !h-8 !py-0"
          icon="heroicons-outline:plus"
          iconClass="w-4 h-4"
          text="Add Tenant"
          aria-label="Add Tenant"
        />
      </template>
    </TenantsTable>
    <div v-else class="p-4">
      <SkeletonTable :count="10" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';
import TenantsTable from '@/components/tenants/TenantsTable.vue';
import SkeletonTable from '@/components/ui/Skeleton/Table.vue';
import Button from '@/components/ui/Button/index.vue';
import Select from '@/components/ui/Select/index.vue';
import api, { extractData } from '@/services/api';
import { useNotify } from '@/plugins/notify';
import { useAuthStore, can } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import { useI18n } from 'vue-i18n';

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
}

const router = useRouter();
const notify = useNotify();
const auth = useAuthStore();
const tenantStore = useTenantStore();
const { t } = useI18n();

const all = ref<TenantRow[]>([]);
const loading = ref(true);
const tenantFilter = ref<string | number | ''>(tenantStore.currentTenantId || '');

const tenantOptions = computed(() => [
  { value: '', label: t('allTenants') },
  ...tenantStore.tenants.map((ten: any) => ({ value: ten.id, label: ten.name })),
]);

async function refreshTenantOptions() {
  try {
    await tenantStore.loadTenants({ per_page: 100 });
  } catch (error) {
    // Ignore tenant loading errors, which are handled globally
  }
}

async function loadTenants() {
  if (!can('tenants.view')) {
    all.value = [];
    loading.value = false;
    return;
  }

  loading.value = true;
  try {
    const { data } = await api.get('/tenants', {
      params: { tenant_id: tenantFilter.value },
    });
    const tenants = extractData<any[]>(data) || [];
    all.value = tenants.map((tenant: any) => ({
      id: tenant.id,
      name: tenant.name,
      slug: tenant.slug,
      domain: tenant.domain,
      phone: tenant.phone,
      address: tenant.address,
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
    }));
  } catch (error) {
    all.value = [];
    notify.error(t('common.error'));
  } finally {
    loading.value = false;
  }
}

async function reload() {
  await Promise.all([refreshTenantOptions(), loadTenants()]);
}

onMounted(async () => {
  await refreshTenantOptions();
  await loadTenants();
});

watch(tenantFilter, () => {
  loadTenants();
});

function view(id: number | string) {
  if (!can('tenants.view')) return;
  router.push({ name: 'tenants.view', params: { id } });
}

function edit(id: number | string) {
  if (!can('tenants.update')) return;
  router.push({ name: 'tenants.edit', params: { id } });
}

async function impersonate(id: number | string) {
  if (!can('tenants.manage')) return;
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

async function remove(id: number | string) {
  if (!can('tenants.delete')) return;
  const result = await Swal.fire({
    title: 'Delete tenant?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete',
  });
  if (!result.isConfirmed) return;
  try {
    await api.delete(`/tenants/${id}`);
    await reload();
  } catch (error) {
    notify.error(t('common.error'));
  }
}

async function removeMany(ids: Array<number | string>) {
  if (!ids.length) return;
  if (!can('tenants.delete')) return;
  const res = await Swal.fire({
    title: 'Delete selected tenants?',
    icon: 'warning',
    showCancelButton: true,
  });
  if (!res.isConfirmed) return;

  let hasError = false;
  for (const id of ids) {
    try {
      await api.delete(`/tenants/${id}`);
    } catch (error) {
      hasError = true;
    }
  }

  if (hasError) {
    notify.error(t('common.error'));
  }

  await reload();
}
</script>

