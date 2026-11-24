# API Documentation

## BDD Scenarios

### Create Product
- Given authenticated seller
- When POST /api/v1/products with {name, description, price, category_id}
- Then 201 Created, product stored in `products` collection with seller_id, status=draft
- DB: products table

### Place Bid
- Given active auction
- When POST /api/v1/auctions/{id}/bids with {amount}
- Then 201, bid stored in `bids` collection
- DB: bids table

### Create Auction
- Given seller owns product
- When POST /api/v1/auctions with {product_id, start_price, min_increment, start_at, end_at}
- Then 201, auction stored in `auctions` collection
- DB: auctions table

## DB reference
See docs/DB.md for collection schema.
