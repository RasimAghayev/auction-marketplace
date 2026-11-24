// MongoDB Initialization Script
// This script runs on first container startup to set up collections and indexes

const dbName = 'auction_marketplace';
const db = db.getSiblingDB(dbName);

// Enable sharding if needed (for future scaling)
// sh.enableSharding(dbName);

// ========================
// USERS COLLECTION
// ========================
db.createCollection('users', {
    validator: {
        $jsonSchema: {
            bsonType: 'object',
            required: ['name', 'email', 'password', 'role'],
            properties: {
                _id: { bsonType: 'objectId' },
                name: { bsonType: 'string', minLength: 1, maxLength: 255 },
                email: { bsonType: 'string', pattern: '^.+@.+\\..+$' },
                email_verified_at: { bsonType: ['date', 'null'] },
                password: { bsonType: 'string', minLength: 60 },
                role: { bsonType: 'string', enum: ['admin', 'user', 'seller'] },
                avatar: { bsonType: ['string', 'null'] },
                phone: { bsonType: ['string', 'null'] },
                address: { bsonType: ['object', 'null'] },
                is_active: { bsonType: 'bool' },
                last_login_at: { bsonType: ['date', 'null'] },
                created_at: { bsonType: 'date' },
                updated_at: { bsonType: 'date' }
            }
        }
    }
});

db.users.createIndex({ email: 1 }, { unique: true, name: 'idx_users_email_unique' });
db.users.createIndex({ role: 1 }, { name: 'idx_users_role' });
db.users.createIndex({ is_active: 1 }, { name: 'idx_users_is_active' });
db.users.createIndex({ created_at: -1 }, { name: 'idx_users_created_at' });

// ========================
// CATEGORIES COLLECTION
// ========================
db.createCollection('categories', {
    validator: {
        $jsonSchema: {
            bsonType: 'object',
            required: ['name', 'slug'],
            properties: {
                _id: { bsonType: 'objectId' },
                name: { bsonType: 'string', minLength: 1, maxLength: 255 },
                slug: { bsonType: 'string', minLength: 1, maxLength: 255 },
                description: { bsonType: ['string', 'null'] },
                parent_id: { bsonType: ['objectId', 'null'] },
                image: { bsonType: ['string', 'null'] },
                is_active: { bsonType: 'bool' },
                sort_order: { bsonType: 'int' },
                created_at: { bsonType: 'date' },
                updated_at: { bsonType: 'date' }
            }
        }
    }
});

db.categories.createIndex({ slug: 1 }, { unique: true, name: 'idx_categories_slug_unique' });
db.categories.createIndex({ parent_id: 1 }, { name: 'idx_categories_parent' });
db.categories.createIndex({ is_active: 1, sort_order: 1 }, { name: 'idx_categories_active_sort' });

// ========================
// PRODUCTS COLLECTION
// ========================
db.createCollection('products', {
    validator: {
        $jsonSchema: {
            bsonType: 'object',
            required: ['seller_id', 'title', 'slug', 'description', 'category_id', 'condition', 'starting_price', 'currency', 'status'],
            properties: {
                _id: { bsonType: 'objectId' },
                seller_id: { bsonType: 'objectId' },
                title: { bsonType: 'string', minLength: 1, maxLength: 255 },
                slug: { bsonType: 'string', minLength: 1, maxLength: 255 },
                description: { bsonType: 'string' },
                category_id: { bsonType: 'objectId' },
                images: {
                    bsonType: 'array',
                    items: {
                        bsonType: 'object',
                        required: ['url', 'is_primary'],
                        properties: {
                            url: { bsonType: 'string' },
                            alt: { bsonType: ['string', 'null'] },
                            is_primary: { bsonType: 'bool' },
                            sort_order: { bsonType: 'int' }
                        }
                    }
                },
                condition: { bsonType: 'string', enum: ['new', 'used'] },
                starting_price: { bsonType: 'decimal' },
                reserve_price: { bsonType: ['decimal', 'null'] },
                currency: { bsonType: 'string', minLength: 3, maxLength: 3 },
                status: { bsonType: 'string', enum: ['draft', 'published', 'in_auction', 'sold', 'archived'] },
                meta: { bsonType: ['object', 'null'] },
                created_at: { bsonType: 'date' },
                updated_at: { bsonType: 'date' },
                deleted_at: { bsonType: ['date', 'null'] }
            }
        }
    }
});

db.products.createIndex({ seller_id: 1, created_at: -1 }, { name: 'idx_products_seller_created' });
db.products.createIndex({ slug: 1 }, { unique: true, name: 'idx_products_slug_unique' });
db.products.createIndex({ category_id: 1, status: 1 }, { name: 'idx_products_category_status' });
db.products.createIndex({ status: 1, created_at: -1 }, { name: 'idx_products_status_created' });
db.products.createIndex({ title: 'text', description: 'text' }, { name: 'idx_products_text_search' });
db.products.createIndex({ 'images.is_primary': 1 }, { name: 'idx_products_primary_image' });

// ========================
// AUCTIONS COLLECTION
// ========================
db.createCollection('auctions', {
    validator: {
        $jsonSchema: {
            bsonType: 'object',
            required: ['product_id', 'seller_id', 'starting_price', 'current_price', 'min_bid_increment', 'start_date', 'end_date', 'status'],
            properties: {
                _id: { bsonType: 'objectId' },
                product_id: { bsonType: 'objectId' },
                seller_id: { bsonType: 'objectId' },
                starting_price: { bsonType: 'decimal' },
                current_price: { bsonType: 'decimal' },
                min_bid_increment: { bsonType: 'decimal' },
                reserve_price: { bsonType: ['decimal', 'null'] },
                start_date: { bsonType: 'date' },
                end_date: { bsonType: 'date' },
                status: { bsonType: 'string', enum: ['scheduled', 'active', 'completed', 'cancelled', 'unsold'] },
                winner_id: { bsonType: ['objectId', 'null'] },
                total_bids: { bsonType: 'int', minimum: 0 },
                version: { bsonType: 'int', minimum: 0 },
                meta: { bsonType: ['object', 'null'] },
                created_at: { bsonType: 'date' },
                updated_at: { bsonType: 'date' },
                cancelled_at: { bsonType: ['date', 'null'] },
                completed_at: { bsonType: ['date', 'null'] }
            }
        }
    }
});

db.auctions.createIndex({ product_id: 1 }, { unique: true, name: 'idx_auctions_product_unique' });
db.auctions.createIndex({ seller_id: 1, created_at: -1 }, { name: 'idx_auctions_seller_created' });
db.auctions.createIndex({ status: 1, end_date: 1 }, { name: 'idx_auctions_status_end_date' });
db.auctions.createIndex({ status: 1, start_date: 1 }, { name: 'idx_auctions_status_start_date' });
db.auctions.createIndex({ end_date: 1 }, { name: 'idx_auctions_end_date', expireAfterSeconds: 0 });
db.auctions.createIndex({ winner_id: 1 }, { name: 'idx_auctions_winner' });
db.auctions.createIndex({ current_price: -1 }, { name: 'idx_auctions_current_price' });

// ========================
// BIDS COLLECTION
// ========================
db.createCollection('bids', {
    validator: {
        $jsonSchema: {
            bsonType: 'object',
            required: ['auction_id', 'user_id', 'amount', 'status'],
            properties: {
                _id: { bsonType: 'objectId' },
                auction_id: { bsonType: 'objectId' },
                user_id: { bsonType: 'objectId' },
                amount: { bsonType: 'decimal' },
                status: { bsonType: 'string', enum: ['pending', 'accepted', 'rejected', 'outbid', 'winning', 'won', 'lost'] },
                request_id: { bsonType: 'string' },
                meta: { bsonType: ['object', 'null'] },
                created_at: { bsonType: 'date' },
                updated_at: { bsonType: 'date' }
            }
        }
    }
});

db.bids.createIndex({ auction_id: 1, created_at: -1 }, { name: 'idx_bids_auction_created' });
db.bids.createIndex({ user_id: 1, created_at: -1 }, { name: 'idx_bids_user_created' });
db.bids.createIndex({ auction_id: 1, amount: -1 }, { name: 'idx_bids_auction_amount' });
db.bids.createIndex({ request_id: 1 }, { unique: true, name: 'idx_bids_request_id_unique' });
db.bids.createIndex({ status: 1 }, { name: 'idx_bids_status' });
db.bids.createIndex({ auction_id: 1, status: 1, amount: -1 }, { name: 'idx_bids_auction_status_amount' });

// ========================
// NOTIFICATIONS COLLECTION
// ========================
db.createCollection('notifications', {
    validator: {
        $jsonSchema: {
            bsonType: 'object',
            required: ['user_id', 'type', 'title', 'data'],
            properties: {
                _id: { bsonType: 'objectId' },
                user_id: { bsonType: 'objectId' },
                type: { bsonType: 'string' },
                title: { bsonType: 'string', maxLength: 255 },
                message: { bsonType: ['string', 'null'] },
                data: { bsonType: 'object' },
                read_at: { bsonType: ['date', 'null'] },
                created_at: { bsonType: 'date' }
            }
        }
    }
});

db.notifications.createIndex({ user_id: 1, read_at: 1, created_at: -1 }, { name: 'idx_notifications_user_read_created' });
db.notifications.createIndex({ created_at: 1 }, { expireAfterSeconds: 2592000, name: 'idx_notifications_ttl' });

// ========================
// AUCTION PARTICIPANTS COLLECTION
// ========================
db.createCollection('auction_participants', {
    validator: {
        $jsonSchema: {
            bsonType: 'object',
            required: ['auction_id', 'user_id'],
            properties: {
                _id: { bsonType: 'objectId' },
                auction_id: { bsonType: 'objectId' },
                user_id: { bsonType: 'objectId' },
                first_bid_at: { bsonType: ['date', 'null'] },
                last_bid_at: { bsonType: ['date', 'null'] },
                total_bids: { bsonType: 'int', minimum: 0 },
                highest_bid: { bsonType: ['decimal', 'null'] },
                is_watcher: { bsonType: 'bool' },
                created_at: { bsonType: 'date' },
                updated_at: { bsonType: 'date' }
            }
        }
    }
});

db.auction_participants.createIndex({ auction_id: 1, user_id: 1 }, { unique: true, name: 'idx_participants_auction_user_unique' });
db.auction_participants.createIndex({ user_id: 1 }, { name: 'idx_participants_user' });
db.auction_participants.createIndex({ auction_id: 1, highest_bid: -1 }, { name: 'idx_participants_auction_highest_bid' });

// ========================
// AUCTION HISTORY COLLECTION
// ========================
db.createCollection('auction_history', {
    validator: {
        $jsonSchema: {
            bsonType: 'object',
            required: ['auction_id', 'event_type', 'data'],
            properties: {
                _id: { bsonType: 'objectId' },
                auction_id: { bsonType: 'objectId' },
                user_id: { bsonType: ['objectId', 'null'] },
                event_type: { bsonType: 'string', enum: ['created', 'started', 'bid_placed', 'bid_outbid', 'reserve_met', 'extended', 'ended', 'cancelled', 'winner_declared', 'payment_initiated', 'completed'] },
                data: { bsonType: 'object' },
                created_at: { bsonType: 'date' }
            }
        }
    }
});

db.auction_history.createIndex({ auction_id: 1, created_at: -1 }, { name: 'idx_history_auction_created' });
db.auction_history.createIndex({ user_id: 1, created_at: -1 }, { name: 'idx_history_user_created' });
db.auction_history.createIndex({ event_type: 1 }, { name: 'idx_history_event_type' });

// ========================
// ACTIVITY LOGS COLLECTION
// ========================
db.createCollection('activity_logs', {
    validator: {
        $jsonSchema: {
            bsonType: 'object',
            required: ['user_id', 'action', 'subject_type', 'subject_id', 'properties'],
            properties: {
                _id: { bsonType: 'objectId' },
                user_id: { bsonType: ['objectId', 'null'] },
                action: { bsonType: 'string' },
                subject_type: { bsonType: 'string' },
                subject_id: { bsonType: 'objectId' },
                properties: { bsonType: 'object' },
                ip_address: { bsonType: ['string', 'null'] },
                user_agent: { bsonType: ['string', 'null'] },
                created_at: { bsonType: 'date' }
            }
        }
    }
});

db.activity_logs.createIndex({ user_id: 1, created_at: -1 }, { name: 'idx_activity_user_created' });
db.activity_logs.createIndex({ subject_type: 1, subject_id: 1 }, { name: 'idx_activity_subject' });
db.activity_logs.createIndex({ action: 1 }, { name: 'idx_activity_action' });
db.activity_logs.createIndex({ created_at: 1 }, { expireAfterSeconds: 31536000, name: 'idx_activity_ttl' });

// ========================
// INSERT DEFAULT DATA
// ========================

// Insert default categories
const categories = [
    { name: 'Electronics', slug: 'electronics', description: 'Electronic devices and gadgets', is_active: true, sort_order: 1, created_at: new Date(), updated_at: new Date() },
    { name: 'Fashion', slug: 'fashion', description: 'Clothing, accessories, and footwear', is_active: true, sort_order: 2, created_at: new Date(), updated_at: new Date() },
    { name: 'Home & Garden', slug: 'home-garden', description: 'Furniture, decor, and garden items', is_active: true, sort_order: 3, created_at: new Date(), updated_at: new Date() },
    { name: 'Collectibles', slug: 'collectibles', description: 'Rare and collectible items', is_active: true, sort_order: 4, created_at: new Date(), updated_at: new Date() },
    { name: 'Art', slug: 'art', description: 'Paintings, sculptures, and artwork', is_active: true, sort_order: 5, created_at: new Date(), updated_at: new Date() },
    { name: 'Vehicles', slug: 'vehicles', description: 'Cars, motorcycles, and vehicles', is_active: true, sort_order: 6, created_at: new Date(), updated_at: new Date() },
    { name: 'Jewelry', slug: 'jewelry', description: 'Fine jewelry and watches', is_active: true, sort_order: 7, created_at: new Date(), updated_at: new Date() },
    { name: 'Sports', slug: 'sports', description: 'Sports equipment and memorabilia', is_active: true, sort_order: 8, created_at: new Date(), updated_at: new Date() }
];

db.categories.insertMany(categories);

// Create admin user (password: password123)
// In production, this should be done via a secure seeder
const adminUser = {
    name: 'Admin User',
    email: 'admin@auction.local',
    email_verified_at: new Date(),
    password: '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBPj/RK.PZvO.S', // password123
    role: 'admin',
    avatar: null,
    phone: null,
    address: null,
    is_active: true,
    last_login_at: null,
    created_at: new Date(),
    updated_at: new Date()
};

db.users.insertOne(adminUser);

print('MongoDB initialization completed successfully!');
print('Collections created with indexes and default data.');