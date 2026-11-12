<script setup>
import { onMounted, reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import StoreHeader from '../components/StoreHeader.vue';
import { apiRequest } from '../services/api';
import { useAuth } from '../services/auth';
import { useCart } from '../services/cart';

const router = useRouter();
const { user, loadCurrentUser } = useAuth();
const { cartCount } = useCart();
const addresses = ref([]);
const loading = ref(true);
const profileSaving = ref(false);
const addressSaving = ref(false);
const profileError = ref('');
const profileMessage = ref('');
const addressError = ref('');
const addressErrors = ref({});
const addressMessage = ref('');
const editingAddressId = ref(null);
const showAddressForm = ref(false);
const profileForm = reactive({ name: '', email: '' });
const addressForm = reactive(emptyAddress());

onMounted(loadProfile);

function emptyAddress() {
  return {
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
  };
}

async function loadProfile() {
  try {
    await loadCurrentUser();

    if (!user.value) {
      await router.replace({ name: 'auth', query: { redirect: '/perfil' } });
      return;
    }

    profileForm.name = user.value.name;
    profileForm.email = user.value.email;
    const response = await apiRequest('/api/addresses');
    addresses.value = response.data;
  } catch (error) {
    profileError.value = error.message;
  } finally {
    loading.value = false;
  }
}

async function saveProfile() {
  profileSaving.value = true;
  profileError.value = '';
  profileMessage.value = '';

  try {
    const response = await apiRequest('/api/user', {
      method: 'PATCH',
      body: profileForm,
    });
    user.value = response.data;
    profileForm.name = response.data.name;
    profileForm.email = response.data.email;
    profileMessage.value = 'Dados atualizados.';
  } catch (error) {
    profileError.value = error.status === 422
      ? Object.values(error.errors || {}).flat()[0]
      : error.message;
  } finally {
    profileSaving.value = false;
  }
}

function startNewAddress() {
  Object.assign(addressForm, emptyAddress(), { is_default: addresses.value.length === 0 });
  editingAddressId.value = null;
  addressErrors.value = {};
  addressError.value = '';
  addressMessage.value = '';
  showAddressForm.value = true;
}

function editAddress(address) {
  Object.assign(addressForm, address);
  editingAddressId.value = address.id;
  addressErrors.value = {};
  addressError.value = '';
  addressMessage.value = '';
  showAddressForm.value = true;
}

async function saveAddress() {
  addressSaving.value = true;
  addressError.value = '';
  addressErrors.value = {};
  addressMessage.value = '';

  try {
    const response = await apiRequest(
      editingAddressId.value ? `/api/addresses/${editingAddressId.value}` : '/api/addresses',
      { method: editingAddressId.value ? 'PATCH' : 'POST', body: addressForm },
    );

    if (editingAddressId.value) {
      addresses.value = addresses.value.map((address) => address.id === response.data.id ? response.data : address);
    } else {
      addresses.value.push(response.data);
    }

    await loadAddresses();
    showAddressForm.value = false;
    addressMessage.value = 'Endereço salvo.';
  } catch (error) {
    addressError.value = error.status === 422 ? '' : error.message;
    addressErrors.value = error.errors || {};
  } finally {
    addressSaving.value = false;
  }
}

async function setDefaultAddress(address) {
  addressError.value = '';

  try {
    await apiRequest(`/api/addresses/${address.id}`, {
      method: 'PATCH',
      body: { is_default: true },
    });
    await loadAddresses();
  } catch (error) {
    addressError.value = error.message;
  }
}

async function deleteAddress(address) {
  if (!window.confirm(`Remover o endereço “${address.label || address.street}”?`)) return;

  addressError.value = '';

  try {
    await apiRequest(`/api/addresses/${address.id}`, { method: 'DELETE' });
    await loadAddresses();
  } catch (error) {
    addressError.value = error.message;
  }
}

async function loadAddresses() {
  const response = await apiRequest('/api/addresses');
  addresses.value = response.data;
}
</script>

<template>
  <div class="site-shell">
    <StoreHeader :cart-count="cartCount" :show-search="false" />

    <main class="container profile-page">
      <div class="profile-heading">
        <div>
          <span class="eyebrow">Sua conta</span>
          <h1>Meu perfil</h1>
          <p>Atualize seus dados e gerencie os endereços de entrega.</p>
        </div>
        <RouterLink class="continue-link" to="/pedidos">Meus pedidos</RouterLink>
      </div>

      <p v-if="loading" class="state-message" role="status">Carregando perfil…</p>
      <p v-else-if="profileError && !user" class="checkout-error" role="alert">{{ profileError }}</p>

      <div v-else class="profile-layout">
        <section class="profile-panel">
          <h2>Dados pessoais</h2>
          <form class="profile-form" @submit.prevent="saveProfile">
            <label>Nome<input v-model="profileForm.name" autocomplete="name" maxlength="255" required /></label>
            <label>E-mail<input v-model="profileForm.email" type="email" autocomplete="email" maxlength="255" required /></label>
            <p v-if="profileError" class="checkout-error" role="alert">{{ profileError }}</p>
            <p v-if="profileMessage" class="profile-success" role="status">{{ profileMessage }}</p>
            <button class="admin-primary-button" type="submit" :disabled="profileSaving">
              {{ profileSaving ? 'Salvando…' : 'Salvar dados' }}
            </button>
          </form>
        </section>

        <section class="profile-panel profile-addresses">
          <div class="profile-section-heading">
            <div><h2>Endereços</h2><p>Escolha um endereço padrão para suas compras.</p></div>
            <button class="admin-primary-button" @click="startNewAddress">Adicionar</button>
          </div>
          <p v-if="addressError" class="checkout-error" role="alert">{{ addressError }}</p>
          <p v-if="addressMessage" class="profile-success" role="status">{{ addressMessage }}</p>
          <div v-if="!addresses.length" class="profile-empty-address">
            <p>Você ainda não cadastrou endereços.</p>
            <button class="text-button" @click="startNewAddress">Adicionar primeiro endereço</button>
          </div>
          <article v-for="address in addresses" :key="address.id" class="profile-address-card">
            <div>
              <div class="profile-address-title">
                <strong>{{ address.label || 'Endereço' }}</strong>
                <span v-if="address.is_default">Padrão</span>
              </div>
              <p>{{ address.recipient_name }} · {{ address.street }}, {{ address.number }}<span v-if="address.complement">, {{ address.complement }}</span></p>
              <p>{{ address.neighborhood }}, {{ address.city }} - {{ address.state }}, {{ address.postal_code }}</p>
            </div>
            <div class="profile-address-actions">
              <button v-if="!address.is_default" class="text-button" @click="setDefaultAddress(address)">Tornar padrão</button>
              <button class="text-button" @click="editAddress(address)">Editar</button>
              <button class="text-button profile-delete-button" @click="deleteAddress(address)">Remover</button>
            </div>
          </article>

          <form v-if="showAddressForm" class="profile-address-form" @submit.prevent="saveAddress">
            <h3>{{ editingAddressId ? 'Editar endereço' : 'Novo endereço' }}</h3>
            <div class="profile-form-row">
              <label>Identificação<input v-model="addressForm.label" maxlength="50" placeholder="Casa, trabalho…" /><small v-if="addressErrors.label">{{ addressErrors.label[0] }}</small></label>
              <label>Destinatário<input v-model="addressForm.recipient_name" autocomplete="name" required /><small v-if="addressErrors.recipient_name">{{ addressErrors.recipient_name[0] }}</small></label>
            </div>
            <div class="profile-form-row profile-form-row-three">
              <label>CEP<input v-model="addressForm.postal_code" autocomplete="postal-code" placeholder="00000-000" required /><small v-if="addressErrors.postal_code">{{ addressErrors.postal_code[0] }}</small></label>
              <label>Estado<input v-model="addressForm.state" maxlength="2" placeholder="PR" required /><small v-if="addressErrors.state">{{ addressErrors.state[0] }}</small></label>
              <label>Cidade<input v-model="addressForm.city" required /><small v-if="addressErrors.city">{{ addressErrors.city[0] }}</small></label>
            </div>
            <div class="profile-form-row">
              <label>Bairro<input v-model="addressForm.neighborhood" required /><small v-if="addressErrors.neighborhood">{{ addressErrors.neighborhood[0] }}</small></label>
              <label>Rua<input v-model="addressForm.street" autocomplete="street-address" required /><small v-if="addressErrors.street">{{ addressErrors.street[0] }}</small></label>
            </div>
            <div class="profile-form-row">
              <label>Número<input v-model="addressForm.number" required /><small v-if="addressErrors.number">{{ addressErrors.number[0] }}</small></label>
              <label>Complemento<input v-model="addressForm.complement" /><small v-if="addressErrors.complement">{{ addressErrors.complement[0] }}</small></label>
            </div>
            <div class="profile-form-row">
              <label>Telefone<input v-model="addressForm.phone" autocomplete="tel" /><small v-if="addressErrors.phone">{{ addressErrors.phone[0] }}</small></label>
              <label class="profile-default-checkbox"><input v-model="addressForm.is_default" type="checkbox" /> Definir como endereço padrão</label>
            </div>
            <div class="profile-form-actions">
              <button class="admin-secondary-button" type="button" @click="showAddressForm = false">Cancelar</button>
              <button class="admin-primary-button" type="submit" :disabled="addressSaving">{{ addressSaving ? 'Salvando…' : 'Salvar endereço' }}</button>
            </div>
          </form>
        </section>
      </div>
    </main>

    <footer class="footer cart-footer">
      <div class="container footer-bottom"><span>© {{ new Date().getFullYear() }} Mangáverse</span><RouterLink to="/">Voltar à loja</RouterLink></div>
    </footer>
  </div>
</template>
