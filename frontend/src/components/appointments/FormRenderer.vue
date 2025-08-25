<template>
  <div>
    <div
      v-for="(config, key) in schema.properties"
      :key="key"
      class="mb-4"
    >
      <FloatLabel v-if="!isRadio(config) && !isCheckbox(config)">
        <component
          :is="widgetFor(config)"
          v-model="model[key]"
          :id="key"
          class="w-full"
          v-bind="resolveProps(config)"
          :aria-required="isRequired(key)"
          :aria-invalid="!!errors[key]"
          :class="{ 'p-invalid': !!errors[key] }"
        />
        <label :for="key">
          {{ t(config.title || key) }}
          <span v-if="isRequired(key)" class="text-red-500">*</span>
        </label>
      </FloatLabel>

      <div v-else-if="isCheckbox(config)" class="flex items-center gap-2">
        <Checkbox
          v-model="model[key]"
          :inputId="key"
          :binary="true"
          :aria-required="isRequired(key)"
          :aria-invalid="!!errors[key]"
          :class="{ 'p-invalid': !!errors[key] }"
        />
        <label :for="key">
          {{ t(config.title || key) }}
          <span v-if="isRequired(key)" class="text-red-500">*</span>
        </label>
      </div>

      <div v-else-if="isRadio(config)">
        <label class="block mb-1">
          {{ t(config.title || key) }}
          <span v-if="isRequired(key)" class="text-red-500">*</span>
        </label>
        <div class="flex flex-col gap-1">
          <div
            v-for="option in config.enum"
            :key="option"
            class="flex items-center gap-2"
          >
            <RadioButton
              v-model="model[key]"
              :inputId="`${key}-${option}`"
              :name="key"
              :value="option"
              :aria-required="isRequired(key)"
              :aria-invalid="!!errors[key]"
              :class="{ 'p-invalid': !!errors[key] }"
            />
            <label :for="`${key}-${option}`">{{ t(option) }}</label>
          </div>
        </div>
      </div>

      <Message v-if="errors[key]" severity="error" :closable="false">
        {{ errors[key] }}
      </Message>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import Checkbox from 'primevue/checkbox';
import RadioButton from 'primevue/radiobutton';
import Chips from 'primevue/chips';
import Calendar from 'primevue/calendar';
import Slider from 'primevue/slider';
import FloatLabel from 'primevue/floatlabel';
import Message from 'primevue/message';

interface Property {
  type: string;
  format?: string;
  enum?: any[];
  items?: { enum?: any[]; type?: string };
  title?: string;
  minimum?: number;
  maximum?: number;
}

interface Schema {
  properties: Record<string, Property>;
  required?: string[];
}

const props = defineProps<{ schema: Schema; modelValue: Record<string, any> }>();
const emit = defineEmits(['update:modelValue']);
const { t } = useI18n();

const model = reactive({ ...props.modelValue });
const errors = reactive<Record<string, string>>({});

function isRequired(key: string) {
  return props.schema.required?.includes(key) ?? false;
}

function validate(key: string) {
  if (!isRequired(key)) {
    delete errors[key];
    return;
  }
  const val = model[key];
  const empty =
    val === undefined ||
    val === null ||
    val === '' ||
    (Array.isArray(val) && val.length === 0);
  if (empty) {
    errors[key] = t('forms.required', 'Required');
  } else {
    delete errors[key];
  }
}

function widgetFor(config: Property) {
  if (config.enum) {
    if (config.type === 'array') return MultiSelect;
    if (config.format === 'radio') return RadioButton;
    return Dropdown;
  }
  if (config.format === 'textarea') return Textarea;
  if (config.format === 'chips') return Chips;
  if (config.format === 'slider') return Slider;
  if (config.format === 'date' || config.format === 'date-time') return Calendar;
  if (config.type === 'number' || config.type === 'integer') return InputNumber;
  return InputText;
}

function resolveProps(config: Property) {
  const p: any = {};
  if (config.enum) p.options = config.enum;
  if (config.format === 'slider') {
    p.min = config.minimum;
    p.max = config.maximum;
  }
  return p;
}

function isRadio(config: Property) {
  return !!config.enum && config.format === 'radio';
}

function isCheckbox(config: Property) {
  return config.type === 'boolean' && !config.enum;
}

watch(
  () => props.modelValue,
  (val) => {
    Object.assign(model, val);
    Object.keys(props.schema.properties).forEach(validate);
  },
  { deep: true }
);

watch(
  model,
  (val) => {
    Object.keys(props.schema.properties).forEach(validate);
    emit('update:modelValue', { ...val });
  },
  { deep: true }
);
</script>
