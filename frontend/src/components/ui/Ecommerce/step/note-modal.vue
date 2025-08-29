<template>
  <Modal
    ref="modal1"
    :activeModal="activeModal"
    title="New Address"
    label="New"
    labelClass="btn-outline-dark"
    @close="emit('close')"
  >
    <form
      class="space-y-4 flex flex-col justify-between"
      @submit.prevent="submit"
    >
      <span class="sr-only">Address</span>
      <Textarea
        id="address"
        name="pn4"
        placeholder="Your Address"
        horizontal
        aria-label="Address"
      />
      <div class="flex">
        <span
          class="flex-none flex-0 mr-6 md:w-[100px] break-words ltr:inline-block rtl:block input-label"
        >
          Country
        </span>
        <div class="flex-1 w-full">
          <Select
            :options="options"
            placeholder="Select Your Country"
            classLabel="!w-[150px]"
            aria-label="Country"
          />
        </div>
      </div>
      <div class="flex">
        <span
          class="flex-none flex-0 mr-6 md:w-[100px] break-words ltr:inline-block rtl:block input-label"
        >
          State
        </span>
        <div class="flex-1 w-full">
          <Select
            :options="options"
            placeholder="Select Your State"
            classLabel="!w-[150px]"
            aria-label="State"
          />
        </div>
      </div>
      <div class="flex">
        <span
          class="flex-none flex-0 mr-6 md:w-[100px] break-words ltr:inline-block rtl:block input-label"
        >
          City
        </span>
        <div class="flex-1 w-full">
          <Select
            :options="options"
            placeholder="Select Your City"
            classLabel="!w-[150px]"
            aria-label="City"
          />
        </div>
      </div>

      <span class="sr-only">Postal Code</span>
      <InputGroup
        id="postal-code"
        type="text"
        name="ps-1"
        placeholder="Your Postal Code"
        horizontal
        aria-label="Postal Code"
      />
      <span class="sr-only">Phone No</span>
      <InputGroup
        id="phone-no"
        type="text"
        name="ps-1"
        placeholder="+880"
        horizontal
        aria-label="Phone No"
      />

      <div class="col-span-12 flex justify-end">
        <button
          type="submit"
          class="btn btn-dark h-min w-max text-sm font-normal"
        >
          Save Address
        </button>
      </div>
    </form>
  </Modal>
</template>

<script setup>
const options = ref([
  { value: "option1", label: "Option 1" },
  { value: "option2", label: "Option 2" },
  { value: "option3", label: "Option 3" },
]);

import Modal from "@/components/ui/Modal";
import Textarea from "@/components/ui/Textarea";
import InputGroup from "@/components/ui/InputGroup";
import Select from "@/components/ui/Select";
import { defineProps, defineEmits, ref } from "vue";
const props = defineProps({
  activeModal: {
    type: Boolean,
    default: false,
  },
});
const emit = defineEmits(["close"]);
</script>

<style lang="scss" scoped></style>
