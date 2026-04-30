# Feature Documentation: Bread Out & Repurposing Engine

**Date Implemented:** April 24, 2026
**Target Audience:** System Administrators, Developers

## Overview
The Bread Out & Repurposing Engine is a robust system designed to track unsold bread that is pulled out from branch inventory. It manages the lifecycle of this bread, allowing supervisors to legally and financially convert it into Toasted Bread (finished products), Bread Crumbs (raw materials), or log it as Spoilage.

## Architecture

### 1. Database Schema
- **`bread_outs` Table:** The primary staging table. Records the `branch_id`, `product_id`, `quantity`, and `status` ('pending', 'repurposed', 'spoiled').
- **`repurpose_logs` Table:** A polymorphic logging table. When bread is converted, this logs the action. Polymorphism (`outputable_type`, `outputable_id`) allows the output to be either a `Product` (for Toasted Bread) or a `RawMaterial` (for Bread Crumbs).
- **`spoilage_logs` Table:** Logs unrecoverable waste, linking back to the `bread_outs` ID and requiring a `reason`.

### 2. Backend Controllers (`GB-Backend`)
- **`BreadOutController.php`:** Handles the initial pull-out from the branch. Crucially, it **does not** immediately deduct the quantity from `BranchProduct.total_quantity`. This is an intentional design choice to align with the daily sales reporting process (preventing negative sales bugs).
- **`RepurposeController.php`:** Handles the supervisor's actions. It processes the conversion logic, updating either the destination branch's `BranchProduct` (for toasted bread) or the warehouse's `RawMaterial` (for crumbs).

### 3. Frontend Implementation (`GB-Frontend`)
- **Branch POS (`BreadPage.vue`):** 
  - Added the **"Repurpose Bread Out"** button next to the standard branch transfer button.
  - Opens `SendBreadForRepurposeButton.vue` to log pending bread outs.
- **Supervisor Dashboard (`BreadOutManagement.vue`):** 
  - A dedicated view (`/supervisor/repurposing`) for supervisors to see all 'pending' bread.
  - Contains a processing dialog to execute 'Toasted', 'Crumbs', or 'Spoilage' workflows.
- **State Management:** Uses a dedicated Pinia store (`bread-out.js`) to handle all API communications.

## Workflow Synchronization with Sales Reports
A critical design feature of this engine is its decoupled nature from the Daily Sales Report (`BreadSalesReport`).
1. When a Sales Lady logs a "Bread Out" for repurposing, the bread is sent to the Supervisor's dashboard. However, the stock is **not instantly deducted** from the live database (`total_quantity` remains untouched).
2. At the end of the day, the Sales Lady physically counts her remaining stock (which naturally excludes the pulled-out bread).
3. She manually enters the "Bread Out" number into her Sales Report to balance the financial math: `(Beginnings + New Production) - (Remaining + Bread Out) = Sold`.
4. Because the live database was *not* automatically deducted earlier, her manual entry ensures `Sold` is calculated accurately without causing "negative sales" conflicts!

## Reverting the Feature
If this feature needs to be disabled, it was designed to be 100% modular. It does not conflict with `AddedProductsController` or the branch-to-branch logic.
1. Remove the "Repurpose Bread Out" button from `BreadPage.vue`.
2. Remove the "Repurposing" tab from `SupervisorLayout.vue` and `routes.js`.
3. The old logic will continue functioning without any errors.
