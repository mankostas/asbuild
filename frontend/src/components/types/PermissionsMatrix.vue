<template>
  <Card>
    <template #header>
      <h2 class="text-lg font-semibold">
        {{ t('abilities.title') }}
      </h2>
    </template>
    <div class="hidden md:block max-h-64 overflow-auto">
      <table class="min-w-full text-sm">
        <thead class="sticky top-0 z-10 bg-white">
          <tr>
            <th scope="col" class="px-4 py-2 text-left">
              {{ t('roles.label') }}
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
          <tr
            v-for="role in roles"
            :key="role.id"
            class="border-t odd:bg-secondary-50 focus-within:bg-primary-50"
          >
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
                :disabled="!canManage || (ability.key === 'transition' && !canTransition)"
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
            :disabled="!canManage || (ability.key === 'transition' && !canTransition)"
          />
        </div>
      </div>
    </div>
    <div class="mt-4 text-xs text-slate-600">
      <p class="font-medium mb-1">{{ t('abilities.legend') }}</p>
      <ul class="grid grid-cols-2 gap-x-4 gap-y-1">
        <li
          v-for="ability in abilityList"
          :key="ability.key"
          class="flex items-center gap-1"
        >
          <span>{{ elAbilities[ability.key] }}</span>
          <span class="text-slate-400">/ {{ enAbilities[ability.key] }}</span>
        </li>
      </ul>
    </div>
  </Card>
</template>

<script setup lang="ts">
import { reactive, watch, computed, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import Card from '@/components/ui/Card/index.vue';
import Switch from '@/components/ui/Switch/index.vue';
import Tooltip from '@/components/ui/Tooltip/index.vue';
import Icon from '@/components/Icon';
import en from '@/i18n/en.json';
import el from '@/i18n/el.json';
import { featureMap } from '@/constants/featureMap';

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
  transition: boolean;
}

const props = defineProps<{
  modelValue: Record<string, Permission>;
  roles: Role[];
  canManage: boolean;
  statusCount: number;
  features: string[];
  featureAbilities?: Record<string, string[]>;
}>();
const emit = defineEmits(['update:modelValue']);
const { t } = useI18n();

const localPermissions = reactive<Record<string, Permission>>(
  Object.fromEntries(
    Object.entries(props.modelValue).map(([k, v]) => [
      k,
      { transition: false, ...v } as Permission,
    ]),
  ),
);

let updatingFromParent = false;

watch(
  () => props.roles,
  (roles) => {
    const slugs = roles.map((r) => r.slug);
    Object.keys(localPermissions).forEach((k) => {
      if (!slugs.includes(k)) delete localPermissions[k];
    });
    roles.forEach((r) => {
      if (!localPermissions[r.slug]) {
        localPermissions[r.slug] = {
          read: false,
          edit: false,
          delete: false,
          export: false,
          assign: false,
          transition: false,
        };
      } else if (localPermissions[r.slug].transition === undefined) {
        localPermissions[r.slug].transition = false;
      }
    });
  },
  { immediate: true, deep: true },
);

watch(
  () => props.modelValue,
  (val) => {
    updatingFromParent = true;
    Object.keys(val).forEach((k) => {
      localPermissions[k] = { transition: false, ...val[k] } as Permission;
    });
    nextTick(() => {
      updatingFromParent = false;
    });
  },
  { deep: true },
);

watch(
  localPermissions,
  (val) => {
    if (updatingFromParent) return;
    emit('update:modelValue', JSON.parse(JSON.stringify(val)));
  },
  { deep: true },
);

const baseAbilityList = [
  { key: 'read', label: t('abilities.read') },
  { key: 'edit', label: t('abilities.edit') },
  { key: 'delete', label: t('abilities.delete') },
  { key: 'export', label: t('abilities.export') },
  { key: 'assign', label: t('abilities.assign') },
  { key: 'transition', label: t('abilities.transition') },
];

const abilityMap: Record<string, string[]> = {
  read: ['tasks.view'],
  edit: ['tasks.update'],
  delete: ['tasks.delete'],
  export: ['tasks.export'],
  assign: ['tasks.assign'],
  transition: ['tasks.status.update'],
};

const allowedAbilities = computed(() =>
  new Set(
    props.featureAbilities && Object.keys(props.featureAbilities).length
      ? Object.values(props.featureAbilities).flat()
      : props.features.flatMap((f) => featureMap[f]?.abilities || []),
  ),
);

const abilityList = computed(() =>
  baseAbilityList.filter((ability) => {
    const req = abilityMap[ability.key];
    return req ? req.some((a) => allowedAbilities.value.has(a)) : true;
  }),
);

const canTransition = computed(() =>
  props.statusCount >= 2 &&
  abilityMap.transition.some((a) => allowedAbilities.value.has(a)),
);

watch(
  canTransition,
  (val) => {
    if (!val) {
      Object.values(localPermissions).forEach((p) => {
        p.transition = false;
      });
    }
  },
  { immediate: true },
);

watch(
  abilityList,
  (list) => {
    const keys = list.map((a) => a.key) as (keyof Permission)[];
    Object.values(localPermissions).forEach((perm) => {
      Object.keys(perm).forEach((k) => {
        if (!keys.includes(k as keyof Permission)) {
          delete (perm as any)[k];
        }
      });
      keys.forEach((k) => {
        if (!(k in perm)) {
          (perm as any)[k] = false;
        }
      });
    });
  },
  { immediate: true },
);

const enAbilities = (en as any).abilities;
const elAbilities = (el as any).abilities;
</script>
