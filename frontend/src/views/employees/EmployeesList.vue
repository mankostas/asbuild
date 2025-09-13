<template>
  <div>
    <EmployeesTable
      v-if="!loading"
      :rows="all"
      @edit="edit"
      @delete="remove"
      @delete-selected="removeMany"
      @impersonate="impersonate"
      @resend-invite="resendInvite"
    >
      <template #header-actions>
        <Select
          v-if="auth.isSuperAdmin"
          v-model="tenantFilter"
          :options="tenantOptions"
          class="w-40"
          classInput="text-xs !h-8 !min-h-0"
          :aria-label="t('tenants')"
        />
        <Button
          v-if="can('employees.create') || can('employees.manage')"
          :link="{ name: 'employees.create' }"
          btnClass="btn-primary btn-sm min-w-[100px] !h-8 !py-0"
          icon="heroicons-outline:plus"
          iconClass="w-4 h-4"
          :text="t('employees.addEmployee')"
          :aria-label="t('employees.addEmployee')"
        />
      </template>
    </EmployeesTable>
    <div v-else class="p-4">
      <SkeletonTable :count="10" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import { useRouter } from 'vue-router';
import EmployeesTable from '@/components/employees/EmployeesTable.vue';
import SkeletonTable from '@/components/ui/Skeleton/Table.vue';
import Button from '@/components/ui/Button';
import Select from '@/components/ui/Select/index.vue';
import Swal from 'sweetalert2';
import api, { extractData } from '@/services/api';
import { useNotify } from '@/plugins/notify';
import { useAuthStore, can } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import { useI18n } from 'vue-i18n';
import { setTokens } from '@/services/authStorage';

interface EmployeeRow {
  id: number;
  name: string;
  email: string;
  roles: string;
  department?: string | null;
  phone?: string | null;
  status?: string | null;
  last_login_at?: string | null;
  tenant?: { id: number; name: string } | null;
  tenant_id?: number | null;
  avatar?: string | null;
}

const router = useRouter();
const notify = useNotify();
const auth = useAuthStore();
const tenantStore = useTenantStore();
const { t } = useI18n();

const all = ref<EmployeeRow[]>([]);
const loading = ref(true);
const tenantFilter = ref<string | number | ''>('');

const tenantOptions = computed(() => [
  { value: '', label: t('allTenants') },
  ...tenantStore.tenants.map((ten: any) => ({ value: ten.id, label: ten.name })),
]);

function formatRoles(roles: any[]) {
  return roles
    .filter((r: any) => r.name !== 'SuperAdmin')
    .map((r: any) => r.name)
    .join(', ');
}

async function load() {
  await tenantStore.loadTenants({ per_page: 100 });
  if (auth.isSuperAdmin) {
    if (!tenantFilter.value) {
      tenantFilter.value = tenantStore.currentTenantId || tenantStore.tenants[0]?.id || '';
    }
    if (!tenantFilter.value) {
      loading.value = false;
      all.value = [];
      return;
    }
  }
  const params: any = {};
  if (auth.isSuperAdmin && tenantFilter.value) {
    params.tenant_id = tenantFilter.value;
  }
  const { data } = await api.get('/employees', { params });
  const employees = extractData(data);
  const tenantMap = tenantStore.tenants.reduce(
    (acc: Record<number, any>, t: any) => ({ ...acc, [t.id]: t }),
    {},
  );
  all.value = employees.map((e: any) => ({
    id: e.id,
    name: e.name,
    email: e.email,
    roles: formatRoles(e.roles || []),
    department: e.department,
    phone: e.phone,
    status: e.status,
    last_login_at: e.last_login_at,
    tenant: tenantMap[e.tenant_id] || null,
    tenant_id: e.tenant_id,
    avatar: e.avatar,
  }));
  loading.value = false;
}

onMounted(load);

function reload() {
  loading.value = true;
  all.value = [];
  load();
}

watch(tenantFilter, () => {
  if (auth.isSuperAdmin) reload();
});

watch(
  () => tenantStore.currentTenantId,
  () => {
    if (!auth.isSuperAdmin) reload();
  },
);

function edit(id: number) {
  router.push({ name: 'employees.edit', params: { id } });
}

async function remove(id: number) {
  const result = await Swal.fire({
    title: 'Delete employee?',
    icon: 'warning',
    showCancelButton: true,
  });
  if (!result.isConfirmed) return;
  try {
    const params: any = {};
    if (auth.isSuperAdmin && tenantFilter.value) params.tenant_id = tenantFilter.value;
    await api.delete(`/employees/${id}`, { params });
    reload();
  } catch (e: any) {
    if (e.status === 403) {
      notify.error('Cannot delete user with SuperAdmin role');
    } else {
      notify.error('Failed to delete');
    }
  }
}

async function removeMany(ids: number[]) {
  const res = await Swal.fire({
    title: 'Delete selected employees?',
    icon: 'warning',
    showCancelButton: true,
  });
  if (res.isConfirmed) {
    for (const id of ids) {
      try {
        const params: any = {};
        if (auth.isSuperAdmin && tenantFilter.value) params.tenant_id = tenantFilter.value;
        await api.delete(`/employees/${id}`, { params });
      } catch (e: any) {
        if (e.status === 403) {
          notify.error('Cannot delete user with SuperAdmin role');
        } else {
          notify.error('Failed to delete');
        }
      }
    }
    reload();
  }
}

async function impersonate(id: number) {
  if (!can('employees.manage')) return;
  try {
    const { data } = await api.post(`/employees/${id}/impersonate`);
    auth.accessToken = data.access_token;
    auth.refreshToken = data.refresh_token;
    setTokens(data.access_token, data.refresh_token);
    api.defaults.headers.common['Authorization'] = `Bearer ${data.access_token}`;
    await auth.fetchUser();
    reload();
  } catch (e: any) {
    notify.error(t('common.error'));
  }
}

async function resendInvite(id: number) {
  if (!can('employees.manage')) return;
  try {
    await api.post(`/employees/${id}/resend-invite`);
    notify.success(t('actions.resendInvite'));
    reload();
  } catch (e: any) {
    notify.error(t('common.error'));
  }
}
</script>
