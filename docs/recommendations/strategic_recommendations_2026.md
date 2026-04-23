# Strategic Business Recommendations & Implementation Plan (2026)

This document contains the analytical breakdown and implementation tasks for enhancing the **GB-Bakeshop** system.

---

## 1. Predictive Stocking Engine
**Analysis:**
The current system tracks ingredient usage per batch through `InitialBakerreports` and `RecipeCost`. By analyzing the "Average Daily Consumption" (ADC) of raw materials against the current `BranchRmStocks`, we can predict the exact day a branch will run out of a specific item.

**Implementation Tasks:**
- [ ] **Backend Service**: Create a new controller `AnalyticsController` with an endpoint `get-stock-predictions`.
- [ ] **Data Logic**: Implement a rolling 14-day average consumption calculation.
- [ ] **Frontend Widget**: Develop a "Smart Reorder" dashboard component using Pinia to fetch and display ingredients nearing depletion.
- [ ] **Notification**: Integrate an alert system (Quasar Notify) for when stock levels fall below the 3-day prediction threshold.

---

## 2. Mobile POS (Device) UI/UX Optimization
**Analysis:**
In high-volume environments, reducing the "mental load" of the branch staff is critical. The current POS interface should be optimized for 7-10 inch tablets, focusing on speed and touch accuracy.

**Implementation Tasks:**
- [ ] **Grid-First Layout**: Refactor product selection into a large-card grid with high-quality icons or color codes.
- [ ] **Touch Optimization**: Increase button sizes to a minimum of 48x48px (standard touch target size).
- [ ] **Streamlined Flow**: Implement a "Quick-Add" feature where tapping a product automatically adds 1 unit to the cart, with a single-tap checkout confirmation.
- [ ] **Offline Resilience**: Add local storage caching for product lists to ensure the POS remains responsive during network fluctuations.

---

## 3. Advanced Financial Reporting (BIR Compliance)
**Analysis:**
While the system has BIR reports, enhancing the "Audit Trail" and "Automatic Tax Calculation" will provide more legal security for the business owner.

**Implementation Tasks:**
- [ ] **Automated VAT Splitting**: Ensure the sales report automatically calculates VAT and Non-VAT components per transaction.
- [ ] **Audit Logs**: Link every `HistoryLog` entry directly to the specific sales record for forensic accounting.

---

## Future Roadmap (Next 6-12 Months)
1. **Customer Loyalty Program**: Integrate a simple QR-code based point system.
2. **Employee Performance Dashboard**: Gamify production targets for bakers based on the `InitialBakerreports` efficiency.
3. **Automated Supplier Integration**: Send automated PDF emails to suppliers when stock reaches the "Reorder Point".
