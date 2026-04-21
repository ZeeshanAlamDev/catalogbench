<script setup>
import { ref, onMounted } from 'vue';
import wpAjax from '../api/ajax';
import { useRouter } from 'vue-router';

const router = useRouter();
const stats = ref({ total_products: 0, optimized_count: 0 });
const loading = ref(true);

const loadStats = async () => {
  try {
    const response = await wpAjax.post('catalogbench_get_dashboard_stats');
    if (response.data.success) {
      stats.value = response.data.data;
    }
  } catch (err) {
    console.error('Failed to load dashboard stats');
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadStats();
});
</script>

<template>
  <div class="cb-dashboard">
    <div class="cb-header">
      <h2>Dashboard Overview</h2>
      <p>Welcome to CatalogBench. View your overall optimization metrics and start generating AI content.</p>
    </div>

    <div v-if="loading" class="cb-loading">Loading stats...</div>

    <div v-else class="cb-stats-grid">
      <!-- Total Products -->
      <div class="cb-stat-card">
        <div class="cb-stat-icon" style="background: #eff6ff; color: #3b82f6;">
          <span class="dashicons dashicons-products"></span>
        </div>
        <div class="cb-stat-info">
          <h4>Total Products</h4>
          <span class="cb-stat-number">{{ stats.total_products }}</span>
        </div>
      </div>

      <!-- Optimized Count -->
      <div class="cb-stat-card">
        <div class="cb-stat-icon" style="background: #f0fdf4; color: #16a34a;">
          <span class="dashicons dashicons-saved"></span>
        </div>
        <div class="cb-stat-info">
          <h4>Optimized with AI</h4>
          <span class="cb-stat-number">{{ stats.optimized_count }}</span>
        </div>
      </div>

      <!-- Remaining (Calc) -->
      <div class="cb-stat-card">
        <div class="cb-stat-icon" style="background: #fdf2f8; color: #db2777;">
          <span class="dashicons dashicons-clock"></span>
        </div>
        <div class="cb-stat-info">
          <h4>Needs Optimization</h4>
          <span class="cb-stat-number">{{ Math.max(0, stats.total_products - stats.optimized_count) }}</span>
        </div>
      </div>
    </div>

    <div class="cb-quick-actions" v-if="!loading">
      <h3>Quick Actions</h3>
      <div class="cb-actions-grid">
        <div class="cb-action-card" @click="router.push('/single')">
          <h4>🎯 Single Product Generator</h4>
          <p>Visually preview and test AI generated descriptions side-by-side for a single product.</p>
          <span class="cb-action-btn">Launch <span class="dashicons dashicons-arrow-right-alt2"></span></span>
        </div>

        <div class="cb-action-card" @click="router.push('/bulk')">
          <h4>🚀 Bulk Processing Engine</h4>
          <p>Select multiple products and auto-optimize their descriptions and SEO data in the background.</p>
          <span class="cb-action-btn">Launch <span class="dashicons dashicons-arrow-right-alt2"></span></span>
        </div>

        <div class="cb-action-card" @click="router.push('/settings')">
          <h4>⚙️ AI Configuration</h4>
          <p>Manage your Gemini / OpenAI API keys and default AI prompt settings.</p>
          <span class="cb-action-btn">Manage <span class="dashicons dashicons-arrow-right-alt2"></span></span>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.cb-dashboard {
  max-width: 1000px;
  animation: fadeIn 0.3s ease;
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

.cb-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.cb-stat-card {
  background: #fff;
  border-radius: 12px;
  padding: 24px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
}

.cb-stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cb-stat-icon .dashicons {
  font-size: 24px;
  width: 24px;
  height: 24px;
}

.cb-stat-info h4 {
  margin: 0;
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
}

.cb-stat-number {
  font-size: 28px;
  font-weight: 700;
  color: #0f172a;
  display: block;
  margin-top: 4px;
}

.cb-quick-actions h3 {
  font-size: 20px;
  color: #1e293b;
  margin-bottom: 16px;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 8px;
}

.cb-actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.cb-action-card {
  background: #fff;
  border-radius: 12px;
  padding: 24px;
  border: 1px solid #e2e8f0;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.cb-action-card:hover {
  transform: translateY(-2px);
  border-color: #cbd5e1;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.cb-action-card h4 {
  margin: 0 0 10px 0;
  font-size: 16px;
  color: #0f172a;
}

.cb-action-card p {
  margin: 0 0 16px 0;
  color: #64748b;
  font-size: 14px;
  line-height: 1.5;
}

.cb-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #3b82f6;
  font-weight: 500;
  font-size: 14px;
}

.cb-action-btn .dashicons {
  font-size: 16px;
  width: 16px;
  height: 16px;
  margin-top: 2px;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
