import { computed, ref } from 'vue';
import { apiRequest } from './api';

const items = ref([]);
const subtotal = ref('0.00');
const cartCount = computed(() => items.value.reduce((total, item) => total + item.quantity, 0));

function updateCart(response) {
  items.value = response.data.items;
  subtotal.value = response.data.subtotal;
}

export function useCart() {
  async function refreshCart() {
    const response = await apiRequest('/api/cart');
    updateCart(response);
  }

  async function addItem(mangaId, quantity = 1) {
    const response = await apiRequest('/api/cart/items', {
      method: 'POST',
      body: { manga_id: mangaId, quantity },
    });
    updateCart(response);
  }

  async function updateItem(itemId, quantity) {
    const response = await apiRequest(`/api/cart/items/${itemId}`, {
      method: 'PATCH',
      body: { quantity },
    });
    updateCart(response);
  }

  async function removeItem(itemId) {
    await apiRequest(`/api/cart/items/${itemId}`, { method: 'DELETE' });
    await refreshCart();
  }

  async function clearCart() {
    await apiRequest('/api/cart', { method: 'DELETE' });
    await refreshCart();
  }

  return { items, subtotal, cartCount, refreshCart, addItem, updateItem, removeItem, clearCart };
}
