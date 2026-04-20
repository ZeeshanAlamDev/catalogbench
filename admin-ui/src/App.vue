<script setup>
import { RouterView } from 'vue-router'
</script>

<template>
  <div class="catalogbench-container">
    <header class="catalogbench-header">
      <div class="catalogbench-logo">
        <h1>CatalogBench <span>Pro</span></h1>
      </div>
      <nav class="catalogbench-nav">
        <router-link to="/">Dashboard</router-link>
        <router-link to="/single-generator">Single Generator</router-link>
        <router-link to="/bulk-generator">Bulk Generator</router-link>
        <router-link to="/settings">Settings</router-link>
      </nav>
    </header>

    <main class="catalogbench-main">
      <RouterView />
    </main>
  </div>
</template>

<style>
/* Base styles */
.catalogbench-container {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
  color: #3c434a;
  padding: 20px;
}

.catalogbench-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #c3c4c7;
  padding-bottom: 15px;
  margin-bottom: 20px;
}

.catalogbench-logo h1 {
  margin: 0;
  font-size: 24px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.catalogbench-logo span {
  font-size: 12px;
  background: #2271b1;
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
  vertical-align: middle;
}

.catalogbench-nav {
  display: flex;
  gap: 15px;
}

.catalogbench-nav a {
  text-decoration: none;
  color: #2271b1;
  font-weight: 500;
  padding: 5px 10px;
  border-radius: 4px;
  transition: all 0.2s;
}

.catalogbench-nav a:hover,
.catalogbench-nav a.router-link-active {
  background: #f0f0f1;
  color: #135e96;
}

.catalogbench-main {
  background: #fff;
  min-height: 400px;
}

/* Common form styles */
.cb-form-group {
  margin-bottom: 15px;
}

.cb-form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: 5px;
}

.cb-input, .cb-select, .cb-textarea {
  width: 100%;
  max-width: 400px;
  padding: 6px 8px;
  border: 1px solid #8c8f94;
  border-radius: 4px;
  box-shadow: 0 0 0 transparent;
  transition: border-color .1s ease-in-out;
}

.cb-input:focus, .cb-select:focus, .cb-textarea:focus {
  border-color: #2271b1;
  box-shadow: 0 0 0 1px #2271b1;
  outline: 2px solid transparent;
}

.cb-textarea {
  min-height: 100px;
}

.cb-button {
  background: #2271b1;
  color: #fff;
  border: 1px solid #2271b1;
  border-radius: 3px;
  padding: 0 10px;
  height: 30px;
  line-height: 28px;
  font-size: 13px;
  cursor: pointer;
  text-decoration: none;
  font-weight: 600;
  display: inline-block;
}

.cb-button:hover {
  background: #135e96;
  border-color: #135e96;
  color: #fff;
}

.cb-button-secondary {
  background: #f6f7f7;
  color: #2271b1;
  border-color: #2271b1;
}

.cb-button-secondary:hover {
  background: #f0f0f1;
  color: #135e96;
  border-color: #135e96;
}

.cb-card {
  background: #fff;
  border: 1px solid #c3c4c7;
  border-radius: 4px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
}
</style>
