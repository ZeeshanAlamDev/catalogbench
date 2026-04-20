import { createRouter, createWebHashHistory } from 'vue-router'
import DashboardPage from './pages/DashboardPage.vue'

const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    {
      path: '/',
      name: 'dashboard',
      component: DashboardPage
    },
    {
      path: '/single-generator',
      name: 'single-generator',
      component: () => import('./pages/SingleGenPage.vue')
    },
    {
      path: '/bulk-generator',
      name: 'bulk-generator',
      component: () => import('./pages/BulkGenPage.vue')
    },
    {
      path: '/settings',
      name: 'settings',
      component: () => import('./pages/SettingsPage.vue')
    }
  ]
})

export default router
