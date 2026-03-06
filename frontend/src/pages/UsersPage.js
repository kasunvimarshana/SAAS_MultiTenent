import React, { useEffect, useState, useCallback } from 'react';
import DataTable from '../components/DataTable';
import Pagination from '../components/Pagination';
import SearchBar from '../components/SearchBar';
import StatusBadge from '../components/StatusBadge';
import api from '../services/api';
import './PageStyles.css';

const UsersPage = () => {
  const [users, setUsers] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [params, setParams] = useState({ per_page: 10, page: 1 });

  const loadUsers = useCallback(async (queryParams) => {
    setLoading(true);
    setError('');
    try {
      const res = await api.get('/users', { params: queryParams });
      const data = res.data;
      if (data.pagination) {
        setUsers(data.data);
        setPagination(data.pagination);
      } else {
        setUsers(Array.isArray(data.data) ? data.data : []);
        setPagination(null);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load users');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadUsers(params);
  }, [params, loadUsers]);

  const columns = [
    { key: 'name', label: 'Name' },
    { key: 'email', label: 'Email' },
    { key: 'is_active', label: 'Status', render: (v) => <StatusBadge status={v ? 'active' : 'inactive'} /> },
    { key: 'created_at', label: 'Joined', render: (v) => v ? new Date(v).toLocaleDateString() : '-' },
  ];

  return (
    <div className="page">
      <div className="page-header">
        <h2>Users</h2>
      </div>

      {error && <div className="error-banner">{error}</div>}

      <div className="toolbar">
        <SearchBar onSearch={(q) => setParams(p => ({ ...p, search: q, page: 1 }))} placeholder="Search users..." />
      </div>

      <DataTable columns={columns} data={users} loading={loading} />
      <Pagination pagination={pagination} onPageChange={(page) => setParams(p => ({ ...p, page }))} />
    </div>
  );
};

export default UsersPage;
