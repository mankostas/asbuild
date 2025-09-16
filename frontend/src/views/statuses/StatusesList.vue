<template>
  <div>
    <TaskStatusesTable
      v-if="!loading"
      :rows="all"
      @edit="edit"
      @delete="remove"
      @copy="copy"
      @delete-selected="removeMany"
      @copy-selected="copyMany"
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
          v-if="can('task_statuses.manage')"
          link="/task-statuses/create"
          btnClass="btn-primary btn-sm min-w-[100px] !h-8 !py-0"
          icon="heroicons-outline:plus"
          iconClass="w-4 h-4"
          :text="t('statuses.addStatus')"
          :aria-label="t('statuses.addStatus')"
        />
      </template>
    </TaskStatusesTable>
    <div v-else class="p-4">
      <SkeletonTable :count="10" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import TaskStatusesTable from '@/components/statuses/TaskStatusesTable.vue';
import SkeletonTable from '@/components/ui/Skeleton/Table.vue';
import Button from '@/components/ui/Button';
import Select from '@/components/ui/Select/index.vue';
import Swal from 'sweetalert2';
import api from '@/services/api';
import { useAuthStore, can } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import { useTaskStatusesStore } from '@/stores/taskStatuses';
import { useI18n } from 'vue-i18n';
import { useNotify } from '@/plugins/notify';

interface TaskStatus {
  id: number;
  name: string;
  slug: string;
  color: string;
  position: number;
  tasks_count: number;
  created_at: string;
  updated_at: string;
  tenant?: { id: number; name: string } | null;
  tenant_id?: number | null;
}

const router = useRouter();
const all = ref<TaskStatus[]>([]);
const loading = ref(true);
const scope = ref<'tenant' | 'global' | 'all'>('tenant');
const tenantFilter = ref<string | number | ''>('');
const auth = useAuthStore();
const tenantStore = useTenantStore();
const statusesStore = useTaskStatusesStore();
const { t } = useI18n();
const notify = useNotify();

if (auth.isSuperAdmin) {
  scope.value = 'all';
}

const tenantOptions = computed(() => [
  { value: '', label: t('allTenants') },
  ...tenantStore.tenants.map((t: any) => ({ value: t.id, label: t.name })),
]);

async function load() {
  const isFilteringByTenant = auth.isSuperAdmin && tenantFilter.value !== '';
  const scopeParam: 'tenant' | 'global' | 'all' = isFilteringByTenant
    ? 'tenant'
    : scope.value;
  const tenantId: string | number | undefined = isFilteringByTenant
    ? tenantFilter.value
    : undefined;

  const { data } = await statusesStore.fetch({
    scope: scopeParam,
    tenant_id: tenantId,
  });
  await tenantStore.loadTenants({ per_page: 100 });
  const tenantMap = tenantStore.tenants.reduce(
    (acc: Record<number, any>, t: any) => ({ ...acc, [t.id]: t }),
    {},
  );
  all.value = data.map((s: any) => ({
    id: s.id,
    name: s.name,
    slug: s.slug,
    color: s.color,
    position: s.position,
    tasks_count: s.tasks_count,
    created_at: s.created_at,
    updated_at: s.updated_at,
    tenant: s.tenant || tenantMap[s.tenant_id] || null,
    tenant_id: s.tenant_id,
  }));
  loading.value = false;
}

onMounted(load);

function reload() {
  loading.value = true;
  all.value = [];
  load();
}

watch(tenantFilter, reload);

watch(
  () => tenantStore.currentTenantId,
  () => {
    if (scope.value !== 'global') {
      all.value = [];
      reload();
    }
  },
);

function edit(id: number) {
  router.push({ name: 'taskStatuses.edit', params: { id } });
}

function ensureCanManage(): boolean {
  if (can('task_statuses.manage')) {
    return true;
  }
  notify.forbidden();
  return false;
}

async function remove(id: number) {
  if (!ensureCanManage()) return;
  const res = await Swal.fire({
    title: 'Delete status?',
    icon: 'warning',
    showCancelButton: true,
  });
  if (res.isConfirmed) {
    await api.delete(`/task-statuses/${id}`);
    reload();
  }
}

async function copy(id: number) {
  if (!ensureCanManage()) return;
  let tenantId: string | number | undefined;
  if (auth.isSuperAdmin) {
    await tenantStore.loadTenants();
    const inputOptions = tenantStore.tenants.reduce(
      (acc: any, t: any) => ({ ...acc, [t.id]: t.name }),
      {},
    );
    const res = await Swal.fire({
      title: 'Copy to tenant',
      input: 'select',
      inputOptions,
      showCancelButton: true,
    });
    if (!res.isConfirmed || !res.value) return;
    tenantId = res.value;
  }
  await statusesStore.copyToTenant(id, tenantId);
  reload();
}

async function removeMany(ids: number[]) {
  if (!ensureCanManage()) return;
  const res = await Swal.fire({
    title: 'Delete selected statuses?',
    icon: 'warning',
    showCancelButton: true,
  });
  if (res.isConfirmed) {
    await statusesStore.deleteMany(ids);
    reload();
  }
}

async function copyMany(ids: number[]) {
  if (!ensureCanManage()) return;
  let tenantId: string | number | undefined;
  if (auth.isSuperAdmin) {
    await tenantStore.loadTenants();
    const inputOptions = tenantStore.tenants.reduce(
      (acc: any, t: any) => ({ ...acc, [t.id]: t.name }),
      {},
    );
    const res = await Swal.fire({
      title: 'Copy to tenant',
      input: 'select',
      inputOptions,
      showCancelButton: true,
    });
    if (!res.isConfirmed || !res.value) return;
    tenantId = res.value;
  }
  await statusesStore.copyManyToTenant(ids, tenantId);
  reload();
}
</script>
