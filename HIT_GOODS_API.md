# API для вивлення Hit товарів

## Опис

Цей API дозволяє отримати список товарів, позначених як "hit" (гарячі пропозиції) у форматі JSON.

## Endpoint

```
GET /api/goods/hits
```

## Параметри запиту

| Параметр | Тип | За замовчуванням | Опис |
|----------|-----|------------------|------|
| `limit` | integer | 10 | Кількість товарів для повернення |
| `offset` | integer | 0 | Зміщення від початку списку (для пагінації) |

## Приклади запитів

### Отримати перші 10 товарів
```bash
curl "http://your-api.com/api/goods/hits"
```

### Отримати 20 товарів з відступом 10
```bash
curl "http://your-api.com/api/goods/hits?limit=20&offset=10"
```

### У JavaScript/React
```javascript
fetch('/api/goods/hits?limit=10&offset=0')
  .then(res => res.json())
  .then(data => console.log(data));
```

## Формат відповіді

### З успіхом (200 OK)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Назва товару",
      "name_ua": "Назва товару укр.",
      "name_en": "Product name",
      "description": "Опис товару",
      "description_ua": "Опис товару укр.",
      "description_en": "Product description",
      "price": 99.99,
      "oldPrice": 129.99,
      "count": 15,
      "image": "/path/to/image.jpg",
      "image_thumb": "/path/to/thumb.jpg"
    }
  ],
  "limit": 10,
  "offset": 0
}
```

## Поля відповіді

| Поле | Тип | Опис |
|------|-----|------|
| `success` | boolean | Статус запиту |
| `data` | array | Масив товарів |
| `limit` | integer | Використане обмеження |
| `offset` | integer | Використане зміщення |

### Об'єкт товару

| Поле | Тип | Опис |
|------|-----|------|
| `id` | integer | Унікальний ID товару |
| `name` | string | Назва товару (рос.) |
| `name_ua` | string | Назва товару (укр.) |
| `name_en` | string | Назва товару (англ.) |
| `description` | string | Опис товару (рос.) |
| `description_ua` | string | Опис товару (укр.) |
| `description_en` | string | Опис товару (англ.) |
| `price` | float | Поточна ціна |
| `oldPrice` | float | Стара ціна (якщо є знижка) |
| `count` | integer | Кількість на складі |
| `image` | string | URL головного зображення |
| `image_thumb` | string | URL мініатюри зображення |

## Коди помилок

| Код | Статус | Опис |
|-----|--------|------|
| 200 | OK | Успішний запит |
| 400 | Bad Request | Неправильні параметри |
| 500 | Server Error | Помилка сервера |

## Використання у React

### Базовий приклад

```javascript
import { useState, useEffect } from 'react';

function HitProducts() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchProducts = async () => {
      try {
        const response = await fetch('/api/goods/hits?limit=12');
        const result = await response.json();
        if (result.success) {
          setProducts(result.data);
        }
      } catch (error) {
        console.error('Error:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchProducts();
  }, []);

  if (loading) return <div>Завантаження...</div>;

  return (
    <div className="products-grid">
      {products.map(product => (
        <div key={product.id} className="product-card">
          <img src={product.image} alt={product.name} />
          <h3>{product.name}</h3>
          <p>${product.price}</p>
          {product.count > 0 ? (
            <button>Додати до кошика</button>
          ) : (
            <button disabled>Немає в наявності</button>
          )}
        </div>
      ))}
    </div>
  );
}

export default HitProducts;
```

### З пагінацією

```javascript
import { useState } from 'react';

function HitProductsWithPagination() {
  const [products, setProducts] = useState([]);
  const [offset, setOffset] = useState(0);
  const limit = 10;

  const handleLoadMore = async () => {
    const newOffset = offset + limit;
    const response = await fetch(
      `/api/goods/hits?limit=${limit}&offset=${newOffset}`
    );
    const result = await response.json();
    if (result.success) {
      setProducts([...products, ...result.data]);
      setOffset(newOffset);
    }
  };

  return (
    <>
      <div className="products">
        {/* Відобразити товари */}
      </div>
      <button onClick={handleLoadMore}>Більше товарів</button>
    </>
  );
}
```

## Примітки

- Тільки товари з флагом `hit = '1'` та `web = '1'` будуть повернені
- Дефолтна група цін: `tgroup = '1'` (роздрібна торгівля)
- Текст в полях `name` та `description` декодується з base64
- Якщо основне зображення (`image`) недоступне, використовуйте мініатюру (`image_thumb`)
