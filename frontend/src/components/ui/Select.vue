<template>
  <select
    class="w-full rounded-2xl border border-foreground/20 bg-background/80 px-3 py-2 text-foreground shadow-sm backdrop-blur-sm transition-colors focus-ring"
    v-bind="$attrs"
  >
    <slot />
  </select>
</template>
<script setup lang="ts"></script>
