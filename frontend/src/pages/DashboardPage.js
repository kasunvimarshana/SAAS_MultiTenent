import React from 'react';
import { useAuth } from '../contexts/AuthContext';
import { useTenant } from '../contexts/TenantContext';
import './DashboardPage.css';

const StatCard = ({ title, value, icon, color }) => (
  <div className="stat-card" style={{ borderTop: `4px solid ${color}` }}>
    <div className="stat-icon" style={{ color }}>{icon}</div>
    <div className="stat-content">
      <div className="stat-value">{value}</div>
      <div className="stat-title">{title}</div>
    </div>
  </div>
);

const DashboardPage = () => {
  const { user } = useAuth();
  const { tenantName } = useTenant();

  return (
    <div className="dashboard">
      <div className="dashboard-header">
        <div>
          <h2>Welcome back, {user?.name || 'Admin'} 👋</h2>
          <p className="subtitle">
            Managing <strong>{tenantName || 'your workspace'}</strong>
          </p>
        </div>
      </div>

      <div className="stats-grid">
        <StatCard title="Total Products" value="—" icon="📦" color="#1565c0" />
        <StatCard title="Inventory Items" value="—" icon="🏭" color="#2e7d32" />
        <StatCard title="Active Orders" value="—" icon="🛒" color="#e65100" />
        <StatCard title="Team Members" value="—" icon="👥" color="#6a1b9a" />
      </div>

      <div className="architecture-info">
        <h3>System Architecture</h3>
        <div className="service-grid">
          {[
            { name: 'User Service', port: 8001, icon: '👤', description: 'Auth, RBAC/ABAC, SSO via Laravel Passport' },
            { name: 'Product Service', port: 8002, icon: '📦', description: 'Product catalog, categories, events' },
            { name: 'Inventory Service', port: 8003, icon: '🏭', description: 'Stock management, cross-service data' },
            { name: 'Order Service', port: 8004, icon: '🛒', description: 'Orders with Saga distributed transactions' },
          ].map(service => (
            <div key={service.name} className="service-card">
              <div className="service-icon">{service.icon}</div>
              <div className="service-info">
                <div className="service-name">{service.name}</div>
                <div className="service-port">Port {service.port}</div>
                <div className="service-desc">{service.description}</div>
              </div>
            </div>
          ))}
        </div>
      </div>

      <div className="features-info">
        <h3>Key Features</h3>
        <div className="feature-list">
          {[
            '✅ Multi-tenant architecture with tenant isolation',
            '✅ Laravel Passport SSO authentication',
            '✅ RBAC + ABAC authorization',
            '✅ Base Repository with CRUD, pagination, filtering, sorting',
            '✅ Conditional pagination (per_page or all results)',
            '✅ Pluggable MessageBroker (RabbitMQ / Kafka)',
            '✅ Saga pattern for distributed transactions',
            '✅ Domain Events (ProductCreated, InventoryUpdated, OrderCreated)',
            '✅ Cross-service data access (inventory ↔ product)',
            '✅ Webhook integration with HMAC signatures',
            '✅ Health check endpoints on all services',
            '✅ Tenant-specific runtime configuration (mail, payment)',
            '✅ DTOs for structured data transfer',
            '✅ ACID-compliant transactions',
          ].map(f => (
            <div key={f} className="feature-item">{f}</div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default DashboardPage;
