<script setup>
import { ref, onMounted } from 'vue';
import wpAjax from '../api/ajax';

const loading = ref(true);
const saving = ref(false);
const testingConnection = ref(false);
const saveMessage = ref('');
const testMessage = ref('');
const testSuccess = ref(true);

const form = ref({
  selected_provider: 'gemini',
  gemini_key: '',
  gemini_model: 'gemini-2.0-flash',
  openai_key: '',
  openai_model: 'gpt-5.4-mini'
});

const loadSettings = async () => {
  try {
    const response = await wpAjax.post('catalogbench_get_settings');
    if (response.data.success) {
      Object.assign(form.value, response.data.data);
    }
  } catch (err) {
    console.error('Failed to load settings', err);
  } finally {
    loading.value = false;
  }
};

const saveSettings = async () => {
  saving.value = true;
  saveMessage.value = '';
  try {
    const response = await wpAjax.post('catalogbench_save_settings', form.value);
    if (response.data.success) {
      saveMessage.value = 'Settings saved successfully!';
      setTimeout(() => saveMessage.value = '', 3000);
    } else {
      saveMessage.value = response.data.data.message || 'Error saving settings.';
    }
  } catch (err) {
    saveMessage.value = 'Network error saving settings.';
  } finally {
    saving.value = false;
  }
};

const testConnection = async () => {
  testingConnection.value = true;
  testMessage.value = '';
  try {
    // If we have unsaved keys dynamically typed in, test those by sending the provider config.
    // Wait, the API we built checks saved keys. We should ideally test the server state.
    // Let's rely on saving first. We'll tell users to save before testing.
    await saveSettings();

    const response = await wpAjax.post('catalogbench_test_api_key', { provider: form.value.selected_provider });
    testSuccess.value = response.data.success;
    testMessage.value = response.data.data?.message || 'Connection tested.';
  } catch (err) {
    testSuccess.value = false;
    testMessage.value = 'Network error during connection test.';
  } finally {
    testingConnection.value = false;
  }
};

onMounted(() => {
  loadSettings();
});
</script>

<template>
  <div class="cb-settings">
    <div class="cb-settings-header">
      <h2>AI Configuration</h2>
      <p>Configure your Bring Your Own Key (BYOK) providers. API keys are encrypted at rest using AES-256 and WordPress salts.</p>
    </div>

    <div v-if="loading" class="cb-loading">Loading settings...</div>

    <div v-else class="cb-settings-content">
      <div class="cb-card">
        <h3>Active Provider</h3>
        <div class="cb-field">
          <label>Select AI Provider</label>
          <select v-model="form.selected_provider" class="cb-input cb-select">
            <option value="gemini">Google Gemini (Free tier available)</option>
            <option value="openai">OpenAI (GPT-5.4 models)</option>
          </select>
        </div>
      </div>

      <div class="cb-card" v-show="form.selected_provider === 'gemini'">
        <h3>Google Gemini Configuration</h3>
        <div class="cb-field">
          <label>Gemini API Key</label>
          <input 
            type="password" 
            v-model="form.gemini_key" 
            class="cb-input" 
            placeholder="AIxaSy..." 
          />
          <p class="cb-help">Get your free key from <a href="https://aistudio.google.com/" target="_blank">Google AI Studio</a>.</p>
        </div>
        <div class="cb-field">
          <label>Gemini Model</label>
          <select v-model="form.gemini_model" class="cb-input cb-select">
            <option value="gemini-2.0-flash">Gemini 2.0 Flash (Fast, Free Tier Support)</option>
            <option value="gemini-3.0-flash">Gemini 3.0 Flash</option>
            <option value="gemini-3.0-pro">Gemini 3.0 Pro (Advanced Reasoning)</option>
            <option value="gemini-3.0-thinking">Gemini 3.0 Thinking (Deep Logic Mode)</option>
          </select>
        </div>
      </div>

      <div class="cb-card" v-show="form.selected_provider === 'openai'">
        <h3>OpenAI Configuration</h3>
        <div class="cb-field">
          <label>OpenAI API Key</label>
          <input 
            type="password" 
            v-model="form.openai_key" 
            class="cb-input" 
            placeholder="sk-proj-..." 
          />
        </div>
        <div class="cb-field">
          <label>OpenAI Model</label>
          <select v-model="form.openai_model" class="cb-input cb-select">
            <option value="gpt-5.4-mini">GPT-5.4-mini (Fastest, Cost-effective)</option>
            <option value="gpt-5.4">GPT-5.4 (Flagship Reasoning)</option>
            <option value="gpt-4o-mini">Legacy: GPT-4o-mini</option>
            <option value="gpt-4o">Legacy: GPT-4o</option>
          </select>
        </div>
      </div>

      <div class="cb-actions">
        <button class="cb-btn cb-btn-primary" @click="saveSettings" :disabled="saving">
          {{ saving ? 'Saving...' : 'Save Settings' }}
        </button>
        <button class="cb-btn cb-btn-secondary" @click="testConnection" :disabled="testingConnection || saving">
          {{ testingConnection ? 'Testing...' : 'Save & Test Connection' }}
        </button>
      </div>

      <div class="cb-messages">
        <div v-if="saveMessage" class="cb-msg cb-msg-info">{{ saveMessage }}</div>
        <div v-if="testMessage" class="cb-msg" :class="testSuccess ? 'cb-msg-success' : 'cb-msg-error'">
          {{ testMessage }}
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.cb-settings {
  max-width: 800px;
  animation: fadeIn 0.3s ease-in-out;
}

.cb-settings-header {
  margin-bottom: 24px;
}

.cb-settings-header h2 {
  font-size: 28px;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 8px 0;
  font-family: 'Inter', system-ui, sans-serif;
}

.cb-settings-header p {
  color: #64748b;
  font-size: 15px;
  margin: 0;
}

.cb-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
  margin-bottom: 24px;
  border: 1px solid #e2e8f0;
}

.cb-card h3 {
  font-size: 18px;
  margin: 0 0 20px 0;
  color: #0f172a;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 12px;
}

.cb-field {
  margin-bottom: 20px;
}

.cb-field:last-child {
  margin-bottom: 0;
}

.cb-field label {
  display: block;
  font-weight: 500;
  color: #334155;
  margin-bottom: 8px;
  font-size: 14px;
}

.cb-input {
  width: 100%;
  max-width: 100%;
  padding: 10px 14px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  font-size: 15px;
  color: #0f172a;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.cb-input:focus {
  border-color: #3b82f6;
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.cb-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.5rem center;
  background-repeat: no-repeat;
  background-size: 1.5em 1.5em;
  padding-right: 2.5rem;
}

.cb-help {
  font-size: 13px;
  color: #64748b;
  margin: 6px 0 0 0;
}

.cb-help a {
  color: #3b82f6;
  text-decoration: none;
}

.cb-help a:hover {
  text-decoration: underline;
}

.cb-actions {
  display: flex;
  gap: 12px;
  margin-top: 30px;
}

.cb-btn {
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 500;
  font-size: 15px;
  cursor: pointer;
  border: none;
  transition: all 0.2s ease;
}

.cb-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.cb-btn-primary {
  background: #3b82f6;
  color: #fff;
  box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
}

.cb-btn-primary:hover:not(:disabled) {
  background: #2563eb;
  transform: translateY(-1px);
}

.cb-btn-secondary {
  background: #f1f5f9;
  color: #334155;
  border: 1px solid #cbd5e1;
}

.cb-btn-secondary:hover:not(:disabled) {
  background: #e2e8f0;
}

.cb-messages {
  margin-top: 24px;
}

.cb-msg {
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 12px;
  font-weight: 500;
  font-size: 14px;
  animation: slideIn 0.3s ease;
}

.cb-msg-info {
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}

.cb-msg-success {
  background: #f0fdf4;
  color: #15803d;
  border: 1px solid #bbf7d0;
}

.cb-msg-error {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes slideIn {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
