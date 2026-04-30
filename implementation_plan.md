# GB-Bakeshop: Strategic Business Analysis & Feature Roadmap (Revised)

Based on your feedback, we have completely restructured the roadmap. Features that are not immediate priorities (Loyalty Program, Automated POs) have been removed. 

We are making the **Wastage, Spoilage, and Repurposing Module** the absolute **First Priority**. This is a highly strategic move, as effectively managing "Bread Out" directly recovers revenue that would otherwise be lost.

---

## 🚀 PRIORITY 1: "Bread Out" Repurposing & Spoilage Tracking Engine

### The Business Challenge
Branches pull out unsold or near-expiry bread ("Bread Out"). Instead of throwing it away, the supervisor collects it. 
*   Some bread is converted into a new sellable product (**Toasted Bread**).
*   Specific types of bread (not salty/sweet) are ground into a raw material (**Bread Crumbs/Powder**) to be used in other recipes.

### Proposed Implementation: The Product Conversion Module
To track this accurately without messing up the financial metrics or inventory, we need a **Product Conversion Workflow**.

**Step 1: Branch Logs "Bread Out"**
*   **Action:** Branch cashiers/supervisors log the exact quantities of bread pulled out from the shelves.
*   **System Update:** This deducts from the branch's active sellable inventory and moves it into a holding state called `Branch Returns` or `Pending Repurpose Inventory`.

**Step 2: Supervisor Conversion (The "Upcycling" Engine)**
*   **Action:** The supervisor receives the consolidated "Bread Out" and logs what it will become in the new **Repurposing Module**.
*   **Workflow A (To Toasted Bread):** 
    *   *Input:* 50 pieces of regular bread.
    *   *Output:* 25 packs of "Toasted Bread" (Product Category).
    *   *System Update:* The system deducts the 50 pieces from the holding inventory, and adds 25 packs to the active sellable inventory. Cost of goods is carried over to maintain accurate profit margins.
*   **Workflow B (To Bread Powder/Crumbs):**
    *   *Input:* 30 pieces of plain bread.
    *   *Output:* 2 kg of "Bread Crumbs" (Raw Material).
    *   *System Update:* The system deducts the 30 pieces from the holding inventory and *increases* the Warehouse Raw Material inventory for Bread Crumbs. The original bread cost is converted into raw material cost.

**Step 3: Actual Spoilage Logging**
*   **Action:** For bread that is moldy or contaminated and *cannot* be repurposed, it is logged as "Actual Spoilage".
*   **System Update:** This permanently removes the item from inventory and logs the financial loss for reporting purposes.

---

## ⚡ PRIORITY 2: Core Enhancements (UI/UX & Resilience)

Once the Repurposing engine is built, we will focus on improving the daily operations of the branches.

### A. Mobile POS (Device) UI/UX Overhaul
Cashiers in high-volume environments need to operate fast without mental strain.
*   **Grid-First Layout:** Refactor the POS screen into a layout with large touch targets (minimum 48x48px) suitable for tablets.
*   **Quick-Add Workflow:** Implement one-tap additions to the cart and streamlined checkout flows to reduce queue times.

### B. Offline-First POS Resilience
If the branch internet goes down, sales cannot stop.
*   **Local Caching:** Utilize local SQLite (via Capacitor) or IndexedDB to cache the product catalog so the POS loads even without WiFi.
*   **Queueing System:** Queue sales locally during an outage and automatically sync them to the server when the connection is restored.

---

## 📊 PRIORITY 3: Advanced Financials & Gamification

### A. Advanced Financial Reporting (BIR Compliance)
*   **Automated VAT Splitting:** Ensure all sales reports automatically calculate VAT, Non-VAT, and Zero-rated sales components for instant tax compliance.
*   **Audit Trails:** Link history logs directly to specific sales records for forensic accounting.

### B. Employee Gamification (Performance Dashboard)
*   **Leaderboards:** Create a dashboard for branches (Highest Sales vs Lowest Actual Spoilage) and Bakers (Highest Efficiency based on baker reports). Attach automated incentives to these metrics in the Payroll module.

---

## User Review Required

> [!IMPORTANT]
> Please review the **"Bread Out" Repurposing & Spoilage Tracking Engine** (Priority 1) proposed above. 
> 
> Does this 3-step workflow (Branch Pull-out -> Holding Inventory -> Conversion to New Product/Raw Material) accurately reflect how your real-world operations work? 

## Open Questions

1. When "Bread Out" is converted to Toasted Bread, does the conversion happen at the Branch itself, or does the Supervisor take the bread back to a central Commissary/Warehouse to bake it, and then redistribute it?
2. Are you ready for me to begin planning the database tables and backend logic for this Repurposing Module?
