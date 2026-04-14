<?php
declare(strict_types=1);

session_start();

date_default_timezone_set("Asia/Ho_Chi_Minh");

function h(string $value): string
{
	return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

$pages = [
	"dashboard" => ["title" => "Dashboard", "icon" => "ri-dashboard-3-line"],
	"tables" => ["title" => "Table Management", "icon" => "ri-layout-grid-line"],
	"orders" => ["title" => "Orders and Billing", "icon" => "ri-receipt-line"],
	"customers" => ["title" => "Customers", "icon" => "ri-group-line"],
	"inventory" => ["title" => "Inventory", "icon" => "ri-archive-line"],
	"staff" => ["title" => "Staff Management", "icon" => "ri-team-line"],
	"reports" => ["title" => "Reports and Analytics", "icon" => "ri-line-chart-line"],
	"settings" => ["title" => "Settings", "icon" => "ri-settings-3-line"],
];

$currentPage = $_GET["page"] ?? "dashboard";
if (!array_key_exists($currentPage, $pages)) {
	$currentPage = "dashboard";
}

$pageTitle = $pages[$currentPage]["title"];
$activeUser = isset($_SESSION["user_name"]) ? (string) $_SESSION["user_name"] : "Club Admin";
$activeRole = isset($_SESSION["user_role"]) ? (string) $_SESSION["user_role"] : "Manager";

$kpiCards = [
	["label" => "Active Tables", "value" => "11/16", "change" => "+2 from yesterday", "tone" => "good", "icon" => "ri-billiards-line"],
	["label" => "Today Revenue", "value" => "6,480,000 VND", "change" => "+13% week-on-week", "tone" => "good", "icon" => "ri-money-dollar-circle-line"],
	["label" => "Open Orders", "value" => "24", "change" => "4 waiting kitchen", "tone" => "warn", "icon" => "ri-shopping-bag-3-line"],
	["label" => "New Customers", "value" => "9", "change" => "2 VIP registered", "tone" => "good", "icon" => "ri-user-add-line"],
];

$revenueSeries = [
	["day" => "Mon", "value" => 4.1],
	["day" => "Tue", "value" => 4.6],
	["day" => "Wed", "value" => 5.0],
	["day" => "Thu", "value" => 5.8],
	["day" => "Fri", "value" => 6.3],
	["day" => "Sat", "value" => 7.4],
	["day" => "Sun", "value" => 6.9],
];

$tableUsage = [
	["name" => "A1", "percent" => 88],
	["name" => "A2", "percent" => 72],
	["name" => "A3", "percent" => 64],
	["name" => "B1", "percent" => 93],
	["name" => "B2", "percent" => 51],
];

$recentActivities = [
	["icon" => "ri-billiards-line", "title" => "Table B1 session reached 2h 12m", "meta" => "Operator: Minh", "state" => "active"],
	["icon" => "ri-shopping-cart-2-line", "title" => "Order #HD2205 has been paid", "meta" => "Cashier: Nhi", "state" => "paid"],
	["icon" => "ri-user-heart-line", "title" => "Customer Tran Kien upgraded to VIP", "meta" => "Membership desk", "state" => "new"],
	["icon" => "ri-alert-line", "title" => "Low stock: Pepsi can", "meta" => "Inventory alert", "state" => "warn"],
];

$tables = [
	["name" => "Table A1", "type" => "Standard", "status" => "playing", "player" => "Nguyen An", "time" => "01:42:20", "rate" => "95,000 VND/h"],
	["name" => "Table A2", "type" => "VIP", "status" => "reserved", "player" => "Group: Team Falcon", "time" => "Start at 20:00", "rate" => "130,000 VND/h"],
	["name" => "Table A3", "type" => "Standard", "status" => "available", "player" => "Ready", "time" => "No active session", "rate" => "95,000 VND/h"],
	["name" => "Table B1", "type" => "Tournament", "status" => "playing", "player" => "Le Hoang", "time" => "00:58:02", "rate" => "150,000 VND/h"],
	["name" => "Table B2", "type" => "Standard", "status" => "available", "player" => "Ready", "time" => "No active session", "rate" => "95,000 VND/h"],
	["name" => "Table B3", "type" => "VIP", "status" => "playing", "player" => "Pham Long", "time" => "02:18:47", "rate" => "130,000 VND/h"],
];

$openOrder = [
	["item" => "Table time", "qty" => "1", "price" => "285,000"],
	["item" => "Fried rice", "qty" => "2", "price" => "130,000"],
	["item" => "Coca Cola", "qty" => "4", "price" => "72,000"],
	["item" => "Mineral water", "qty" => "2", "price" => "24,000"],
];

$menuItems = [
	["name" => "Pho bo", "category" => "Food", "price" => "65,000", "icon" => "ri-bowl-line"],
	["name" => "Tra dao", "category" => "Drinks", "price" => "22,000", "icon" => "ri-cup-line"],
	["name" => "French fries", "category" => "Snacks", "price" => "40,000", "icon" => "ri-restaurant-line"],
	["name" => "Lemon soda", "category" => "Drinks", "price" => "28,000", "icon" => "ri-goblet-line"],
	["name" => "Noodle stir fry", "category" => "Food", "price" => "58,000", "icon" => "ri-knife-blood-line"],
	["name" => "Chicken popcorn", "category" => "Snacks", "price" => "49,000", "icon" => "ri-cake-2-line"],
];

$customers = [
	["name" => "Tran Kien", "phone" => "0901 110 778", "visits" => 34, "spent" => "22,450,000", "tier" => "VIP"],
	["name" => "Mai Linh", "phone" => "0912 314 888", "visits" => 18, "spent" => "9,780,000", "tier" => "Regular"],
	["name" => "Doan Bao", "phone" => "0988 402 223", "visits" => 7, "spent" => "3,960,000", "tier" => "New"],
	["name" => "Ngoc Anh", "phone" => "0939 522 016", "visits" => 26, "spent" => "14,200,000", "tier" => "VIP"],
];

$inventory = [
	["name" => "Coca Cola can", "category" => "Drinks", "qty" => 8, "min" => 12, "supplier" => "NCC Sai Gon"],
	["name" => "Pepsi can", "category" => "Drinks", "qty" => 5, "min" => 10, "supplier" => "NCC Sai Gon"],
	["name" => "Billiard chalk", "category" => "Accessories", "qty" => 42, "min" => 20, "supplier" => "Cue Pro"],
	["name" => "Cue tip", "category" => "Equipment", "qty" => 18, "min" => 15, "supplier" => "Cue Pro"],
	["name" => "Instant noodle", "category" => "Food", "qty" => 32, "min" => 20, "supplier" => "Vinfood"],
];

$staff = [
	["name" => "Nguyen Minh", "role" => "Manager", "shift" => "Morning", "status" => "On duty", "hours" => 36],
	["name" => "Pham Nhi", "role" => "Cashier", "shift" => "Evening", "status" => "On duty", "hours" => 32],
	["name" => "Le Quan", "role" => "Service", "shift" => "Evening", "status" => "On leave", "hours" => 19],
	["name" => "Tran Vu", "role" => "Floor", "shift" => "Night", "status" => "Off duty", "hours" => 28],
];

$reportMonths = ["Jan", "Feb", "Mar", "Apr", "May", "Jun"];
$reportRevenue = [58, 61, 63, 72, 75, 84];
$topServices = [
	["name" => "Table Time", "value" => 48],
	["name" => "Drink Combo", "value" => 31],
	["name" => "Food Combo", "value" => 23],
	["name" => "VIP Upgrade", "value" => 14],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo h($pageTitle); ?> | DOAN_WEB</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css">
	<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
	<div class="bg-aura bg-aura-one"></div>
	<div class="bg-aura bg-aura-two"></div>

	<div class="app-shell" id="appShell">
		<?php include __DIR__ . "/includes/sidebar.php"; ?>

		<div class="app-main">
			<?php include __DIR__ . "/includes/header.php"; ?>

			<main class="page-content">
				<?php if ($currentPage === "dashboard"): ?>
					<section class="metric-grid stagger-fade">
						<?php foreach ($kpiCards as $card): ?>
							<article class="metric-card">
								<div class="metric-head">
									<p><?php echo h($card["label"]); ?></p>
									<span class="metric-icon"><i class="<?php echo h($card["icon"]); ?>"></i></span>
								</div>
								<h3><?php echo h($card["value"]); ?></h3>
								<div class="chip chip-<?php echo h($card["tone"]); ?>"><?php echo h($card["change"]); ?></div>
							</article>
						<?php endforeach; ?>
					</section>

					<section class="dashboard-grid stagger-fade">
						<article class="panel panel-large">
							<div class="panel-header">
								<div>
									<h3>Revenue Trend (M VND)</h3>
									<p>Daily performance for current week</p>
								</div>
								<button class="ghost-btn">Export</button>
							</div>
							<div class="bar-chart">
								<?php
								$maxRevenue = max(array_column($revenueSeries, "value"));
								foreach ($revenueSeries as $point):
									$height = (int) round(($point["value"] / $maxRevenue) * 100);
								?>
									<div class="bar-item">
										<div class="bar-fill" style="height: <?php echo $height; ?>%;"></div>
										<span><?php echo h($point["day"]); ?></span>
									</div>
								<?php endforeach; ?>
							</div>
						</article>

						<article class="panel">
							<div class="panel-header">
								<div>
									<h3>Table Usage</h3>
									<p>Live utilization ratio</p>
								</div>
							</div>
							<div class="usage-list">
								<?php foreach ($tableUsage as $usage): ?>
									<div class="usage-item">
										<div class="usage-meta">
											<span><?php echo h($usage["name"]); ?></span>
											<strong><?php echo h((string) $usage["percent"]); ?>%</strong>
										</div>
										<div class="usage-track">
											<div class="usage-progress" style="width: <?php echo (int) $usage["percent"]; ?>%;"></div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</article>

						<article class="panel panel-wide">
							<div class="panel-header">
								<div>
									<h3>Recent Activities</h3>
									<p>Latest actions from floor and cashier</p>
								</div>
							</div>
							<div class="activity-list">
								<?php foreach ($recentActivities as $activity): ?>
									<div class="activity-item">
										<span class="activity-icon"><i class="<?php echo h($activity["icon"]); ?>"></i></span>
										<div class="activity-body">
											<p><?php echo h($activity["title"]); ?></p>
											<small><?php echo h($activity["meta"]); ?></small>
										</div>
										<span class="chip chip-<?php echo h($activity["state"]); ?>"><?php echo h(strtoupper($activity["state"])); ?></span>
									</div>
								<?php endforeach; ?>
							</div>
						</article>
					</section>
				<?php elseif ($currentPage === "tables"): ?>
					<section class="panel stagger-fade">
						<div class="panel-header">
							<div>
								<h3>Live Table Board</h3>
								<p>Filter by status and manage active sessions</p>
							</div>
							<div class="filter-pills" id="tableFilters">
								<button class="pill is-active" data-filter="all">All</button>
								<button class="pill" data-filter="playing">Playing</button>
								<button class="pill" data-filter="reserved">Reserved</button>
								<button class="pill" data-filter="available">Available</button>
							</div>
						</div>

						<div class="table-card-grid" id="tableCardGrid">
							<?php foreach ($tables as $table): ?>
								<article class="table-card" data-status="<?php echo h($table["status"]); ?>">
									<div class="table-top">
										<h4><?php echo h($table["name"]); ?></h4>
										<span class="chip chip-<?php echo h($table["status"]); ?>"><?php echo h(strtoupper($table["status"])); ?></span>
									</div>
									<p class="table-type"><?php echo h($table["type"]); ?> • <?php echo h($table["rate"]); ?></p>
									<div class="table-center"><i class="ri-layout-grid-line"></i></div>
									<div class="table-bottom">
										<strong><?php echo h($table["player"]); ?></strong>
										<small><?php echo h($table["time"]); ?></small>
									</div>
									<button class="ghost-btn full">Manage</button>
								</article>
							<?php endforeach; ?>
						</div>
					</section>
				<?php elseif ($currentPage === "orders"): ?>
					<section class="split-grid stagger-fade">
						<article class="panel">
							<div class="panel-header">
								<div>
									<h3>Current Bill - Table B3</h3>
									<p>Player: Pham Long • Session 02:18:47</p>
								</div>
							</div>
							<table class="data-table">
								<thead>
									<tr>
										<th>Item</th>
										<th>Qty</th>
										<th>Amount (VND)</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($openOrder as $line): ?>
										<tr>
											<td><?php echo h($line["item"]); ?></td>
											<td><?php echo h($line["qty"]); ?></td>
											<td><?php echo h($line["price"]); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
							<div class="bill-total">
								<div><span>Subtotal</span><strong>511,000</strong></div>
								<div><span>Tax 8%</span><strong>40,880</strong></div>
								<div class="bill-grand"><span>Total</span><strong>551,880</strong></div>
							</div>
							<div class="action-row">
								<button class="solid-btn">Checkout and Print</button>
								<button class="ghost-btn">Save Draft</button>
							</div>
						</article>

						<article class="panel">
							<div class="panel-header">
								<div>
									<h3>Quick Menu</h3>
									<p>Add food and drinks to active order</p>
								</div>
							</div>
							<div class="menu-grid">
								<?php foreach ($menuItems as $menu): ?>
									<button class="menu-card">
										<span class="menu-icon"><i class="<?php echo h($menu["icon"]); ?>"></i></span>
										<strong><?php echo h($menu["name"]); ?></strong>
										<small><?php echo h($menu["category"]); ?> • <?php echo h($menu["price"]); ?> VND</small>
									</button>
								<?php endforeach; ?>
							</div>
						</article>
					</section>
				<?php elseif ($currentPage === "customers"): ?>
					<section class="panel stagger-fade">
						<div class="panel-header">
							<div>
								<h3>Customer Directory</h3>
								<p>Track loyalty and spending behavior</p>
							</div>
							<button class="solid-btn thin"><i class="ri-user-add-line"></i> Add customer</button>
						</div>
						<table class="data-table">
							<thead>
								<tr>
									<th>Customer</th>
									<th>Phone</th>
									<th>Visits</th>
									<th>Total Spent (VND)</th>
									<th>Tier</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($customers as $customer): ?>
									<?php
									$tierClass = "new";
									if ($customer["tier"] === "VIP") {
										$tierClass = "warn";
									} elseif ($customer["tier"] === "Regular") {
										$tierClass = "paid";
									}
									?>
									<tr>
										<td><?php echo h($customer["name"]); ?></td>
										<td><?php echo h($customer["phone"]); ?></td>
										<td><?php echo h((string) $customer["visits"]); ?></td>
										<td><?php echo h($customer["spent"]); ?></td>
										<td><span class="chip chip-<?php echo h($tierClass); ?>"><?php echo h($customer["tier"]); ?></span></td>
										<td><button class="ghost-btn thin">History</button></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</section>
				<?php elseif ($currentPage === "inventory"): ?>
					<section class="panel stagger-fade">
						<div class="panel-header">
							<div>
								<h3>Inventory Snapshot</h3>
								<p>Stock alerts and supplier monitoring</p>
							</div>
							<button class="solid-btn thin"><i class="ri-add-line"></i> New item</button>
						</div>
						<table class="data-table">
							<thead>
								<tr>
									<th>Item</th>
									<th>Category</th>
									<th>Quantity</th>
									<th>Min</th>
									<th>Supplier</th>
									<th>Status</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($inventory as $item): ?>
									<?php $stockState = $item["qty"] < $item["min"] ? "warn" : "good"; ?>
									<tr>
										<td><?php echo h($item["name"]); ?></td>
										<td><?php echo h($item["category"]); ?></td>
										<td><?php echo h((string) $item["qty"]); ?></td>
										<td><?php echo h((string) $item["min"]); ?></td>
										<td><?php echo h($item["supplier"]); ?></td>
										<td><span class="chip chip-<?php echo h($stockState); ?>"><?php echo $stockState === "warn" ? "LOW" : "OK"; ?></span></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</section>
				<?php elseif ($currentPage === "staff"): ?>
					<section class="panel stagger-fade">
						<div class="panel-header">
							<div>
								<h3>Shift Control</h3>
								<p>Team assignments and capacity</p>
							</div>
							<button class="solid-btn thin"><i class="ri-team-line"></i> Add member</button>
						</div>
						<div class="staff-grid">
							<?php foreach ($staff as $member): ?>
								<?php
								$statusClass = "paid";
								if ($member["status"] === "On leave") {
									$statusClass = "warn";
								} elseif ($member["status"] === "Off duty") {
									$statusClass = "mute";
								}
								?>
								<article class="staff-card">
									<div class="avatar-circle"><?php echo h(strtoupper(substr($member["name"], 0, 1))); ?></div>
									<div>
										<h4><?php echo h($member["name"]); ?></h4>
										<p><?php echo h($member["role"]); ?> • <?php echo h($member["shift"]); ?> shift</p>
									</div>
									<div class="staff-foot">
										<span class="chip chip-<?php echo h($statusClass); ?>"><?php echo h($member["status"]); ?></span>
										<small><?php echo h((string) $member["hours"]); ?>h this week</small>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					</section>
				<?php elseif ($currentPage === "reports"): ?>
					<section class="split-grid stagger-fade">
						<article class="panel">
							<div class="panel-header">
								<div>
									<h3>Monthly Revenue (M VND)</h3>
									<p>Half-year trend</p>
								</div>
								<button class="ghost-btn thin">Last 6 months</button>
							</div>
							<div class="line-bars">
								<?php
								$maxReportRevenue = max($reportRevenue);
								foreach ($reportRevenue as $index => $value):
									$height = (int) round(($value / $maxReportRevenue) * 100);
								?>
									<div class="line-bar-item">
										<div class="line-bar" style="height: <?php echo $height; ?>%;"></div>
										<span><?php echo h($reportMonths[$index]); ?></span>
									</div>
								<?php endforeach; ?>
							</div>
						</article>
						<article class="panel">
							<div class="panel-header">
								<div>
									<h3>Top Services</h3>
									<p>Share by order volume</p>
								</div>
							</div>
							<div class="usage-list">
								<?php
								$maxService = max(array_column($topServices, "value"));
								foreach ($topServices as $service):
									$width = (int) round(($service["value"] / $maxService) * 100);
								?>
									<div class="usage-item">
										<div class="usage-meta">
											<span><?php echo h($service["name"]); ?></span>
											<strong><?php echo h((string) $service["value"]); ?>%</strong>
										</div>
										<div class="usage-track">
											<div class="usage-progress amber" style="width: <?php echo $width; ?>%;"></div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</article>
					</section>
				<?php elseif ($currentPage === "settings"): ?>
					<section class="settings-grid stagger-fade">
						<article class="panel">
							<div class="panel-header">
								<div>
									<h3>Club Profile</h3>
									<p>Core business information</p>
								</div>
							</div>
							<div class="settings-form">
								<label>Club name<input type="text" value="DOAN_WEB Billiards" readonly></label>
								<label>Address<input type="text" value="123 Cue Street, Ho Chi Minh" readonly></label>
								<label>Phone<input type="text" value="0909 888 777" readonly></label>
								<label>Email<input type="text" value="contact@doanweb.local" readonly></label>
							</div>
						</article>
						<article class="panel">
							<div class="panel-header">
								<div>
									<h3>System Behavior</h3>
									<p>Quick operational toggles</p>
								</div>
							</div>
							<div class="toggle-list">
								<div class="toggle-row"><span>Auto end session after 3 hours</span><button class="switch is-on"></button></div>
								<div class="toggle-row"><span>Low stock notifications</span><button class="switch is-on"></button></div>
								<div class="toggle-row"><span>Sound alerts</span><button class="switch"></button></div>
								<div class="toggle-row"><span>Compact card layout</span><button class="switch"></button></div>
							</div>
							<button class="solid-btn full">Save settings</button>
						</article>
					</section>
				<?php endif; ?>
			</main>

			<?php include __DIR__ . "/includes/footer.php"; ?>
		</div>
	</div>

	<div class="mobile-overlay" id="mobileOverlay"></div>

	<script src="assets/js/main.js"></script>
</body>
</html>
