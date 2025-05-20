<template>
    <div class="container my-4">
      <h1 class="text-center mb-4">Animal Location Details</h1>
  
      <!-- Error Message -->
      <div v-if="errors.length" class="alert alert-danger">
        <ul>
          <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
        </ul>
      </div>
  
      <!-- Location Information -->
      <div v-if="location" class="card">
        <div class="card-header">
          <h5 class="card-title">Device ID: {{ device_id }}</h5>
        </div>
        <div class="card-body">
          <p><strong>Latitude:</strong> {{ location.latitude }}</p>
          <p><strong>Longitude:</strong> {{ location.longitude }}</p>
          <p><strong>Timestamp:</strong> {{ location.timestamp }}</p>
        </div>
      </div>
  
      <!-- If no location data is available -->
      <div v-else class="alert alert-warning mt-4">
        <p>No location data available for this animal.</p>
      </div>
    </div>
  </template>
  
  <script setup>
  defineProps({
    location: {
      type: Object,
      required: true,
    },
    device_id: {
      type: String,
      required: true,
    },
    errors: {
      type: Array,
      default: () => [],
    }
  });
  </script>
  
  <style scoped>
  .container {
    max-width: 800px;
  }
  .card {
    margin-top: 20px;
  }
  </style>
  