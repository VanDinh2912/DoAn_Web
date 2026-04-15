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

	var tableBillRuntime = null;
	var tableBillTickTimer = null;

	function normalizeBusinessTableStatus(status) {
		var normalized = String(status || "").trim().toLowerCase();
		if (normalized === "playing" || normalized === "đang sử dụng" || normalized === "dang su dung" || normalized === "active") {
			return "PLAYING";
		}
		if (normalized === "reserved" || normalized === "đặt trước" || normalized === "dat truoc") {
			return "RESERVED";
		}
		if (normalized === "empty" || normalized === "available" || normalized === "trống" || normalized === "trong") {
			return "EMPTY";
		}

		return "EMPTY";
	}

	function mapUiTableStatusToBusinessStatus(uiStatus) {
		var normalized = String(uiStatus || "").trim().toLowerCase();
		if (normalized === "playing") {
			return "PLAYING";
		}
		if (normalized === "reserved") {
			return "RESERVED";
		}
		if (normalized === "available") {
			return "EMPTY";
		}

		return normalizeBusinessTableStatus(uiStatus);
	}

	function mapBusinessStatusToUiStatus(status) {
		if (status === "PLAYING") {
			return "playing";
		}
		if (status === "RESERVED") {
			return "reserved";
		}

		return "available";
	}

	function mapBusinessStatusToLabel(status) {
		if (status === "PLAYING") {
			return "ĐANG CHƠI";
		}
		if (status === "RESERVED") {
			return "ĐẶT TRƯỚC";
		}

		return "TRỐNG";
	}

	function parseRuntimeDateTime(value) {
		var raw = String(value || "").trim();
		if (raw === "") {
			return null;
		}

		var direct = new Date(raw);
		if (!isNaN(direct.getTime())) {
			return direct;
		}

		var normalized = raw.replace(" ", "T");
		var fallback = new Date(normalized);
		if (!isNaN(fallback.getTime())) {
			return fallback;
		}

		return null;
	}

	function parseElapsedLabelToMs(label) {
		var text = String(label || "").trim();
		var match = text.match(/^(\d{1,2}):(\d{2}):(\d{2})$/);
		if (!match) {
			return 0;
		}

		var hours = parseInt(match[1], 10);
		var minutes = parseInt(match[2], 10);
		var seconds = parseInt(match[3], 10);

		if (!isFinite(hours) || !isFinite(minutes) || !isFinite(seconds)) {
			return 0;
		}

		return (hours * 3600 + minutes * 60 + seconds) * 1000;
	}

	function formatDurationFromMs(durationMs) {
		var safeDuration = Math.max(0, durationMs);
		var totalSeconds = Math.floor(safeDuration / 1000);
		var hours = Math.floor(totalSeconds / 3600);
		var minutes = Math.floor((totalSeconds % 3600) / 60);
		var seconds = totalSeconds % 60;

		var hh = String(hours);
		if (hh.length < 2) {
			hh = "0" + hh;
		}

		var mm = String(minutes);
		if (mm.length < 2) {
			mm = "0" + mm;
		}

		var ss = String(seconds);
		if (ss.length < 2) {
			ss = "0" + ss;
		}

		return hh + ":" + mm + ":" + ss;
	}

	function roundCurrency(value) {
		return Math.round((Number(value) + Number.EPSILON) * 100) / 100;
	}

	function createTableBillRuntime(initialState) {
		var sourceTables = Array.isArray(initialState && initialState.tables) ? initialState.tables : [];
		var sourceBills = Array.isArray(initialState && initialState.bills) ? initialState.bills : [];

		var tables = sourceTables.map(function (table) {
			return {
				id: parseInt(String(table.id || "0"), 10),
				name: String(table.name || ""),
				status: normalizeBusinessTableStatus(table.status),
				price: Math.max(0, parseFloat(String(table.price || "0").replace(",", ".")) || 0),
				currentBillId: table.currentBillId ? parseInt(String(table.currentBillId), 10) : null,
			};
		});

		var bills = sourceBills.map(function (bill) {
			var startAt = parseRuntimeDateTime(bill.startTime) || new Date();
			var endAt = parseRuntimeDateTime(bill.endTime);

			return {
				id: parseInt(String(bill.id || "0"), 10),
				tableId: parseInt(String(bill.tableId || "0"), 10),
				startTime: startAt.toISOString(),
				endTime: endAt ? endAt.toISOString() : null,
				status: String(bill.status || "OPEN").toUpperCase() === "PAID" ? "PAID" : "OPEN",
				totalAmount: roundCurrency(bill.totalAmount || 0),
				items: Array.isArray(bill.items)
					? bill.items.map(function (item) {
						var quantity = Math.max(0, parseInt(String(item.quantity || "0"), 10) || 0);
						var unitPrice = Math.max(0, parseFloat(String(item.unitPrice || "0").replace(",", ".")) || 0);
						return {
							name: String(item.name || "Dich vu"),
							quantity: quantity,
							unitPrice: unitPrice,
						};
					})
					: [],
			};
		});

		var nextBillId = bills.reduce(function (maxId, bill) {
			return bill.id > maxId ? bill.id : maxId;
		}, 0) + 1;

		function cloneTable(table) {
			if (!table) {
				return null;
			}

			return {
				id: table.id,
				name: table.name,
				status: table.status,
				price: table.price,
				currentBillId: table.currentBillId,
			};
		}

		function cloneBill(bill) {
			if (!bill) {
				return null;
			}

			return {
				id: bill.id,
				tableId: bill.tableId,
				startTime: bill.startTime,
				endTime: bill.endTime,
				status: bill.status,
				totalAmount: bill.totalAmount,
				items: bill.items.map(function (item) {
					return {
						name: item.name,
						quantity: item.quantity,
						unitPrice: item.unitPrice,
					};
				}),
			};
		}

		function findTableById(tableId) {
			for (var i = 0; i < tables.length; i += 1) {
				if (tables[i].id === tableId) {
					return tables[i];
				}
			}

			return null;
		}

		function findOpenBillByTable(tableId) {
			for (var i = bills.length - 1; i >= 0; i -= 1) {
				if (bills[i].tableId === tableId && bills[i].status === "OPEN") {
					return bills[i];
				}
			}

			return null;
		}

		function parsePositiveId(value) {
			var parsed = parseInt(String(value || "0"), 10);
			if (!isFinite(parsed) || parsed <= 0) {
				return 0;
			}

			return parsed;
		}

		function computeServiceAmount(items) {
			var total = 0;
			for (var i = 0; i < items.length; i += 1) {
				total += items[i].quantity * items[i].unitPrice;
			}

			return roundCurrency(total);
		}

		return {
			getTables: function () {
				return tables.map(cloneTable);
			},
			getBills: function () {
				return bills.map(cloneBill);
			},
			getTable: function (tableId) {
				return cloneTable(findTableById(parsePositiveId(tableId)));
			},
			getOpenBillByTable: function (tableId) {
				return cloneBill(findOpenBillByTable(parsePositiveId(tableId)));
			},
			openTable: function (tableId, options) {
				var resolvedTableId = parsePositiveId(tableId);
				var table = findTableById(resolvedTableId);
				if (!table) {
					return {
						success: false,
						message: "Không tìm thấy bàn.",
					};
				}

				if (table.status !== "EMPTY") {
					return {
						success: false,
						message: "Bàn đang sử dụng hoặc đã đặt trước.",
					};
				}

				if (findOpenBillByTable(resolvedTableId) !== null) {
					return {
						success: false,
						message: "Bàn này đã có hóa đơn đang mở.",
					};
				}

				var providedId = parsePositiveId(options && options.billId);
				var billId = providedId > 0 ? providedId : nextBillId;
				nextBillId = Math.max(nextBillId + 1, billId + 1);

				var startAt = parseRuntimeDateTime(options && options.startTime) || new Date();

				var newBill = {
					id: billId,
					tableId: resolvedTableId,
					startTime: startAt.toISOString(),
					endTime: null,
					status: "OPEN",
					totalAmount: 0,
					items: [],
				};

				bills.push(newBill);
				table.currentBillId = billId;
				table.status = "PLAYING";

				return {
					success: true,
					table: cloneTable(table),
					bill: cloneBill(newBill),
				};
			},
			addServiceToTable: function (tableId, item) {
				var resolvedTableId = parsePositiveId(tableId);
				var openBill = findOpenBillByTable(resolvedTableId);
				if (!openBill) {
					return {
						success: false,
						message: "Không có hóa đơn mở để thêm món.",
					};
				}

				var quantity = Math.max(1, parseInt(String((item && item.quantity) || "1"), 10) || 1);
				var unitPrice = Math.max(0, parseFloat(String((item && item.unitPrice) || "0").replace(",", ".")) || 0);
				var name = String((item && item.name) || "Dich vu").trim() || "Dich vu";

				openBill.items.push({
					name: name,
					quantity: quantity,
					unitPrice: unitPrice,
				});

				return {
					success: true,
					bill: cloneBill(openBill),
				};
			},
			getPlayingDurationMs: function (tableId, atTime) {
				var openBill = findOpenBillByTable(parsePositiveId(tableId));
				if (!openBill) {
					return null;
				}

				var startAt = parseRuntimeDateTime(openBill.startTime) || new Date();
				var at = parseRuntimeDateTime(atTime) || new Date();
				return Math.max(0, at.getTime() - startAt.getTime());
			},
			calculateBill: function (tableId, endTime) {
				var resolvedTableId = parsePositiveId(tableId);
				var table = findTableById(resolvedTableId);
				if (!table) {
					return {
						success: false,
						message: "Không tìm thấy bàn.",
					};
				}

				var openBill = findOpenBillByTable(resolvedTableId);
				if (!openBill) {
					return {
						success: false,
						message: "Không có hóa đơn mở để thanh toán.",
					};
				}

				var startAt = parseRuntimeDateTime(openBill.startTime) || new Date();
				var closedAt = parseRuntimeDateTime(endTime) || new Date();
				var durationMs = Math.max(0, closedAt.getTime() - startAt.getTime());
				var durationHours = durationMs / 3600000;
				var tableAmount = roundCurrency(durationHours * table.price);
				var serviceAmount = computeServiceAmount(openBill.items);
				var totalAmount = roundCurrency(tableAmount + serviceAmount);

				return {
					success: true,
					table: cloneTable(table),
					bill: cloneBill(openBill),
					endTime: closedAt,
					durationMs: durationMs,
					durationHours: durationHours,
					tableAmount: tableAmount,
					serviceAmount: serviceAmount,
					totalAmount: totalAmount,
				};
			},
			closeTable: function (tableId, endTime) {
				var resolvedTableId = parsePositiveId(tableId);
				var table = findTableById(resolvedTableId);
				if (!table) {
					return {
						success: false,
						message: "Không tìm thấy bàn.",
					};
				}

				var openBill = findOpenBillByTable(resolvedTableId);
				if (!openBill) {
					return {
						success: false,
						message: "Không có hóa đơn mở để thanh toán.",
					};
				}

				var calculated = this.calculateBill(resolvedTableId, endTime);
				if (!calculated.success) {
					return calculated;
				}

				openBill.endTime = calculated.endTime.toISOString();
				openBill.totalAmount = calculated.totalAmount;
				openBill.status = "PAID";

				table.currentBillId = null;
				table.status = "EMPTY";

				return {
					success: true,
					table: cloneTable(table),
					bill: cloneBill(openBill),
					durationMs: calculated.durationMs,
					durationHours: calculated.durationHours,
					tableAmount: calculated.tableAmount,
					serviceAmount: calculated.serviceAmount,
					totalAmount: calculated.totalAmount,
				};
			},
			resetTable: function (tableId) {
				var resolvedTableId = parsePositiveId(tableId);
				var table = findTableById(resolvedTableId);
				if (!table) {
					return {
						success: false,
						message: "Không tìm thấy bàn.",
					};
				}

				var openBill = findOpenBillByTable(resolvedTableId);
				if (openBill) {
					if (!openBill.endTime) {
						openBill.endTime = new Date().toISOString();
					}
					openBill.status = "PAID";
				}

				table.currentBillId = null;
				table.status = "EMPTY";

				return {
					success: true,
					table: cloneTable(table),
					bill: cloneBill(openBill),
				};
			},
		};
	}

	function buildTableBillRuntimeStateFromDom() {
		var state = {
			tables: [],
			bills: [],
		};

		if (!tableCardGrid) {
			return state;
		}

		var cards = tableCardGrid.querySelectorAll(".table-card[data-table-id]");
		Array.prototype.forEach.call(cards, function (card) {
			var tableId = parseInt(card.getAttribute("data-table-id") || "0", 10);
			if (!isFinite(tableId) || tableId <= 0) {
				return;
			}

			var tableRate = parseFloat(String(card.getAttribute("data-table-rate") || "0").replace(",", "."));
			if (!isFinite(tableRate) || tableRate < 0) {
				tableRate = 0;
			}

			var uiStatus = card.getAttribute("data-status") || "available";
			var businessStatus = mapUiTableStatusToBusinessStatus(uiStatus);
			var currentBillId = parseInt(card.getAttribute("data-current-bill-id") || "0", 10);
			if (!isFinite(currentBillId) || currentBillId <= 0) {
				currentBillId = null;
			}

			state.tables.push({
				id: tableId,
				name: card.getAttribute("data-table-name") || "Ban " + tableId,
				status: businessStatus,
				price: tableRate,
				currentBillId: currentBillId,
			});

			if (currentBillId !== null) {
				var openedAt = parseRuntimeDateTime(card.getAttribute("data-opened-at") || "");
				if (!openedAt) {
					var elapsedNode = card.querySelector('[data-role="table-elapsed"]');
					var elapsedMs = parseElapsedLabelToMs(elapsedNode ? elapsedNode.textContent : "");
					if (elapsedMs > 0) {
						openedAt = new Date(Date.now() - elapsedMs);
					} else {
						openedAt = new Date();
					}
				}

				state.bills.push({
					id: currentBillId,
					tableId: tableId,
					startTime: openedAt.toISOString(),
					endTime: null,
					status: "OPEN",
					totalAmount: 0,
					items: [],
				});
			}
		});

		return state;
	}

	function getRuntimeTableCardById(tableId) {
		if (!tableCardGrid) {
			return null;
		}

		return tableCardGrid.querySelector('.table-card[data-table-id="' + tableId + '"]');
	}

	function updateRuntimeTableCard(tableId) {
		if (!tableBillRuntime) {
			return;
		}

		var table = tableBillRuntime.getTable(tableId);
		if (!table) {
			return;
		}

		var card = getRuntimeTableCardById(table.id);
		if (!card) {
			return;
		}

		var uiStatus = mapBusinessStatusToUiStatus(table.status);
		card.setAttribute("data-status", uiStatus);
		card.setAttribute("data-current-bill-id", table.currentBillId ? String(table.currentBillId) : "0");

		var openBill = tableBillRuntime.getOpenBillByTable(table.id);
		card.setAttribute("data-opened-at", openBill && openBill.startTime ? openBill.startTime : "");

		var statusNode = card.querySelector('[data-role="table-status-label"]');
		if (statusNode) {
			statusNode.classList.remove("chip-available", "chip-playing", "chip-reserved");
			statusNode.classList.add("chip-" + uiStatus);
			statusNode.textContent = mapBusinessStatusToLabel(table.status);
		}

		var playerNode = card.querySelector('[data-role="table-player"]');
		if (playerNode) {
			if (table.status === "PLAYING") {
				playerNode.textContent = "Đang phục vụ";
			} else if (table.status === "RESERVED") {
				playerNode.textContent = "Đã đặt trước";
			} else {
				playerNode.textContent = "Sẵn sàng";
			}
		}

		var elapsedNode = card.querySelector('[data-role="table-elapsed"]');
		if (elapsedNode) {
			if (table.status === "PLAYING") {
				var durationMs = tableBillRuntime.getPlayingDurationMs(table.id);
				elapsedNode.textContent = durationMs === null ? "00:00:00" : formatDurationFromMs(durationMs);
			} else {
				elapsedNode.textContent = "Chưa mở phiên";
			}
		}
	}

	function updateAllRuntimeTableCards() {
		if (!tableBillRuntime || !tableCardGrid) {
			return;
		}

		var cards = tableCardGrid.querySelectorAll(".table-card[data-table-id]");
		Array.prototype.forEach.call(cards, function (card) {
			var tableId = parseInt(card.getAttribute("data-table-id") || "0", 10);
			if (!isFinite(tableId) || tableId <= 0) {
				return;
			}

			updateRuntimeTableCard(tableId);
		});
	}

	function bindTableBillApi() {
		window.tableBillRuntime = tableBillRuntime;

		window.openTable = function (tableId, options) {
			var result = tableBillRuntime ? tableBillRuntime.openTable(tableId, options) : { success: false, message: "Runtime chưa sẵn sàng." };
			if (!result.success) {
				window.alert(result.message || "Không thể mở bàn.");
				return result;
			}

			if (result.table) {
				updateRuntimeTableCard(result.table.id);
			}

			return result;
		};

		window.addServiceToTable = function (tableId, item) {
			var result = tableBillRuntime ? tableBillRuntime.addServiceToTable(tableId, item) : { success: false, message: "Runtime chưa sẵn sàng." };
			if (!result.success) {
				window.alert(result.message || "Không thể thêm món.");
			}

			return result;
		};

		window.getPlayingDurationMs = function (tableId, atTime) {
			if (!tableBillRuntime) {
				return null;
			}

			return tableBillRuntime.getPlayingDurationMs(tableId, atTime);
		};

		window.closeTable = function (tableId, endTime) {
			var result = tableBillRuntime ? tableBillRuntime.closeTable(tableId, endTime) : { success: false, message: "Runtime chưa sẵn sàng." };
			if (!result.success) {
				window.alert(result.message || "Không thể thanh toán.");
				return result;
			}

			if (result.table) {
				updateRuntimeTableCard(result.table.id);
			}

			return result;
		};

		window.resetTable = function (tableId) {
			var result = tableBillRuntime ? tableBillRuntime.resetTable(tableId) : { success: false, message: "Runtime chưa sẵn sàng." };
			if (!result.success) {
				window.alert(result.message || "Không thể reset bàn.");
				return result;
			}

			if (result.table) {
				updateRuntimeTableCard(result.table.id);
			}

			return result;
		};

		window.getTableBillSnapshot = function () {
			if (!tableBillRuntime) {
				return {
					tables: [],
					bills: [],
				};
			}

			return {
				tables: tableBillRuntime.getTables(),
				bills: tableBillRuntime.getBills(),
			};
		};
	}

	function initTableBillRuntime() {
		var runtimeState = buildTableBillRuntimeStateFromDom();
		tableBillRuntime = createTableBillRuntime(runtimeState);
		bindTableBillApi();

		if (tableCardGrid) {
			updateAllRuntimeTableCards();

			if (tableBillTickTimer !== null) {
				window.clearInterval(tableBillTickTimer);
			}

			tableBillTickTimer = window.setInterval(function () {
				updateAllRuntimeTableCards();
			}, 1000);
		}
	}

	initTableBillRuntime();

	var overlay = document.getElementById("overlay");
	var popupTable = document.getElementById("popupTable");
	var popupTableType = document.getElementById("popupTableType");
	var popupArea = document.getElementById("popupArea");
	var popupTableForm = document.getElementById("popupTableForm");
	var popupTableTitle = document.getElementById("popupTableTitle");
	var popupTableSubtitle = document.getElementById("popupTableSubtitle");
	var popupTableAction = document.getElementById("popupTableAction");
	var popupTableId = document.getElementById("popupTableId");
	var popupTableName = document.getElementById("popupTableName");
	var popupTableTypeId = document.getElementById("popupTableTypeId");
	var popupTableAreaId = document.getElementById("popupTableAreaId");
	var popupTableCalculatedPrice = document.getElementById("popupTableCalculatedPrice");
	var popupTableStatus = document.getElementById("popupTableStatus");
	var popupTableSubmit = document.getElementById("popupTableSubmit");
	var popupTableDeleteBtn = document.getElementById("popupTableDeleteBtn");
	var popupTableDeleteId = document.getElementById("popupTableDeleteId");
	var popupTableDeleteForm = document.getElementById("popupTableDeleteForm");
	var popupTableTypeForm = document.getElementById("popupTableTypeForm");
	var popupTableTypeNameInput = document.getElementById("popupTableTypeName");
	var popupTableTypeBasePriceInput = document.getElementById("popupTableTypeBasePrice");
	var popupTableTypeSubmit = document.getElementById("popupTableTypeSubmit");
	var popupTableTypeCancelEdit = document.getElementById("popupTableTypeCancelEdit");
	var popupTableTypeList = document.getElementById("popupTableTypeList");
	var popupAreaForm = document.getElementById("popupAreaForm");
	var popupAreaNameInput = document.getElementById("popupAreaName");
	var popupAreaExtraPriceInput = document.getElementById("popupAreaExtraPrice");
	var popupAreaSubmit = document.getElementById("popupAreaSubmit");
	var popupAreaCancelEdit = document.getElementById("popupAreaCancelEdit");
	var popupAreaList = document.getElementById("popupAreaList");
	var confirmOverlay = document.getElementById("confirmOverlay");
	var popupConfirmDelete = document.getElementById("popupConfirmDelete");
	var popupConfirmDeleteTitle = document.getElementById("popupConfirmDeleteTitle");
	var popupConfirmDeleteMessage = document.getElementById("popupConfirmDeleteMessage");
	var popupConfirmDeleteAccept = document.getElementById("popupConfirmDeleteAccept");
	var closePopupTimer = null;
	var closeConfirmTimer = null;
	var pendingDeleteAction = null;
	var popupMap = {
		table: popupTable,
		type: popupTableType,
		area: popupArea,
	};
	var tableDefaults = {
		action: "table_create",
		title: "Thêm bàn",
		subtitle: "Nhập thông tin để tạo bàn mới.",
		submitText: "Lưu bàn",
		id: "",
		name: "",
		typeId: popupTableTypeId ? popupTableTypeId.value : "",
		areaId: popupTableAreaId ? popupTableAreaId.value : "",
		status: popupTableStatus ? popupTableStatus.value : "Trống",
	};
	let tableTypes = [];
	let areas = [];
	var nextTableTypeId = 1;
	var nextAreaId = 1;
	var editingTableTypeId = null;
	var editingAreaId = null;

	function readNonNegativeNumber(value) {
		var normalized = String(value || "").replace(",", ".").trim();
		var parsed = parseFloat(normalized);
		return isFinite(parsed) && parsed > 0 ? parsed : 0;
	}

	function escapeHtml(value) {
		return String(value)
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/\"/g, "&quot;")
			.replace(/'/g, "&#39;");
	}

	function formatVnd(amount) {
		return Math.round(amount).toLocaleString("vi-VN") + " VND";
	}

	function hydrateTableTypesFromSelect() {
		tableTypes = [];
		if (!popupTableTypeId) {
			return;
		}

		Array.prototype.forEach.call(popupTableTypeId.options, function (option) {
			var rawId = parseInt(option.value || "0", 10);
			if (!isFinite(rawId) || rawId <= 0) {
				return;
			}
			var basePrice = readNonNegativeNumber(option.getAttribute("data-base-price") || "0");

			tableTypes.push({
				id: rawId,
				name: option.textContent ? option.textContent.trim() : "",
				basePrice: basePrice,
			});
		});

		nextTableTypeId = tableTypes.reduce(function (maxId, item) {
			return item.id > maxId ? item.id : maxId;
		}, 0) + 1;
	}

	function hydrateAreasFromSelect() {
		areas = [];
		if (!popupTableAreaId) {
			return;
		}

		Array.prototype.forEach.call(popupTableAreaId.options, function (option) {
			var rawId = parseInt(option.value || "0", 10);
			if (!isFinite(rawId) || rawId <= 0) {
				return;
			}
			var extraPrice = readNonNegativeNumber(option.getAttribute("data-extra-price") || "0");

			areas.push({
				id: rawId,
				name: option.textContent ? option.textContent.trim() : "",
				extraPrice: extraPrice,
			});
		});

		nextAreaId = areas.reduce(function (maxId, item) {
			return item.id > maxId ? item.id : maxId;
		}, 0) + 1;
	}

	function syncTableTypeSelect(preferredId) {
		if (!popupTableTypeId) {
			return;
		}

		var selectedBefore = popupTableTypeId.value;
		popupTableTypeId.innerHTML = "";

		var placeholderOption = document.createElement("option");
		placeholderOption.value = "";
		placeholderOption.textContent = "Chọn loại bàn";
		popupTableTypeId.appendChild(placeholderOption);

		tableTypes.forEach(function (item) {
			var option = document.createElement("option");
			option.value = String(item.id);
			option.textContent = item.name;
			option.setAttribute("data-base-price", String(item.basePrice));
			popupTableTypeId.appendChild(option);
		});

		var resolvedValue = selectedBefore;
		if (preferredId !== undefined && preferredId !== null && preferredId !== "") {
			resolvedValue = String(preferredId);
		}

		var exists = tableTypes.some(function (item) {
			return String(item.id) === resolvedValue;
		});

		if (!exists) {
			resolvedValue = tableTypes.length > 0 ? String(tableTypes[0].id) : "";
		}

		popupTableTypeId.value = resolvedValue;
		tableDefaults.typeId = resolvedValue;
	}

	function syncAreaSelect(preferredId) {
		if (!popupTableAreaId) {
			return;
		}

		var selectedBefore = popupTableAreaId.value;
		popupTableAreaId.innerHTML = "";

		var placeholderOption = document.createElement("option");
		placeholderOption.value = "";
		placeholderOption.textContent = "Chọn khu vực";
		popupTableAreaId.appendChild(placeholderOption);

		areas.forEach(function (item) {
			var option = document.createElement("option");
			option.value = String(item.id);
			option.textContent = item.name;
			option.setAttribute("data-extra-price", String(item.extraPrice));
			popupTableAreaId.appendChild(option);
		});

		var resolvedValue = selectedBefore;
		if (preferredId !== undefined && preferredId !== null && preferredId !== "") {
			resolvedValue = String(preferredId);
		}

		var exists = areas.some(function (item) {
			return String(item.id) === resolvedValue;
		});

		if (!exists) {
			resolvedValue = areas.length > 0 ? String(areas[0].id) : "";
		}

		popupTableAreaId.value = resolvedValue;
		tableDefaults.areaId = resolvedValue;
	}

	function renderTableTypes() {
		if (!popupTableTypeList) {
			return;
		}

		if (tableTypes.length === 0) {
			popupTableTypeList.innerHTML = '<p class="popup-entity-empty">Chưa có loại bàn nào.</p>';
			return;
		}

		popupTableTypeList.innerHTML = tableTypes
			.map(function (item) {
				return (
					'<div class="popup-entity-item">' +
					'<div class="popup-entity-meta">' +
					'<strong>' + escapeHtml(item.name) + '</strong>' +
					'<span>Giá cơ bản: ' + formatVnd(item.basePrice) + '/h</span>' +
					"</div>" +
					'<div class="popup-entity-actions">' +
					'<button type="button" class="ghost-btn thin" data-action="edit-table-type" data-id="' + item.id + '">Sửa</button>' +
					'<button type="button" class="danger-btn thin" data-action="delete-table-type" data-id="' + item.id + '">Xóa</button>' +
					"</div>" +
					"</div>"
				);
			})
			.join("");
	}

	function renderAreas() {
		if (!popupAreaList) {
			return;
		}

		if (areas.length === 0) {
			popupAreaList.innerHTML = '<p class="popup-entity-empty">Chưa có khu vực nào.</p>';
			return;
		}

		popupAreaList.innerHTML = areas
			.map(function (item) {
				return (
					'<div class="popup-entity-item">' +
					'<div class="popup-entity-meta">' +
					'<strong>' + escapeHtml(item.name) + '</strong>' +
					'<span>Phụ thu: ' + formatVnd(item.extraPrice) + '</span>' +
					"</div>" +
					'<div class="popup-entity-actions">' +
					'<button type="button" class="ghost-btn thin" data-action="edit-area" data-id="' + item.id + '">Sửa</button>' +
					'<button type="button" class="danger-btn thin" data-action="delete-area" data-id="' + item.id + '">Xóa</button>' +
					"</div>" +
					"</div>"
				);
			})
			.join("");
	}

	function resetTableTypeFormState() {
		editingTableTypeId = null;
		if (popupTableTypeForm) {
			popupTableTypeForm.reset();
		}
		if (popupTableTypeBasePriceInput) {
			popupTableTypeBasePriceInput.value = "0";
		}
		if (popupTableTypeSubmit) {
			popupTableTypeSubmit.textContent = "Lưu loại bàn";
		}
		if (popupTableTypeCancelEdit) {
			popupTableTypeCancelEdit.classList.add("hidden");
		}
	}

	function resetAreaFormState() {
		editingAreaId = null;
		if (popupAreaForm) {
			popupAreaForm.reset();
		}
		if (popupAreaExtraPriceInput) {
			popupAreaExtraPriceInput.value = "0";
		}
		if (popupAreaSubmit) {
			popupAreaSubmit.textContent = "Lưu khu vực";
		}
		if (popupAreaCancelEdit) {
			popupAreaCancelEdit.classList.add("hidden");
		}
	}

	function startEditTableType(id) {
		var item = tableTypes.find(function (entry) {
			return entry.id === id;
		});
		if (!item) {
			return;
		}

		editingTableTypeId = id;
		if (popupTableTypeNameInput) {
			popupTableTypeNameInput.value = item.name;
		}
		if (popupTableTypeBasePriceInput) {
			popupTableTypeBasePriceInput.value = String(item.basePrice);
		}
		if (popupTableTypeSubmit) {
			popupTableTypeSubmit.textContent = "Cập nhật loại bàn";
		}
		if (popupTableTypeCancelEdit) {
			popupTableTypeCancelEdit.classList.remove("hidden");
		}
	}

	function startEditArea(id) {
		var item = areas.find(function (entry) {
			return entry.id === id;
		});
		if (!item) {
			return;
		}

		editingAreaId = id;
		if (popupAreaNameInput) {
			popupAreaNameInput.value = item.name;
		}
		if (popupAreaExtraPriceInput) {
			popupAreaExtraPriceInput.value = String(item.extraPrice);
		}
		if (popupAreaSubmit) {
			popupAreaSubmit.textContent = "Cập nhật khu vực";
		}
		if (popupAreaCancelEdit) {
			popupAreaCancelEdit.classList.remove("hidden");
		}
	}

	function deleteTableType(id) {
		tableTypes = tableTypes.filter(function (entry) {
			return entry.id !== id;
		});

		if (editingTableTypeId === id) {
			resetTableTypeFormState();
		}

		renderTableTypes();
		syncTableTypeSelect();
		updateCalculatedTablePriceDisplay();
	}

	function deleteArea(id) {
		areas = areas.filter(function (entry) {
			return entry.id !== id;
		});

		if (editingAreaId === id) {
			resetAreaFormState();
		}

		renderAreas();
		syncAreaSelect();
		updateCalculatedTablePriceDisplay();
	}

	function addTableType() {
		if (!popupTableTypeNameInput || !popupTableTypeBasePriceInput) {
			return;
		}

		var name = popupTableTypeNameInput.value.trim();
		var basePrice = readNonNegativeNumber(popupTableTypeBasePriceInput.value);
		if (name === "") {
			popupTableTypeNameInput.focus();
			return;
		}

		var targetId = editingTableTypeId;
		if (targetId === null) {
			targetId = nextTableTypeId;
			nextTableTypeId += 1;
			tableTypes.push({
				id: targetId,
				name: name,
				basePrice: basePrice,
			});
		} else {
			tableTypes = tableTypes.map(function (entry) {
				if (entry.id !== targetId) {
					return entry;
				}
				return {
					id: entry.id,
					name: name,
					basePrice: basePrice,
				};
			});
		}

		renderTableTypes();
		syncTableTypeSelect(targetId);
		updateCalculatedTablePriceDisplay();
		resetTableTypeFormState();
	}

	function addArea() {
		if (!popupAreaNameInput || !popupAreaExtraPriceInput) {
			return;
		}

		var name = popupAreaNameInput.value.trim();
		var extraPrice = readNonNegativeNumber(popupAreaExtraPriceInput.value);
		if (name === "") {
			popupAreaNameInput.focus();
			return;
		}

		var targetId = editingAreaId;
		if (targetId === null) {
			targetId = nextAreaId;
			nextAreaId += 1;
			areas.push({
				id: targetId,
				name: name,
				extraPrice: extraPrice,
			});
		} else {
			areas = areas.map(function (entry) {
				if (entry.id !== targetId) {
					return entry;
				}
				return {
					id: entry.id,
					name: name,
					extraPrice: extraPrice,
				};
			});
		}

		renderAreas();
		syncAreaSelect(targetId);
		updateCalculatedTablePriceDisplay();
		resetAreaFormState();
	}

	function readOptionAmount(selectElement, dataAttribute) {
		if (!selectElement || !dataAttribute || selectElement.selectedIndex < 0) {
			return 0;
		}

		var selectedOption = selectElement.options[selectElement.selectedIndex];
		if (!selectedOption) {
			return 0;
		}

		var raw = selectedOption.getAttribute(dataAttribute) || "0";
		var normalized = raw.replace(",", ".").trim();
		var parsed = parseFloat(normalized);
		return isFinite(parsed) && parsed > 0 ? parsed : 0;
	}

	function formatVndPerHour(amount) {
		var rounded = Math.round(amount);
		return rounded.toLocaleString("vi-VN") + " VND/h";
	}

	function updateCalculatedTablePriceDisplay() {
		if (!popupTableCalculatedPrice) {
			return;
		}

		var hasType = popupTableTypeId && popupTableTypeId.value !== "";
		var hasArea = popupTableAreaId && popupTableAreaId.value !== "";

		if (!hasType || !hasArea) {
			popupTableCalculatedPrice.value = "";
			return;
		}

		var basePrice = readOptionAmount(popupTableTypeId, "data-base-price");
		var extraPrice = readOptionAmount(popupTableAreaId, "data-extra-price");
		popupTableCalculatedPrice.value = formatVndPerHour(basePrice + extraPrice);
	}

	hydrateTableTypesFromSelect();
	hydrateAreasFromSelect();
	renderTableTypes();
	renderAreas();
	syncTableTypeSelect(tableDefaults.typeId);
	syncAreaSelect(tableDefaults.areaId);

	function setTablePopupMode(mode, data) {
		if (!popupTableForm) {
			return;
		}

		if (mode === "edit") {
			if (popupTableTitle) {
				popupTableTitle.textContent = "Cập nhật bàn #" + data.id;
			}
			if (popupTableSubtitle) {
				popupTableSubtitle.textContent = "Điều chỉnh thông tin bàn và lưu thay đổi.";
			}
			if (popupTableAction) {
				popupTableAction.value = "table_update";
			}
			if (popupTableId) {
				popupTableId.value = data.id;
			}
			if (popupTableDeleteId) {
				popupTableDeleteId.value = data.id;
			}
			if (popupTableName) {
				popupTableName.value = data.name;
			}
			if (popupTableTypeId) {
				popupTableTypeId.value = data.typeId;
			}
			if (popupTableAreaId) {
				popupTableAreaId.value = data.areaId;
			}
			if (popupTableStatus) {
				popupTableStatus.value = data.status;
			}
			updateCalculatedTablePriceDisplay();
			if (popupTableSubmit) {
				popupTableSubmit.textContent = "Lưu thay đổi";
			}
			if (popupTableDeleteBtn) {
				popupTableDeleteBtn.classList.remove("hidden");
			}
			return;
		}

		popupTableForm.reset();
		if (popupTableTitle) {
			popupTableTitle.textContent = tableDefaults.title;
		}
		if (popupTableSubtitle) {
			popupTableSubtitle.textContent = tableDefaults.subtitle;
		}
		if (popupTableAction) {
			popupTableAction.value = tableDefaults.action;
		}
		if (popupTableId) {
			popupTableId.value = tableDefaults.id;
		}
		if (popupTableDeleteId) {
			popupTableDeleteId.value = "";
		}
		if (popupTableName) {
			popupTableName.value = tableDefaults.name;
		}
		if (popupTableTypeId) {
			popupTableTypeId.value = tableDefaults.typeId;
		}
		if (popupTableAreaId) {
			popupTableAreaId.value = tableDefaults.areaId;
		}
		if (popupTableStatus) {
			popupTableStatus.value = tableDefaults.status;
		}
		updateCalculatedTablePriceDisplay();
		if (popupTableSubmit) {
			popupTableSubmit.textContent = tableDefaults.submitText;
		}
		if (popupTableDeleteBtn) {
			popupTableDeleteBtn.classList.add("hidden");
		}
	}

	function clearCloseTimer() {
		if (closePopupTimer !== null) {
			window.clearTimeout(closePopupTimer);
			closePopupTimer = null;
		}
	}

	function clearConfirmTimer() {
		if (closeConfirmTimer !== null) {
			window.clearTimeout(closeConfirmTimer);
			closeConfirmTimer = null;
		}
	}

	function openDeleteConfirm(options) {
		var title = (options && options.title) || "Xác nhận xóa";
		var message = (options && options.message) || "Bạn có chắc muốn xóa dữ liệu này?";

		if (!confirmOverlay || !popupConfirmDelete) {
			return;
		}

		pendingDeleteAction = options && typeof options.onConfirm === "function" ? options.onConfirm : null;

		if (popupConfirmDeleteTitle) {
			popupConfirmDeleteTitle.textContent = title;
		}
		if (popupConfirmDeleteMessage) {
			popupConfirmDeleteMessage.textContent = message;
		}

		clearConfirmTimer();
		confirmOverlay.classList.remove("hidden");
		popupConfirmDelete.classList.remove("hidden");
		confirmOverlay.setAttribute("aria-hidden", "false");

		requestAnimationFrame(function () {
			confirmOverlay.classList.add("is-visible");
			popupConfirmDelete.classList.add("is-active");
		});
	}

	function closeConfirmPopup() {
		if (!confirmOverlay || !popupConfirmDelete) {
			pendingDeleteAction = null;
			return;
		}

		clearConfirmTimer();
		confirmOverlay.classList.remove("is-visible");
		popupConfirmDelete.classList.remove("is-active");

		closeConfirmTimer = window.setTimeout(function () {
			popupConfirmDelete.classList.add("hidden");
			confirmOverlay.classList.add("hidden");
			confirmOverlay.setAttribute("aria-hidden", "true");
			pendingDeleteAction = null;
			closeConfirmTimer = null;
		}, 180);
	}

	function acceptDeleteConfirm() {
		if (typeof pendingDeleteAction !== "function") {
			closeConfirmPopup();
			return;
		}

		var action = pendingDeleteAction;
		pendingDeleteAction = null;
		closeConfirmPopup();
		action();
	}

	function hideAllPopups() {
		Object.keys(popupMap).forEach(function (key) {
			if (popupMap[key]) {
				popupMap[key].classList.remove("is-active");
				popupMap[key].classList.add("hidden");
			}
		});
	}

	function openPopup(type, options) {
		var targetPopup = popupMap[type];
		var shouldPreserveState = !!(options && options.preserveState);
		if (!overlay || !targetPopup) {
			return;
		}

		clearCloseTimer();

		if (type === "table" && !shouldPreserveState) {
			syncTableTypeSelect(tableDefaults.typeId);
			syncAreaSelect(tableDefaults.areaId);
			setTablePopupMode("create");
		} else if (!shouldPreserveState) {
			var targetForm = targetPopup.querySelector("form");
			if (targetForm) {
				targetForm.reset();
			}

			if (type === "type") {
				resetTableTypeFormState();
				renderTableTypes();
			} else if (type === "area") {
				resetAreaFormState();
				renderAreas();
			}
		}

		hideAllPopups();
		overlay.classList.remove("hidden");
		targetPopup.classList.remove("hidden");
		targetPopup.scrollTop = 0;
		overlay.setAttribute("aria-hidden", "false");
		body.classList.add("popup-open");

		requestAnimationFrame(function () {
			overlay.classList.add("is-visible");
			targetPopup.classList.add("is-active");
		});
	}

	function closePopup() {
		if (!overlay) {
			return;
		}

		clearCloseTimer();
		overlay.classList.remove("is-visible");

		Object.keys(popupMap).forEach(function (key) {
			if (popupMap[key]) {
				popupMap[key].classList.remove("is-active");
			}
		});

		closePopupTimer = window.setTimeout(function () {
			hideAllPopups();
			overlay.classList.add("hidden");
			overlay.setAttribute("aria-hidden", "true");
			body.classList.remove("popup-open");
			closePopupTimer = null;
		}, 180);
	}

	function openEditTablePopup(triggerButton) {
		if (!triggerButton) {
			return;
		}

		setTablePopupMode("edit", {
			id: triggerButton.getAttribute("data-table-id") || "",
			name: triggerButton.getAttribute("data-table-name") || "",
			typeId: triggerButton.getAttribute("data-table-type-id") || "",
			areaId: triggerButton.getAttribute("data-table-area-id") || "",
			status: triggerButton.getAttribute("data-table-status") || "Trống",
		});

		openPopup("table", { preserveState: true });
	}

	function confirmDeleteTableFromPopup() {
		if (!popupTableDeleteForm || !popupTableDeleteId || popupTableDeleteId.value === "") {
			return;
		}

		openDeleteConfirm({
			title: "Xóa bàn",
			message: "Bạn có chắc muốn xóa bàn này?",
			onConfirm: function () {
				console.log("[Popup Submit]", "table-delete", { table_id: popupTableDeleteId.value });
				closePopup();
				popupTableDeleteForm.submit();
			},
		});
	}

	if (overlay) {
		overlay.addEventListener("click", function (event) {
			if (event.target === overlay) {
				closePopup();
			}
		});
	}

	if (confirmOverlay) {
		confirmOverlay.addEventListener("click", function (event) {
			if (event.target === confirmOverlay) {
				closeConfirmPopup();
			}
		});
	}

	if (popupConfirmDeleteAccept) {
		popupConfirmDeleteAccept.addEventListener("click", acceptDeleteConfirm);
	}

	if (popupTableTypeId) {
		popupTableTypeId.addEventListener("change", updateCalculatedTablePriceDisplay);
	}

	if (popupTableAreaId) {
		popupTableAreaId.addEventListener("change", updateCalculatedTablePriceDisplay);
	}

	if (popupTableTypeForm) {
		popupTableTypeForm.addEventListener("submit", function (event) {
			event.preventDefault();
			addTableType();
		});
	}

	if (popupAreaForm) {
		popupAreaForm.addEventListener("submit", function (event) {
			event.preventDefault();
			addArea();
		});
	}

	if (popupTableTypeCancelEdit) {
		popupTableTypeCancelEdit.addEventListener("click", resetTableTypeFormState);
	}

	if (popupAreaCancelEdit) {
		popupAreaCancelEdit.addEventListener("click", resetAreaFormState);
	}

	if (popupTableTypeList) {
		popupTableTypeList.addEventListener("click", function (event) {
			var actionButton = event.target.closest("button[data-action]");
			if (!actionButton) {
				return;
			}

			var id = parseInt(actionButton.getAttribute("data-id") || "0", 10);
			if (!isFinite(id) || id <= 0) {
				return;
			}

			var action = actionButton.getAttribute("data-action");
			if (action === "edit-table-type") {
				startEditTableType(id);
				return;
			}

			if (action === "delete-table-type") {
				openDeleteConfirm({
					title: "Xóa loại bàn",
					message: "Bạn có chắc muốn xóa loại bàn này?",
					onConfirm: function () {
						deleteTableType(id);
					},
				});
			}
		});
	}

	if (popupAreaList) {
		popupAreaList.addEventListener("click", function (event) {
			var actionButton = event.target.closest("button[data-action]");
			if (!actionButton) {
				return;
			}

			var id = parseInt(actionButton.getAttribute("data-id") || "0", 10);
			if (!isFinite(id) || id <= 0) {
				return;
			}

			var action = actionButton.getAttribute("data-action");
			if (action === "edit-area") {
				startEditArea(id);
				return;
			}

			if (action === "delete-area") {
				openDeleteConfirm({
					title: "Xóa khu vực",
					message: "Bạn có chắc muốn xóa khu vực này?",
					onConfirm: function () {
						deleteArea(id);
					},
				});
			}
		});
	}

	updateCalculatedTablePriceDisplay();

	document.addEventListener("keydown", function (event) {
		if (event.key === "Escape") {
			if (confirmOverlay && !confirmOverlay.classList.contains("hidden")) {
				closeConfirmPopup();
				return;
			}

			closePopup();
		}
	});

	var popupForms = document.querySelectorAll('[data-popup-form="table"]');
	popupForms.forEach(function (form) {
		form.addEventListener("submit", function () {
			var formData = new FormData(form);
			var payload = {};

			formData.forEach(function (value, key) {
				if (/csrf/i.test(key) || key === "action" || key === "redirect_page") {
					return;
				}

				payload[key] = typeof value === "string" ? value.trim() : value;
			});

			console.log("[Popup Submit]", form.getAttribute("data-popup-form"), payload);
			closePopup();
		});
	});

	var confirmDeleteForms = document.querySelectorAll(".js-confirm-delete-form");
	confirmDeleteForms.forEach(function (form) {
		form.addEventListener("submit", function (event) {
			event.preventDefault();

			var title = form.getAttribute("data-confirm-title") || "Xác nhận xóa";
			var message = form.getAttribute("data-confirm-message") || "Bạn có chắc muốn xóa dữ liệu này?";

			openDeleteConfirm({
				title: title,
				message: message,
				onConfirm: function () {
					form.submit();
				},
			});
		});
	});

	window.openPopup = openPopup;
	window.closePopup = closePopup;
	window.openEditTablePopup = openEditTablePopup;
	window.confirmDeleteTableFromPopup = confirmDeleteTableFromPopup;
	window.closeConfirmPopup = closeConfirmPopup;
	window.renderTableTypes = renderTableTypes;
	window.renderAreas = renderAreas;
	window.addTableType = addTableType;
	window.addArea = addArea;

	var switches = document.querySelectorAll(".switch");
	switches.forEach(function (toggle) {
		toggle.addEventListener("click", function () {
			toggle.classList.toggle("is-on");
		});
	});
});