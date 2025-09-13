<template>
  <div v-if="tenant" class="max-w-md">
    <div class="grid gap-2">
      <div><strong>ID:</strong> {{ tenant.id }}</div>
      <div><strong>Name:</strong> {{ tenant.name }}</div>
      <div><strong>Phone:</strong> {{ tenant.phone }}</div>
      <div><strong>Address:</strong> {{ tenant.address }}</div>
    </div>
    <div class="mt-4 flex gap-2">
      <Button
        v-if="can('tenants.update') || can('tenants.manage')"
        btnClass="btn-primary btn-sm"
        text="Edit"
        :to="{ name: 'tenants.edit', params: { id: tenant.id } }"
      />
      <Button btnClass="btn-secondary btn-sm" text="Back" :to="{ name: 'tenants.list' }" />
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

const route = useRoute();
const tenant = ref<any>(null);

onMounted(async () => {
  if (!hasAbility('tenants.view') && !hasAbility('tenants.manage')) return;
  const { data } = await api.get(`/tenants/${route.params.id}`);
  tenant.value = data;
});
</script>
