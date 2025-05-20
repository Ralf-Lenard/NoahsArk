<template>
    <div>
      <label v-if="label" :for="id" class="block mb-1 text-sm font-medium text-gray-700">{{ label }}</label>
      <textarea
        :id="id"
        :placeholder="placeholder"
        :rows="rows"
        v-model="modelValue"
        class="w-full p-4 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 shadow-sm resize-none transition duration-200 ease-in-out"
      ></textarea>
    </div>
  </template>
  
  <script setup lang="ts">
  import { defineProps, defineEmits } from 'vue'
  
  const props = defineProps<{
    modelValue: string
    label?: string
    placeholder?: string
    rows?: number
    id?: string
  }>()
  
  const emit = defineEmits(['update:modelValue'])
  </script>
  
  <script lang="ts">
  export default {
    name: 'Textarea'
  }
  </script>
  