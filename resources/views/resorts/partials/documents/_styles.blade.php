{{-- Shared CSS for downloadable formal documents (DomPDF: no flex/grid/var(), so tables + literal token values).
     Tokens: teal #014653 · ink #14232A · mono ramp g1 #3A4145 g2 #6B7378 g3 #99A1A5 g4 #C7CDCF g6 #F4F5F5 · line #E2EBEC
             teal-soft #F5F8F8 · positive #1f6e40 / #E4F3E9 · warning #B7791F / #FBF0DC · critical #E5573F / #FDEEEB.
     Weight: bold (600) only on title, section titles, figure values and uppercase labels. No coloured bars or side strips. --}}
body { font-family: Helvetica, Arial, sans-serif; font-size: 10.5px; line-height: 1.5; color: #14232A; margin: 0; }
table { border-collapse: collapse; }

/* Letterhead */
table.lh { width: 100%; }
table.lh td { padding: 0 0 14px; border-bottom: 1px solid #14232A; vertical-align: top; }
td.lh-r { width: 44%; text-align: right; font-size: 9.5px; color: #6B7378; line-height: 1.75; }
td.lh-r b { font-weight: normal; color: #14232A; }
.org { font-size: 9px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; color: #014653; }
h1 { font-size: 20px; font-weight: bold; line-height: 1.2; margin: 5px 0 0; }
.lh-sub { font-size: 11px; color: #6B7378; margin-top: 3px; }

/* Key figures */
table.figs { width: 100%; border-bottom: 1px solid #E2EBEC; }
table.figs td { width: 25%; padding: 14px 12px 12px; border-left: 1px solid #E2EBEC; vertical-align: top; }
table.figs td.f1 { padding-left: 0; border-left: none; }
.fig-k { font-size: 8.5px; font-weight: bold; letter-spacing: .7px; text-transform: uppercase; color: #6B7378; }
.fig-v { font-size: 16px; font-weight: bold; margin-top: 3px; }
.fig-n { font-size: 9.5px; color: #99A1A5; margin-top: 2px; }

/* Sections */
h2 { font-size: 12px; font-weight: bold; color: #014653; margin: 26px 0 2px; page-break-after: avoid; }
h2 .no { color: #99A1A5; font-weight: normal; margin-right: 7px; }
.lead { font-size: 10px; color: #6B7378; margin: 0 0 9px; page-break-after: avoid; }
.brk { page-break-before: always; margin-top: 0; }

/* Tables */
table.dt { width: 100%; font-size: 9.5px; }
table.dt thead { display: table-header-group; }
table.dt tr { page-break-inside: avoid; }
table.dt th { font-size: 8.5px; font-weight: bold; letter-spacing: .6px; text-transform: uppercase; color: #6B7378; text-align: left; padding: 6px 7px; border-bottom: 1px solid #14232A; }
table.dt td { padding: 4px 7px; text-align: left; border-bottom: 1px solid #F4F5F5; vertical-align: top; }
table.dt th.r, table.dt td.r { text-align: right; }
td.c { color: #99A1A5; }
td.nw { white-space: nowrap; }
td.id { color: #6B7378; }
td.nm { font-weight: normal; }
tr.h td { font-size: 8.5px; font-weight: bold; letter-spacing: .8px; text-transform: uppercase; color: #6B7378; padding-top: 14px; border-bottom: none; }
tr.d td { color: #14232A; }
tr.s td { color: #3A4145; }
tr.s td.l { padding-left: 22px; }
tr.st td { border-top: 1px solid #C7CDCF; border-bottom: 1px solid #C7CDCF; background: #F5F8F8; }
tr.gt td { color: #014653; border-top: 1px solid #14232A; border-bottom: 3px double #14232A; padding-top: 7px; padding-bottom: 7px; }
tr.nil td { color: #99A1A5; }

/* Wide reports: smaller type and tighter cells */
table.dense { font-size: 8px; }
table.dense th { font-size: 7px; padding: 5px 4px; }
table.dense td { padding: 3px 4px; }

/* Insights text block (generic reports) */
.ins p, .ins li { font-size: 10px; color: #3A4145; margin: 0 0 5px; }
.ins ul { margin: 0 0 6px; padding-left: 16px; }
.ins h1, .ins h2, .ins h3, .ins h4 { font-size: 11px; font-weight: bold; color: #014653; margin: 10px 0 3px; }

/* Status chip + share bar */
.status { display: inline-block; font-size: 8.5px; font-weight: bold; letter-spacing: .4px; padding: 1px 7px; border-radius: 9px; background: #F4F5F5; color: #3A4145; }
.status.ok { background: #E4F3E9; color: #1f6e40; }
.status.warn { background: #FBF0DC; color: #B7791F; }
.status.bad { background: #FDEEEB; color: #E5573F; }
.bar { display: inline-block; height: 5px; background: #014653; margin-right: 6px; }
td.share { color: #6B7378; }

/* Side-by-side summary tables */
table.split { width: 100%; }
table.split > tbody > tr > td { width: 48%; vertical-align: top; padding: 0; border: none; }
table.split > tbody > tr > td.gap { width: 4%; }

/* Footer */
table.foot { width: 100%; margin-top: 28px; }
table.foot td { padding: 8px 0 0; border-top: 1px solid #E2EBEC; font-size: 8.5px; color: #99A1A5; vertical-align: top; }
table.foot td.r { text-align: right; }
