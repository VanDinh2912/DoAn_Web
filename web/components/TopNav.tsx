import { useState, useRef, useEffect } from "react";

const notifications = [
  { id: 1, icon: "ri-table-line", text: "Table 7 session exceeded 2 hours", time: "5 min ago", unread: true },
  { id: 2, icon: "ri-alert-line", text: "Low stock: Coca-Cola (3 left)", time: "18 min ago", unread: true },
  { id: 3, icon: "ri-user-add-line", text: "New customer: Tom Bradley registered", time: "35 min ago", unread: false },
  { id: 4, icon: "ri-money-dollar-circle-line", text: "Daily revenue target reached!", time: "1h ago", unread: false },
];

interface TopNavProps {
  pageTitle: string;
}

export default function TopNav({ pageTitle }: TopNavProps) {
  const [notifOpen, setNotifOpen] = useState(false);
  const [profileOpen, setProfileOpen] = useState(false);
  const [search, setSearch] = useState("");
  const notifRef = useRef<HTMLDivElement>(null);
  const profileRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    function handleClick(e: MouseEvent) {
      if (notifRef.current && !notifRef.current.contains(e.target as Node)) setNotifOpen(false);
      if (profileRef.current && !profileRef.current.contains(e.target as Node)) setProfileOpen(false);
    }
    document.addEventListener("mousedown", handleClick);
    return () => document.removeEventListener("mousedown", handleClick);
  }, []);

  const unreadCount = notifications.filter((n) => n.unread).length;

  return (
    <header
      className="flex items-center justify-between px-6 py-3 sticky top-0 z-30"
      style={{
        background: "rgba(13,17,23,0.95)",
        backdropFilter: "blur(12px)",
        borderBottom: "1px solid rgba(255,255,255,0.06)",
        minHeight: "64px",
      }}
    >
      {/* Page Title */}
      <div>
        <h1 className="text-white font-semibold text-lg" style={{ fontFamily: "'Rajdhani', sans-serif", letterSpacing: "0.04em" }}>
          {pageTitle}
        </h1>
        <p className="text-xs" style={{ color: "#555" }}>
          {new Date().toLocaleDateString("en-US", { weekday: "long", year: "numeric", month: "long", day: "numeric" })}
        </p>
      </div>

      {/* Right Controls */}
      <div className="flex items-center gap-3">
        {/* Search */}
        <div className="relative hidden md:flex items-center">
          <div className="absolute left-3 w-4 h-4 flex items-center justify-center" style={{ color: "#555" }}>
            <i className="ri-search-line text-sm" />
          </div>
          <input
            type="text"
            placeholder="Search..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pl-9 pr-4 py-2 text-sm rounded-lg outline-none transition-all duration-200"
            style={{
              background: "rgba(255,255,255,0.05)",
              border: "1px solid rgba(255,255,255,0.08)",
              color: "#ccc",
              width: "200px",
              fontFamily: "'Inter', sans-serif",
            }}
            onFocus={(e) => { e.currentTarget.style.borderColor = "rgba(0,255,180,0.4)"; e.currentTarget.style.width = "240px"; }}
            onBlur={(e) => { e.currentTarget.style.borderColor = "rgba(255,255,255,0.08)"; e.currentTarget.style.width = "200px"; }}
          />
        </div>

        {/* Notifications */}
        <div className="relative" ref={notifRef}>
          <button
            onClick={() => { setNotifOpen(!notifOpen); setProfileOpen(false); }}
            className="relative w-9 h-9 flex items-center justify-center rounded-lg transition-all duration-200 cursor-pointer"
            style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)", color: "#aaa" }}
            onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.color = "#00ffb4"; }}
            onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.color = "#aaa"; }}
          >
            <i className="ri-notification-3-line text-base" />
            {unreadCount > 0 && (
              <span
                className="absolute -top-1 -right-1 w-4 h-4 flex items-center justify-center rounded-full text-xs font-bold text-black"
                style={{ background: "#00ffb4", fontSize: "10px" }}
              >
                {unreadCount}
              </span>
            )}
          </button>

          {notifOpen && (
            <div
              className="absolute right-0 mt-2 rounded-xl overflow-hidden"
              style={{
                width: "320px",
                background: "rgba(15,25,35,0.98)",
                border: "1px solid rgba(0,255,180,0.15)",
                backdropFilter: "blur(20px)",
                boxShadow: "0 20px 60px rgba(0,0,0,0.5)",
                zIndex: 100,
              }}
            >
              <div className="flex items-center justify-between px-4 py-3" style={{ borderBottom: "1px solid rgba(255,255,255,0.06)" }}>
                <span className="text-white text-sm font-semibold">Notifications</span>
                <span className="text-xs px-2 py-0.5 rounded-full" style={{ background: "rgba(0,255,180,0.15)", color: "#00ffb4" }}>
                  {unreadCount} new
                </span>
              </div>
              {notifications.map((n) => (
                <div
                  key={n.id}
                  className="flex items-start gap-3 px-4 py-3 cursor-pointer transition-all duration-150"
                  style={{
                    background: n.unread ? "rgba(0,255,180,0.04)" : "transparent",
                    borderBottom: "1px solid rgba(255,255,255,0.04)",
                  }}
                  onMouseEnter={(e) => { (e.currentTarget as HTMLDivElement).style.background = "rgba(255,255,255,0.04)"; }}
                  onMouseLeave={(e) => { (e.currentTarget as HTMLDivElement).style.background = n.unread ? "rgba(0,255,180,0.04)" : "transparent"; }}
                >
                  <div className="w-8 h-8 flex items-center justify-center rounded-lg flex-shrink-0" style={{ background: "rgba(0,255,180,0.1)", color: "#00ffb4" }}>
                    <i className={`${n.icon} text-sm`} />
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-xs text-gray-300 leading-snug">{n.text}</p>
                    <p className="text-xs mt-1" style={{ color: "#555" }}>{n.time}</p>
                  </div>
                  {n.unread && <div className="w-2 h-2 rounded-full mt-1 flex-shrink-0" style={{ background: "#00ffb4" }} />}
                </div>
              ))}
              <div className="px-4 py-2 text-center">
                <button className="text-xs cursor-pointer transition-colors duration-150" style={{ color: "#00ffb4" }}>
                  View all notifications
                </button>
              </div>
            </div>
          )}
        </div>

        {/* Profile */}
        <div className="relative" ref={profileRef}>
          <button
            onClick={() => { setProfileOpen(!profileOpen); setNotifOpen(false); }}
            className="flex items-center gap-2 px-3 py-1.5 rounded-lg transition-all duration-200 cursor-pointer"
            style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)" }}
          >
            <img
              src="https://readdy.ai/api/search-image?query=professional%20male%20manager%20portrait%20dark%20background%20studio%20lighting%20confident%20expression&width=40&height=40&seq=avatar1&orientation=squarish"
              alt="Admin"
              className="rounded-full object-cover"
              style={{ width: "28px", height: "28px" }}
            />
            <div className="hidden md:block text-left">
              <p className="text-white text-xs font-medium leading-tight">Alex Morgan</p>
              <p className="text-xs" style={{ color: "#00ffb4" }}>Admin</p>
            </div>
            <div className="w-4 h-4 flex items-center justify-center" style={{ color: "#555" }}>
              <i className="ri-arrow-down-s-line text-sm" />
            </div>
          </button>

          {profileOpen && (
            <div
              className="absolute right-0 mt-2 rounded-xl overflow-hidden"
              style={{
                width: "200px",
                background: "rgba(15,25,35,0.98)",
                border: "1px solid rgba(255,255,255,0.08)",
                backdropFilter: "blur(20px)",
                boxShadow: "0 20px 60px rgba(0,0,0,0.5)",
                zIndex: 100,
              }}
            >
              {[
                { icon: "ri-user-line", label: "My Profile" },
                { icon: "ri-settings-3-line", label: "Settings" },
                { icon: "ri-question-line", label: "Help & Support" },
              ].map((item) => (
                <button
                  key={item.label}
                  className="w-full flex items-center gap-3 px-4 py-3 text-sm text-gray-300 transition-all duration-150 cursor-pointer"
                  style={{ borderBottom: "1px solid rgba(255,255,255,0.04)" }}
                  onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "rgba(255,255,255,0.05)"; (e.currentTarget as HTMLButtonElement).style.color = "#fff"; }}
                  onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "transparent"; (e.currentTarget as HTMLButtonElement).style.color = ""; }}
                >
                  <div className="w-4 h-4 flex items-center justify-center">
                    <i className={`${item.icon} text-sm`} />
                  </div>
                  {item.label}
                </button>
              ))}
              <button
                className="w-full flex items-center gap-3 px-4 py-3 text-sm transition-all duration-150 cursor-pointer"
                style={{ color: "#ff4d6d" }}
                onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "rgba(255,77,109,0.08)"; }}
                onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "transparent"; }}
              >
                <div className="w-4 h-4 flex items-center justify-center">
                  <i className="ri-logout-box-r-line text-sm" />
                </div>
                Logout
              </button>
            </div>
          )}
        </div>
      </div>
    </header>
  );
}
