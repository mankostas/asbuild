<template>
  <div>
    <div v-if="selected" class="inspector">
      <UiTabs>
        <template #list>
          <Tab
            v-for="tab in tabs"
            :key="tab"
            #default="{ selected: isSelected }"
            as="template"
          >
            <button
              class="px-2 py-1 text-sm rounded"
              :class="isSelected ? 'bg-primary-500 text-white' : 'bg-gray-100'"
            >
              {{ tab }}
            </button>
          </Tab>
        </template>
        <template #panel>
          <TabPanel>
            <div class="space-y-2">
              <FromGroup #default="{ inputId, labelId }" :label="t('Label')">
                <Textinput
                  :id="inputId"
                  v-model="label"
                  :aria-labelledby="labelId"
                  classInput="text-sm"
                />
              </FromGroup>
              <FromGroup #default="{ inputId, labelId }" :label="t('Required')">
                <Switch
                  :id="inputId"
                  v-model="required"
                  :aria-labelledby="labelId"
                />
              </FromGroup>
            </div>
          </TabPanel>
          <TabPanel>
            <div class="space-y-2">
              <FromGroup #default="{ inputId, labelId }" :label="t('validation.regex')">
                <Textinput
                  :id="inputId"
                  v-model="validations.regex"
                  :aria-labelledby="labelId"
                  classInput="text-sm"
                />
              </FromGroup>
              <FromGroup #default="{ inputId, labelId }" :label="t('validation.min')">
                <Textinput
                  :id="inputId"
                  v-model.number="validations.min"
                  type="number"
                  :aria-labelledby="labelId"
                  classInput="text-sm"
                />
              </FromGroup>
              <FromGroup #default="{ inputId, labelId }" :label="t('validation.max')">
                <Textinput
                  :id="inputId"
                  v-model.number="validations.max"
                  type="number"
                  :aria-labelledby="labelId"
                  classInput="text-sm"
                />
              </FromGroup>
              <FromGroup #default="{ inputId, labelId }" :label="t('validation.unique')">
                <Switch
                  :id="inputId"
                  v-model="validations.unique"
                  :aria-labelledby="labelId"
                />
              </FromGroup>
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
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import UiTabs from '@/components/ui/Tabs/index.vue';
import { Tab, TabPanel } from '@headlessui/vue';
import Textinput from '@/components/ui/Textinput/index.vue';
import Switch from '@/components/ui/Switch/index.vue';
import FromGroup from '@/components/ui/FromGroup/index.vue';

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

const validations = computed(() => props.selected?.validations ?? {});
watch(
  () => props.selected,
  (val) => {
    if (val && !val.validations) val.validations = {};
  },
  { immediate: true },
);

const required = computed({
  get: () => validations.value.required ?? false,
  set: (val: boolean) => {
    validations.value.required = val;
  },
});
</script>
