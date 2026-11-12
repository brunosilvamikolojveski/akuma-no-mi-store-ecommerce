<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import AdminHeader from '../components/AdminHeader.vue';
import { apiRequest } from '../services/api';
import { useAuth } from '../services/auth';

const { user, loadCurrentUser } = useAuth();
const orders = ref([]);
const selectedStatus = ref('');
const loading = ref(true);
const busyOrderId = ref(null);
const errorMessage = ref('');
const actionMessages = ref({});
const statusFilters = [
  { value: '', label: 'Todos' },
  { value: 'pending', label: 'Aguardando' },
  { value: 'paid', label: 'Pagos' },
  { value: 'shipped', label: 'Enviados' },
  { value: 'delivered', label: 'Entregues' },
  { value: 'cancelled', label: 'Cancelados' },
];
const visibleOrders = computed(() => selectedStatus.value
  ? orders.value.filter((order) => order.status === selectedStatus.value)
  : orders.value);
const counts = computed(() => ({
  all: orders.value.length,
  pending: orders.value.filter((order) => order.status === 'pending').length,
  processing: orders.value.filter((order) => ['paid', 'shipped'].includes(order.status)).length,
  delivered: orders.value.filter((order) => order.status === 'delivered').length,
}));

onMounted(initialize);

async function initialize() {
  await loadCurrentUser();
  loading.value = false;

  if (user.value?.role === 'admin') {
    await loadOrders();
  }
}

async function loadOrders() {
  if (user.value?.role !== 'admin') return;

  loading.value = true;
  errorMessage.value = '';
  try {
    const response = await apiRequest('/api/admin/orders');
    orders.value = response.data;
  } catch (error) {
    errorMessage.value = error.message;
  } finally {
    loading.value = false;
  }
}

function nextStatus(order) {
  return {
    pending: 'cancelled',
    paid: 'shipped',
    shipped: 'delivered',
  }[order.status];
}

function actionLabel(status) {
  return {
    cancelled: 'Cancelar pedido',
    shipped: 'Marcar como enviado',
    delivered: 'Marcar como entregue',
  }[status];
}

async function updateStatus(order) {
  const status = nextStatus(order);
  if (!status) return;

  busyOrderId.value = order.id;
  setActionMessage(order.id, '');

  try {
    const response = await apiRequest(`/api/admin/orders/${order.id}/status`, {
      method: 'PATCH',
      body: { status },
    });
    orders.value = orders.value.map((current) => current.id === order.id ? response.data : current);
    setActionMessage(order.id, `Status atualizado: ${statusLabel(response.data.status)}.`);
  } catch (error) {
    setActionMessage(order.id, error.message);
  } finally {
    busyOrderId.value = null;
  }
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
  return date ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(date)) : 'Data indisponível';
}
</script>

<template>
  <div class="site-shell">
    <AdminHeader />

    <main class="admin-orders-background">
      <div class="container admin-orders-page">
      <div class="orders-heading">
        <div>
          <span class="eyebrow">Painel administrativo</span>
          <h1>Gestão de pedidos</h1>
          <p>Veja os pedidos recentes e atualize o andamento das entregas.</p>
        </div>
        <RouterLink class="continue-link" to="/">Voltar à loja</RouterLink>
      </div>

      <section v-if="loading && user?.role !== 'admin'" class="state-message" role="status">Verificando acesso…</section>
      <section v-else-if="user?.role !== 'admin'" class="checkout-message">
        <h2>Acesso restrito</h2>
        <p>Entre com uma conta administrativa para gerenciar pedidos.</p>
        <RouterLink class="retry-button" :to="{ name: 'auth', query: { redirect: '/admin/pedidos' } }">Entrar como administrador</RouterLink>
      </section>
      <template v-else>
        <div class="admin-stats">
          <article class="admin-stat-card">
            <span>Pedidos totais</span>
            <strong>{{ counts.all }}</strong>
            <small>Em todos os status</small>
          </article>
          <article class="admin-stat-card pending-stat">
            <span>Aguardando pagamento</span>
            <strong>{{ counts.pending }}</strong>
            <small>Precisam de atenção</small>
          </article>
          <article class="admin-stat-card processing-stat">
            <span>Em andamento</span>
            <strong>{{ counts.processing }}</strong>
            <small>Pagos ou enviados</small>
          </article>
          <article class="admin-stat-card delivered-stat">
            <span>Entregues</span>
            <strong>{{ counts.delivered }}</strong>
            <small>Pedidos concluídos</small>
          </article>
        </div>

        <div class="admin-order-filter" aria-label="Filtrar pedidos por status">
          <button
            v-for="filter in statusFilters"
            :key="filter.value || 'all'"
            :class="{ active: selectedStatus === filter.value }"
            @click="selectedStatus = filter.value"
          >
            {{ filter.label }}
            <span>{{ filter.value ? orders.filter((order) => order.status === filter.value).length : orders.length }}</span>
          </button>
        </div>

        <p v-if="loading" class="state-message" role="status">Carregando pedidos…</p>
        <section v-else-if="errorMessage" class="checkout-message" role="alert">
          <h2>Não foi possível carregar os pedidos</h2>
          <p>{{ errorMessage }}</p>
          <button class="retry-button" @click="loadOrders">Tentar novamente</button>
        </section>
        <section v-else-if="!visibleOrders.length" class="empty-state admin-empty-state">
          <h2>Nenhum pedido neste filtro</h2>
          <p>Quando houver pedidos, eles aparecerão aqui.</p>
        </section>

        <section v-else class="orders-list" aria-label="Pedidos para administrar">
          <article v-for="order in visibleOrders" :key="order.id" class="order-card admin-order-card">
            <header class="order-card-header">
              <div>
                <span class="eyebrow">Pedido #{{ order.id }}</span>
                <p>{{ formatDate(order.placed_at) }} · {{ order.items.length }} item(ns)</p>
              </div>
              <span class="order-status" :class="`status-${order.status}`">{{ statusLabel(order.status) }}</span>
            </header>

            <div class="order-card-body">
              <div class="admin-order-content">
              <div class="order-product-list">
                <div v-for="item in order.items" :key="item.id" class="order-product-row">
                  <span>{{ item.quantity }} × {{ item.manga_title }} · Vol. {{ item.volume_number }}</span>
                  <strong>{{ formatPrice(item.line_total) }}</strong>
                </div>
              </div>

              <div class="admin-shipping-address">
                <span>Entrega para {{ order.shipping_address.recipient_name }}</span>
                <p>
                  {{ order.shipping_address.street }}, {{ order.shipping_address.number }} —
                  {{ order.shipping_address.neighborhood }}, {{ order.shipping_address.city }} - {{ order.shipping_address.state }}
                  {{ order.shipping_address.postal_code }}
                </p>
              </div>
              </div>

              <div class="order-card-footer">
                <div class="order-payment-info">
                  <span>Pagamento: {{ paymentMethodLabel(order.payments[0]?.method) }}</span>
                  <strong>Total: {{ formatPrice(order.total) }}</strong>
                </div>
                <button
                  v-if="nextStatus(order)"
                  class="admin-status-action"
                  :class="{ cancel: nextStatus(order) === 'cancelled' }"
                  :disabled="busyOrderId === order.id"
                  @click="updateStatus(order)"
                >
                  {{ busyOrderId === order.id ? 'Aguarde…' : actionLabel(nextStatus(order)) }}
                </button>
              </div>
              <p v-if="actionMessages[order.id]" class="order-action-message" role="status">{{ actionMessages[order.id] }}</p>
            </div>
          </article>
        </section>
      </template>
      </div>
    </main>

    <footer class="footer cart-footer">
      <div class="container footer-bottom">
        <span>© {{ new Date().getFullYear() }} Mangáverse</span>
        <RouterLink to="/">Voltar à loja</RouterLink>
      </div>
    </footer>
  </div>
</template>
