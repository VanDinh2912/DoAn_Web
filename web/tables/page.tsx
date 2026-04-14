import { useState, useEffect } from "react";
import AppLayout from "@/components/feature/AppLayout";
import { tablesData, BilliardsTable, TableStatus } from "@/mocks/tablesData";

const statusCfg: Record<TableStatus, { color: string; bg: string; border: string; label: string; dot: string }> = {
  available: { color: "#00ffb4", bg: "rgba(0,255,180,0.08)", border: "rgba(0,255,180,0.25)", label: "Available", dot: "#00ffb4" },
  playing:   { color: "#ff4d6d", bg: "rgba(255,77,109,0.08)", border: "rgba(255,77,109,0.25)", label: "Playing",   dot: "#ff4d6d" },
  reserved:  { color: "#ff9f43", bg: "rgba(255,159,67,0.08)", border: "rgba(255,159,67,0.25)", label: "Reserved",  dot: "#ff9f43" },
};

function useTimer(startTime?: string) {
  const [elapsed, setElapsed] = useState(0);
  useEffect(() => {
    if (!startTime) return;
    const tick = () => setElapsed(Math.floor((Date.now() - new Date(startTime).getTime()) / 1000));
    tick();
    const id = setInterval(tick, 1000);
    return () => clearInterval(id);
  }, [startTime]);
  const h = Math.floor(elapsed / 3600);
  const m = Math.floor((elapsed % 3600) / 60);
  const s = elapsed % 60;
  return `${String(h).padStart(2,"0")}:${String(m).padStart(2,"0")}:${String(s).padStart(2,"0")}`;
}

function TableCard({ table, onOpen }: { table: BilliardsTable; onOpen: (t: BilliardsTable) => void }) {
  const cfg = statusCfg[table.status];
  const timer = useTimer(table.status === "playing" ? table.startTime : undefined);
  const elapsed = table.startTime ? (Date.now() - new Date(table.startTime).getTime()) / 3600000 : 0;
  const cost = (elapsed * table.ratePerHour).toFixed(2);

  return (
    <div
      onClick={() => onOpen(table)}
      className="rounded-xl p-4 flex flex-col gap-3 cursor-pointer transition-all duration-250"
      style={{ background: cfg.bg, border: `1px solid ${cfg.border}`, minHeight: "160px" }}
      onMouseEnter={(e) => { (e.currentTarget as HTMLDivElement).style.transform = "translateY(-3px)"; (e.currentTarget as HTMLDivElement).style.filter = "brightness(1.15)"; }}
      onMouseLeave={(e) => { (e.currentTarget as HTMLDivElement).style.transform = "translateY(0)"; (e.currentTarget as HTMLDivElement).style.filter = "brightness(1)"; }}
    >
      <div className="flex items-center justify-between">
        <span className="text-white font-bold text-sm" style={{ fontFamily: "'Rajdhani', sans-serif" }}>{table.name}</span>
        <span className="flex items-center gap-1.5 text-xs px-2 py-0.5 rounded-full" style={{ background: "rgba(0,0,0,0.3)", color: cfg.color }}>
          <span className="w-1.5 h-1.5 rounded-full animate-pulse inline-block" style={{ background: cfg.dot }} />
          {cfg.label}
        </span>
      </div>
      <div className="flex items-center justify-center flex-1">
        <div className="w-12 h-12 flex items-center justify-center rounded-full" style={{ background: "rgba(0,0,0,0.3)", color: cfg.color }}>
          <i className="ri-layout-grid-line text-2xl" />
        </div>
      </div>
      {table.status === "playing" && (
        <div className="flex flex-col gap-1">
          <p className="text-xs text-center font-mono font-bold" style={{ color: cfg.color }}>{timer}</p>
          <p className="text-xs text-center" style={{ color: "#888" }}>{table.player} · ${cost}</p>
        </div>
      )}
      {table.status === "reserved" && (
        <p className="text-xs text-center" style={{ color: cfg.color }}>{table.reservedFor} @ {table.reservedAt}</p>
      )}
      {table.status === "available" && (
        <p className="text-xs text-center" style={{ color: "#555" }}>${table.ratePerHour}/hr · Click to start</p>
      )}
    </div>
  );
}

function SessionModal({ table, onClose, onAction }: { table: BilliardsTable; onClose: () => void; onAction: (id: number, action: "start" | "stop" | "reserve") => void }) {
  const cfg = statusCfg[table.status];
  const timer = useTimer(table.status === "playing" ? table.startTime : undefined);
  const elapsed = table.startTime ? (Date.now() - new Date(table.startTime).getTime()) / 3600000 : 0;
  const cost = (elapsed * table.ratePerHour).toFixed(2);
  const [playerName, setPlayerName] = useState("");

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "rgba(0,0,0,0.7)", backdropFilter: "blur(6px)" }} onClick={onClose}>
      <div className="rounded-2xl p-6 w-full max-w-sm mx-4 flex flex-col gap-5" style={{ background: "#0f1923", border: "1px solid rgba(255,255,255,0.1)" }} onClick={(e) => e.stopPropagation()}>
        <div className="flex items-center justify-between">
          <h2 className="text-white font-bold text-lg" style={{ fontFamily: "'Rajdhani', sans-serif" }}>{table.name}</h2>
          <button onClick={onClose} className="w-8 h-8 flex items-center justify-center rounded-lg cursor-pointer" style={{ background: "rgba(255,255,255,0.05)", color: "#888" }}>
            <i className="ri-close-line" />
          </button>
        </div>
        <div className="flex items-center justify-center py-4">
          <div className="w-20 h-20 flex items-center justify-center rounded-full" style={{ background: cfg.bg, border: `2px solid ${cfg.border}`, color: cfg.color }}>
            <i className="ri-layout-grid-line text-4xl" />
          </div>
        </div>
        <div className="text-center">
          <span className="text-xs px-3 py-1 rounded-full" style={{ background: cfg.bg, color: cfg.color }}>{cfg.label}</span>
        </div>
        {table.status === "playing" && (
          <div className="rounded-xl p-4 text-center" style={{ background: "rgba(0,0,0,0.3)" }}>
            <p className="text-3xl font-mono font-bold" style={{ color: cfg.color }}>{timer}</p>
            <p className="text-sm mt-1" style={{ color: "#888" }}>Player: {table.player}</p>
            <p className="text-lg font-bold mt-2 text-white">${cost} <span className="text-xs text-gray-500">@ ${table.ratePerHour}/hr</span></p>
          </div>
        )}
        {table.status === "available" && (
          <div className="flex flex-col gap-2">
            <label className="text-xs" style={{ color: "#888" }}>Player Name</label>
            <input value={playerName} onChange={(e) => setPlayerName(e.target.value)} placeholder="Enter player name..." className="px-3 py-2 rounded-lg text-sm outline-none text-white" style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.1)" }} />
          </div>
        )}
        {table.status === "reserved" && (
          <div className="rounded-xl p-4 text-center" style={{ background: "rgba(0,0,0,0.3)" }}>
            <p className="text-sm" style={{ color: "#ff9f43" }}>Reserved for: {table.reservedFor}</p>
            <p className="text-xs mt-1" style={{ color: "#666" }}>At: {table.reservedAt}</p>
          </div>
        )}
        <div className="flex gap-3">
          {table.status === "available" && (
            <button onClick={() => { onAction(table.id, "start"); onClose(); }} className="flex-1 py-2.5 rounded-lg text-sm font-semibold cursor-pointer transition-all duration-200 whitespace-nowrap" style={{ background: "linear-gradient(135deg,#00ffb4,#00c8ff)", color: "#000" }}>
              Start Session
            </button>
          )}
          {table.status === "playing" && (
            <button onClick={() => { onAction(table.id, "stop"); onClose(); }} className="flex-1 py-2.5 rounded-lg text-sm font-semibold cursor-pointer transition-all duration-200 whitespace-nowrap" style={{ background: "rgba(255,77,109,0.2)", color: "#ff4d6d", border: "1px solid rgba(255,77,109,0.3)" }}>
              End Session & Checkout
            </button>
          )}
          {table.status === "reserved" && (
            <button onClick={() => { onAction(table.id, "start"); onClose(); }} className="flex-1 py-2.5 rounded-lg text-sm font-semibold cursor-pointer transition-all duration-200 whitespace-nowrap" style={{ background: "linear-gradient(135deg,#00ffb4,#00c8ff)", color: "#000" }}>
              Start Session
            </button>
          )}
          <button onClick={onClose} className="px-4 py-2.5 rounded-lg text-sm cursor-pointer whitespace-nowrap" style={{ background: "rgba(255,255,255,0.05)", color: "#888" }}>
            Cancel
          </button>
        </div>
      </div>
    </div>
  );
}

export default function TablesPage() {
  const [tables, setTables] = useState<BilliardsTable[]>(tablesData);
  const [selected, setSelected] = useState<BilliardsTable | null>(null);
  const [filter, setFilter] = useState<"all" | TableStatus>("all");

  const counts = { all: tables.length, available: tables.filter(t => t.status === "available").length, playing: tables.filter(t => t.status === "playing").length, reserved: tables.filter(t => t.status === "reserved").length };
  const filtered = filter === "all" ? tables : tables.filter(t => t.status === filter);

  const handleAction = (id: number, action: "start" | "stop" | "reserve") => {
    setTables(prev => prev.map(t => {
      if (t.id !== id) return t;
      if (action === "start") return { ...t, status: "playing" as TableStatus, startTime: new Date().toISOString(), player: t.reservedFor ?? "Guest" };
      if (action === "stop") return { ...t, status: "available" as TableStatus, startTime: undefined, player: undefined };
      return t;
    }));
  };

  return (
    <AppLayout>
      <div className="p-6 flex flex-col gap-6">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h2 className="text-white font-bold text-xl" style={{ fontFamily: "'Rajdhani', sans-serif" }}>Table Management</h2>
            <p className="text-xs mt-1" style={{ color: "#555" }}>Click any table to manage session</p>
          </div>
          <div className="flex items-center gap-2 flex-wrap">
            {(["all","available","playing","reserved"] as const).map(f => (
              <button key={f} onClick={() => setFilter(f)} className="px-3 py-1.5 rounded-lg text-xs font-medium cursor-pointer transition-all duration-200 whitespace-nowrap capitalize" style={{ background: filter === f ? (f === "all" ? "rgba(0,255,180,0.15)" : statusCfg[f as TableStatus]?.bg ?? "rgba(0,255,180,0.15)") : "rgba(255,255,255,0.04)", color: filter === f ? (f === "all" ? "#00ffb4" : statusCfg[f as TableStatus]?.color ?? "#00ffb4") : "#666", border: `1px solid ${filter === f ? (f === "all" ? "rgba(0,255,180,0.3)" : statusCfg[f as TableStatus]?.border ?? "rgba(0,255,180,0.3)") : "rgba(255,255,255,0.06)"}` }}>
                {f === "all" ? `All (${counts.all})` : `${f.charAt(0).toUpperCase()+f.slice(1)} (${counts[f as TableStatus]})`}
              </button>
            ))}
          </div>
        </div>

        {/* Stats Bar */}
        <div className="grid grid-cols-3 gap-4">
          {[{ label: "Available", count: counts.available, color: "#00ffb4" }, { label: "Playing", count: counts.playing, color: "#ff4d6d" }, { label: "Reserved", count: counts.reserved, color: "#ff9f43" }].map(s => (
            <div key={s.label} className="rounded-xl p-4 text-center" style={{ background: "rgba(255,255,255,0.03)", border: "1px solid rgba(255,255,255,0.07)" }}>
              <p className="text-2xl font-bold" style={{ color: s.color, fontFamily: "'Rajdhani', sans-serif" }}>{s.count}</p>
              <p className="text-xs mt-1" style={{ color: "#555" }}>{s.label}</p>
            </div>
          ))}
        </div>

        {/* Grid */}
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          {filtered.map(t => <TableCard key={t.id} table={t} onOpen={setSelected} />)}
        </div>
      </div>
      {selected && <SessionModal table={selected} onClose={() => setSelected(null)} onAction={handleAction} />}
    </AppLayout>
  );
}
