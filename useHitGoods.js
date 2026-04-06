import { useState, useCallback } from 'react';

/**
 * useHitGoods Hook
 * Custom hook for fetching hit goods from the API
 *
 * @param {number} initialLimit - Initial number of items to fetch (default: 10)
 * @returns {Object} Hook state and functions
 */
export const useHitGoods = (initialLimit = 10) => {
  const [goods, setGoods] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [offset, setOffset] = useState(0);
  const [limit] = useState(initialLimit);
  const [hasMore, setHasMore] = useState(true);

  const fetchHitGoods = useCallback(async (newOffset = 0, append = false) => {
    try {
      setLoading(true);
      setError(null);

      const apiUrl = import.meta.env.VITE_API_URL || '';
      const response = await fetch(
        `${apiUrl}/api/goods/hits?limit=${limit}&offset=${newOffset}`
      );

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();

      if (!result.success) {
        throw new Error('API returned unsuccessful response');
      }

      if (append) {
        setGoods((prev) => [...prev, ...result.data]);
      } else {
        setGoods(result.data);
      }

      setOffset(newOffset);
      setHasMore(result.data.length === limit);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unknown error');
      console.error('Error fetching hit goods:', err);
    } finally {
      setLoading(false);
    }
  }, [limit]);

  const loadMore = useCallback(() => {
    const newOffset = offset + limit;
    fetchHitGoods(newOffset, true);
  }, [offset, limit, fetchHitGoods]);

  const refresh = useCallback(() => {
    fetchHitGoods(0, false);
  }, [fetchHitGoods]);

  return {
    goods,
    loading,
    error,
    offset,
    limit,
    hasMore,
    fetchHitGoods,
    loadMore,
    refresh
  };
};

export default useHitGoods;
