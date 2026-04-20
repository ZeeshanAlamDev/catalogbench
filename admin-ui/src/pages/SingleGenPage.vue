<script setup>
import { ref, reactive, computed } from 'vue';
import wpAjax from '../api/ajax';

const productSearchId = ref('');
const loadingStatus = ref('');
const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const productData = ref(null);

const config = reactive({
  tone: 'professional',
  custom_instruction: '',
  types_to_generate: {
    description: true,
    short_description: false,
    seo_title: false,
    seo_meta: false
  }
});

const generatedResults = ref({});
const savingStates = ref({});
const editModes = ref({});

const loadProduct = async () => {
  if (!productSearchId.value) return;
  
  loading.value = true;
  loadingStatus.value = 'Retrieving product data...';
  errorMessage.value = '';
  productData.value = null;
  generatedResults.value = {};
  
  try {
    const response = await wpAjax.post('catalogbench_get_product_data', { product_id: productSearchId.value });
    if (response.data.success) {
      productData.value = response.data.data;
    } else {
      errorMessage.value = response.data.data.message || 'Failed to load product.';
    }
  } catch (err) {
    errorMessage.value = 'Network error fetching product data.';
  } finally {
    loading.value = false;
    loadingStatus.value = '';
  }
};

const generateContent = async () => {
  if (!productData.value) return;
  
  const selectedTypes = Object.entries(config.types_to_generate)
    .filter(([_, isSelected]) => isSelected)
    .map(([type]) => type);

  if (selectedTypes.length === 0) {
    errorMessage.value = 'Please select at least one content type to generate.';
    setTimeout(() => errorMessage.value = '', 3000);
    return;
  }

  loading.value = true;
  errorMessage.value = '';
  generatedResults.value = {};

  try {
    for (const type of selectedTypes) {
      loadingStatus.value = `Generating ${type.replace('_', ' ')}...`;
      
      const payload = {
        product_id: productData.value.id,
        content_type: type,
        tone: config.tone,
        custom_instruction: config.custom_instruction
      };

      const response = await wpAjax.post('catalogbench_generate_single', payload);
      
      if (response.data.success) {
        generatedResults.value[type] = response.data.data.content;
      } else {
        errorMessage.value += `Failed generating ${type}: ${response.data.data.message}\n`;
      }
    }
  } catch (err) {
    errorMessage.value = 'Network error during generation.';
  } finally {
    loading.value = false;
    loadingStatus.value = '';
  }
};

const applyContent = async (type) => {
  if (!generatedResults.value[type]) return;

  savingStates.value[type] = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const payload = {
      product_id: productData.value.id,
      content_type: type,
      content: generatedResults.value[type]
    };

    const response = await wpAjax.post('catalogbench_apply_single', payload);

    if (response.data.success) {
      successMessage.value = `Successfully applied ${type.replace('_', ' ')}!`;
      productData.value[type] = generatedResults.value[type]; // Update local state
      delete generatedResults.value[type]; // Remove visual preview for applied type
      setTimeout(() => successMessage.value = '', 3000);
    } else {
      errorMessage.value = response.data.data.message || `Error saving ${type}.`;
    }
  } catch (err) {
    errorMessage.value = 'Network error saving product.';
  } finally {
    savingStates.value[type] = false;
  }
};

const discardContent = (type) => {
  delete generatedResults.value[type];
};

const formatLabel = (key) => {
  const labels = {
    description: 'Long Description',
    short_description: 'Short Description',
    seo_title: 'SEO Title',
    seo_meta: 'SEO Meta Description'
  };
  return labels[key] || key;
};
</script>

<template>
  <div class="cb-single-gen">
    <div class="cb-header">
      <h2>Single Product Generator</h2>
      <p>Target a specific product, configure the AI tone, and visually compare the newly generated descriptions before applying them live.</p>
    </div>

    <!-- Feedback Messages -->
    <div class="cb-messages" v-if="errorMessage || successMessage">
      <div v-if="successMessage" class="cb-msg cb-msg-success">{{ successMessage }}</div>
      <div v-if="errorMessage" class="cb-msg cb-msg-error" style="white-space: pre-wrap;">{{ errorMessage }}</div>
    </div>

    <!-- Product Selection Box -->
    <div class="cb-card cb-search-box">
      <h3>Select Product</h3>
      <div class="cb-search-form">
        <div class="cb-field">
          <label>Product ID</label>
          <input 
            type="number" 
            v-model="productSearchId" 
            class="cb-input" 
            placeholder="e.g. 1024" 
            @keyup.enter="loadProduct"
          />
        </div>
        <button class="cb-btn cb-btn-primary cb-search-btn" @click="loadProduct" :disabled="loading || !productSearchId">
          {{ loading && !productData ? 'Fetching...' : 'Load Product' }}
        </button>
      </div>
    </div>

    <div v-if="loading && loadingStatus" class="cb-loading-overlay">
      <div class="spinner"></div>
      <p>{{ loadingStatus }}</p>
    </div>

    <!-- Workspace -->
    <div v-if="productData" class="cb-workspace animate-fade-in">
      
      <!-- Product Info Summary -->
      <div class="cb-product-banner">
        <div>
          <h3 class="product-title">{{ productData.title }}</h3>
          <span class="product-sku">SKU: {{ productData.sku || 'N/A' }}</span>
        </div>
        <div class="product-price" v-html="productData.price"></div>
      </div>

      <div class="cb-grid">
        <!-- Sidebar Config -->
        <div class="cb-sidebar">
          <div class="cb-card">
            <h3>Generation Settings</h3>
            
            <div class="cb-field">
              <label>Output Tone</label>
              <select v-model="config.tone" class="cb-input cb-select">
                <option value="professional">Professional & Trustworthy</option>
                <option value="casual">Casual & Friendly</option>
                <option value="persuasive">Persuasive (Sales Copy)</option>
                <option value="urgent">Urgent & Exciting</option>
                <option value="humorous">Humorous & Witty</option>
              </select>
            </div>

            <div class="cb-field">
              <label>Custom Instructions (Optional)</label>
              <textarea 
                v-model="config.custom_instruction" 
                class="cb-input cb-textarea" 
                placeholder="E.g., Highlight that this product is eco-friendly."
              ></textarea>
            </div>

            <div class="cb-divider"></div>

            <label class="section-label">Fields to Generate</label>
            <div class="cb-checkbox-group">
              <label class="cb-checkbox">
                <input type="checkbox" v-model="config.types_to_generate.description"> <span class="chk-label">Long Description</span>
              </label>
              <label class="cb-checkbox">
                <input type="checkbox" v-model="config.types_to_generate.short_description"> <span class="chk-label">Short Description</span>
              </label>
              <label class="cb-checkbox">
                <input type="checkbox" v-model="config.types_to_generate.seo_title"> <span class="chk-label">SEO Title</span>
              </label>
              <label class="cb-checkbox">
                <input type="checkbox" v-model="config.types_to_generate.seo_meta"> <span class="chk-label">SEO Meta Description</span>
              </label>
            </div>

            <button class="cb-btn cb-btn-brand cb-generate-btn" @click="generateContent" :disabled="loading">
              ✨ Generate Content
            </button>
          </div>
        </div>

        <!-- Main Content Area -->
        <div class="cb-main">
          
          <div v-if="Object.keys(generatedResults).length === 0" class="cb-empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="empty-icon"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>
            <p>Configure your settings and click Generate to preview AI content here.</p>
          </div>

          <!-- Visual Previews -->
          <div v-for="(content, type) in generatedResults" :key="type" class="cb-preview-block animate-slide-up">
            <div class="preview-header">
              <h3>{{ formatLabel(type) }}</h3>
              <div class="preview-actions">
                <button class="cb-btn-text" @click="discardContent(type)">Discard</button>
                <button class="cb-btn cb-btn-success" @click="applyContent(type)" :disabled="savingStates[type]">
                  {{ savingStates[type] ? 'Saving...' : 'Apply to Product' }}
                </button>
              </div>
            </div>
            
            <div class="split-view">
              <div class="split-pane pane-old">
                <div class="pane-badge badge-old">Current</div>
                <div class="pane-content html-render" v-if="productData[type]" v-html="productData[type]"></div>
                <div class="pane-content empty-text" v-else>No current {{ formatLabel(type).toLowerCase() }}.</div>
              </div>
              
              <div class="split-pane pane-new">
                <div class="pane-badge badge-new">AI Generated</div>
                <textarea class="pane-editor" v-model="generatedResults[type]"></textarea>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.cb-single-gen {
  max-width: 1200px;
}

.cb-header {
  margin-bottom: 24px;
}

.cb-header h2 {
  font-size: 28px;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 8px 0;
  font-family: 'Inter', system-ui, sans-serif;
}

.cb-header p {
  color: #64748b;
  font-size: 15px;
  margin: 0;
}

.cb-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
}

.cb-search-box {
  margin-bottom: 24px;
  padding: 20px 24px;
}

.cb-search-box h3 {
  margin: 0 0 16px 0;
  font-size: 16px;
  color: #334155;
}

.cb-search-form {
  display: flex;
  align-items: flex-end;
  gap: 16px;
}

.cb-search-form .cb-field {
  margin-bottom: 0;
  flex: 1;
  max-width: 300px;
}

.cb-field label {
  display: block;
  font-weight: 500;
  color: #475569;
  margin-bottom: 8px;
  font-size: 14px;
}

.cb-input {
  width: 100%;
  padding: 10px 14px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  font-size: 15px;
  color: #0f172a;
  transition: all 0.2s ease;
  box-sizing: border-box;
  font-family: inherit;
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

.cb-textarea {
  min-height: 80px;
  resize: vertical;
}

.cb-btn {
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 500;
  font-size: 15px;
  cursor: pointer;
  border: none;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.cb-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.cb-btn-primary {
  background: #334155;
  color: #fff;
}
.cb-btn-primary:hover:not(:disabled) { background: #1e293b; }

.cb-btn-brand {
  background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
  color: white;
  width: 100%;
  margin-top: 24px;
  padding: 12px;
  font-weight: 600;
  box-shadow: 0 4px 14px rgba(168, 85, 247, 0.4);
}
.cb-btn-brand:hover:not(:disabled) {
  opacity: 0.9;
  transform: translateY(-1px);
}

.cb-btn-success {
  background: #10b981;
  color: white;
  padding: 8px 16px;
  font-size: 14px;
}
.cb-btn-success:hover:not(:disabled) { background: #059669; }

.cb-btn-text {
  background: transparent;
  border: none;
  color: #64748b;
  font-weight: 500;
  cursor: pointer;
  padding: 8px 12px;
}
.cb-btn-text:hover { color: #ef4444; }

.cb-product-banner {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.product-title {
  margin: 0 0 4px 0;
  font-size: 18px;
  color: #0f172a;
}
.product-sku {
  font-size: 13px;
  color: #64748b;
}
.product-price {
  font-weight: 600;
  font-size: 18px;
  color: #10b981;
}

.cb-grid {
  display: flex;
  gap: 24px;
  align-items: flex-start;
}

.cb-sidebar {
  width: 320px;
  flex-shrink: 0;
}

.cb-sidebar .cb-card {
  padding: 20px;
}
.cb-sidebar h3 {
  margin: 0 0 20px 0;
  font-size: 16px;
  color: #0f172a;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 12px;
}

.cb-field {
  margin-bottom: 20px;
}
.cb-divider {
  height: 1px;
  background: #e2e8f0;
  margin: 24px 0;
}
.section-label {
  display: block;
  font-weight: 600;
  color: #334155;
  margin-bottom: 12px;
  font-size: 14px;
}

.cb-checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.cb-checkbox {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
}
.cb-checkbox input {
  width: 18px;
  height: 18px;
  border-radius: 4px;
  border: 1px solid #cbd5e1;
  accent-color: #6366f1;
  cursor: pointer;
}
.chk-label {
  font-size: 14px;
  color: #475569;
}

.cb-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.cb-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 40px;
  background: #ffffff;
  border-radius: 12px;
  border: 2px dashed #cbd5e1;
  text-align: center;
  color: #94a3b8;
}
.empty-icon {
  margin-bottom: 16px;
  color: #cbd5e1;
  animation: pulse 2s infinite ease-in-out;
}

.cb-preview-block {
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
  overflow: hidden;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}
.preview-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
}

.split-view {
  display: flex;
  min-height: 250px;
}
.split-pane {
  flex: 1;
  position: relative;
  padding: 20px;
  display: flex;
  flex-direction: column;
}
.split-pane + .split-pane {
  border-left: 1px solid #e2e8f0;
  background: #fafaf9;
}

.pane-badge {
  position: absolute;
  top: 0;
  right: 0;
  padding: 4px 12px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom-left-radius: 8px;
}
.badge-old { background: #f1f5f9; color: #64748b; }
.badge-new { background: #e0e7ff; color: #4338ca; }

.pane-content {
  margin-top: 10px;
  font-size: 14px;
  line-height: 1.6;
  color: #334155;
  overflow-y: auto;
}
.empty-text {
  color: #94a3b8;
  font-style: italic;
  text-align: center;
  padding-top: 20px;
}

.pane-editor {
  margin-top: 10px;
  width: 100%;
  flex: 1;
  min-height: 200px;
  padding: 12px;
  border-radius: 6px;
  border: 1px solid #cbd5e1;
  font-family: inherit;
  font-size: 14px;
  line-height: 1.6;
  color: #1e293b;
  resize: vertical;
  background: #ffffff;
  box-sizing: border-box;
}

.pane-editor:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.cb-messages { margin-bottom: 24px; }
.cb-msg { padding: 12px 16px; border-radius: 8px; margin-bottom: 12px; font-weight: 500; font-size: 14px; animation: slideIn 0.3s ease; }
.cb-msg-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.cb-msg-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

.cb-loading-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  color: #6366f1;
  font-weight: 600;
}
.spinner {
  width: 40px; height: 40px;
  border: 4px solid #e0e7ff;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin { to { transform: rotate(360deg); } }
@keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(0.95); } }
@keyframes slideIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fadeIn 0.4s ease forwards; }
.animate-slide-up { animation: slideUp 0.4s ease forwards; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* Ensure HTML preview formatting */
.html-render h1, .html-render h2, .html-render h3 { margin-top: 0; color: #0f172a; }
.html-render p { margin-bottom: 1em; }
.html-render ul, .html-render ol { margin-bottom: 1em; padding-left: 20px; }
</style>
