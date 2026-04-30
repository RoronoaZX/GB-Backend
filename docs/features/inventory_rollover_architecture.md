# Feature Documentation: Inventory Rollover Architecture

**Date Documented:** April 24, 2026
**Target Audience:** System Administrators, Developers

## The Original Vulnerability
Historically, the system triggered the live inventory rollover (resetting `new_production` to 0 and hard-resetting `total_quantity`) **when the Administrator clicked "Confirm"** on the daily Sales Report. 

Because of the specific shift timeline (PM Shift ending at 4:00 AM, and AM Bakers delivering bread at 1:00 PM), this created a race condition. If an Administrator logged in to confirm reports *after* the new AM Baker delivered bread, the system would reset the database based on the 4:00 AM count, effectively **deleting the new morning deliveries**.

## The Safe Handover Architecture
To solve this, the Inventory Rollover has been separated from the Handover Verification.

### 1. Shift the Rollover Trigger to "Submit" (End of Shift)
The database rollover now happens at the exact moment the Cashier finishes her shift and clicks **Submit**.
- `beginnings = remaining` (Her reported count)
- `new_production = 0` (Wiping the slate clean for the next baker)
- `total_quantity = remaining`

Because the PM Cashier submits at 4:00 AM, the database is safely prepared long before the AM Baker arrives at 1:00 PM. The 1:00 PM delivery is safely recorded as `new_production`.

### 2. Safe "Confirm" Handover
When the incoming Cashier (or Administrator) logs in to "Confirm" the previous shift's report, it acts as a physical verification.
- Clicking "Confirm" simply marks the report as `status = confirmed`.
- It **does not** overwrite the database. This protects the 1:00 PM delivery.

### 3. Safe "Decline" Handover (Discrepancy Correction)
If the incoming Cashier physically counts the bread and finds a discrepancy (e.g., the previous cashier reported 90, but there are only 88), she clicks "Decline" and inputs her count (88).
- Instead of hard-resetting the database to 88 (which would destroy the 1:00 PM delivery), the system intelligently subtracts the discrepancy.
- Discrepancy = 90 - 88 = 2 missing breads.
- `total_quantity -= 2`.
- `beginnings = 88`.
- The live inventory remains completely accurate!

## Chronological Workflow Example (April 1 & April 2)
To visualize exactly how this Safe Handover works, here is the timeline:

**April 1 (AM Shift)**
1. `1:00 PM` - AM Baker delivers 50 breads. Live database: `new_production = 50`.
2. `8:00 PM` - AM Cashier submits AM Report. The system instantly rolls over her shift. It sets `beginnings = remaining` and `new_production = 0`. 

**April 1 (PM Shift)**
3. `1:00 AM (April 2)` - PM Baker delivers 50 breads. Live database: `new_production = 50`.
4. `4:00 AM (April 2)` - PM Cashier submits PM Report. The system instantly rolls over her shift. It sets `beginnings = remaining` and `new_production = 0`. 

**April 2 (New AM Shift Begins)**
5. `6:00 AM (April 2)` - New AM Cashier logs in and clicks **"Confirm"** to accept the handover. The system just marks it confirmed. It **does not** touch the database.
6. `8:00 AM (April 2)` - Admin logs in and views the April 1 Sales Report (The system automatically grouped the 8 PM and 4 AM reports together into the "April 1 Report"). Admin clicks "Confirm" on everything. The system **does not** touch the live database.
7. `1:00 PM (April 2)` - AM Baker delivers 50 breads. Live database: `new_production = 50`. The cycle continues perfectly!
