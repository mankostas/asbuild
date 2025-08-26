<template>
  <div class="max-w-xl mx-auto space-y-8">
    <section>
      <h1 class="text-xl font-bold mb-4">Export Data</h1>
      <Button btnClass="btn-dark" @click="exportData">Export</Button>
    </section>
    <section>
      <h1 class="text-xl font-bold mb-4">Consents</h1>
      <Card>
        <table class="w-full border-collapse text-sm">
          <thead>
            <tr class="text-left">
              <th class="p-4">Consent</th>
              <th class="p-4">Granted</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="c in consents"
              :key="c.name"
              class="border-t border-slate-100 dark:border-slate-700"
            >
              <td class="p-4 capitalize">{{ c.name }}</td>
              <td class="p-4"><Checkbox v-model="c.granted" /></td>
            </tr>
          </tbody>
        </table>
      </Card>
      <Button btnClass="btn-dark" :isDisabled="!dirty" class="mt-4" @click="saveConsents"
        >Save</Button
      >
    </section>
    <section>
      <h1 class="text-xl font-bold mb-4 text-red-600">Delete Account</h1>
      <Button btnClass="bg-red-600 text-white" @click="requestDelete"
        >Request Deletion</Button
      >
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useToast } from '@/plugins/toast';
import api from '@/services/api';
import Button from '@/components/ui/Button/index.vue';
import Card from '@/components/ui/Card/index.vue';
import Checkbox from '@/components/ui/Checkbox/index.vue';

interface Consent {
  name: string;
  granted: boolean;
}

const consents = ref<Consent[]>([]);
const initial = ref<Consent[]>([]);
const toast = useToast();

async function load() {
  const { data } = await api.get('/gdpr/consents');
  consents.value = data;
  initial.value = JSON.parse(JSON.stringify(data));
}

const dirty = computed(
  () => JSON.stringify(consents.value) !== JSON.stringify(initial.value),
);

async function saveConsents() {
  if (!dirty.value) return;
  await api.put('/gdpr/consents', consents.value);
  initial.value = JSON.parse(JSON.stringify(consents.value));
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
