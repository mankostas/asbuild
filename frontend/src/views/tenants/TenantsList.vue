<template>
  <div>
    <h2 class="text-xl font-bold mb-4">Tenants</h2>
    <DashcodeServerTable
      :key="tableKey"
      :columns="columns"
      :fetcher="fetchTenants"
    >
      <template #actions="{ row }">
        <button class="text-blue-600" @click="impersonate(row)">Impersonate</button>
      </template>
    </DashcodeServerTable>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import DashcodeServerTable from '@/components/datatable/DashcodeServerTable.vue';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const tableKey = ref(0);
const all = ref<any[]>([]);

const columns = [
  { label: 'ID', field: 'id', sortable: true },
  { label: 'Name', field: 'name', sortable: true },
  { label: 'Phone', field: 'phone', sortable: true },
  { label: 'Address', field: 'address', sortable: false },
];

async function fetchTenants({ page, perPage, sort, search }: any) {
  if (!all.value.length) {
    const { data } = await api.get('/tenants');
    all.value = data;
  }
  let rows = all.value.slice();
  if (search) {
    const q = String(search).toLowerCase();
    rows = rows.filter((r) =>
      Object.values(r).some((v) => String(v ?? '').toLowerCase().includes(q)),
    );
  }
  if (sort && sort.field) {
    rows.sort((a: any, b: any) => {
      const fa = a[sort.field] ?? '';
      const fb = b[sort.field] ?? '';
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

async function impersonate(t: any) {
  await auth.impersonate(t.id, t.name);
}
</script>

