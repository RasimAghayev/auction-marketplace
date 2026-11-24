# Database

## Collections

| Collection | Key fields |
|---|---|
| users | _id, name, email, password, role |
| products | _id, seller_id, category_id, name, price, status |
| auctions | _id, product_id, seller_id, start_price, min_increment, status, start_at, end_at |
| bids | _id, auction_id, user_id, amount, status |
| categories | _id, name, slug, parent_id, is_active |
