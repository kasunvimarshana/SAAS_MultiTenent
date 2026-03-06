import React, { createContext, useContext, useState } from 'react';

const TenantContext = createContext(null);

export const TenantProvider = ({ children }) => {
  const [tenantId, setTenantId] = useState(
    () => localStorage.getItem('tenant_id') || ''
  );
  const [tenantName, setTenantName] = useState(
    () => localStorage.getItem('tenant_name') || ''
  );

  const setTenant = (id, name) => {
    localStorage.setItem('tenant_id', id);
    localStorage.setItem('tenant_name', name);
    setTenantId(id);
    setTenantName(name);
  };

  return (
    <TenantContext.Provider value={{ tenantId, tenantName, setTenant }}>
      {children}
    </TenantContext.Provider>
  );
};

export const useTenant = () => {
  const context = useContext(TenantContext);
  if (!context) throw new Error('useTenant must be used within TenantProvider');
  return context;
};
