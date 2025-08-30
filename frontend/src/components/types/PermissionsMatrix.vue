<template>
  <Card>
    <template #header>
      <h2 class="text-lg font-semibold">
        {{ t('abilities.title') }}
      </h2>
    </template>
    <div class="hidden md:block max-h-64 overflow-auto">
      <table class="min-w-full text-sm">
        <thead class="sticky top-0 bg-white">
          <tr>
            <th scope="col" class="px-4 py-2 text-left">
              {{ t('roles') }}
            </th>
            <th
              v-for="ability in abilityList"
              :key="ability.key"
              scope="col"
              class="px-4 py-2 text-center"
            >
              <Tooltip theme="light" trigger="mouseenter focus click">
                <template #button>
                  <span class="inline-flex items-center gap-1 cursor-help">
                    {{ ability.label }}
                    <Icon
                      icon="heroicons-outline:question-mark-circle"
                      class="w-4 h-4"
                      aria-hidden="true"
                    />
                  </span>
                </template>
                {{ t(`permissions.tooltip.${ability.key}`) }}
              </Tooltip>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="role in roles" :key="role.id" class="border-t">
            <th scope="row" class="px-4 py-2 text-left">
              {{ role.name }}
            </th>
            <td
              v-for="ability in abilityList"
              :key="ability.key"
              class="px-4 py-2 text-center"
            >
              <Switch
                :id="`perm-${role.slug}-${ability.key}`"
                v-model="localPermissions[role.slug][ability.key]"
                :aria-label="`${role.name} ${ability.label}`"
                classLabel="sr-only"
                :disabled="!canManage"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="md:hidden space-y-4">
      <div v-for="role in roles" :key="role.id" class="border p-4 rounded">
        <h3 class="font-medium mb-2">{{ role.name }}</h3>
        <div
          v-for="ability in abilityList"
          :key="ability.key"
          class="flex items-center justify-between py-1"
        >
          <span>{{ ability.label }}</span>
          <Switch
            :id="`perm-${role.slug}-${ability.key}-m`"
            v-model="localPermissions[role.slug][ability.key]"
            :aria-label="`${role.name} ${ability.label}`"
            classLabel="sr-only"
            :disabled="!canManage"
          />
        </div>
      </div>
    </div>
  </Card>
</template>

<script setup lang="ts">
import { reactive, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Card from '@/components/ui/Card/index.vue';
import Switch from '@/components/ui/Switch/index.vue';
import Tooltip from '@/components/ui/Tooltip/index.vue';
import Icon from '@/components/Icon';

interface Role {
  id: number;
  name: string;
  slug: string;
}

interface Permission {
  read: boolean;
  edit: boolean;
  delete: boolean;
  export: boolean;
  assign: boolean;
}

const props = defineProps<{
  modelValue: Record<string, Permission>;
  roles: Role[];
  canManage: boolean;
}>();
const emit = defineEmits(['update:modelValue']);
const { t } = useI18n();

const localPermissions = reactive<Record<string, Permission>>({ ...props.modelValue });

watch(
  () => props.roles,
  (roles) => {
    roles.forEach((r) => {
      if (!localPermissions[r.slug]) {
        localPermissions[r.slug] = {
          read: false,
          edit: false,
          delete: false,
          export: false,
          assign: false,
        };
      }
    });
  },
  { immediate: true },
);

watch(
  () => props.modelValue,
  (val) => {
    Object.keys(val).forEach((k) => {
      localPermissions[k] = { ...val[k] };
    });
  },
  { deep: true },
);

watch(
  localPermissions,
  (val) => {
    emit('update:modelValue', JSON.parse(JSON.stringify(val)));
  },
  { deep: true },
);

const abilityList = [
  { key: 'read', label: t('abilities.read') },
  { key: 'edit', label: t('abilities.edit') },
  { key: 'delete', label: t('abilities.delete') },
  { key: 'export', label: t('abilities.export') },
  { key: 'assign', label: t('abilities.assign') },
];
</script>
