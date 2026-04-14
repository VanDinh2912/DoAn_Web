import { recentActivity } from "@/mocks/dashboardData";

const statusConfig: Record<string, { color: string; bg: string; label: string }> = {
  active: { color: "#00ffb4", bg: "rgba(0,255,180,0.12)", label: "Active" },
  paid: { color: "#00c8ff", bg: "rgba(0,200,255,0.12)", label: "Paid" },
  done: { color: "#888", bg: "rgba(136,136,136,0.12)", label: "Done" },
  new: { color: "#ff9f43", bg: "rgba(255,159,67,0.12)", label: "New" },
};

const typeIcon: Record<string, string> = {
  session: "ri-billiards-line",
  order: "ri-receipt-line",
  customer: "ri-user-add-line",
};

export default function RecentActivity() {
  return (
    <div
      className="rounded-xl p-5 flex flex-col gap-4"
      style={{
        background: "rgba(255,255,255,0.03)",
        border: "1px solid rgba(255,255,255,0.07)",
      }}
    >
      <div className="flex items-center justify-between">
        <h3 className="text-white font-semibold text-sm" style={{ fontFamily: "'Rajdhani', sans-serif", letterSpacing: "0.04em" }}>
          Recent Activity
        </h3>
        <button className="text-xs cursor-pointer transition-colors duration-150" style={{ color: "#00ffb4" }}>
          View all
        </button>
      </div>

      <div className="flex flex-col gap-1">
        {recentActivity.map((item) => {
          const st = statusConfig[item.status];
          const icon = typeIcon[item.type] ?? "ri-information-line";
          return (
            <div
              key={item.id}
              className="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-150 cursor-default"
              style={{ borderBottom: "1px solid rgba(255,255,255,0.04)" }}
              onMouseEnter={(e) => { (e.currentTarget as HTMLDivElement).style.background = "rgba(255,255,255,0.03)"; }}
              onMouseLeave={(e) => { (e.currentTarget as HTMLDivElement).style.background = "transparent"; }}
            >
              <div className="w-8 h-8 flex items-center justify-center rounded-lg flex-shrink-0" style={{ background: "rgba(255,255,255,0.05)", color: "#666" }}>
                <i className={`${icon} text-sm`} />
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-xs text-gray-300 truncate">{item.desc}</p>
                <p className="text-xs mt-0.5" style={{ color: "#555" }}>{item.user} · {item.time}</p>
              </div>
              <span className="text-xs px-2 py-0.5 rounded-full whitespace-nowrap flex-shrink-0" style={{ background: st.bg, color: st.color }}>
                {st.label}
              </span>
            </div>
          );
        })}
      </div>
    </div>
  );
}
