import { defineStore } from 'pinia';
import { auth } from '../services/api';

export const useUserStore = defineStore('user', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user') || 'null'),
    token: localStorage.getItem('token') || null,
    loading: false,
    error: null
  }),
  
  getters: {
    isLoggedIn: (state) => !!state.token && !!state.user,
    isGymOwner: (state) => state.user?.tipo_utente === 'palestra',
    isAdmin: (state) => state.user?.tipo_utente === 'admin'
  },
  
  actions: {
    async login(credentials) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await auth.login(credentials);
        
        if (response.data.success) {
          const { token, user } = response.data.data;
          this.token = token;
          this.user = user;
          localStorage.setItem('token', token);
          localStorage.setItem('user', JSON.stringify(user));
          return true;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante il login';
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    async register(userData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await auth.register(userData);
        
        if (response.data.success) {
          return true;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante la registrazione';
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    updateUserData(userData) {
      this.user = { ...this.user, ...userData };
      localStorage.setItem('user', JSON.stringify(this.user));
    },
    
    async updateProfile(userData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await auth.put(`/users?id=${this.user.id}`, userData);
        
        if (response.data.success) {
          // Aggiorna i dati dell'utente
          this.user = { ...this.user, ...userData };
          localStorage.setItem('user', JSON.stringify(this.user));
          return true;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante l\'aggiornamento del profilo';
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    async changePassword(passwordData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await auth.put(`/users?id=${this.user.id}&action=change-password`, passwordData);
        
        if (response.data.success) {
          return true;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante il cambio password';
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    logout() {
      this.user = null;
      this.token = null;
      localStorage.removeItem('user');
      localStorage.removeItem('token');
    }
  }
});
