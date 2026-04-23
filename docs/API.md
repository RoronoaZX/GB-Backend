# GB-Bakeshop API Documentation

This document provides a simple overview of the available API endpoints for the GB-Bakeshop Backend system.

## Base URL
All API endpoints are prefixed with `/api`.
Example: `https://your-domain.com/api/login`

## Authentication
Most endpoints require authentication using a Bearer Token.

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| POST | `/register` | Register a new user and employee |
| POST | `/login` | Authenticate user and receive token |
| GET | `/profile` | Get current authenticated user profile |
| GET | `/logout` | Revoke the current user's token |

---

## User Management
Endpoints for managing system users.

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| GET | `/users` | List all users |
| POST | `/users` | Create a new user |
| GET | `/users/{id}` | Get specific user details |
| PUT | `/users/{id}` | Update user information |
| DELETE | `/users/{id}` | Delete a user |
| POST | `/search-user` | Search users by name/email |
| POST | `/verify-password` | Verify admin password for sensitive tasks |

---

## Core Entities
CRUD operations for main business data.

| Resource | Base Endpoint | Description |
| :--- | :--- | :--- |
| **Raw Materials** | `/raw-materials` | Manage ingredients and supplies |
| **Warehouses** | `/warehouses` | Warehouse management |
| **Branches** | `/branches` | Branch store management |
| **Products** | `/products` | Bread and other product catalog |
| **Recipes** | `/recipes` | Product recipes and ingredients mapping |

---

## Branch Operations
Managing inventory and production at the branch level.

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| GET | `/branch-products` | List products in a branch |
| POST | `/branch-production-report` | Submit daily production report |
| GET | `/branch-raw-materials` | Check branch raw material stocks |
| POST | `/branch-premix` | Manage branch premix requests |
| GET | `/cake-report` | Manage cake display and sales |

---

## Employee & Payroll
Tools for managing staff, attendance, and compensation.

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| GET | `/employee` | List all employees |
| POST | `/dtr` | Daily Time Record (mark In/Out) |
| GET | `/payslip` | Generate/View employee payslips |
| POST | `/cash-advance` | Manage employee cash advances |
| GET | `/employee-allowance` | Manage employee allowances |
| GET | `/employee-benefit` | Manage SSS, PhilHealth, Pag-IBIG |

---

## Sales & Reports
Reporting endpoints for business intelligence.

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| GET | `/sales-report` | General sales analysis |
| GET | `/bread-production-report`| Detailed bread production metrics |
| GET | `/selecta-stocks-report` | Selecta product inventory tracking |
| GET | `/softdrinks-stocks-report`| Softdrinks inventory tracking |
| GET | `/delivery-receipt` | BIR-compliant delivery receipts |

---

## Inventory Logistics
Movement of goods between warehouse and branches.

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| POST | `/raw-materials-delivery`| Log raw material deliveries |
| POST | `/request-premix` | Request premix from warehouse |
| POST | `/sending-bread-to-branch`| Log bread transfers to branches |

---

## Note
- All POST/PUT requests should send data as `application/json`.
- Success responses usually return `200 OK` or `201 Created` with a JSON body.
- Error codes include `401 Unauthorized`, `422 Validation Error`, and `500 Server Error`.
