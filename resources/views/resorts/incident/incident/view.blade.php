@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    @php
        // Presentational-only helpers for this view. No query/data changes —
        // same pattern already used in resorts/incident/incident/investigation.blade.php,
        // reused here so the two screens read identically per the redesign brief.
        $idtInitials = function ($name) {
            $parts = preg_split('/\s+/', trim((string) $name));
            $initials = '';
            foreach (array_slice($parts, 0, 2) as $part) {
                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
            }
            return $initials !== '' ? $initials : '?';
        };
        $idtPalette = ['#0E8A9E', '#6B4FA0', 'var(--positive)', 'var(--warning)', '#4A5F8A', '#A0527A'];
        $idtAvatarColor = function ($name) use ($idtPalette) {
            $hash = 0;
            foreach (str_split((string) $name) as $ch) {
                $hash = (ord($ch) + (($hash << 5) - $hash)) % 1000000007;
            }
            return $idtPalette[abs($hash) % count($idtPalette)];
        };
        $idtPillClass = function ($value) {
            $map = [
                'Low' => 'neu', 'Medium' => 'warn', 'High' => 'error',
                'Reported' => 'neu', 'Under review' => 'warn', 'Under Investigation' => 'warn',
                'Resolved' => 'ok', 'Closed' => 'ok',
            ];
            return $map[$value] ?? 'neu';
        };
        // Correctly-spelled `attachments` doesn't exist on this model — the
        // real column is the typo'd `attachements` (see $fillable) — so this
        // reads the one that actually holds data.
        $idtAttachments = [];
        if (!empty($incident->attachements)) {
            $decoded = json_decode($incident->attachements, true);
            if (is_array($decoded)) {
                foreach ($decoded as $a) {
                    if (isset($a['Filename']) && isset($a['Child_id'])) {
                        $idtAttachments[] = $a;
                    }
                }
            }
        }
        // Victims and Witnesses both render through the same scalable
        // stack/roster markup below, normalized to a plain list of
        // [name, empid, dept, photo] first. Victims is structurally 0-or-1
        // today — Incidents::victim() is a belongsTo, not hasMany — so it
        // naturally renders via this same code's "single person" / "none
        // recorded" branches without a separate path.
        $idtNormalizePeople = function ($employees) {
            $out = [];
            foreach ($employees as $emp) {
                if (!$emp) continue;
                $out[] = [
                    'name'      => $emp->resortAdmin->full_name ?? '-',
                    'empid'     => $emp->Emp_id ?? '-',
                    'dept'      => $emp->department->name ?? 'N/A',
                    'photo_id'  => $emp->Admin_Parent_id ?? null,
                    'has_photo' => !empty($emp->resortAdmin->profile_picture ?? null),
                ];
            }
            return $out;
        };
        $idtRoles = [
            [
                'label'    => 'Victims',
                'singular' => 'victim',
                'rosterId' => 'idtVictimRoster',
                'people'   => $idtNormalizePeople($incident->victim ? [$incident->victim] : []),
            ],
            [
                'label'    => 'Witnesses',
                'singular' => 'witness',
                'rosterId' => 'idtWitnessRoster',
                'people'   => $idtNormalizePeople(collect($incident->witness ?? [])->pluck('employee')),
            ],
        ];
    @endphp
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row g-3 justify-content-between">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Incident</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('incident.index') }}" class="btn btn-sm eb-btn-neutral">
                            <i class="fa-solid fa-arrow-left"></i> Back to Incidents
                        </a>
                    </div>
                </div>
            </div>

            <div class="idt-wrap">

                {{-- Hero — two panels, equal height, matches Incident Investigation's hero DNA --}}
                <div class="idt-hero">
                    <div class="idt-summary">
                        <h1>{{ $incident->incident_name }}</h1>
                        <div class="idt-class">
                            <span class="cat">{{ $incident->categoryName->category_name ?? 'Uncategorized' }}</span>
                            @if(!empty($incident->subcategoryName->subcategory_name))
                                <span class="chev">&rsaquo;</span>
                                <span class="sub">{{ $incident->subcategoryName->subcategory_name }}</span>
                            @endif
                        </div>
                        <div class="idt-desc">
                            <div class="dk">Description</div>
                            <div class="dv">{{ $incident->description }}</div>
                        </div>
                        <div class="idt-facts">
                            <div class="idt-fact"><span class="k">Reference</span><span class="pill idt-ref-pill tnum">#{{ $incident->incident_id }}</span></div>
                            <div class="idt-fact"><span class="k">Status</span><span class="pill {{ $idtPillClass($incident->status) }}"><span class="d"></span>{{ $incident->status }}</span></div>
                            <div class="idt-fact"><span class="k">Priority</span><span class="pill {{ $idtPillClass($incident->priority) }}"><span class="d"></span>{{ $incident->priority ?: '-' }}</span></div>
                            <div class="idt-fact"><span class="k">Location</span><span class="fv">{{ $incident->location ?: '-' }}</span></div>
                            <div class="idt-fact">
                                <span class="k">Evidence</span>
                                @if(count($idtAttachments))
                                    <span class="ev">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a5 5 0 01-7.07-7.07l9.19-9.19a3 3 0 014.24 4.24l-9.2 9.19a1 1 0 01-1.41-1.41l8.49-8.49"/></svg>
                                        <a href="javascript:void(0)" class="download-link" data-id="{{ base64_encode($idtAttachments[0]['Child_id']) }}">{{ $idtAttachments[0]['Filename'] }}</a>
                                        @if(count($idtAttachments) > 1)
                                            <span class="idt-more-ev">+{{ count($idtAttachments) - 1 }}</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="ev"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a5 5 0 01-7.07-7.07l9.19-9.19a3 3 0 014.24 4.24l-9.2 9.19a1 1 0 01-1.41-1.41l8.49-8.49"/></svg>No attachment available</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="idt-rail">
                        <div class="rh">Reported by</div>
                        <div class="idt-who">
                            <span class="idt-av idt-bigav" style="background:{{ $idtAvatarColor($incident->reporter->resortAdmin->full_name ?? '') }};color:#fff">
                                @if(!empty($incident->reporter->resortAdmin->profile_picture ?? null))
                                    <img src="{{ Common::getResortUserPicture($incident->reporter->Admin_Parent_id) }}" alt="" onerror="this.remove()">
                                @endif
                                {{ $idtInitials($incident->reporter->resortAdmin->full_name ?? '') }}
                            </span>
                            <div class="nm">{{ $incident->reporter->resortAdmin->full_name ?? '-' }}</div>
                        </div>
                        <div class="idt-klist">
                            <div class="idt-krow"><span class="kk">Employee ID</span><span class="kv">{{ $incident->reporter->Emp_id ?? '-' }}</span></div>
                            <div class="idt-krow"><span class="kk">Department</span><span class="kv">{{ $incident->reporter->department->name ?? 'N/A' }}</span></div>
                            <div class="idt-krow"><span class="kk">Reported</span><span class="kv">{{ \Carbon\Carbon::parse($incident->created_at)->format('d M Y') }}</span></div>
                            <div class="idt-krow"><span class="kk">Time</span><span class="kv">{{ \Carbon\Carbon::parse($incident->created_at)->format('h:i A') }}</span></div>
                        </div>
                    </div>
                </div>

                {{-- People involved — scalable Victims | Witnesses, two columns --}}
                <div class="idt-card idt-pad idt-mb">
                    <div class="sec-h">People involved</div>
                    <div class="idt-people2">
                        @foreach($idtRoles as $role)
                            @php $ppl = $role['people']; $n = count($ppl); @endphp
                            <div class="idt-role">
                                <div class="idt-cap">{{ $role['label'] }}</div>
                                @if($n === 0)
                                    <div class="idt-none">None recorded</div>
                                @elseif($n === 1)
                                    @php $p = $ppl[0]; @endphp
                                    <div class="idt-rline">
                                        <span class="idt-av" style="background:{{ $idtAvatarColor($p['name']) }};color:#fff">
                                            @if($p['has_photo'])
                                                <img src="{{ Common::getResortUserPicture($p['photo_id']) }}" alt="" onerror="this.remove()">
                                            @endif
                                            {{ $idtInitials($p['name']) }}
                                        </span>
                                        <div class="idt-pmeta">
                                            <div class="idt-nm">{{ $p['name'] }} <span class="idt-id">#{{ $p['empid'] }}</span></div>
                                            <div class="idt-sum">{{ $p['dept'] }}</div>
                                        </div>
                                    </div>
                                @else
                                    <div class="idt-rline">
                                        <div class="idt-avstack">
                                            @foreach(array_slice($ppl, 0, 5) as $p)
                                                <span class="idt-av" style="background:{{ $idtAvatarColor($p['name']) }};color:#fff">
                                                    @if($p['has_photo'])
                                                        <img src="{{ Common::getResortUserPicture($p['photo_id']) }}" alt="" onerror="this.remove()">
                                                    @endif
                                                    {{ $idtInitials($p['name']) }}
                                                </span>
                                            @endforeach
                                            @if($n > 5)
                                                <span class="idt-av idt-more">+{{ $n - 5 }}</span>
                                            @endif
                                        </div>
                                        <div class="idt-pmeta">
                                            <div class="idt-nm">{{ $ppl[0]['name'] }} <span class="idt-id">#{{ $ppl[0]['empid'] }}</span></div>
                                            <div class="idt-sum">{{ $n }} {{ Str::plural($role['singular'], $n) }}</div>
                                        </div>
                                        <button type="button" class="idt-viewall" data-target="{{ $role['rosterId'] }}">View all {{ $n }} &rarr;</button>
                                    </div>
                                    <div class="idt-roster" id="{{ $role['rosterId'] }}" hidden>
                                        @foreach($ppl as $p)
                                            <div class="idt-rrow">
                                                <span class="idt-av" style="background:{{ $idtAvatarColor($p['name']) }};color:#fff">
                                                    @if($p['has_photo'])
                                                        <img src="{{ Common::getResortUserPicture($p['photo_id']) }}" alt="" onerror="this.remove()">
                                                    @endif
                                                    {{ $idtInitials($p['name']) }}
                                                </span>
                                                <span class="idt-rn">{{ $p['name'] }}</span>
                                                <span class="idt-rid">#{{ $p['empid'] }}</span>
                                                <span class="idt-rr">{{ $p['dept'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Manage incident — existing form fields/route/handler, restyled only --}}
                <div class="idt-card idt-manage">
                    <div class="idt-mh"><div class="t">Manage incident</div></div>
                    <form id="incidentForm">
                        @csrf
                        <input type="hidden" name="incident_id" value="{{$incident->id}}"/>
                        <div class="idt-frow">
                            <div class="idt-fld">
                                <label for="priority_level">Priority level</label>
                                <select class="form-select select2t-none" id="priority_level" name="priority" aria-label="Priority level">
                                    <option value="">Select Priority</option>
                                    <option value="Low" {{ $incident->priority == "Low" ? 'selected' : '' }}>Low</option>
                                    <option value="Medium" {{ $incident->priority == "Medium" ? 'selected' : '' }}>Medium</option>
                                    <option value="High" {{ $incident->priority == "High" ? 'selected' : '' }}>High</option>
                                </select>
                            </div>
                            <div class="idt-fld">
                                <label for="status">Status</label>
                                <select class="form-select select2t-none" name="status" id="status" aria-label="Status">
                                    <option value="">Select Status</option>
                                    @if($statuses)
                                        @foreach($statuses as $st)
                                            <option value="{{$st}}" {{ $incident->status == $st ? 'selected' : '' }}>{{$st}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="idt-fld">
                                <label for="assign_to">Assign to</label>
                                <select class="form-select select2t-none" name="assigned_commiteee[]" id="assign_to" aria-label="Assign to" multiple>
                                    <option value="">Select Committee</option>
                                    @if($incident_committee)
                                        @foreach($incident_committee as $committee)
                                            <option {{ in_array($committee->id, json_decode($incident->assigned_to, true) ?? []) ? 'selected' : '' }} value="{{$committee->id}}">{{$committee->commitee_name}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="idt-full">
                            <label for="add_comments">Add comments</label>
                            <textarea class="form-control idt-ctrl" name="comments" id="add_comments" placeholder="Type here&hellip;" rows="3">{{ $incident->comments }}</textarea>
                        </div>
                        <div class="idt-mfoot">
                            <button type="submit" class="btn eb-btn-primary">Submit</button>
                        </div>
                    </form>
                </div>

            </div>

        </div>
    </div>
     @include('partials._file_view_modal')
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
<style>
.idt-wrap{width:100%}
.idt-wrap .tnum{font-variant-numeric:tabular-nums}
.idt-wrap [hidden]{display:none}

.idt-card{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.05)}
.idt-wrap .sec-h{font-size:11px;font-weight:600;letter-spacing:.7px;text-transform:uppercase;color:var(--muted);margin-bottom:16px}

/* ===== hero ===== */
.idt-hero{display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:stretch;margin:22px 0 16px}
@media(max-width:900px){.idt-hero{grid-template-columns:1fr}}
.idt-summary{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.05);padding:24px 26px}
.idt-summary h1{font-size:26px;font-weight:600;letter-spacing:-.4px;line-height:1.15;color:var(--ink);text-transform:capitalize;margin:0}
.idt-class{display:flex;align-items:center;gap:8px;font-size:12.5px;margin-top:10px}
.idt-class .cat{color:var(--teal);font-weight:500}
.idt-class .chev{color:var(--faint)}
.idt-class .sub{color:var(--muted)}
.idt-desc{margin-top:18px}
.idt-desc .dk{font-size:10.5px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;color:var(--faint);margin-bottom:5px}
.idt-desc .dv{font-size:14.5px;color:var(--ink);line-height:1.5;text-transform:capitalize}
.idt-facts{display:flex;flex-wrap:wrap;gap:16px 26px;margin-top:20px;padding-top:18px;border-top:1px solid var(--line-2)}
.idt-fact{display:flex;flex-direction:column;gap:6px}
.idt-fact .k{font-size:10.5px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;color:var(--faint)}
.idt-fact .fv{font-size:14px;font-weight:500;color:var(--ink);text-transform:capitalize}
.idt-fact .ev{display:inline-flex;align-items:center;gap:7px;font-size:13px;color:var(--muted)}
.idt-fact .ev svg{color:var(--faint);flex:none}
.idt-more-ev{font-size:11px;font-weight:600;color:var(--faint)}

.idt-wrap .pill{display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:500;color:var(--ink);background:var(--neutral-bg);padding:5px 12px;border-radius:20px}
.idt-wrap .pill .d{width:6px;height:6px;border-radius:50%;background:currentColor;flex:none}
.idt-wrap .pill.warn{background:var(--warning-bg);color:var(--warning)}
.idt-wrap .pill.ok{background:var(--positive-bg);color:var(--positive)}
.idt-wrap .pill.error{background:var(--error-bg);color:var(--error)}
.idt-wrap .pill.neu{background:var(--neutral-bg);color:var(--muted)}
.idt-wrap .pill.idt-ref-pill{background:var(--teal-3);color:var(--teal);font-weight:600}

.idt-rail{background:var(--teal-soft);border:1px solid var(--line);border-radius:16px;padding:22px 24px;display:flex;flex-direction:column;gap:16px}
.idt-rail .rh{font-size:11px;font-weight:600;letter-spacing:.7px;text-transform:uppercase;color:var(--teal)}
.idt-who{display:flex;align-items:center;gap:13px}
.idt-who .nm{font-size:16.5px;font-weight:600;color:var(--ink);letter-spacing:-.2px}
.idt-klist{display:flex;flex-direction:column}
.idt-krow{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:11px 0;border-bottom:1px solid var(--line)}
.idt-krow:last-child{border-bottom:none;padding-bottom:0}
.idt-krow .kk{font-size:12.5px;color:var(--muted)}
.idt-krow .kv{font-size:13.5px;font-weight:600;color:var(--ink);text-align:right;font-variant-numeric:tabular-nums}

/* ===== avatars (shared by rail + people-involved) ===== */
.idt-av{width:40px;height:40px;border-radius:50%;flex:none;object-fit:cover;display:grid;place-items:center;font-size:13px;font-weight:600;letter-spacing:.3px;position:relative;overflow:hidden;border:2px solid #fff}
.idt-av img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block}
.idt-bigav{width:52px;height:52px;font-size:15px;border:none}

/* ===== people involved (scalable) ===== */
.idt-pad{padding:22px 24px}
.idt-mb{margin-bottom:16px}
.idt-people2{display:grid;grid-template-columns:1fr 1fr;gap:0;align-items:start;margin-top:16px}
.idt-role{padding:0 30px}
.idt-role:first-child{padding-left:0}
.idt-role:last-child{padding-right:0;border-left:1px solid var(--line-2)}
@media(max-width:767px){
  .idt-people2{grid-template-columns:1fr}
  .idt-role{padding:16px 0;border-left:none;border-top:1px solid var(--line-2)}
  .idt-role:first-child{padding-top:0;border-top:none}
}
.idt-cap{font-size:10.5px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;color:var(--faint);margin-bottom:10px}
.idt-none{font-size:13px;color:var(--faint)}
.idt-rline{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.idt-avstack{display:flex;flex:none}
.idt-avstack .idt-av:not(:first-child){margin-left:-13px}
.idt-avstack .idt-more{background:var(--neutral-bg);color:var(--muted);font-size:12px}
.idt-pmeta{display:flex;flex-direction:column;gap:1px;min-width:0}
.idt-pmeta .idt-nm{font-size:14.5px;font-weight:600;color:var(--ink);display:flex;align-items:baseline;gap:8px;flex-wrap:wrap}
.idt-pmeta .idt-id{font-size:11px;font-weight:600;color:var(--teal);background:var(--teal-3);padding:1px 7px;border-radius:20px;font-variant-numeric:tabular-nums}
.idt-pmeta .idt-sum{font-size:12.5px;color:var(--muted)}
.idt-viewall{background:none;border:none;font-family:inherit;font-size:12px;font-weight:600;color:var(--teal);cursor:pointer;padding:0;margin-left:auto;white-space:nowrap}
.idt-viewall:hover{text-decoration:underline}
.idt-roster{margin-top:12px;border:1px solid var(--line);border-radius:12px;max-height:200px;overflow:auto}
.idt-rrow{display:flex;align-items:center;gap:11px;padding:9px 13px;border-bottom:1px solid var(--line-2)}
.idt-rrow:last-child{border-bottom:none}
.idt-rrow .idt-av{width:32px;height:32px;font-size:11px}
.idt-rrow .idt-rn{font-size:13px;font-weight:500;color:var(--ink)}
.idt-rrow .idt-rid{font-size:10.5px;font-weight:600;color:var(--teal);background:var(--teal-3);padding:1px 6px;border-radius:20px;font-variant-numeric:tabular-nums}
.idt-rrow .idt-rr{font-size:11.5px;color:var(--muted);margin-left:auto}

/* ===== manage form ===== */
.idt-manage{padding:24px 26px}
.idt-mh{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
.idt-mh .t{font-size:15px;font-weight:600;color:var(--ink)}
.idt-frow{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
@media(max-width:767px){.idt-frow{grid-template-columns:1fr}}
.idt-fld{display:flex;flex-direction:column;gap:7px}
.idt-fld label{font-size:11px;font-weight:600;letter-spacing:.4px;text-transform:uppercase;color:var(--muted)}
.idt-full{margin-top:16px;display:flex;flex-direction:column;gap:7px}
.idt-full label{font-size:11px;font-weight:600;letter-spacing:.4px;text-transform:uppercase;color:var(--muted)}
.idt-ctrl{font-family:inherit;font-size:14px;color:var(--ink);border-color:var(--faint);border-radius:10px;padding:11px 13px}
.idt-mfoot{display:flex;justify-content:flex-end;margin-top:18px}
</style>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function(){
        $('.select2t-none').select2();

        $("#incidentForm").submit(function (e) {
            e.preventDefault(); // Prevent default form submission

            let formData = new FormData($("#incidentForm")[0]);

            // Manually append assigned committee values as an array
            let assignedCommittees = $("#assign_to").val(); // This returns an array
            if (assignedCommittees) {
                assignedCommittees.forEach(id => formData.append("assigned_commiteee[]", id)); // Ensure array format
            }

            $.ajax({
                url: "{{ route('incident.assign') }}", // Adjust the route accordingly
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    setTimeout(function() {
                        window.location.href = response.redirect_url;
                    }, 1000);

                },
                error: function (xhr) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessage = "An error occurred!";
                    if (errors) {
                        errorMessage = Object.values(errors).join("\n");
                    }

                    toastr.error(errorMessage, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        $(document).on("click", ".download-link", function(e) {
            e.preventDefault();
            var childId = $(this).data('id');
            var $downloadLink = $(this);

            // First, set a loading message
            $("#ViewModeOfFiles").html('<div class="text-center"><p>A file link is being generated. Please wait...</p><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');

            // Show the modal with the loading message
            $("#bdVisa-iframeModel-modal-lg").modal('show');

            $.ajax({
                url: "{{ route('resort.visa.XpactEmpFileDownload', '') }}/" + childId,
                type: 'GET',
                data: { child_id: childId, "_token":"{{csrf_token()}}"},
                success: function(response)
                {
                    let fileUrl = response.NewURLshow;
                    $(".downloadLink").attr("href", fileUrl);

                    let mimeType = response.mimeType.toLowerCase();
                    let iframeTypes = [
                                        'video/mp4', 'video/quicktime', 'video/x-msvideo', // Videos
                                        'application/pdf', 'text/plain',                   // PDF & Text
                                        'application/msword', 'application/vnd.ms-excel'   // Word & Excel
                                    ];
                    let imageTypes = ['image/jpeg', 'image/png', 'image/gif'];

                    // Clear the loading message and show the actual content
                    if (imageTypes.includes(mimeType))
                    {
                        $("#ViewModeOfFiles").html(`
                            <img src="${fileUrl}" class="popupimgFileModule" onclick="showImage('${fileUrl}')" alt="Image Preview">`);
                    }
                    // If file type is supported for iframe display
                    else if (iframeTypes.includes(mimeType)) {
                        $("#ViewModeOfFiles").html(`
                            <iframe style="width: 100%; height: 100%;" src="${fileUrl}" allowfullscreen></iframe>
                        `);
                    }
                    else {
                        $("#bdVisa-iframeModel-modal-lg").modal('hide');
                        // window.location.href = fileUrl; // Triggers download automatically
                    }
                },
                error: function(xhr, status, error)
                {
                    $("#bdVisa-iframeModel-modal-lg").modal('hide');
                    toastr.error("An error occurred while downloading the file.", "Error", { positionClass: 'toast-bottom-right' });
                }
            });
        });

        // Roster "View all" toggle — tiny vanilla JS, no library, keyboard
        // accessible (real <button>, native Enter/Space activation).
        document.querySelectorAll('.idt-viewall').forEach(function (b) {
            var label = b.textContent;
            b.addEventListener('click', function () {
                var r = document.getElementById(b.dataset.target);
                r.hidden = !r.hidden;
                b.textContent = r.hidden ? label : 'Hide ↑';
            });
        });
    });
</script>
@endsection
