# Architecture Recommendation: Event-Driven Live Deductions

**Date:** April 24, 2026
**Topic:** Transitioning from Manual End-of-Day Accounting to Automated Event-Driven Auditing

## Current Architecture: Manual Accounting
Currently, the GB-Bakeshop system relies on **Manual End-of-Day Accounting** for inventory tracking related to sales and transfers.
- When bread is transferred or pulled out during the day, the live database (`BranchProduct.total_quantity`) is **not** immediately deducted.
- Instead, the system waits for the Sales Lady to physically count her remaining stock at the end of the shift and manually input her "Bread Out" and "Remaining" numbers into the Sales Report.
- The system then relies on the mathematical formula: `Sold = (Beginnings + New Production) - (Remaining + Bread Out)` to balance the books.

### Vulnerabilities of the Current Architecture:
1. **The Transparency Gap:** Between the moment bread leaves the branch and the moment the Sales Report is submitted hours later, the live inventory database is inaccurate.
2. **Human Error / Data Mismatch:** The Sales Lady could accidentally type "8" for her Bread Out when she actually sent "10". This creates a discrepancy where the Supervisor's logs show 10 received, but the branch's financial audit shows 8 sent out.

---

## The "Gold Standard" Approach
For enterprise-level transparency and strict auditing, the system should move to **"Strict Event-Driven Live Deductions with Automated Reporting."**

### Proposed Changes:
1. **Instant Deduction:** Whenever a physical action occurs (e.g., clicking "Repurpose Bread Out" or "Send to Branch"), the system **MUST immediately deduct** the quantity from the live database (`total_quantity`). The database must always perfectly reflect physical reality in real-time.
2. **Automated Sales Reporting (No Double Entry):** 
   - The "Bread Out" field on the end-of-day Sales Report becomes **READ-ONLY**.
   - The backend automatically calculates the total "Bread Out" for the day by querying the immutable, timestamped logs (e.g., `bread_outs` table, `added_products` table).
   - The Sales Lady only inputs her physical `Remaining` count.
   - The system automatically calculates: `Sold = Beginnings - (Remaining + Auto_Calculated_Bread_Out)`.

### Business Benefits:
- **Impossible to Fake:** Every single "Bread Out" on the financial report is strictly backed by an immutable log that the Supervisor has to approve.
- **100% Real-Time Accuracy:** Your live inventory is never inaccurate, enabling real-time supply chain monitoring.
- **Cashier Convenience:** The cashier does less manual math at the end of the day, reducing typos, stress, and cash-drawer mismatches.
