<template>
  <div class="login-page">
    <div class="container py-5">
      <div class="row justify-content-center">
        <div class="col-md-6">
          <div class="card shadow">
            <div class="card-body p-5">
              <h1 class="text-center mb-4">Accedi</h1>
              
              <div v-if="error" class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ error }}
              </div>
              
              <form @submit.prevent="login">
                <div class="mb-3">
                  <label for="username" class="form-label">Username</label>
                  <input
                    type="text"
                    class="form-control"
                    id="username"
                    v-model="credentials.username"
                    required
                    autocomplete="username"
                  >
                </div>
                <div class="mb-3">
                  <label for="password" class="form-label">Password</label>
                  <input
                    type="password"
                    class="form-control"
                    id="password"
                    v-model="credentials.password"
                    required
                    autocomplete="current-password"
                  >
                </div>
                <div class="d-grid gap-2">
                  <button 
                    type="submit" 
                    class="btn btn-primary btn-lg"
                    :disabled="loading"
                  >
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Accedi
                  </button>
                </div>
              </form>
              
              <div class="text-center mt-4">
                <p>Non hai un account? <router-link to="/registrazione">Registrati</router-link></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { auth } from '../services/api';

export default {
  name: 'Login',
  setup() {
    const router = useRouter();
    const route = useRoute();
    
    const credentials = ref({
      username: '',
      password: ''
    });
    
    const error = ref('');
    const loading = ref(false);
    
    const login = async () => {
      error.value = '';
      loading.value = true;
      
      try {
        console.log('Tentativo di login...');
        const response = await auth.login(credentials.value);
        
        console.log('Risposta ricevuta:', response.data);
        
        if (response.data.success) {
          // Salva il token e i dati utente
          localStorage.setItem('token', response.data.data.token);
          localStorage.setItem('user', JSON.stringify(response.data.data.user));
          
          // Reindirizza in base al tipo di utente
          if (response.data.data.user.tipo_utente === 'palestra') {
            router.push('/gestione-palestra');
          } else {
            router.push('/palestre');
          }
        } else {
          error.value = response.data.message || 'Credenziali non valide';
        }
      } catch (err) {
        console.error('Errore durante il login:', err);
        error.value = err.response?.data?.message || 'Errore durante il login';
      } finally {
        loading.value = false;
      }
    };
    
    return {
      credentials,
      login,
      error,
      loading
    };
  }
};
</script>

<style scoped>
.login-page {
  min-height: calc(100vh - 300px);
  display: flex;
  align-items: center;
}
</style>
