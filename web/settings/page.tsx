import { useState } from "react";
import AppLayout from "@/components/feature/AppLayout";

interface PricingTier {
  id: number;
  label: string;
  ratePerHour: number;
  color: string;
}

interface ClubSettings {
  clubName: string;
  address: string;
  phone: string;
  email: string;
  website: string;
  currency: string;
  timezone: string;
  openTime: string;
  closeTime: string;
  taxRate: number;
  receiptFooter: string;
  autoEndSession: boolean;
  autoEndMinutes: number;
  lowStockAlert: boolean;
  lowStockThreshold: number;
  soundNotifications: boolean;
  darkMode: boolean;
  compactMode: boolean;
  showTimerOnCard: boolean;
}

const defaultSettings: ClubSettings = {
  clubName: "Pool Manager Billiards Club",
  address: "123 Cue Street, Downtown, NY 10001",
  phone: "+1 (555) 000-1234",
  email: "info@poolmanager.com",
  website: "www.poolmanager.com",
  currency: "USD",
  timezone: "America/New_York",
  openTime: "10:00",
  closeTime: "02:00",
  taxRate: 8.5,
  receiptFooter: "Thank you for visiting Pool Manager! See you next time.",
  autoEndSession: true,
  autoEndMinutes: 180,
  lowStockAlert: true,
  lowStockThreshold: 10,
  soundNotifications: false,
  darkMode: true,
  compactMode: false,
  showTimerOnCard: true,
};

const defaultPricing: PricingTier[] = [
  { id: 1, label: "Standard Table",  ratePerHour: 15, color: "#00ffb4" },
  { id: 2, label: "Premium Table",   ratePerHour: 20, color: "#00c8ff" },
  { id: 3, label: "VIP Table",       ratePerHour: 25, color: "#ff9f43" },
  { id: 4, label: "Tournament Table",ratePerHour: 30, color: "#a78bfa" },
];

function SectionCard({ title, icon, children }: { title: string; icon: string; children: React.ReactNode }) {
  return (
    <div className="rounded-xl overflow-hidden" style={{ background: "rgba(255,255,255,0.03)", border: "1px solid rgba(255,255,255,0.07)" }}>
      <div className="flex items-center gap-3 px-5 py-4" style={{ borderBottom: "1px solid rgba(255,255,255,0.06)" }}>
        <div className="w-8 h-8 flex items-center justify-center rounded-lg" style={{ background: "rgba(0,255,180,0.1)", color: "#00ffb4" }}>
          <i className={`${icon} text-sm`} />
        </div>
        <h3 className="text-white font-semibold text-sm" style={{ fontFamily: "'Rajdhani', sans-serif", letterSpacing: "0.04em" }}>{title}</h3>
      </div>
      <div className="p-5">{children}</div>
    </div>
  );
}

function ToggleSwitch({ value, onChange, label, desc }: { value: boolean; onChange: (v: boolean) => void; label: string; desc?: string }) {
  return (
    <div className="flex items-center justify-between py-3" style={{ borderBottom: "1px solid rgba(255,255,255,0.04)" }}>
      <div>
        <p className="text-sm text-white">{label}</p>
        {desc && <p className="text-xs mt-0.5" style={{ color: "#555" }}>{desc}</p>}
      </div>
      <button
        onClick={() => onChange(!value)}
        className="relative flex-shrink-0 rounded-full transition-all duration-300 cursor-pointer"
        style={{ width: "44px", height: "24px", background: value ? "linear-gradient(135deg,#00ffb4,#00c8ff)" : "rgba(255,255,255,0.1)" }}
      >
        <span
          className="absolute top-1 rounded-full transition-all duration-300"
          style={{ width: "16px", height: "16px", background: "#fff", left: value ? "24px" : "4px" }}
        />
      </button>
    </div>
  );
}

function InputField({ label, value, onChange, type = "text", suffix }: { label: string; value: string | number; onChange: (v: string) => void; type?: string; suffix?: string }) {
  return (
    <div className="flex flex-col gap-1.5">
      <label className="text-xs font-medium" style={{ color: "#888" }}>{label}</label>
      <div className="relative flex items-center">
        <input
          type={type}
          value={value}
          onChange={e => onChange(e.target.value)}
          className="w-full px-3 py-2.5 rounded-lg text-sm outline-none text-white transition-all duration-200"
          style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)", paddingRight: suffix ? "40px" : "12px" }}
          onFocus={e => { e.currentTarget.style.borderColor = "rgba(0,255,180,0.4)"; }}
          onBlur={e => { e.currentTarget.style.borderColor = "rgba(255,255,255,0.08)"; }}
        />
        {suffix && <span className="absolute right-3 text-xs" style={{ color: "#555" }}>{suffix}</span>}
      </div>
    </div>
  );
}

export default function SettingsPage() {
  const [settings, setSettings] = useState<ClubSettings>(defaultSettings);
  const [pricing, setPricing] = useState<PricingTier[]>(defaultPricing);
  const [saved, setSaved] = useState(false);
  const [activeTab, setActiveTab] = useState<"general" | "pricing" | "notifications" | "display">("general");

  const set = (key: keyof ClubSettings, value: unknown) => setSettings(prev => ({ ...prev, [key]: value }));

  const handleSave = () => {
    setSaved(true);
    setTimeout(() => setSaved(false), 2500);
  };

  const tabs = [
    { id: "general",       label: "General",       icon: "ri-settings-3-line" },
    { id: "pricing",       label: "Pricing",        icon: "ri-money-dollar-circle-line" },
    { id: "notifications", label: "Notifications",  icon: "ri-notification-3-line" },
    { id: "display",       label: "Display",        icon: "ri-palette-line" },
  ] as const;

  return (
    <AppLayout>
      <div className="p-6 flex flex-col gap-6">
        {/* Toast */}
        {saved && (
          <div className="fixed top-20 right-6 z-50 px-5 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-300" style={{ background: "linear-gradient(135deg,#00ffb4,#00c8ff)", color: "#000" }}>
            <i className="ri-checkbox-circle-line" /> Settings saved successfully!
          </div>
        )}

        {/* Page Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h2 className="text-white font-bold text-xl" style={{ fontFamily: "'Rajdhani', sans-serif" }}>Settings</h2>
            <p className="text-xs mt-1" style={{ color: "#555" }}>Manage your club configuration and preferences</p>
          </div>
          <button
            onClick={handleSave}
            className="px-5 py-2.5 rounded-xl text-sm font-semibold cursor-pointer transition-all duration-200 whitespace-nowrap flex items-center gap-2 self-start sm:self-auto"
            style={{ background: "linear-gradient(135deg,#00ffb4,#00c8ff)", color: "#000" }}
            onMouseEnter={e => { (e.currentTarget as HTMLButtonElement).style.opacity = "0.9"; }}
            onMouseLeave={e => { (e.currentTarget as HTMLButtonElement).style.opacity = "1"; }}
          >
            <i className="ri-save-line" /> Save Changes
          </button>
        </div>

        {/* Tabs */}
        <div className="flex items-center gap-1 p-1 rounded-xl w-fit" style={{ background: "rgba(255,255,255,0.04)", border: "1px solid rgba(255,255,255,0.07)" }}>
          {tabs.map(t => (
            <button
              key={t.id}
              onClick={() => setActiveTab(t.id)}
              className="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all duration-200 whitespace-nowrap"
              style={{
                background: activeTab === t.id ? "rgba(0,255,180,0.15)" : "transparent",
                color: activeTab === t.id ? "#00ffb4" : "#666",
                border: activeTab === t.id ? "1px solid rgba(0,255,180,0.25)" : "1px solid transparent",
              }}
            >
              <div className="w-4 h-4 flex items-center justify-center">
                <i className={`${t.icon} text-sm`} />
              </div>
              <span className="hidden sm:inline">{t.label}</span>
            </button>
          ))}
        </div>

        {/* GENERAL TAB */}
        {activeTab === "general" && (
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <SectionCard title="Club Information" icon="ri-building-line">
              <div className="flex flex-col gap-4">
                <InputField label="Club Name" value={settings.clubName} onChange={v => set("clubName", v)} />
                <InputField label="Address" value={settings.address} onChange={v => set("address", v)} />
                <div className="grid grid-cols-2 gap-3">
                  <InputField label="Phone" value={settings.phone} onChange={v => set("phone", v)} />
                  <InputField label="Email" value={settings.email} onChange={v => set("email", v)} />
                </div>
                <InputField label="Website" value={settings.website} onChange={v => set("website", v)} />
              </div>
            </SectionCard>

            <SectionCard title="Business Hours & Tax" icon="ri-time-line">
              <div className="flex flex-col gap-4">
                <div className="grid grid-cols-2 gap-3">
                  <InputField label="Opening Time" value={settings.openTime} onChange={v => set("openTime", v)} type="time" />
                  <InputField label="Closing Time" value={settings.closeTime} onChange={v => set("closeTime", v)} type="time" />
                </div>
                <InputField label="Tax Rate" value={settings.taxRate} onChange={v => set("taxRate", parseFloat(v)||0)} type="number" suffix="%" />
                <div className="flex flex-col gap-1.5">
                  <label className="text-xs font-medium" style={{ color: "#888" }}>Currency</label>
                  <select value={settings.currency} onChange={e => set("currency", e.target.value)} className="px-3 py-2.5 rounded-lg text-sm outline-none text-white" style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)" }}>
                    {["USD","EUR","GBP","CAD","AUD","SGD"].map(c => <option key={c} value={c} style={{ background: "#0f1923" }}>{c}</option>)}
                  </select>
                </div>
                <div className="flex flex-col gap-1.5">
                  <label className="text-xs font-medium" style={{ color: "#888" }}>Timezone</label>
                  <select value={settings.timezone} onChange={e => set("timezone", e.target.value)} className="px-3 py-2.5 rounded-lg text-sm outline-none text-white" style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)" }}>
                    {["America/New_York","America/Chicago","America/Los_Angeles","Europe/London","Asia/Singapore","Australia/Sydney"].map(tz => <option key={tz} value={tz} style={{ background: "#0f1923" }}>{tz}</option>)}
                  </select>
                </div>
              </div>
            </SectionCard>

            <SectionCard title="Receipt Settings" icon="ri-receipt-line">
              <div className="flex flex-col gap-4">
                <div className="flex flex-col gap-1.5">
                  <label className="text-xs font-medium" style={{ color: "#888" }}>Receipt Footer Message</label>
                  <textarea
                    value={settings.receiptFooter}
                    onChange={e => set("receiptFooter", e.target.value)}
                    rows={3}
                    maxLength={200}
                    className="w-full px-3 py-2.5 rounded-lg text-sm outline-none text-white resize-none transition-all duration-200"
                    style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)" }}
                    onFocus={e => { e.currentTarget.style.borderColor = "rgba(0,255,180,0.4)"; }}
                    onBlur={e => { e.currentTarget.style.borderColor = "rgba(255,255,255,0.08)"; }}
                  />
                  <p className="text-xs text-right" style={{ color: "#444" }}>{settings.receiptFooter.length}/200</p>
                </div>
              </div>
            </SectionCard>

            <SectionCard title="Session Management" icon="ri-timer-line">
              <div className="flex flex-col">
                <ToggleSwitch
                  value={settings.autoEndSession}
                  onChange={v => set("autoEndSession", v)}
                  label="Auto-End Sessions"
                  desc="Automatically end sessions after a set duration"
                />
                {settings.autoEndSession && (
                  <div className="pt-3">
                    <InputField label="Auto-end after (minutes)" value={settings.autoEndMinutes} onChange={v => set("autoEndMinutes", parseInt(v)||60)} type="number" suffix="min" />
                  </div>
                )}
              </div>
            </SectionCard>
          </div>
        )}

        {/* PRICING TAB */}
        {activeTab === "pricing" && (
          <div className="flex flex-col gap-5">
            <SectionCard title="Table Pricing Tiers" icon="ri-price-tag-3-line">
              <div className="flex flex-col gap-4">
                <p className="text-xs" style={{ color: "#666" }}>Set hourly rates for each table type. Changes apply to new sessions immediately.</p>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  {pricing.map(tier => (
                    <div key={tier.id} className="rounded-xl p-4 flex flex-col gap-3" style={{ background: "rgba(255,255,255,0.03)", border: `1px solid ${tier.color}25` }}>
                      <div className="flex items-center gap-2">
                        <div className="w-3 h-3 rounded-full" style={{ background: tier.color }} />
                        <span className="text-sm font-semibold text-white">{tier.label}</span>
                      </div>
                      <div className="flex items-center gap-3">
                        <div className="relative flex-1">
                          <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold" style={{ color: tier.color }}>$</span>
                          <input
                            type="number"
                            value={tier.ratePerHour}
                            onChange={e => setPricing(prev => prev.map(p => p.id === tier.id ? { ...p, ratePerHour: parseFloat(e.target.value)||0 } : p))}
                            className="w-full pl-7 pr-12 py-2.5 rounded-lg text-sm font-bold outline-none transition-all duration-200"
                            style={{ background: "rgba(255,255,255,0.05)", border: `1px solid ${tier.color}30`, color: tier.color }}
                            onFocus={e => { e.currentTarget.style.borderColor = tier.color; }}
                            onBlur={e => { e.currentTarget.style.borderColor = `${tier.color}30`; }}
                          />
                          <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs" style={{ color: "#555" }}>/hr</span>
                        </div>
                      </div>
                      <div className="flex items-center justify-between text-xs" style={{ color: "#555" }}>
                        <span>2hr session</span>
                        <span style={{ color: tier.color }}>${(tier.ratePerHour * 2).toFixed(2)}</span>
                      </div>
                    </div>
                  ))}
                </div>

                {/* Pricing Preview */}
                <div className="rounded-xl p-4 mt-2" style={{ background: "rgba(0,255,180,0.04)", border: "1px solid rgba(0,255,180,0.1)" }}>
                  <p className="text-xs font-semibold mb-3" style={{ color: "#00ffb4", letterSpacing: "0.08em" }}>PRICING PREVIEW</p>
                  <div className="grid grid-cols-4 gap-2">
                    {["1h","2h","3h","4h"].map(dur => {
                      const hrs = parseInt(dur);
                      return (
                        <div key={dur} className="flex flex-col gap-2">
                          <p className="text-xs text-center font-bold" style={{ color: "#888" }}>{dur}</p>
                          {pricing.map(tier => (
                            <div key={tier.id} className="rounded-lg py-1.5 text-center text-xs font-bold" style={{ background: `${tier.color}12`, color: tier.color }}>
                              ${(tier.ratePerHour * hrs).toFixed(0)}
                            </div>
                          ))}
                        </div>
                      );
                    })}
                  </div>
                  <div className="flex flex-col gap-1 mt-3">
                    {pricing.map(tier => (
                      <div key={tier.id} className="flex items-center gap-2">
                        <div className="w-2 h-2 rounded-full" style={{ background: tier.color }} />
                        <span className="text-xs" style={{ color: "#666" }}>{tier.label}</span>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </SectionCard>
          </div>
        )}

        {/* NOTIFICATIONS TAB */}
        {activeTab === "notifications" && (
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <SectionCard title="Alert Settings" icon="ri-alert-line">
              <div className="flex flex-col">
                <ToggleSwitch value={settings.lowStockAlert} onChange={v => set("lowStockAlert", v)} label="Low Stock Alerts" desc="Get notified when inventory is running low" />
                {settings.lowStockAlert && (
                  <div className="pt-3 pb-3" style={{ borderBottom: "1px solid rgba(255,255,255,0.04)" }}>
                    <InputField label="Alert threshold (units)" value={settings.lowStockThreshold} onChange={v => set("lowStockThreshold", parseInt(v)||5)} type="number" />
                  </div>
                )}
                <ToggleSwitch value={settings.soundNotifications} onChange={v => set("soundNotifications", v)} label="Sound Notifications" desc="Play a sound for important alerts" />
              </div>
            </SectionCard>

            <SectionCard title="Session Alerts" icon="ri-timer-flash-line">
              <div className="flex flex-col">
                <ToggleSwitch value={settings.autoEndSession} onChange={v => set("autoEndSession", v)} label="Session Timeout Warning" desc="Warn staff before a session auto-ends" />
                <div className="pt-4 rounded-xl p-4 mt-2" style={{ background: "rgba(255,159,67,0.06)", border: "1px solid rgba(255,159,67,0.15)" }}>
                  <div className="flex items-start gap-3">
                    <div className="w-8 h-8 flex items-center justify-center rounded-lg flex-shrink-0" style={{ background: "rgba(255,159,67,0.15)", color: "#ff9f43" }}>
                      <i className="ri-information-line text-sm" />
                    </div>
                    <p className="text-xs leading-relaxed" style={{ color: "#888" }}>
                      Session alerts appear in the notification panel and on the table card. Staff will be notified 15 minutes before auto-end.
                    </p>
                  </div>
                </div>
              </div>
            </SectionCard>
          </div>
        )}

        {/* DISPLAY TAB */}
        {activeTab === "display" && (
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <SectionCard title="Theme & Appearance" icon="ri-palette-line">
              <div className="flex flex-col">
                <ToggleSwitch value={settings.darkMode} onChange={v => set("darkMode", v)} label="Dark Mode" desc="Use dark theme across the dashboard" />
                <ToggleSwitch value={settings.compactMode} onChange={v => set("compactMode", v)} label="Compact Mode" desc="Reduce spacing for more content on screen" />
                <ToggleSwitch value={settings.showTimerOnCard} onChange={v => set("showTimerOnCard", v)} label="Show Timer on Table Cards" desc="Display live session timer on table grid" />
              </div>
            </SectionCard>

            <SectionCard title="Theme Preview" icon="ri-eye-line">
              <div className="flex flex-col gap-3">
                {/* Mini dashboard preview */}
                <div className="rounded-xl overflow-hidden" style={{ background: settings.darkMode ? "#080d12" : "#f8fafc", border: "1px solid rgba(255,255,255,0.08)" }}>
                  <div className="px-3 py-2 flex items-center gap-2" style={{ background: settings.darkMode ? "#0d1117" : "#fff", borderBottom: "1px solid rgba(255,255,255,0.06)" }}>
                    <div className="w-4 h-4 rounded" style={{ background: "#00ffb4" }} />
                    <div className="flex-1 h-2 rounded-full" style={{ background: settings.darkMode ? "rgba(255,255,255,0.08)" : "#e5e7eb" }} />
                    <div className="w-5 h-5 rounded-full" style={{ background: settings.darkMode ? "rgba(255,255,255,0.1)" : "#e5e7eb" }} />
                  </div>
                  <div className="p-3 grid grid-cols-3 gap-2">
                    {["#00ffb4","#00c8ff","#ff9f43"].map((c, i) => (
                      <div key={i} className="rounded-lg p-2" style={{ background: settings.darkMode ? "rgba(255,255,255,0.04)" : "#fff", border: `1px solid ${c}30` }}>
                        <div className="h-1.5 rounded-full mb-1.5" style={{ background: c, width: "60%" }} />
                        <div className="h-1 rounded-full" style={{ background: settings.darkMode ? "rgba(255,255,255,0.1)" : "#e5e7eb", width: "80%" }} />
                      </div>
                    ))}
                  </div>
                </div>
                <p className="text-xs text-center" style={{ color: "#555" }}>
                  Currently: <span style={{ color: "#00ffb4" }}>{settings.darkMode ? "Dark Mode" : "Light Mode"}</span>
                  {settings.compactMode && <span style={{ color: "#00c8ff" }}> · Compact</span>}
                </p>
              </div>
            </SectionCard>
          </div>
        )}

        {/* Save Button Bottom */}
        <div className="flex justify-end pt-2">
          <button
            onClick={handleSave}
            className="px-6 py-3 rounded-xl text-sm font-semibold cursor-pointer transition-all duration-200 whitespace-nowrap flex items-center gap-2"
            style={{ background: "linear-gradient(135deg,#00ffb4,#00c8ff)", color: "#000" }}
          >
            <i className="ri-save-line" /> Save All Settings
          </button>
        </div>
      </div>
    </AppLayout>
  );
}
