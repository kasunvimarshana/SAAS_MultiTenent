import React from 'react';

const statusColors = {
  active: { bg: '#e8f5e9', color: '#2e7d32' },
  inactive: { bg: '#ffebee', color: '#c62828' },
  pending: { bg: '#fff3e0', color: '#e65100' },
  confirmed: { bg: '#e3f2fd', color: '#1565c0' },
  processing: { bg: '#f3e5f5', color: '#6a1b9a' },
  shipped: { bg: '#e8eaf6', color: '#283593' },
  delivered: { bg: '#e8f5e9', color: '#2e7d32' },
  cancelled: { bg: '#ffebee', color: '#c62828' },
  healthy: { bg: '#e8f5e9', color: '#2e7d32' },
  unhealthy: { bg: '#ffebee', color: '#c62828' },
  completed: { bg: '#e8f5e9', color: '#2e7d32' },
};

const StatusBadge = ({ status }) => {
  const styles = statusColors[status?.toLowerCase()] || { bg: '#f5f5f5', color: '#555' };
  return (
    <span
      style={{
        display: 'inline-block',
        padding: '0.2rem 0.6rem',
        borderRadius: '12px',
        fontSize: '0.78rem',
        fontWeight: 600,
        background: styles.bg,
        color: styles.color,
        textTransform: 'capitalize',
      }}
    >
      {status}
    </span>
  );
};

export default StatusBadge;
