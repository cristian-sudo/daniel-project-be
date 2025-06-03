import { defineStore } from 'pinia';
import axios from '../config/axios';

export const useGymStore = defineStore('gym', {
  state: () => ({
    gyms: [],
    currentGym: null,
    loading: false,
    error: null,
    searchCity: '',
    myGyms: []
  }),
  
  getters: {
    hasGyms: (state) => state.gyms.length > 0,
    hasMyGyms: (state) => state.myGyms.length > 0
  },
  
  actions: {
    async searchGyms(city) {
      this.loading = true;
      this.error = null;
      this.searchCity = city;
      
      try {
        const response = await axios.get(`/gyms.php?action=search&citta=${city}`);
        console.log('Search response:', response.data);
        
        if (response.data.success) {
          this.gyms = response.data.data.gyms;
          return true;
        } else {
          this.gyms = [];
          return false;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante la ricerca delle palestre';
        this.gyms = [];
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    async getGymById(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get(`/gyms.php?id=${id}`);
        console.log('GetGymById response:', response.data);
        
        if (response.data.success) {
          this.currentGym = response.data.data.gym;
          return true;
        } else {
          this.currentGym = null;
          return false;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante il recupero dei dati della palestra';
        this.currentGym = null;
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    async getMyGyms() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('/gyms.php?action=my');
        console.log('GetMyGyms response:', response.data);
        
        if (response.data.success) {
          this.myGyms = response.data.data.gyms;
          return true;
        } else {
          this.myGyms = [];
          return false;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante il recupero delle tue palestre';
        this.myGyms = [];
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    async createGym(gymData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post('/gyms.php', gymData);
        
        if (response.data.success) {
          await this.getMyGyms();
          return response.data.gym_id;
        } else {
          return false;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante la creazione della palestra';
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    async updateGym(id, gymData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.put(`/gyms.php?id=${id}`, gymData);
        
        if (response.data.success) {
          if (this.currentGym && this.currentGym.id === id) {
            await this.getGymById(id);
          }
          await this.getMyGyms();
          return true;
        } else {
          return false;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante l\'aggiornamento della palestra';
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    async deleteGym(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.delete(`/gyms.php?id=${id}`);
        
        if (response.data.success) {
          await this.getMyGyms();
          return true;
        } else {
          return false;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante l\'eliminazione della palestra';
        return false;
      } finally {
        this.loading = false;
      }
    }
  }
});
