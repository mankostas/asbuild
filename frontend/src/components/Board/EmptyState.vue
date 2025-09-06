<template>
  <Card
    :class="['border bg-white/60 rounded-2xl shadow-base', wrapperClass]"
    bodyClass="p-10 text-center"
  >
    <img :src="illustration" alt="" class="mx-auto mb-6" />
    <p class="text-slate-600 dark:text-slate-300 text-sm">
      <slot>{{ message }}</slot>
    </p>
    <div v-if="$slots.action" class="mt-6">
      <slot name="action" />
    </div>
  </Card>
</template>

<script setup lang="ts">
import Card from '@dc/components/Card';

interface Props {
  illustration: string;
  message?: string;
  wrapperClass?: string;
}

const props = defineProps<Props>();
const wrapperClass = props.wrapperClass;
</script>
