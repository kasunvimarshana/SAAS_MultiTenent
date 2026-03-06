import React, { useEffect, useState, useCallback } from 'react';
import DataTable from '../components/DataTable';
import Pagination from '../components/Pagination';
import SearchBar from '../components/SearchBar';
import StatusBadge from '../components/StatusBadge';
import { getProducts, createProduct, deleteProduct } from '../services/productService';
import './PageStyles.css';

const ProductsPage = () => {
  const [products, setProducts] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [showForm, setShowForm] = useState(false);
  const [params, setParams] = useState({ per_page: 10, page: 1 });

  const [form, setForm] = useState({ name: '', sku: '', price: '', category: '', brand: '', description: '' });

  const loadProducts = useCallback(async (queryParams) => {
    setLoading(true);
    setError('');
    try {
      const res = await getProducts(queryParams);
      const data = res.data;
      if (data.pagination) {
        setProducts(data.data);
        setPagination(data.pagination);
      } else {
        setProducts(Array.isArray(data.data) ? data.data : []);
        setPagination(null);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load products');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadProducts(params);
  }, [params, loadProducts]);

  const handleSearch = (query) => {
    setParams(p => ({ ...p, search: query, page: 1 }));
  };

  const handlePageChange = (page) => {
    setParams(p => ({ ...p, page }));
  };

  const handleCreate = async (e) => {
    e.preventDefault();
    try {
      await createProduct({ ...form, price: parseFloat(form.price) });
      setShowForm(false);
      setForm({ name: '', sku: '', price: '', category: '', brand: '', description: '' });
      loadProducts(params);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to create product');
    }
  };

  const handleDelete = async (product) => {
    if (window.confirm(`Delete "${product.name}"?`)) {
      try {
        await deleteProduct(product.id);
        loadProducts(params);
      } catch (err) {
        setError('Failed to delete product');
      }
    }
  };

  const columns = [
    { key: 'name', label: 'Name' },
    { key: 'sku', label: 'SKU' },
    { key: 'price', label: 'Price', render: (v) => `$${parseFloat(v).toFixed(2)}` },
    { key: 'category', label: 'Category' },
    { key: 'brand', label: 'Brand' },
    { key: 'is_active', label: 'Status', render: (v) => <StatusBadge status={v ? 'active' : 'inactive'} /> },
    {
      key: 'actions',
      label: 'Actions',
      render: (_, row) => (
        <button onClick={(e) => { e.stopPropagation(); handleDelete(row); }} className="btn btn-danger btn-sm">
          Delete
        </button>
      ),
    },
  ];

  return (
    <div className="page">
      <div className="page-header">
        <h2>Products</h2>
        <button onClick={() => setShowForm(!showForm)} className="btn btn-primary">
          {showForm ? 'Cancel' : '+ Add Product'}
        </button>
      </div>

      {error && <div className="error-banner">{error}</div>}

      {showForm && (
        <div className="form-card">
          <h3>New Product</h3>
          <form onSubmit={handleCreate} className="form-grid">
            <div className="form-group">
              <label>Name *</label>
              <input value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} required placeholder="Product name" />
            </div>
            <div className="form-group">
              <label>SKU *</label>
              <input value={form.sku} onChange={e => setForm({ ...form, sku: e.target.value })} required placeholder="SKU-001" />
            </div>
            <div className="form-group">
              <label>Price *</label>
              <input type="number" step="0.01" value={form.price} onChange={e => setForm({ ...form, price: e.target.value })} required placeholder="0.00" />
            </div>
            <div className="form-group">
              <label>Category</label>
              <input value={form.category} onChange={e => setForm({ ...form, category: e.target.value })} placeholder="Category" />
            </div>
            <div className="form-group">
              <label>Brand</label>
              <input value={form.brand} onChange={e => setForm({ ...form, brand: e.target.value })} placeholder="Brand" />
            </div>
            <div className="form-group form-full">
              <label>Description</label>
              <textarea value={form.description} onChange={e => setForm({ ...form, description: e.target.value })} placeholder="Product description" rows={3} />
            </div>
            <div className="form-actions">
              <button type="submit" className="btn btn-primary">Create Product</button>
              <button type="button" onClick={() => setShowForm(false)} className="btn btn-secondary">Cancel</button>
            </div>
          </form>
        </div>
      )}

      <div className="toolbar">
        <SearchBar onSearch={handleSearch} placeholder="Search products..." />
      </div>

      <DataTable columns={columns} data={products} loading={loading} />
      <Pagination pagination={pagination} onPageChange={handlePageChange} />
    </div>
  );
};

export default ProductsPage;
