<template>
  <div class="p-4">
    <h1 class="text-xl font-semibold">{{ t('routes.taskBoard') }}</h1>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>
