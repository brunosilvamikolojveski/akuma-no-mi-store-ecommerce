<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import StoreHeader from '../components/StoreHeader.vue';
import { apiRequest } from '../services/api';
import { useCart } from '../services/cart';

const route = useRoute();
const { cartCount, refreshCart } = useCart();
const order = ref(null);
const loading = ref(true);
const busy = ref(false);
const errorMessage = ref('');
const actionMessage = ref('');
const statusLabels = { pending: 'Aguardando pagamento', paid: 'Pago', shipped: 'Enviado', delivered: 'Entregue', cancelled: 'Cancelado' };
const paymentLabels = { pix: 'Pix', boleto: 'Boleto', credit_card: 'Cartão de crédito' };
const currency = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
const dateFormat = new Intl.DateTimeFormat('pt-BR', { dateStyle: 'medium', timeStyle: 'short' });

onMounted(async () => {
  try {
    const [response] = await Promise.all([apiRequest(`/api/orders/${route.params.id}`), refreshCart()]);
    order.value = response.data;
  } catch (error) {
    errorMessage.value = error.status === 404 ? 'Pedido não encontrado ou indisponível para sua conta.' : error.message;
  } finally {
    loading.value = false;
  }
});

async function updateOrder(action) {
  busy.value = true;
  errorMessage.value = '';

  try {
    const response = await apiRequest(`/api/orders/${order.value.id}/${action}`, { method: 'POST' });
    order.value = response.data;
    actionMessage.value = action === 'cancel' ? 'Pedido cancelado. O estoque foi devolvido.' : 'Pagamento simulado confirmado.';
  } catch (error) {
    errorMessage.value = error.message;
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <div class="site-shell">
    <StoreHeader :cart-count="cartCount" :show-search="false" />
    <main class="container orders-page">
      <div class="orders-heading">
        <div><span class="eyebrow">Sua compra</span><h1>Detalhes do pedido{{ order ? ` #${order.id}` : '' }}</h1></div>
        <RouterLink class="continue-link" to="/pedidos">Voltar aos meus pedidos</RouterLink>
      </div>
      <p v-if="loading" class="state-message" role="status">Carregando pedido…</p>
      <p v-if="errorMessage" class="checkout-error" role="alert">{{ errorMessage }}</p>
      <article v-if="order" class="order-card">
        <header class="order-card-header">
          <p>Realizado em {{ dateFormat.format(new Date(order.placed_at)) }}</p>
          <span class="order-status" :class="`status-${order.status}`">{{ statusLabels[order.status] }}</span>
        </header>
        <div class="order-card-body">
          <div class="order-product-list">
            <div v-for="item in order.items" :key="item.id" class="order-product-row">
              <span>{{ item.manga_title }} · Vol. {{ item.volume_number }}<br /><small>{{ item.quantity }} × {{ currency.format(item.unit_price) }} por unidade</small></span>
              <strong>{{ currency.format(item.line_total) }}</strong>
            </div>
          </div>
          <section class="order-delivery-address">
            <h2>Endereço de entrega</h2>
            <p>{{ order.shipping_address.recipient_name }}</p>
            <p>{{ order.shipping_address.street }}, {{ order.shipping_address.number }}<template v-if="order.shipping_address.complement"> · {{ order.shipping_address.complement }}</template></p>
            <p>{{ order.shipping_address.neighborhood }} · {{ order.shipping_address.city }} — {{ order.shipping_address.state }}</p>
            <p>CEP {{ order.shipping_address.postal_code }}</p>
          </section>
          <div class="summary-row"><span>Subtotal</span><span>{{ currency.format(order.subtotal) }}</span></div>
          <div class="summary-row"><span>Frete</span><span>{{ Number(order.shipping_cost) === 0 ? 'Grátis' : currency.format(order.shipping_cost) }}</span></div>
          <div class="summary-total"><span>Total do pedido</span><strong>{{ currency.format(order.total) }}</strong></div>
          <div class="order-card-footer">
            <div class="order-payment-info">
              <span>Pagamento: {{ paymentLabels[order.payments[0]?.method] }}</span>
              <span v-if="order.payments[0]?.paid_at">Pago em {{ dateFormat.format(new Date(order.payments[0].paid_at)) }}</span>
              <span>Pagamento simulado para fins acadêmicos.</span>
            </div>
            <div v-if="order.status === 'pending'" class="order-actions">
              <button :disabled="busy" @click="updateOrder('confirm-payment')">{{ busy ? 'Aguarde…' : 'Confirmar pagamento (simulado)' }}</button>
              <button class="cancel-order-button" :disabled="busy" @click="updateOrder('cancel')">Cancelar pedido</button>
            </div>
          </div>
          <p v-if="actionMessage" class="order-action-message" role="status">{{ actionMessage }}</p>
        </div>
      </article>
    </main>
    <footer class="footer cart-footer"><div class="container footer-bottom"><span>© {{ new Date().getFullYear() }} Mangáverse</span><RouterLink to="/">Voltar à loja</RouterLink></div></footer>
  </div>
</template>
