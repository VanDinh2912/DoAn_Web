import { useState } from "react";
import AppLayout from "@/components/feature/AppLayout";
import { monthlyRevenue, bestSellers, tableStats, summaryKpis } from "@/mocks/reportsData";

const DATE_RANGES = ["Last 7 Days", "Last 30 Days", "Last 3 Months", "Last 6 Months", "This Year"] as const;
type DateRange = typeof DATE_RANGES[number];

function RevenueChart({ range }: { range: DateRange }) {
  const data = monthlyRevenue;
  const maxVal = Math.max(...data.map(d => d.tableRevenue + d.itemRevenue));

  return (
    <div className="rounded-xl p-5 flex flex-col gap-4" style={{ background: "rgba(255,255,255,0.03)", border: "1px solid rgba(255,255,255,0.07)" }}>
      <div className="flex items-center justify-between">
        <div>
          <h3 className="text-white font-semibold text-sm" style={{ fontFamily: "'Rajdhani', sans-serif", letterSpacing: "0.04em" }}>Revenue Breakdown</h3>
          <p className="text-xs mt-0.5" style={{ color: "#555" }}>{range}</p>
        </div>
        <div className="flex items-center gap-4">
          {[{ color: "#00ffb4", label: "Table Time" }, { color: "#00c8ff", label: "Items" }].map(l => (
            <div key={l.label} className="flex items-center gap-1.5">
              <div className="w-2 h-2 rounded-full" style={{ background: l.color }} />
              <span className="text-xs" style={{ color: "#666" }}>{l.label}</span>
            </div>
          ))}
        </div>
      </div>

      <div className="flex items-end gap-3 h-44">
        {data.map((d, i) => {
          const totalH = ((d.tableRevenue + d.itemRevenue) / maxVal) * 100;
          const tableH = (d.tableRevenue / (d.tableRevenue + d.itemRevenue)) * totalH;
          const itemH = totalH - tableH;
          const isLast = i === data.length - 1;
          return (
            <div key={d.month} className="flex-1 flex flex-col items-center gap-1 group">
              <div className="relative w-full flex flex-col items-center justify-end" style={{ height: "160px" }}>
                <div className="w-full flex flex-col rounded-t-md overflow-hidden cursor-pointer transition-all duration-200 group-hover:brightness-125" style={{ height: `${totalH}%`, minHeight: "4px" }}>
                  <div style={{ flex: `0 0 ${itemH}%`, background: isLast ? "rgba(0,200,255,0.9)" : "rgba(0,200,255,0.5)" }} />
                  <div style={{ flex: `0 0 ${tableH}%`, background: isLast ? "#00ffb4" : "rgba(0,255,180,0.5)" }} />
                </div>
              </div>
              <span className="text-xs" style={{ color: isLast ? "#00ffb4" : "#555" }}>{d.month}</span>
            </div>
          );
        })}
      </div>

      <div className="grid grid-cols-3 gap-3 pt-3" style={{ borderTop: "1px solid rgba(255,255,255,0.05)" }}>
        {[
          { label: "Total Revenue",  value: `$${data.reduce((s,d)=>s+d.tableRevenue+d.itemRevenue,0).toLocaleString()}`, color: "#00ffb4" },
          { label: "Table Revenue",  value: `$${data.reduce((s,d)=>s+d.tableRevenue,0).toLocaleString()}`,              color: "#00ffb4" },
          { label: "Items Revenue",  value: `$${data.reduce((s,d)=>s+d.itemRevenue,0).toLocaleString()}`,               color: "#00c8ff" },
        ].map(s => (
          <div key={s.label} className="text-center">
            <p className="text-base font-bold" style={{ color: s.color, fontFamily: "'Rajdhani', sans-serif" }}>{s.value}</p>
            <p className="text-xs mt-0.5" style={{ color: "#555" }}>{s.label}</p>
          </div>
        ))}
      </div>
    </div>
  );
}

function BestSellers() {
  const maxSold = Math.max(...bestSellers.map(b => b.sold));
  return (
    <div className="rounded-xl p-5 flex flex-col gap-4" style={{ background: "rgba(255,255,255,0.03)", border: "1px solid rgba(255,255,255,0.07)" }}>
      <div>
        <h3 className="text-white font-semibold text-sm" style={{ fontFamily: "'Rajdhani', sans-serif", letterSpacing: "0.04em" }}>Best-Selling Items</h3>
        <p className="text-xs mt-0.5" style={{ color: "#555" }}>By units sold</p>
      </div>
      <div className="flex flex-col gap-3">
        {bestSellers.map((item, i) => (
          <div key={item.name} className="flex items-center gap-3">
            <span className="text-xs w-4 text-right flex-shrink-0 font-bold" style={{ color: i < 3 ? "#ff9f43" : "#444" }}>#{i+1}</span>
            <div className="w-7 h-7 flex items-center justify-center rounded-lg flex-shrink-0" style={{ background: `${item.color}18`, color: item.color }}>
              <i className={`${item.icon} text-sm`} />
            </div>
            <div className="flex-1 min-w-0">
              <div className="flex items-center justify-between mb-1">
                <span className="text-xs text-white font-medium truncate">{item.name}</span>
                <span className="text-xs ml-2 flex-shrink-0" style={{ color: "#666" }}>{item.sold} sold</span>
              </div>
              <div className="w-full rounded-full overflow-hidden" style={{ background: "rgba(255,255,255,0.05)", height: "5px" }}>
                <div className="h-full rounded-full" style={{ width: `${(item.sold/maxSold)*100}%`, background: item.color }} />
              </div>
            </div>
            <span className="text-xs font-bold flex-shrink-0" style={{ color: "#00ffb4", fontFamily: "'Rajdhani', sans-serif" }}>${item.revenue.toFixed(0)}</span>
          </div>
        ))}
      </div>
    </div>
  );
}

function TableStatsPanel() {
  const maxRev = Math.max(...tableStats.map(t => t.revenue));
  return (
    <div className="rounded-xl p-5 flex flex-col gap-4" style={{ background: "rgba(255,255,255,0.03)", border: "1px solid rgba(255,255,255,0.07)" }}>
      <div>
        <h3 className="text-white font-semibold text-sm" style={{ fontFamily: "'Rajdhani', sans-serif", letterSpacing: "0.04em" }}>Table Performance</h3>
        <p className="text-xs mt-0.5" style={{ color: "#555" }}>Sessions, hours & revenue</p>
      </div>
      <div className="flex flex-col gap-3">
        {tableStats.map((t, i) => {
          const pct = (t.revenue / maxRev) * 100;
          const isVip = t.table.includes("VIP");
          return (
            <div key={t.table} className="rounded-xl p-3 flex flex-col gap-2" style={{ background: "rgba(255,255,255,0.03)", border: `1px solid ${isVip ? "rgba(255,159,67,0.15)" : "rgba(255,255,255,0.05)"}` }}>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <span className="text-xs font-bold" style={{ color: isVip ? "#ff9f43" : "#888" }}>#{i+1}</span>
                  <span className="text-sm text-white font-medium">{t.table}</span>
                  {isVip && <span className="text-xs px-1.5 py-0.5 rounded-full" style={{ background: "rgba(255,159,67,0.12)", color: "#ff9f43" }}>VIP</span>}
                </div>
                <span className="text-sm font-bold" style={{ color: "#00ffb4", fontFamily: "'Rajdhani', sans-serif" }}>${t.revenue.toLocaleString()}</span>
              </div>
              <div className="w-full rounded-full overflow-hidden" style={{ background: "rgba(255,255,255,0.05)", height: "5px" }}>
                <div className="h-full rounded-full" style={{ width: `${pct}%`, background: isVip ? "linear-gradient(90deg,#ff9f43,#ff4d6d)" : "linear-gradient(90deg,#00ffb4,#00c8ff)" }} />
              </div>
              <div className="flex items-center gap-4">
                <span className="text-xs" style={{ color: "#555" }}><span className="text-white">{t.sessions}</span> sessions</span>
                <span className="text-xs" style={{ color: "#555" }}><span className="text-white">{t.hours}h</span> total</span>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}

export default function ReportsPage() {
  const [range, setRange] = useState<DateRange>("Last 6 Months");

  return (
    <AppLayout>
      <div className="p-6 flex flex-col gap-6">
        {/* Header with date filter */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h2 className="text-white font-bold text-xl" style={{ fontFamily: "'Rajdhani', sans-serif" }}>Reports & Analytics</h2>
            <p className="text-xs mt-1" style={{ color: "#555" }}>Performance overview and insights</p>
          </div>
          <div className="flex items-center gap-2 flex-wrap">
            {DATE_RANGES.map(r => (
              <button key={r} onClick={() => setRange(r)} className="px-3 py-1.5 rounded-lg text-xs font-medium cursor-pointer transition-all duration-200 whitespace-nowrap" style={{ background: range === r ? "rgba(0,255,180,0.15)" : "rgba(255,255,255,0.04)", color: range === r ? "#00ffb4" : "#666", border: `1px solid ${range === r ? "rgba(0,255,180,0.3)" : "rgba(255,255,255,0.06)"}` }}>
                {r}
              </button>
            ))}
          </div>
        </div>

        {/* KPI Cards */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          {summaryKpis.map((k, i) => (
            <div key={k.label} className="rounded-xl p-4 flex flex-col gap-3 transition-all duration-200" style={{ background: "rgba(255,255,255,0.03)", border: "1px solid rgba(255,255,255,0.07)" }}
              onMouseEnter={e => { (e.currentTarget as HTMLDivElement).style.transform = "translateY(-2px)"; }}
              onMouseLeave={e => { (e.currentTarget as HTMLDivElement).style.transform = "translateY(0)"; }}>
              <div className="flex items-center justify-between">
                <span className="text-xs uppercase tracking-widest" style={{ color: "#555" }}>{k.label}</span>
                <div className="w-8 h-8 flex items-center justify-center rounded-lg" style={{ background: `${k.color}18`, color: k.color }}>
                  <i className={`${k.icon} text-sm`} />
                </div>
              </div>
              <p className="text-2xl font-bold text-white" style={{ fontFamily: "'Rajdhani', sans-serif" }}>{k.value}</p>
              <div className="flex items-center gap-1">
                <div className="w-3 h-3 flex items-center justify-center">
                  <i className={`${k.up ? "ri-arrow-up-s-line" : "ri-arrow-down-s-line"} text-xs`} style={{ color: k.up ? "#00ffb4" : "#ff4d6d" }} />
                </div>
                <span className="text-xs" style={{ color: k.up ? "#00ffb4" : "#ff4d6d" }}>{k.change}</span>
              </div>
            </div>
          ))}
        </div>

        {/* Revenue Chart full width */}
        <RevenueChart range={range} />

        {/* Best Sellers + Table Stats */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <BestSellers />
          <TableStatsPanel />
        </div>
      </div>
    </AppLayout>
  );
}
