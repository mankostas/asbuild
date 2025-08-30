<template>
  <div>
    <div v-if="selected" class="inspector">
      <UiTabs>
        <template #list>
          <Tab as="template" v-for="tab in tabs" :key="tab" v-slot="{ selected: isSelected }">
            <button
              class="px-2 py-1 text-sm rounded"
              :class="isSelected ? 'bg-primary-500 text-white' : 'bg-gray-100'">
              {{ tab }}
            </button>
          </Tab>
        </template>
        <template #panel>
          <TabPanel>
            <div class="space-y-2">
              <Textinput
                id="fieldLabel"
                v-model="label"
                :label="t('Label')"
                classInput="text-sm"
              />
              <Switch id="fieldRequired" v-model="required" :label="t('Required')" />
            </div>
          </TabPanel>
          <TabPanel>
            <div class="space-y-2">
              <Textinput
                id="valRegex"
                v-model="validations.regex"
                :label="t('validation.regex')"
                classInput="text-sm"
              />
              <Textinput
                id="valMin"
                type="number"
                v-model.number="validations.min"
                :label="t('validation.min')"
                classInput="text-sm"
              />
              <Textinput
                id="valMax"
                type="number"
                v-model.number="validations.max"
                :label="t('validation.max')"
                classInput="text-sm"
              />
              <Switch id="valUnique" v-model="validations.unique" :label="t('validation.unique')" />
            </div>
          </TabPanel>
        </template>
      </UiTabs>
    </div>
    <div v-else class="text-sm text-gray-500">{{ t('Select a field') }}</div>
  </div>
</template>

<script setup lang="ts">
/* eslint-disable vue/no-mutating-props */
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import UiTabs from '@/components/ui/Tabs/index.vue';
import { Tab, TabPanel } from '@headlessui/vue';
import Textinput from '@/components/ui/Textinput/index.vue';
import Switch from '@/components/ui/Switch/index.vue';

interface RoleOption {
  id: number;
  name: string;
  slug: string;
}

const props = withDefaults(
  defineProps<{ selected: any | null; roleOptions?: RoleOption[] }>(),
  { roleOptions: () => [] },
);
const { t, locale } = useI18n();
const tabs = ['Basics', 'Validation'];

const label = computed({
  get: () => props.selected?.label?.[locale.value] ?? '',
  set: (val: string) => {
    if (props.selected) props.selected.label[locale.value] = val;
  },
});

const validations = computed(() => {
  if (!props.selected) return {} as any;
  if (!props.selected.validations) props.selected.validations = {};
  return props.selected.validations;
});

const required = computed({
  get: () => validations.value.required ?? false,
  set: (val: boolean) => {
    validations.value.required = val;
  },
});
</script>
