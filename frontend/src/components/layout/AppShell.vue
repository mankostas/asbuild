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
            aria-label="Menu"
            @click="sidebarOpen = true"
            text
          >
            <template #icon>
              <Icon icon="heroicons-outline:bars-3" class="w-5 h-5" />
            </template>
          </Button>
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
          <Bell />
          <Button
            aria-label="Command"
            @click="open(paletteActions.value)"
            text
          >
            <template #icon>
              <Icon icon="heroicons-outline:magnifying-glass" class="w-5 h-5" />
            </template>
          </Button>
          <Avatar class="cursor-pointer" @click="profileMenu?.toggle($event)">
            <template #icon>
              <Icon icon="heroicons-outline:user" class="w-5 h-5" />
            </template>
          </Avatar>
          <TieredMenu ref="profileMenu" :model="profileItems" popup />
        </template>
      </Menubar>
      <Breadcrumb :home="home" :model="breadcrumbs" class="px-4 py-2">
        <template #home>
          <RouterLink :to="home.to" class="p-breadcrumb-home">
            <Icon icon="heroicons-outline:home" class="w-5 h-5" />
          </RouterLink>
        </template>
      </Breadcrumb>
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
          <Icon :icon="item.icon" class="w-5 h-5" />
          <span>{{ item.label }}</span>
        </RouterLink>
      </nav>
    </Sidebar>
    <main id="main" tabindex="-1" class="p-4">
      <router-view />
    </main>
    <UploadQueue />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import Button from 'primevue/button';
import UploadQueue from '../appointments/UploadQueue.vue';
import Bell from '@/components/notifications/Bell.vue';
import Sidebar from 'primevue/sidebar';
import Menubar from 'primevue/menubar';
import Breadcrumb from 'primevue/breadcrumb';
import Avatar from 'primevue/avatar';
import TieredMenu from 'primevue/tieredmenu';
import Dropdown from 'primevue/dropdown';
import { useBrandingStore } from '@/stores/branding';
import { useAuthStore } from '@/stores/auth';
import CommandPalette from './CommandPalette.vue';
import { useCommandPalette } from '@/composables/useCommandPalette';
import Icon from '@/components/ui/Icon';

const theme = ref<'light' | 'dark'>('light');
const density = ref<'compact' | ''>('');
const sidebarOpen = ref(false);
const { t, locale } = useI18n();
const route = useRoute();

watch(
  theme,
  (value) => {
    document.documentElement.classList.toggle('dark', value === 'dark');
  },
  { immediate: true },
);

const languages = [
  { label: 'Ελληνικά', value: 'el' },
  { label: 'English', value: 'en' },
];

const brandingStore = useBrandingStore();
const branding = computed(() => brandingStore.branding);
onMounted(() => brandingStore.load());

const auth = useAuthStore();

const isAdmin = computed(() =>
  auth.user?.roles?.some((r: any) => ['ClientAdmin', 'SuperAdmin'].includes(r.name)),
);

const menuItems = computed(() => {
  const items = [
    { label: t('routes.appointments'), icon: 'heroicons-outline:calendar', to: '/appointments' },
  ];
  if (isAdmin.value) {
    items.push({ label: t('routes.manuals'), icon: 'heroicons-outline:book-open', to: '/manuals' });
  }
  items.push(
    { label: t('routes.reports'), icon: 'heroicons-outline:chart-bar', to: '/reports' },
    { label: t('routes.settings'), icon: 'heroicons-outline:cog-6-tooth', to: '/settings' },
  );
  if (isAdmin.value) {
    items.push({ label: t('routes.employees'), icon: 'heroicons-outline:users', to: '/employees' });
  }
  if (auth.user?.roles?.some((r: any) => r.name === 'SuperAdmin')) {
    items.push({ label: t('routes.tenants'), icon: 'heroicons-outline:building-office', to: '/tenants' });
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
const home = { to: '/' };

function toggleTheme() {
  theme.value = theme.value === 'dark' ? 'light' : 'dark';
}
function toggleDensity() {
  density.value = density.value === 'compact' ? '' : 'compact';
}

const { isOpen, open, close } = useCommandPalette();
const paletteActions = computed(() => {
  const actions = [
    { id: 'appointments', label: 'Go to Appointments', to: '/appointments' },
    { id: 'reports', label: 'Go to Reports', to: '/reports' },
    { id: 'settings', label: 'Go to Settings', to: '/settings' },
  ];
  if (isAdmin.value) {
    actions.splice(1, 0, { id: 'manuals', label: 'Go to Manuals', to: '/manuals' });
  }
  return actions;
});
</script>
