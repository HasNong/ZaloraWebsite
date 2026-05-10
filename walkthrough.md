# Database Evolution Report

This document summarizes the strategic additions and enhancements made to the Zalora ecosystem's database schema to support professional logistics, trust moderation, and seller management.

## 1. Entirely New/Strategically Rebuilt Tables
These tables were either introduced or fundamentally redesigned to handle core marketplace logic.

### `return_request`
Handles the full "Return-to-Pickup" loop, coordinating between Customers, Admins, and Drivers.
- **Key Fields**: `Rtrn_Type`, `Rtrn_Status` (PENDING, APPROVED, PICKED_UP), `Rtrn_PicEvidence` (for trust).

### `review`
Power the trust ecosystem with a moderated feedback loop.
- **Key Fields**: `Rview_IsApproved` (Default 0 for Admin vet), `Rview_PicUrl` (Photo reviews), `OdItm_Id` (Verification).

### `shipment`
The backbone of the Driver module, tracking deliveries and Proof of Delivery.
- **Key Fields**: `Driv_Id`, `Ship_ProofImg` (POD Photo), `Ship_Status` (OUT_FOR_DELIVERY, DELIVERED).

---

## 2. Strategic Attribute Additions
Existing tables were enhanced with new attributes to support complex workflows.

### `product`
- **`Prod_IsActive`**: Expanded from simple 0/1 to include `2` (Rejected) for the Admin Governance Center.

### `seller`
- **`Sell_BusinessName`**: Added/Standardized to ensure professional catalog display instead of person-name splits.
- **`Sell_IsVerified`**: Status flag for admin-approved sellers.
- **`Sell_JoinedAt`**: Standardized timestamp for profile display.

### `driver`
- **`Driv_Balance`**: Real-time commission tracking for completed deliveries.
- **`Driv_Status`**: Dynamic state (ONLINE, OFFLINE, BUSY).
- **`Driv_IsActive`**: Administrative kill-switch for driver access.

### `orders`
- **`Order_Status`**: Expanded states to synchronize with the `shipment` status (e.g., SHIPPED, DELIVERED).

---

## 3. Data Integrity Enhancements
- **ID Generation**: Transitioned from simple auto-increment to manual `MAX(Id) + 1` patterns where necessary to maintain sequence integrity during large batch imports.
- **Case Sensitivity**: Standardized all table references to **lowercase** in SQL queries to ensure 100% cross-platform compatibility (Linux/Windows).
