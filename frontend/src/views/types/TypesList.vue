<template>
  <div>
      <div class="mb-4">
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
      <TaskTypesTable
        v-if="!loading"
        :rows="all"
        @edit="edit"
        @delete="remove"
        @copy="copy"
        @publish="publish"
        @deprecate="deprecate"
      >
        <template #header-actions>
          <button
            v-if="can('task_field_snippets.manage')"
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
        </template>
      </TaskTypesTable>
      <div v-else class="p-4">
        <SkeletonTable :count="10" />
      </div>
      <TemplatesDrawer
        v-if="can('task_field_snippets.manage')"
        :open="templatesOpen"
        :types="all"
        @close="templatesOpen = false"
        @imported="onImported"
      />
  </div>
</template>

<script setup lang="ts">
  import { ref, computed, onMounted } from 'vue';
  import { useRouter } from 'vue-router';
  import TaskTypesTable from '@/components/types/TaskTypesTable.vue';
  import Swal from 'sweetalert2';
  import SkeletonTable from '@/components/ui/Skeleton/Table.vue';
  import Icon from '@/components/ui/Icon';
  import api from '@/services/api';
  import { useAuthStore, can } from '@/stores/auth';
  import { useTenantStore } from '@/stores/tenant';
  import { useTaskTypesStore } from '@/stores/taskTypes';
  import { useTaskTypeVersionsStore } from '@/stores/taskTypeVersions';
  import TemplatesDrawer from '@/components/types/TemplatesDrawer.vue';

const router = useRouter();
const all = ref<any[]>([]);
const scope = ref<'tenant' | 'global' | 'all'>("tenant");
const auth = useAuthStore();
const tenantStore = useTenantStore();
const typesStore = useTaskTypesStore();
const versionsStore = useTaskTypeVersionsStore();
const templatesOpen = ref(false);
const loading = ref(true);

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

async function load() {
  const tenantId = auth.isSuperAdmin && scope.value !== 'all' ? tenantStore.currentTenantId : undefined;
  all.value = (await typesStore.fetch(scope.value, tenantId)).data;
  loading.value = false;
}

onMounted(load);

function reload() {
  loading.value = true;
  all.value = [];
  load();
}

function changeScope() {
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
    reload();
  }

function onImported() {
  templatesOpen.value = false;
  reload();
}

async function publish(id: number) {
  const type = all.value.find((t) => t.id === id);
  const versionId = type?.current_version?.id;
  if (!versionId) return;
  await versionsStore.publish(versionId);
  reload();
}

async function deprecate(id: number) {
  const type = all.value.find((t) => t.id === id);
  const versionId = type?.current_version?.id;
  if (!versionId) return;
  await versionsStore.deprecate(versionId);
  reload();
}
</script>
