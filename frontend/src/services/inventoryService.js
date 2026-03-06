import axios from 'axios';

const INVENTORY_SERVICE_URL = process.env.REACT_APP_INVENTORY_SERVICE_URL || 'http://localhost:8003/api';

const inventoryApi = axios.create({
  baseURL: INVENTORY_SERVICE_URL,
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
});

inventoryApi.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token');
  const tenantId = localStorage.getItem('tenant_id');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  if (tenantId) config.headers['X-Tenant-ID'] = tenantId;
  return config;
});

export const getInventory = (params = {}) => inventoryApi.get('/inventory', { params });
export const getInventoryWithProducts = (params = {}) => inventoryApi.get('/inventory/with-products', { params });
export const addStock = (id, data) => inventoryApi.post(`/inventory/${id}/add-stock`, data);
export const removeStock = (id, data) => inventoryApi.post(`/inventory/${id}/remove-stock`, data);
export const getLowStock = () => inventoryApi.get('/inventory/reports/low-stock');
export const searchInventoryByProductName = (q) => inventoryApi.get('/inventory/search/product-name', { params: { q } });

export default inventoryApi;
