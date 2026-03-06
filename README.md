# Multi-Tenant SaaS Inventory Management System

A fully dynamic, extendible, reusable multi-tenant SaaS Inventory Management System with a **React frontend** and **Laravel backend**, where each module is a separate microservice following the **Controller → Service → Repository** architecture pattern.

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        React Frontend                            │
│          (Auth, Products, Inventory, Orders, Users UI)           │
│                    Port 3000 (nginx)                             │
└──────────┬──────────┬────────────┬──────────────────────────────┘
           │          │            │            │
           ▼          ▼            ▼            ▼
    ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
    │  User    │ │ Product  │ │Inventory │ │  Order   │
    │ Service  │ │ Service  │ │ Service  │ │ Service  │
    │ :8001    │ │ :8002    │ │ :8003    │ │ :8004    │
    └────┬─────┘ └────┬─────┘ └────┬─────┘ └────┬─────┘
         │            │            │              │
         ▼            ▼            │  ◄──────────►│ (Saga)
    ┌─────────┐  ┌─────────┐       │
    │ user-db │  │product  │  ┌─────────┐  ┌─────────┐
    │ MySQL   │  │   db    │  │inventory│  │order-db │
    └─────────┘  └─────────┘  │   db   │  │ MySQL  │
                               └─────────┘  └─────────┘
                                    │
                     ┌──────────────┴──────────────┐
                     │          Redis               │
                     │   (Cache, Queue, Session)    │
                     └──────────────────────────────┘
                                    │
                     ┌──────────────┴──────────────┐
                     │         RabbitMQ             │
                     │  (Event-driven Messaging)    │
                     └──────────────────────────────┘
```

---

## Microservices

| Service | Port | Database | Responsibilities |
|---------|------|----------|-----------------|
| **User Service** | 8001 | `user_service` | Authentication (Passport SSO), RBAC/ABAC, user management, tenant management |
| **Product Service** | 8002 | `product_service` | Product catalog, categories, product events |
| **Inventory Service** | 8003 | `inventory_service` | Stock management, warehouse, cross-service product data |
| **Order Service** | 8004 | `order_service` | Order management, Saga pattern, distributed transactions |
| **Frontend** | 3000 | — | React SPA, auth, all CRUD views |

---

## Key Architectural Features

### 1. Base Repository Pattern
Every service contains a fully reusable `BaseRepository` with:
- `all()`, `find()`, `findBy()`, `findAllBy()` — basic CRUD
- `create()`, `update()`, `delete()` — write operations
- `paginate()` — standard pagination
- `search()` — LIKE-based multi-field search
- `filter()` — dynamic filtering with operators (`=`, `>`, `between`, `in`)
- `orderBy()` — sorting
- `with()` — eager loading
- `conditionalPaginate()` — **returns paginated results if `per_page` is present, all results otherwise** (works for queries, arrays, collections, API responses)
- `paginateIterable()` — paginate any iterable (array, Collection, API response)
- `transaction()` — ACID-compliant DB transactions

```php
// Usage example
$products = $productRepository
    ->with(['category'])
    ->orderBy('name', 'asc')
    ->conditionalPaginate([
        'per_page' => 15,
        'page' => 1,
        'search' => 'Widget',
        'filters' => ['is_active' => true],
        'sort_by' => 'price',
        'sort_direction' => 'asc',
    ]);
```

### 2. Laravel Passport SSO
- OAuth2 with personal access tokens
- Token refresh and revocation
- Cross-service token validation via Bearer header
- Tenant isolation enforced at authentication level

### 3. RBAC + ABAC Authorization
**RBAC** (Role-Based): Roles (`super_admin`, `admin`, `manager`, `viewer`) each with specific permissions (`create_products`, `read_inventory`, etc.)

**ABAC** (Attribute-Based): Evaluated policies consider:
- User attributes (role, tenant_id)
- Resource attributes (ownership)
- Environment (current tenant context)

```php
// Route protection
Route::middleware(['auth:api', 'tenant', 'abac:read,products'])->get('/products', ...);
Route::middleware(['auth:api', 'tenant', 'permission:create_products'])->post('/products', ...);
```

### 4. Multi-Tenant Architecture
- Every request carries `X-Tenant-ID` header
- `TenantMiddleware` validates tenant and applies runtime config
- `TenantConfigService` dynamically overrides mail, payment, notification settings per tenant
- All data is tenant-scoped

### 5. Pluggable MessageBroker Interface
Swap between RabbitMQ and Kafka without changing application code:

```php
// In .env
MESSAGE_BROKER_DRIVER=rabbitmq   # or: kafka

// Usage
$messageBroker->publish('product.created', ['product_id' => 1, ...]);
$messageBroker->subscribe('inventory.updated', fn($msg) => ...);
```

Supported brokers: `RabbitMQBroker`, `KafkaBroker` (plug in new ones by implementing `MessageBrokerInterface`)

### 6. Domain Events + Listeners
| Event | Listener | Effect |
|-------|----------|--------|
| `UserCreated` | `SendWelcomeEmail` | Queued welcome email |
| `ProductCreated` | — | Message broker publish |
| `ProductDeleted` | `NotifyInventoryOnProductDeleted` | Notifies inventory service |
| `InventoryUpdated` | — | Message broker publish |
| `OrderCreated` | — | Message broker publish + webhook |

### 7. Saga Pattern (Distributed Transactions)
Order creation uses the Saga Orchestrator pattern:

```
CreateOrder (pending)
    ↓
[Step 1] ReserveInventoryStep  →  calls Inventory Service API
    ↓ success
[Step 2] ProcessPaymentStep    →  calls Payment Gateway
    ↓ success  
[Step 3] ConfirmOrderStep      →  updates order to 'confirmed'
    ↓
Order Created ✅

On any failure:
[Step N] FAILS
    ↑
[Step N-1].compensate()  →  reverse each completed step
[Step N-2].compensate()
...
Order Cancelled ↩
```

Extend with new saga steps by implementing `SagaStep::execute()` and `SagaStep::compensate()`.

### 8. Cross-Service Data Access
The Inventory Service includes a `ProductServiceClient` that fetches product details from the Product Service via HTTP, enabling:
- Filter inventory by product name
- List inventory with enriched product details
- Search products across services

### 9. Webhook Integration
All services support tenant webhooks with HMAC-SHA256 signatures:

```
POST {tenant.webhook_url}
Headers:
  X-Webhook-Signature: <hmac-sha256>
  X-Webhook-Event: product.created
  X-Tenant-ID: 1

Body (WebhookPayloadDTO):
{
  "event": "product.created",
  "data": { "product_id": 1, "name": "Widget A" },
  "tenant_id": 1,
  "timestamp": "2024-01-01T00:00:00Z",
  "version": "1.0"
}
```

### 10. Health Check Endpoints
Every service exposes `GET /api/health`:

```json
{
  "status": "healthy",
  "service": "product-service",
  "timestamp": "2024-01-01T00:00:00.000Z",
  "checks": {
    "database": { "status": "healthy" },
    "cache": { "status": "healthy" },
    "app": { "status": "healthy", "name": "Product Service", "version": "1.0.0" }
  }
}
```

---

## Project Structure

```
SAAS_MultiTenent/
├── docker-compose.yml              # Full orchestration
├── frontend/                       # React SPA
│   ├── src/
│   │   ├── App.js
│   │   ├── contexts/               # AuthContext, TenantContext
│   │   ├── hooks/                  # useApi, usePaginatedApi
│   │   ├── services/               # api.js, productService, inventoryService, orderService
│   │   ├── components/             # Layout, DataTable, Pagination, SearchBar, StatusBadge
│   │   └── pages/                  # Login, Dashboard, Products, Inventory, Orders, Users
│   └── Dockerfile
├── user-service/                   # Laravel 10 - Auth/Users
│   └── app/
│       ├── Contracts/Repository/   # BaseRepositoryInterface
│       ├── Contracts/Messaging/    # MessageBrokerInterface
│       ├── Repositories/           # BaseRepository, UserRepository, TenantRepository
│       ├── Services/               # UserService, WebhookService, TenantConfigService
│       ├── Http/Controllers/       # AuthController, UserController, HealthCheckController
│       ├── Http/Middleware/        # TenantMiddleware, AuthorizeAbac, CheckPermission
│       ├── Models/                 # User, Tenant, Role, Permission
│       ├── Events/                 # UserCreated
│       ├── Listeners/              # SendWelcomeEmail
│       ├── DTOs/                   # UserDTO, WebhookPayloadDTO
│       └── Messaging/              # RabbitMQBroker, KafkaBroker
├── product-service/                # Laravel 10 - Products
│   └── app/
│       ├── (same pattern as user-service)
│       ├── Models/                 # Product, Category
│       ├── Events/                 # ProductCreated, ProductDeleted
│       └── Listeners/              # NotifyInventoryOnProductDeleted
├── inventory-service/              # Laravel 10 - Inventory
│   └── app/
│       ├── (same pattern as user-service)
│       ├── Models/                 # Inventory, InventoryTransaction, Warehouse
│       ├── Services/               # InventoryService, ProductServiceClient
│       └── Events/                 # InventoryUpdated
└── order-service/                  # Laravel 10 - Orders
    └── app/
        ├── (same pattern as user-service)
        ├── Models/                 # Order, OrderItem
        ├── Services/               # OrderService, InventoryServiceClient
        ├── Saga/                   # SagaOrchestrator, SagaStep, SagaResult
        │   └── Steps/              # ReserveInventoryStep, ProcessPaymentStep, ConfirmOrderStep
        └── Events/                 # OrderCreated
```

---

## Quick Start

### Prerequisites
- Docker & Docker Compose
- Node.js 18+ (for local frontend development)

### Run with Docker Compose

```bash
# 1. Clone repository
git clone <repo-url>
cd SAAS_MultiTenent

# 2. Start all services
docker-compose up -d

# 3. Initialize each Laravel service (run once)
docker-compose exec user-service php artisan key:generate
docker-compose exec user-service php artisan passport:install
docker-compose exec user-service php artisan migrate --seed

docker-compose exec product-service php artisan key:generate
docker-compose exec product-service php artisan migrate --seed

docker-compose exec inventory-service php artisan key:generate
docker-compose exec inventory-service php artisan migrate --seed

docker-compose exec order-service php artisan key:generate
docker-compose exec order-service php artisan migrate

# 4. Access the application
# Frontend:       http://localhost:3000
# User Service:   http://localhost:8001/api
# Product:        http://localhost:8002/api
# Inventory:      http://localhost:8003/api
# Orders:         http://localhost:8004/api
# RabbitMQ UI:    http://localhost:15672 (guest/guest)
```

### Local Development (Frontend)

```bash
cd frontend
cp .env.example .env
npm install
npm start
```

---

## API Reference

### Authentication (User Service - port 8001)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register new user |
| POST | `/api/auth/login` | Login, get Bearer token |
| POST | `/api/auth/logout` | Revoke current token |
| GET | `/api/auth/me` | Get authenticated user |
| POST | `/api/auth/refresh` | Refresh access token |

### Users (port 8001) — requires `X-Tenant-ID`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/users?per_page=10&search=john&sort_by=name` | List users (conditional pagination) |
| GET | `/api/users/{id}` | Get user with roles & permissions |
| PUT | `/api/users/{id}` | Update user |
| DELETE | `/api/users/{id}` | Delete user |
| POST | `/api/users/{id}/roles` | Assign role to user |

### Products (port 8002) — requires `X-Tenant-ID`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/products?per_page=15&search=widget&sort_by=price&sort_direction=desc` | List products |
| POST | `/api/products` | Create product (triggers ProductCreated event) |
| GET | `/api/products/{id}` | Get product |
| PUT | `/api/products/{id}` | Update product |
| DELETE | `/api/products/{id}` | Delete product (triggers ProductDeleted → notifies inventory) |
| GET | `/api/products/search/query?q=widget` | Search products |

### Inventory (port 8003) — requires `X-Tenant-ID`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/inventory?per_page=10` | List inventory |
| GET | `/api/inventory/with-products` | List inventory with product details (cross-service) |
| GET | `/api/inventory/reports/low-stock` | Get items below reorder level |
| GET | `/api/inventory/search/product-name?q=widget` | Search by product name |
| POST | `/api/inventory/{id}/add-stock` | Add stock (records transaction) |
| POST | `/api/inventory/{id}/remove-stock` | Remove stock (records transaction) |

### Orders (port 8004) — requires `X-Tenant-ID`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/orders?per_page=10&sort_by=created_at&sort_direction=desc` | List orders |
| POST | `/api/orders` | Create order (Saga: reserve → pay → confirm) |
| GET | `/api/orders/{id}` | Get order with items |
| POST | `/api/orders/{id}/cancel` | Cancel order + release inventory |

### Health Checks (all services)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/health` | Health status (DB + cache + app) |

---

## Tenant Runtime Configuration

Configure per-tenant mail, payment, and other settings via the `settings` JSON column:

```json
{
  "mail": {
    "host": "smtp.sendgrid.net",
    "port": 587,
    "username": "apikey",
    "password": "SG.xxx",
    "from_address": "noreply@acme.com",
    "from_name": "Acme Corp"
  },
  "payment": {
    "gateway": "stripe",
    "public_key": "pk_live_xxx",
    "secret_key": "sk_live_xxx"
  },
  "notifications": {
    "slack_webhook": "https://hooks.slack.com/services/..."
  }
}
```

These settings are dynamically applied per-request by `TenantConfigService::applyTenantConfig()`.

---

## Extending the System

### Add a New Microservice

1. Create `{service-name}/` directory mirroring existing service structure
2. Extend `BaseRepository` for your entity
3. Extend `BaseService`
4. Implement `BaseController` with `successResponse()` / `paginatedResponse()`
5. Add `TenantMiddleware` to your routes
6. Register in `docker-compose.yml`

### Add a New Message Broker

```php
// Create app/Messaging/PulsarBroker.php
class PulsarBroker implements MessageBrokerInterface {
    // implement: connect, disconnect, publish, subscribe, acknowledge, reject
}

// Register in RepositoryServiceProvider
'pulsar' => new PulsarBroker(...),

// Switch via .env
MESSAGE_BROKER_DRIVER=pulsar
```

### Add a New Saga Step

```php
class SendConfirmationEmailStep extends SagaStep
{
    public function getName(): string { return 'send_confirmation_email'; }

    public function execute(array &$context): array
    {
        // Send email...
        return ['email_sent' => true];
    }

    public function compensate(array $context): void
    {
        // Nothing to undo for emails
    }
}

// Register in OrderService
$saga->addStep(new SendConfirmationEmailStep($this->mailer));
```

---

## Technology Stack

| Layer | Technology |
|-------|-----------|
| Frontend | React 18, React Router 6, Axios |
| Backend | PHP 8.1, Laravel 10 |
| Authentication | Laravel Passport (OAuth2) |
| Database | MySQL 8.0 (per service) |
| Cache / Queue | Redis 7 |
| Message Broker | RabbitMQ 3 (or Apache Kafka) |
| Container | Docker, Docker Compose |
| Web Server | Nginx (frontend), PHP-FPM (services) |

---

## Security

- **Authentication**: Laravel Passport OAuth2 Bearer tokens
- **Authorization**: RBAC roles + ABAC attribute policies, enforced per-request
- **Tenant Isolation**: Every query is scoped to `tenant_id`; cross-tenant access returns 404
- **Webhook Signatures**: HMAC-SHA256 signed payloads; receivers validate before processing
- **SQL Injection Prevention**: All LIKE searches use `addcslashes()` to escape wildcards; Eloquent parameterized queries throughout
- **Password Hashing**: Laravel's `bcrypt` via `Hash::make()`
- **No Secrets in Code**: All credentials via environment variables (`.env.example` provided)

---

## Design Patterns Used

| Pattern | Where |
|---------|-------|
| Repository Pattern | `BaseRepository` + concrete repositories per entity |
| Service Layer | `BaseService` + concrete services per domain |
| Strategy / Interface | `MessageBrokerInterface` for swappable brokers |
| Saga Orchestrator | `SagaOrchestrator` + `SagaStep` in order-service |
| DTO | `UserDTO`, `ProductDTO`, `InventoryDTO`, `OrderDTO`, `WebhookPayloadDTO` |
| Observer / Events | Laravel Events + Listeners for domain events |
| Middleware Chain | Tenant → Auth → ABAC/RBAC → Controller |
| Factory Method | `DTOs::fromModel()` static factories |
| Template Method | `SagaStep::execute()` / `compensate()` abstract methods |
