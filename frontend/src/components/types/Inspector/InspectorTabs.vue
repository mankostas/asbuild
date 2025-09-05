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
              type="button"
              :aria-selected="isSelected"
              :class="
                (isSelected
                  ? 'bg-primary-500 text-white'
                  : 'bg-gray-100') + ' px-2 py-1 text-sm rounded'
              "
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
              <FromGroup
                v-if="showPlaceholder"
                #default="{ inputId, labelId }"
                :label="t('fields.placeholder')"
              >
                <Textinput
                  :id="inputId"
                  v-model="placeholder"
                  :aria-labelledby="labelId"
                  classInput="text-sm"
                />
              </FromGroup>
              <FromGroup #default="{ inputId, labelId }" :label="t('fields.help')">
                <Textinput
                  :id="inputId"
                  v-model="help"
                  :aria-labelledby="labelId"
                  classInput="text-sm"
                />
              </FromGroup>
              <FromGroup
                v-if="showDefaultText"
                #default="{ inputId, labelId }"
                :label="t('fields.default')"
              >
                <Textinput
                  :id="inputId"
                  v-model="defaultValue"
                  :aria-labelledby="labelId"
                  classInput="text-sm"
                />
              </FromGroup>
              <FromGroup
                v-else-if="showDefaultNumber"
                #default="{ inputId, labelId }"
                :label="t('fields.default')"
              >
                <Textinput
                  :id="inputId"
                  v-model.number="defaultValue"
                  type="number"
                  :aria-labelledby="labelId"
                  classInput="text-sm"
                />
              </FromGroup>
              <FromGroup
                v-else-if="showDefaultDate"
                #default="{ inputId, labelId }"
                :label="t('fields.default')"
              >
                <Textinput
                  :id="inputId"
                  v-model="defaultValue"
                  type="date"
                  :aria-labelledby="labelId"
                  classInput="text-sm"
                />
              </FromGroup>
              <FromGroup
                v-else-if="showDefaultTime"
                #default="{ inputId, labelId }"
                :label="t('fields.default')"
              >
                <Textinput
                  :id="inputId"
                  v-model="defaultValue"
                  type="time"
                  :aria-labelledby="labelId"
                  classInput="text-sm"
                />
              </FromGroup>
              <FromGroup
                v-else-if="showDefaultBoolean"
                #default="{ inputId, labelId }"
                :label="t('fields.default')"
              >
                <Switch
                  :id="inputId"
                  v-model="defaultValue"
                  :aria-labelledby="labelId"
                />
              </FromGroup>
              <FromGroup #default="{ inputId, labelId }" :label="t('Required')">
                <Switch
                  :id="inputId"
                  v-model="required"
                  :aria-labelledby="labelId"
                />
              </FromGroup>
              <FromGroup #default="{ inputId, labelId }" :label="t('Columns')">
                <Select
                  :id="inputId"
                  v-model.number="cols"
                  :options="[
                    { value: 1, label: '1' },
                    { value: 2, label: '2' },
                    { value: 3, label: '3' },
                  ]"
                  :aria-labelledby="labelId"
                  classInput="text-sm"
                />
              </FromGroup>
            </div>
          </TabPanel>
          <TabPanel>
            <div class="space-y-2">
              <FromGroup #default="{ inputId, labelId }" :label="t('design.fontSize')">
                <Select
                  :id="inputId"
                  v-model="fontSize"
                  :options="[
                    { value: 'text-sm', label: t('design.small') },
                    { value: 'text-base', label: t('design.medium') },
                    { value: 'text-lg', label: t('design.large') },
                  ]"
                  :aria-labelledby="labelId"
                  classInput="text-sm"
                />
              </FromGroup>
              <FromGroup #default="{ inputId, labelId }" :label="t('design.textColor')">
                <Textinput
                  :id="inputId"
                  v-model="textColor"
                  type="color"
                  :aria-labelledby="labelId"
                  classInput="h-8 w-16 p-0 border-none"
                />
              </FromGroup>
              <FromGroup #default="{ inputId, labelId }" :label="t('design.backgroundColor')">
                <Textinput
                  :id="inputId"
                  v-model="backgroundColor"
                  type="color"
                  :aria-labelledby="labelId"
                  classInput="h-8 w-16 p-0 border-none"
                />
              </FromGroup>
            </div>
          </TabPanel>
          <TabPanel>
            <div class="space-y-2">
              <FromGroup
                v-if="showRegex"
                #default="{ inputId, labelId }"
                :label="t('validation.regex')"
              >
                <Textinput
                  :id="inputId"
                  v-model="validations.regex"
                  :aria-labelledby="labelId"
                  classInput="text-sm"
                />
              </FromGroup>
              <FromGroup
                v-if="showLength"
                #default="{ inputId, labelId }"
                :label="t('validation.lengthMin')"
              >
                <Textinput
                  :id="inputId"
                  v-model.number="validations.lengthMin"
                  type="number"
                  :aria-labelledby="labelId"
                  classInput="text-sm"
                />
              </FromGroup>
              <FromGroup
                v-if="showLength"
                #default="{ inputId, labelId }"
                :label="t('validation.lengthMax')"
              >
                <Textinput
                  :id="inputId"
                  v-model.number="validations.lengthMax"
                  type="number"
                  :aria-labelledby="labelId"
                  classInput="text-sm"
                />
              </FromGroup>
              <FromGroup
                v-if="showMinMax"
                #default="{ inputId, labelId }"
                :label="t('validation.min')"
              >
                <Textinput
                  :id="inputId"
                  v-model="validations.min"
                  :type="minMaxType"
                  :aria-labelledby="labelId"
                  classInput="text-sm"
                />
              </FromGroup>
              <FromGroup
                v-if="showMinMax"
                #default="{ inputId, labelId }"
                :label="t('validation.max')"
              >
                <Textinput
                  :id="inputId"
                  v-model="validations.max"
                  :type="minMaxType"
                  :aria-labelledby="labelId"
                  classInput="text-sm"
                />
              </FromGroup>
              <FromGroup
                v-if="showUnique"
                #default="{ inputId, labelId }"
                :label="t('validation.unique')"
              >
                <Switch
                  :id="inputId"
                  v-model="validations.unique"
                  :aria-labelledby="labelId"
                />
              </FromGroup>
            </div>
          </TabPanel>
          <TabPanel v-if="auth.isSuperAdmin || (roleOptions?.length ?? 0)">
            <div class="space-y-2">
              <FromGroup #default="{ inputId, labelId }" :label="t('roles.view')">
                <div :id="inputId" :aria-labelledby="labelId" class="flex flex-col gap-1">
                  <Checkbox
                    v-for="r in (roleOptions || [])"
                    :key="r.id"
                    v-model="roles.view"
                    :value="r.slug"
                    :label="r.name"
                  />
                </div>
              </FromGroup>
              <FromGroup #default="{ inputId, labelId }" :label="t('roles.edit')">
                <div :id="inputId" :aria-labelledby="labelId" class="flex flex-col gap-1">
                  <Checkbox
                    v-for="r in (roleOptions || [])"
                    :key="r.id"
                    v-model="roles.edit"
                    :value="r.slug"
                    :label="r.name"
                  />
                </div>
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
import Checkbox from '@/components/ui/Checkbox/index.vue';
import Select from '@/components/ui/Select/index.vue';
import { useAuthStore } from '@/stores/auth';

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
const auth = useAuthStore();
const tabs = computed(() => {
  const tbs = [t('inspector.basics'), t('inspector.design'), t('inspector.validation')];
  if (auth.isSuperAdmin || (props.roleOptions?.length ?? 0)) tbs.push(t('roles.label'));
  return tbs;
});

const label = computed({
  get: () => props.selected?.label?.[locale.value] ?? '',
  set: (val: string) => {
    if (props.selected) props.selected.label[locale.value] = val;
  },
});

const validations = computed(() => props.selected?.validations ?? {});
const roles = computed(() => props.selected?.roles ?? { view: [], edit: [] });
const styles = computed(() => props.selected?.styles ?? {});
const cols = computed({
  get: () => props.selected?.cols ?? 2,
  set: (val: number) => {
    if (props.selected) props.selected.cols = val;
  },
});
const placeholder = computed({
  get: () => props.selected?.placeholder?.[locale.value] ?? '',
  set: (val: string) => {
    if (props.selected) props.selected.placeholder[locale.value] = val;
  },
});
const help = computed({
  get: () => props.selected?.help?.[locale.value] ?? '',
  set: (val: string) => {
    if (props.selected) props.selected.help[locale.value] = val;
  },
});
const defaultValue = computed({
  get: () => props.selected?.data?.default,
  set: (val: any) => {
    if (props.selected) props.selected.data.default = val;
  },
});
const typeKey = computed(() => props.selected?.typeKey || '');
const showPlaceholder = computed(() =>
  ['text', 'number', 'date', 'time'].includes(typeKey.value),
);
const showDefaultText = computed(() => typeKey.value === 'text');
const showDefaultNumber = computed(() => typeKey.value === 'number');
const showDefaultDate = computed(() => typeKey.value === 'date');
const showDefaultTime = computed(() => typeKey.value === 'time');
const showDefaultBoolean = computed(() => typeKey.value === 'boolean');
const showRegex = computed(() => typeKey.value === 'text');
const showLength = computed(() => typeKey.value === 'text');
const showMinMax = computed(() =>
  ['number', 'date', 'time', 'repeater'].includes(typeKey.value),
);
const minMaxType = computed(() => {
  if (typeKey.value === 'date') return 'date';
  if (typeKey.value === 'time') return 'time';
  return 'number';
});
const showUnique = computed(() => ['text', 'number'].includes(typeKey.value));
watch(
  () => props.selected,
  (val) => {
    if (val) {
      if (!val.validations) val.validations = {};
      if (!val.roles) val.roles = { view: ['super_admin'], edit: ['super_admin'] };
      if (!val.styles)
        val.styles = { fontSize: 'text-base', textColor: '#000000', backgroundColor: '#ffffff' };
      if (val.cols === undefined) val.cols = 2;
      if (!val.placeholder)
        val.placeholder = { en: '', el: '' };
      if (!val.help) val.help = { en: '', el: '' };
      if (!val.data) val.data = { default: '', enum: [] };
      if (val.data.default === undefined)
        val.data.default = val.typeKey === 'boolean' ? false : '';
    }
  },
  { immediate: true },
);

const required = computed({
  get: () => validations.value.required ?? false,
  set: (val: boolean) => {
    validations.value.required = val;
  },
});

const fontSize = computed({
  get: () => styles.value.fontSize ?? 'text-base',
  set: (val: string) => {
    styles.value.fontSize = val;
  },
});
const textColor = computed({
  get: () => styles.value.textColor ?? '#000000',
  set: (val: string) => {
    styles.value.textColor = val;
  },
});
const backgroundColor = computed({
  get: () => styles.value.backgroundColor ?? '#ffffff',
  set: (val: string) => {
    styles.value.backgroundColor = val;
  },
});
</script>
