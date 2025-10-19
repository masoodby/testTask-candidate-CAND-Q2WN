<script setup>
import { ref, watch, onMounted, computed } from 'vue'

/**
 * Orders.vue — Improved UX/State
 * - Skeletons + Empty state
 * - AbortController cancellation on filter changes
 * - Offline cache (localStorage) for last successful response
 * - Optimistic update + rollback for order note editing
 * - Cache-bust fetch after PATCH to avoid stale data
 */

const API = import.meta.env.VITE_API_BASE || 'http://127.0.0.1:8080'

// filters/state
const userId = ref(1)
const page = ref(1)
const per = ref(20)

const loading = ref(false)
const fromCache = ref(false)
const error = ref('')
const rows = ref([])
const count = ref(0)
const total = ref(0)
const hasNext = ref(false)
const hasPrev = ref(false)
const nextPage = ref(null)
const prevPage = ref(null)

// optimistic
const savingNoteId = ref(null)

// AbortController
let controller /** @type AbortController | null */ = null

// اگر کاربر فیلد را خالی/۰ کرد، 1 استفاده شود تا 400 نشود
const safeUserId = computed(() => {
  const n = Number(userId.value)
  return Number.isFinite(n) && n > 0 ? n : 1
})

// cache key based on filters (از userId واقعی استفاده می‌کنیم)
const cacheKey = computed(() =>
  `orders:user:${userId.value}:page:${page.value}:per:${per.value}`
)

// helper: save to cache
function cacheSave(json) {
  try {
    localStorage.setItem(
      cacheKey.value,
      JSON.stringify({ ts: Date.now(), json })
    )
  } catch (_) {}
}

// helper: load from cache
function cacheLoad() {
  try {
    const raw = localStorage.getItem(cacheKey.value)
    if (!raw) return null
    const { json } = JSON.parse(raw)
    return json
  } catch (_) {
    return null
  }
}

// core fetch (با گزینه‌ی cacheBust برای دور زدن کش)
async function fetchOrders(opts = {}) {
  const cacheBust = !!opts.cacheBust

  // cancel previous if any
  if (controller) controller.abort()
  controller = new AbortController()
  const signal = controller.signal

  loading.value = true
  error.value = ''
  fromCache.value = false

  const q = new URLSearchParams({
    user_id: String(safeUserId.value),
    page: String(page.value),
    per_page: String(per.value),
  })
  if (cacheBust) q.set('_cb', String(Date.now()))

  try {
    const res = await fetch(`${API}/api/orders?${q.toString()}`, { signal })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const json = await res.json()

    // state
    rows.value = json.data ?? []
    count.value = json.count ?? (json.data?.length ?? 0)
    total.value = json.total ?? 0
    hasNext.value = !!json.has_next
    hasPrev.value = !!json.has_prev
    nextPage.value = json.next_page ?? null
    prevPage.value = json.prev_page ?? null

    // cache: در حالت cacheBust کش محلی ننویس
    if (!cacheBust) cacheSave(json)
  } catch (e) {
    if (e && typeof e === 'object' && e.name === 'AbortError') {
      return
    }
    const cached = cacheLoad()
    // در حالت cacheBust از کش محلی هم نخون
    if (!cacheBust && cached) {
      rows.value = cached.data ?? []
      count.value = cached.count ?? (cached.data?.length ?? 0)
      total.value = cached.total ?? 0
      hasNext.value = !!cached.has_next
      hasPrev.value = !!cached.has_prev
      nextPage.value = cached.next_page ?? null
      prevPage.value = cached.prev_page ?? null
      fromCache.value = true
      error.value = 'نمایش از کش آفلاین'
    } else {
      error.value = String(e)
      rows.value = []
      count.value = 0
      total.value = 0
      hasNext.value = false
      hasPrev.value = false
      nextPage.value = null
      prevPage.value = null
    }
  } finally {
    loading.value = false
  }
}

// Debounce کوچک برای تغییر فیلترها
let debounceT = null
function debouncedFetch() {
  if (debounceT) clearTimeout(debounceT)
  debounceT = setTimeout(fetchOrders, 250)
}

onMounted(fetchOrders)
watch([userId, page, per], debouncedFetch)

// ---------- Optimistic note update ----------
async function saveNote(order, newNote) {
  if (!order || !order.id) return
  const prevNote = order.note
  order.note = newNote
  savingNoteId.value = order.id

  try {
    const res = await fetch(`${API}/api/orders/${order.id}/note`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ note: newNote }),
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)

    // (اختیاری) لاگ پاسخ
    // const body = await res.json().catch(() => ({}))
    // console.log('PATCH result:', res.status, body)

    // پس از موفقیت، فوراً با cache-bust ری‌فچ کن تا هیچ کشی بین راه نباشد
    await fetchOrders({ cacheBust: true })

    // کش محلی را هم به‌روز کن (پشتیبان)
    const cached = cacheLoad()
    if (cached && Array.isArray(cached.data)) {
      const idx = cached.data.findIndex(r => r.id === order.id)
      if (idx >= 0) {
        cached.data[idx].note = newNote
        cacheSave(cached)
      }
    }
  } catch (e) {
    // rollback
    order.note = prevNote
    alert('ذخیره‌ی یادداشت ناموفق بود؛ مقدار قبلی بازگردانده شد.')
  } finally {
    savingNoteId.value = null
  }
}
</script>

<template>
  <section style="display:flex; flex-direction:column; gap:12px;">
    <!-- Filters -->
    <div style="display:flex; gap:8px; align-items:center;">
      <label>User:
        <input type="number" v-model.number="userId" min="1" style="width:100px;" />
      </label>
      <label>Page:
        <input type="number" v-model.number="page" min="1" style="width:100px;" />
      </label>
      <label>Per:
        <input type="number" v-model.number="per" min="1" max="100" style="width:100px;" />
      </label>
      <button @click="fetchOrders">Reload</button>

      <span v-if="fromCache" title="Offline cache" style="margin-left:auto; font-size:12px; padding:2px 6px; border-radius:6px; background:#eee;">
        offline cache
      </span>
    </div>

    <!-- Status / Errors -->
    <div v-if="error && !loading" style="color:#b00020;">Error: {{ error }}</div>

    <!-- Skeletons -->
    <div v-if="loading">
      <table border="1" cellspacing="0" cellpadding="6" style="width:100%; opacity:0.7;">
        <thead>
          <tr><th style="width:70px;">ID</th><th>Total</th><th>Created At</th><th>Payment</th><th>Items</th><th>Note</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <tr v-for="i in 8" :key="i">
            <td><div class="skl"></div></td>
            <td><div class="skl"></div></td>
            <td><div class="skl"></div></td>
            <td><div class="skl"></div></td>
            <td><div class="skl"></div></td>
            <td><div class="skl"></div></td>
            <td><div class="skl btn"></div></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty -->
    <div v-else-if="count === 0">
      <div style="padding:16px; border:1px dashed #aaa; border-radius:8px;">
        هیچ سفارشی پیدا نشد. پارامترها را تغییر دهید و دوباره تلاش کنید.
      </div>
    </div>

    <!-- Table -->
    <div v-else>
      <table border="1" cellspacing="0" cellpadding="6" style="width:100%;">
        <thead>
          <tr>
            <th style="width:70px;">ID</th>
            <th>Total</th>
            <th>Created At</th>
            <th>Payment</th>
            <th>Items</th>
            <th style="min-width:240px;">Note (optimistic)</th>
            <th style="width:120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows" :key="r.id">
            <td>{{ r.id }}</td>
            <td>{{ r.total }}</td>
            <td>{{ r.created_at }}</td>
            <td>{{ r.payment?.method }} / {{ r.payment?.status }}</td>
            <td>{{ r.items_count }}</td>
            <td>
              <input
                v-model="r.note"
                type="text"
                placeholder="type a note..."
                style="width:100%;"
                :disabled="savingNoteId === r.id"
              />
            </td>
            <td>
              <button
                @click="saveNote(r, r.note)"
                :disabled="savingNoteId === r.id"
              >
                {{ savingNoteId === r.id ? 'Saving…' : 'Save' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination footer -->
      <div style="display:flex; align-items:center; gap:8px; margin-top:8px;">
        <button :disabled="!hasPrev" @click="page = prevPage || Math.max(1, page - 1)">Prev</button>
        <span> Page {{ page }} </span>
        <button :disabled="!hasNext" @click="page = nextPage || (page + 1)">Next</button>
        <span style="margin-left:auto; font-size:12px; color:#555;">
          Showing {{ count }} / Total {{ total }}
        </span>
      </div>
    </div>
  </section>
</template>

<style scoped>
.skl {
  height: 12px;
  border-radius: 6px;
  background: linear-gradient(90deg, #eee 25%, #f6f6f6 37%, #eee 63%);
  background-size: 400% 100%;
  animation: shimmer 1.2s infinite;
}
.skl.btn { height: 24px; border-radius: 4px; }
@keyframes shimmer {
  0% { background-position: 100% 0; }
  100% { background-position: 0 0; }
}
</style>