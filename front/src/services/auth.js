import { computed, ref } from 'vue';
import { apiRequest } from './api';
import { useCart } from './cart';

const user = ref(null);
const checked = ref(false);
const cartMergeNotice = ref('');
const isAdmin = computed(() => user.value?.role === 'admin');

function showCartMergeNotice(result) {
  const messages = [];

  for (const item of result?.adjusted_items || []) {
    messages.push(`${item.title}: quantidade ajustada de ${item.requested_quantity} para ${item.kept_quantity}, conforme o estoque.`);
  }

  for (const item of result?.removed_items || []) {
    messages.push(`${item.title}: não foi adicionado porque está indisponível.`);
  }

  cartMergeNotice.value = messages.join(' ');
}

export function useAuth() {
  const { refreshCart } = useCart();

  async function loadCurrentUser() {
    if (checked.value) return user.value;

    try {
      const response = await apiRequest('/api/user');
      user.value = response.data;
    } catch {
      user.value = null;
    } finally {
      checked.value = true;
    }

    return user.value;
  }

  async function signIn(credentials) {
    const response = await apiRequest('/api/auth/login', {
      method: 'POST',
      body: credentials,
    });

    user.value = response.data;
    checked.value = true;
    showCartMergeNotice(response.cart_merge);
    await refreshCart();

    return response.cart_merge;
  }

  async function register(attributes) {
    const response = await apiRequest('/api/auth/register', {
      method: 'POST',
      body: attributes,
    });

    user.value = response.data;
    checked.value = true;
    showCartMergeNotice(response.cart_merge);
    await refreshCart();

    return response.cart_merge;
  }

  async function signOut() {
    await apiRequest('/api/auth/logout', { method: 'POST' });
    user.value = null;
    checked.value = false;
    cartMergeNotice.value = '';
  }

  return { user, isAdmin, cartMergeNotice, loadCurrentUser, signIn, register, signOut };
}
