<template>
  <div class="registrazione-page">
    <div class="container py-5">
      <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="card shadow">
            <div class="card-body p-5">
              <h1 class="text-center mb-4">Registrazione</h1>
              
              <div v-if="error" class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ error }}
              </div>
              
              <form @submit.prevent="register">
                <div class="row mb-3">
                  <div class="col-md-6 mb-3 mb-md-0">
                    <label for="nome" class="form-label">Nome</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      id="nome" 
                      v-model="userData.nome" 
                      required
                    >
                  </div>
                  <div class="col-md-6">
                    <label for="cognome" class="form-label">Cognome</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      id="cognome" 
                      v-model="userData.cognome" 
                      required
                    >
                  </div>
                </div>
                
                <div class="mb-3">
                  <label for="email" class="form-label">Email</label>
                  <input 
                    type="email" 
                    class="form-control" 
                    id="email" 
                    v-model="userData.email" 
                    required
                  >
                </div>
                
                <div class="mb-3">
                  <label for="username" class="form-label">Username</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    id="username" 
                    v-model="userData.username" 
                    required
                  >
                </div>
                
                <div class="row mb-3">
                  <div class="col-md-6 mb-3 mb-md-0">
                    <label for="password" class="form-label">Password</label>
                    <input 
                      type="password" 
                      class="form-control" 
                      id="password" 
                      v-model="userData.password" 
                      required
                    >
                  </div>
                  <div class="col-md-6">
                    <label for="confirm_password" class="form-label">Conferma Password</label>
                    <input 
                      type="password" 
                      class="form-control" 
                      id="confirm_password" 
                      v-model="confirmPassword" 
                      required
                    >
                  </div>
                </div>
                
                <div class="mb-4">
                  <label class="form-label">Tipo di account</label>
                  <div class="d-flex">
                    <div class="form-check me-4">
                      <input 
                        class="form-check-input" 
                        type="radio" 
                        name="tipo_utente" 
                        id="tipo_cliente" 
                        value="cliente" 
                        v-model="userData.tipo_utente"
                        checked
                      >
                      <label class="form-check-label" for="tipo_cliente">
                        Cliente
                      </label>
                    </div>
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="radio" 
                        name="tipo_utente" 
                        id="tipo_palestra" 
                        value="palestra" 
                        v-model="userData.tipo_utente"
                      >
                      <label class="form-check-label" for="tipo_palestra">
                        Gestore Palestra
                      </label>
                    </div>
                  </div>
                </div>
                
                <div class="d-grid gap-2">
                  <button 
                    type="submit" 
                    class="btn btn-primary btn-lg"
                    :disabled="loading || !isFormValid"
                  >
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Registrati
                  </button>
                </div>
              </form>
              
              <div class="text-center mt-4">
                <p>Hai già un account? <router-link to="/login">Accedi</router-link></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { auth } from '../services/api';

export default {
  name: 'Registrazione',
  setup() {
    const router = useRouter();
    
    const userData = ref({
      nome: '',
      cognome: '',
      email: '',
      username: '',
      password: '',
      tipo_utente: 'cliente'
    });
    
    const confirmPassword = ref('');
    const error = ref('');
    const loading = ref(false);
    
    const isFormValid = computed(() => {
      return userData.value.password === confirmPassword.value && 
             userData.value.password.length >= 6;
    });
    
    const register = async () => {
      error.value = '';
      
      if (!isFormValid.value) {
        if (userData.value.password !== confirmPassword.value) {
          error.value = 'Le password non coincidono';
        } else if (userData.value.password.length < 6) {
          error.value = 'La password deve essere di almeno 6 caratteri';
        }
        return;
      }
      
      loading.value = true;
      
      try {
        const response = await auth.register(userData.value);
        
        if (response.data.success) {
          // Reindirizza alla pagina di login con messaggio di successo
          router.push({ 
            name: 'Login', 
            query: { 
              message: 'Registrazione completata con successo! Ora puoi accedere.' 
            } 
          });
        } else {
          error.value = response.data.message || 'Errore durante la registrazione';
        }
      } catch (error) {
        console.error('Errore durante la registrazione:', error);
        error.value = error.response?.data?.message || 'Errore durante la registrazione';
      } finally {
        loading.value = false;
      }
    };
    
    return {
      userData,
      confirmPassword,
      error,
      loading,
      isFormValid,
      register
    };
  }
};
</script>

<style scoped>
.registrazione-page {
  min-height: calc(100vh - 300px);
  display: flex;
  align-items: center;
}
</style>
