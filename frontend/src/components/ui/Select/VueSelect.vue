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
      :id="inputId"
    >
      {{ label }}</label
    >
    <div class="relative">
      <div v-if="!$slots.default">
        <vSelect
          :name="name"
          :modelValue="modelValue"
          :readonly="isReadonly"
          :disabled="disabled"
          :multiple="multiple"
          :options="options"
          :aria-labelledby="inputId"
          @update:model-value="$emit('update:modelValue', $event)"
          @input="$emit('input', $event)"
          @change="$emit('change', $event)"
        >
        </vSelect>
      </div>
      <slot :input-id="inputId"></slot>
      <div class="flex text-xl absolute right-[14px] top-1/2 -translate-y-1/2">
        <span v-if="errorText" class="text-danger-500">
          <Icon icon="heroicons-outline:information-circle" />
        </span>
        <span v-if="showSuccessIcon" class="text-success-500">
          <Icon icon="bi:check-lg" />
        </span>
      </div>
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
import vSelect from "vue-select";
import "vue-select/dist/vue-select.css";
import Icon from "@/components/ui/Icon";
export default {
  components: {
    vSelect,
    Icon,
  },
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
    name: { type: String, default: "" },
    id: { type: String, default: "" },
    description: { type: String, default: "" },
    placeholder: { type: String, default: "Select Option" },
    classLabel: { type: String, default: " " },
    classInput: { type: String, default: "classinput" },
    isReadonly: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    horizontal: { type: Boolean, default: false },
    msgTooltip: { type: Boolean, default: false },
    multiple: { type: Boolean, default: false },
    options: { type: Array, default: () => [] },
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
<style lang="scss">
.fromGroup {
  .vs__dropdown-toggle {
    @apply bg-transparent  dark:bg-slate-900 border-slate-200 dark:border-slate-700 dark:text-white min-h-[40px] text-slate-900 text-sm;
  }
  .v-select {
    @apply dark:text-slate-300;
  }
  &.has-error {
    .vs__dropdown-toggle {
      @apply border-danger-500;
    }
  }
  .vs__dropdown-option {
    @apply dark:text-slate-100;
  }
  .vs__dropdown-option--highlight {
    @apply bg-slate-900 dark:bg-slate-600 dark:bg-opacity-20 py-2 text-sm;
  }
  .vs__dropdown-menu {
    li {
      @apply capitalize;
    }
  }
  .vs__dropdown-menu {
    @apply shadow-dropdown bg-white dark:bg-slate-800  text-sm  border-[0px] dark:border-[1px] dark:border-slate-700;
  }
  .vs__search::placeholder {
    @apply text-secondary-500;
  }
  .vs__actions svg {
    @apply fill-secondary-500 w-[15px] h-[15px] mt-[6px] scale-[.8];
  }

  .vs--multiple {
    .vs__selected {
      @apply text-xs text-slate-900 dark:text-slate-300 font-light bg-white dark:bg-slate-700 border-slate-200 dark:border-slate-700 border rounded-[3px] h-fit;
      padding: 4px 8px !important;
    }
    .vs__deselect {
      @apply dark:fill-slate-300;
    }

    .vs__selected-options {
      @apply items-center capitalize;
      svg {
        @apply scale-[0.8];
      }
    }
  }
  .vs--single .vs__selected {
    @apply dark:text-slate-300;
  }
  .vs__dropdown-option--disabled {
    @apply bg-slate-50 dark:bg-slate-700;
  }
}
</style>
