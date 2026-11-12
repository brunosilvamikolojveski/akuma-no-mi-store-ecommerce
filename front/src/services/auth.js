import { computed, ref } from 'vue';
import { apiRequest } from './api';
import { useCart } from './cart';

const user = ref(null);
const checked = ref(false);
const isAdmin = computed(() => user.value?.role === 'admin');

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
    await refreshCart();

    return response.cart_merge;
  }

  async function signOut() {
    await apiRequest('/api/auth/logout', { method: 'POST' });
    user.value = null;
    checked.value = false;
  }

  return { user, isAdmin, loadCurrentUser, signIn, register, signOut };
}
