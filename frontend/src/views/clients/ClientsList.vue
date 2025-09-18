<template>
  <div class="p-4">
    <Card>
      <template #header>
        <div class="flex items-center justify-between gap-4">
          <h1 class="text-lg font-semibold">{{ t('routes.clients') }}</h1>
          <Button
            v-if="canCreate"
            :link="{ name: 'clients.create' }"
            btnClass="btn-primary btn-sm min-w-[120px] !h-8 !py-0"
            icon="heroicons-outline:plus"
            iconClass="w-4 h-4"
            :text="t('clients.addClient')"
            :aria-label="t('clients.addClient')"
          />
        </div>
      </template>
      <p class="text-slate-600">
        {{ t('clients.form.search') }}
      </p>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Card from '@/components/ui/Card/index.vue';
import Button from '@/components/ui/Button';
import { can } from '@/stores/auth';

const { t } = useI18n();
const canCreate = computed(
  () => can('clients.create') || can('clients.manage'),
);
</script>
