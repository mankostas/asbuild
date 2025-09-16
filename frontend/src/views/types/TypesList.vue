<template>
  <div>
      <TaskTypesTable
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
            v-if="can('task_types.manage')"
            btnClass="btn-secondary light btn-sm !h-8 !py-0"
            :aria-label="t('templates.title')"
            :text="t('templates.title')"
            @click="templatesOpen = true"
          />
          <Button
            v-if="can('task_types.create') || can('task_types.manage')"
            link="/task-types/create"
            btnClass="btn-primary btn-sm min-w-[100px] !h-8 !py-0"
            icon="heroicons-outline:plus"
            iconClass="w-4 h-4"
            :text="t('types.addType')"
            :aria-label="t('types.addType')"
          />
        </template>
      </TaskTypesTable>
      <div v-else class="p-4">
        <SkeletonTable :count="10" />
      </div>
      <TemplatesDrawer
        v-if="can('task_types.manage')"
        :open="templatesOpen"
        :types="all"
        @close="templatesOpen = false"
        @imported="onImported"
      />
  </div>
</template>

<script setup lang="ts">
  import { ref, onMounted, computed, watch } from 'vue';
  import { useRouter } from 'vue-router';
  import TaskTypesTable from '@/components/types/TaskTypesTable.vue';
  import Swal from 'sweetalert2';
  import SkeletonTable from '@/components/ui/Skeleton/Table.vue';
  import Button from '@/components/ui/Button';
  import Select from '@/components/ui/Select/index.vue';
  import api from '@/services/api';
  import { useAuthStore, can } from '@/stores/auth';
  import { useTenantStore } from '@/stores/tenant';
  import { useTaskTypesStore } from '@/stores/taskTypes';
  import TemplatesDrawer from '@/components/types/TemplatesDrawer.vue';
  import { useI18n } from 'vue-i18n';

const router = useRouter();
interface TaskType {
  id: number;
  name: string;
  tenant?: { id: number; name: string } | null;
  statuses?: Record<string, string[]>;
  tasks_count?: number;
  updated_at?: string;
  require_subtasks_complete?: boolean;
}
const all = ref<TaskType[]>([]);
const auth = useAuthStore();
const tenantStore = useTenantStore();
const typesStore = useTaskTypesStore();
const templatesOpen = ref(false);
const loading = ref(true);
const { t } = useI18n();

const scope: 'tenant' | 'all' = auth.isSuperAdmin ? 'all' : 'tenant';
const tenantFilter = ref<string | number | ''>('');

const tenantOptions = computed(() => [
  { value: '', label: 'All tenants' },
  ...tenantStore.tenants.map((t: any) => ({ value: t.id, label: t.name })),
]);

async function load() {
  await tenantStore.loadTenants({ per_page: 100 });
  let tenantId: string | number | undefined;
  let scopeParam: 'tenant' | 'all' = scope;
  if (auth.isSuperAdmin) {
    scopeParam = tenantFilter.value ? 'all' : 'all';
    tenantId = tenantFilter.value || undefined;
  } else if (scope !== 'all') {
    tenantId = tenantStore.currentTenantId;
  }
  const { data } = await typesStore.fetch(scopeParam, tenantId);
  all.value = data.map((t: any) => ({
    ...t,
    statuses: t.statuses,
    tasks_count: t.tasks_count,
    updated_at: t.updated_at,
    require_subtasks_complete: t.require_subtasks_complete,
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

function edit(id: number) {
  router.push({ name: 'taskTypes.edit', params: { id } });
}

async function remove(id: number) {
  if (!can('task_types.manage')) return;
  const res = await Swal.fire({
    title: 'Delete type?',
    icon: 'warning',
    showCancelButton: true,
  });
  if (res.isConfirmed) {
      await api.delete(`/task-types/${id}`);
      reload();
    }
  }

async function copy(id: number) {
  if (!can('task_types.manage')) return;
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
    await typesStore.copyToTenant(id, tenantId);
  reload();
}

async function removeMany(ids: number[]) {
  if (!can('task_types.manage')) return;
  const res = await Swal.fire({
    title: 'Delete selected types?',
    icon: 'warning',
    showCancelButton: true,
  });
  if (res.isConfirmed) {
    await typesStore.deleteMany(ids);
    reload();
  }
}

async function copyMany(ids: number[]) {
  if (!can('task_types.manage')) return;
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
  await typesStore.copyManyToTenant(ids, tenantId);
  reload();
}
function onImported() {
  templatesOpen.value = false;
  reload();
}
</script>
