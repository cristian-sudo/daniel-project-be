import { defineStore } from 'pinia';
import axios from '../config/axios';

export const useBookingStore = defineStore('booking', {
  state: () => ({
    bookings: [],
    gymBookings: [],
    loading: false,
    error: null
  }),
  
  actions: {
    async getMyBookings() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get('bookings.php?action=my');
        console.log('getMyBookings response:', response.data);
        
        if (response.data.success) {
          this.bookings = response.data.data.bookings || [];
          return true;
        } else {
          this.bookings = [];
          return false;
        }
      } catch (error) {
        console.error('Error in getMyBookings:', error);
        this.error = error.response?.data?.message || 'Errore durante il recupero delle prenotazioni';
        this.bookings = [];
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    async getGymBookings(gymId) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get(`bookings.php?gym_id=${gymId}`);
        console.log('getGymBookings response:', response.data);
        
        if (response.data.success) {
          this.gymBookings = response.data.data.bookings || [];
          return true;
        } else {
          this.gymBookings = [];
          return false;
        }
      } catch (error) {
        console.error('Error in getGymBookings:', error);
        this.error = error.response?.data?.message || 'Errore durante il recupero delle prenotazioni della palestra';
        this.gymBookings = [];
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    async createBooking(bookingData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post('bookings.php', bookingData);
        console.log('createBooking response:', response.data);
        
        if (response.data.success) {
          await this.getMyBookings();
          return response.data.data.booking_id;
        } else {
          this.error = response.data.message;
          return false;
        }
      } catch (error) {
        console.error('Error in createBooking:', error);
        this.error = error.response?.data?.message || 'Errore durante la creazione della prenotazione';
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    async updateBookingStatus(id, status) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.put(`bookings.php?id=${id}&action=status`, { stato: status });
        console.log('updateBookingStatus response:', response.data);
        
        if (response.data.success) {
          // Aggiorna le liste di prenotazioni
          await this.getMyBookings();
          return true;
        } else {
          this.error = response.data.message;
          return false;
        }
      } catch (error) {
        console.error('Error in updateBookingStatus:', error);
        this.error = error.response?.data?.message || 'Errore durante l\'aggiornamento dello stato della prenotazione';
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    async deleteBooking(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.delete(`bookings.php?id=${id}`);
        console.log('deleteBooking response:', response.data);
        
        if (response.data.success) {
          // Aggiorna le liste di prenotazioni
          await this.getMyBookings();
          return true;
        } else {
          this.error = response.data.message;
          return false;
        }
      } catch (error) {
        console.error('Error in deleteBooking:', error);
        this.error = error.response?.data?.message || 'Errore durante l\'eliminazione della prenotazione';
        return false;
      } finally {
        this.loading = false;
      }
    }
  }
});
