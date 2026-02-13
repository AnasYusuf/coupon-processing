# Laravel Coupon Processing System

A high-concurrency, asynchronous coupon processing system built with **Laravel**, **Redis**, and **MySQL**.  
Designed for reliability, scalability, and auditability, this project demonstrates coupon reservation, consumption, and release with full event tracking.

---

## 🛠 Tech Stack

- **Backend:** Laravel 10 (PHP 8+)  
- **Queue & Jobs:** Laravel Jobs, Queues, optional Horizon  
- **Caching & Reservations:** Redis  
- **Database:** MySQL  
- **Event Tracking:** Coupon lifecycle events logged in `coupon_events` table  
- **Services:** `CouponService` for core business logic, `RuleEngine` for dynamic rule validation

---

## 🚀 Features

- **Asynchronous Coupon Validation**  
  All coupon logic runs via Laravel Jobs. Controllers only dispatch jobs.  

- **Idempotent Jobs**  
  Repeated job execution does not create duplicate reservations or events.  

- **Coupon Reservation System**  
  - Atomic reservation using Redis (`SET NX EX`)  
  - TTL: 5 minutes  
  - Safe across multiple servers  
  - Automatic release after expiration  

- **Dynamic Rule Validation**  
  - Rules/settings can change frequently  
  - Rules include per-user limit, global usage limit, min cart value, category restrictions, etc.  

- **Event Tracking for Audit**  
  - All lifecycle events recorded in `coupon_events`:  
    `validated`, `reserved`, `consumed`, `released`, `failed`  
  - Includes cart context and rule version  

- **Reliability & Recovery**  
  - Jobs configured with retries, backoff, and timeouts  
  - Reservation cleanup mechanism for expired or stuck reservations  

- **Queue Organization**  
  - `high` → validation  
  - `default` → cart updates  
  - `low` → analytics/logging  

---

## 📂 Project Structure

```text
coupon-processing/
│
├── app/
│   ├── Console/
│   │   ├── Commands/
│   │   │   └── TestCouponFlow.php
│   │   └── Kernel.php
│   │
│   ├── Http/
│   │   └── Controllers/
│   │       └── ApplyCouponController.php
│   │
│   ├── Jobs/
│   │   ├── ApplyCouponJob.php
│   │   ├── UpdateCartJob.php
│   │   └── LogEventJob.php
│   │
│   ├── Models/
│   │   ├── Coupon.php
│   │   ├── CouponEvent.php
│   │   └── Cart.php
│   │
│   └── Services/
│       ├── CouponService.php
│       └── RuleEngine.php
│
├── database/
│   ├── migrations/
│   └── seeders/
│
├── routes/
│   ├── api.php
│   └── console.php
│
├── config/
│   └── queue.php
│
└── README.md
```

---

## 📝 API Endpoints

| Method | Endpoint         | Description                                |
|--------|-----------------|--------------------------------------------|
| POST   | /api/apply-coupon | Apply a coupon (asynchronous validation) |
| POST   | /api/consume-coupon | Consume a reserved coupon                 |
| POST   | /api/release-coupon | Release a reserved coupon                 |

---

### Apply a Coupon

```json
POST /api/apply-coupon
{
  "user_id": 1,
  "coupon_code": "WELCOME10",
  "cart": {
    "total": 100,
    "items": [
      {"id":101,"name":"T-shirt","price":50,"quantity":1,"category":"clothing"},
      {"id":102,"name":"Jeans","price":50,"quantity":1,"category":"clothing"}
    ]
  }
}
```
### Response 
{
  "message": "Coupon verification in progress"
}

### Coupon_events table
event_type -> reserved
rule_version -> v1
cart_context -> {"total":100,"items":[{"id":101,"name":"T-shirt","price":50,"quantity":1,"category":"clothing"},{"id":102,"name":"Jeans","price":50,"quantity":1,"category":"clothing"}],"user_order_count":0}

---

### CheckOut Success (coupon consumed)

```json
POST /api/checkout-success
{
  "user_id": 2,
  "coupon_code": "WELCOME10",
  "cart": {"total":150,"items":[{"id":103,"name":"Shoes","price":150,"quantity":1}]}
}
```

### Response 
{
  "message": "Coupon consumed"
}

### Coupon_events table
event_type -> conusmed
rule_version -> v1

---

### CheckOut Failure (coupon released)

```json
POST /api/checkout-fail
{
  "user_id": 2,
  "coupon_code": "WELCOME10",
  "cart": {"total":150,"items":[{"id":103,"name":"Shoes","price":150,"quantity":1}]}
}
```

### Response 
{
  "message": "Coupon released"
}

### Coupon_events table
event_type -> released
rule_version -> v1

---

## 🔧 Testing Flow (End-to-End)

### 1. Start Laravel server:
php artisan serve

### 2. Start queue worker:
php artisan queue:work --queue=high,default,low

### 3. Apply a coupon via /api/apply-coupon
 - Check coupon_events → reserved event created.

### 5. Consume coupon via /api/consume-coupon.
 - Check coupon_events → consumed event created; reservation removed from Redis.

### 7. Release coupon via /api/release-coupon for failed or expired cases.

### 8. For end-to-end in one go, run the command 'TestCouponFlow.php'.

### All lifecycle events, cart context, and rule version are logged for audit.

### ⚡ Notes
- Uses **Redis** for atomic coupon reservations.
- Jobs are **idempotent**, ensuring retries or repeated execution do not cause inconsistencies.
- `CouponService` centralizes all business logic.
- `RuleEngine` handles dynamic validation rules (e.g., min cart value, first-time user, category-based eligibility).
- **Horizon** is configured for queue monitoring and scaling, but it is **not active on Windows** due to missing PHP extensions (`pcntl`).
- Queues and jobs still function normally without Horizon.

