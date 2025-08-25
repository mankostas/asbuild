<template>
  <div class="min-h-screen" :data-theme="theme" :data-density="density">
    <a href="#main" class="sr-only focus:not-sr-only focus-ring m-2">
      {{ t('a11y.skipToContent') }}
    </a>
    <CommandPalette :open="isOpen" :actions="paletteActions" @close="close" />
    <header class="sticky top-0 z-20 bg-background shadow">
      <Menubar :model="[]" class="!border-none">
        <template #start>
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
        </template>
        <template #end>
          <Dropdown
            v-model="locale"
            :options="languages"
            optionLabel="label"
            optionValue="value"
            class="w-32"
          />
          <Button @click="toggleTheme" :label="t('actions.toggleTheme')" text />
          <Button @click="toggleDensity" :label="t('actions.toggleDensity')" text />
          <Button icon="pi pi-bell" class="p-overlay-badge" text>
            <Badge v-if="queue.length" :value="queue.length" />
          </Button>
          <Button
            icon="pi pi-search"
            aria-label="Command"
            @click="open(paletteActions)"
            text
          />
          <Avatar
            icon="pi pi-user"
            class="cursor-pointer"
            @click="profileMenu?.toggle($event)"
          />
          <TieredMenu ref="profileMenu" :model="profileItems" popup />
        </template>
      </Menubar>
      <Breadcrumb :home="home" :model="breadcrumbs" class="px-4 py-2" />
    </header>
    <Sidebar v-model:visible="sidebarOpen">
      <nav class="w-56 p-2 space-y-1">
        <RouterLink
          v-for="item in menuItems"
          :key="item.to"
          :to="item.to"
          :class="[
            'flex items-center gap-3 p-2 rounded transition-colors',
            route.path.startsWith(item.to)
              ? 'bg-primary-100 font-medium'
              : 'hover:bg-primary-50'
          ]"
          @click="sidebarOpen = false"
        >
          <i :class="item.icon"></i>
          <span>{{ item.label }}</span>
        </RouterLink>
      </nav>
    </Sidebar>
    <main id="main" tabindex="-1" class="p-4">
      <router-view />
    </main>
    <Toast />
    <ConfirmDialog />
    <UploadQueue />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import Button from 'primevue/button';
import Toast from 'primevue/toast';
import UploadQueue from '../appointments/UploadQueue.vue';
import Badge from 'primevue/badge';
import Sidebar from 'primevue/sidebar';
import Menubar from 'primevue/menubar';
import Breadcrumb from 'primevue/breadcrumb';
import Avatar from 'primevue/avatar';
import TieredMenu from 'primevue/tieredmenu';
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
const route = useRoute();

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
    { label: t('routes.appointments'), icon: 'pi pi-calendar', to: '/appointments' },
    { label: t('routes.manuals'), icon: 'pi pi-book', to: '/manuals' },
    { label: t('routes.reports'), icon: 'pi pi-chart-bar', to: '/reports' },
    { label: t('routes.settings'), icon: 'pi pi-cog', to: '/settings' },
  ];
  if (auth.user?.roles?.some((r: any) => ['ClientAdmin', 'SuperAdmin'].includes(r.name))) {
    items.push({ label: t('routes.employees'), icon: 'pi pi-users', to: '/employees' });
  }
  if (auth.user?.roles?.some((r: any) => r.name === 'SuperAdmin')) {
    items.push({ label: t('routes.tenants'), icon: 'pi pi-building', to: '/tenants' });
  }
  return items;
});

const profileMenu = ref();
const profileItems = computed(() => [
  { label: t('profile.settings'), to: '/settings' },
  { label: t('actions.logout'), command: () => auth.logout() },
]);

const breadcrumbs = computed(() =>
  route.matched
    .filter((r) => r.meta?.breadcrumb)
    .map((r) => ({ label: t(r.meta.breadcrumb as string), to: r.path })),
);
const home = { icon: 'pi pi-home', to: '/' };

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
