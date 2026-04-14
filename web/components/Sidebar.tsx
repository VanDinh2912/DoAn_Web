import { useState } from "react";
import { NavLink } from "react-router-dom";

interface NavItem {
  label: string;
  icon: string;
  path: string;
}

const navItems: NavItem[] = [
  { label: "Dashboard", icon: "ri-dashboard-3-line", path: "/" },
  { label: "Table Management", icon: "ri-layout-grid-line", path: "/tables" },
  { label: "Orders & Billing", icon: "ri-receipt-line", path: "/orders" },
  { label: "Customers", icon: "ri-group-line", path: "/customers" },
  { label: "Inventory", icon: "ri-archive-line", path: "/inventory" },
  { label: "Staff Management", icon: "ri-team-line", path: "/staff" },
  { label: "Reports", icon: "ri-bar-chart-2-line", path: "/reports" },
  { label: "Settings", icon: "ri-settings-3-line", path: "/settings" },
];

interface SidebarProps {
  collapsed: boolean;
  onToggle: () => void;
}

export default function Sidebar({ collapsed, onToggle }: SidebarProps) {
  return (
    <aside
      className="flex flex-col h-screen sticky top-0 z-40 transition-all duration-300"
      style={{
        width: collapsed ? "72px" : "240px",
        background: "linear-gradient(180deg, #0d1117 0%, #0f1923 100%)",
        borderRight: "1px solid rgba(0,255,180,0.08)",
        flexShrink: 0,
      }}
    >
      {/* Logo */}
      <div
        className="flex items-center gap-3 px-4 py-5"
        style={{ borderBottom: "1px solid rgba(255,255,255,0.06)" }}
      >
        <img
          src="https://public.readdy.ai/ai/img_res/2b1ca771-09e1-4832-8b0d-8dea1ca40d8b.png"
          alt="Billiards Club Logo"
          className="rounded-lg object-cover flex-shrink-0"
          style={{ width: "36px", height: "36px" }}
        />
        {!collapsed && (
          <div className="overflow-hidden">
            <p className="text-white font-bold text-sm leading-tight whitespace-nowrap" style={{ fontFamily: "'Rajdhani', sans-serif", letterSpacing: "0.05em" }}>
              POOL MANAGER
            </p>
            <p className="text-xs" style={{ color: "#00ffb4" }}>Billiards Club</p>
          </div>
        )}
      </div>

      {/* Nav Items */}
      <nav className="flex-1 py-4 overflow-y-auto overflow-x-hidden">
        {navItems.map((item) => (
          <NavLink
            key={item.path}
            to={item.path}
            end={item.path === "/"}
            className={({ isActive }) =>
              `flex items-center gap-3 mx-2 mb-1 rounded-lg transition-all duration-200 cursor-pointer group ${
                isActive
                  ? "text-white"
                  : "text-gray-400 hover:text-white"
              }`
            }
            style={({ isActive }) => ({
              padding: collapsed ? "10px 18px" : "10px 14px",
              background: isActive
                ? "linear-gradient(90deg, rgba(0,255,180,0.15) 0%, rgba(0,200,255,0.08) 100%)"
                : "transparent",
              borderLeft: isActive ? "2px solid #00ffb4" : "2px solid transparent",
            })}
          >
            <div className="w-5 h-5 flex items-center justify-center flex-shrink-0">
              <i className={`${item.icon} text-lg`} />
            </div>
            {!collapsed && (
              <span className="text-sm font-medium whitespace-nowrap" style={{ fontFamily: "'Inter', sans-serif" }}>
                {item.label}
              </span>
            )}
          </NavLink>
        ))}
      </nav>

      {/* Collapse Toggle */}
      <div style={{ borderTop: "1px solid rgba(255,255,255,0.06)" }} className="p-3">
        <button
          onClick={onToggle}
          className="w-full flex items-center justify-center rounded-lg py-2 transition-all duration-200 cursor-pointer"
          style={{ background: "rgba(255,255,255,0.04)", color: "#888" }}
          onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.color = "#00ffb4"; }}
          onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.color = "#888"; }}
        >
          <div className="w-5 h-5 flex items-center justify-center">
            <i className={`${collapsed ? "ri-arrow-right-s-line" : "ri-arrow-left-s-line"} text-lg`} />
          </div>
          {!collapsed && <span className="ml-2 text-xs">Collapse</span>}
        </button>
      </div>
    </aside>
  );
}
