<template>
  <TenantForm v-if="isOpen" force-modal @close="close" />
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia';
import TenantForm from '@/views/tenants/TenantForm.vue';
import { useTenantModalStore } from '@/stores/tenantModal';

const tenantModal = useTenantModalStore();
const { isOpen } = storeToRefs(tenantModal);

const close = () => {
  tenantModal.close();
};
</script>
