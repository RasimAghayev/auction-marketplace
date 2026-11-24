# Auction Marketplace

A distributed, production-ready online auction marketplace built with Laravel 12, Vue.js 3, MongoDB, and Redis.

## Features

- **Real-time Bidding**: Live auction updates using Laravel Reverb and WebSockets
- **Distributed Architecture**: Horizontal scaling support with Docker, Nginx load balancing
- **Complete Auction Workflow**: Registration → Product Creation → Auction → Bidding → Winner Determination
- **User Dashboard**: Products, auctions, bids, history, and profile management
- **Admin Panel**: User management, product moderation, auction oversight, categories, reports
- **Security**: JWT/Sanctum auth, rate limiting, input validation, XSS/CSRF protection
- **Modern UI**: Tailwind CSS, dark/light mode, responsive design

## Technology Stack

### Backend
- PHP 8.3+
- Laravel 12+
- MongoDB (primary database)
- Redis (caching, queues, real-time coordination)
- Laravel Reverb (WebSockets)
- Laravel Sanctum (API authentication)

### Frontend
- Vue.js 3 (Composition API)
- TypeScript
- Vite
- Pinia (state management)
- Vue Router
- Tailwind CSS
- Axios

### Infrastructure
- Docker & Docker Compose
- Nginx (load balancer)
- PHP-FPM
- MongoDB Replica Set ready
- Multiple queue workers
- Laravel Scheduler

## Project Structure

```
auction-marketplace/
├── backend/                 # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/Api/V1/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Repositories/
│   │   ├── Events/Listeners/
│   │   ├── Jobs/
│   │   ├── Policies/
│   │   ├── DTOs/Enums/Traits/Rules/
│   │   └── Notifications/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── tests/
│   └── docker/
├── frontend/                # Vue.js 3 App
│   ├── src/
│   │   ├── components/
│   │   ├── views/
│   │   ├── layouts/
│   │   ├── stores/
│   │   ├── router/
│   │   ├── services/
│   │   ├── utils/
│   │   ├── types/
│   │   ├── composables/
│   │   └── plugins/
│   └── public/
├── docker/
│   ├── nginx/
│   ├── mongodb/
│   ├── redis/
│   └── php/
├── docker-compose.yml
└── .env.example
```

## Quick Start

### Prerequisites
- Docker 24+
- Docker Compose 2+
- Git

### Development Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd auction-marketplace
   ```

2. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your configuration
   ```

3. **Start all services**
   ```bash
   docker compose up -d --build
   ```

4. **Install backend dependencies**
   ```bash
   docker compose exec backend composer install
   ```

5. **Run migrations and seeders**
   ```bash
   docker compose exec backend php artisan migrate --seed
   ```

6. **Install frontend dependencies**
   ```bash
   docker compose exec frontend npm install
   ```

7. **Build frontend assets**
   ```bash
   docker compose exec frontend npm run build
   ```

8. **Access the application**
   - Frontend: http://localhost
   - API: http://localhost/api
   - Reverb WebSocket: ws://localhost:8080

### Production Deployment

1. **Set production environment variables**
   ```bash
   cp .env.example .env.production
   # Configure production values
   ```

2. **Deploy with Docker Compose**
   ```bash
   docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
   ```

## Services

| Service | Port | Description |
|---------|------|-------------|
| Nginx | 80, 443 | Load balancer & static files |
| Backend | 9000 | Laravel API (PHP-FPM) |
| Frontend | 80 | Vue.js app (served by Nginx) |
| MongoDB | 27017 | Primary database |
| Redis | 6379 | Cache, queues, pub/sub |
| Reverb | 8080 | WebSocket server |
| Queue Workers | - | Background job processing |
| Scheduler | - | Cron job execution |

## API Endpoints

### Authentication
- `POST /api/v1/auth/register` - Register new user
- `POST /api/v1/auth/login` - Login
- `POST /api/v1/auth/logout` - Logout
- `POST /api/v1/auth/forgot-password` - Request password reset
- `POST /api/v1/auth/reset-password` - Reset password
- `GET /api/v1/auth/user` - Get authenticated user

### Products
- `GET /api/v1/products` - List products
- `POST /api/v1/products` - Create product
- `GET /api/v1/products/{product}` - Get product
- `PUT /api/v1/products/{product}` - Update product
- `DELETE /api/v1/products/{product}` - Delete product

### Auctions
- `GET /api/v1/auctions` - List auctions
- `POST /api/v1/auctions` - Create auction
- `GET /api/v1/auctions/{auction}` - Get auction details
- `POST /api/v1/auctions/{auction}/start` - Start auction
- `POST /api/v1/auctions/{auction}/cancel` - Cancel auction

### Bids
- `POST /api/v1/auctions/{auction}/bids` - Place bid
- `GET /api/v1/auctions/{auction}/bids` - Get auction bids
- `GET /api/v1/my-bids` - Get user's bids

### Dashboard
- `GET /api/v1/dashboard/overview` - Dashboard overview
- `GET /api/v1/dashboard/products` - User's products
- `GET /api/v1/dashboard/auctions` - User's auctions
- `GET /api/v1/dashboard/participated` - Participated auctions
- `GET /api/v1/dashboard/won` - Won auctions
- `GET /api/v1/dashboard/history` - Auction history
- `GET /api/v1/dashboard/bids` - User's bids

### Admin
- `GET /api/v1/admin/dashboard` - Admin statistics
- `GET /api/v1/admin/users` - Manage users
- `GET /api/v1/admin/products` - Manage products
- `GET /api/v1/admin/auctions` - Manage auctions
- `GET /api/v1/admin/categories` - Manage categories
- `GET /api/v1/admin/reports` - Reports & statistics

## Real-time Events

### WebSocket Channels
- `auction.{id}` - Auction-specific updates
- `user.{id}` - User-specific notifications

### Events Broadcast
- `bid.placed` - New bid placed
- `auction.updated` - Auction state changed
- `auction.ended` - Auction completed
- `notification.created` - New notification

## Testing

```bash
# Run all tests
docker compose exec backend php artisan test

# Run with coverage
docker compose exec backend php artisan test --coverage

# Run specific test
docker compose exec backend php artisan test --filter=BidTest
```

## Architecture Decisions

### Distributed Bidding Consistency
- Redis distributed locks for bid processing
- Optimistic concurrency control with version field
- Atomic operations for bid validation
- Idempotent bid submission via request IDs

### Database Design
- MongoDB collections with appropriate indexes
- Compound indexes for auction/bid queries
- TTL indexes for notifications and activity logs
- Soft deletes for audit trail

### Caching Strategy
- Redis for session storage
- Query caching for frequent reads
- Auction state caching for real-time updates
- Rate limiting with Redis counters

## Security Considerations

- Password hashing with bcrypt (cost 12)
- JWT tokens with expiration
- Rate limiting on auth endpoints
- Input validation on all requests
- CORS configuration
- Secure file upload validation
- XSS protection with CSP headers
- Audit logging for sensitive operations

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests and linting
5. Submit a pull request

## License

MIT License - see LICENSE file for details.