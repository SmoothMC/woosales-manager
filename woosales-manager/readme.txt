=== WooSales Manager ===
Contributors: SmoothMC
Tags: woocommerce, commission, sales, agents
Requires at least: 6.0
Tested up to: 6.8.3
Requires PHP: 8.0
Stable tag: 1.0.1
License: GPLv2 or later

Global sales commissions for WooCommerce, JSON-based self-updater.

== Description ==
* Assign global commission to multiple agents.
* Completed-only calculation, cancelled/refunded -> rejected.
* Net base (default) or gross.
* Dashboard, filters, payouts + CSV.
* Self-updater via configurable update.json URL.

== Installation ==
Upload ZIP, activate, then WooCommerce → Sales.
Set **Update JSON URL** under Settings.

== Changelog ==
## [1.5.0] – 2026-01-15
### Added
- Payout reporting with selectable billing period (month or quarter)
- Agent filter and status filter (approved / paid / all) in payout view
- CSV export for payouts with period and status filtering
- Print-friendly PDF payout report (save as PDF via browser)
- `paid_at` timestamp column to track actual payout date
- Admin list column showing payout date when commission is marked as paid
- Summary feedback in payout view showing:
  - number of filtered commissions
  - selected period, agent and status
  - total payout amount

### Changed
- Payout actions now operate on billing period (`commission_month`) instead of raw timestamps
- PDF export sorting:
  - by payout date when exporting paid commissions
  - by order date otherwise
- CSV and PDF exports only display “Paid at” column when exporting paid commissions
- Commission status updates now automatically maintain `paid_at` timestamp

### Fixed
- Inconsistent payout totals when filtering by month
- Export actions previously ignoring selected billing period
- Missing payout date handling when changing commission status manually
- Sorting inconsistencies in payout reports

## [1.4.0] – 2025-12-02
### Added
- Billing period support via `commission_month` (YYYY-MM) based on order completion date
- Automatic migration for existing commissions to set correct billing months
- Admin dashboard month selector for commission reporting
- Frontend "My Sales" dashboard period filtering based on billing month

### Changed
- Commission reporting shifted from technical timestamps (`created_at`)
  to business billing period (`commission_month`)
- Frontend period navigation now uses billing periods instead of insert dates

### Fixed
- Incorrect frontend month grouping after commission recalculations
- Missing SELECT of `commission_month` in agent commission queries
- Safety fallback added for date handling when billing month is missing

## [1.3.2] – 2025-11-30
### Fixed
- Orders with already assigned agents now correctly sync checkbox state
- Pending commissions are now generated reliably on manual assignment
- Reassignment now removes outdated commissions before recreating new ones
- Correct rate handling in admin edit screen (percent ↔ decimal conversion)

### Added
- Manual agent assignment workflow for existing orders
- Commission recalculation stability on reassignment & status changes

## [1.3.1] – 2025-11-30
### Fixed
- Edit rate as percentage (UI fix)
- Remove manual amount editing
### Added
- Add live JS preview for recalculated commission
- Recalculate commission amount on save

## [1.3.0] – 2025-11-30
### Added
- Admin single-commission edit screen
- Manual reassignment of commissions to agents
- Status, rate and amount can be corrected post-creation
- Secure save handling via admin-post endpoint

## [1.2.1] – 2025-11-12
### Fixed
- Commissions are now correctly created with status `pending` when order moves to **processing**
- Commission auto-approval is triggered only once order is **completed**
- Pending commissions now visible in admin dashboard and agent frontend
- Status normalization added to handle WooCommerce variants (`pending_payment`, etc.)
- Improved reject logic for refunded/canceled orders

### Notes
This patch resolves the issue where pending commissions were never recorded, causing them not to appear in reporting or filtering.

## [1.2.0] – 2025-11-12
### Added
- New **period filter** (All, This Month, This Quarter, This Year) in *My Account → My Sales*
- **Dynamic month/quarter/year navigation** via Previous/Next buttons
- **Multi-select status buttons** for Approved, Pending, Rejected, Paid
- Default view now shows **current month** and **approved sales only**
- Added total commission calculation based on current filters
- Improved visual design with rounded buttons and responsive layout

### Fixed
- Prevented navigation into future periods
- Fixed total calculation including rejected/paid commissions
- Minor layout and accessibility adjustments

### Notes
This update enhances the frontend dashboard for agents and improves usability and clarity in commission tracking.

## [1.1.1] – 2025-11-10
### Added
- Introduced **agent-to-WordPress-user linking** to allow agents to view their sales in the My Account area.
- Added **frontend “My Sales” endpoint** under WooCommerce → My Account for connected agents.
- Implemented basic **commission overview table** with order reference, net amount, commission and status.

### Improved
- Enhanced backend “Agents” section with user association support.
- Updated internal UI class to register custom My Account endpoint dynamically.
- Refined permission handling and nonce verification in agent management.

### Fixed
- Resolved missing class autoload issue for `Woo_Sales_Manager_Installer`.
- Fixed plugin initialization order and improved safety checks for WooCommerce availability.

### Notes
This update established the foundation for the agent-facing dashboard and improved the stability of the core plugin structure.

## [1.0.2]
* Added self-updater compatible with JSON feed (like wp-notification-settings).
## [1.0.1]
* Initial release
