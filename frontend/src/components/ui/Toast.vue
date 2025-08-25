<template>
  <div
    class="fixed bottom-0 right-0 space-y-2 p-4"
    aria-live="polite"
    role="alert"
  >
    <div
      v-for="t in toasts"
      :key="t.id"
      class="flex items-center rounded bg-background px-4 py-2 text-foreground shadow"
    >
      <span class="mr-2">{{ t.message }}</span>
      <button
        class="ml-auto text-sm text-foreground/70 hover:text-foreground"
        @click="dismiss(t.id)"
        aria-label="Close"
      >
        &times;
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useToast } from '../../plugins/toast';

const { toasts, dismiss } = useToast();
</script>
