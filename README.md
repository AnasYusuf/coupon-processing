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

## 🧩 Project Structure

app/
├─ Jobs/
│ └─ ApplyCouponJob.php
├─ Services/
│ ├─ CouponService.php
│ └─ RuleEngine.php
└─ Http/Controllers/
└─ CouponController.php

database/
└─ migrations/
├─ create_coupons_table.php
└─ create_coupon_events_table.php


---

## 📝 API Endpoints

| Method | Endpoint         | Description                                |
|--------|-----------------|--------------------------------------------|
| POST   | /api/apply-coupon | Apply a coupon (asynchronous validation) |
| POST   | /api/consume-coupon | Consume a reserved coupon                 |
| POST   | /api/release-coupon | Release a reserved coupon                 |

---

### Example Request: Apply Coupon

```json
POST /api/apply-coupon
{
  "user_id": 1,
  "coupon_code": "WELCOME10",
  "cart": {
    "total": 150,
    "items": [
      {"id":103,"name":"Shoes","price":150,"quantity":1}
    ]
  }
}
```

### Response 
{
  "message": "Coupon verification in progress"
}

## 🔧 Testing Flow (End-to-End)

### 1. Start Laravel server:
php artisan serve

### 2. Start queue worker:
php artisan queue:work --queue=high,default,low

### 3. Apply a coupon via /api/apply-coupon.

### 4. Check coupon_events → reserved event created.

### 5. Consume coupon via /api/consume-coupon.

### 6. Check coupon_events → consumed event created; reservation removed from Redis.

### 7. Release coupon via /api/release-coupon for failed or expired cases.

### All lifecycle events, cart context, and rule version are logged for audit.

### ⚡ Notes
- Uses **Redis** for atomic coupon reservations.
- Jobs are **idempotent**, ensuring retries or repeated execution do not cause inconsistencies.
- `CouponService` centralizes all business logic.
- `RuleEngine` handles dynamic validation rules (e.g., min cart value, first-time user, category-based eligibility).
- **Horizon** is configured for queue monitoring and scaling, but it is **not active on Windows** due to missing PHP extensions (`pcntl`).
- Queues and jobs still function normally without Horizon.

