<template>
  <div class="min-h-screen flex flex-col" :data-theme="theme" :data-density="density">
    <a href="#main" class="sr-only focus:not-sr-only focus-ring m-2">{{ t('a11y.skipToContent') }}</a>
    <header class="sticky top-0 z-10 flex items-center justify-between bg-background p-4 shadow">
      <div class="flex items-center gap-4">
        <h1 class="font-bold">{{ t('app.title') }}</h1>
        <nav class="flex gap-2">
          <router-link class="text-blue-600" to="/appointments">Appointments</router-link>
          <router-link class="text-blue-600" to="/manuals">Manuals</router-link>
          <router-link class="text-blue-600" to="/notifications">Notifications</router-link>
        </nav>
      </div>
      <div class="flex gap-2 items-center">
        <label class="sr-only" for="language">{{ t('a11y.language') }}</label>
        <select id="language" v-model="locale" class="border rounded p-1 focus-ring">
          <option value="el">Ελληνικά</option>
          <option value="en">English</option>
        </select>
        <Button @click="toggleTheme">{{ t('actions.toggleTheme') }}</Button>
        <Button @click="toggleDensity">{{ t('actions.toggleDensity') }}</Button>
        <Button @click="notify">{{ t('actions.toast') }}</Button>
      </div>
    </header>
    <main id="main" tabindex="-1" class="flex-1 p-4">
      <router-view />
    </main>
    <Toast />
    <UploadQueue />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import Button from '../ui/Button.vue';
import Toast from '../ui/Toast.vue';
import UploadQueue from '../appointments/UploadQueue.vue';
import { useToast } from '../../plugins/toast';

const theme = ref<'light' | 'dark'>('light');
const density = ref<'compact' | ''>('');
const { t, locale } = useI18n();

function toggleTheme() {
  theme.value = theme.value === 'dark' ? 'light' : 'dark';
}

function toggleDensity() {
  density.value = density.value === 'compact' ? '' : 'compact';
}

const { show } = useToast();
function notify() {
  show(t('messages.helloToast'));
}
</script>
