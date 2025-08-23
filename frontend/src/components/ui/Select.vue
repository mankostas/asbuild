<template>
  <select
    class="w-full rounded border border-foreground/20 bg-background px-3 py-2 text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/50"
    v-bind="$attrs"
  >
    <slot />
  </select>
</template>
<script setup lang="ts"></script>
