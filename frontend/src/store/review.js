import { defineStore } from 'pinia';
import axios from '../config/axios';

export const useReviewStore = defineStore('review', {
  state: () => ({
    reviews: [],
    userReviews: [],
    loading: false,
    error: null
  }),
  
  actions: {
    async getGymReviews(gymId) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get(`reviews.php?gym_id=${gymId}`);
        console.log('Reviews response:', response.data); // Add logging for debugging
        
        if (response.data.success) {
          this.reviews = response.data.data.reviews || [];
          return true;
        } else {
          this.reviews = [];
          this.error = response.data.message;
          return false;
        }
      } catch (error) {
        console.error('Error fetching reviews:', error);
        this.error = error.response?.data?.message || 'Errore durante il recupero delle recensioni';
        this.reviews = [];
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    async getUserReviews(userId) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.get(`reviews.php?user_id=${userId}`);
        
        if (response.data.success) {
          this.userReviews = response.data.reviews;
          return true;
        } else {
          this.userReviews = [];
          return false;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante il recupero delle recensioni dell\'utente';
        this.userReviews = [];
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    async createReview(reviewData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post('reviews.php', reviewData);
        console.log('Create review response:', response.data); // Add logging for debugging
        
        if (response.data.success) {
          // Aggiorna la lista delle recensioni
          await this.getGymReviews(reviewData.gym_id);
          return response.data.data.review_id;
        } else {
          this.error = response.data.message;
          return false;
        }
      } catch (error) {
        console.error('Error creating review:', error);
        this.error = error.response?.data?.message || 'Errore durante la creazione della recensione';
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    async updateReview(id, reviewData) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.put(`reviews.php?id=${id}`, reviewData);
        console.log('Update review response:', response.data); // Add logging for debugging
        
        if (response.data.success) {
          // Aggiorna la lista delle recensioni
          await this.getGymReviews(reviewData.gym_id);
          return true;
        } else {
          this.error = response.data.message;
          return false;
        }
      } catch (error) {
        console.error('Error updating review:', error);
        this.error = error.response?.data?.message || 'Errore durante l\'aggiornamento della recensione';
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    async deleteReview(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.delete(`reviews.php?id=${id}`);
        
        if (response.data.success) {
          // Aggiorna la lista delle recensioni
          if (this.reviews.length > 0) {
            const gymId = this.reviews[0].gym_id;
            await this.getGymReviews(gymId);
          }
          return true;
        } else {
          return false;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante l\'eliminazione della recensione';
        return false;
      } finally {
        this.loading = false;
      }
    }
  }
});
