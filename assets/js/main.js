document.addEventListener("DOMContentLoaded", function () {
	var body = document.body;
	var mobileMenuToggle = document.getElementById("mobileMenuToggle");
	var mobileOverlay = document.getElementById("mobileOverlay");
	var notifToggle = document.getElementById("notifToggle");
	var notifPanel = document.getElementById("notifPanel");
	var tableFilters = document.getElementById("tableFilters");
	var tableCardGrid = document.getElementById("tableCardGrid");

	function closeSidebar() {
		body.classList.remove("sidebar-open");
	}

	if (mobileMenuToggle) {
		mobileMenuToggle.addEventListener("click", function () {
			body.classList.toggle("sidebar-open");
		});
	}

	if (mobileOverlay) {
		mobileOverlay.addEventListener("click", closeSidebar);
	}

	window.addEventListener("resize", function () {
		if (window.innerWidth > 1024) {
			closeSidebar();
		}
	});

	if (notifToggle && notifPanel) {
		notifToggle.addEventListener("click", function (event) {
			event.stopPropagation();
			notifPanel.classList.toggle("is-open");
		});

		document.addEventListener("click", function (event) {
			if (!notifPanel.contains(event.target) && !notifToggle.contains(event.target)) {
				notifPanel.classList.remove("is-open");
			}
		});
	}

	if (tableFilters && tableCardGrid) {
		var pills = tableFilters.querySelectorAll(".pill");
		var cards = tableCardGrid.querySelectorAll(".table-card");

		pills.forEach(function (pill) {
			pill.addEventListener("click", function () {
				var filter = pill.getAttribute("data-filter");

				pills.forEach(function (item) {
					item.classList.remove("is-active");
				});
				pill.classList.add("is-active");

				cards.forEach(function (card) {
					var status = card.getAttribute("data-status");
					var shouldShow = filter === "all" || status === filter;
					card.style.display = shouldShow ? "grid" : "none";
				});
			});
		});
	}

	var switches = document.querySelectorAll(".switch");
	switches.forEach(function (toggle) {
		toggle.addEventListener("click", function () {
			toggle.classList.toggle("is-on");
		});
	});
});