import { createRouter, createWebHashHistory } from 'vue-router'

const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    {
      path: '/',
      name: 'dashboard',
      component: () => import('./pages/DashboardPage.vue')
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
