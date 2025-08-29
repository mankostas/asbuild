<template>
  <div
    class="fromGroup relative"
    :class="`${error ? 'has-error' : ''}  ${horizontal ? 'flex' : ''}  ${
      validate ? 'is-valid' : ''
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
      <slot :input-id="inputId"></slot>
    </div>

    <span
      v-if="error"
      class="mt-2"
      :class="
        msgTooltip
          ? ' inline-block bg-danger-500 text-white text-[10px] px-2 py-1 rounded'
          : ' text-danger-500 block text-sm'
      "
      >{{ error }}</span
    >
    <span
      v-if="validate"
      class="mt-2"
      :class="
        msgTooltip
          ? ' inline-block bg-success-500 text-white text-[10px] px-2 py-1 rounded'
          : ' text-success-500 block text-sm'
      "
      >{{ validate }}</span
    >
    <span
      v-if="description"
      class="block text-secondary-500 font-light leading-4 text-xs mt-2"
      >{{ description }}</span
    >
  </div>
</template>
<script>
export default {
  components: {},
  props: {
    label: {
      type: String,
      default: "",
    },
    classLabel: {
      type: String,
      default: " ",
    },
    classInput: {
      type: String,
      default: "classinput",
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

    horizontal: {
      type: Boolean,
      default: false,
    },
    validate: {
      type: [Array, String, Function],
      default: () => [],
    },
    msgTooltip: {
      type: Boolean,
      default: false,
    },

    description: {
      type: String,
      default: "",
    },
  },
  data() {
    return {
      generatedId: `fld-${Math.random().toString(36).slice(2)}`,
    };
  },
  computed: {
    inputId() {
      return this.id || this.generatedId;
    },
  },
};
</script>
<style lang="scss"></style>
