<template>
  <div class="p-4">
    <Card class="max-w-3xl mx-auto">
      <template #header>
        <h1 class="text-lg font-semibold">
          {{ isEdit ? t('routes.clientEdit') : t('routes.clientCreate') }}
        </h1>
      </template>
      <p class="text-slate-600">
        {{ t('routes.clients') }}
      </p>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import Card from '@/components/ui/Card/index.vue';

const route = useRoute();
const { t } = useI18n();

const isEdit = computed(() => route.name === 'clients.edit');
</script>
