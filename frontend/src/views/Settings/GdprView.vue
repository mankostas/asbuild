<template>
  <div class="max-w-xl mx-auto space-y-8">
    <section>
      <h1 class="text-xl font-bold mb-4">Export Data</h1>
      <button @click="exportData" class="bg-blue-500 text-white px-4 py-2">Export</button>
    </section>
    <section>
      <h1 class="text-xl font-bold mb-4">Consents</h1>
      <div v-for="c in consents" :key="c.name" class="flex items-center gap-2 py-1">
        <input type="checkbox" v-model="c.granted" />
        <span class="capitalize">{{ c.name }}</span>
      </div>
      <button @click="saveConsents" class="mt-2 bg-blue-500 text-white px-4 py-2">Save</button>
    </section>
    <section>
      <h1 class="text-xl font-bold mb-4 text-red-600">Delete Account</h1>
      <button @click="requestDelete" class="bg-red-600 text-white px-4 py-2">Request Deletion</button>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/services/api';

interface Consent {
  name: string;
  granted: boolean;
}

const consents = ref<Consent[]>([]);

async function load() {
  consents.value = (await api.get('/gdpr/consents')).data;
}

async function saveConsents() {
  await api.put('/gdpr/consents', consents.value);
}

async function exportData() {
  const res = await api.get('/gdpr/export', { responseType: 'blob' });
  const url = window.URL.createObjectURL(new Blob([res.data]));
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', 'export.zip');
  document.body.appendChild(link);
  link.click();
}

async function requestDelete() {
  await api.post('/gdpr/delete');
  alert('Deletion requested');
}

onMounted(load);
</script>
