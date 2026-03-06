import React, { useEffect, useState, useCallback } from 'react';
import DataTable from '../components/DataTable';
import Pagination from '../components/Pagination';
import SearchBar from '../components/SearchBar';
import { getInventory, addStock, removeStock, getLowStock } from '../services/inventoryService';
import './PageStyles.css';

const InventoryPage = () => {
  const [items, setItems] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [params, setParams] = useState({ per_page: 10, page: 1 });
  const [stockModal, setStockModal] = useState(null);
  const [stockForm, setStockForm] = useState({ quantity: 1, notes: '' });
  const [showLowStock, setShowLowStock] = useState(false);

  const loadInventory = useCallback(async (queryParams) => {
    setLoading(true);
    setError('');
    try {
      const res = await getInventory(queryParams);
      const data = res.data;
      if (data.pagination) {
        setItems(data.data);
        setPagination(data.pagination);
      } else {
        setItems(Array.isArray(data.data) ? data.data : []);
        setPagination(null);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load inventory');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!showLowStock) {
      loadInventory(params);
    }
  }, [params, loadInventory, showLowStock]);

  const handleLowStock = async () => {
    if (!showLowStock) {
      setLoading(true);
      try {
        const res = await getLowStock();
        setItems(res.data.data || []);
        setPagination(null);
        setShowLowStock(true);
      } catch (err) {
        setError('Failed to load low stock items');
      } finally {
        setLoading(false);
      }
    } else {
      setShowLowStock(false);
    }
  };

  const handleSearch = (query) => {
    setParams(p => ({ ...p, search: query, page: 1 }));
  };

  const handleStockAction = async (e) => {
    e.preventDefault();
    try {
      if (stockModal.type === 'add') {
        await addStock(stockModal.item.id, stockForm);
      } else {
        await removeStock(stockModal.item.id, stockForm);
      }
      setStockModal(null);
      setStockForm({ quantity: 1, notes: '' });
      loadInventory(params);
    } catch (err) {
      setError(err.response?.data?.message || 'Stock operation failed');
    }
  };

  const columns = [
    { key: 'product_name', label: 'Product' },
    { key: 'product_sku', label: 'SKU' },
    { key: 'quantity', label: 'Qty' },
    { key: 'reserved_quantity', label: 'Reserved' },
    {
      key: 'available',
      label: 'Available',
      render: (_, row) => {
        const avail = (row.quantity || 0) - (row.reserved_quantity || 0);
        return <span style={{ color: avail <= (row.reorder_level || 0) ? '#c62828' : '#2e7d32', fontWeight: 600 }}>{avail}</span>;
      }
    },
    { key: 'reorder_level', label: 'Reorder Level' },
    {
      key: 'actions',
      label: 'Actions',
      render: (_, row) => (
        <div style={{ display: 'flex', gap: '0.5rem' }}>
          <button onClick={() => { setStockModal({ item: row, type: 'add' }); setStockForm({ quantity: 1, notes: '' }); }} className="btn btn-success btn-sm">+ Add</button>
          <button onClick={() => { setStockModal({ item: row, type: 'remove' }); setStockForm({ quantity: 1, notes: '' }); }} className="btn btn-warning btn-sm">- Remove</button>
        </div>
      )
    }
  ];

  return (
    <div className="page">
      <div className="page-header">
        <h2>Inventory {showLowStock && <span className="badge-warning">Low Stock View</span>}</h2>
        <button onClick={handleLowStock} className={`btn ${showLowStock ? 'btn-secondary' : 'btn-warning'}`}>
          {showLowStock ? 'Show All' : '⚠️ Low Stock'}
        </button>
      </div>

      {error && <div className="error-banner">{error}</div>}

      <div className="toolbar">
        <SearchBar onSearch={handleSearch} placeholder="Search by product name..." />
      </div>

      <DataTable columns={columns} data={items} loading={loading} />
      {!showLowStock && <Pagination pagination={pagination} onPageChange={(page) => setParams(p => ({ ...p, page }))} />}

      {stockModal && (
        <div className="modal-overlay" onClick={() => setStockModal(null)}>
          <div className="modal" onClick={e => e.stopPropagation()}>
            <h3>{stockModal.type === 'add' ? '+ Add Stock' : '- Remove Stock'}: {stockModal.item.product_name}</h3>
            <form onSubmit={handleStockAction}>
              <div className="form-group">
                <label>Quantity *</label>
                <input type="number" min="1" value={stockForm.quantity} onChange={e => setStockForm({ ...stockForm, quantity: parseInt(e.target.value) })} required />
              </div>
              <div className="form-group">
                <label>Notes</label>
                <input type="text" value={stockForm.notes} onChange={e => setStockForm({ ...stockForm, notes: e.target.value })} placeholder="Optional notes" />
              </div>
              <div className="form-actions">
                <button type="submit" className={`btn ${stockModal.type === 'add' ? 'btn-success' : 'btn-warning'}`}>
                  {stockModal.type === 'add' ? 'Add Stock' : 'Remove Stock'}
                </button>
                <button type="button" onClick={() => setStockModal(null)} className="btn btn-secondary">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default InventoryPage;
