<template>
  <div class="min-h-screen flex flex-col" :data-theme="theme" :data-density="density">
    <header class="sticky top-0 z-10 flex items-center justify-between bg-background p-4 shadow">
      <h1 class="font-bold">Asbuild</h1>
      <div class="flex gap-2">
        <Button @click="toggleTheme">Toggle Theme</Button>
        <Button @click="toggleDensity">Toggle Density</Button>
        <Button @click="notify">Toast</Button>
      </div>
    </header>
    <main class="flex-1 p-4">
      <router-view />
    </main>
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import Button from '../ui/Button.vue';
import Toast from '../ui/Toast.vue';
import { useToast } from '../../plugins/toast';

const theme = ref<'light' | 'dark'>('light');
const density = ref<'compact' | ''>('');

function toggleTheme() {
  theme.value = theme.value === 'dark' ? 'light' : 'dark';
}

function toggleDensity() {
  density.value = density.value === 'compact' ? '' : 'compact';
}

const { show } = useToast();
function notify() {
  show('Hello from toast');
}
</script>
