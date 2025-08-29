<template>
  <div class="max-w-2xl mx-auto">
    <table class="w-full">
      <thead>
        <tr>
          <th class="text-left p-2">Category</th>
          <th class="text-left p-2">In-app</th>
          <th class="text-left p-2">Email</th>
          <th class="text-left p-2">SMS</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(p, i) in prefs" :key="p.name" class="border-t">
          <td class="p-2 capitalize">{{ p.name }}</td>
          <td class="p-2">
            <input
              :id="`inapp-${i}`"
              v-model="p.inapp"
              type="checkbox"
              :aria-label="`In-app for ${p.name}`"
            />
          </td>
          <td class="p-2">
            <input
              :id="`email-${i}`"
              v-model="p.email"
              type="checkbox"
              :aria-label="`Email for ${p.name}`"
            />
          </td>
          <td class="p-2">
            <input
              :id="`sms-${i}`"
              v-model="p.sms"
              type="checkbox"
              :aria-label="`SMS for ${p.name}`"
            />
          </td>
        </tr>
      </tbody>
    </table>
    <button
      class="mt-4 bg-blue-500 text-white px-4 py-2"
      @click="save"
    >
      Save
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/services/api';

interface Preference {
  name: string;
  inapp: boolean;
  email: boolean;
  sms: boolean;
}

const prefs = ref<Preference[]>([]);

async function load() {
  const { data } = await api.get('/notification-preferences');
  prefs.value = data;
}

async function save() {
  await api.put('/notification-preferences', prefs.value);
}

onMounted(load);
</script>
