/**
 * main.js — Entry point Vue 3
 * Sesuai 04_ARCHITECTURE.md §4.1
 */

// CSS harus diimport PERTAMA agar Tailwind ter-bundle oleh Vite
import './style.css'

import { createApp }  from 'vue'
import { createPinia } from 'pinia'
import App            from './App.vue'
import router         from './router/index.js'

// Global components
import SidebarItem  from '@/components/sidebar/SidebarItem.vue'
import SidebarGroup from '@/components/sidebar/SidebarGroup.vue'

const app   = createApp(App)
const pinia = createPinia()

// Plugins
app.use(pinia)
app.use(router)

// Daftarkan komponen global
app.component('SidebarItem',  SidebarItem)
app.component('SidebarGroup', SidebarGroup)

app.mount('#app')
