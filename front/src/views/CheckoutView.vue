<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import StoreHeader from '../components/StoreHeader.vue';
import { apiRequest } from '../services/api';
import { useAuth } from '../services/auth';
import { useCart } from '../services/cart';

const { user, loadCurrentUser } = useAuth();
const { items, subtotal, cartCount, refreshCart } = useCart();
const addresses = ref([]);
const selectedAddressId = ref('');
const paymentMethod = ref('pix');
const showAddressForm = ref(false);
const loading = ref(true);
const busy = ref(false);
const errorMessage = ref('');
const fieldErrors = ref({});
const order = ref(null);
const paymentConfirmed = ref(false);
const isLoggedIn = computed(() => Boolean(user.value));

const addressForm = reactive({
  label: 'Principal',
  recipient_name: '',
  postal_code: '',
  state: '',
  city: '',
  neighborhood: '',
  street: '',
  number: '',
  complement: '',
  phone: '',
  is_default: false,
});

onMounted(loadCheckout);

async function loadCheckout() {
  try {
    await loadCurrentUser();

    if (!user.value) return;

    const [addressResponse] = await Promise.all([
      apiRequest('/api/addresses'),
      refreshCart(),
    ]);

    addresses.value = addressResponse.data;
    const defaultAddress = addresses.value.find((address) => address.is_default) || addresses.value[0];

    if (defaultAddress) {
      selectedAddressId.value = String(defaultAddress.id);
    } else {
      showAddressForm.value = true;
    }
  } catch (error) {
    errorMessage.value = error.message;
  } finally {
    loading.value = false;
  }
}

async function saveAddress() {
  busy.value = true;
  errorMessage.value = '';
  fieldErrors.value = {};

  try {
    const response = await apiRequest('/api/addresses', {
      method: 'POST',
      body: addressForm,
    });

    addresses.value.push(response.data);
    selectedAddressId.value = String(response.data.id);
    showAddressForm.value = false;
  } catch (error) {
    errorMessage.value = error.status === 422 ? '' : error.message;
    fieldErrors.value = error.errors || {};
  } finally {
    busy.value = false;
  }
}

async function createOrder() {
  busy.value = true;
  errorMessage.value = '';

  try {
    const response = await apiRequest('/api/orders', {
      method: 'POST',
      body: {
        address_id: Number(selectedAddressId.value),
        payment_method: paymentMethod.value,
      },
    });

    order.value = response.data;
    await refreshCart();
  } catch (error) {
    errorMessage.value = error.message;
  } finally {
    busy.value = false;
  }
}

async function confirmPayment() {
  busy.value = true;
  errorMessage.value = '';

  try {
    const response = await apiRequest(`/api/orders/${order.value.id}/confirm-payment`, {
      method: 'POST',
    });
    order.value = response.data;
    paymentConfirmed.value = true;
  } catch (error) {
    errorMessage.value = error.message;
  } finally {
    busy.value = false;
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(Number(price));
}

function formatPaymentMethod(method) {
  return {
    pix: 'Pix',
    boleto: 'Boleto',
    credit_card: 'Cartão de crédito',
  }[method] || method;
}
</script>

<template>
  <div class="site-shell">
    <StoreHeader :cart-count="cartCount" :show-search="false" />

    <main class="container checkout-page">
      <div class="checkout-heading">
        <span class="eyebrow">Quase lá</span>
        <h1>Finalizar compra</h1>
      </div>

      <p v-if="loading" class="state-message" role="status">Carregando seus dados…</p>

      <section v-else-if="!isLoggedIn" class="checkout-message">
        <h2>Entre para continuar</h2>
        <p>Seus itens continuam no carrinho. Entre ou crie uma conta para concluir o pedido.</p>
        <RouterLink class="retry-button" :to="{ name: 'auth', query: { redirect: '/finalizar' } }">Entrar ou criar conta</RouterLink>
      </section>

      <section v-else-if="order" class="order-result">
        <span class="eyebrow">Pedido #{{ order.id }}</span>
        <h2>{{ paymentConfirmed ? 'Pagamento confirmado!' : 'Pedido criado' }}</h2>
        <p v-if="paymentConfirmed">Seu pedido foi registrado e o pagamento simulado foi aprovado.</p>
        <p v-else>O pedido está aguardando pagamento. Use o botão abaixo para simular a confirmação.</p>

        <div class="order-result-summary">
          <div><span>Status</span><strong>{{ order.status }}</strong></div>
          <div><span>Pagamento</span><strong>{{ formatPaymentMethod(order.payments[0]?.method) }}</strong></div>
          <div><span>Total</span><strong>{{ formatPrice(order.total) }}</strong></div>
        </div>

        <p v-if="errorMessage" class="checkout-error" role="alert">{{ errorMessage }}</p>
        <button v-if="!paymentConfirmed" class="checkout-submit" :disabled="busy" @click="confirmPayment">
          {{ busy ? 'Aguarde…' : 'Confirmar pagamento (simulado)' }}
        </button>
        <RouterLink v-if="paymentConfirmed" class="checkout-submit order-history-link" to="/pedidos">Ver meus pedidos</RouterLink>
        <RouterLink v-else class="continue-link" to="/">Voltar à loja</RouterLink>
      </section>

      <div v-else-if="!cartCount" class="checkout-message">
        <h2>Seu carrinho está vazio</h2>
        <p>Adicione pelo menos um mangá antes de finalizar a compra.</p>
        <RouterLink class="retry-button" to="/">Voltar ao catálogo</RouterLink>
      </div>

      <div v-else class="checkout-layout">
        <div class="checkout-steps">
          <section class="checkout-panel">
            <div class="checkout-panel-heading">
              <span class="step-number">1</span>
              <h2>Endereço de entrega</h2>
            </div>

            <label v-if="addresses.length && !showAddressForm" class="address-select-label">
              Escolha um endereço salvo
              <select v-model="selectedAddressId">
                <option v-for="address in addresses" :key="address.id" :value="String(address.id)">
                  {{ address.label || address.recipient_name }} — {{ address.street }}, {{ address.number }}
                </option>
              </select>
            </label>

            <button v-if="addresses.length && !showAddressForm" class="text-button" @click="showAddressForm = true">
              Adicionar outro endereço
            </button>

            <form v-if="showAddressForm" class="address-form" @submit.prevent="saveAddress">
              <label>Nome do destinatário<input v-model="addressForm.recipient_name" autocomplete="name" required /><small v-if="fieldErrors.recipient_name">{{ fieldErrors.recipient_name[0] }}</small></label>
              <label>CEP<input v-model="addressForm.postal_code" autocomplete="postal-code" placeholder="00000-000" required /><small v-if="fieldErrors.postal_code">{{ fieldErrors.postal_code[0] }}</small></label>
              <div class="address-form-row">
                <label>Estado<input v-model="addressForm.state" maxlength="2" autocomplete="address-level1" placeholder="PR" required /><small v-if="fieldErrors.state">{{ fieldErrors.state[0] }}</small></label>
                <label>Cidade<input v-model="addressForm.city" autocomplete="address-level2" required /><small v-if="fieldErrors.city">{{ fieldErrors.city[0] }}</small></label>
              </div>
              <label>Bairro<input v-model="addressForm.neighborhood" required /><small v-if="fieldErrors.neighborhood">{{ fieldErrors.neighborhood[0] }}</small></label>
              <div class="address-form-row">
                <label>Rua<input v-model="addressForm.street" autocomplete="street-address" required /><small v-if="fieldErrors.street">{{ fieldErrors.street[0] }}</small></label>
                <label>Número<input v-model="addressForm.number" required /><small v-if="fieldErrors.number">{{ fieldErrors.number[0] }}</small></label>
              </div>
              <label>Complemento (opcional)<input v-model="addressForm.complement" /></label>
              <label>Telefone (opcional)<input v-model="addressForm.phone" autocomplete="tel" /></label>
              <div class="address-form-actions">
                <button class="checkout-submit" type="submit" :disabled="busy">{{ busy ? 'Salvando…' : 'Salvar endereço' }}</button>
                <button v-if="addresses.length" class="text-button" type="button" @click="showAddressForm = false">Cancelar</button>
              </div>
            </form>
          </section>

          <section class="checkout-panel">
            <div class="checkout-panel-heading">
              <span class="step-number">2</span>
              <h2>Forma de pagamento</h2>
            </div>
            <div class="payment-options">
              <label><input v-model="paymentMethod" type="radio" value="pix" /> Pix</label>
              <label><input v-model="paymentMethod" type="radio" value="boleto" /> Boleto</label>
              <label><input v-model="paymentMethod" type="radio" value="credit_card" /> Cartão de crédito</label>
            </div>
            <p class="payment-note">O pagamento é simulado para fins do projeto acadêmico.</p>
          </section>

          <p v-if="errorMessage" class="checkout-error" role="alert">{{ errorMessage }}</p>
        </div>

        <aside class="checkout-summary">
          <h2>Resumo do pedido</h2>
          <div v-for="item in items" :key="item.id" class="checkout-item-row">
            <span>{{ item.quantity }} × {{ item.manga.title }} · Vol. {{ item.manga.volume_number }}<br /><small>{{ formatPrice(item.unit_price) }} por unidade</small></span>
            <strong>{{ formatPrice(item.line_total) }}</strong>
          </div>
          <div class="summary-row"><span>Itens</span><span>{{ cartCount }}</span></div>
          <div class="summary-row"><span>Frete</span><span>Grátis</span></div>
          <div class="summary-total"><span>Total</span><strong>{{ formatPrice(subtotal) }}</strong></div>
          <button
            class="checkout-submit"
            :disabled="busy || !selectedAddressId || showAddressForm"
            @click="createOrder"
          >
            {{ busy ? 'Processando…' : 'Fazer pedido' }}
          </button>
        </aside>
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
