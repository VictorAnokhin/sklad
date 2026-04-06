import React, { useState, useEffect } from 'react';

/**
 * HitGoods Component
 * Displays featured/hit products from the API
 */
export default function HitGoods() {
  const [goods, setGoods] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchHitGoods();
  }, []);

  const fetchHitGoods = async (limit = 10, offset = 0) => {
    try {
      setLoading(true);
      const response = await fetch(
        `${import.meta.env.VITE_API_URL}/api/goods/hits?limit=${limit}&offset=${offset}`
      );

      if (!response.ok) {
        throw new Error(`API error: ${response.status}`);
      }

      const result = await response.json();

      if (result.success) {
        setGoods(result.data);
      } else {
        setError('Failed to fetch hit goods');
      }
    } catch (err) {
      setError(err.message);
      console.error('Error fetching hit goods:', err);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <div className="hit-goods-loading">Завантаження...</div>;
  }

  if (error) {
    return <div className="hit-goods-error">Помилка: {error}</div>;
  }

  if (goods.length === 0) {
    return <div className="hit-goods-empty">Немає доступних товарів</div>;
  }

  return (
    <div className="hit-goods-container">
      <h2>Гарячі пропозиції</h2>
      <div className="hit-goods-grid">
        {goods.map((item) => (
          <div key={item.id} className="hit-goods-card">
            <div className="hit-goods-image">
              {item.image && (
                <img
                  src={item.image}
                  alt={item.name}
                  onError={(e) => {
                    // Fallback to thumb image if main image fails
                    if (item.image_thumb) {
                      e.target.src = item.image_thumb;
                    }
                  }}
                />
              )}
              <span className="hit-badge">HOT</span>
            </div>
            <div className="hit-goods-content">
              <h3 className="hit-goods-name">{item.name}</h3>
              <p className="hit-goods-description">{item.description}</p>
              <div className="hit-goods-price">
                {item.oldPrice && item.oldPrice !== item.price && (
                  <span className="hit-goods-old-price">${item.oldPrice}</span>
                )}
                <span className="hit-goods-current-price">${item.price}</span>
              </div>
              <div className="hit-goods-stock">
                {item.count > 0 ? (
                  <span className="hit-goods-in-stock">В наявності ({item.count})</span>
                ) : (
                  <span className="hit-goods-out-of-stock">Немає в наявності</span>
                )}
              </div>
              <button className="hit-goods-button" disabled={item.count === 0}>
                Додати до кошика
              </button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
