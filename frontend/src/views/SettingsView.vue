<template>
  <div class="max-w-2xl mx-auto space-y-8">
    <section>
      <h1 class="text-xl font-bold mb-4">Profile</h1>
      <ProfileForm />
    </section>
    <section>
      <h1 class="text-xl font-bold mb-4">Branding</h1>
      <BrandingForm />
    </section>
    <section>
      <h1 class="text-xl font-bold mb-4">Notification Preferences</h1>
      <div v-for="pref in prefs" :key="pref.category" class="flex items-center gap-4 py-1">
        <span class="w-32 capitalize">{{ pref.category }}</span>
        <label class="flex items-center gap-1">
          <input type="checkbox" v-model="pref.inapp" /> In-app
        </label>
        <label class="flex items-center gap-1">
          <input type="checkbox" v-model="pref.email" /> Email
        </label>
      </div>
      <button @click="savePrefs" class="mt-2 bg-blue-500 text-white px-4 py-2">Save</button>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import ProfileForm from '@/components/settings/ProfileForm.vue';
import BrandingForm from '@/components/settings/BrandingForm.vue';

interface Pref {
  category: string;
  inapp: boolean;
  email: boolean;
}

const prefs = ref<Pref[]>([]);

async function load() {
  prefs.value = (await api.get('/notification-preferences')).data;
}

async function savePrefs() {
  await api.put('/notification-preferences', prefs.value);
}

onMounted(load);
</script>
