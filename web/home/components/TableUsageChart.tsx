import { tableUsageData } from "@/mocks/dashboardData";

export default function TableUsageChart() {
  return (
    <div
      className="rounded-xl p-5 flex flex-col gap-4"
      style={{
        background: "rgba(255,255,255,0.03)",
        border: "1px solid rgba(255,255,255,0.07)",
      }}
    >
      <div className="flex items-center justify-between">
        <div>
          <h3 className="text-white font-semibold text-sm" style={{ fontFamily: "'Rajdhani', sans-serif", letterSpacing: "0.04em" }}>
            Table Usage
          </h3>
          <p className="text-xs mt-0.5" style={{ color: "#555" }}>Utilization rate today</p>
        </div>
      </div>

      <div className="flex flex-col gap-2">
        {tableUsageData.map((d) => {
          const color = d.value >= 80 ? "#ff4d6d" : d.value >= 60 ? "#ff9f43" : "#00ffb4";
          return (
            <div key={d.label} className="flex items-center gap-3">
              <span className="text-xs w-6 text-right flex-shrink-0" style={{ color: "#666" }}>{d.label}</span>
              <div className="flex-1 rounded-full overflow-hidden" style={{ background: "rgba(255,255,255,0.05)", height: "8px" }}>
                <div
                  className="h-full rounded-full transition-all duration-700"
                  style={{ width: `${d.value}%`, background: `linear-gradient(90deg, ${color}99, ${color})` }}
                />
              </div>
              <span className="text-xs w-8 flex-shrink-0 font-medium" style={{ color }}>{d.value}%</span>
            </div>
          );
        })}
      </div>

      <div className="flex items-center gap-4 pt-2" style={{ borderTop: "1px solid rgba(255,255,255,0.05)" }}>
        {[{ color: "#00ffb4", label: "Low" }, { color: "#ff9f43", label: "Medium" }, { color: "#ff4d6d", label: "High" }].map((l) => (
          <div key={l.label} className="flex items-center gap-1.5">
            <div className="w-2 h-2 rounded-full" style={{ background: l.color }} />
            <span className="text-xs" style={{ color: "#555" }}>{l.label}</span>
          </div>
        ))}
      </div>
    </div>
  );
}
