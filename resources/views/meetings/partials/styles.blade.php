<style>
.consultation-layout { display:grid; grid-template-columns:minmax(0,1.4fr) minmax(260px,.9fr); gap:20px; align-items:start; }
.consultation-copy { color:var(--text-muted); font-size:14px; line-height:1.7; }
.consultation-actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:20px; }
.consultation-heading { display:flex; align-items:center; gap:12px; margin-bottom:18px; }
.consultation-heading .service-icon { margin:0; flex-shrink:0; width:48px; height:48px; font-size:20px; }
.consultation-heading h3 { font-size:17px; margin-bottom:4px; }
.consultation-note { padding:14px 16px; border:1px solid var(--glass-border); border-radius:var(--radius); background:var(--dark-2); margin:16px 0; }
.consultation-table-wrap { overflow-x:auto; }
.consultation-table { width:100%; border-collapse:collapse; font-size:13px; }
.consultation-table th { color:var(--text-muted); font-weight:500; text-align:left; padding:12px 10px; border-bottom:1px solid var(--glass-border); }
.consultation-table td { padding:14px 10px; border-bottom:1px solid var(--glass-border); white-space:nowrap; }
.consultation-table tr.is-you td { color:var(--primary-light); background:var(--dark-2); }
.queue-ticket { display:inline-flex; flex-direction:column; align-items:center; justify-content:center; min-width:110px; padding:18px; border-radius:var(--radius-lg); background:var(--dark-2); border:1px solid var(--glass-border); color:var(--primary-light); }
.queue-ticket strong { font-size:32px; letter-spacing:2px; }
.queue-ticket small { font-size:11px; color:var(--text-muted); }
.queue-summary { display:flex; align-items:center; gap:20px; margin:20px 0; }
.meeting-card .consultation-actions { margin-top:14px; }
.meeting-card h3 { overflow-wrap:anywhere; }
@media(max-width:950px) { .consultation-layout { grid-template-columns:minmax(0,1fr); } }
@media(max-width:540px) { .queue-summary { align-items:flex-start; flex-direction:column; } .consultation-actions .btn { flex:1; justify-content:center; } }
</style>
