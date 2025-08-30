<template>
  <div class="p-2" role="textbox" :aria-label="label" tabindex="0">
    {{ value }}
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ label: string; value: string | number | null }>();
</script>
