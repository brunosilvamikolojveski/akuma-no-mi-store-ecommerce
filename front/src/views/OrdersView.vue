<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import StoreHeader from '../components/StoreHeader.vue';
import { apiRequest } from '../services/api';
import { useAuth } from '../services/auth';
import { useCart } from '../services/cart';

const { user, loadCurrentUser } = useAuth();
const { cartCount } = useCart();
const orders = ref([]);
const loading = ref(true);
const busyOrderId = ref(null);
const errorMessage = ref('');
const actionMessages = ref({});

onMounted(loadOrders);

async function loadOrders() {
  try {
    await loadCurrentUser();

    if (user.value) {
      const response = await apiRequest('/api/orders');
      orders.value = response.data;
    }
  } catch (error) {
    errorMessage.value = error.message;
  } finally {
    loading.value = false;
  }
}

async function confirmPayment(order) {
  busyOrderId.value = order.id;
  setActionMessage(order.id, '');

  try {
    const response = await apiRequest(`/api/orders/${order.id}/confirm-payment`, { method: 'POST' });
    replaceOrder(response.data);
    setActionMessage(order.id, 'Pagamento confirmado.');
  } catch (error) {
    setActionMessage(order.id, error.message);
  } finally {
    busyOrderId.value = null;
  }
}

async function cancelOrder(order) {
  busyOrderId.value = order.id;
  setActionMessage(order.id, '');

  try {
    const response = await apiRequest(`/api/orders/${order.id}/cancel`, { method: 'POST' });
    replaceOrder(response.data);
    setActionMessage(order.id, 'Pedido cancelado. O estoque foi devolvido.');
  } catch (error) {
    setActionMessage(order.id, error.message);
  } finally {
    busyOrderId.value = null;
  }
}

function replaceOrder(updatedOrder) {
  orders.value = orders.value.map((order) => order.id === updatedOrder.id ? updatedOrder : order);
}

function setActionMessage(orderId, message) {
  actionMessages.value = { ...actionMessages.value, [orderId]: message };
}

function statusLabel(status) {
  return {
    pending: 'Aguardando pagamento',
    paid: 'Pago',
    shipped: 'Enviado',
    delivered: 'Entregue',
    cancelled: 'Cancelado',
  }[status] || status;
}

function paymentMethodLabel(method) {
  return {
    pix: 'Pix',
    boleto: 'Boleto',
    credit_card: 'Cartão de crédito',
  }[method] || method;
}

function formatPrice(price) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(Number(price));
}

function formatDate(date) {
  return date ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'medium' }).format(new Date(date)) : 'Data indisponível';
}
</script>

<template>
  <div class="site-shell">
    <StoreHeader :cart-count="cartCount" :show-search="false" />

    <main class="container orders-page">
      <div class="orders-heading">
        <div>
          <span class="eyebrow">Sua conta</span>
          <h1>Meus pedidos</h1>
          <p v-if="user">Acompanhe seus pedidos, {{ user.name }}.</p>
        </div>
        <RouterLink class="continue-link" to="/">Voltar à loja</RouterLink>
      </div>

      <p v-if="loading" class="state-message" role="status">Carregando pedidos…</p>

      <section v-else-if="!user" class="checkout-message">
        <h2>Entre para consultar seus pedidos</h2>
        <p>Faça login com a conta usada na compra.</p>
        <RouterLink class="retry-button" :to="{ name: 'auth', query: { redirect: '/pedidos' } }">Entrar</RouterLink>
      </section>

      <section v-else-if="errorMessage" class="checkout-message" role="alert">
        <h2>Não foi possível carregar seus pedidos</h2>
        <p>{{ errorMessage }}</p>
        <button class="retry-button" @click="loadOrders">Tentar novamente</button>
      </section>

      <section v-else-if="!orders.length" class="empty-state">
        <h2>Você ainda não fez pedidos</h2>
        <p>Quando finalizar uma compra, ela aparecerá aqui.</p>
        <RouterLink class="retry-button" to="/">Explorar catálogo</RouterLink>
      </section>

      <section v-else class="orders-list" aria-label="Lista de pedidos">
        <article v-for="order in orders" :key="order.id" class="order-card">
          <header class="order-card-header">
            <div>
              <span class="eyebrow">Pedido #{{ order.id }}</span>
              <p>Realizado em {{ formatDate(order.placed_at) }}</p>
            </div>
            <span class="order-status" :class="`status-${order.status}`">{{ statusLabel(order.status) }}</span>
          </header>

          <div class="order-card-body">
            <div class="order-product-list">
              <div v-for="item in order.items" :key="item.id" class="order-product-row">
                <span>{{ item.quantity }} × {{ item.manga_title }} · Vol. {{ item.volume_number }}</span>
                <strong>{{ formatPrice(item.line_total) }}</strong>
              </div>
            </div>

            <div class="order-card-footer">
              <div class="order-payment-info">
                <span>Pagamento: {{ paymentMethodLabel(order.payments[0]?.method) }}</span>
                <strong>Total: {{ formatPrice(order.total) }}</strong>
              </div>
              <div v-if="order.status === 'pending'" class="order-actions">
                <button :disabled="busyOrderId === order.id" @click="confirmPayment(order)">
                  {{ busyOrderId === order.id ? 'Aguarde…' : 'Confirmar pagamento (simulado)' }}
                </button>
                <button class="cancel-order-button" :disabled="busyOrderId === order.id" @click="cancelOrder(order)">
                  Cancelar pedido
                </button>
              </div>
            </div>
            <p v-if="actionMessages[order.id]" class="order-action-message" role="status">{{ actionMessages[order.id] }}</p>
          </div>
        </article>
      </section>
    </main>

    <footer class="footer cart-footer">
      <div class="container footer-bottom">
        <span>© {{ new Date().getFullYear() }} Mangáverse</span>
        <RouterLink to="/">Voltar à loja</RouterLink>
      </div>
    </footer>
  </div>
</template>
