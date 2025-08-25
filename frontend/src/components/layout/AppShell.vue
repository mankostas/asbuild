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
        <button
          class="mr-2 rounded p-2 focus-ring md:hidden"
          aria-label="Menu"
          :aria-expanded="sidebarOpen"
          @click="sidebarOpen = !sidebarOpen"
        >
          ☰
        </button>
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
        <label class="sr-only" for="language">{{ t('a11y.language') }}</label>
        <select
          id="language"
          v-model="locale"
          class="rounded border border-foreground/20 bg-background px-2 py-1 focus-ring"
        >
          <option value="el">Ελληνικά</option>
          <option value="en">English</option>
        </select>
        <Button @click="toggleTheme">{{ t('actions.toggleTheme') }}</Button>
        <Button @click="toggleDensity">{{ t('actions.toggleDensity') }}</Button>
        <Badge v-if="queue.length" class="bg-blue-600 text-white">{{
          queue.length
        }}</Badge>
        <button
          class="rounded p-2 focus-ring"
          aria-label="Command"
          @click="open(paletteActions)"
        >
          ⌘K
        </button>
      </div>
    </header>
    <div class="flex">
      <aside
        class="fixed inset-y-0 left-0 z-10 w-64 -translate-x-full bg-background shadow transition-transform md:static md:translate-x-0"
        :class="{ 'translate-x-0': sidebarOpen }"
        aria-label="Sidebar"
      >
        <nav class="mt-4 space-y-1 p-4">
          <router-link
            class="block rounded px-2 py-1 hover:bg-foreground/10"
            to="/appointments"
          >
            Appointments
          </router-link>
          <router-link
            class="block rounded px-2 py-1 hover:bg-foreground/10"
            to="/manuals"
            >Manuals</router-link
          >
          <router-link
            class="block rounded px-2 py-1 hover:bg-foreground/10"
            to="/reports"
            >Reports</router-link
          >
          <router-link
            class="block rounded px-2 py-1 hover:bg-foreground/10"
            to="/settings"
            >Settings</router-link
          >
          <router-link
            v-if="auth.user?.roles?.some((r: any) => r.name === 'SuperAdmin')"
            class="block rounded px-2 py-1 hover:bg-foreground/10"
            to="/tenants"
            >Tenants</router-link
          >
        </nav>
      </aside>
      <main id="main" tabindex="-1" class="flex-1 p-4 md:ml-64">
        <router-view />
      </main>
    </div>
    <Toast />
    <UploadQueue />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import Button from '../ui/Button.vue';
import Toast from '../ui/Toast.vue';
import UploadQueue from '../appointments/UploadQueue.vue';
import Badge from '../ui/Badge.vue';
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

const brandingStore = useBrandingStore();
const branding = computed(() => brandingStore.branding);
onMounted(() => brandingStore.load());

const auth = useAuthStore();
const drafts = useDraftsStore();
const { queue } = storeToRefs(drafts);

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
