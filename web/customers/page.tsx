import { useState } from "react";
import AppLayout from "@/components/feature/AppLayout";
import { customersData, Customer } from "@/mocks/customersData";

const statusCfg = {
  vip:     { color: "#ff9f43", bg: "rgba(255,159,67,0.12)",  label: "VIP"     },
  regular: { color: "#00c8ff", bg: "rgba(0,200,255,0.12)",   label: "Regular" },
  new:     { color: "#00ffb4", bg: "rgba(0,255,180,0.12)",   label: "New"     },
};

function HistoryModal({ customer, onClose }: { customer: Customer; onClose: () => void }) {
  const st = statusCfg[customer.status];
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "rgba(0,0,0,0.75)", backdropFilter: "blur(6px)" }} onClick={onClose}>
      <div className="rounded-2xl w-full max-w-lg mx-4 overflow-hidden flex flex-col" style={{ background: "#0f1923", border: "1px solid rgba(255,255,255,0.1)", maxHeight: "85vh" }} onClick={(e) => e.stopPropagation()}>
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4" style={{ borderBottom: "1px solid rgba(255,255,255,0.07)" }}>
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm" style={{ background: "linear-gradient(135deg,#00ffb4,#00c8ff)", color: "#000" }}>
              {customer.name.split(" ").map(n => n[0]).join("")}
            </div>
            <div>
              <p className="text-white font-bold text-sm">{customer.name}</p>
              <p className="text-xs" style={{ color: "#555" }}>{customer.email}</p>
            </div>
          </div>
          <button onClick={onClose} className="w-8 h-8 flex items-center justify-center rounded-lg cursor-pointer" style={{ background: "rgba(255,255,255,0.05)", color: "#888" }}>
            <i className="ri-close-line" />
          </button>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-3 gap-3 p-4" style={{ borderBottom: "1px solid rgba(255,255,255,0.07)" }}>
          {[
            { label: "Total Visits", value: customer.visits, color: "#00c8ff" },
            { label: "Total Spent",  value: `$${customer.totalSpent.toFixed(2)}`, color: "#00ffb4" },
            { label: "Status",       value: st.label, color: st.color },
          ].map(s => (
            <div key={s.label} className="rounded-xl p-3 text-center" style={{ background: "rgba(255,255,255,0.03)" }}>
              <p className="text-lg font-bold" style={{ color: s.color, fontFamily: "'Rajdhani', sans-serif" }}>{s.value}</p>
              <p className="text-xs mt-0.5" style={{ color: "#555" }}>{s.label}</p>
            </div>
          ))}
        </div>

        {/* Visit History */}
        <div className="flex flex-col gap-1 p-4 overflow-y-auto">
          <p className="text-xs font-semibold mb-2" style={{ color: "#666", letterSpacing: "0.08em" }}>VISIT HISTORY</p>
          {customer.history.map((h, i) => (
            <div key={i} className="flex items-center gap-3 px-3 py-2.5 rounded-lg" style={{ background: "rgba(255,255,255,0.03)", borderBottom: "1px solid rgba(255,255,255,0.04)" }}>
              <div className="w-8 h-8 flex items-center justify-center rounded-lg flex-shrink-0" style={{ background: "rgba(0,255,180,0.08)", color: "#00ffb4" }}>
                <i className="ri-billiards-line text-sm" />
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-xs text-white font-medium">{h.table}</p>
                <p className="text-xs mt-0.5" style={{ color: "#555" }}>{h.date} · {h.duration}</p>
              </div>
              <span className="text-sm font-bold" style={{ color: "#00ffb4", fontFamily: "'Rajdhani', sans-serif" }}>${h.spent.toFixed(2)}</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

export default function CustomersPage() {
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState<"all" | "vip" | "regular" | "new">("all");
  const [selected, setSelected] = useState<Customer | null>(null);
  const [sortBy, setSortBy] = useState<"name" | "visits" | "spent">("spent");

  const filtered = customersData
    .filter(c => {
      const matchSearch = c.name.toLowerCase().includes(search.toLowerCase()) || c.phone.includes(search) || c.email.toLowerCase().includes(search.toLowerCase());
      const matchStatus = statusFilter === "all" || c.status === statusFilter;
      return matchSearch && matchStatus;
    })
    .sort((a, b) => {
      if (sortBy === "name")   return a.name.localeCompare(b.name);
      if (sortBy === "visits") return b.visits - a.visits;
      return b.totalSpent - a.totalSpent;
    });

  const counts = { all: customersData.length, vip: customersData.filter(c=>c.status==="vip").length, regular: customersData.filter(c=>c.status==="regular").length, new: customersData.filter(c=>c.status==="new").length };

  return (
    <AppLayout>
      <div className="p-6 flex flex-col gap-6">
        {/* KPI Row */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          {[
            { label: "Total Customers", value: customersData.length, color: "#00ffb4", icon: "ri-group-line" },
            { label: "VIP Members",     value: counts.vip,           color: "#ff9f43", icon: "ri-vip-crown-line" },
            { label: "Total Revenue",   value: `$${customersData.reduce((s,c)=>s+c.totalSpent,0).toLocaleString("en",{minimumFractionDigits:2})}`, color: "#00c8ff", icon: "ri-money-dollar-circle-line" },
            { label: "Avg Spent",       value: `$${(customersData.reduce((s,c)=>s+c.totalSpent,0)/customersData.length).toFixed(2)}`, color: "#ff4d6d", icon: "ri-bar-chart-line" },
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

        {/* Filters */}
        <div className="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
          <div className="flex items-center gap-2 flex-wrap">
            {(["all","vip","regular","new"] as const).map(f => (
              <button key={f} onClick={() => setStatusFilter(f)} className="px-3 py-1.5 rounded-lg text-xs font-medium cursor-pointer transition-all duration-200 whitespace-nowrap capitalize" style={{ background: statusFilter === f ? "rgba(0,255,180,0.15)" : "rgba(255,255,255,0.04)", color: statusFilter === f ? "#00ffb4" : "#666", border: `1px solid ${statusFilter === f ? "rgba(0,255,180,0.3)" : "rgba(255,255,255,0.06)"}` }}>
                {f === "all" ? `All (${counts.all})` : `${f.toUpperCase()} (${counts[f]})`}
              </button>
            ))}
          </div>
          <div className="flex items-center gap-2">
            <div className="relative flex items-center">
              <div className="absolute left-3 w-4 h-4 flex items-center justify-center" style={{ color: "#555" }}>
                <i className="ri-search-line text-sm" />
              </div>
              <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search customers..." className="pl-9 pr-4 py-2 text-sm rounded-lg outline-none text-white" style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)", width: "220px" }} />
            </div>
            <select value={sortBy} onChange={e => setSortBy(e.target.value as "name"|"visits"|"spent")} className="px-3 py-2 text-sm rounded-lg outline-none cursor-pointer" style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)", color: "#aaa" }}>
              <option value="spent" style={{ background: "#0f1923" }}>Sort: Top Spent</option>
              <option value="visits" style={{ background: "#0f1923" }}>Sort: Most Visits</option>
              <option value="name" style={{ background: "#0f1923" }}>Sort: Name A-Z</option>
            </select>
          </div>
        </div>

        {/* Table */}
        <div className="rounded-xl overflow-hidden" style={{ background: "rgba(255,255,255,0.03)", border: "1px solid rgba(255,255,255,0.07)" }}>
          <table className="w-full">
            <thead>
              <tr style={{ borderBottom: "1px solid rgba(255,255,255,0.07)" }}>
                {["Customer", "Phone", "Visits", "Total Spent", "Last Visit", "Status", ""].map(h => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style={{ color: "#555" }}>{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {filtered.length === 0 ? (
                <tr><td colSpan={7} className="px-4 py-12 text-center text-sm" style={{ color: "#444" }}>
                  <div className="flex flex-col items-center gap-2">
                    <div className="w-10 h-10 flex items-center justify-center rounded-full" style={{ background: "rgba(255,255,255,0.04)", color: "#333" }}><i className="ri-user-search-line text-xl" /></div>
                    No customers found
                  </div>
                </td></tr>
              ) : filtered.map((c, i) => {
                const st = statusCfg[c.status];
                return (
                  <tr key={c.id} className="transition-all duration-150 cursor-default" style={{ borderBottom: "1px solid rgba(255,255,255,0.04)" }}
                    onMouseEnter={e => { (e.currentTarget as HTMLTableRowElement).style.background = "rgba(255,255,255,0.03)"; }}
                    onMouseLeave={e => { (e.currentTarget as HTMLTableRowElement).style.background = "transparent"; }}>
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" style={{ background: i % 2 === 0 ? "linear-gradient(135deg,#00ffb4,#00c8ff)" : "linear-gradient(135deg,#ff9f43,#ff4d6d)", color: "#000" }}>
                          {c.name.split(" ").map(n => n[0]).join("")}
                        </div>
                        <div>
                          <p className="text-sm text-white font-medium">{c.name}</p>
                          <p className="text-xs" style={{ color: "#555" }}>{c.email}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-3 text-sm" style={{ color: "#888" }}>{c.phone}</td>
                    <td className="px-4 py-3">
                      <span className="text-sm font-bold text-white" style={{ fontFamily: "'Rajdhani', sans-serif" }}>{c.visits}</span>
                    </td>
                    <td className="px-4 py-3">
                      <span className="text-sm font-bold" style={{ color: "#00ffb4", fontFamily: "'Rajdhani', sans-serif" }}>${c.totalSpent.toFixed(2)}</span>
                    </td>
                    <td className="px-4 py-3 text-sm" style={{ color: "#888" }}>{c.lastVisit}</td>
                    <td className="px-4 py-3">
                      <span className="text-xs px-2 py-0.5 rounded-full" style={{ background: st.bg, color: st.color }}>{st.label}</span>
                    </td>
                    <td className="px-4 py-3">
                      <button onClick={() => setSelected(c)} className="px-3 py-1.5 rounded-lg text-xs cursor-pointer transition-all duration-200 whitespace-nowrap" style={{ background: "rgba(0,200,255,0.1)", color: "#00c8ff", border: "1px solid rgba(0,200,255,0.2)" }}
                        onMouseEnter={e => { (e.currentTarget as HTMLButtonElement).style.background = "rgba(0,200,255,0.2)"; }}
                        onMouseLeave={e => { (e.currentTarget as HTMLButtonElement).style.background = "rgba(0,200,255,0.1)"; }}>
                        <i className="ri-eye-line mr-1" />View History
                      </button>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>
      {selected && <HistoryModal customer={selected} onClose={() => setSelected(null)} />}
    </AppLayout>
  );
}
