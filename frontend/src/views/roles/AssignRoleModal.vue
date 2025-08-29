<template>
  <TransitionRoot v-if="can('roles.manage')" appear :show="true" as="template">
    <Dialog as="div" class="relative z-50" @close="emit('close')">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-slate-900/60" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-md rounded-md bg-white dark:bg-slate-800 p-6">
              <h2 class="text-lg font-bold mb-4">Assign Role</h2>
              <form class="grid gap-4" @submit.prevent="onSubmit">
                <VueSelect label="User">
                  <vSelect
                    v-model="userId"
                    :options="users"
                    :reduce="(u: any) => u.id"
                    label="name"
                    @search="searchUsers"
                  />
                </VueSelect>
                <VueSelect v-if="auth.isSuperAdmin" label="Tenant">
                  <vSelect
                    v-model="tenantId"
                    :options="tenantOptions"
                    :reduce="(t: any) => t.id"
                    label="name"
                  />
                </VueSelect>
                <div class="flex justify-end gap-2 mt-4">
                  <button type="button" class="btn btn-outline-secondary" @click="emit('close')">Cancel</button>
                  <button type="submit" class="btn btn-primary">Assign</button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
} from '@headlessui/vue';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import vSelect from 'vue-select';
import { useAuthStore, can } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import { useRolesStore } from '@/stores/roles';
import api from '@/services/api';

const props = defineProps<{ roleId: number }>();
const emit = defineEmits(['assigned', 'close']);

const auth = useAuthStore();
const tenantStore = useTenantStore();
const rolesStore = useRolesStore();

const users = ref<any[]>([]);
const userId = ref<number | null>(null);
const tenantId = ref<string>('');

const tenantOptions = computed(() => [
  { id: '', name: 'Global' },
  ...tenantStore.tenants.map((t: any) => ({ id: t.id, name: t.name })),
]);

async function searchUsers(search: string) {
  if (!search) return;
  const { data } = await api.get('/users', { params: { search } });
  users.value = data;
}

async function onSubmit() {
  if (!userId.value) return;
  await rolesStore.assignUser(props.roleId, {
    user_id: userId.value,
    tenant_id: auth.isSuperAdmin ? tenantId.value || undefined : tenantStore.currentTenantId,
  });
  emit('assigned');
  emit('close');
}

onMounted(async () => {
  if (auth.isSuperAdmin) {
    await tenantStore.loadTenants();
  } else {
    tenantId.value = tenantStore.currentTenantId;
  }
});
</script>
