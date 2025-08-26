<template>
  <div class="max-w-2xl mx-auto">
    <Tabs v-model="active" :tabs="tabs">
      <template #default="{ active }">
        <div v-if="active === 'profile'">
          <ProfileForm />
        </div>
        <div v-else-if="active === 'branding'">
          <BrandingForm />
        </div>
        <div v-else-if="active === 'notifications'" class="space-y-4">
          <div
            v-for="pref in prefs"
            :key="pref.category"
            class="flex items-center gap-4 py-1"
          >
            <span class="flex-1 capitalize">{{ pref.category }}</span>
            <Switch v-model="pref.inapp" />
            <Switch v-model="pref.email" />
          </div>
          <Button
            btnClass="btn-dark"
            :isDisabled="!dirty"
            @click="savePrefs"
            >Save</Button
          >
        </div>
        <div v-else-if="active === 'gdpr'" class="space-y-4">
          <router-link class="text-primary-500 underline" to="/gdpr"
            >Manage your data</router-link
          >
        </div>
      </template>
    </Tabs>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '@/services/api';
import ProfileForm from '@/components/settings/ProfileForm.vue';
import BrandingForm from '@/components/settings/BrandingForm.vue';
import Tabs from '@/components/ui/Tabs.vue';
import Button from '@/components/ui/Button/index.vue';
import Switch from '@/components/ui/Switch/index.vue';
import { RouterLink } from 'vue-router';
import { useToast } from '@/plugins/toast';

interface Pref {
  category: string;
  inapp: boolean;
  email: boolean;
}

const tabs = [
  { id: 'profile', label: 'Profile' },
  { id: 'branding', label: 'Branding' },
  { id: 'notifications', label: 'Notifications' },
  { id: 'gdpr', label: 'GDPR' },
];

const active = ref('profile');
const prefs = ref<Pref[]>([]);
const initialPrefs = ref<Pref[]>([]);
const toast = useToast();

async function load() {
  const { data } = await api.get('/notification-preferences');
  prefs.value = data;
  initialPrefs.value = JSON.parse(JSON.stringify(data));
}

const dirty = computed(
  () => JSON.stringify(prefs.value) !== JSON.stringify(initialPrefs.value),
);

async function savePrefs() {
  if (!dirty.value) return;
  await api.put('/notification-preferences', prefs.value);
  initialPrefs.value = JSON.parse(JSON.stringify(prefs.value));
  toast.add({ severity: 'success', summary: 'Preferences saved', detail: '' });
}

onMounted(load);
</script>
