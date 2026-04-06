import React from 'react';
import useHitGoods from './useHitGoods';
import './HitGoods.css';

/**
 * HitGoodsWithHook Component
 * Advanced example using the useHitGoods custom hook
 */
export default function HitGoodsWithHook() {
  const {
    goods,
    loading,
    error,
    hasMore,
    loadMore,
    refresh
  } = useHitGoods(12);

  // Load initial data on mount
  React.useEffect(() => {
    refresh();
  }, [refresh]);

  if (error && goods.length === 0) {
    return (
      <div className="hit-goods-error">
        <p>Помилка завантаження: {error}</p>
        <button onClick={refresh}>Спробувати знову</button>
      </div>
    );
  }

  return (
    <div className="hit-goods-container">
      <div className="hit-goods-header">
        <h2>🔥 Гарячі пропозиції</h2>
        <button className="hit-goods-refresh-btn" onClick={refresh} disabled={loading}>
          {loading ? 'Оновлення...' : 'Оновити'}
        </button>
      </div>

      {goods.length === 0 && !loading && (
        <div className="hit-goods-empty">Немає доступних товарів</div>
      )}

      <div className="hit-goods-grid">
        {goods.map((item) => (
          <HitGoodsCard key={item.id} item={item} />
        ))}
      </div>

      {loading && goods.length > 0 && (
        <div className="hit-goods-loading-more">Завантаження більше товарів...</div>
      )}

      {hasMore && goods.length > 0 && !loading && (
        <div className="hit-goods-footer">
          <button className="hit-goods-load-more-btn" onClick={loadMore}>
            +Завантажити більше
          </button>
        </div>
      )}

      {!hasMore && goods.length > 0 && (
        <div className="hit-goods-end-message">Це все товари</div>
      )}
    </div>
  );
}

/**
 * HitGoodsCard Component
 * Individual product card
 */
function HitGoodsCard({ item }) {
  const [inCart, setInCart] = React.useState(false);

  const handleAddToCart = (e) => {
    e.preventDefault();
    setInCart(true);
    // Тут буде логика додавання до кошика
    console.log('Added to cart:', item.id);
    setTimeout(() => setInCart(false), 2000);
  };

  return (
    <div className="hit-goods-card">
      <div className="hit-goods-image">
        {item.image && (
          <picture>
            <source srcSet={item.image_thumb} media="(max-width: 768px)" />
            <img
              src={item.image}
              alt={item.name}
              loading="lazy"
              onError={(e) => {
                if (item.image_thumb && e.target.src !== item.image_thumb) {
                  e.target.src = item.image_thumb;
                }
              }}
            />
          </picture>
        )}
        <span className="hit-badge">HOT</span>
        {item.oldPrice && item.oldPrice !== item.price && (
          <span className="hit-discount-badge">
            {Math.round(((item.oldPrice - item.price) / item.oldPrice) * 100)}%
          </span>
        )}
      </div>

      <div className="hit-goods-content">
        <h3 className="hit-goods-name" title={item.name}>
          {item.name}
        </h3>

        {item.description && (
          <p className="hit-goods-description" title={item.description}>
            {item.description}
          </p>
        )}

        <div className="hit-goods-price">
          {item.oldPrice && item.oldPrice !== item.price && (
            <span className="hit-goods-old-price">₴{item.oldPrice.toFixed(2)}</span>
          )}
          <span className="hit-goods-current-price">₴{item.price.toFixed(2)}</span>
        </div>

        <div className="hit-goods-stock">
          {item.count > 0 ? (
            <>
              <span className="hit-goods-in-stock">✓ В наявності</span>
              {item.count <= 5 && (
                <span className="hit-goods-limited">Залишилось {item.count}</span>
              )}
            </>
          ) : (
            <span className="hit-goods-out-of-stock">✗ Немає в наявності</span>
          )}
        </div>

        <button
          className={`hit-goods-button ${inCart ? 'added' : ''}`}
          onClick={handleAddToCart}
          disabled={item.count === 0}
        >
          {inCart ? '✓ Додано' : 'Додати до кошика'}
        </button>
      </div>
    </div>
  );
}
