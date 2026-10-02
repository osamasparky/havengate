<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&display=swap">
<style>
.fi-sidebar { background: linear-gradient(180deg, rgb(11 20 36 / .02), transparent 40%); }
.fi-header-heading { font-family: 'Cormorant Garamond', Georgia, serif; font-weight: 600; letter-spacing: -.01em; }
.fi-wi-stats-overview-stat-value { font-family: 'Cormorant Garamond', Georgia, serif; }

/* Availability calendar grid */
.hg-cal { border-collapse: separate; border-spacing: 0; font-size: .75rem; }
.hg-cal th, .hg-cal td { border-inline-end: 1px solid rgb(148 163 184 / .2); border-bottom: 1px solid rgb(148 163 184 / .2); min-width: 2.6rem; height: 2.4rem; text-align: center; padding: 0; }
.hg-cal thead th { position: sticky; top: 0; background: rgb(var(--gray-50)); z-index: 2; font-weight: 600; }
.dark .hg-cal thead th { background: rgb(var(--gray-900)); }
.hg-cal .unit { position: sticky; inset-inline-start: 0; background: white; z-index: 1; text-align: start; padding-inline: .75rem; min-width: 7rem; font-weight: 600; }
.dark .hg-cal .unit { background: rgb(var(--gray-900)); }
.hg-cal .weekend { background: rgb(184 135 90 / .06); }
.hg-cal .today { box-shadow: inset 0 0 0 2px #b8875a; }
.hg-cal .bk { color: #fff; font-weight: 600; overflow: hidden; white-space: nowrap; cursor: pointer; }
.hg-cal .bk-confirmed { background: #4e7d5b; }
.hg-cal .bk-checked_in { background: #3d7891; }
.hg-cal .bk-pending { background: #e0813a; }
.hg-cal .blocked { background: repeating-linear-gradient(45deg, rgb(180 72 60 / .15) 0 6px, transparent 6px 12px); }
</style>
