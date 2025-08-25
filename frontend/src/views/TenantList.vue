<template>
  <div>
    <h2 class="text-xl font-bold mb-4">Tenants</h2>
    <button class="bg-blue-600 text-white px-4 py-2" @click="showForm = !showForm">
      Add Tenant
    </button>
    <TenantForm v-if="showForm" @saved="load" />
    <ul class="mt-4">
      <li v-for="t in tenants" :key="t.id" class="mb-2 flex gap-2 items-center">
        <span>{{ t.name }} - {{ t.phone }} - {{ t.address }} - {{ t.quota_storage_mb }} MB</span>
        <button class="text-green-600" @click="view(t.id)">View</button>
        <button class="text-blue-600" @click="impersonate(t.id)">Impersonate</button>
        <button class="text-red-600 ml-auto" @click="remove(t.id)">Delete</button>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import TenantForm from '@/components/tenants/TenantForm.vue';
import { useAuthStore } from '@/stores/auth';

const tenants = ref<any[]>([]);
const showForm = ref(false);
const auth = useAuthStore();

async function load() {
  const { data } = await api.get('/tenants');
  tenants.value = data;
  showForm.value = false;
}

async function remove(id: number) {
  if (confirm('Delete tenant?')) {
    await api.delete(`/tenants/${id}`);
    await load();
  }
}

async function impersonate(id: number) {
  await auth.impersonate(id);
}

async function view(id: number) {
  const { data } = await api.get(`/tenants/${id}`);
  alert(`${data.name} - ${data.phone} - ${data.address}`);
}

onMounted(load);
</script>
