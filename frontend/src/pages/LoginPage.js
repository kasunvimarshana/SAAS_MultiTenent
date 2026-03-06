import React, { useState } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { useTenant } from '../contexts/TenantContext';
import './LoginPage.css';

const LoginPage = () => {
  const [form, setForm] = useState({ email: '', password: '', tenantId: '', tenantName: '' });
  const [error, setError] = useState('');
  const { login, loading } = useAuth();
  const { setTenant } = useTenant();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');

    if (!form.tenantId) {
      setError('Please enter a Tenant ID');
      return;
    }

    setTenant(form.tenantId, form.tenantName || `Tenant ${form.tenantId}`);
    const result = await login(form.email, form.password);
    if (!result.success) {
      setError(result.message);
    }
  };

  return (
    <div className="login-page">
      <div className="login-card">
        <div className="login-header">
          <h1>🏪 SaaS Inventory</h1>
          <p>Multi-Tenant Management System</p>
        </div>

        <form onSubmit={handleSubmit} className="login-form">
          {error && <div className="error-banner">{error}</div>}

          <div className="form-group">
            <label>Tenant ID</label>
            <input
              type="text"
              value={form.tenantId}
              onChange={e => setForm({ ...form, tenantId: e.target.value })}
              placeholder="Enter your tenant ID"
              required
            />
          </div>

          <div className="form-group">
            <label>Email</label>
            <input
              type="email"
              value={form.email}
              onChange={e => setForm({ ...form, email: e.target.value })}
              placeholder="admin@acme.com"
              required
            />
          </div>

          <div className="form-group">
            <label>Password</label>
            <input
              type="password"
              value={form.password}
              onChange={e => setForm({ ...form, password: e.target.value })}
              placeholder="Enter your password"
              required
            />
          </div>

          <button type="submit" disabled={loading} className="btn btn-primary btn-full">
            {loading ? 'Signing in...' : 'Sign In'}
          </button>
        </form>

        <div className="login-footer">
          <p>Powered by Laravel Passport SSO + React</p>
        </div>
      </div>
    </div>
  );
};

export default LoginPage;
