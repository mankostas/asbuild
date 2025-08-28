<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <div>
        <select
          v-model="scope"
          class="border rounded p-2"
        >
          <option value="tenant">Tenant</option>
          <option v-if="auth.isSuperAdmin" value="global">Global</option>
          <option v-if="auth.isSuperAdmin" value="all">All</option>
        </select>
      </div>
      <RouterLink
        class="bg-blue-600 text-white px-4 py-2 rounded flex items-center gap-2"
        :to="{ name: 'roles.create' }"
      >
        <Icon icon="heroicons-outline:plus" class="w-5 h-5" />
        Add Role
      </RouterLink>
    </div>
    <DashcodeServerTable
      :key="tableKey"
      :columns="columns"
      :fetcher="fetchRoles"
    >
      <template #actions="{ row }">
        <div v-if="row.name !== 'SuperAdmin'" class="flex gap-2">
          <button class="text-blue-600" title="Edit" @click="edit(row.id)">
            <Icon icon="heroicons-outline:pencil-square" class="w-5 h-5" />
          </button>
          <button class="text-red-600" title="Delete" @click="remove(row.id)">
            <Icon icon="heroicons-outline:trash" class="w-5 h-5" />
          </button>
          <button class="text-green-600" title="Assign" @click="openAssign(row.id)">
            <Icon icon="heroicons-outline:user-plus" class="w-5 h-5" />
          </button>
        </div>
      </template>
    </DashcodeServerTable>
    <AssignRoleModal
      v-if="assignRoleId"
      :role-id="assignRoleId"
      @close="assignRoleId = null"
      @assigned="reload"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import DashcodeServerTable from '@/components/datatable/DashcodeServerTable.vue';
import Swal from 'sweetalert2';
import Icon from '@/components/ui/Icon';
import { useNotify } from '@/plugins/notify';
import { useRolesStore } from '@/stores/roles';
import { useAuthStore } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import AssignRoleModal from './AssignRoleModal.vue';

const router = useRouter();
const notify = useNotify();
const rolesStore = useRolesStore();
const auth = useAuthStore();
const tenantStore = useTenantStore();

const tableKey = ref(0);
const scope = ref(auth.isSuperAdmin ? 'all' : 'tenant');
const assignRoleId = ref<number | null>(null);

const columns = [
  { label: 'ID', field: 'id', sortable: true },
  { label: 'Name', field: 'name', sortable: true },
  // Show the role hierarchy level in the table
  { label: 'Level', field: 'level', sortable: true },
];

async function fetchRoles({ page, perPage, sort, search }: any) {
  await rolesStore.fetch({ scope: scope.value, tenantId: tenantStore.currentTenantId });
  let rows = rolesStore.roles.slice();
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

function edit(id: number) {
  router.push({ name: 'roles.edit', params: { id } });
}

async function remove(id: number) {
  const res = await Swal.fire({
    title: 'Delete role?',
    icon: 'warning',
    showCancelButton: true,
  });
  if (!res.isConfirmed) return;
  try {
    await rolesStore.remove(id);
    reload();
  } catch (e: any) {
    notify.error('Failed to delete');
  }
}

function openAssign(id: number) {
  assignRoleId.value = id;
}

watch(scope, reload);
watch(
  () => tenantStore.currentTenantId,
  () => {
    if (scope.value !== 'global') reload();
  },
);
</script>
