<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <Textinput
      v-model="email"
      label="Email"
      type="email"
      placeholder="Type your email"
      name="emil"
      :error="emailError"
      classInput="h-[48px]"
    />

    <button type="submit" class="btn btn-dark block w-full text-center">
      Send recovery email
    </button>
  </form>
</template>
<script>
import Textinput from "@/components/Textinput";
import { useField, useForm } from "vee-validate";
import * as yup from "yup";

export default {
  components: {
    Textinput,
  },
  setup() {
    // Define a validation schema
    const schema = yup.object({
      email: yup.string().required().email(),
    });

    const { handleSubmit } = useForm({
      validationSchema: schema,
    });
    // No need to define rules for fields

    const { value: email, errorMessage: emailError } = useField("email");

    const onSubmit = handleSubmit(() => {
      // console.warn(values);
    });

    return {
      email,
      emailError,
      onSubmit,
    };
  },
  data() {
    return {
      checkbox: false,
    };
  },
};
</script>
<style lang="scss"></style>
