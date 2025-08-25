<template>
  <select
    class="w-full rounded-2xl border border-foreground/20 bg-background px-3 py-2 text-foreground focus-ring"
    v-bind="$attrs"
  >
    <slot />
  </select>
</template>
<script setup lang="ts"></script>
