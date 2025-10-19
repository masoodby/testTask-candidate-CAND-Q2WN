import { mount } from '@vue/test-utils'
import Orders from '../src/components/Orders.vue'
import { vi, describe, it, expect, beforeEach } from 'vitest'


const flush = () => new Promise(r => setTimeout(r, 0))

describe('Orders.vue', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
   
    Object.keys(localStorage).forEach(k => localStorage.removeItem(k))
  })

  it('renders orders correctly (note in input value)', async () => {
    const mockResponse = {
      data: [{
        id: 1, user_id: 1, total: 100, created_at: '2025-01-01',
        note: 'test note', items_count: 2,
        payment: { method: 'card', status: 'paid' }
      }],
      count: 1, total: 1, has_next: false, has_prev: false, next_page: null, prev_page: null,
    }

    vi.spyOn(global, 'fetch' as any).mockResolvedValueOnce({
      ok: true,
      json: async () => mockResponse
    } as Response)

    const wrapper = mount(Orders)
    await flush()              
    await wrapper.vm.$nextTick()

    
    const input = wrapper.get('tbody input[type="text"]')
   
    expect((input.element as HTMLInputElement).value).toBe('test note')

    
    expect(wrapper.text()).toContain('card / paid')
  })

  it('optimistic note + PATCH + refetch with cache-bust', async () => {
   
    const list1 = {
      data: [{
        id: 88522, user_id: 1, total: 172.15, created_at: '2025-03-01 23:23:26',
        note: 'old', items_count: 5, payment: { method: 'card', status: 'paid' }
      }],
      count: 1, total: 1, has_next: false, has_prev: false, next_page: null, prev_page: null,
    }
   
    const patchResp = { ok: true, affected: 1, order_id: 88522, note: 'new-note' }
    
    const list2 = {
      ...list1,
      data: [{ ...list1.data[0], note: 'new-note' }]
    }

    const fetchMock = vi.spyOn(global, 'fetch' as any)
      .mockResolvedValueOnce({ ok: true, json: async () => list1 } as Response)   // GET
      .mockResolvedValueOnce({ ok: true, json: async () => patchResp } as Response) // PATCH
      .mockResolvedValueOnce({ ok: true, json: async () => list2 } as Response)   // GET(_cb)

    const wrapper = mount(Orders)
    await flush()


    const input = wrapper.get('tbody input[type="text"]')
    await input.setValue('new-note')
    const btn = wrapper.get('tbody button')
    await btn.trigger('click')

    await flush()
    await wrapper.vm.$nextTick()

   
    expect(fetchMock).toHaveBeenCalledTimes(3)

   
    expect((wrapper.get('tbody input[type="text"]').element as HTMLInputElement).value).toBe('new-note')
  })
})