export default function AppFooter() {
  return (
    <footer
      className="flex flex-col sm:flex-row items-center justify-between px-6 py-3 text-xs gap-2"
      style={{
        background: "rgba(13,17,23,0.95)",
        borderTop: "1px solid rgba(255,255,255,0.06)",
        color: "#444",
        fontFamily: "'Inter', sans-serif",
      }}
    >
      <span>&copy; {new Date().getFullYear()} Pool Manager — Billiards Club System. All rights reserved.</span>
      <div className="flex items-center gap-4">
        <span style={{ color: "#333" }}>v2.4.1</span>
        <a href="#" className="transition-colors duration-150 cursor-pointer" style={{ color: "#444" }}
          onMouseEnter={(e) => { (e.currentTarget as HTMLAnchorElement).style.color = "#00ffb4"; }}
          onMouseLeave={(e) => { (e.currentTarget as HTMLAnchorElement).style.color = "#444"; }}>
          Help
        </a>
        <a href="#" className="transition-colors duration-150 cursor-pointer" style={{ color: "#444" }}
          onMouseEnter={(e) => { (e.currentTarget as HTMLAnchorElement).style.color = "#00ffb4"; }}
          onMouseLeave={(e) => { (e.currentTarget as HTMLAnchorElement).style.color = "#444"; }}>
          Contact
        </a>
        <a href="#" className="transition-colors duration-150 cursor-pointer" style={{ color: "#444" }}
          onMouseEnter={(e) => { (e.currentTarget as HTMLAnchorElement).style.color = "#00ffb4"; }}
          onMouseLeave={(e) => { (e.currentTarget as HTMLAnchorElement).style.color = "#444"; }}>
          Privacy
        </a>
      </div>
    </footer>
  );
}
