import { revenueData } from "@/mocks/dashboardData";

export default function RevenueChart() {
  const max = Math.max(...revenueData.map((d) => d.value));

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
            Revenue Overview
          </h3>
          <p className="text-xs mt-0.5" style={{ color: "#555" }}>Last 7 days</p>
        </div>
        <div className="flex items-center gap-2">
          <span className="text-xs px-2 py-1 rounded-full" style={{ background: "rgba(0,255,180,0.1)", color: "#00ffb4" }}>
            Weekly
          </span>
        </div>
      </div>

      {/* Chart */}
      <div className="flex items-end gap-2 h-36">
        {revenueData.map((d, i) => {
          const heightPct = (d.value / max) * 100;
          const isLast = i === revenueData.length - 1;
          return (
            <div key={d.label} className="flex-1 flex flex-col items-center gap-1 group">
              <div className="relative w-full flex items-end justify-center" style={{ height: "120px" }}>
                <div
                  className="w-full rounded-t-md transition-all duration-300 cursor-pointer relative"
                  style={{
                    height: `${heightPct}%`,
                    background: isLast
                      ? "linear-gradient(180deg, #00ffb4 0%, rgba(0,255,180,0.3) 100%)"
                      : "linear-gradient(180deg, rgba(0,200,255,0.6) 0%, rgba(0,200,255,0.15) 100%)",
                    minHeight: "4px",
                  }}
                  onMouseEnter={(e) => {
                    const el = e.currentTarget as HTMLDivElement;
                    el.style.filter = "brightness(1.3)";
                    const tooltip = el.querySelector(".tooltip") as HTMLElement;
                    if (tooltip) tooltip.style.opacity = "1";
                  }}
                  onMouseLeave={(e) => {
                    const el = e.currentTarget as HTMLDivElement;
                    el.style.filter = "brightness(1)";
                    const tooltip = el.querySelector(".tooltip") as HTMLElement;
                    if (tooltip) tooltip.style.opacity = "0";
                  }}
                >
                  <div
                    className="tooltip absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 rounded text-xs text-white whitespace-nowrap pointer-events-none transition-opacity duration-150"
                    style={{ background: "rgba(0,0,0,0.8)", opacity: 0, fontSize: "10px" }}
                  >
                    ${d.value.toLocaleString()}
                  </div>
                </div>
              </div>
              <span className="text-xs" style={{ color: isLast ? "#00ffb4" : "#555" }}>{d.label}</span>
            </div>
          );
        })}
      </div>

      <div className="flex items-center justify-between pt-2" style={{ borderTop: "1px solid rgba(255,255,255,0.05)" }}>
        <span className="text-xs" style={{ color: "#555" }}>Total this week</span>
        <span className="text-sm font-bold" style={{ color: "#00ffb4", fontFamily: "'Rajdhani', sans-serif" }}>
          ${revenueData.reduce((a, b) => a + b.value, 0).toLocaleString()}
        </span>
      </div>
    </div>
  );
}
