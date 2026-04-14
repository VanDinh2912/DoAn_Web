import { useState } from "react";
import { useLocation } from "react-router-dom";
import Sidebar from "./Sidebar";
import TopNav from "./TopNav";
import AppFooter from "./AppFooter";

const pageTitles: Record<string, string> = {
  "/": "Dashboard",
  "/tables": "Table Management",
  "/orders": "Orders & Billing",
  "/customers": "Customers",
  "/inventory": "Inventory",
  "/staff": "Staff Management",
  "/reports": "Reports & Analytics",
  "/settings": "Settings",
};

interface AppLayoutProps {
  children: React.ReactNode;
}

export default function AppLayout({ children }: AppLayoutProps) {
  const [collapsed, setCollapsed] = useState(false);
  const location = useLocation();
  const pageTitle = pageTitles[location.pathname] ?? "Pool Manager";

  return (
    <div className="flex h-screen overflow-hidden" style={{ background: "#080d12", fontFamily: "'Inter', sans-serif" }}>
      <Sidebar collapsed={collapsed} onToggle={() => setCollapsed(!collapsed)} />
      <div className="flex flex-col flex-1 overflow-hidden">
        <TopNav pageTitle={pageTitle} />
        <main className="flex-1 overflow-y-auto" style={{ background: "#080d12" }}>
          {children}
        </main>
        <AppFooter />
      </div>
    </div>
  );
}
