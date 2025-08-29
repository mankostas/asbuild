<template>
  <div
    class="fromGroup relative"
    :class="`${error ? 'has-error' : ''}  ${horizontal ? 'flex' : ''}  ${
      validate ? 'is-valid' : ''
    } `"
  >
    <label
      v-if="label"
      :class="`${classLabel} ${
        horizontal ? 'flex-0 mr-6 md:w-[100px] w-[60px] break-words' : ''
      }  ltr:inline-block rtl:block input-label `"
      :for="inputId"
    >
      {{ label }}</label
    >
    <div class="relative" :class="horizontal ? 'flex-1' : ''">
      <input
        v-if="!isMask"
        :id="inputId"
        :type="types"
        :name="name"
        :placeholder="placeholder"
        :class="`${classInput} input-control w-full block focus:outline-none h-[40px] ${
          hasicon ? 'ltr:pr-10 rtl:pl-10' : ''
        } `"
        :value="modelValue"
        :error="error"
        :readonly="isReadonly"
        :disabled="disabled"
        :validate="validate"
        @input="
          ($event) => {
            $emit('update:modelValue', $event.target.value);
            $emit('input', $event);
          }
        "
        @change="$emit('change', $event)"
      />
      <cleave
        v-if="isMask"
        :id="inputId"
        :class="`${classInput} cleave input-control block w-full focus:outline-none h-[40px] `"
        :name="name"
        :placeholder="placeholder"
        :value="modelValue"
        :error="error"
        :readonly="isReadonly"
        :disabled="disabled"
        :validate="validate"
        :options="options"
        modelValue="modelValue"
        @input="
          ($event) => {
            $emit('update:modelValue', $event.target.value);
            $emit('input', $event);
          }
        "
        @change="$emit('change', $event)"
      />

      <div
        class="flex text-xl absolute ltr:right-[14px] rtl:left-[14px] top-1/2 -translate-y-1/2"
      >
        <span
          v-if="hasicon"
          class="cursor-pointer text-secondary-500"
          @click="toggleType"
        >
          <Icon v-if="types === 'password'" icon="heroicons-outline:eye" />
          <Icon v-else icon="heroicons-outline:eye-off" />
        </span>

        <span v-if="error" class="text-danger-500">
          <Icon icon="heroicons-outline:information-circle" />
        </span>

        <span v-if="validate" class="text-success-500">
          <Icon icon="bi:check-lg" />
        </span>
      </div>
    </div>

    <span
      v-if="error"
      class="mt-2"
      :class="
        msgTooltip
          ? ' inline-block bg-danger-500 text-white text-[10px] px-2 py-1 rounded'
          : ' text-danger-500 block text-sm'
      "
      >{{ $msg(error) }}</span
    >
    <span
      v-if="validate"
      class="mt-2"
      :class="
        msgTooltip
          ? ' inline-block bg-success-500 text-white text-[10px] px-2 py-1 rounded'
          : ' text-success-500 block text-sm'
      "
      >{{ $msg(validate) }}</span
    >
    <span
      v-if="description"
      class="block text-secondary-500 font-light leading-4 text-xs mt-2"
      >{{ description }}</span
    >
  </div>
</template>
<script>
import Icon from "@/components/ui/Icon";
import Cleave from "vue-cleave-component";
export default {
  components: { Icon, Cleave },
  props: {
    modelValue: {
      type: [String, Number, Boolean, Object, Array],
      default: "",
    },
    label: {
      type: String,
      default: "",
    },
    name: {
      type: String,
      default: "",
    },
    id: {
      type: String,
      default: "",
    },
    error: {
      type: [String, Boolean],
      default: "",
    },
    description: {
      type: String,
      default: "",
    },
    validate: {
      type: [Array, String, Function],
      default: () => [],
    },
    placeholder: {
      type: String,
      default: "Search",
    },
    classLabel: {
      type: String,
      default: " ",
    },
    classInput: {
      type: String,
      default: "classinput",
    },
    type: {
      type: String,
      default: "text",
    },
    hasicon: {
      type: Boolean,
      default: false,
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
    isMask: {
      type: Boolean,
      default: false,
    },
    options: {
      type: Object,
      default: () => ({
        creditCard: true,
        delimiter: "-",
      }),
    },
  },
  emits: ["update:modelValue", "input", "change"],
  data() {
    return {
      types: this.type,
      generatedId: `fld-${Math.random().toString(36).slice(2)}`,
    };
  },
  computed: {
    inputId() {
      return this.id || this.generatedId;
    },
  },
  methods: {
    toggleType() {
      this.types = this.types === "text" ? "password" : "text";
    },
  },
};
</script>
<style lang="scss"></style>
