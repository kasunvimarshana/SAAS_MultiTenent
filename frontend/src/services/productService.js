import axios from 'axios';

const PRODUCT_SERVICE_URL = process.env.REACT_APP_PRODUCT_SERVICE_URL || 'http://localhost:8002/api';

const productApi = axios.create({
  baseURL: PRODUCT_SERVICE_URL,
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
});

productApi.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token');
  const tenantId = localStorage.getItem('tenant_id');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  if (tenantId) config.headers['X-Tenant-ID'] = tenantId;
  return config;
});

export const getProducts = (params = {}) => productApi.get('/products', { params });
export const getProduct = (id) => productApi.get(`/products/${id}`);
export const createProduct = (data) => productApi.post('/products', data);
export const updateProduct = (id, data) => productApi.put(`/products/${id}`, data);
export const deleteProduct = (id) => productApi.delete(`/products/${id}`);
export const searchProducts = (q) => productApi.get('/products/search/query', { params: { q } });

export default productApi;
