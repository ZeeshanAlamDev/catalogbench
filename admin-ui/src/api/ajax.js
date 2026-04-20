import axios from 'axios'

// Ensure catalogbenchData is available (injected via wp_localize_script)
const wpData = window.catalogbenchData || {
  ajaxUrl: '',
  nonce: '',
}

const ajax = axios.create({
  baseURL: wpData.ajaxUrl,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
  },
})

// Automatically append standard WP AJAX parameters
ajax.interceptors.request.use((config) => {
  // If it's a POST request and data is FormData, append nonce
  if (config.data instanceof FormData) {
    config.data.append('_ajax_nonce', wpData.nonce)
  }
  // If it's a URL-encoded string or object
  else if (config.method === 'post' || config.method === 'put') {
    if (!config.data) config.data = {}
    
    // Convert object to URLSearchParams for standard WP AJAX handling
    if (!(config.data instanceof URLSearchParams)) {
      const params = new URLSearchParams()
      for (const key in config.data) {
        if (typeof config.data[key] === 'object') {
          params.append(key, JSON.stringify(config.data[key]))
        } else {
          params.append(key, config.data[key])
        }
      }
      params.append('_ajax_nonce', wpData.nonce)
      config.data = params
    } else {
      config.data.append('_ajax_nonce', wpData.nonce)
    }
  }
  // If it's a GET request
  else if (config.method === 'get') {
    if (!config.params) config.params = {}
    config.params._ajax_nonce = wpData.nonce
  }

  return config
})

export default ajax
