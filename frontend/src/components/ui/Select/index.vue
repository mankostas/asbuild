<template>
  <div
    class="fromGroup relative"
    :class="`${errorText ? 'has-error' : ''}  ${horizontal ? 'flex' : ''}  ${
      showSuccessIcon ? 'is-valid' : ''
    } `"
  >
    <label
      v-if="label"
      :class="`${classLabel} inline-block input-label `"
      :for="inputId"
    >
      {{ label }}</label
    >
    <div class="relative">
      <select
        :id="inputId"
        :name="name"
        :class="`${classInput} input-control block w-full focus:outline-none min-h-[40px] `"
        :value="modelValue"
        :readonly="isReadonly"
        :disabled="disabled"
        :formatter="formatter"
        :size="size"
        :multiple="multiple"
        @input="
          ($event) => {
            $emit('update:modelValue', $event.target.value);
            $emit('input', $event);
          }
        "
        @change="$emit('change', $event)"
      >
        <option value="" disabled selected>{{ placeholder }}</option>
        <template v-if="!$slots.default && options">
          <option
            v-for="(item, index) in options"
            :key="index"
            :value="item.value"
          >
            {{ item.label }}
          </option>
        </template>
        <slot v-if="$slots.default"></slot>
      </select>
    </div>

    <p
      v-if="errorText"
      class="mt-2"
      :class="
        msgTooltip
          ? ' inline-block bg-danger-500 text-white text-[10px] px-2 py-1 rounded'
          : ' text-danger-500 block text-sm whitespace-pre-line'
      "
    >
      {{ errorText }}
    </p>
    <p
      v-if="successText"
      class="mt-2"
      :class="
        msgTooltip
          ? ' inline-block bg-success-500 text-white text-[10px] px-2 py-1 rounded'
          : ' text-success-500 block text-sm'
      "
    >
      {{ successText }}
    </p>
    <span
      v-if="description"
      class="block text-secondary-500 font-light leading-4 text-xs mt-2"
      >{{ description }}</span
    >
  </div>
</template>
<script>
export default {
  props: {
    modelValue: {
      type: [String, Number, Boolean, Object, Array, Date],
      default: "",
    },
    // incoming error could be string or array from backend/lib
    error: {
      type: [String, Array, Object, Boolean],
      default: "",
    },
    // legacy: sometimes boolean, sometimes string/array -> normalize
    validate: {
      type: [Boolean, String, Array, Object],
      default: false,
    },
    // NEW (preferred): explicit flags/messages
    showValidation: { type: Boolean, default: false },
    successMessage: { type: String, default: "" },
    label: { type: String, default: "" },
    id: { type: String, default: "" },
    name: { type: String, default: "" },
    description: { type: String, default: "" },
    placeholder: {
      type: String,
      default: "Select Option",
    },
    classLabel: {
      type: String,
      default: " ",
    },
    classInput: {
      type: String,
      default: "classinput",
    },
    isReadonly: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    horizontal: {
      type: Boolean,
      default: false,
    },
    msgTooltip: {
      type: Boolean,
      default: false,
    },
    formatter: {
      type: Function,
      default: (value) => value,
    },
    size: {
      type: String,
      default: "",
    },
    multiple: {
      type: Boolean,
      default: false,
    },
    options: {
      type: Array,
      default: () => [
        {
          value: "",
          label: "Select Option",
        },
        {
          value: "",
          label: "Select Option2",
        },
      ],
    },
  },
  emits: ["update:modelValue", "input", "change"],
  data() {
    return {
      generatedId: `fld-${Math.random().toString(36).slice(2)}`,
    };
  },
  computed: {
    inputId() {
      return this.id || this.generatedId;
    },
    // normalize error to string
    errorText() {
      const e = this.error;
      if (!e) return "";
      if (Array.isArray(e)) return e.filter(Boolean).join("\n");
      if (typeof e === "object")
        return Object.values(e).flat().filter(Boolean).join("\n");
      return String(e);
    },
    // determine whether validation UI should show
    validationEnabled() {
      return this.showValidation || !!this.validate;
    },
    // success text comes only from dedicated prop or legacy when string
    successText() {
      if (this.successMessage) return this.successMessage;
      if (typeof this.validate === "string") return this.validate;
      return "";
    },
    // success icon shows only if validation is enabled and there is no error
    showSuccessIcon() {
      return this.validationEnabled && !this.errorText;
    },
  },
};
</script>
<style lang="scss" scoped>
select {
  @apply appearance-none bg-[url('https://api.iconify.design/akar-icons/chevron-down.svg')] bg-no-repeat bg-right;
  background-position-x: 98%;
}
[dir="rtl"] select {
  background-position-x: 2%;
}
option {
  @apply capitalize;
}
.dark {
  select {
    @apply bg-[url('https://api.iconify.design/heroicons/chevron-down-solid.svg?color=white')];
  }
}
</style>
