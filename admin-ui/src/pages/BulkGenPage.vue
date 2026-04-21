<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import wpAjax from '../api/ajax';

const loading = ref(false);
const pollingInterval = ref(null);
const errorMessage = ref('');

const products = ref([]);
const totalResults = ref(0);
const page = ref(1);

const search = ref('');
const selectedIds = ref([]);
const selectAll = ref(false);
const emptyOnly = ref(false);

const jobStatus = ref(null); // Will hold the transient response

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

const loadProducts = async () => {
  loading.value = true;
  try {
    const response = await wpAjax.post('catalogbench_get_products', { page: page.value, search: search.value });
    if (response.data.success) {
      products.value = response.data.data.products;
      totalResults.value = response.data.data.total_results;
    }
  } catch (e) {
    console.error('Failed to load products');
  } finally {
    loading.value = false;
  }
};

const toggleSelectAll = () => {
  if (selectAll.value) {
    if (emptyOnly.value) {
      selectedIds.value = products.value.filter(p => !p.has_desc).map(p => p.id);
    } else {
      selectedIds.value = products.value.map(p => p.id);
    }
  } else {
    selectedIds.value = [];
  }
};

const toggleEmptyOnly = () => {
  if (selectAll.value) {
    // Re-run select all logic to filter out the ones with description
    toggleSelectAll();
  }
};

const calculateCost = computed(() => {
  // Rough average: ~300 tokens per full product run. 
  // GPT-4.5/5.4 is ~$0.005 / 1K tokens. So ~$0.0015 per product.
  // Gemini is 0 on free tier, but let's base it purely on generic estimates.
  const costPerProduct = 0.0015;
  return (selectedIds.value.length * costPerProduct).toFixed(3);
});

const startBulkJob = async () => {
  if (selectedIds.value.length === 0) {
    alert('Select at least one product.');
    return;
  }
  
  loading.value = true;
  try {
    const payload = {
      product_ids: selectedIds.value,
      config: {
        tone: config.tone,
        custom_instruction: config.custom_instruction,
        types: config.types_to_generate
      }
    };
    
    const response = await wpAjax.post('catalogbench_start_bulk_job', payload);
    if (response.data.success) {
      jobStatus.value = { id: response.data.data.job_id, status: 'processing', completed: 0, total: selectedIds.value.length };
      pollStatus();
    } else {
      errorMessage.value = response.data.data.message || 'Failed to start job.';
    }
  } catch (err) {
    errorMessage.value = 'Failed to communicate with server.';
  } finally {
    loading.value = false;
  }
};

const pollStatus = () => {
  if (pollingInterval.value) clearInterval(pollingInterval.value);
  
  pollingInterval.value = setInterval(async () => {
    if (!jobStatus.value || !jobStatus.value.id) return;
    
    try {
      const response = await wpAjax.post('catalogbench_get_bulk_status', { job_id: jobStatus.value.id });
      if (response.data.success) {
        jobStatus.value = response.data.data;
        if (jobStatus.value.status === 'completed') {
          clearInterval(pollingInterval.value);
        }
      }
    } catch (e) {
      console.error('Polling error');
    }
  }, 2000);
};

const progressPercent = computed(() => {
  if (!jobStatus.value || !jobStatus.value.total) return 0;
  return Math.min(100, Math.round((jobStatus.value.completed / jobStatus.value.total) * 100));
});

const resetJob = () => {
  jobStatus.value = null;
  selectedIds.value = [];
  selectAll.value = false;
  if (pollingInterval.value) clearInterval(pollingInterval.value);
};

onMounted(() => {
  loadProducts();
});

onUnmounted(() => {
  if (pollingInterval.value) {
    clearInterval(pollingInterval.value);
  }
});

</script>

<template>
  <div class="cb-bulk-gen">
    <div class="cb-header">
      <h2>Bulk Processing Engine</h2>
      <p>Select products and run background AI generation batches using WooCommerce Action Scheduler. Content is auto-applied to products.</p>
    </div>

    <!-- Active Job View -->
    <div v-if="jobStatus" class="cb-card cb-job-card animate-fade-in">
      <h3>Job In Progress</h3>
      <p class="job-status-label" v-if="jobStatus.status === 'processing'">
        <span class="pulse-dot"></span> Processing in background... You may close this page.
      </p>
      <p class="job-status-label success" v-else>✅ Processing Completed.</p>
      
      <div class="progress-bar-container">
        <div class="progress-fill" :style="{ width: progressPercent + '%' }"></div>
      </div>
      
      <div class="job-stats">
        <div><strong>Completed:</strong> {{ jobStatus.completed }} / {{ jobStatus.total }}</div>
        <div><strong>Errors:</strong> <span class="error-count">{{ jobStatus.errors || 0 }}</span></div>
      </div>

      <button class="cb-btn cb-btn-secondary" v-if="jobStatus.status === 'completed'" @click="resetJob">Run Another Batch</button>
    </div>

    <!-- Selection View -->
    <div v-else class="cb-grid animate-fade-in">
      
      <!-- Data Table -->
      <div class="cb-main">
        <div class="cb-card filters-card">
          <input type="text" v-model="search" placeholder="Search by name or SKU..." class="cb-input" @keyup.enter="loadProducts" />
          <button class="cb-btn cb-btn-secondary" @click="loadProducts">Search</button>
          <label class="cb-filter-toggle" style="margin-left:12px; font-size:14px; display:inline-flex; align-items:center; gap:6px;">
            <input type="checkbox" v-model="emptyOnly" @change="toggleEmptyOnly" />
            Only select products with empty descriptions
          </label>
        </div>

        <div class="cb-card table-card">
          <table class="cb-table">
            <thead>
              <tr>
                <th width="40"><input type="checkbox" v-model="selectAll" @change="toggleSelectAll" /></th>
                <th>Product</th>
                <th>SKU</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in products" :key="p.id">
                <td><input type="checkbox" :value="p.id" v-model="selectedIds" /></td>
                <td class="primary-col">{{ p.name }}</td>
                <td>{{ p.sku || '-' }}</td>
                <td>
                  <span class="status-badge" :class="p.has_desc ? 'yes' : 'no'">{{ p.has_desc ? 'Has Content' : 'Empty' }}</span>
                </td>
              </tr>
              <tr v-if="products.length === 0">
                <td colspan="4" class="empty-table">No products found.</td>
              </tr>
            </tbody>
          </table>
          <div class="pagination">
            <button class="cb-btn cb-btn-text" @click="page--; loadProducts()" :disabled="page <= 1">Previous</button>
            <span>Page {{ page }}</span>
            <button class="cb-btn cb-btn-text" @click="page++; loadProducts()" :disabled="products.length < 50">Next</button>
          </div>
        </div>
      </div>

      <!-- Config Sidebar -->
      <div class="cb-sidebar">
        <div class="cb-card config-card">
          <h3>Bulk Configuration</h3>
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
          
          <div class="cb-divider"></div>
          
          <label class="section-label">Fields to Overwrite/Generate</label>
          <div class="cb-checkbox-group">
            <label class="cb-checkbox"><input type="checkbox" v-model="config.types_to_generate.description"> <span class="chk-label">Long Description</span></label>
            <label class="cb-checkbox"><input type="checkbox" v-model="config.types_to_generate.short_description"> <span class="chk-label">Short Description</span></label>
            <label class="cb-checkbox"><input type="checkbox" v-model="config.types_to_generate.seo_title"> <span class="chk-label">SEO Title</span></label>
            <label class="cb-checkbox"><input type="checkbox" v-model="config.types_to_generate.seo_meta"> <span class="chk-label">SEO Meta Description</span></label>
          </div>
          
          <div class="cb-divider"></div>

          <div class="cost-estimator">
            <div class="cost-stat">
              <span>Selected Products:</span>
              <strong>{{ selectedIds.length }}</strong>
            </div>
            <div class="cost-stat">
              <span>Estimated Cost:</span>
              <strong class="price-val">~${{ calculateCost }}</strong>
            </div>
          </div>

          <button class="cb-btn cb-btn-brand cb-generate-btn" @click="startBulkJob" :disabled="selectedIds.length === 0 || loading">
            🚀 Start Bulk Job
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.cb-bulk-gen {
  max-width: 1200px;
}
.cb-header { margin-bottom: 24px; }
.cb-header h2 { font-size: 28px; font-weight: 600; color: #1e293b; margin: 0 0 8px 0; }
.cb-header p { color: #64748b; font-size: 15px; margin: 0; }

.cb-card { background: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0; }
.cb-grid { display: flex; gap: 24px; align-items: flex-start; }
.cb-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 24px; }
.cb-sidebar { width: 320px; flex-shrink: 0; }

.filters-card { padding: 16px; display: flex; gap: 12px; }
.filters-card .cb-input { flex: 1; }

.cb-table { width: 100%; border-collapse: collapse; text-align: left; }
.cb-table th { padding: 12px 16px; border-bottom: 2px solid #e2e8f0; color: #475569; font-weight: 600; font-size: 13px; }
.cb-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-size: 14px; vertical-align: middle; }
.cb-table tr:hover td { background: #f8fafc; }
.primary-col { font-weight: 500; color: #0f172a; }

.empty-table { text-align: center; color: #64748b; padding: 40px !important; }

.status-badge { padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; }
.status-badge.yes { background: #dcfce7; color: #166534; }
.status-badge.no { background: #fee2e2; color: #991b1b; }

.pagination { display: flex; justify-content: space-between; align-items: center; padding: 16px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 14px; }

.config-card { padding: 20px; }
.config-card h3 { margin: 0 0 20px 0; font-size: 16px; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; }

.cb-field { margin-bottom: 20px; }
.cb-field label { display: block; font-weight: 500; margin-bottom: 8px; font-size: 14px; }
.cb-input { width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; box-sizing: border-box; }
.cb-select { appearance: none; background: url("data:image/svg+xml,...") right 0.5rem center no-repeat; padding-right: 2.5rem; }

.cb-checkbox-group { display: flex; flex-direction: column; gap: 12px; }
.cb-checkbox { display: flex; align-items: center; gap: 10px; cursor: pointer; }
.cb-divider { height: 1px; background: #e2e8f0; margin: 24px 0; }
.section-label { display: block; font-weight: 600; color: #334155; margin-bottom: 12px; font-size: 14px; }

.cost-estimator { background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #cbd5e1; }
.cost-stat { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: #334155; }
.cost-stat:last-child { margin-bottom: 0; }
.price-val { color: #10b981; font-size: 16px; }

.cb-btn { padding: 10px 20px; border-radius: 8px; font-weight: 500; font-size: 15px; cursor: pointer; border: none; transition: 0.2s; }
.cb-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.cb-btn-secondary { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
.cb-btn-secondary:hover:not(:disabled) { background: #e2e8f0; }
.cb-btn-brand { background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); color: white; width: 100%; margin-top: 24px; padding: 12px; font-weight: 600; }
.cb-btn-brand:hover:not(:disabled) { transform: translateY(-1px); }

/* Job Card */
.cb-job-card { padding: 30px; text-align: center; }
.progress-bar-container { width: 100%; height: 16px; background: #f1f5f9; border-radius: 8px; overflow: hidden; margin: 24px 0; }
.progress-fill { height: 100%; background: linear-gradient(90deg, #6366f1, #a855f7); transition: width 0.5s ease-in-out; }
.job-status-label { font-weight: 500; color: #475569; display: flex; align-items: center; justify-content: center; gap: 8px; }
.job-status-label.success { color: #10b981; font-size: 18px; }
.pulse-dot { width: 10px; height: 10px; background: #6366f1; border-radius: 50%; animation: pulse 1.5s infinite; }
.job-stats { display: flex; justify-content: center; gap: 24px; font-size: 15px; margin-bottom: 24px; color: #334155; }
.error-count { color: #ef4444; font-weight: 600; }

.animate-fade-in { animation: fadeIn 0.4s ease forwards; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes pulse { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.7); } 70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(99, 102, 241, 0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); } }
</style>
