interface KpiCardProps {
  label: string;
  value: string;
  change: string;
  up: boolean;
  icon: string;
  index: number;
}

const accentColors = ["#00ffb4", "#00c8ff", "#ff9f43", "#ff4d6d"];
const bgColors = [
  "rgba(0,255,180,0.08)",
  "rgba(0,200,255,0.08)",
  "rgba(255,159,67,0.08)",
  "rgba(255,77,109,0.08)",
];

export default function KpiCard({ label, value, change, up, icon, index }: KpiCardProps) {
  const accent = accentColors[index % accentColors.length];
  const bg = bgColors[index % bgColors.length];

  return (
    <div
      className="rounded-xl p-5 flex flex-col gap-3 transition-all duration-300 cursor-default"
      style={{
        background: "rgba(255,255,255,0.03)",
        border: "1px solid rgba(255,255,255,0.07)",
        backdropFilter: "blur(10px)",
      }}
      onMouseEnter={(e) => {
        (e.currentTarget as HTMLDivElement).style.background = "rgba(255,255,255,0.05)";
        (e.currentTarget as HTMLDivElement).style.borderColor = `${accent}30`;
        (e.currentTarget as HTMLDivElement).style.transform = "translateY(-2px)";
      }}
      onMouseLeave={(e) => {
        (e.currentTarget as HTMLDivElement).style.background = "rgba(255,255,255,0.03)";
        (e.currentTarget as HTMLDivElement).style.borderColor = "rgba(255,255,255,0.07)";
        (e.currentTarget as HTMLDivElement).style.transform = "translateY(0)";
      }}
    >
      <div className="flex items-center justify-between">
        <span className="text-xs font-medium uppercase tracking-widest" style={{ color: "#666" }}>
          {label}
        </span>
        <div
          className="w-9 h-9 flex items-center justify-center rounded-lg"
          style={{ background: bg, color: accent }}
        >
          <i className={`${icon} text-base`} />
        </div>
      </div>
      <div>
        <p className="text-2xl font-bold text-white" style={{ fontFamily: "'Rajdhani', sans-serif" }}>
          {value}
        </p>
        <div className="flex items-center gap-1 mt-1">
          <div className="w-3 h-3 flex items-center justify-center">
            <i className={`${up ? "ri-arrow-up-s-line" : "ri-arrow-down-s-line"} text-xs`} style={{ color: up ? "#00ffb4" : "#ff4d6d" }} />
          </div>
          <span className="text-xs" style={{ color: up ? "#00ffb4" : "#ff4d6d" }}>
            {change}
          </span>
        </div>
      </div>
    </div>
  );
}
