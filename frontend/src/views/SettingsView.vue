<template>
  <div class="max-w-2xl mx-auto">
    <Tabs v-model="active" :tabs="tabs">
      <template #default="{ active: slotActive }">
        <div v-if="slotActive === 'profile'">
          <ProfileForm />
        </div>
        <div v-else-if="slotActive === 'branding'">
          <BrandingForm />
        </div>
        <div v-else-if="slotActive === 'footer'">
          <FooterForm />
        </div>
        <div v-else-if="slotActive === 'notifications'" class="space-y-4">
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
        <div v-else-if="slotActive === 'gdpr'" class="space-y-4">
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
import FooterForm from '@/components/settings/FooterForm.vue';
import Tabs from '@/components/ui/Tabs.vue';
import Button from '@/components/ui/Button/index.vue';
import Switch from '@/components/ui/Switch/index.vue';
import { RouterLink } from 'vue-router';
import { useNotify } from '@/plugins/notify';
import { useAuthStore } from '@/stores/auth';
import { accessForRoute } from '@/constants/menu';

interface Pref {
  category: string;
  inapp: boolean;
  email: boolean;
}

const auth = useAuthStore();

const notificationAccess = accessForRoute('notifications.prefs');
const gdprAccess = accessForRoute('gdpr.index');

const canAccessNotifications = computed(() => {
  const features = notificationAccess.requiredFeatures || [];
  if (!features.every((f) => auth.features.includes(f))) {
    return false;
  }
  const req = notificationAccess.requiredAbilities || [];
  return notificationAccess.requireAllAbilities
    ? auth.hasAll(req)
    : auth.hasAny(req);
});

const canAccessGdpr = computed(() => {
  const features = gdprAccess.requiredFeatures || [];
  if (!features.every((f) => auth.features.includes(f))) {
    return false;
  }
  const req = gdprAccess.requiredAbilities || [];
  return gdprAccess.requireAllAbilities
    ? auth.hasAll(req)
    : auth.hasAny(req);
});

const tabs = computed(() => {
  const t = [
    { id: 'profile', label: 'Profile' },
    { id: 'branding', label: 'Branding' },
    { id: 'footer', label: 'Footer' },
  ];
  if (canAccessNotifications.value) {
    t.push({ id: 'notifications', label: 'Notifications' });
  }
  if (canAccessGdpr.value) {
    t.push({ id: 'gdpr', label: 'GDPR' });
  }
  return t;
});

const active = ref('profile');
const prefs = ref<Pref[]>([]);
const initialPrefs = ref<Pref[]>([]);
const notify = useNotify();

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
  notify.success('Preferences saved');
}

onMounted(() => {
  if (canAccessNotifications.value) {
    load();
  }
});
</script>
