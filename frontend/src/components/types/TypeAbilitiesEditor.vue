<template>
  <div>
    <h2 class="text-lg font-semibold mb-2">{{ t('abilities.title') }}</h2>
    <div class="grid grid-cols-2 gap-2">
      <Switch
        v-for="(label, key) in abilityLabels"
        :id="`ability-${key}`"
        :key="key"
        v-model="localAbilities[key]"
        :label="label"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Switch from '@/components/ui/Switch/index.vue';
import { featureMap } from '@/constants/featureMap';

interface Abilities {
  read: boolean;
  edit: boolean;
  delete: boolean;
  export: boolean;
  assign: boolean;
  transition: boolean;
}

const props = defineProps<{ modelValue: Abilities; features: string[] }>();
const emit = defineEmits(['update:modelValue']);
const { t } = useI18n();

const localAbilities = reactive({ ...props.modelValue });

const baseAbilityLabels: Record<keyof Abilities, string> = {
  read: t('abilities.read'),
  edit: t('abilities.edit'),
  delete: t('abilities.delete'),
  export: t('abilities.export'),
  assign: t('abilities.assign'),
  transition: t('abilities.transition'),
};

const abilityMap: Record<keyof Abilities, string[]> = {
  read: ['tasks.view'],
  edit: ['tasks.update'],
  delete: ['tasks.delete'],
  export: ['tasks.export'],
  assign: ['tasks.assign'],
  transition: ['tasks.status.update'],
};

const allowedAbilities = computed(() =>
  new Set(
    props.features.flatMap((f) => featureMap[f]?.abilities || []),
  ),
);

const abilityLabels = computed(() => {
  const labels: Record<string, string> = {};
  (Object.keys(baseAbilityLabels) as (keyof Abilities)[]).forEach((key) => {
    const req = abilityMap[key];
    if (req.some((a) => allowedAbilities.value.has(a))) {
      labels[key] = baseAbilityLabels[key];
    }
  });
  return labels as Record<keyof Abilities, string>;
});

watch(
  localAbilities,
  () => {
    emit('update:modelValue', { ...localAbilities });
  },
  { deep: true },
);

watch(
  abilityLabels,
  (labels) => {
    const keys = Object.keys(labels) as (keyof Abilities)[];
    Object.keys(localAbilities).forEach((k) => {
      if (!keys.includes(k as keyof Abilities)) {
        delete (localAbilities as any)[k];
      }
    });
    keys.forEach((k) => {
      if (!(k in localAbilities)) {
        (localAbilities as any)[k] = false;
      }
    });
  },
  { immediate: true },
);
</script>
