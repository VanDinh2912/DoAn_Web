import { useState } from "react";
import AppLayout from "@/components/feature/AppLayout";
import { menuItems, activeTables, MenuItem } from "@/mocks/ordersData";

interface OrderItem { item: MenuItem; qty: number; }

const TABLE_RATE = 15;
const TAX_RATE = 0.085;

// ─── Receipt Modal ────────────────────────────────────────────────────────────
interface ReceiptData {
  receiptNo: string;
  table: string;
  player: string;
  date: string;
  time: string;
  sessionMins: number;
  tableCost: number;
  items: OrderItem[];
  itemsTotal: number;
  subtotal: number;
  tax: number;
  grandTotal: number;
}

function ReceiptModal({ data, onClose }: { data: ReceiptData; onClose: () => void }) {
  const handlePrint = () => window.print();

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center"
      style={{ background: "rgba(0,0,0,0.8)", backdropFilter: "blur(8px)" }}
      onClick={onClose}
    >
      <div
        className="rounded-2xl w-full max-w-sm mx-4 overflow-hidden flex flex-col"
        style={{ background: "#0f1923", border: "1px solid rgba(0,255,180,0.2)", maxHeight: "90vh" }}
        onClick={e => e.stopPropagation()}
      >
        {/* Receipt Header */}
        <div
          className="px-6 py-5 text-center flex flex-col items-center gap-2"
          style={{ background: "linear-gradient(135deg, rgba(0,255,180,0.12), rgba(0,200,255,0.08))", borderBottom: "1px dashed rgba(255,255,255,0.1)" }}
        >
          <img
            src="https://public.readdy.ai/ai/img_res/2b1ca771-09e1-4832-8b0d-8dea1ca40d8b.png"
            alt="Logo"
            className="rounded-lg object-cover"
            style={{ width: "40px", height: "40px" }}
          />
          <div>
            <p className="text-white font-bold text-base" style={{ fontFamily: "'Rajdhani', sans-serif", letterSpacing: "0.08em" }}>POOL MANAGER</p>
            <p className="text-xs" style={{ color: "#00ffb4" }}>Billiards Club</p>
            <p className="text-xs mt-1" style={{ color: "#555" }}>123 Cue Street, Downtown, NY 10001</p>
          </div>
        </div>

        {/* Receipt Body */}
        <div className="flex-1 overflow-y-auto px-6 py-4 flex flex-col gap-4">
          {/* Receipt Meta */}
          <div className="flex flex-col gap-1.5">
            {[
              { label: "Receipt #", value: data.receiptNo },
              { label: "Date",      value: data.date },
              { label: "Time",      value: data.time },
              { label: "Table",     value: data.table },
              { label: "Player",    value: data.player },
            ].map(row => (
              <div key={row.label} className="flex items-center justify-between">
                <span className="text-xs" style={{ color: "#555" }}>{row.label}</span>
                <span className="text-xs text-white font-medium">{row.value}</span>
              </div>
            ))}
          </div>

          {/* Divider */}
          <div style={{ borderTop: "1px dashed rgba(255,255,255,0.1)" }} />

          {/* Table Time */}
          <div>
            <p className="text-xs font-semibold mb-2 uppercase tracking-wider" style={{ color: "#555" }}>Session</p>
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-white">Table Time</p>
                <p className="text-xs mt-0.5" style={{ color: "#555" }}>{data.sessionMins}min @ ${TABLE_RATE}/hr</p>
              </div>
              <span className="text-sm font-bold" style={{ color: "#00ffb4" }}>${data.tableCost.toFixed(2)}</span>
            </div>
          </div>

          {/* Items */}
          {data.items.length > 0 && (
            <div>
              <p className="text-xs font-semibold mb-2 uppercase tracking-wider" style={{ color: "#555" }}>Items Ordered</p>
              <div className="flex flex-col gap-2">
                {data.items.map(o => (
                  <div key={o.item.id} className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <span className="text-xs" style={{ color: "#555" }}>{o.qty}×</span>
                      <span className="text-xs text-white">{o.item.name}</span>
                    </div>
                    <span className="text-xs text-white">${(o.item.price * o.qty).toFixed(2)}</span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Divider */}
          <div style={{ borderTop: "1px dashed rgba(255,255,255,0.1)" }} />

          {/* Totals */}
          <div className="flex flex-col gap-2">
            <div className="flex justify-between text-xs" style={{ color: "#888" }}>
              <span>Subtotal</span><span>${data.subtotal.toFixed(2)}</span>
            </div>
            <div className="flex justify-between text-xs" style={{ color: "#888" }}>
              <span>Tax (8.5%)</span><span>${data.tax.toFixed(2)}</span>
            </div>
            <div
              className="flex justify-between text-base font-bold pt-2 mt-1"
              style={{ borderTop: "1px solid rgba(255,255,255,0.1)", color: "#fff" }}
            >
              <span style={{ fontFamily: "'Rajdhani', sans-serif" }}>TOTAL</span>
              <span style={{ color: "#00ffb4", fontFamily: "'Rajdhani', sans-serif" }}>${data.grandTotal.toFixed(2)}</span>
            </div>
          </div>

          {/* Payment Badge */}
          <div className="flex items-center justify-center gap-2 py-2 rounded-xl" style={{ background: "rgba(0,255,180,0.08)", border: "1px solid rgba(0,255,180,0.15)" }}>
            <div className="w-5 h-5 flex items-center justify-center" style={{ color: "#00ffb4" }}>
              <i className="ri-checkbox-circle-fill text-base" />
            </div>
            <span className="text-sm font-semibold" style={{ color: "#00ffb4" }}>PAID — Cash</span>
          </div>

          {/* Footer */}
          <p className="text-xs text-center leading-relaxed" style={{ color: "#444" }}>
            Thank you for visiting Pool Manager!<br />See you next time.
          </p>
        </div>

        {/* Actions */}
        <div
          className="flex gap-3 px-6 py-4"
          style={{ borderTop: "1px solid rgba(255,255,255,0.07)" }}
        >
          <button
            onClick={handlePrint}
            className="flex-1 py-2.5 rounded-xl text-sm font-semibold cursor-pointer transition-all duration-200 whitespace-nowrap flex items-center justify-center gap-2"
            style={{ background: "linear-gradient(135deg,#00ffb4,#00c8ff)", color: "#000" }}
          >
            <i className="ri-printer-line" /> Print Receipt
          </button>
          <button
            onClick={onClose}
            className="px-4 py-2.5 rounded-xl text-sm cursor-pointer whitespace-nowrap"
            style={{ background: "rgba(255,255,255,0.05)", color: "#888" }}
          >
            Close
          </button>
        </div>
      </div>
    </div>
  );
}

// ─── Main Page ────────────────────────────────────────────────────────────────
export default function OrdersPage() {
  const [selectedTable, setSelectedTable] = useState(activeTables[0]);
  const [orderItems, setOrderItems] = useState<OrderItem[]>([]);
  const [category, setCategory] = useState<"all" | "drinks" | "food" | "snacks">("all");
  const [sessionMins] = useState(47);
  const [showConfirm, setShowConfirm] = useState(false);
  const [receiptData, setReceiptData] = useState<ReceiptData | null>(null);

  const filtered = category === "all" ? menuItems : menuItems.filter(m => m.category === category);
  const tableCost = parseFloat(((sessionMins / 60) * TABLE_RATE).toFixed(2));
  const itemsTotal = orderItems.reduce((s, o) => s + o.item.price * o.qty, 0);
  const subtotal = tableCost + itemsTotal;
  const tax = parseFloat((subtotal * TAX_RATE).toFixed(2));
  const grandTotal = subtotal + tax;

  const addItem = (item: MenuItem) => {
    setOrderItems(prev => {
      const ex = prev.find(o => o.item.id === item.id);
      if (ex) return prev.map(o => o.item.id === item.id ? { ...o, qty: o.qty + 1 } : o);
      return [...prev, { item, qty: 1 }];
    });
  };

  const removeItem = (id: number) =>
    setOrderItems(prev => prev.map(o => o.item.id === id ? { ...o, qty: Math.max(0, o.qty - 1) } : o).filter(o => o.qty > 0));

  const clearOrder = () => setOrderItems([]);

  const handleCheckout = () => {
    const now = new Date();
    const receipt: ReceiptData = {
      receiptNo: `#${String(Math.floor(1000 + Math.random() * 9000))}`,
      table: selectedTable.name,
      player: selectedTable.player,
      date: now.toLocaleDateString("en-US", { year: "numeric", month: "short", day: "numeric" }),
      time: now.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit" }),
      sessionMins,
      tableCost,
      items: [...orderItems],
      itemsTotal,
      subtotal,
      tax,
      grandTotal,
    };
    setShowConfirm(false);
    setReceiptData(receipt);
    setOrderItems([]);
  };

  const catColors: Record<string, string> = { drinks: "#00c8ff", food: "#ff9f43", snacks: "#00ffb4" };

  return (
    <AppLayout>
      <div className="p-6 flex flex-col gap-4 h-full">

        {/* Confirm Modal */}
        {showConfirm && (
          <div
            className="fixed inset-0 z-50 flex items-center justify-center"
            style={{ background: "rgba(0,0,0,0.7)", backdropFilter: "blur(6px)" }}
          >
            <div
              className="rounded-2xl p-6 w-full max-w-sm mx-4 flex flex-col gap-4"
              style={{ background: "#0f1923", border: "1px solid rgba(255,255,255,0.1)" }}
            >
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 flex items-center justify-center rounded-xl" style={{ background: "rgba(0,255,180,0.1)", color: "#00ffb4" }}>
                  <i className="ri-bank-card-line text-xl" />
                </div>
                <h3 className="text-white font-bold text-lg" style={{ fontFamily: "'Rajdhani', sans-serif" }}>Confirm Checkout</h3>
              </div>

              <div className="rounded-xl p-4 flex flex-col gap-2" style={{ background: "rgba(0,0,0,0.3)" }}>
                <div className="flex justify-between text-xs" style={{ color: "#888" }}>
                  <span>Table: {selectedTable.name}</span>
                  <span>{sessionMins}min</span>
                </div>
                <div className="flex justify-between text-xs" style={{ color: "#888" }}>
                  <span>Table time</span><span>${tableCost.toFixed(2)}</span>
                </div>
                <div className="flex justify-between text-xs" style={{ color: "#888" }}>
                  <span>Items ({orderItems.reduce((s,o)=>s+o.qty,0)})</span><span>${itemsTotal.toFixed(2)}</span>
                </div>
                <div className="flex justify-between text-xs" style={{ color: "#888" }}>
                  <span>Tax (8.5%)</span><span>${tax.toFixed(2)}</span>
                </div>
                <div className="flex justify-between text-sm font-bold text-white pt-2" style={{ borderTop: "1px solid rgba(255,255,255,0.08)" }}>
                  <span>Total</span>
                  <span style={{ color: "#00ffb4" }}>${grandTotal.toFixed(2)}</span>
                </div>
              </div>

              <div className="flex gap-3">
                <button
                  onClick={handleCheckout}
                  className="flex-1 py-2.5 rounded-xl text-sm font-semibold cursor-pointer whitespace-nowrap flex items-center justify-center gap-2"
                  style={{ background: "linear-gradient(135deg,#00ffb4,#00c8ff)", color: "#000" }}
                >
                  <i className="ri-receipt-line" /> Confirm & Print Receipt
                </button>
                <button
                  onClick={() => setShowConfirm(false)}
                  className="px-4 py-2.5 rounded-xl text-sm cursor-pointer whitespace-nowrap"
                  style={{ background: "rgba(255,255,255,0.05)", color: "#888" }}
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Receipt Modal */}
        {receiptData && (
          <ReceiptModal data={receiptData} onClose={() => setReceiptData(null)} />
        )}

        {/* Table Selector */}
        <div className="flex items-center gap-3 flex-wrap">
          <span className="text-xs" style={{ color: "#666" }}>Active Table:</span>
          {activeTables.map(t => (
            <button
              key={t.id}
              onClick={() => { setSelectedTable(t); clearOrder(); }}
              className="px-3 py-1.5 rounded-lg text-xs font-medium cursor-pointer transition-all duration-200 whitespace-nowrap"
              style={{
                background: selectedTable.id === t.id ? "rgba(0,255,180,0.15)" : "rgba(255,255,255,0.04)",
                color: selectedTable.id === t.id ? "#00ffb4" : "#666",
                border: `1px solid ${selectedTable.id === t.id ? "rgba(0,255,180,0.3)" : "rgba(255,255,255,0.06)"}`,
              }}
            >
              {t.name}
            </button>
          ))}
        </div>

        {/* POS Split Layout */}
        <div className="flex flex-col lg:flex-row gap-4 flex-1 min-h-0">
          {/* LEFT: Menu */}
          <div className="flex-1 flex flex-col gap-4 min-h-0">
            <div className="rounded-xl p-4 flex flex-col gap-4" style={{ background: "rgba(255,255,255,0.03)", border: "1px solid rgba(255,255,255,0.07)" }}>
              {/* Category Tabs */}
              <div className="flex items-center gap-2 flex-wrap">
                {(["all","drinks","food","snacks"] as const).map(c => (
                  <button
                    key={c}
                    onClick={() => setCategory(c)}
                    className="px-3 py-1.5 rounded-lg text-xs font-medium cursor-pointer transition-all duration-200 whitespace-nowrap capitalize"
                    style={{
                      background: category === c ? "rgba(0,255,180,0.15)" : "rgba(255,255,255,0.04)",
                      color: category === c ? "#00ffb4" : "#666",
                      border: `1px solid ${category === c ? "rgba(0,255,180,0.3)" : "rgba(255,255,255,0.06)"}`,
                    }}
                  >
                    {c === "all" ? "All Items" : c.charAt(0).toUpperCase()+c.slice(1)}
                  </button>
                ))}
              </div>

              {/* Menu Grid */}
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 overflow-y-auto" style={{ maxHeight: "420px" }}>
                {filtered.map(item => {
                  const inOrder = orderItems.find(o => o.item.id === item.id);
                  const accent = catColors[item.category] ?? "#00ffb4";
                  return (
                    <button
                      key={item.id}
                      onClick={() => addItem(item)}
                      className="rounded-xl p-3 flex flex-col items-center gap-2 cursor-pointer transition-all duration-200 relative"
                      style={{
                        background: inOrder ? "rgba(0,255,180,0.08)" : "rgba(255,255,255,0.03)",
                        border: `1px solid ${inOrder ? "rgba(0,255,180,0.25)" : "rgba(255,255,255,0.06)"}`,
                      }}
                      onMouseEnter={e => { (e.currentTarget as HTMLButtonElement).style.transform = "translateY(-2px)"; }}
                      onMouseLeave={e => { (e.currentTarget as HTMLButtonElement).style.transform = "translateY(0)"; }}
                    >
                      {inOrder && (
                        <span
                          className="absolute top-2 right-2 w-5 h-5 flex items-center justify-center rounded-full font-bold text-black"
                          style={{ background: "#00ffb4", fontSize: "10px" }}
                        >
                          {inOrder.qty}
                        </span>
                      )}
                      <div className="w-10 h-10 flex items-center justify-center rounded-xl" style={{ background: `${accent}18`, color: accent }}>
                        <i className={`${item.icon} text-xl`} />
                      </div>
                      <p className="text-xs text-white font-medium text-center leading-tight">{item.name}</p>
                      <p className="text-xs font-bold" style={{ color: accent }}>${item.price.toFixed(2)}</p>
                    </button>
                  );
                })}
              </div>
            </div>
          </div>

          {/* RIGHT: Order Summary */}
          <div className="w-full lg:w-80 flex flex-col gap-4">
            <div className="rounded-xl p-4 flex flex-col gap-4 flex-1" style={{ background: "rgba(255,255,255,0.03)", border: "1px solid rgba(255,255,255,0.07)" }}>
              <div className="flex items-center justify-between">
                <h3 className="text-white font-bold text-sm" style={{ fontFamily: "'Rajdhani', sans-serif" }}>Current Order</h3>
                <span className="text-xs px-2 py-0.5 rounded-full" style={{ background: "rgba(255,77,109,0.12)", color: "#ff4d6d" }}>
                  <i className="ri-time-line mr-1" />{sessionMins}m
                </span>
              </div>

              {/* Table billing */}
              <div className="rounded-lg p-3 flex items-center justify-between" style={{ background: "rgba(0,0,0,0.3)" }}>
                <div>
                  <p className="text-xs text-white font-medium">{selectedTable.name}</p>
                  <p className="text-xs mt-0.5" style={{ color: "#666" }}>{sessionMins}min @ ${TABLE_RATE}/hr</p>
                </div>
                <span className="text-sm font-bold" style={{ color: "#00ffb4" }}>${tableCost.toFixed(2)}</span>
              </div>

              {/* Order Items */}
              <div className="flex flex-col gap-2 flex-1 overflow-y-auto" style={{ maxHeight: "200px" }}>
                {orderItems.length === 0 ? (
                  <div className="flex flex-col items-center justify-center py-8 gap-2">
                    <div className="w-10 h-10 flex items-center justify-center rounded-full" style={{ background: "rgba(255,255,255,0.04)", color: "#444" }}>
                      <i className="ri-shopping-cart-2-line text-xl" />
                    </div>
                    <p className="text-xs" style={{ color: "#444" }}>No items added yet</p>
                  </div>
                ) : orderItems.map(o => (
                  <div key={o.item.id} className="flex items-center gap-2 px-2 py-1.5 rounded-lg" style={{ background: "rgba(255,255,255,0.03)" }}>
                    <div className="w-6 h-6 flex items-center justify-center rounded" style={{ background: "rgba(0,255,180,0.1)", color: "#00ffb4" }}>
                      <i className={`${o.item.icon} text-xs`} />
                    </div>
                    <span className="flex-1 text-xs text-gray-300 truncate">{o.item.name}</span>
                    <div className="flex items-center gap-1">
                      <button onClick={() => removeItem(o.item.id)} className="w-5 h-5 flex items-center justify-center rounded cursor-pointer text-xs" style={{ background: "rgba(255,77,109,0.15)", color: "#ff4d6d" }}>-</button>
                      <span className="text-xs text-white w-4 text-center">{o.qty}</span>
                      <button onClick={() => addItem(o.item)} className="w-5 h-5 flex items-center justify-center rounded cursor-pointer text-xs" style={{ background: "rgba(0,255,180,0.15)", color: "#00ffb4" }}>+</button>
                    </div>
                    <span className="text-xs font-medium text-white w-12 text-right">${(o.item.price * o.qty).toFixed(2)}</span>
                  </div>
                ))}
              </div>

              {/* Totals */}
              <div className="flex flex-col gap-1.5 pt-3" style={{ borderTop: "1px solid rgba(255,255,255,0.06)" }}>
                <div className="flex justify-between text-xs" style={{ color: "#666" }}>
                  <span>Table time</span><span>${tableCost.toFixed(2)}</span>
                </div>
                <div className="flex justify-between text-xs" style={{ color: "#666" }}>
                  <span>Items ({orderItems.reduce((s,o)=>s+o.qty,0)})</span><span>${itemsTotal.toFixed(2)}</span>
                </div>
                <div className="flex justify-between text-xs" style={{ color: "#666" }}>
                  <span>Tax (8.5%)</span><span>${tax.toFixed(2)}</span>
                </div>
                <div className="flex justify-between text-sm font-bold text-white pt-1.5" style={{ borderTop: "1px solid rgba(255,255,255,0.06)" }}>
                  <span>Total</span><span style={{ color: "#00ffb4" }}>${grandTotal.toFixed(2)}</span>
                </div>
              </div>

              {/* Actions */}
              <div className="flex flex-col gap-2">
                <button
                  onClick={() => setShowConfirm(true)}
                  className="w-full py-3 rounded-xl text-sm font-bold cursor-pointer transition-all duration-200 whitespace-nowrap flex items-center justify-center gap-2"
                  style={{ background: "linear-gradient(135deg,#00ffb4,#00c8ff)", color: "#000" }}
                >
                  <i className="ri-receipt-line" /> Checkout — ${grandTotal.toFixed(2)}
                </button>
                {orderItems.length > 0 && (
                  <button
                    onClick={clearOrder}
                    className="w-full py-2 rounded-xl text-xs cursor-pointer whitespace-nowrap"
                    style={{ background: "rgba(255,77,109,0.1)", color: "#ff4d6d", border: "1px solid rgba(255,77,109,0.2)" }}
                  >
                    Clear Order
                  </button>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
