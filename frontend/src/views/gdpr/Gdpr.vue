<template>
  <div class="max-w-xl mx-auto space-y-8">
    <section>
      <h1 class="text-xl font-bold mb-4">Export Data</h1>
      <button @click="exportData" class="bg-blue-500 text-white px-4 py-2 rounded">Export</button>
    </section>
    <section>
      <h1 class="text-xl font-bold mb-4">Consents</h1>
      <div
        v-for="c in consents"
        :key="c.name"
        class="flex items-center justify-between py-1"
      >
        <span class="capitalize">{{ c.name }}</span>
        <input type="checkbox" v-model="c.granted" class="w-5 h-5" />
      </div>
      <button
        @click="saveConsents"
        class="mt-2 bg-blue-500 text-white px-4 py-2 rounded"
      >
        Save
      </button>
    </section>
    <section>
      <h1 class="text-xl font-bold mb-4 text-red-600">Delete Account</h1>
      <button
        @click="requestDelete"
        class="bg-red-600 text-white px-4 py-2 rounded"
      >
        Request Deletion
      </button>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useToast } from '@/plugins/toast';
import api from '@/services/api';

interface Consent {
  name: string;
  granted: boolean;
}

const consents = ref<Consent[]>([]);
const toast = useToast();

async function load() {
  const { data } = await api.get('/gdpr/consents');
  consents.value = data;
}

async function saveConsents() {
  await api.put('/gdpr/consents', consents.value);
  toast.add({ severity: 'success', summary: 'Consents saved', detail: '' });
}

async function exportData() {
  const { data } = await api.get('/gdpr/export', { responseType: 'blob' });
  const url = window.URL.createObjectURL(new Blob([data]));
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', 'export.zip');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  window.URL.revokeObjectURL(url);
}

async function requestDelete() {
  await api.post('/gdpr/delete');
  toast.add({ severity: 'info', summary: 'Deletion queued', detail: '' });
}

onMounted(load);
</script>
