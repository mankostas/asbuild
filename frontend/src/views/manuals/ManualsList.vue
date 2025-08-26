<template>
  <div>
    <h2 class="text-xl font-bold mb-4">Manuals</h2>
    <DashcodeServerTable
      :key="tableKey"
      :columns="columns"
      :fetcher="fetchManuals"
    >
      <template #search="{ search }">
        <div class="flex gap-2">
          <input
            v-model="search.value"
            placeholder="Search"
            class="border p-2 flex-1"
          />
          <button
            class="bg-blue-600 text-white px-4 py-2 rounded"
            @click="create"
          >
            Upload Manual
          </button>
        </div>
      </template>
      <template #actions="{ row }">
        <div class="space-x-2">
          <button class="text-blue-600" @click="download(row)">Download</button>
          <button class="text-green-600" @click="edit(row)">Edit</button>
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
const all = ref<any[]>([]);
const tableKey = ref(0);

const columns = [
  { label: 'File', field: 'file', sortable: true },
  { label: 'Category', field: 'category', sortable: true },
  { label: 'Tags', field: 'tags', sortable: false },
];

async function fetchManuals({ page, perPage, sort, search }: any) {
  if (!all.value.length) {
    const { data } = await api.get('/manuals');
    all.value = data;
  }
  let rows = all.value.slice();
  if (search) {
    const q = String(search).toLowerCase();
    rows = rows.filter((r) =>
      [r.file?.filename, r.category, ...(r.tags || [])].some((v) =>
        String(v ?? '').toLowerCase().includes(q),
      ),
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
  const paged = rows.slice(start, start + perPage).map((r: any) => ({
    id: r.id,
    file: r.file?.filename,
    category: r.category,
    tags: (r.tags || []).join(', '),
  }));
  return { rows: paged, total };
}

function reload() {
  tableKey.value++;
}

function create() {
  router.push({ name: 'manuals.create' });
}

function edit(m: any) {
  router.push({ name: 'manuals.edit', params: { id: m.id } });
}

async function download(m: any) {
  const { data } = await api.get(`/manuals/${m.id}/download`, { responseType: 'blob' });
  const url = window.URL.createObjectURL(data);
  const a = document.createElement('a');
  a.href = url;
  a.download = m.file?.filename || 'manual';
  a.click();
  window.URL.revokeObjectURL(url);
}
</script>

