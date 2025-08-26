<template>
  <div>
    <h2 class="text-xl font-bold mb-4">Tenants</h2>
    <table class="min-w-full border">
      <thead>
        <tr class="bg-gray-100 text-left">
          <th class="p-2 border">ID</th>
          <th class="p-2 border">Name</th>
          <th class="p-2 border">Phone</th>
          <th class="p-2 border">Address</th>
          <th class="p-2 border w-32">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="t in tenants" :key="t.id" class="border-t">
          <td class="p-2 border">{{ t.id }}</td>
          <td class="p-2 border">{{ t.name }}</td>
          <td class="p-2 border">{{ t.phone }}</td>
          <td class="p-2 border">{{ t.address }}</td>
          <td class="p-2 border">
            <button class="text-blue-600" @click="impersonate(t)">Impersonate</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';

const tenants = ref<any[]>([]);
const auth = useAuthStore();

async function load() {
  const { data } = await api.get('/tenants');
  tenants.value = data;
}

async function impersonate(t: any) {
  await auth.impersonate(t.id, t.name);
}

onMounted(load);
</script>

