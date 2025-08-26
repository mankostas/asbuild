<template>
  <div>
    <h2 class="text-xl font-bold mb-4">Manuals</h2>
    <div class="flex gap-2 mb-4">
      <input
        v-model="q"
        placeholder="Search"
        class="border p-2 flex-1"
        @input="load"
      />
      <button
        class="bg-blue-600 text-white px-4 py-2 rounded"
        @click="create"
      >
        Upload Manual
      </button>
    </div>
    <table class="w-full border">
      <thead>
        <tr class="bg-gray-100 text-left">
          <th class="p-2 border">File</th>
          <th class="p-2 border">Category</th>
          <th class="p-2 border">Tags</th>
          <th class="p-2 border">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="m in manuals" :key="m.id">
          <td class="border p-2">{{ m.file?.filename }}</td>
          <td class="border p-2">{{ m.category }}</td>
          <td class="border p-2">{{ (m.tags || []).join(', ') }}</td>
          <td class="border p-2 space-x-2">
            <button class="text-blue-600" @click="download(m)">Download</button>
            <button class="text-green-600" @click="edit(m)">Edit</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';

const manuals = ref<any[]>([]);
const q = ref('');
const router = useRouter();

async function load() {
  const { data } = await api.get('/manuals', q.value ? { params: { q: q.value } } : undefined);
  manuals.value = data;
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

onMounted(load);
</script>

