<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import StoreHeader from '../components/StoreHeader.vue';
import { useAuth } from '../services/auth';
import { useCart } from '../services/cart';

const router = useRouter();
const route = useRoute();
const { user, loadCurrentUser, signIn, register, signOut } = useAuth();
const { cartCount } = useCart();
const mode = ref('login');
const busy = ref(false);
const formError = ref('');
const fieldErrors = ref({});
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' });
const isRegistering = computed(() => mode.value === 'register');

onMounted(loadCurrentUser);

function setMode(nextMode) {
  mode.value = nextMode;
  formError.value = '';
  fieldErrors.value = {};
}

async function submit() {
  busy.value = true;
  formError.value = '';
  fieldErrors.value = {};

  try {
    if (isRegistering.value) {
      await register({
        name: form.name,
        email: form.email,
        password: form.password,
        password_confirmation: form.password_confirmation,
      });
    } else {
      await signIn({ email: form.email, password: form.password });
    }

    const destination = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/')
      ? route.query.redirect
      : '/';
    await router.push(destination);
  } catch (error) {
    formError.value = error.status === 422 ? '' : error.message;
    fieldErrors.value = error.errors || {};
  } finally {
    busy.value = false;
  }
}

async function logout() {
  try {
    await signOut();
    await router.push('/');
  } catch (error) {
    formError.value = error.message;
  }
}
</script>

<template>
  <div class="site-shell">
    <StoreHeader :cart-count="cartCount" :show-search="false" />

    <main class="auth-page container">
      <section v-if="user" class="auth-card signed-in-card">
        <span class="eyebrow">Sua conta</span>
        <h1>Olá, {{ user.name }}</h1>
        <p>Você está conectado como {{ user.email }}.</p>
        <button class="auth-submit" @click="logout">Sair da conta</button>
        <RouterLink class="continue-link" to="/carrinho">Ver meu carrinho</RouterLink>
      </section>

      <section v-else class="auth-card">
        <span class="eyebrow">Bem-vindo à Mangáverse</span>
        <h1>{{ isRegistering ? 'Crie sua conta' : 'Entre na sua conta' }}</h1>
        <p class="auth-intro">
          {{ isRegistering ? 'Cadastre-se para acompanhar seus pedidos e continuar suas compras.' : 'Entre para acompanhar seus pedidos e continuar suas compras.' }}
        </p>

        <div class="auth-tabs" role="tablist" aria-label="Acesso à conta">
          <button :class="{ active: !isRegistering }" role="tab" :aria-selected="!isRegistering" @click="setMode('login')">Entrar</button>
          <button :class="{ active: isRegistering }" role="tab" :aria-selected="isRegistering" @click="setMode('register')">Criar conta</button>
        </div>

        <form class="auth-form" @submit.prevent="submit">
          <label v-if="isRegistering">
            Nome
            <input v-model="form.name" autocomplete="name" required />
            <small v-if="fieldErrors.name">{{ fieldErrors.name[0] }}</small>
          </label>
          <label>
            E-mail
            <input v-model="form.email" type="email" autocomplete="email" required />
            <small v-if="fieldErrors.email">{{ fieldErrors.email[0] }}</small>
          </label>
          <label>
            Senha
            <input
              v-model="form.password"
              type="password"
              :autocomplete="isRegistering ? 'new-password' : 'current-password'"
              :minlength="isRegistering ? 8 : undefined"
              required
            />
            <small v-if="fieldErrors.password">{{ fieldErrors.password[0] }}</small>
          </label>
          <label v-if="isRegistering">
            Confirme a senha
            <input v-model="form.password_confirmation" type="password" autocomplete="new-password" required />
          </label>

          <p v-if="formError" class="auth-error" role="alert">{{ formError }}</p>
          <button class="auth-submit" type="submit" :disabled="busy">
            {{ busy ? 'Aguarde…' : isRegistering ? 'Criar conta' : 'Entrar' }}
          </button>
        </form>
      </section>
    </main>

    <footer class="footer cart-footer">
      <div class="container footer-bottom">
        <span>© {{ new Date().getFullYear() }} Mangáverse</span>
        <RouterLink to="/">Voltar ao catálogo</RouterLink>
      </div>
    </footer>
  </div>
</template>
