import axios from 'axios';

// Create axios instance with custom config
const instance = axios.create({
  baseURL: '/api', // Updated to use the Vite proxy
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  withCredentials: true // Required for CORS with credentials
});

// Add request interceptor
instance.interceptors.request.use(
  (config) => {
    // Get token from localStorage if it exists
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

export default instance; 