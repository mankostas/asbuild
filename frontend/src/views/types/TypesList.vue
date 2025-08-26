<template>
  <div>
    <h2 class="text-xl font-bold mb-4">Appointment Types</h2>
    <div class="mb-4">
      <RouterLink
        class="bg-blue-600 text-white px-4 py-2 rounded"
        :to="{ name: 'types.create' }"
        >Add Type</RouterLink
      >
    </div>
    <DashcodeServerTable
      :key="tableKey"
      :columns="columns"
      :fetcher="fetchTypes"
    >
      <template #actions="{ row }">
        <div class="flex gap-2">
          <button class="text-blue-600" @click="edit(row.id)">Edit</button>
          <button class="text-red-600" @click="remove(row.id)">Delete</button>
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

const router = useRouter();
const tableKey = ref(0);
const all = ref<any[]>([]);

const columns = [
  { label: 'ID', field: 'id', sortable: true },
  { label: 'Name', field: 'name', sortable: true },
];

async function fetchTypes({ page, perPage, sort, search }: any) {
  if (!all.value.length) {
    const { data } = await api.get('/appointment-types');
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
  router.push({ name: 'types.edit', params: { id } });
}

async function remove(id: number) {
  if (confirm('Delete type?')) {
    await api.delete(`/appointment-types/${id}`);
    all.value = [];
    reload();
  }
}
</script>
