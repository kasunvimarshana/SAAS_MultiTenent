import { useState, useCallback } from 'react';

export const useApi = (apiFunc) => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const execute = useCallback(async (...args) => {
    setLoading(true);
    setError(null);
    try {
      const response = await apiFunc(...args);
      setData(response.data);
      return response.data;
    } catch (err) {
      const message = err.response?.data?.message || err.message || 'An error occurred';
      setError(message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, [apiFunc]);

  return { data, loading, error, execute };
};

export const usePaginatedApi = (apiFunc) => {
  const [items, setItems] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const execute = useCallback(async (params = {}) => {
    setLoading(true);
    setError(null);
    try {
      const response = await apiFunc(params);
      const responseData = response.data;
      if (responseData.pagination) {
        setItems(responseData.data);
        setPagination(responseData.pagination);
      } else {
        setItems(Array.isArray(responseData.data) ? responseData.data : []);
        setPagination(null);
      }
      return responseData;
    } catch (err) {
      const message = err.response?.data?.message || err.message || 'An error occurred';
      setError(message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, [apiFunc]);

  return { items, pagination, loading, error, execute };
};
