import { useState } from "react";
import AppLayout from "@/components/feature/AppLayout";
import {
  staffData, allPermissions, shiftSchedule, weekDays,
  StaffMember, StaffRole, ShiftStatus,
} from "@/mocks/staffData";

// ─── Config ──────────────────────────────────────────────────────────────────
const roleCfg: Record<StaffRole, { color: string; bg: string; border: string; icon: string }> = {
  admin:   { color: "#ff9f43", bg: "rgba(255,159,67,0.12)",  border: "rgba(255,159,67,0.3)",  icon: "ri-shield-star-line"  },
  manager: { color: "#00c8ff", bg: "rgba(0,200,255,0.12)",   border: "rgba(0,200,255,0.3)",   icon: "ri-user-star-line"    },
  staff:   { color: "#00ffb4", bg: "rgba(0,255,180,0.12)",   border: "rgba(0,255,180,0.3)",   icon: "ri-user-line"         },
};

const statusCfg: Record<ShiftStatus, { color: string; bg: string; label: string; dot: string }> = {
  "on-duty":  { color: "#00ffb4", bg: "rgba(0,255,180,0.12)",  label: "On Duty",  dot: "#00ffb4" },
  "off-duty": { color: "#888",    bg: "rgba(136,136,136,0.1)", label: "Off Duty", dot: "#888"    },
  "on-leave": { color: "#ff9f43", bg: "rgba(255,159,67,0.12)", label: "On Leave", dot: "#ff9f43" },
};

const avatarGradients = [
  "linear-gradient(135deg,#00ffb4,#00c8ff)",
  "linear-gradient(135deg,#ff9f43,#ff4d6d)",
  "linear-gradient(135deg,#a78bfa,#00c8ff)",
  "linear-gradient(135deg,#00c8ff,#00ffb4)",
  "linear-gradient(135deg,#ff4d6d,#ff9f43)",
  "linear-gradient(135deg,#00ffb4,#a78bfa)",
  "linear-gradient(135deg,#fbbf24,#ff9f43)",
];

const shiftColors: Record<string, string> = {
  "Morning (8AM–4PM)":  "rgba(0,255,180,0.7)",
  "Evening (4PM–12AM)": "rgba(0,200,255,0.7)",
  "Night (12AM–8AM)":   "rgba(167,139,250,0.7)",
};

const permissionGroups = [...new Set(allPermissions.map(p => p.group))];

// ─── Staff Form Modal ─────────────────────────────────────────────────────────
function StaffModal({
  member, onClose, onSave,
}: {
  member: Partial<StaffMember> & { id?: number };
  onClose: () => void;
  onSave: (m: StaffMember) => void;
}) {
  const isEdit = !!member.id;
  const [form, setForm] = useState<Omit<StaffMember, "id">>({
    name: member.name ?? "",
    role: member.role ?? "staff",
    email: member.email ?? "",
    phone: member.phone ?? "",
    avatar: member.avatar ?? "",
    joinDate: member.joinDate ?? new Date().toISOString().split("T")[0],
    status: member.status ?? "off-duty",
    shift: member.shift ?? "Morning (8AM–4PM)",
    hoursThisWeek: member.hoursThisWeek ?? 0,
    permissions: member.permissions ?? ["view_dashboard", "view_tables", "create_orders"],
  });

  const togglePerm = (key: string) =>
    setForm(f => ({
      ...f,
      permissions: f.permissions.includes(key)
        ? f.permissions.filter(p => p !== key)
        : [...f.permissions, key],
    }));

  const handleSave = () => {
    if (!form.name.trim()) return;
    const initials = form.name.split(" ").map(n => n[0]).join("").toUpperCase().slice(0, 2);
    onSave({ ...form, avatar: initials, id: member.id ?? Date.now() });
    onClose();
  };

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center"
      style={{ background: "rgba(0,0,0,0.8)", backdropFilter: "blur(8px)" }}
      onClick={onClose}
    >
      <div
        className="rounded-2xl w-full max-w-2xl mx-4 flex flex-col overflow-hidden"
        style={{ background: "#0f1923", border: "1px solid rgba(255,255,255,0.1)", maxHeight: "90vh" }}
        onClick={e => e.stopPropagation()}
      >
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4" style={{ borderBottom: "1px solid rgba(255,255,255,0.07)" }}>
          <h3 className="text-white font-bold text-lg" style={{ fontFamily: "'Rajdhani', sans-serif" }}>
            {isEdit ? "Edit Staff Member" : "Add New Staff Member"}
          </h3>
          <button onClick={onClose} className="w-8 h-8 flex items-center justify-center rounded-lg cursor-pointer" style={{ background: "rgba(255,255,255,0.05)", color: "#888" }}>
            <i className="ri-close-line" />
          </button>
        </div>

        <div className="flex-1 overflow-y-auto p-6 flex flex-col gap-6">
          {/* Basic Info */}
          <div>
            <p className="text-xs font-semibold mb-3 uppercase tracking-wider" style={{ color: "#555" }}>Basic Information</p>
            <div className="grid grid-cols-2 gap-4">
              <div className="col-span-2 flex flex-col gap-1.5">
                <label className="text-xs" style={{ color: "#888" }}>Full Name</label>
                <input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} placeholder="e.g. John Smith" className="px-3 py-2.5 rounded-lg text-sm outline-none text-white" style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)" }} onFocus={e => { e.currentTarget.style.borderColor = "rgba(0,255,180,0.4)"; }} onBlur={e => { e.currentTarget.style.borderColor = "rgba(255,255,255,0.08)"; }} />
              </div>
              <div className="flex flex-col gap-1.5">
                <label className="text-xs" style={{ color: "#888" }}>Email</label>
                <input value={form.email} onChange={e => setForm(f => ({ ...f, email: e.target.value }))} placeholder="email@example.com" className="px-3 py-2.5 rounded-lg text-sm outline-none text-white" style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)" }} onFocus={e => { e.currentTarget.style.borderColor = "rgba(0,255,180,0.4)"; }} onBlur={e => { e.currentTarget.style.borderColor = "rgba(255,255,255,0.08)"; }} />
              </div>
              <div className="flex flex-col gap-1.5">
                <label className="text-xs" style={{ color: "#888" }}>Phone</label>
                <input value={form.phone} onChange={e => setForm(f => ({ ...f, phone: e.target.value }))} placeholder="+1 555-0000" className="px-3 py-2.5 rounded-lg text-sm outline-none text-white" style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)" }} onFocus={e => { e.currentTarget.style.borderColor = "rgba(0,255,180,0.4)"; }} onBlur={e => { e.currentTarget.style.borderColor = "rgba(255,255,255,0.08)"; }} />
              </div>
              <div className="flex flex-col gap-1.5">
                <label className="text-xs" style={{ color: "#888" }}>Role</label>
                <select value={form.role} onChange={e => setForm(f => ({ ...f, role: e.target.value as StaffRole }))} className="px-3 py-2.5 rounded-lg text-sm outline-none text-white" style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)" }}>
                  {(["admin","manager","staff"] as StaffRole[]).map(r => <option key={r} value={r} style={{ background: "#0f1923" }}>{r.charAt(0).toUpperCase()+r.slice(1)}</option>)}
                </select>
              </div>
              <div className="flex flex-col gap-1.5">
                <label className="text-xs" style={{ color: "#888" }}>Shift</label>
                <select value={form.shift} onChange={e => setForm(f => ({ ...f, shift: e.target.value }))} className="px-3 py-2.5 rounded-lg text-sm outline-none text-white" style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)" }}>
                  {["Morning (8AM–4PM)","Evening (4PM–12AM)","Night (12AM–8AM)"].map(s => <option key={s} value={s} style={{ background: "#0f1923" }}>{s}</option>)}
                </select>
              </div>
              <div className="flex flex-col gap-1.5">
                <label className="text-xs" style={{ color: "#888" }}>Status</label>
                <select value={form.status} onChange={e => setForm(f => ({ ...f, status: e.target.value as ShiftStatus }))} className="px-3 py-2.5 rounded-lg text-sm outline-none text-white" style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)" }}>
                  {(["on-duty","off-duty","on-leave"] as ShiftStatus[]).map(s => <option key={s} value={s} style={{ background: "#0f1923" }}>{statusCfg[s].label}</option>)}
                </select>
              </div>
              <div className="flex flex-col gap-1.5">
                <label className="text-xs" style={{ color: "#888" }}>Join Date</label>
                <input type="date" value={form.joinDate} onChange={e => setForm(f => ({ ...f, joinDate: e.target.value }))} className="px-3 py-2.5 rounded-lg text-sm outline-none text-white" style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)" }} />
              </div>
            </div>
          </div>

          {/* Permissions */}
          <div>
            <p className="text-xs font-semibold mb-3 uppercase tracking-wider" style={{ color: "#555" }}>Access Permissions</p>
            <div className="flex flex-col gap-4">
              {permissionGroups.map(group => (
                <div key={group}>
                  <p className="text-xs font-medium mb-2" style={{ color: "#666" }}>{group}</p>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    {allPermissions.filter(p => p.group === group).map(perm => {
                      const active = form.permissions.includes(perm.key);
                      return (
                        <button
                          key={perm.key}
                          onClick={() => togglePerm(perm.key)}
                          className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-left cursor-pointer transition-all duration-200"
                          style={{
                            background: active ? "rgba(0,255,180,0.08)" : "rgba(255,255,255,0.03)",
                            border: `1px solid ${active ? "rgba(0,255,180,0.25)" : "rgba(255,255,255,0.06)"}`,
                          }}
                        >
                          <div
                            className="w-4 h-4 rounded flex items-center justify-center flex-shrink-0 transition-all duration-200"
                            style={{ background: active ? "#00ffb4" : "rgba(255,255,255,0.08)", border: active ? "none" : "1px solid rgba(255,255,255,0.15)" }}
                          >
                            {active && <i className="ri-check-line text-black" style={{ fontSize: "10px" }} />}
                          </div>
                          <div className="min-w-0">
                            <p className="text-xs font-medium" style={{ color: active ? "#fff" : "#888" }}>{perm.label}</p>
                            <p className="text-xs mt-0.5 truncate" style={{ color: "#444" }}>{perm.description}</p>
                          </div>
                        </button>
                      );
                    })}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="flex gap-3 px-6 py-4" style={{ borderTop: "1px solid rgba(255,255,255,0.07)" }}>
          <button onClick={handleSave} className="flex-1 py-2.5 rounded-xl text-sm font-semibold cursor-pointer whitespace-nowrap" style={{ background: "linear-gradient(135deg,#00ffb4,#00c8ff)", color: "#000" }}>
            {isEdit ? "Save Changes" : "Add Staff Member"}
          </button>
          <button onClick={onClose} className="px-5 py-2.5 rounded-xl text-sm cursor-pointer whitespace-nowrap" style={{ background: "rgba(255,255,255,0.05)", color: "#888" }}>Cancel</button>
        </div>
      </div>
    </div>
  );
}

// ─── Delete Confirm ───────────────────────────────────────────────────────────
function DeleteConfirm({ member, onClose, onDelete }: { member: StaffMember; onClose: () => void; onDelete: () => void }) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "rgba(0,0,0,0.8)", backdropFilter: "blur(8px)" }} onClick={onClose}>
      <div className="rounded-2xl p-6 w-full max-w-sm mx-4 flex flex-col gap-4" style={{ background: "#0f1923", border: "1px solid rgba(255,77,109,0.2)" }} onClick={e => e.stopPropagation()}>
        <div className="w-12 h-12 flex items-center justify-center rounded-full mx-auto" style={{ background: "rgba(255,77,109,0.12)", color: "#ff4d6d" }}><i className="ri-user-unfollow-line text-2xl" /></div>
        <div className="text-center">
          <h3 className="text-white font-bold">Remove Staff Member?</h3>
          <p className="text-sm mt-1" style={{ color: "#888" }}>Remove <span className="text-white font-medium">{member.name}</span> from the system?</p>
        </div>
        <div className="flex gap-3">
          <button onClick={() => { onDelete(); onClose(); }} className="flex-1 py-2.5 rounded-xl text-sm font-semibold cursor-pointer whitespace-nowrap" style={{ background: "rgba(255,77,109,0.15)", color: "#ff4d6d", border: "1px solid rgba(255,77,109,0.3)" }}>Remove</button>
          <button onClick={onClose} className="flex-1 py-2.5 rounded-xl text-sm cursor-pointer whitespace-nowrap" style={{ background: "rgba(255,255,255,0.05)", color: "#888" }}>Cancel</button>
        </div>
      </div>
    </div>
  );
}

// ─── Permission Detail Modal ──────────────────────────────────────────────────
function PermissionsModal({ member, onClose }: { member: StaffMember; onClose: () => void }) {
  const rc = roleCfg[member.role];
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "rgba(0,0,0,0.8)", backdropFilter: "blur(8px)" }} onClick={onClose}>
      <div className="rounded-2xl w-full max-w-md mx-4 overflow-hidden flex flex-col" style={{ background: "#0f1923", border: "1px solid rgba(255,255,255,0.1)", maxHeight: "85vh" }} onClick={e => e.stopPropagation()}>
        <div className="flex items-center justify-between px-5 py-4" style={{ borderBottom: "1px solid rgba(255,255,255,0.07)" }}>
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold text-black" style={{ background: avatarGradients[member.id % avatarGradients.length] }}>{member.avatar}</div>
            <div>
              <p className="text-white font-semibold text-sm">{member.name}</p>
              <span className="text-xs px-2 py-0.5 rounded-full" style={{ background: rc.bg, color: rc.color }}>{member.role.charAt(0).toUpperCase()+member.role.slice(1)}</span>
            </div>
          </div>
          <button onClick={onClose} className="w-8 h-8 flex items-center justify-center rounded-lg cursor-pointer" style={{ background: "rgba(255,255,255,0.05)", color: "#888" }}><i className="ri-close-line" /></button>
        </div>
        <div className="flex-1 overflow-y-auto p-5 flex flex-col gap-4">
          {permissionGroups.map(group => {
            const groupPerms = allPermissions.filter(p => p.group === group);
            return (
              <div key={group}>
                <p className="text-xs font-semibold mb-2 uppercase tracking-wider" style={{ color: "#555" }}>{group}</p>
                <div className="flex flex-col gap-1.5">
                  {groupPerms.map(perm => {
                    const has = member.permissions.includes(perm.key);
                    return (
                      <div key={perm.key} className="flex items-center gap-3 px-3 py-2 rounded-lg" style={{ background: has ? "rgba(0,255,180,0.05)" : "rgba(255,255,255,0.02)", border: `1px solid ${has ? "rgba(0,255,180,0.12)" : "rgba(255,255,255,0.04)"}` }}>
                        <div className="w-5 h-5 flex items-center justify-center rounded flex-shrink-0" style={{ background: has ? "rgba(0,255,180,0.15)" : "rgba(255,255,255,0.05)", color: has ? "#00ffb4" : "#444" }}>
                          <i className={`${has ? "ri-check-line" : "ri-close-line"} text-xs`} />
                        </div>
                        <div className="flex-1 min-w-0">
                          <p className="text-xs font-medium" style={{ color: has ? "#ccc" : "#555" }}>{perm.label}</p>
                          <p className="text-xs" style={{ color: "#444" }}>{perm.description}</p>
                        </div>
                        {has
                          ? <span className="text-xs px-1.5 py-0.5 rounded-full flex-shrink-0" style={{ background: "rgba(0,255,180,0.1)", color: "#00ffb4" }}>Granted</span>
                          : <span className="text-xs px-1.5 py-0.5 rounded-full flex-shrink-0" style={{ background: "rgba(255,255,255,0.04)", color: "#444" }}>Denied</span>
                        }
                      </div>
                    );
                  })}
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}

// ─── Shift Schedule Grid ──────────────────────────────────────────────────────
function ShiftSchedule({ members }: { members: StaffMember[] }) {
  const today = new Date().getDay();
  const todayLabel = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"][today];

  return (
    <div className="rounded-xl overflow-hidden" style={{ background: "rgba(255,255,255,0.03)", border: "1px solid rgba(255,255,255,0.07)" }}>
      <div className="flex items-center justify-between px-5 py-4" style={{ borderBottom: "1px solid rgba(255,255,255,0.07)" }}>
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 flex items-center justify-center rounded-lg" style={{ background: "rgba(0,255,180,0.1)", color: "#00ffb4" }}>
            <i className="ri-calendar-schedule-line text-sm" />
          </div>
          <h3 className="text-white font-semibold text-sm" style={{ fontFamily: "'Rajdhani', sans-serif", letterSpacing: "0.04em" }}>Weekly Shift Schedule</h3>
        </div>
        <div className="flex items-center gap-3">
          {Object.entries({ "Morning (8AM–4PM)": shiftColors["Morning (8AM–4PM)"], "Evening (4PM–12AM)": shiftColors["Evening (4PM–12AM)"], "Night (12AM–8AM)": shiftColors["Night (12AM–8AM)"] }).map(([label, color]) => (
            <div key={label} className="hidden sm:flex items-center gap-1.5">
              <div className="w-2.5 h-2.5 rounded-sm" style={{ background: color }} />
              <span className="text-xs" style={{ color: "#555" }}>{label.split(" ")[0]}</span>
            </div>
          ))}
        </div>
      </div>

      <div className="overflow-x-auto">
        <table className="w-full" style={{ minWidth: "600px" }}>
          <thead>
            <tr style={{ borderBottom: "1px solid rgba(255,255,255,0.06)" }}>
              <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider w-40" style={{ color: "#555" }}>Staff</th>
              {weekDays.map(day => (
                <th key={day} className="px-2 py-3 text-center text-xs font-semibold uppercase tracking-wider" style={{ color: day === todayLabel ? "#00ffb4" : "#555", background: day === todayLabel ? "rgba(0,255,180,0.04)" : "transparent" }}>
                  {day}
                  {day === todayLabel && <span className="block text-xs font-normal" style={{ color: "#00ffb4", fontSize: "9px" }}>TODAY</span>}
                </th>
              ))}
              <th className="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider" style={{ color: "#555" }}>Hrs/Wk</th>
            </tr>
          </thead>
          <tbody>
            {members.map((member, mi) => {
              const rc = roleCfg[member.role];
              return (
                <tr key={member.id} style={{ borderBottom: "1px solid rgba(255,255,255,0.04)" }}
                  onMouseEnter={e => { (e.currentTarget as HTMLTableRowElement).style.background = "rgba(255,255,255,0.02)"; }}
                  onMouseLeave={e => { (e.currentTarget as HTMLTableRowElement).style.background = "transparent"; }}>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <div className="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-black flex-shrink-0" style={{ background: avatarGradients[mi % avatarGradients.length] }}>{member.avatar}</div>
                      <div className="min-w-0">
                        <p className="text-xs text-white font-medium truncate">{member.name.split(" ")[0]}</p>
                        <span className="text-xs" style={{ color: rc.color }}>{member.role}</span>
                      </div>
                    </div>
                  </td>
                  {weekDays.map(day => {
                    const entry = shiftSchedule.find(s => s.staffId === member.id && s.day === day);
                    const isToday = day === todayLabel;
                    const shiftLabel = entry ? (entry.start === "08:00" ? "Morning (8AM–4PM)" : entry.start === "16:00" ? "Evening (4PM–12AM)" : "Night (12AM–8AM)") : null;
                    const color = shiftLabel ? shiftColors[shiftLabel] : null;
                    return (
                      <td key={day} className="px-2 py-3 text-center" style={{ background: isToday ? "rgba(0,255,180,0.02)" : "transparent" }}>
                        {entry ? (
                          <div className="flex flex-col items-center gap-0.5">
                            <div className="w-full rounded-md py-1 px-1 text-center" style={{ background: `${color}20`, border: `1px solid ${color}40` }}>
                              <p className="text-xs font-medium leading-tight" style={{ color, fontSize: "10px" }}>{entry.start}</p>
                              <p className="text-xs leading-tight" style={{ color: `${color}99`, fontSize: "9px" }}>{entry.end}</p>
                            </div>
                          </div>
                        ) : (
                          <div className="w-full rounded-md py-2" style={{ background: "rgba(255,255,255,0.02)" }}>
                            <span style={{ color: "#333", fontSize: "10px" }}>—</span>
                          </div>
                        )}
                      </td>
                    );
                  })}
                  <td className="px-3 py-3 text-center">
                    <span className="text-sm font-bold" style={{ color: member.hoursThisWeek > 0 ? "#00ffb4" : "#444", fontFamily: "'Rajdhani', sans-serif" }}>
                      {member.hoursThisWeek}h
                    </span>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
}

// ─── Main Page ────────────────────────────────────────────────────────────────
export default function StaffPage() {
  const [members, setMembers] = useState<StaffMember[]>(staffData);
  const [activeTab, setActiveTab] = useState<"list" | "schedule">("list");
  const [roleFilter, setRoleFilter] = useState<"all" | StaffRole>("all");
  const [search, setSearch] = useState("");
  const [modalMember, setModalMember] = useState<Partial<StaffMember> | null>(null);
  const [deleteMember, setDeleteMember] = useState<StaffMember | null>(null);
  const [permsMember, setPermsMember] = useState<StaffMember | null>(null);
  const [toast, setToast] = useState("");

  const showToast = (msg: string) => { setToast(msg); setTimeout(() => setToast(""), 2500); };

  const filtered = members.filter(m => {
    const matchSearch = m.name.toLowerCase().includes(search.toLowerCase()) || m.email.toLowerCase().includes(search.toLowerCase());
    const matchRole = roleFilter === "all" || m.role === roleFilter;
    return matchSearch && matchRole;
  });

  const counts = {
    all: members.length,
    admin: members.filter(m => m.role === "admin").length,
    manager: members.filter(m => m.role === "manager").length,
    staff: members.filter(m => m.role === "staff").length,
    onDuty: members.filter(m => m.status === "on-duty").length,
  };

  const handleSave = (m: StaffMember) => {
    setMembers(prev => prev.find(s => s.id === m.id) ? prev.map(s => s.id === m.id ? m : s) : [...prev, m]);
    showToast(members.find(s => s.id === m.id) ? "Staff member updated!" : "Staff member added!");
  };

  return (
    <AppLayout>
      <div className="p-6 flex flex-col gap-6">
        {/* Toast */}
        {toast && (
          <div className="fixed top-20 right-6 z-50 px-5 py-3 rounded-xl text-sm font-semibold flex items-center gap-2" style={{ background: "linear-gradient(135deg,#00ffb4,#00c8ff)", color: "#000" }}>
            <i className="ri-checkbox-circle-line" /> {toast}
          </div>
        )}

        {/* Page Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h2 className="text-white font-bold text-xl" style={{ fontFamily: "'Rajdhani', sans-serif" }}>Staff Management</h2>
            <p className="text-xs mt-1" style={{ color: "#555" }}>Manage roles, permissions and shift schedules</p>
          </div>
          <button
            onClick={() => setModalMember({})}
            className="px-5 py-2.5 rounded-xl text-sm font-semibold cursor-pointer transition-all duration-200 whitespace-nowrap flex items-center gap-2 self-start sm:self-auto"
            style={{ background: "linear-gradient(135deg,#00ffb4,#00c8ff)", color: "#000" }}
          >
            <i className="ri-user-add-line" /> Add Staff Member
          </button>
        </div>

        {/* KPI Cards */}
        <div className="grid grid-cols-2 lg:grid-cols-5 gap-4">
          {[
            { label: "Total Staff",  value: counts.all,     color: "#00ffb4", icon: "ri-team-line"         },
            { label: "On Duty Now",  value: counts.onDuty,  color: "#00c8ff", icon: "ri-user-follow-line"  },
            { label: "Admins",       value: counts.admin,   color: "#ff9f43", icon: "ri-shield-star-line"  },
            { label: "Managers",     value: counts.manager, color: "#00c8ff", icon: "ri-user-star-line"    },
            { label: "Staff",        value: counts.staff,   color: "#00ffb4", icon: "ri-user-line"         },
          ].map(k => (
            <div key={k.label} className="rounded-xl p-4 flex items-center gap-3" style={{ background: "rgba(255,255,255,0.03)", border: "1px solid rgba(255,255,255,0.07)" }}>
              <div className="w-9 h-9 flex items-center justify-center rounded-xl flex-shrink-0" style={{ background: `${k.color}18`, color: k.color }}>
                <i className={`${k.icon} text-base`} />
              </div>
              <div>
                <p className="text-xl font-bold text-white" style={{ fontFamily: "'Rajdhani', sans-serif" }}>{k.value}</p>
                <p className="text-xs" style={{ color: "#555" }}>{k.label}</p>
              </div>
            </div>
          ))}
        </div>

        {/* Tabs */}
        <div className="flex items-center gap-1 p-1 rounded-xl w-fit" style={{ background: "rgba(255,255,255,0.04)", border: "1px solid rgba(255,255,255,0.07)" }}>
          {[{ id: "list", label: "Staff List", icon: "ri-list-check" }, { id: "schedule", label: "Shift Schedule", icon: "ri-calendar-schedule-line" }].map(t => (
            <button key={t.id} onClick={() => setActiveTab(t.id as "list"|"schedule")} className="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all duration-200 whitespace-nowrap" style={{ background: activeTab === t.id ? "rgba(0,255,180,0.15)" : "transparent", color: activeTab === t.id ? "#00ffb4" : "#666", border: activeTab === t.id ? "1px solid rgba(0,255,180,0.25)" : "1px solid transparent" }}>
              <div className="w-4 h-4 flex items-center justify-center"><i className={`${t.icon} text-sm`} /></div>
              {t.label}
            </button>
          ))}
        </div>

        {/* STAFF LIST TAB */}
        {activeTab === "list" && (
          <>
            {/* Filters */}
            <div className="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
              <div className="flex items-center gap-2 flex-wrap">
                {(["all","admin","manager","staff"] as const).map(f => {
                  const rc = f !== "all" ? roleCfg[f as StaffRole] : null;
                  return (
                    <button key={f} onClick={() => setRoleFilter(f)} className="px-3 py-1.5 rounded-lg text-xs font-medium cursor-pointer transition-all duration-200 whitespace-nowrap capitalize" style={{ background: roleFilter === f ? (rc ? rc.bg : "rgba(0,255,180,0.15)") : "rgba(255,255,255,0.04)", color: roleFilter === f ? (rc ? rc.color : "#00ffb4") : "#666", border: `1px solid ${roleFilter === f ? (rc ? rc.border : "rgba(0,255,180,0.3)") : "rgba(255,255,255,0.06)"}` }}>
                      {f === "all" ? `All (${counts.all})` : `${f.charAt(0).toUpperCase()+f.slice(1)} (${counts[f as StaffRole]})`}
                    </button>
                  );
                })}
              </div>
              <div className="relative flex items-center">
                <div className="absolute left-3 w-4 h-4 flex items-center justify-center" style={{ color: "#555" }}>
                  <i className="ri-search-line text-sm" />
                </div>
                <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search staff..." className="pl-9 pr-4 py-2 text-sm rounded-lg outline-none text-white" style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)", width: "220px" }} />
              </div>
            </div>

            {/* Staff Cards Grid */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              {filtered.map((member, mi) => {
                const rc = roleCfg[member.role];
                const sc = statusCfg[member.status];
                return (
                  <div key={member.id} className="rounded-xl p-4 flex flex-col gap-4 transition-all duration-200" style={{ background: "rgba(255,255,255,0.03)", border: "1px solid rgba(255,255,255,0.07)" }}
                    onMouseEnter={e => { (e.currentTarget as HTMLDivElement).style.borderColor = `${rc.color}30`; (e.currentTarget as HTMLDivElement).style.transform = "translateY(-2px)"; }}
                    onMouseLeave={e => { (e.currentTarget as HTMLDivElement).style.borderColor = "rgba(255,255,255,0.07)"; (e.currentTarget as HTMLDivElement).style.transform = "translateY(0)"; }}>

                    {/* Avatar + Status */}
                    <div className="flex items-start justify-between">
                      <div className="relative">
                        <div className="w-12 h-12 rounded-full flex items-center justify-center text-sm font-bold text-black" style={{ background: avatarGradients[mi % avatarGradients.length] }}>
                          {member.avatar}
                        </div>
                        <div className="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2" style={{ background: sc.dot, borderColor: "#0f1923" }} />
                      </div>
                      <div className="flex items-center gap-1">
                        <button onClick={() => setModalMember(member)} className="w-7 h-7 flex items-center justify-center rounded-lg cursor-pointer transition-all duration-150" style={{ background: "rgba(0,200,255,0.1)", color: "#00c8ff" }}
                          onMouseEnter={e => { (e.currentTarget as HTMLButtonElement).style.background = "rgba(0,200,255,0.2)"; }}
                          onMouseLeave={e => { (e.currentTarget as HTMLButtonElement).style.background = "rgba(0,200,255,0.1)"; }}>
                          <i className="ri-edit-line text-xs" />
                        </button>
                        {member.role !== "admin" && (
                          <button onClick={() => setDeleteMember(member)} className="w-7 h-7 flex items-center justify-center rounded-lg cursor-pointer transition-all duration-150" style={{ background: "rgba(255,77,109,0.1)", color: "#ff4d6d" }}
                            onMouseEnter={e => { (e.currentTarget as HTMLButtonElement).style.background = "rgba(255,77,109,0.2)"; }}
                            onMouseLeave={e => { (e.currentTarget as HTMLButtonElement).style.background = "rgba(255,77,109,0.1)"; }}>
                            <i className="ri-delete-bin-line text-xs" />
                          </button>
                        )}
                      </div>
                    </div>

                    {/* Info */}
                    <div>
                      <p className="text-white font-semibold text-sm">{member.name}</p>
                      <p className="text-xs mt-0.5" style={{ color: "#555" }}>{member.email}</p>
                    </div>

                    {/* Badges */}
                    <div className="flex items-center gap-2 flex-wrap">
                      <span className="flex items-center gap-1 text-xs px-2 py-0.5 rounded-full" style={{ background: rc.bg, color: rc.color, border: `1px solid ${rc.border}` }}>
                        <i className={`${rc.icon} text-xs`} />
                        {member.role.charAt(0).toUpperCase()+member.role.slice(1)}
                      </span>
                      <span className="flex items-center gap-1 text-xs px-2 py-0.5 rounded-full" style={{ background: sc.bg, color: sc.color }}>
                        <span className="w-1.5 h-1.5 rounded-full" style={{ background: sc.dot }} />
                        {sc.label}
                      </span>
                    </div>

                    {/* Shift & Hours */}
                    <div className="flex flex-col gap-1.5 pt-2" style={{ borderTop: "1px solid rgba(255,255,255,0.05)" }}>
                      <div className="flex items-center gap-2">
                        <div className="w-4 h-4 flex items-center justify-center" style={{ color: "#555" }}><i className="ri-time-line text-xs" /></div>
                        <span className="text-xs" style={{ color: "#888" }}>{member.shift}</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <div className="w-4 h-4 flex items-center justify-center" style={{ color: "#555" }}><i className="ri-calendar-line text-xs" /></div>
                        <span className="text-xs" style={{ color: "#888" }}>Since {member.joinDate}</span>
                      </div>
                      <div className="flex items-center justify-between mt-1">
                        <span className="text-xs" style={{ color: "#555" }}>This week</span>
                        <span className="text-sm font-bold" style={{ color: "#00ffb4", fontFamily: "'Rajdhani', sans-serif" }}>{member.hoursThisWeek}h</span>
                      </div>
                    </div>

                    {/* Permissions Summary */}
                    <div className="pt-2" style={{ borderTop: "1px solid rgba(255,255,255,0.05)" }}>
                      <div className="flex items-center justify-between mb-2">
                        <span className="text-xs" style={{ color: "#555" }}>Permissions</span>
                        <button onClick={() => setPermsMember(member)} className="text-xs cursor-pointer transition-colors duration-150" style={{ color: "#00c8ff" }}>View all</button>
                      </div>
                      <div className="flex flex-wrap gap-1">
                        {member.permissions.slice(0, 4).map(pk => {
                          const perm = allPermissions.find(p => p.key === pk);
                          return perm ? (
                            <span key={pk} className="text-xs px-1.5 py-0.5 rounded" style={{ background: "rgba(0,255,180,0.08)", color: "#00ffb4", fontSize: "10px" }}>{perm.label}</span>
                          ) : null;
                        })}
                        {member.permissions.length > 4 && (
                          <span className="text-xs px-1.5 py-0.5 rounded" style={{ background: "rgba(255,255,255,0.05)", color: "#666", fontSize: "10px" }}>+{member.permissions.length - 4} more</span>
                        )}
                      </div>
                    </div>
                  </div>
                );
              })}

              {/* Empty State */}
              {filtered.length === 0 && (
                <div className="col-span-full flex flex-col items-center justify-center py-16 gap-3">
                  <div className="w-14 h-14 flex items-center justify-center rounded-full" style={{ background: "rgba(255,255,255,0.04)", color: "#333" }}>
                    <i className="ri-user-search-line text-2xl" />
                  </div>
                  <p className="text-sm" style={{ color: "#444" }}>No staff members found</p>
                </div>
              )}
            </div>
          </>
        )}

        {/* SCHEDULE TAB */}
        {activeTab === "schedule" && (
          <ShiftSchedule members={members} />
        )}
      </div>

      {/* Modals */}
      {modalMember !== null && <StaffModal member={modalMember} onClose={() => setModalMember(null)} onSave={handleSave} />}
      {deleteMember && <DeleteConfirm member={deleteMember} onClose={() => setDeleteMember(null)} onDelete={() => { setMembers(prev => prev.filter(m => m.id !== deleteMember.id)); showToast("Staff member removed."); }} />}
      {permsMember && <PermissionsModal member={permsMember} onClose={() => setPermsMember(null)} />}
    </AppLayout>
  );
}
