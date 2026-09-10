import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api';
const PYTHON_URL = import.meta.env.VITE_PYTHON_API_URL || 'http://127.0.0.1:8001';
const PYTHON_API_KEY = import.meta.env.VITE_PYTHON_API_KEY || '';

export const api = axios.create({ baseURL: API_URL });

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  config.headers.Accept = 'application/json';
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export const pythonApi = axios.create({ baseURL: PYTHON_URL });

pythonApi.interceptors.request.use((config) => {
  config.headers['x-api-key'] = PYTHON_API_KEY;
  return config;
});
