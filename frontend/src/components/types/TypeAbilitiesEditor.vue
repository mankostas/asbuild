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
import { reactive, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Switch from '@/components/ui/Switch/index.vue';

interface Abilities {
  read: boolean;
  edit: boolean;
  delete: boolean;
  export: boolean;
  assign: boolean;
  transition: boolean;
}

const props = defineProps<{ modelValue: Abilities }>();
const emit = defineEmits(['update:modelValue']);
const { t } = useI18n();

const localAbilities = reactive({ ...props.modelValue });

const abilityLabels: Record<keyof Abilities, string> = {
  read: t('abilities.read'),
  edit: t('abilities.edit'),
  delete: t('abilities.delete'),
  export: t('abilities.export'),
  assign: t('abilities.assign'),
  transition: t('abilities.transition'),
};

watch(
  localAbilities,
  () => {
    emit('update:modelValue', { ...localAbilities });
  },
  { deep: true }
);
</script>
