<template>
  <div class="max-w-2xl mx-auto space-y-6">
    <div>
      <h1 class="text-xl font-bold mb-2">Notifications</h1>
      <div v-for="n in notifications" :key="n.id" class="border-b py-2">
        <a :href="n.link" class="text-blue-600">{{ n.message }}</a>
      </div>
    </div>
    <div>
      <h2 class="text-lg font-bold mb-2">Preferences</h2>
      <div v-for="pref in prefs" :key="pref.category" class="flex items-center gap-4 py-1">
        <span class="w-32 capitalize">{{ pref.category }}</span>
        <label class="flex items-center gap-1">
          <input type="checkbox" v-model="pref.inapp" /> In-app
        </label>
        <label class="flex items-center gap-1">
          <input type="checkbox" v-model="pref.email" /> Email
        </label>
      </div>
      <button @click="save" class="mt-2 bg-blue-500 text-white px-4 py-2">Save</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/services/api';

interface Pref {
  category: string;
  inapp: boolean;
  email: boolean;
}

const notifications = ref([] as any[]);
const prefs = ref<Pref[]>([]);

const load = async () => {
  notifications.value = (await api.get('/notifications')).data;
  prefs.value = (await api.get('/notification-preferences')).data;
};

const save = async () => {
  await api.put('/notification-preferences', prefs.value);
};

onMounted(load);
</script>
