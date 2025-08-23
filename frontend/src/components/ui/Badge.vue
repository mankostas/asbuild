<template>
  <span
    class="inline-flex items-center rounded bg-foreground/10 px-2 py-1 text-xs font-medium text-foreground"
    v-bind="$attrs"
  >
    <slot />
  </span>
</template>
<script setup lang="ts"></script>
