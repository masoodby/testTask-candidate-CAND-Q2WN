<script setup>
import { ref, watch, onMounted } from 'vue'
// Deliberately simplistic & flawed (candidate should improve UX/state)
const userId = ref(1)
const page = ref(1)
const per = ref(20)
const loading = ref(false)
const data = ref([])
const count = ref(0)
const error = ref('')

async function fetchOrders() {
  loading.value = true
  error.value = ''
  try {
    const q = new URLSearchParams({ user_id: String(userId.value), page: String(page.value), per_page: String(per.value) })
    const res = await fetch(`http://127.0.0.1:8080/api/orders?${q.toString()}`)
    const json = await res.json()
    data.value = json.data
    count.value = json.count
  } catch (e) {
    error.value = String(e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchOrders)
watch([userId, page, per], fetchOrders)
</script>

<template>
  <section>
    <div style="display:flex; gap:8px; margin-bottom:12px;">
      <label>User: <input type="number" v-model="userId" min="1" /></label>
      <label>Page: <input type="number" v-model="page" min="1" /></label>
      <label>Per: <input type="number" v-model="per" min="1" max="100" /></label>
      <button @click="fetchOrders">Reload</button>
    </div>

    <div v-if="loading">Loading...</div>
    <div v-else-if="error">Error: {{ error }}</div>
    <div v-else>
      <div v-if="count === 0">No orders.</div>
      <table border="1" cellspacing="0" cellpadding="6">
        <thead>
          <tr><th>ID</th><th>Total</th><th>Created At</th><th>Payment</th><th>Items</th></tr>
        </thead>
        <tbody>
          <tr v-for="r in data" :key="r.id">
            <td>{{ r.id }}</td>
            <td>{{ r.total }}</td>
            <td>{{ r.created_at }}</td>
            <td>{{ r.payment?.method }} / {{ r.payment?.status }}</td>
            <td>{{ r.items_count }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
