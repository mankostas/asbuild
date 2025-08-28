<template>
  <div>
    <div class="flex items-center justify-end mb-4">
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
        </div>
      </template>
    </DashcodeServerTable>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import DashcodeServerTable from '@/components/datatable/DashcodeServerTable.vue';
import api from '@/services/api';
import Swal from 'sweetalert2';
import Icon from '@/components/ui/Icon';
import { useNotify } from '@/plugins/notify';

const router = useRouter();
const notify = useNotify();
const tableKey = ref(0);
const all = ref<any[]>([]);

const columns = [
  { label: 'ID', field: 'id', sortable: true },
  { label: 'Name', field: 'name', sortable: true },
];

async function fetchRoles({ page, perPage, sort, search }: any) {
  if (!all.value.length) {
    const { data } = await api.get('/roles');
    all.value = data;
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
    await api.delete(`/roles/${id}`);
    all.value = [];
    reload();
  } catch (e: any) {
    notify.error('Failed to delete');
  }
}
</script>
