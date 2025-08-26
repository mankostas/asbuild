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
    <ul>
      <li
        v-for="t in types"
        :key="t.id"
        class="mb-2 flex items-center gap-2"
      >
        <input
          v-model="t.name"
          class="border rounded p-1 flex-1"
        />
        <button
          class="text-green-600"
          @click="save(t)"
        >Save</button>
        <button
          class="text-blue-600"
          @click="edit(t.id)"
        >Edit</button>
        <button
          class="text-red-600"
          @click="remove(t.id)"
        >Delete</button>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';

const types = ref<any[]>([]);
const router = useRouter();

async function load() {
  const { data } = await api.get('/appointment-types');
  types.value = data;
}

async function save(t: any) {
  await api.patch(`/appointment-types/${t.id}`, { name: t.name });
}

function edit(id: number) {
  router.push({ name: 'types.edit', params: { id } });
}

async function remove(id: number) {
  if (confirm('Delete type?')) {
    await api.delete(`/appointment-types/${id}`);
    await load();
  }
}

onMounted(load);
</script>
