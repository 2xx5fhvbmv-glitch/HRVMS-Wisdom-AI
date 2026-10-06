{{--
    Shared helpers for the Talent Acquisition list screens (see _ta_list_styles). Included inside an
    existing <script> block. Pulls in the country/flag helper too.
--}}
@include('resorts.talentacquisition._flag_js')
const talEsc = v => String(v == null ? '' : v).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const talDec = s => { const t = document.createElement('textarea'); t.innerHTML = s == null ? '' : s; return t.value; };
const talIni = n => (String(n || '').trim().split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('')) || '?';
const talPill = (cls, text) => '<span class="tal-pill ' + cls + '">' + talEsc(text) + '</span>';
// Photo-first avatar; if the image is missing or broken the initials underneath show.
const talAvatar = (name, img) => '<span class="tal-av">' + talEsc(talIni(name)) + (img ? '<img src="' + talEsc(img) + '" alt="" onerror="this.remove()">' : '') + '</span>';
const talApplicant = (name, img, subHtml) => '<div class="tal-appcell">' + talAvatar(name, img) + '<div><div class="nm">' + talEsc(name) + '</div><div class="sub">' + subHtml + '</div></div></div>';
// Pipeline stage (status + approver rank) -> pill. Unknown values stay visible as a muted pill.
function talStage(status, rank) {
    const R = rank && rank !== 'Wisdom AI' && rank !== 'Unknown Rank' ? rank + ' ' : '';
    const map = {
        'Sortlisted By Wisdom AI': ['Shortlisted by WAI', 'muted'], 'Sortlisted': [R + 'Shortlisted', rank === 'EXCOM' ? 'teal' : 'violet'],
        'Round': [R + 'Interview round', 'info'], 'Complete': [R + 'Round complete', 'teal'], 'Selected': ['Selected', 'ok'],
        'Rejected': [R + 'Rejected', 'err'], 'Rejected By Wisdom AI': ['Rejected by WAI', 'err'],
        'Offer Letter Sent': ['Offer letter sent', 'warn'], 'Offer Letter Accepted': ['Offer letter accepted', 'ok'], 'Offer Letter Rejected': ['Offer letter rejected', 'err'],
        'Contract Sent': ['Contract sent', 'info'], 'Contract Accepted': ['Contract accepted', 'ok'], 'Contract Rejected': ['Contract rejected', 'err']
    };
    const m = map[status] || [String(status || '—').replace('Sortlisted', 'Shortlisted'), 'muted'];
    return talPill(m[1], m[0]);
}
const TAL_ICON = {
    eye: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>',
    video: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="6" width="13" height="12" rx="2"/><path d="M22 8l-7 4 7 4z"/></svg>'
};
// Interview cell: date, then Maldives time and the applicant's local time (flag if the country is known)
function talInterview(date, mvTime, appTime, countryName) {
    const has = v => v && v !== '-';
    if (!has(date)) return '<div class="tal-iv"><span class="none">Not scheduled</span></div>';
    const nat = taFlag(TA_COUNTRY_CODES[countryName] || '');
    const t1 = has(mvTime) ? '<span title="Maldives time">' + taFlag('MV') + '<b>' + talEsc(mvTime) + '</b></span>' : '';
    const t2 = has(appTime) ? '<span title="' + talEsc(countryName ? countryName + ' · ' : '') + 'applicant local time">' + (nat || (countryName ? '' : '<span class="ccode">Applicant</span>')) + '<b>' + talEsc(appTime) + '</b></span>' : '';
    return '<div class="tal-iv"><div class="ivd">' + talEsc(date) + '</div><div class="ivt">' + [t1, t2].filter(Boolean).join('<span class="sep">·</span>') + '</div></div>';
}
