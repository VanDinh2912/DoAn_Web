import { useState } from "react";
import AppLayout from "@/components/feature/AppLayout";
import { inventoryData, InventoryItem, InventoryCategory } from "@/mocks/inventoryData";

const catColors: Record<InventoryCategory, string> = {
  drinks:      "#00c8ff",
  food:        "#ff9f43",
  snacks:      "#00ffb4",
  equipment:   "#a78bfa",
  accessories: "#fb7185",
};

const catIcons: Record<InventoryCategory, string> = {
  drinks:      "ri-cup-line",
  food:        "ri-restaurant-line",
  snacks:      "ri-leaf-line",
  equipment:   "ri-tools-line",
  accessories: "ri-price-tag-3-line",
};

function StockBadge({ qty, min }: { qty: number; min: number }) {
  if (qty === 0)       return <span className="text-xs px-2 py-0.5 rounded-full" style={{ background: "rgba(255,77,109,0.15)", color: "#ff4d6d" }}>Out of Stock</span>;
  if (qty <= min * 0.3) return <span className="text-xs px-2 py-0.5 rounded-full" style={{ background: "rgba(255,77,109,0.15)", color: "#ff4d6d" }}>Critical</span>;
  if (qty < min)       return <span className="text-xs px-2 py-0.5 rounded-full" style={{ background: "rgba(255,159,67,0.15)", color: "#ff9f43" }}>Low Stock</span>;
  return <span className="text-xs px-2 py-0.5 rounded-full" style={{ background: "rgba(0,255,180,0.12)", color: "#00ffb4" }}>In Stock</span>;
}

const emptyItem: Omit<InventoryItem, "id"> = { name: "", category: "drinks", quantity: 0, minStock: 10, price: 0, costPrice: 0, supplier: "", lastRestocked: new Date().toISOString().split("T")[0] };

function ItemModal({ item, onClose, onSave }: { item: Partial<InventoryItem> & { id?: number }; onClose: () => void; onSave: (item: InventoryItem) => void }) {
  const isEdit = !!item.id;
  const [form, setForm] = useState<Omit<InventoryItem,"id">>({
    name: item.name ?? "", category: item.category ?? "drinks", quantity: item.quantity ?? 0,
    minStock: item.minStock ?? 10, price: item.price ?? 0, costPrice: item.costPrice ?? 0,
    supplier: item.supplier ?? "", lastRestocked: item.lastRestocked ?? new Date().toISOString().split("T")[0],
  });

  const handleSave = () => {
    if (!form.name.trim()) return;
    onSave({ ...form, id: item.id ?? Date.now() });
    onClose();
  };

  const field = (label: string, key: keyof typeof form, type = "text") => (
    <div className="flex flex-col gap-1">
      <label className="text-xs" style={{ color: "#888" }}>{label}</label>
      {key === "category" ? (
        <select value={form.category} onChange={e => setForm(f => ({ ...f, category: e.target.value as InventoryCategory }))} className="px-3 py-2 rounded-lg text-sm outline-none text-white" style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.1)" }}>
          {(["drinks","food","snacks","equipment","accessories"] as InventoryCategory[]).map(c => <option key={c} value={c} style={{ background: "#0f1923" }}>{c.charAt(0).toUpperCase()+c.slice(1)}</option>)}
        </select>
      ) : (
        <input type={type} value={String(form[key])} onChange={e => setForm(f => ({ ...f, [key]: type === "number" ? parseFloat(e.target.value)||0 : e.target.value }))} className="px-3 py-2 rounded-lg text-sm outline-none text-white" style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.1)" }} />
      )}
    </div>
  );

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "rgba(0,0,0,0.75)", backdropFilter: "blur(6px)" }} onClick={onClose}>
      <div className="rounded-2xl p-6 w-full max-w-md mx-4 flex flex-col gap-4" style={{ background: "#0f1923", border: "1px solid rgba(255,255,255,0.1)" }} onClick={e => e.stopPropagation()}>
        <div className="flex items-center justify-between">
          <h3 className="text-white font-bold text-lg" style={{ fontFamily: "'Rajdhani', sans-serif" }}>{isEdit ? "Edit Item" : "Add New Item"}</h3>
          <button onClick={onClose} className="w-8 h-8 flex items-center justify-center rounded-lg cursor-pointer" style={{ background: "rgba(255,255,255,0.05)", color: "#888" }}><i className="ri-close-line" /></button>
        </div>
        <div className="grid grid-cols-2 gap-3">
          <div className="col-span-2">{field("Item Name", "name")}</div>
          {field("Category", "category")}
          {field("Supplier", "supplier")}
          {field("Quantity", "quantity", "number")}
          {field("Min Stock", "minStock", "number")}
          {field("Sale Price ($)", "price", "number")}
          {field("Cost Price ($)", "costPrice", "number")}
          <div className="col-span-2">{field("Last Restocked", "lastRestocked", "date")}</div>
        </div>
        <div className="flex gap-3 pt-2">
          <button onClick={handleSave} className="flex-1 py-2.5 rounded-xl text-sm font-semibold cursor-pointer whitespace-nowrap" style={{ background: "linear-gradient(135deg,#00ffb4,#00c8ff)", color: "#000" }}>
            {isEdit ? "Save Changes" : "Add Item"}
          </button>
          <button onClick={onClose} className="px-4 py-2.5 rounded-xl text-sm cursor-pointer whitespace-nowrap" style={{ background: "rgba(255,255,255,0.05)", color: "#888" }}>Cancel</button>
        </div>
      </div>
    </div>
  );
}

function DeleteConfirm({ item, onClose, onDelete }: { item: InventoryItem; onClose: () => void; onDelete: () => void }) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "rgba(0,0,0,0.75)", backdropFilter: "blur(6px)" }} onClick={onClose}>
      <div className="rounded-2xl p-6 w-full max-w-sm mx-4 flex flex-col gap-4" style={{ background: "#0f1923", border: "1px solid rgba(255,77,109,0.2)" }} onClick={e => e.stopPropagation()}>
        <div className="w-12 h-12 flex items-center justify-center rounded-full mx-auto" style={{ background: "rgba(255,77,109,0.12)", color: "#ff4d6d" }}><i className="ri-delete-bin-line text-2xl" /></div>
        <div className="text-center">
          <h3 className="text-white font-bold text-base">Delete Item?</h3>
          <p className="text-sm mt-1" style={{ color: "#888" }}>Remove <span className="text-white">{item.name}</span> from inventory?</p>
        </div>
        <div className="flex gap-3">
          <button onClick={() => { onDelete(); onClose(); }} className="flex-1 py-2.5 rounded-xl text-sm font-semibold cursor-pointer whitespace-nowrap" style={{ background: "rgba(255,77,109,0.15)", color: "#ff4d6d", border: "1px solid rgba(255,77,109,0.3)" }}>Delete</button>
          <button onClick={onClose} className="flex-1 py-2.5 rounded-xl text-sm cursor-pointer whitespace-nowrap" style={{ background: "rgba(255,255,255,0.05)", color: "#888" }}>Cancel</button>
        </div>
      </div>
    </div>
  );
}

export default function InventoryPage() {
  const [items, setItems] = useState<InventoryItem[]>(inventoryData);
  const [search, setSearch] = useState("");
  const [catFilter, setCatFilter] = useState<"all" | InventoryCategory>("all");
  const [modalItem, setModalItem] = useState<Partial<InventoryItem> | null>(null);
  const [deleteItem, setDeleteItem] = useState<InventoryItem | null>(null);
  const [toast, setToast] = useState("");

  const showToast = (msg: string) => { setToast(msg); setTimeout(() => setToast(""), 2500); };

  const filtered = items.filter(i => {
    const matchSearch = i.name.toLowerCase().includes(search.toLowerCase()) || i.supplier.toLowerCase().includes(search.toLowerCase());
    const matchCat = catFilter === "all" || i.category === catFilter;
    return matchSearch && matchCat;
  });

  const lowStockCount = items.filter(i => i.quantity < i.minStock).length;
  const categories = ["all","drinks","food","snacks","equipment","accessories"] as const;

  const handleSave = (item: InventoryItem) => {
    setItems(prev => prev.find(i => i.id === item.id) ? prev.map(i => i.id === item.id ? item : i) : [...prev, item]);
    showToast(item.id && items.find(i=>i.id===item.id) ? "Item updated!" : "Item added!");
  };

  return (
    <AppLayout>
      <div className="p-6 flex flex-col gap-6">
        {toast && (
          <div className="fixed top-20 right-6 z-50 px-5 py-3 rounded-xl text-sm font-semibold flex items-center gap-2" style={{ background: "linear-gradient(135deg,#00ffb4,#00c8ff)", color: "#000" }}>
            <i className="ri-checkbox-circle-line" /> {toast}
          </div>
        )}

        {/* KPI Row */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          {[
            { label: "Total Items",    value: items.length,      color: "#00ffb4", icon: "ri-archive-line" },
            { label: "Low Stock",      value: lowStockCount,     color: "#ff9f43", icon: "ri-alert-line" },
            { label: "Out of Stock",   value: items.filter(i=>i.quantity===0).length, color: "#ff4d6d", icon: "ri-close-circle-line" },
            { label: "Total Value",    value: `$${items.reduce((s,i)=>s+i.quantity*i.costPrice,0).toFixed(0)}`, color: "#00c8ff", icon: "ri-money-dollar-circle-line" },
          ].map(k => (
            <div key={k.label} className="rounded-xl p-4 flex items-center gap-3" style={{ background: "rgba(255,255,255,0.03)", border: "1px solid rgba(255,255,255,0.07)" }}>
              <div className="w-10 h-10 flex items-center justify-center rounded-xl flex-shrink-0" style={{ background: `${k.color}18`, color: k.color }}>
                <i className={`${k.icon} text-lg`} />
              </div>
              <div>
                <p className="text-lg font-bold text-white" style={{ fontFamily: "'Rajdhani', sans-serif" }}>{k.value}</p>
                <p className="text-xs" style={{ color: "#555" }}>{k.label}</p>
              </div>
            </div>
          ))}
        </div>

        {/* Low Stock Warning */}
        {lowStockCount > 0 && (
          <div className="rounded-xl px-4 py-3 flex items-center gap-3" style={{ background: "rgba(255,159,67,0.08)", border: "1px solid rgba(255,159,67,0.25)" }}>
            <div className="w-8 h-8 flex items-center justify-center rounded-lg flex-shrink-0" style={{ background: "rgba(255,159,67,0.15)", color: "#ff9f43" }}>
              <i className="ri-alert-line" />
            </div>
            <p className="text-sm" style={{ color: "#ff9f43" }}>
              <strong>{lowStockCount} items</strong> are below minimum stock level and need restocking.
            </p>
          </div>
        )}

        {/* Controls */}
        <div className="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
          <div className="flex items-center gap-2 flex-wrap">
            {categories.map(c => (
              <button key={c} onClick={() => setCatFilter(c)} className="px-3 py-1.5 rounded-lg text-xs font-medium cursor-pointer transition-all duration-200 whitespace-nowrap capitalize" style={{ background: catFilter === c ? "rgba(0,255,180,0.15)" : "rgba(255,255,255,0.04)", color: catFilter === c ? "#00ffb4" : "#666", border: `1px solid ${catFilter === c ? "rgba(0,255,180,0.3)" : "rgba(255,255,255,0.06)"}` }}>
                {c === "all" ? "All" : c.charAt(0).toUpperCase()+c.slice(1)}
              </button>
            ))}
          </div>
          <div className="flex items-center gap-2">
            <div className="relative flex items-center">
              <div className="absolute left-3 w-4 h-4 flex items-center justify-center" style={{ color: "#555" }}>
                <i className="ri-search-line text-sm" />
              </div>
              <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search items..." className="pl-9 pr-4 py-2 text-sm rounded-lg outline-none text-white" style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)", width: "200px" }} />
            </div>
            <button onClick={() => setModalItem(emptyItem)} className="px-4 py-2 rounded-lg text-sm font-semibold cursor-pointer transition-all duration-200 whitespace-nowrap flex items-center gap-2" style={{ background: "linear-gradient(135deg,#00ffb4,#00c8ff)", color: "#000" }}>
              <i className="ri-add-line" /> Add Item
            </button>
          </div>
        </div>

        {/* Table */}
        <div className="rounded-xl overflow-hidden" style={{ background: "rgba(255,255,255,0.03)", border: "1px solid rgba(255,255,255,0.07)" }}>
          <table className="w-full">
            <thead>
              <tr style={{ borderBottom: "1px solid rgba(255,255,255,0.07)" }}>
                {["Item", "Category", "Quantity", "Min Stock", "Sale Price", "Cost Price", "Supplier", "Status", "Actions"].map(h => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style={{ color: "#555" }}>{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {filtered.length === 0 ? (
                <tr><td colSpan={9} className="px-4 py-12 text-center text-sm" style={{ color: "#444" }}>
                  <div className="flex flex-col items-center gap-2">
                    <div className="w-10 h-10 flex items-center justify-center rounded-full" style={{ background: "rgba(255,255,255,0.04)", color: "#333" }}><i className="ri-archive-line text-xl" /></div>
                    No items found
                  </div>
                </td></tr>
              ) : filtered.map(item => {
                const color = catColors[item.category];
                const icon = catIcons[item.category];
                const isLow = item.quantity < item.minStock;
                return (
                  <tr key={item.id} className="transition-all duration-150" style={{ borderBottom: "1px solid rgba(255,255,255,0.04)", background: isLow ? "rgba(255,159,67,0.02)" : "transparent" }}
                    onMouseEnter={e => { (e.currentTarget as HTMLTableRowElement).style.background = "rgba(255,255,255,0.03)"; }}
                    onMouseLeave={e => { (e.currentTarget as HTMLTableRowElement).style.background = isLow ? "rgba(255,159,67,0.02)" : "transparent"; }}>
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-2">
                        <div className="w-7 h-7 flex items-center justify-center rounded-lg flex-shrink-0" style={{ background: `${color}18`, color }}>
                          <i className={`${icon} text-sm`} />
                        </div>
                        <span className="text-sm text-white font-medium">{item.name}</span>
                      </div>
                    </td>
                    <td className="px-4 py-3"><span className="text-xs px-2 py-0.5 rounded-full capitalize" style={{ background: `${color}18`, color }}>{item.category}</span></td>
                    <td className="px-4 py-3">
                      <span className="text-sm font-bold" style={{ color: item.quantity < item.minStock ? "#ff9f43" : "#fff", fontFamily: "'Rajdhani', sans-serif" }}>{item.quantity}</span>
                    </td>
                    <td className="px-4 py-3 text-sm" style={{ color: "#666" }}>{item.minStock}</td>
                    <td className="px-4 py-3 text-sm font-medium" style={{ color: "#00ffb4" }}>${item.price.toFixed(2)}</td>
                    <td className="px-4 py-3 text-sm" style={{ color: "#888" }}>${item.costPrice.toFixed(2)}</td>
                    <td className="px-4 py-3 text-sm" style={{ color: "#888" }}>{item.supplier}</td>
                    <td className="px-4 py-3"><StockBadge qty={item.quantity} min={item.minStock} /></td>
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-1">
                        <button onClick={() => setModalItem(item)} className="w-7 h-7 flex items-center justify-center rounded-lg cursor-pointer transition-all duration-150" style={{ background: "rgba(0,200,255,0.1)", color: "#00c8ff" }}
                          onMouseEnter={e => { (e.currentTarget as HTMLButtonElement).style.background = "rgba(0,200,255,0.2)"; }}
                          onMouseLeave={e => { (e.currentTarget as HTMLButtonElement).style.background = "rgba(0,200,255,0.1)"; }}>
                          <i className="ri-edit-line text-xs" />
                        </button>
                        <button onClick={() => setDeleteItem(item)} className="w-7 h-7 flex items-center justify-center rounded-lg cursor-pointer transition-all duration-150" style={{ background: "rgba(255,77,109,0.1)", color: "#ff4d6d" }}
                          onMouseEnter={e => { (e.currentTarget as HTMLButtonElement).style.background = "rgba(255,77,109,0.2)"; }}
                          onMouseLeave={e => { (e.currentTarget as HTMLButtonElement).style.background = "rgba(255,77,109,0.1)"; }}>
                          <i className="ri-delete-bin-line text-xs" />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>

      {modalItem && <ItemModal item={modalItem} onClose={() => setModalItem(null)} onSave={handleSave} />}
      {deleteItem && <DeleteConfirm item={deleteItem} onClose={() => setDeleteItem(null)} onDelete={() => { setItems(prev => prev.filter(i => i.id !== deleteItem.id)); showToast("Item deleted."); }} />}
    </AppLayout>
  );
}
