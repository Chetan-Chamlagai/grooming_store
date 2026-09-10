Oggentleme Admin Dashboard
A secure, modular, PHP-based administrative backend designed for the Oggentleme e-commerce platform to manage products, customer inquiries, order fulfillment, and user authentication.

Tech Stack
Backend: PHP 8+

Database: MySQL (InnoDB with foreign key constraints)

Frontend: HTML, CSS, JavaScript

Key Features
Secure Authentication: Self-contained admin authentication portal with session fixation protection and secure logout confirmation.

Order Fulfillment Management: Review customer orders, update order and payment statuses, and inspect ordered line items, prices, and totals.

Client Inquiries & Consultations: Triage customer contact forms, general inquiries, and bespoke consultation requests with real-time live search and subject filtering.

Product Catalog: Manage inventory across restricted categories (wallets, fragrances, watches).

[ Customer Storefront ]          [ Administrative Backend ]
       │                                     │
       │ (1. Place Orders / Inquiries)       │ (3. Fulfillment / Inventory / Triage)
       ▼                                     ▼
 ┌────────────────────────────────────────────────────────┐
 │                     PHP Application Layer              │
 │       (Data Validation, Auth Guard, SQL Prepared)      │
 └───────────────────────────┬────────────────────────────┘
                             │
                             │ (2. CRUD Operations)
                             ▼
 ┌────────────────────────────────────────────────────────┐
 │                MySQL Relational Database               │
 │                   (InnoDB Foreign Keys)                │
 └────────────────────────────────────────────────────────┘