<template>
  <div v-if="tenant" class="max-w-md">
    <div class="grid gap-2">
      <div><strong>{{ t('tenants.details.id') }}:</strong> {{ tenant.id }}</div>
      <div><strong>{{ t('tenants.details.name') }}:</strong> {{ tenant.name }}</div>
      <div><strong>{{ t('tenants.details.phone') }}:</strong> {{ tenant.phone }}</div>
      <div><strong>{{ t('tenants.details.address') }}:</strong> {{ tenant.address }}</div>
    </div>
    <div class="mt-4 flex gap-2">
      <Button
        v-if="can('tenants.update') || can('tenants.manage')"
        btnClass="btn-primary btn-sm"
        :text="t('actions.edit')"
        :to="{ name: 'tenants.edit', params: { id: tenant.id } }"
      />
      <Button
        btnClass="btn-secondary btn-sm"
        :text="t('actions.back')"
        :to="{ name: 'tenants.list' }"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/services/api';
import Button from '@/components/ui/Button/index.vue';
import { can } from '@/stores/auth';
import hasAbility from '@/utils/ability';
import { useI18n } from 'vue-i18n';

const route = useRoute();
const tenant = ref<any>(null);
const { t } = useI18n();

onMounted(async () => {
  if (!hasAbility('tenants.view') && !hasAbility('tenants.manage')) return;
  const { data } = await api.get(`/tenants/${route.params.id}`);
  tenant.value = data;
});
</script>
