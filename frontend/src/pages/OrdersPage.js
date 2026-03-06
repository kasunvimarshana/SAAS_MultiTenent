import React, { useEffect, useState, useCallback } from 'react';
import DataTable from '../components/DataTable';
import Pagination from '../components/Pagination';
import StatusBadge from '../components/StatusBadge';
import { getOrders, cancelOrder } from '../services/orderService';
import './PageStyles.css';

const OrdersPage = () => {
  const [orders, setOrders] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [params, setParams] = useState({ per_page: 10, page: 1 });

  const loadOrders = useCallback(async (queryParams) => {
    setLoading(true);
    setError('');
    try {
      const res = await getOrders(queryParams);
      const data = res.data;
      if (data.pagination) {
        setOrders(data.data);
        setPagination(data.pagination);
      } else {
        setOrders(Array.isArray(data.data) ? data.data : []);
        setPagination(null);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load orders');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadOrders(params);
  }, [params, loadOrders]);

  const handleCancel = async (order) => {
    if (window.confirm(`Cancel order ${order.order_number}?`)) {
      try {
        await cancelOrder(order.id);
        loadOrders(params);
      } catch (err) {
        setError(err.response?.data?.message || 'Failed to cancel order');
      }
    }
  };

  const columns = [
    { key: 'order_number', label: 'Order #' },
    { key: 'status', label: 'Status', render: (v) => <StatusBadge status={v} /> },
    { key: 'payment_status', label: 'Payment', render: (v) => <StatusBadge status={v} /> },
    { key: 'total_amount', label: 'Total', render: (v) => `$${parseFloat(v || 0).toFixed(2)}` },
    { key: 'currency', label: 'Currency' },
    { key: 'created_at', label: 'Created', render: (v) => v ? new Date(v).toLocaleDateString() : '-' },
    {
      key: 'actions',
      label: 'Actions',
      render: (_, row) => (
        ['pending', 'confirmed'].includes(row.status) && (
          <button onClick={(e) => { e.stopPropagation(); handleCancel(row); }} className="btn btn-danger btn-sm">
            Cancel
          </button>
        )
      ),
    }
  ];

  return (
    <div className="page">
      <div className="page-header">
        <h2>Orders</h2>
        <div className="page-info">
          <span>🔄 Powered by Saga Pattern</span>
        </div>
      </div>

      {error && <div className="error-banner">{error}</div>}

      <div className="saga-info">
        <strong>Order Creation Flow (Saga Pattern):</strong>
        <div className="saga-steps">
          <span className="saga-step">1. Reserve Inventory</span>
          <span className="saga-arrow">→</span>
          <span className="saga-step">2. Process Payment</span>
          <span className="saga-arrow">→</span>
          <span className="saga-step">3. Confirm Order</span>
          <span className="saga-arrow">→</span>
          <span className="saga-step saga-rollback">↩ Rollback on failure</span>
        </div>
      </div>

      <DataTable columns={columns} data={orders} loading={loading} />
      <Pagination pagination={pagination} onPageChange={(page) => setParams(p => ({ ...p, page }))} />
    </div>
  );
};

export default OrdersPage;
