import axios from 'axios';

const ORDER_SERVICE_URL = process.env.REACT_APP_ORDER_SERVICE_URL || 'http://localhost:8004/api';

const orderApi = axios.create({
  baseURL: ORDER_SERVICE_URL,
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
});

orderApi.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token');
  const tenantId = localStorage.getItem('tenant_id');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  if (tenantId) config.headers['X-Tenant-ID'] = tenantId;
  return config;
});

export const getOrders = (params = {}) => orderApi.get('/orders', { params });
export const getOrder = (id) => orderApi.get(`/orders/${id}`);
export const createOrder = (data) => orderApi.post('/orders', data);
export const cancelOrder = (id) => orderApi.post(`/orders/${id}/cancel`);

export default orderApi;
