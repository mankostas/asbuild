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
        <Button
          v-if="can('task_statuses.create') || can('task_statuses.manage')"
          link="/task-statuses/create"
          btnClass="btn-primary btn-sm min-w-[100px]"
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
import { ref, watch, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import TaskStatusesTable from '@/components/statuses/TaskStatusesTable.vue';
import SkeletonTable from '@/components/ui/Skeleton/Table.vue';
import Button from '@/components/ui/Button';
import Swal from 'sweetalert2';
import api from '@/services/api';
import { useAuthStore, can } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import { useTaskStatusesStore } from '@/stores/taskStatuses';
import { useI18n } from 'vue-i18n';

interface TaskStatus {
  id: number;
  name: string;
  tenant?: { id: number; name: string } | null;
  tenant_id?: number | null;
}

const router = useRouter();
const all = ref<TaskStatus[]>([]);
const loading = ref(true);
const scope = ref<'tenant' | 'global' | 'all'>('tenant');
const auth = useAuthStore();
const tenantStore = useTenantStore();
const statusesStore = useTaskStatusesStore();
const { t } = useI18n();

if (auth.isSuperAdmin) {
  scope.value = 'all';
}

async function load() {
  const tenantId =
    auth.isSuperAdmin && scope.value !== 'all'
      ? tenantStore.currentTenantId
      : undefined;
  const { data } = await statusesStore.fetch(scope.value, tenantId);
  await tenantStore.loadTenants({ per_page: 100 });
  const tenantMap = tenantStore.tenants.reduce(
    (acc: Record<number, any>, t: any) => ({ ...acc, [t.id]: t }),
    {},
  );
  all.value = data.map((s: any) => ({
    ...s,
    tenant: s.tenant || tenantMap[s.tenant_id] || null,
  }));
  loading.value = false;
}

onMounted(load);

function reload() {
  loading.value = true;
  all.value = [];
  load();
}

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

async function remove(id: number) {
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
