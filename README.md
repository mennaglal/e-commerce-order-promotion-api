# E-Commerce Order & Promotion API

A backend for a small e-commerce platform, built with **Laravel 12**, **PHP 8.2+** and **MySQL**.
Customers browse products, fill a cart, apply promo codes, check out and manage their orders.

The main design goals were: **never oversell stock**, **never trust the client for prices**, and **keep checkout atomic and safe to retry**.

**Postman collection:** [`docs/postman/ecommerce-api.postman_collection.json`](docs/postman/ecommerce-api.postman_collection.json)
(Import it into Postman, run **Login**, and the token is saved automatically.)

---

## 1. What is implemented

- **Products:** paginated list with search by name, min/max price, availability filter and sorting; product details.
- **Authentication:** register / login / logout with Laravel Sanctum tokens. Cart and order endpoints require a token.
- **Cart:** add, update quantity, remove, show. Quantities can never exceed the available stock.
- **Promotion codes:** percentage or fixed value, minimum cart amount, maximum discount, start/end date, total usage limit, per-customer limit, active flag.
- **Checkout:** one database transaction that validates the cart, re-reads prices and stock from the database, validates the coupon, calculates subtotal / discount / total, reduces stock, creates the order and its items, and clears the cart.
- **Orders:** `orders` and `order_items`. Items keep a **snapshot** of product name, SKU and price, so changing a product later never changes old orders.
- **Cancellation:** allowed only from `pending` or `confirmed`; restores stock; safe to call many times.
- **Concurrency:** pessimistic row locking, explained in [section 6](#6-concurrency).
- **Validation and errors:** Form Requests, meaningful status codes and one JSON error format.

- **Bonus features implemented:** Redis caching of the product list, queued order-confirmation emails (events + listeners), idempotent checkout, API rate limiting, database indexes, and an admin panel for products and promotion codes.
- **Not implemented:** Docker/Laravel Sail setup, due to the available assessment timeframe. The project can be run locally using the setup instructions above.
- **Not included:** Docker/Sail.

---

## 2. Setup

**Requirements:** PHP 8.2+ (extensions `bcmath`, `pdo_mysql`, `mbstring`, `openssl`), Composer 2, MySQL 8, Redis is optional.

```bash
composer install
cp .env.example .env                  # PowerShell: copy .env.example .env
php artisan key:generate
```

Create an empty MySQL database, then set these values in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce_api
DB_USERNAME=root
DB_PASSWORD=
```

Then:

```bash
php artisan migrate --seed            # or: php artisan migrate:fresh --seed
php artisan serve                     # API: http://127.0.0.1:8000/api
php artisan queue:work                # separate terminal: sends order e-mails
```

### Environment variables

| Variable | Default | Meaning |
|---|---|---|
| `CACHE_STORE` | `database` | Use `redis` for the product list cache in production |
| `QUEUE_CONNECTION` | `database` | `redis` also works; no code change needed |
| `REDIS_CLIENT` | `phpredis` in `.env.example` | Use `predis` if the PHP extension is not installed (e.g. on Windows) |
| `MAIL_MAILER` | `log` | E-mails are written to `storage/logs/laravel.log` |

**Redis (optional):** set `CACHE_STORE=redis` (and `QUEUE_CONNECTION=redis`). If Redis is down, the product list falls back to the database and logs a warning, so the API keeps working.

### Seeded accounts

| Account | Credentials |
|---|---|
| Admin (`/admin`) | `admin@example.com` / `Admin@123456` |
| Customer 1 | `customer1@example.com` / `Password@123` |
| Customer 2 | `customer2@example.com` / `Password@123` |

The seeders also create about 125 random products (normal, low stock, out of stock, inactive) and about 40 random promotion codes (percentage, fixed, expired, scheduled, inactive, limited usage).

To try the example coupon from the assessment, create it in the admin panel (`/admin/promotion-codes`) or with `php artisan tinker`:

```php
App\Models\PromotionCode::create([
    'code' => 'SUMMER20', 'type' => 'percentage', 'value' => 20,
    'min_cart_amount' => 100, 'max_discount_amount' => 50, 'is_active' => true,
]);
```

### Tests

```bash
php artisan test
```

Tests run on in-memory SQLite (no MySQL needed). They cover authentication, product filters and caching, the cart, every coupon rule, checkout, idempotency, the order snapshot, cancellation, and the "two customers compete for the last items" scenario. SQLite ignores row locks, so the tests verify the business rules; the real lock behaviour is shown in [section 6.3](#63-trying-it-on-mysql).

---

## 3. API documentation

Base URL: `http://127.0.0.1:8000/api` · JSON · `Authorization: Bearer <token>` for protected routes.

| Method | Path | Auth | Purpose |
|---|---|---|---|
| POST | `/register` | – | Create an account, returns a token |
| POST | `/login` | – | Returns a token |
| POST | `/logout` | yes | Revoke the current token |
| GET | `/profile` | yes | Current user |
| GET | `/products` | – | Paginated list |
| GET | `/products/{id}` | – | Product details |
| GET | `/cart` | yes | Cart with line totals and subtotal |
| POST | `/cart/items` | yes | Add a product |
| PATCH | `/cart/items/{id}` | yes | Update quantity |
| DELETE | `/cart/items/{id}` | yes | Remove an item |
| POST | `/checkout` | yes | Create an order from the cart |
| GET | `/orders` | yes | My orders (paginated) |
| GET | `/orders/{id}` | yes | One of my orders |
| POST | `/orders/{id}/cancel` | yes | Cancel an order |

### Products

`GET /products` query parameters: `search`, `min_price`, `max_price`, `available` (`true`/`false`), `sort_by` (`name`, `price`, `created_at`, `stock_quantity`), `sort_direction` (`asc`/`desc`), `per_page` (1–50, default 20), `page`.
Only active products are returned.

### Cart

`POST /cart/items` → `{ "product_id": 2, "quantity": 2 }`. Adding a product that is already in the cart adds up the quantity. The total can never exceed the stock.
`PATCH /cart/items/{id}` → `{ "quantity": 4 }`.
`GET /cart` returns `items` (each with `unit_price` and `line_total`), `items_count`, `total_quantity`, `subtotal` and `currency`.

### Checkout

`POST /checkout` needs the header **`Idempotency-Key: <uuid>`** and a body like:

```json
{
    "customer_name": "Menna Galal",
    "customer_email": "menna@example.com",
    "customer_phone": "01000000001",
    "address": "12 Nile St, Giza",
    "notes": "Call before delivery",
    "payment_method": "cash_on_delivery",
    "promotion_code": "SUMMER20"
}
```

`payment_method` is `cash_on_delivery` or `online`; `promotion_code` and `notes` are optional.
**Prices, totals and discounts are not accepted from the client**; anything extra in the body is ignored.
The response is `201` for a new order and `200` when the same `Idempotency-Key` was already used (the existing order is returned).

```json
{
    "success": true,
    "message": "Order placed successfully.",
    "data": {
        "id": 12, "status": "pending", "promotion_code": "SUMMER20",
        "subtotal": "300.00", "discount_amount": "50.00", "total": "250.00", "currency": "EGP",
        "items": [ { "name": "Demo Product", "sku": "DEMO-1", "unit_price": "100.00", "quantity": 3, "line_total": "300.00" } ]
    }
}
```

### Orders

`POST /orders/{id}/cancel` accepts an optional `reason`. A second call returns `200` with "Order was already cancelled." and changes nothing. Orders that are `shipped` or `delivered` return `409`.

### Response format and error codes

Success: `{ "success": true, "message": "...", "data": ..., "meta": ... }` (`meta` only for paginated lists).
Failure: `{ "success": false, "message": "...", "code": "...", "errors": ... }`.

Every error under `/api` is JSON, even if the client forgets `Accept: application/json`.

| HTTP | `code` | When |
|---|---|---|
| 401 | `unauthenticated` | Missing/invalid token, wrong login |
| 403 | `forbidden` | Reading or cancelling another customer's order |
| 404 | `not_found` | Unknown product, order, cart item or route |
| 405 | `method_not_allowed` | Wrong HTTP verb |
| 409 | `insufficient_stock` | Requested quantity is more than the stock |
| 409 | `product_unavailable` | Product is inactive |
| 409 | `invalid_order_status` | Order status does not allow cancellation |
| 422 | `validation_failed` | Invalid input (`errors` lists the fields) |
| 422 | `cart_empty` | Checkout with an empty cart |
| 422 | `coupon_invalid` | Unknown or inactive code |
| 422 | `coupon_not_started` / `coupon_expired` | Outside the coupon dates |
| 422 | `coupon_min_amount` | Subtotal is below the minimum cart amount |
| 422 | `coupon_usage_limit_reached` | Total usage limit used up |
| 422 | `coupon_per_customer_limit_reached` | This customer reached their limit |
| 429 | `too_many_requests` | Rate limit exceeded |
| 500 | `server_error` | Unexpected error (details hidden unless `APP_DEBUG=true`) |

### Admin panel

`/admin` is a small Blade dashboard (admin account only) to manage products and promotion codes. Products and coupons that are referenced by carts, orders or usages cannot be deleted; they should be deactivated instead, so history stays intact.

---

## 4. Architecture

```
app/
├── Http/Controllers/Api/     thin controllers: call a service, return a response
├── Http/Controllers/Admin/   Blade admin panel
├── Http/Requests/            Form Requests (all validation rules)
├── Http/Resources/           JSON shape of products, cart, orders
├── Http/Responses/ApiResponse   builds every JSON response (success and error)
├── Services/                 business logic: Cart, Inventory, Checkout, Order,
│                             Product, PromotionCode, Auth
├── Models/                   relations, casts, status constants
├── Policies/OrderPolicy      "is this my order?"
├── Events/OrderPlaced  →  Listeners/SendOrderConfirmationEmail (queued)
└── Providers/AppServiceProvider   rate limiters
bootstrap/app.php             API exception handling
```

Request flow: route (throttle + auth) → Form Request → Controller → Service (transaction) → Resource → `ApiResponse`.

Decisions:

1. **Controllers stay thin; services hold the rules.** This keeps the business logic reusable and easy to test.
2. **Services return arrays instead of throwing business exceptions.** A failure is `['success' => false, 'message' => ..., 'errors' => ['code' => ...]]`. `ApiResponse::failure()` maps each `code` to an HTTP status in one table (`FAILURE_STATUS`). Because returning from a transaction closure commits, every validation in `CheckoutService` happens **before** the first write.
3. **Money is never a float.** Columns are `DECIMAL`, models cast to `decimal:2` strings, calculations use `bcmath`.
4. **The server is the only source of truth for prices.** They are re-read from the database after the product rows are locked.
5. **Order items are snapshots** (`product_name`, `product_sku`, `product_price`).
6. **Consistent errors.** `bootstrap/app.php` renders all exceptions under `/api/*` in one JSON format and hides internals in production.
7. **Authorization with a Policy.** Another customer's order gives `403`; an unknown id gives `404`.
8. **The event is fired after commit**, only for newly created orders. A rolled-back checkout or an idempotent replay never sends an e-mail.
9. **Order status transitions live in one place** (`Order::STATUS_TRANSITIONS` and `canTransitionTo()`):
   `pending → confirmed → shipped → delivered`, and `cancelled` is allowed only from `pending` or `confirmed`.

---

## 5. Database

Tables: `users`, `products`, `carts`, `cart_items`, `promotion_codes`, `promotion_usages`, `orders`, `order_items`, plus the framework tables (Sanctum tokens, jobs, cache, sessions).

| Table | Notes |
|---|---|
| `products` | `price DECIMAL`, `stock_quantity UNSIGNED INT` (cannot go negative in MySQL strict mode), `status` flag, unique `sku` |
| `carts` / `cart_items` | One cart per user; a product appears once per cart (unique `cart_id, product_id`) |
| `promotion_codes` | All coupon rules; nullable limits mean "no limit" |
| `promotion_usages` | One row per order that used a coupon; `cancelled_at` is set when the order is cancelled, so the row stays as history but no longer counts toward limits |
| `orders` | Totals, customer info, `status`, `payment_status`, `idempotency_key`; foreign keys use `RESTRICT` so history is never deleted by accident |
| `order_items` | Snapshot of name, SKU, price and quantity at purchase time |

### Indexes and why

| Index | Reason |
|---|---|
| `products (status, created_at)` | Default listing: active products, newest first |
| `products (status, price)` | Min/max price filter and price sorting |
| `products (status, stock_quantity)` | Availability filter |
| `products.sku` unique | SKU uniqueness |
| `carts.user_id` unique | One cart per user; fast lookup and lock |
| `cart_items (cart_id, product_id)` unique | No duplicate lines; add-to-cart upsert |
| `promotion_codes.code` unique | Coupon lookup at checkout |
| `orders (user_id, idempotency_key)` unique | One key creates one order per customer, even under concurrency |
| `orders (user_id, created_at)` | "My orders, newest first" |
| `orders (user_id, status)` | Filtering a customer's orders by status |
| `promotion_usages (promotion_id, user_id)` | Per-customer usage count |
| `promotion_usages (promotion_id, order_id)` unique | An order uses a coupon only once |

Every index slows writes a little and uses disk. The product table is read-heavy and rarely written, so the three composite indexes are worth it. `name LIKE '%x%'` cannot use an index (see trade-offs).

---

## 6. Concurrency

### 6.1 The problem

Stock is 5. Customer A buys 4 and customer B buys 3 at the same time. If both read "5", both pass the check and the stock ends at −2.

### 6.2 The solution

Checkout runs in a single transaction (`CheckoutService`):

1. Lock the customer's cart row (`SELECT … FOR UPDATE`): one checkout per customer at a time.
2. Check the idempotency key (same key → return the existing order).
3. Lock **all product rows of the cart, ordered by id**, and read price and stock *after* the lock.
4. Validate stock, calculate the subtotal, lock and validate the promotion code, calculate the discount.
5. Create the order and items, then decrement stock with an atomic `stock_quantity = stock_quantity - n`.
6. Record the coupon usage, clear the cart, commit.

| Step | Transaction A (4) | Transaction B (3) |
|---|---|---|
| 1 | Locks the product row, stock is 5 | Starts |
| 2 | 5 ≥ 4, OK | Tries to lock the same row → **waits** |
| 3 | Creates the order, stock becomes 1, commits | |
| 4 | | Gets the lock, **re-reads stock = 1**, 1 < 3 → `insufficient_stock` (409), nothing written |

The stock check when adding to the cart is only advisory (the cart does not reserve stock); the check after the lock is the one that counts.

Extra safety:

- **Deadlocks:** locks are always taken in the same order (cart → products by ascending id → promotion). Cancellation also touches products in ascending id order. If MySQL still reports a deadlock, the transaction is retried (up to 3 attempts).
- **Unsigned stock column:** a decrement that would go below zero fails in MySQL strict mode and rolls back.
- **Promotion limits:** the promotion row is locked too, so two customers cannot both take the last allowed usage.
- **Cancellation:** the order row is locked and the status is read *after* the lock. Parallel cancel requests restore the stock exactly once.


One request returns `201`, the other `409 insufficient_stock`, and the stock never goes negative.

### 6.4 Idempotent checkout

The client sends a UUID in `Idempotency-Key`. It is stored on the order with a unique index on `(user_id, idempotency_key)`. Sending the same key again returns the existing order (`200`): no second order, no second stock reduction, no second e-mail. Two identical requests at the same time are serialized by the cart lock.

---

## 7. Other features

**Promotion codes.** Checks run in this order: exists and active → started → not expired → minimum cart amount → total usage limit → per-customer limit. The discount is a percentage of the subtotal (or the fixed value), capped by `max_discount_amount` and never more than the subtotal. With `SUMMER20` (20 %, min 100, max 50): a cart of 300 gives 60, capped to **50**; a cart of 150 gives **30**. Codes are case-insensitive. Cancelling an order marks its usage as cancelled, which frees the coupon.

**Product list cache.** Each filter combination and page is cached under a key that contains a version number. Changing or deleting a product in the admin panel bumps the version, which invalidates all cached lists at once. Entries also expire after `PRODUCT_LIST_CACHE_TTL` seconds. Checkout never reads the cache.

**Queue and events.** `OrderPlaced` → `SendOrderConfirmationEmail` (queued, 3 tries, back-off of 10 s then 60 s). Run `php artisan queue:work`.

**Rate limiting.**

| Limiter | Limit | Key |
|---|---|---|
| `login`, `register` | 5 per minute | IP |
| `api-public` (products) | 60 per minute | IP |
| `api-auth` (authenticated routes) | 60 per minute | user |
| `checkout` (additional) | 10 per minute | user |

---

## 8. Trade-offs

| Decision | Benefit | Cost / alternative |
|---|---|---|
| Pessimistic row locks on stock | Always correct, easy to reason about | Checkouts of the same hot product queue up. Alternatives: a guarded `UPDATE … WHERE stock_quantity >= ?`, or Redis counters for flash sales |
| No stock reservation in the cart | Cheap carts, no expiry jobs | A cart can still fail at checkout (with a clear 409) |
| Product list cached with a TTL | Large read-load reduction | Stock and availability in the list can be up to 60 s old; orders do not invalidate the cache (checkout re-checks the database). No stampede protection |
| Cache failure falls back to the database | API survives a Redis outage | The database takes the full read load meanwhile |
| Idempotency key stored on `orders` | Atomic with the order, no extra table | Only successful orders are remembered; reusing a key with a different body returns the first order |
| Services return arrays | Explicit flow, no exception-driven logic | Less type-safe than DTOs; failures must come before any write inside a transaction |
| Event fired after commit | No e-mail for rolled-back orders | If the process dies between commit and dispatch, the event is lost (an outbox table would fix this) |
| `bcmath` money, percentage discount truncated | Exact arithmetic | 15 % of 10.10 = 1.515 becomes 1.51. Single currency (EGP), no tax or shipping |
| `LIKE '%term%'` search | Works everywhere | Cannot use a normal index; large catalogues need FULLTEXT or a search engine |
| Sanctum personal access tokens | Simple and revocable | Tokens do not expire by default |
| Rate limits by IP on public routes | Protects login and registration | Users behind one NAT share a limit |
| Blade admin panel instead of a JSON admin API | Usable right away | Not scriptable |
| Order status transitions modelled, but no admin screen to move orders along and no payment gateway | Keeps the scope on the assessment | Needed next: order management and payment attempts |

