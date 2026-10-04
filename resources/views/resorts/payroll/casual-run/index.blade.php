@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>PAYROLL</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

<div id="cpx">
  {{-- Stepper — presentation over the existing sections; progress comes from the real draft actions below. --}}
  <nav class="stepper" id="cpStepper" aria-label="Casual payroll steps">
    <div class="step is-active" data-go="1"><div class="mk">1</div><div class="bd"><div class="nm">Period</div><div class="mt" id="cpM1">Select a period</div></div></div>
    <div class="step todo" data-go="2"><div class="mk">2</div><div class="bd"><div class="nm">Select employees</div><div class="mt" id="cpM2">Casual staff</div></div></div>
    <div class="step todo" data-go="3"><div class="mk">3</div><div class="bd"><div class="nm">Earnings</div><div class="mt" id="cpM3">Basic + OT</div></div></div>
    <div class="step todo" data-go="4"><div class="mk">4</div><div class="bd"><div class="nm">Deductions</div><div class="mt" id="cpM4">Withholdings</div></div></div>
    <div class="step todo" data-go="5"><div class="mk">5</div><div class="bd"><div class="nm">Review</div><div class="mt" id="cpM5">Net payable</div></div></div>
  </nav>

  {{-- Step 1 — period --}}
  <section class="panel is-on cpx-card" data-step="1">
    <div class="ch"><div class="idx">1</div><div><div class="ht">Period</div><div class="hs">Choose the pay cycle for this casual run</div></div></div>
    <div class="cbody">
      <div class="field"><div class="lbl">Available periods</div>
        <div class="selwrap">
          <select id="cp-period-select" class="sel">
            <option value="">Select a period…</option>
            @foreach ($availablePeriods as $p)
              <option value="{{ $p['start_date'] }}|{{ $p['end_date'] }}" data-payroll-id="{{ $p['payroll_id'] }}">{{ $p['label'] }} {{ $p['status_label'] }}</option>
            @endforeach
          </select>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <div class="draftline" id="cp-payroll-status"></div>
      </div>
    </div>
    <div class="card-foot"><span class="ft-note">Step 1 of 5</span><button type="button" class="cpx-btn primary" id="cp-start-draft">Start / resume draft →</button></div>
  </section>

  {{-- Step 2 — employees --}}
  <section class="panel cpx-card" data-step="2" id="cp-step-employees">
    <div class="ch"><div class="idx">2</div><div><div class="ht">Select casual employees</div><div class="hs">Who gets paid in this run</div></div></div>
    <div class="cbody">
      <div class="note"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg><span>Only casual employees whose position has a <b>configured basic salary</b> appear here.</span></div>
      <div class="toolbar"><label class="srch"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg><input type="text" id="cpSearch" placeholder="Search name, position or department…" autocomplete="off"></label><span class="selcount"><b id="cpSelN">0</b> of <span id="cpSelOf">0</span> selected</span></div>
      <div class="scrollwrap">
        <table class="etbl mid" id="cp-employees-table">
          <thead><tr class="single"><th class="emp" style="width:40px"><input type="checkbox" class="ck" id="cpSelAll" aria-label="Select all"></th><th class="emp" style="text-align:left">Name</th><th style="text-align:left">Position</th><th style="text-align:left">Department</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
    <div class="card-foot"><span class="ft-note"><b id="cpSelN2">0</b> casuals selected</span><button type="button" class="cpx-btn ghost" data-back="1">← Back</button><button type="button" class="cpx-btn primary" id="cp-save-employees">Save &amp; continue →</button></div>
  </section>

  {{-- Step 3 — earnings / attendance --}}
  <section class="panel cpx-card" data-step="3" id="cp-step-earnings">
    <div class="ch"><div class="idx">3</div><div><div class="ht">Earnings</div><div class="hs">Position basic salary + overtime, computed from attendance</div></div></div>
    <div class="cbody">
      <div class="earn-top"><button type="button" class="cpx-btn ghost sm" id="cp-load-earnings"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.5 9a9 9 0 0 1 14.9-3.4L23 10M1 14l4.6 4.4A9 9 0 0 0 20.5 15"/></svg>Load earnings</button><span class="meta">Attendance × position rate · {{ $currency }}</span></div>
      <div id="cp-unconfigured-warning" class="note warn" style="display:none;margin-bottom:12px"></div>
      <div class="scrollwrap">
        <table class="etbl wide" id="cp-earnings-table">
          <thead>
            <tr class="grp"><th class="emp" rowspan="2">Employee</th><th colspan="3">Attendance (days)</th><th colspan="3" class="gsep">Overtime (hrs)</th><th colspan="5" class="gsep">Pay · {{ $currency }}</th></tr>
            <tr class="sub"><th>Present</th><th>Day off</th><th>Unpaid</th><th class="gsep">Regular</th><th>Friday</th><th>Holiday</th><th class="gsep">Basic</th><th>Earned</th><th>Total OT pay</th><th>Commission<span class="io">info only</span></th><th>Gross pay</th></tr>
          </thead>
          <tbody><tr><td colspan="12" class="empty">Load earnings to see attendance and pay for the selected casuals.</td></tr></tbody>
          <tfoot></tfoot>
        </table>
      </div>
    </div>
    <div class="card-foot"><span class="ft-note">Gross pay <b id="cpEarnTot">—</b></span><button type="button" class="cpx-btn ghost" data-back="2">← Back</button><button type="button" class="cpx-btn primary" id="cp-save-attendance">Save attendance &amp; continue →</button></div>
  </section>

  {{-- Step 4 — deductions --}}
  <section class="panel cpx-card" data-step="4" id="cp-step-deductions">
    <div class="ch"><div class="idx">4</div><div><div class="ht">Deductions</div><div class="hs">Amounts withheld per casual, in {{ $currency }}</div></div></div>
    <div class="cbody">
      <div class="scrollwrap">
        <table class="etbl mid" id="cp-deductions-table">
          <thead><tr class="single"><th class="emp" style="text-align:left">Employee</th><th>Attendance</th><th>City ledger</th><th>Advance / loan</th><th>Pension</th><th>EWT</th><th>Other</th><th class="gsep">Total</th></tr></thead>
          <tbody></tbody>
          <tfoot></tfoot>
        </table>
      </div>
    </div>
    <div class="card-foot"><span class="ft-note">Total deductions <b id="cpDedTot">—</b></span><button type="button" class="cpx-btn ghost" data-back="3">← Back</button><button type="button" class="cpx-btn primary" id="cp-save-deductions">Save deductions &amp; continue →</button></div>
  </section>

  {{-- Step 5 — review + lock --}}
  <section class="panel cpx-card" data-step="5" id="cp-step-review">
    <div class="ch"><div class="idx">5</div><div><div class="ht">Review</div><div class="hs">Full summary of the selected casuals before approval</div></div><div class="cpx-right"><span class="chip draft">Draft</span></div></div>
    <div class="cbody">
      <div class="scrollwrap">
        <table class="etbl wide" id="cp-review-table" style="min-width:1120px">
          <thead>
            <tr class="grp"><th class="emp" rowspan="2">Employee</th><th colspan="3">Attendance (days)</th><th colspan="3" class="gsep">Earnings · {{ $currency }}</th><th class="gsep" rowspan="2">Deductions<span class="io">{{ $currency }}</span></th><th class="gsep" rowspan="2">Net salary<span class="io">{{ $currency }}</span></th><th class="gsep" rowspan="2">Commission<span class="io">not paid to employee</span></th></tr>
            <tr class="sub"><th>Present</th><th>Day off</th><th>Unpaid</th><th class="gsep">Basic</th><th>OT pay</th><th>Gross pay</th></tr>
          </thead>
          <tbody></tbody>
          <tfoot></tfoot>
        </table>
      </div>
      <div class="note" style="margin-top:14px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg><span><b>Net salary = Gross pay − Deductions.</b> Commission is shown for reference and is <b>not</b> paid to the employee through this run.</span></div>
    </div>
    <div class="card-foot">
      <span class="ft-note">Net payable <b id="cpRevNet">—</b></span>
      <button type="button" class="cpx-btn ghost" data-back="4">← Back</button>
      <button type="button" class="cpx-btn ghost sm" id="cp-save-review">Save review</button>
      <button type="button" class="cpx-btn primary" id="cp-send-approval">Send for approval</button>
    </div>
  </section>
</div>

    </div>
</div>
@endsection

@section('import-css')
<style>
#cpx{
  --teal:#014653; --teal-2:#035b6c; --teal-3:#E6F0F1; --teal-soft:#f1f7f7;
  --ink:#14232A; --g1:#3A4145; --g2:#6B7378; --muted:#5D6F75; --faint:#93A4A9; --g4:#C7CDCF;
  --line:#E2EBEC; --line-2:#EEF4F4; --bg:#EEF2F2; --card:#fff;
  --ok:#1F9D6B; --ok-bg:#E7F4EE; --warn:#B7791F; --warn-bg:#FBF0DC; --err:#E5573F; --err-bg:#FDEEEB;
  --lime:#E0FF02;
  --shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.06);
  --spring:cubic-bezier(.34,1.56,.64,1);
  --font:'Poppins',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
}
:where(#cpx) *{box-sizing:border-box;margin:0;padding:0}
#cpx .tnum{font-variant-numeric:tabular-nums}
#cpx button{font-family:inherit;cursor:pointer}
#cpx .stepper{display:flex;align-items:flex-start;background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);padding:18px 14px 15px;margin-bottom:18px}
#cpx .step{flex:1;position:relative;display:flex;flex-direction:column;align-items:center;gap:9px;text-align:center;padding:0 6px;cursor:pointer}
#cpx .step::before{content:"";position:absolute;top:14px;right:50%;width:100%;height:2px;background:var(--line);z-index:0}
#cpx .step:first-child::before{display:none}
#cpx .step .mk{position:relative;z-index:1;width:30px;height:30px;border-radius:50%;display:grid;place-items:center;font-size:12.5px;font-weight:600;background:var(--teal-3);color:var(--faint);transition:background .2s,box-shadow .2s}
#cpx .step .bd .nm{font-size:12.5px;font-weight:500;color:var(--g1);line-height:1.25}
#cpx .step .bd .mt{font-size:11px;color:var(--faint);margin-top:2px}
#cpx .step.done .mk{background:var(--teal);color:#fff}
#cpx .step.done::before,#cpx .step.is-active::before{background:var(--teal)}
#cpx .step.is-active .mk{background:#fff;color:var(--teal);box-shadow:0 0 0 2px var(--teal)}
#cpx .step.is-active .bd .nm{color:var(--teal);font-weight:600}
#cpx .step.todo .bd .nm{color:var(--faint)}
#cpx .panel{display:none;animation:cpxFade .22s ease}
#cpx .panel.is-on{display:block}
@keyframes cpxFade{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:none}}
#cpx .cpx-card{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:var(--shadow);min-width:0}
#cpx .ch{display:flex;align-items:center;gap:13px;padding:18px 22px;border-bottom:1px solid var(--line-2)}
#cpx .ch .idx{flex:none;width:30px;height:30px;border-radius:50%;display:grid;place-items:center;font-size:13px;font-weight:600;background:var(--teal);color:#fff}
#cpx .ch .ht{font-size:16px;font-weight:600;color:var(--ink)}
#cpx .ch .hs{font-size:12.5px;color:var(--muted);margin-top:1px}
#cpx .ch .cpx-right{margin-left:auto;display:flex;align-items:center;gap:10px}
#cpx .chip{display:inline-flex;align-items:center;gap:6px;font-size:11.5px;font-weight:600;padding:5px 11px;border-radius:20px;white-space:nowrap}
#cpx .chip.ok{background:var(--ok-bg);color:var(--ok)}
#cpx .chip.draft{background:var(--teal-soft);color:var(--teal)}
#cpx .cbody{padding:20px 22px}
#cpx .field{max-width:460px}
#cpx .lbl{font-size:11px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;color:var(--muted);margin-bottom:8px}
#cpx .selwrap{position:relative}
#cpx .selwrap svg{position:absolute;right:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:var(--g2);pointer-events:none}
#cpx select.sel{width:100%;appearance:none;-webkit-appearance:none;font:inherit;font-size:14px;color:var(--ink);background:#fff;border:1px solid var(--line);border-radius:11px;padding:12px 40px 12px 14px}
#cpx select.sel:focus{outline:none;border-color:var(--teal);box-shadow:0 0 0 3px rgba(1,70,83,.1)}
#cpx .draftline{font-size:13px;color:var(--muted);margin-top:14px}
#cpx .draftline b{color:var(--ink);font-weight:600}
#cpx .note{display:flex;gap:9px;align-items:flex-start;font-size:12.5px;color:var(--muted);background:var(--teal-soft);border-radius:11px;padding:11px 13px;line-height:1.5}
#cpx .note svg{flex:none;width:15px;height:15px;color:var(--teal);margin-top:1px}
#cpx .note b{color:var(--g1);font-weight:600}
#cpx .toolbar{display:flex;align-items:center;gap:12px;margin:14px 0 12px;flex-wrap:wrap}
#cpx .srch{flex:1;min-width:220px;display:flex;align-items:center;gap:9px;background:#fff;border:1px solid var(--line);border-radius:10px;padding:9px 12px}
#cpx .srch svg{width:15px;height:15px;color:var(--faint);flex:none}
#cpx .srch input{flex:1;border:none;outline:none;font:inherit;font-size:13px;background:none;color:var(--ink)}
#cpx .selcount{font-size:12.5px;color:var(--muted)}
#cpx .selcount b{color:var(--teal);font-weight:600}
#cpx .earn-top{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px}
#cpx .earn-top .meta{font-size:12px;color:var(--faint);margin-left:auto}
#cpx .earn-top .meta b{color:var(--muted);font-weight:600}
#cpx .scrollwrap{border:1px solid var(--line);border-radius:13px;overflow:auto;max-height:460px}
#cpx .etbl{border-collapse:separate;border-spacing:0;width:100%;font-size:12.5px}
#cpx .etbl.wide{min-width:1040px}
#cpx .etbl.mid{min-width:720px}
#cpx .etbl th,#cpx .etbl td{padding:10px 12px;text-align:right;white-space:nowrap;border-bottom:1px solid var(--line-2)}
#cpx .etbl thead th{position:sticky;top:0;background:var(--teal-soft);z-index:4}
#cpx .etbl thead tr.grp th{top:0;height:30px;font-size:10px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;color:var(--teal);text-align:center;border-bottom:1px solid var(--line)}
#cpx .etbl thead tr.sub th{top:30px;font-size:10px;font-weight:600;letter-spacing:.3px;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--line)}
#cpx .etbl thead tr.single th{top:0;font-size:10px;font-weight:600;letter-spacing:.3px;text-transform:uppercase;color:var(--muted);border-bottom:1px solid var(--line)}
#cpx .etbl thead th .io{display:block;font-size:8.5px;font-weight:500;letter-spacing:0;text-transform:none;color:var(--faint);margin-top:1px}
#cpx .etbl .emp{position:sticky;left:0;text-align:left;background:#fff;z-index:3}
#cpx .etbl thead .emp{z-index:6;background:var(--teal-soft)}
#cpx .etbl .gsep{border-left:1px solid var(--line)}
#cpx .etbl tbody td{color:var(--g1)}
#cpx .etbl tbody tr:hover td{background:#fafcfc}
#cpx .etbl tbody tr:hover .emp{background:#fafcfc}
#cpx .etbl .empcell{display:flex;align-items:center;gap:11px;min-width:0}
#cpx .etbl .empcell .nm{font-size:13px;font-weight:500;color:var(--ink)}
#cpx .etbl .empcell .po{font-size:11px;color:var(--muted);margin-top:1px}
#cpx .cpx-av{flex:none;width:32px;height:32px;border-radius:50%;background:#E1EBEC;color:var(--teal);font-size:10.5px;font-weight:600;display:grid;place-items:center;overflow:hidden;border:2px solid #fff;box-shadow:0 0 0 1.5px var(--teal-3)}
#cpx .cpx-av img{width:100%;height:100%;object-fit:cover}
#cpx .cpx-num{font-variant-numeric:tabular-nums}
#cpx .z{color:var(--faint)}
#cpx .comm{color:var(--faint)}
#cpx .np,#cpx .net{font-weight:600;color:var(--teal);font-variant-numeric:tabular-nums}
#cpx .net.neg{color:var(--err)}
#cpx .rowtot{font-weight:600;color:var(--ink);font-variant-numeric:tabular-nums}
#cpx .etbl tfoot td{position:sticky;bottom:0;border-top:1px solid var(--line);background:#fbfdfd;font-weight:600;color:var(--ink);font-variant-numeric:tabular-nums;z-index:4}
#cpx .etbl tfoot .emp{z-index:5;background:#fbfdfd}
#cpx .etbl tfoot .np,#cpx .etbl tfoot .net{color:var(--teal)}
#cpx .cur{font-size:9.5px;color:var(--faint);font-weight:500;margin-right:2px}
#cpx .ck{appearance:none;-webkit-appearance:none;width:18px;height:18px;border:1.5px solid var(--g4);border-radius:6px;background:#fff;display:grid;place-items:center;cursor:pointer}
#cpx .ck:checked{background:var(--teal);border-color:var(--teal)}
#cpx .ck:checked::after{content:"";width:8px;height:4px;border-left:2px solid #fff;border-bottom:2px solid #fff;transform:rotate(-45deg) translateY(-1px)}
#cpx .dinput{width:60px;text-align:right;font:inherit;font-size:12px;border:1px solid var(--line);border-radius:7px;padding:5px 7px;color:var(--ink);font-variant-numeric:tabular-nums}
#cpx .dinput:focus{outline:none;border-color:var(--teal);box-shadow:0 0 0 3px rgba(1,70,83,.1)}
#cpx .chkcell{text-align:center}
#cpx .card-foot{display:flex;align-items:center;gap:12px;padding:16px 22px;border-top:1px solid var(--line-2);flex-wrap:wrap}
#cpx .card-foot .ft-note{margin-right:auto;font-size:12.5px;color:var(--muted)}
#cpx .card-foot .ft-note b{color:var(--teal);font-weight:600}
#cpx .cpx-btn{display:inline-flex;align-items:center;gap:8px;font-size:13.5px;font-weight:600;border-radius:11px;padding:11px 20px;border:1px solid transparent;transition:transform .18s var(--spring),background .15s,box-shadow .15s}
#cpx .cpx-btn:active{transform:scale(.97)}
#cpx .cpx-btn.primary{background:var(--teal);color:#fff}
#cpx .cpx-btn.primary:hover{background:var(--teal-2);box-shadow:0 6px 16px rgba(1,70,83,.18)}
#cpx .cpx-btn.ghost{background:#fff;color:var(--teal);border-color:var(--line)}
#cpx .cpx-btn.ghost:hover{border-color:var(--teal);background:var(--teal-soft)}
#cpx .cpx-btn.sm{padding:9px 15px;font-size:12.5px}
@media (max-width:860px){#cpx .stepper{overflow-x:auto}
#cpx .step{min-width:116px}}
@media (prefers-reduced-motion:reduce){:where(#cpx) *{transition:none!important;animation:none!important}}#cpx .note.warn{background:var(--warn-bg);color:var(--warn)}
#cpx .note.warn svg{color:var(--warn)}
#cpx .empty{text-align:center;color:var(--muted);padding:26px 12px;white-space:normal}
#cpx .etbl tbody tr:hover .emp{background:#fafcfc}
#cpx .cpx-btn[disabled]{opacity:.6;cursor:default}
#cpx{font-size:14px;line-height:1.5;color:var(--ink);font-family:var(--font)}
</style>
@endsection

@section('import-scripts')
<script>
    // ---- existing draft state + endpoints (unchanged) ----
    let cpPayrollId = null;
    let cpStartDate = null;
    let cpEndDate = null;
    let cpEmployeesData = []; // last fetched earnings rows
    let cpDeductionsData = {}; // Emp_id -> deduction row values

    // ---- presentation: stepper / formatting helpers ----
    const CP_CUR = @json($currency);
    let cpMax = 1; // highest step unlocked by a real action
    const cpMoney = n => (Number(n) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const cpEsc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const cpInitials = n => (String(n || '').trim().split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('')) || '?';
    // Photo isn't part of the casual-run payloads, so avatars are initials (the photo-first slot is ready when a URL is supplied).
    const cpEmpCell = (name, sub) => `<td class="emp"><div class="empcell"><span class="cpx-av">${cpEsc(cpInitials(name))}</span><div><div class="nm">${cpEsc(name)}</div>${sub ? `<div class="po">${cpEsc(sub)}</div>` : ''}</div></div></td>`;
    const cpSum = (rows, f) => rows.reduce((t, r) => t + (Number(f(r)) || 0), 0);
    const cpShortDate = s => { const d = new Date(s + 'T00:00:00'); return isNaN(d) ? s : d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }); };

    function cpGo(n) {
        cpMax = Math.max(cpMax, n);
        document.querySelectorAll('#cpx .panel').forEach(p => p.classList.toggle('is-on', +p.dataset.step === n));
        document.querySelectorAll('#cpx .step').forEach(s => {
            const i = +s.dataset.go;
            s.className = 'step ' + (i === n ? 'is-active' : i < cpMax ? 'done' : 'todo');
            s.querySelector('.mk').textContent = (i !== n && i < cpMax) ? '✓' : i;
        });
        const top = document.getElementById('cpx').getBoundingClientRect().top + window.scrollY - 20;
        window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
    }
    document.querySelectorAll('#cpx .step').forEach(s => s.addEventListener('click', () => { if (+s.dataset.go <= cpMax) cpGo(+s.dataset.go); }));
    document.querySelectorAll('#cpx [data-back]').forEach(b => b.addEventListener('click', () => cpGo(+b.dataset.back)));

    // ---- Step 1 — period ----
    $('#cp-period-select').on('change', function () {
        const val = $(this).val();
        if (!val) return;
        [cpStartDate, cpEndDate] = val.split('|');
        cpPayrollId = $(this).find(':selected').data('payroll-id') || null;
        $('#cpM1').text(cpShortDate(cpStartDate) + ' – ' + cpShortDate(cpEndDate));
    });

    $('#cp-start-draft').on('click', function () {
        if (!cpStartDate || !cpEndDate) {
            toastr.error('Select a period first.', 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }
        $.ajax({
            url: '{{ route('payroll.save.draft') }}',
            type: 'POST',
            data: { start_date: cpStartDate, end_date: cpEndDate, status: 'draft', payroll_category: 'Casual', _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (!res.success) {
                    toastr.error(res.message, 'Error', { positionClass: 'toast-bottom-right' });
                    return;
                }
                cpPayrollId = res.payroll_id;
                $('#cp-payroll-status').html('Resuming <b>Draft #' + cpEsc(cpPayrollId) + '</b> — ' + cpEsc(cpStartDate) + ' to ' + cpEsc(cpEndDate));
                cpGo(2);
                cpLoadEmployees();
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not start draft.', 'Error', { positionClass: 'toast-bottom-right' });
            }
        });
    });

    // ---- Step 2 — employees ----
    function cpSyncSelection() {
        const boxes = document.querySelectorAll('#cp-employees-table .cp-emp-checkbox');
        let c = 0; boxes.forEach(b => { if (b.checked) c++; });
        $('#cpSelN, #cpSelN2').text(c); $('#cpSelOf').text(boxes.length);
        $('#cpM2').text(c + (c === 1 ? ' casual' : ' casuals'));
        const all = document.getElementById('cpSelAll');
        all.checked = boxes.length > 0 && c === boxes.length; all.indeterminate = c > 0 && c < boxes.length;
    }

    function cpLoadEmployees() {
        $.ajax({
            url: '{{ route('resort.casualPayroll.employees') }}',
            type: 'GET',
            data: { draw: 1, start: 0, length: 500 },
            success: function (res) {
                const rows = res.data || [];
                $('#cp-employees-table tbody').html(rows.map(function (e) {
                    const q = cpEsc((e.name + ' ' + e.position + ' ' + e.department).toLowerCase());
                    return `<tr data-employee-id="${cpEsc(e.id)}" data-emp-code="${cpEsc(e.Emp_id)}" data-q="${q}">
                        <td class="chkcell"><input type="checkbox" class="ck cp-emp-checkbox" checked></td>
                        ${cpEmpCell(e.name).replace('<td class="emp">', '<td class="emp" style="text-align:left">')}
                        <td style="text-align:left">${cpEsc(e.position)}</td><td style="text-align:left">${cpEsc(e.department)}</td>
                    </tr>`;
                }).join('') || '<tr><td colspan="4" class="empty">No configured casual employees found.</td></tr>');
                cpSyncSelection();
            }
        });
    }

    $('#cp-employees-table').on('change', '.cp-emp-checkbox', cpSyncSelection);
    $('#cpSelAll').on('change', function () {
        const on = this.checked;
        document.querySelectorAll('#cp-employees-table tbody tr:not([style*="none"]) .cp-emp-checkbox').forEach(b => { b.checked = on; });
        cpSyncSelection();
    });
    $('#cpSearch').on('input', function () {
        const q = this.value.trim().toLowerCase();
        document.querySelectorAll('#cp-employees-table tbody tr[data-q]').forEach(tr => { tr.style.display = tr.dataset.q.includes(q) ? '' : 'none'; });
    });

    $('#cp-save-employees').on('click', function () {
        const employeeIds = [];
        $('#cp-employees-table tbody tr[data-employee-id]').each(function () {
            if ($(this).find('.cp-emp-checkbox').is(':checked')) {
                employeeIds.push($(this).data('employee-id'));
            }
        });
        if (!employeeIds.length) {
            toastr.error('Select at least one employee.', 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }
        $.ajax({
            url: '{{ route('payroll.saveEmployees') }}',
            type: 'POST',
            data: { payroll_id: cpPayrollId, employee_ids: employeeIds, _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (!res.success) { toastr.error(res.message, 'Error', { positionClass: 'toast-bottom-right' }); return; }
                toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                cpGo(3);
            },
            error: function () { toastr.error('Could not save employees.', 'Error', { positionClass: 'toast-bottom-right' }); }
        });
    });

    // ---- Step 3 — earnings ----
    function cpRenderEarnings() {
        const rows = cpEmployeesData, z = v => (Number(v) ? '' : ' z');
        $('#cp-earnings-table tbody').html(rows.map(function (e) {
            return `<tr data-emp-code="${cpEsc(e.id)}" data-employee-id="${cpEsc(e.employee_id)}">${cpEmpCell(e.name, e.position)}
                <td class="cpx-num">${e.present}</td><td class="cpx-num">${e.day_offs}</td><td class="cpx-num${z(e.unpaid_days)}">${e.unpaid_days}</td>
                <td class="cpx-num gsep${z(e.regular_ot)}">${e.regular_ot}</td><td class="cpx-num${z(e.friday_ot)}">${e.friday_ot}</td><td class="cpx-num${z(e.holiday_ot)}">${e.holiday_ot}</td>
                <td class="cpx-num gsep">${cpMoney(e.basic_salary)}</td><td class="cpx-num">${cpMoney(e.earned_salary)}</td><td class="cpx-num">${cpMoney(e.total_ot_pay)}</td>
                <td class="cpx-num comm">${cpMoney(e.service_provider_commission)}</td><td class="np">${cpMoney(e.normal_pay)}</td></tr>`;
        }).join('') || '<tr><td colspan="12" class="empty">No earnings to show.</td></tr>');
        const t = f => cpSum(rows, f);
        $('#cp-earnings-table tfoot').html(rows.length ? `<tr><td class="emp">Totals · ${rows.length}</td>
            <td class="cpx-num">${t(e => e.present)}</td><td class="cpx-num">${t(e => e.day_offs)}</td><td class="cpx-num">${t(e => e.unpaid_days)}</td>
            <td class="cpx-num gsep">${+t(e => e.regular_ot).toFixed(2)}</td><td class="cpx-num">${+t(e => e.friday_ot).toFixed(2)}</td><td class="cpx-num">${+t(e => e.holiday_ot).toFixed(2)}</td>
            <td class="cpx-num gsep">${cpMoney(t(e => e.basic_salary))}</td><td class="cpx-num">${cpMoney(t(e => e.earned_salary))}</td><td class="cpx-num">${cpMoney(t(e => e.total_ot_pay))}</td>
            <td class="cpx-num comm">${cpMoney(t(e => e.service_provider_commission))}</td><td class="np">${cpMoney(t(e => e.normal_pay))}</td></tr>` : '');
        const tot = CP_CUR + ' ' + cpMoney(t(e => e.normal_pay));
        $('#cpEarnTot').text(rows.length ? tot + ' · ' + rows.length + (rows.length === 1 ? ' casual' : ' casuals') : '—');
        $('#cpM3').text(rows.length ? tot : 'Basic + OT');
    }

    $('#cp-load-earnings').on('click', function () {
        const employeeIds = [];
        $('#cp-employees-table tbody tr[data-employee-id]').each(function () {
            if ($(this).find('.cp-emp-checkbox').is(':checked')) {
                employeeIds.push($(this).data('employee-id'));
            }
        });
        $.ajax({
            url: '{{ route('resort.casualPayroll.fetchTimeAttendance') }}',
            type: 'POST',
            data: { employees: employeeIds, startDate: cpStartDate, endDate: cpEndDate, currency: '{{ $currency }}', _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (!res.success) { toastr.error('Could not compute earnings.', 'Error', { positionClass: 'toast-bottom-right' }); return; }
                cpEmployeesData = res.data || [];
                if (res.unconfigured_employees && res.unconfigured_employees.length) {
                    $('#cp-unconfigured-warning').show().text('No pay config for: ' + res.unconfigured_employees.join(', ') + ' — excluded.');
                } else {
                    $('#cp-unconfigured-warning').hide();
                }
                cpRenderEarnings();
                cpMax = Math.max(cpMax, 4); // deductions unlock once earnings are loaded (as before)
                cpRenderDeductions();
                cpGo(3);
            },
            error: function () { toastr.error('Could not load earnings.', 'Error', { positionClass: 'toast-bottom-right' }); }
        });
    });

    $('#cp-save-attendance').on('click', function () {
        if (!cpEmployeesData.length) {
            toastr.error('Load earnings first.', 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }
        const attendance = cpEmployeesData.map(function (e) {
            return {
                id: e.id, // Emp_id
                present: e.present,
                absent: e.unpaid_days,
                leaveTypes: '',
                // PayrollTimeAndAttendance has no Friday-specific column —
                // folded into holidayOT, same convention the Permanent run uses.
                regularOT: e.regular_ot,
                holidayOT: e.friday_ot + e.holiday_ot,
                notes: null,
            };
        });
        $.ajax({
            url: '{{ route('payroll.saveAttendance') }}',
            type: 'POST',
            data: { payroll_id: cpPayrollId, attendance: attendance, _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (!res.success) { toastr.error(res.message, 'Error', { positionClass: 'toast-bottom-right' }); return; }
                toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                cpGo(4);
            },
            error: function () { toastr.error('Could not save attendance.', 'Error', { positionClass: 'toast-bottom-right' }); }
        });
    });

    // ---- Step 4 — deductions (live totals are display-only; the server recomputes on save) ----
    const CP_DED_FIELDS = ['attendanceDeduction', 'cityLedger', 'advanceLoan', 'pension', 'ewt', 'other'];

    function cpDeductionTotal(d) {
        return (parseFloat(d.attendanceDeduction) || 0) + (parseFloat(d.cityLedger) || 0) + (parseFloat(d.advanceLoan) || 0)
            + (parseFloat(d.pension) || 0) + (parseFloat(d.ewt) || 0) + (parseFloat(d.other) || 0);
    }

    function cpRenderDeductionFoot() {
        if (!cpEmployeesData.length) { $('#cp-deductions-table tfoot').html(''); $('#cpDedTot').text('—'); return; }
        const cols = {}; CP_DED_FIELDS.forEach(f => { cols[f] = 0; }); let grand = 0;
        cpEmployeesData.forEach(function (e) {
            const d = cpDeductionsData[e.id];
            CP_DED_FIELDS.forEach(f => { cols[f] += parseFloat(d[f]) || 0; });
            grand += cpDeductionTotal(d);
        });
        $('#cp-deductions-table tfoot').html(`<tr><td class="emp">Totals</td>${CP_DED_FIELDS.map(f => `<td class="cpx-num">${cpMoney(cols[f])}</td>`).join('')}<td class="gsep np">${cpMoney(grand)}</td></tr>`);
        $('#cpDedTot').text(CP_CUR + ' ' + cpMoney(grand));
        $('#cpM4').text(CP_CUR + ' ' + cpMoney(grand));
    }

    function cpRenderDeductions() {
        $('#cp-deductions-table tbody').html(cpEmployeesData.map(function (e) {
            cpDeductionsData[e.id] = cpDeductionsData[e.id] || { attendanceDeduction: e.absent_deduction, cityLedger: 0, advanceLoan: 0, pension: 0, ewt: 0, other: 0 };
            const d = cpDeductionsData[e.id];
            return `<tr data-emp-code="${cpEsc(e.id)}">${cpEmpCell(e.name)}
                ${CP_DED_FIELDS.map(f => `<td><input type="number" step="0.01" class="dinput cp-ded" data-field="${f}" value="${cpEsc(d[f])}"></td>`).join('')}
                <td class="cp-ded-total gsep rowtot">${cpMoney(cpDeductionTotal(d))}</td>
            </tr>`;
        }).join('') || '<tr><td colspan="8" class="empty">Load earnings to enter deductions.</td></tr>');
        cpRenderDeductionFoot();
    }

    $('#cp-deductions-table').on('input', '.cp-ded', function () {
        const row = $(this).closest('tr');
        const empCode = row.data('emp-code');
        const field = $(this).data('field');
        cpDeductionsData[empCode][field] = $(this).val();
        row.find('.cp-ded-total').text(cpMoney(cpDeductionTotal(cpDeductionsData[empCode])));
        cpRenderDeductionFoot();
    });

    $('#cp-save-deductions').on('click', function () {
        if (!cpEmployeesData.length) {
            toastr.error('Load earnings first.', 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }
        const deductionData = cpEmployeesData.map(function (e) {
            const d = cpDeductionsData[e.id];
            const total = cpDeductionTotal(d);
            return {
                id: e.id, attendanceDeduction: d.attendanceDeduction, cityLedger: d.cityLedger,
                staffShop: 0, advanceLoan: d.advanceLoan, pension: d.pension, ewt: d.ewt, other: d.other, total: total,
            };
        });
        $.ajax({
            url: '{{ route('payroll.saveDeductions') }}',
            type: 'POST',
            data: { payroll_id: cpPayrollId, DeductionData: deductionData, _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (!res.success) { toastr.error(res.message, 'Error', { positionClass: 'toast-bottom-right' }); return; }
                toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
                cpRenderReview();
                cpGo(5);
            },
            error: function () { toastr.error('Could not save deductions.', 'Error', { positionClass: 'toast-bottom-right' }); }
        });
    });

    // ---- Step 5 — review (Net = Gross pay − Deductions; commission shown, never added) ----
    function cpRenderReview() {
        const rows = cpEmployeesData.map(function (e) {
            const ded = cpDeductionTotal(cpDeductionsData[e.id]);
            return { e: e, ded: ded, net: e.normal_pay - ded };
        });
        $('#cp-review-table tbody').html(rows.map(function (r) {
            const e = r.e, z = v => (Number(v) ? '' : ' z');
            return `<tr data-emp-code="${cpEsc(e.id)}">${cpEmpCell(e.name, e.position)}
                <td class="cpx-num">${e.present}</td><td class="cpx-num">${e.day_offs}</td><td class="cpx-num${z(e.unpaid_days)}">${e.unpaid_days}</td>
                <td class="cpx-num gsep">${cpMoney(e.basic_salary)}</td><td class="cpx-num">${cpMoney(e.total_ot_pay)}</td><td class="cpx-num">${cpMoney(e.normal_pay)}</td>
                <td class="cpx-num gsep">${cpMoney(r.ded)}</td><td class="net${r.net < 0 ? ' neg' : ''} gsep">${cpMoney(r.net)}</td><td class="cpx-num comm gsep">${cpMoney(e.service_provider_commission)}</td></tr>`;
        }).join(''));
        const t = f => cpSum(rows, f), net = t(r => r.net);
        $('#cp-review-table tfoot').html(rows.length ? `<tr><td class="emp">Totals · ${rows.length}</td>
            <td class="cpx-num">${t(r => r.e.present)}</td><td class="cpx-num">${t(r => r.e.day_offs)}</td><td class="cpx-num">${t(r => r.e.unpaid_days)}</td>
            <td class="cpx-num gsep">${cpMoney(t(r => r.e.basic_salary))}</td><td class="cpx-num">${cpMoney(t(r => r.e.total_ot_pay))}</td><td class="cpx-num">${cpMoney(t(r => r.e.normal_pay))}</td>
            <td class="cpx-num gsep">${cpMoney(t(r => r.ded))}</td><td class="net gsep">${cpMoney(net)}</td><td class="cpx-num comm gsep">${cpMoney(t(r => r.e.service_provider_commission))}</td></tr>` : '');
        $('#cpRevNet').text(CP_CUR + ' ' + cpMoney(net) + ' · ' + rows.length + (rows.length === 1 ? ' casual' : ' casuals'));
        $('#cpM5').text(CP_CUR + ' ' + cpMoney(net));
    }

    $('#cp-save-review').on('click', function () {
        const reviewData = cpEmployeesData.map(function (e) {
            const totalDeductions = cpDeductionTotal(cpDeductionsData[e.id]);
            return {
                id: e.id,
                serviceCharge: 0, // §35 — Casual never gets service charge
                serviceProviderCommission: e.service_provider_commission,
                overtimeNormal: e.regular_ot_pay,
                overtimeFriday: e.friday_ot_pay,
                overtimeHoliday: e.holiday_ot_pay,
                overtimeTotal: e.total_ot_pay,
                earningsBasic: e.basic_salary,
                earnedSalary: e.earned_salary,
                earningsAllowance: 0,
                earningsNormal: e.normal_pay,
                totalDeductions: totalDeductions,
                allowances: [],
            };
        });
        $.ajax({
            url: '{{ route('payroll.saveReviews') }}',
            type: 'POST',
            data: { payroll_id: cpPayrollId, reviewData: reviewData, _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (!res.success) { toastr.error(res.message, 'Error', { positionClass: 'toast-bottom-right' }); return; }
                toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
            },
            error: function () { toastr.error('Could not save review.', 'Error', { positionClass: 'toast-bottom-right' }); }
        });
    });

    $('#cp-send-approval').on('click', function () {
        $.ajax({
            url: '{{ route('payroll.send.approval') }}',
            type: 'POST',
            data: { payroll_id: cpPayrollId, _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (!res.success) { toastr.error(res.message || 'Failed.', 'Error', { positionClass: 'toast-bottom-right' }); return; }
                toastr.success(res.message, 'Success', { positionClass: 'toast-bottom-right' });
            },
            error: function () { toastr.error('Could not send for approval.', 'Error', { positionClass: 'toast-bottom-right' }); }
        });
    });
</script>
@endsection
