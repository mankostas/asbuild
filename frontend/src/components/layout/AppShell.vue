<template>
  <div class="min-h-screen" :data-theme="theme" :data-density="density">
    <a href="#main" class="sr-only focus:not-sr-only focus-ring m-2">
      {{ t('a11y.skipToContent') }}
    </a>
    <CommandPalette :open="isOpen" :actions="paletteActions" @close="close" />
    <header
      class="sticky top-0 z-20 flex items-center justify-between bg-background p-4 shadow"
    >
      <div class="flex items-center gap-2">
        <Button
          class="mr-2 md:hidden"
          icon="pi pi-bars"
          aria-label="Menu"
          @click="sidebarOpen = true"
          text
        />
        <div class="flex items-center gap-2">
          <img
            v-if="branding.logo"
            :src="branding.logo"
            alt="logo"
            class="h-6"
          />
          <h1 class="font-bold">{{ branding.name || t('app.title') }}</h1>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Dropdown
          v-model="locale"
          :options="languages"
          optionLabel="label"
          optionValue="value"
          class="w-32"
        />
        <Button @click="toggleTheme" :label="t('actions.toggleTheme')" text />
        <Button @click="toggleDensity" :label="t('actions.toggleDensity')" text />
        <Badge v-if="queue.length" :value="queue.length" />
        <Button
          icon="pi pi-search"
          aria-label="Command"
          @click="open(paletteActions)"
          text
        />
      </div>
    </header>
    <Sidebar v-model:visible="sidebarOpen">
      <Menu :model="menuItems" class="w-full" />
    </Sidebar>
    <main id="main" tabindex="-1" class="p-4">
      <router-view />
    </main>
    <Toast />
    <UploadQueue />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import Button from 'primevue/button';
import Toast from '../ui/Toast.vue';
import UploadQueue from '../appointments/UploadQueue.vue';
import Badge from 'primevue/badge';
import Sidebar from 'primevue/sidebar';
import Menu from 'primevue/menu';
import Dropdown from 'primevue/dropdown';
import { useBrandingStore } from '@/stores/branding';
import { useAuthStore } from '@/stores/auth';
import { useDraftsStore } from '@/stores/drafts';
import { storeToRefs } from 'pinia';
import CommandPalette from './CommandPalette.vue';
import { useCommandPalette } from '@/composables/useCommandPalette';

const theme = ref<'light' | 'dark'>('light');
const density = ref<'compact' | ''>('');
const sidebarOpen = ref(false);
const { t, locale } = useI18n();

const languages = [
  { label: 'Ελληνικά', value: 'el' },
  { label: 'English', value: 'en' },
];

const brandingStore = useBrandingStore();
const branding = computed(() => brandingStore.branding);
onMounted(() => brandingStore.load());

const auth = useAuthStore();
const drafts = useDraftsStore();
const { queue } = storeToRefs(drafts);

const menuItems = computed(() => {
  const items = [
    { label: 'Appointments', to: '/appointments' },
    { label: 'Manuals', to: '/manuals' },
    { label: 'Reports', to: '/reports' },
    { label: 'Settings', to: '/settings' },
  ];
  if (auth.user?.roles?.some((r: any) => r.name === 'SuperAdmin')) {
    items.push({ label: 'Tenants', to: '/tenants' });
  }
  return items;
});

function toggleTheme() {
  theme.value = theme.value === 'dark' ? 'light' : 'dark';
}
function toggleDensity() {
  density.value = density.value === 'compact' ? '' : 'compact';
}

const { isOpen, open, close } = useCommandPalette();
const paletteActions = [
  { id: 'appointments', label: 'Go to Appointments', to: '/appointments' },
  { id: 'manuals', label: 'Go to Manuals', to: '/manuals' },
  { id: 'reports', label: 'Go to Reports', to: '/reports' },
  { id: 'settings', label: 'Go to Settings', to: '/settings' },
];
</script>
