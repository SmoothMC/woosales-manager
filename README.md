# WooSales Manager

Sales agent and commission management for WooCommerce.

WooSales Manager allows assigning sales commissions globally or per order, tracking payouts, and viewing agent performance directly inside WordPress.  
The plugin integrates seamlessly into WooCommerce and does not require referral links, coupons, or UTM tracking.

---

## Features

- **Global commissions** applied to every WooCommerce order
- **Per-order agent assignment** (override global rules individually)
- **Multiple agents per order** (optional)
- **Commission calculation based on net or gross total**
- **Commission lifecycle**  
  - Pending  
  - Approved (completed orders)  
  - Rejected (refunded or cancelled)
- **Payout tracking** (manual confirmation or CSV export)
- **Agent dashboard** with filters
- **Minimal UI**, no bulky extra admin screens
- **JSON-based self-update system** (no dependency on WordPress.org)

---

## Requirements

| Component | Minimum |
|---|---|
| WordPress | 6.0+ |
| WooCommerce | 7.0+ |
| PHP | 8.0+ |

---

## Installation

1. Download the latest release ZIP.
2. Upload to your WordPress `wp-admin → Plugins → Add New → Upload Plugin`
3. Activate the plugin.
4. Ensure WooCommerce is active.

---

## Update System

WooSales Manager uses a JSON-based updater compatible with custom CDN deployments.

The update metadata is retrieved from: https://cdn.zzzooo.studio

---

| Feature                                        | Status         |
| ---------------------------------------------- | -------------- |
| Order-level agent assignment UI                | ✅ Done         |
| Multiple agents per order                      | ✅ Configurable |
| Agent dashboard                                | 🔧 In progress |
| Payout batch export                            | 🟡 Planned     |
| Analytics (conversion view, totals per period) | 🟡 Planned     |


---

### Author

Created and maintained by Mikkka | zzzooo Studio
https://zzzooo.studio/