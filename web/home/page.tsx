import AppLayout from "@/components/feature/AppLayout";
import KpiCard from "./components/KpiCard";
import RevenueChart from "./components/RevenueChart";
import TableUsageChart from "./components/TableUsageChart";
import RecentActivity from "./components/RecentActivity";
import { kpiCards } from "@/mocks/dashboardData";

export default function Home() {
  return (
    <AppLayout>
      <div className="p-6 flex flex-col gap-6">
        {/* KPI Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {kpiCards.map((card, i) => (
            <KpiCard key={card.label} {...card} index={i} />
          ))}
        </div>

        {/* Charts Row */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <div className="lg:col-span-2">
            <RevenueChart />
          </div>
          <div>
            <TableUsageChart />
          </div>
        </div>

        {/* Recent Activity */}
        <RecentActivity />
      </div>
    </AppLayout>
  );
}
