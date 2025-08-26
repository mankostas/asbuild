<template>
  <div
    v-if="active"
    class="fixed inset-0 z-40 flex items-center justify-center bg-black/50"
  >
    <div class="w-8 h-8 border-4 border-white border-t-transparent rounded-full animate-spin"></div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ active: boolean }>();
</script>
