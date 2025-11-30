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
