<template>
  <div>
      <div class="flex items-center justify-between mb-4">
        <div>
          <select
            id="task-types-scope"
            v-model="scope"
            class="border rounded px-2 py-1"
            aria-label="Scope"
            @change="changeScope"
          >
            <option v-for="opt in scopeOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </div>
        <div class="flex gap-2">
          <button
            class="bg-gray-200 px-4 py-2 rounded"
            aria-label="Templates"
            @click="templatesOpen = true"
          >
            Templates
          </button>
          <RouterLink
            v-if="can('task_types.create') || can('task_types.manage')"
            class="bg-blue-600 text-white px-4 py-2 rounded flex items-center gap-2"
            :to="{ name: 'taskTypes.create' }"
          >
            <Icon icon="heroicons-outline:plus" class="w-5 h-5" />
            Add Type
          </RouterLink>
        </div>
      </div>
    <DashcodeServerTable
      :key="tableKey"
      :columns="columns"
      :fetcher="fetchTypes"
    >
      <template
        v-if="
          can('task_types.update') ||
          can('task_types.delete') ||
          can('task_types.create') ||
          can('task_types.manage')
        "
        #actions="{ row }"
      >
        <div class="flex gap-2">
          <button
            v-if="can('task_types.update') || can('task_types.manage')"
            class="text-blue-600"
            title="Edit"
            @click="edit(row.id)"
          >
            <Icon icon="heroicons-outline:pencil-square" class="w-5 h-5" />
          </button>
          <button
            v-if="can('task_types.delete') || can('task_types.manage')"
            class="text-red-600"
            title="Delete"
            @click="remove(row.id)"
          >
            <Icon icon="heroicons-outline:trash" class="w-5 h-5" />
          </button>
          <button
            v-if="
              (can('task_types.create') || can('task_types.manage')) &&
              (auth.isSuperAdmin || !row.tenant_id)
            "
            class="text-green-600"
            title="Copy to Tenant"
            @click="copy(row.id)"
          >
            <Icon icon="heroicons-outline:document-duplicate" class="w-5 h-5" />
          </button>
        </div>
      </template>
    </DashcodeServerTable>
    <TemplatesDrawer
      :open="templatesOpen"
      :types="all"
      @close="templatesOpen = false"
      @imported="onImported"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import DashcodeServerTable from '@/components/datatable/DashcodeServerTable.vue';
import Swal from 'sweetalert2';
import Icon from '@/components/ui/Icon';
import api from '@/services/api';
import { useAuthStore, can } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import { useTaskTypesStore } from '@/stores/taskTypes';
import TemplatesDrawer from '@/components/types/TemplatesDrawer.vue';

const router = useRouter();
const tableKey = ref(0);
const all = ref<any[]>([]);
const scope = ref<'tenant' | 'global' | 'all'>("tenant");
const auth = useAuthStore();
const tenantStore = useTenantStore();
const typesStore = useTaskTypesStore();
const templatesOpen = ref(false);

if (auth.isSuperAdmin) {
  scope.value = 'all';
}

const scopeOptions = computed(() => {
  const opts = [
    { value: 'tenant', label: 'Tenant' },
    { value: 'all', label: 'All' },
  ];
  if (auth.isSuperAdmin) {
    opts.splice(1, 0, { value: 'global', label: 'Global' });
  }
  return opts;
});

const columns = [
  { label: 'ID', field: 'id', sortable: true },
  { label: 'Name', field: 'name', sortable: true },
];

async function fetchTypes({ page, perPage, sort, search }: any) {
  if (!all.value.length) {
    const tenantId = auth.isSuperAdmin ? tenantStore.currentTenantId : undefined;
    all.value = (await typesStore.fetch(scope.value, tenantId)).data;
  }
  let rows = all.value.slice();
  if (search) {
    const q = String(search).toLowerCase();
    rows = rows.filter((r) => r.name.toLowerCase().includes(q));
  }
  if (sort && sort.field) {
    rows.sort((a: any, b: any) => {
      const fa = a[sort.field];
      const fb = b[sort.field];
      if (fa < fb) return sort.type === 'asc' ? -1 : 1;
      if (fa > fb) return sort.type === 'asc' ? 1 : -1;
      return 0;
    });
  }
  const total = rows.length;
  const start = (page - 1) * perPage;
  const paged = rows.slice(start, start + perPage);
  return { rows: paged, total };
}

function reload() {
  tableKey.value++;
}

function changeScope() {
  all.value = [];
  reload();
}

function edit(id: number) {
  router.push({ name: 'taskTypes.edit', params: { id } });
}

async function remove(id: number) {
  const res = await Swal.fire({
    title: 'Delete type?',
    icon: 'warning',
    showCancelButton: true,
  });
  if (res.isConfirmed) {
    await api.delete(`/task-types/${id}`);
    all.value = [];
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
  await typesStore.copyToTenant(id, tenantId);
  all.value = [];
  reload();
}

function onImported() {
  all.value = [];
  templatesOpen.value = false;
  reload();
}
</script>
