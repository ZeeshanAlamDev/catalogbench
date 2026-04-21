<script setup>
import { ref, onMounted } from 'vue';
import wpAjax from '../api/ajax';

const loading = ref(true);
const saving = ref(false);
const testingConnection = ref(false);
const saveMessage = ref('');
const testMessage = ref('');
const testSuccess = ref(true);

const activeTab = ref('ai');

const form = ref({
  selected_provider: 'gemini',
  gemini_key: '',
  gemini_model: 'gemini-2.0-flash',
  openai_key: '',
  openai_model: 'gpt-5.4-mini',
  default_tone: 'professional',
  batch_size: 5,
  batch_delay: 2,
  seo_sync: true,
  prompt_template: '',
  max_length: {
    description: 300,
    short_description: 60,
    seo_title: 60,
    seo_meta: 160
  }
});

const loadSettings = async () => {
  try {
    const response = await wpAjax.post('catalogbench_get_settings');
    if (response.data.success) {
      Object.assign(form.value, response.data.data);
      // Ensure max_length object exists if missing from response
      if (!form.value.max_length) {
        form.value.max_length = { description: 300, short_description: 60, seo_title: 60, seo_meta: 160 };
      }
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

const insertToken = (token) => {
  form.value.prompt_template += ` {${token}} `;
};

onMounted(() => {
  loadSettings();
});
</script>

<template>
  <div class="cb-settings">
    <div class="cb-settings-header">
      <h2>CatalogBench Settings</h2>
      <p>Configure your AI models, generation preferences, and background processing options.</p>
    </div>

    <div v-if="loading" class="cb-loading">Loading settings...</div>

    <div v-else class="cb-settings-layout">
      <!-- Tabs Sidebar -->
      <div class="cb-tabs-sidebar">
        <button class="cb-tab" :class="{ 'active': activeTab === 'ai' }" @click="activeTab = 'ai'">
          <span class="dashicons dashicons-admin-network"></span> AI Providers
        </button>
        <button class="cb-tab" :class="{ 'active': activeTab === 'content' }" @click="activeTab = 'content'">
          <span class="dashicons dashicons-edit-page"></span> Content & Prompts
        </button>
        <button class="cb-tab" :class="{ 'active': activeTab === 'seo' }" @click="activeTab = 'seo'">
          <span class="dashicons dashicons-search"></span> SEO Sync
        </button>
        <button class="cb-tab" :class="{ 'active': activeTab === 'bulk' }" @click="activeTab = 'bulk'">
          <span class="dashicons dashicons-update-alt"></span> Bulk Processing
        </button>
      </div>

      <!-- Tab Content -->
      <div class="cb-tab-content">
        <!-- AI Providers Tab -->
        <div v-show="activeTab === 'ai'">
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
              <input type="password" v-model="form.gemini_key" class="cb-input" placeholder="AIxaSy..." />
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
              <input type="password" v-model="form.openai_key" class="cb-input" placeholder="sk-proj-..." />
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
            <button class="cb-btn cb-btn-secondary" @click="testConnection" :disabled="testingConnection || saving">
              {{ testingConnection ? 'Testing...' : 'Test Connection' }}
            </button>
          </div>
          <div class="cb-messages" style="margin-top:10px;">
            <div v-if="testMessage" class="cb-msg" :class="testSuccess ? 'cb-msg-success' : 'cb-msg-error'">
              {{ testMessage }}
            </div>
          </div>
        </div>

        <!-- Content Tab -->
        <div v-show="activeTab === 'content'">
          <div class="cb-card">
            <h3>Default Tone</h3>
            <div class="cb-field">
              <label>Select default tone for generations</label>
              <select v-model="form.default_tone" class="cb-input cb-select">
                <option value="professional">Professional</option>
                <option value="casual">Casual</option>
                <option value="persuasive">Persuasive</option>
                <option value="technical">Technical</option>
                <option value="luxury">Luxury</option>
              </select>
            </div>
          </div>

          <div class="cb-card">
            <h3>Max Lengths (Words)</h3>
            <div class="cb-field-grid">
              <div class="cb-field">
                <label>Long Description</label>
                <input type="number" v-model="form.max_length.description" class="cb-input" min="50" max="1000" />
              </div>
              <div class="cb-field">
                <label>Short Description</label>
                <input type="number" v-model="form.max_length.short_description" class="cb-input" min="10" max="200" />
              </div>
              <div class="cb-field">
                <label>SEO Title (Chars)</label>
                <input type="number" v-model="form.max_length.seo_title" class="cb-input" min="10" max="100" />
              </div>
              <div class="cb-field">
                <label>SEO Meta (Chars)</label>
                <input type="number" v-model="form.max_length.seo_meta" class="cb-input" min="50" max="300" />
              </div>
            </div>
          </div>

          <div class="cb-card">
            <h3>Custom Prompt Template (Advanced)</h3>
            <p class="cb-help" style="margin-bottom: 12px; font-size:14px;">Leave blank to use the highly-optimized default system prompt. <strong>Available Tokens:</strong></p>
            <div class="cb-tokens">
              <span class="cb-token" @click="insertToken('title')">{title}</span>
              <span class="cb-token" @click="insertToken('current_description')">{current_description}</span>
              <span class="cb-token" @click="insertToken('short_description')">{short_description}</span>
              <span class="cb-token" @click="insertToken('price')">{price}</span>
              <span class="cb-token" @click="insertToken('categories')">{categories}</span>
              <span class="cb-token" @click="insertToken('tags')">{tags}</span>
              <span class="cb-token" @click="insertToken('attributes')">{attributes}</span>
              <span class="cb-token" @click="insertToken('target_tone')">{target_tone}</span>
              <span class="cb-token" @click="insertToken('content_type')">{content_type}</span>
            </div>
            <div class="cb-field" style="margin-top:12px;">
              <textarea v-model="form.prompt_template" class="cb-input" rows="6" placeholder="Write a product description for the following product: {title}..."></textarea>
            </div>
          </div>
        </div>

        <!-- SEO Sync Tab -->
        <div v-show="activeTab === 'seo'">
          <div class="cb-card">
            <h3>SEO Plugin Integration</h3>
            <p class="cb-help">Automatically write generated SEO Titles and Meta Descriptions to your active SEO plugin.</p>
            
            <div class="cb-field cb-checkbox-field" style="margin-top:20px;">
              <label class="cb-toggle">
                <input type="checkbox" v-model="form.seo_sync" />
                <span class="cb-slider"></span>
              </label>
              <span class="cb-toggle-label">Enable SEO Synchronization</span>
            </div>
            
            <div class="cb-info-box" style="margin-top:15px;" v-if="form.seo_sync">
              <strong>Supported Plugins:</strong> Yoast SEO, Rank Math. CatalogBench auto-detects active plugins on generation.
            </div>
          </div>
        </div>

        <!-- Bulk Processing Tab -->
        <div v-show="activeTab === 'bulk'">
          <div class="cb-card">
            <h3>Background Processing Limits</h3>
            <p class="cb-help">Adjust how aggressive the background queue processor should be. Higher numbers process faster but use more server resources.</p>
            
            <div class="cb-field" style="margin-top:20px;">
              <label>Products Per Batch <span class="cb-badge">{{ form.batch_size }}</span></label>
              <input type="range" v-model="form.batch_size" min="1" max="20" class="cb-range" />
              <p class="cb-help">Number of products to process in a single Action Scheduler cron run.</p>
            </div>
            
            <div class="cb-field" style="margin-top:20px;">
              <label>Concurrency Delay (Seconds) <span class="cb-badge">{{ form.batch_delay }}s</span></label>
              <input type="range" v-model="form.batch_delay" min="0" max="10" class="cb-range" />
              <p class="cb-help">Delay between batches. Helps prevent rate limits on the AI provider side.</p>
            </div>
          </div>
        </div>

        <!-- Save Bar -->
        <div class="cb-save-bar">
          <button class="cb-btn cb-btn-primary" @click="saveSettings" :disabled="saving">
            {{ saving ? 'Saving...' : 'Save All Settings' }}
          </button>
          <span v-if="saveMessage" class="cb-save-msg">{{ saveMessage }}</span>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.cb-settings {
  max-width: 900px;
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

.cb-settings-layout {
  display: flex;
  gap: 24px;
  align-items: flex-start;
}

.cb-tabs-sidebar {
  width: 250px;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.cb-tab {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 16px;
  background: transparent;
  border: none;
  border-bottom: 1px solid #f1f5f9;
  text-align: left;
  font-size: 15px;
  font-weight: 500;
  color: #475569;
  cursor: pointer;
  transition: all 0.2s;
}

.cb-tab:last-child {
  border-bottom: none;
}

.cb-tab:hover {
  background: #f8fafc;
  color: #0f172a;
}

.cb-tab.active {
  background: #eff6ff;
  color: #2563eb;
  border-left: 3px solid #2563eb;
  padding-left: 13px; /* visual balance for the border */
}

.cb-tab-content {
  flex: 1;
  min-width: 0;
}

.cb-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  margin-bottom: 20px;
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

.cb-field-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
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
  transition: border-color 0.2s;
  box-sizing: border-box;
}

.cb-input:focus {
  border-color: #3b82f6;
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

textarea.cb-input {
  resize: vertical;
  min-height: 100px;
  font-family: monospace;
  font-size: 13px;
  line-height: 1.5;
}

.cb-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.5rem center;
  background-repeat: no-repeat;
  background-size: 1.5em 1.5em;
  padding-right: 2.5rem;
}

.cb-range {
  width: 100%;
  margin-top: 5px;
}

.cb-badge {
  background: #e2e8f0;
  color: #334155;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 12px;
  margin-left: 8px;
}

.cb-help {
  font-size: 13px;
  color: #64748b;
  margin: 6px 0 0 0;
}

.cb-tokens {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
}

.cb-token {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-family: monospace;
  color: #334155;
  cursor: pointer;
  transition: all 0.2s;
}

.cb-token:hover {
  background: #e2e8f0;
  border-color: #94a3b8;
}

.cb-checkbox-field {
  display: flex;
  align-items: center;
  gap: 12px;
}

.cb-toggle {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
}

.cb-toggle input {
  opacity: 0;
  width: 0;
  height: 0;
}

.cb-slider {
  position: absolute;
  cursor: pointer;
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: #cbd5e1;
  transition: .4s;
  border-radius: 24px;
}

.cb-slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
}

input:checked + .cb-slider {
  background-color: #3b82f6;
}

input:focus + .cb-slider {
  box-shadow: 0 0 1px #3b82f6;
}

input:checked + .cb-slider:before {
  transform: translateX(20px);
}

.cb-toggle-label {
  font-weight: 500;
  color: #0f172a;
}

.cb-info-box {
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  border-radius: 8px;
  padding: 12px;
  font-size: 14px;
  color: #475569;
}

.cb-save-bar {
  display: flex;
  align-items: center;
  gap: 16px;
  background: #fff;
  padding: 16px 24px;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
}

.cb-save-msg {
  color: #15803d;
  font-weight: 500;
  font-size: 14px;
  animation: fadeIn 0.3s ease;
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

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>

