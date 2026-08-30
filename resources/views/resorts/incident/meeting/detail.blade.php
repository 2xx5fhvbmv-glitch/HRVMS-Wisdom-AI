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
        // same pattern already used in resorts/search/index.blade.php and
        // the Incident Investigation redesign.
        $mtgInitials = function ($name) {
            $parts = preg_split('/\s+/', trim((string) $name));
            $initials = '';
            foreach (array_slice($parts, 0, 2) as $part) {
                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
            }
            return $initials !== '' ? $initials : '?';
        };
        $mtgPalette = ['#0E8A9E', '#6B4FA0', 'var(--positive)', 'var(--warning)', '#4A5F8A', '#A0527A'];
        $mtgAvatarColor = function ($name) use ($mtgPalette) {
            $hash = 0;
            foreach (str_split((string) $name) as $ch) {
                $hash = (ord($ch) + (($hash << 5) - $hash)) % 1000000007;
            }
            return $mtgPalette[abs($hash) % count($mtgPalette)];
        };
        // Location is free text in this app (room names like "HR Office" are
        // just as common as real URLs, even on Online-type meetings — real
        // data checked), so only render it as a link when it actually looks
        // like one. Never fabricate a link for a room name.
        $mtgLocationUrl = null;
        if (!empty($meeting->location) && preg_match('/^(https?:\/\/|www\.)/i', trim($meeting->location))) {
            $mtgLocationUrl = preg_match('/^https?:\/\//i', $meeting->location) ? $meeting->location : 'https://' . $meeting->location;
        }
        $mtgAttachments = [];
        $decodedAttachments = json_decode($meeting->attachments, true);
        if (is_array($decodedAttachments)) {
            foreach ($decodedAttachments as $a) {
                if (is_array($a) && !empty($a['Filename']) && !empty($a['Child_id'])) {
                    $mtgAttachments[] = $a;
                }
            }
        }
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
                        <a href="{{ route('incident.meeting') }}" class="btn btn-sm eb-btn-neutral">
                            <i class="fa-solid fa-arrow-left"></i> Back to Meetings
                        </a>
                    </div>
                </div>
            </div>

            <div class="mtg-wrap">

                {{-- Hero: meeting summary card + teal-soft info rail, equal height --}}
                <div class="mtg-hero">
                    <div class="mtg-main">
                        <div class="mtg-titleline">
                            <h2>{{ $meeting->meeting_subject }}</h2>
                        </div>

                        <div class="mtg-block mtg-block-first">
                            <span class="mtg-mlabel">Agenda</span>
                            <div class="mtg-agenda">{{ $meeting->meeting_agenda }}</div>
                        </div>

                        <div class="mtg-block">
                            <div class="mtg-cols2">
                                <div>
                                    <span class="mtg-mlabel">Participants</span>
                                    <div class="mtg-people">
                                        @if($meeting->participant && count($meeting->participant))
                                            @foreach($meeting->participant as $participant)
                                                @php
                                                    $pName = $participant->employee->resortAdmin->full_name ?? 'Unknown';
                                                    $pHasPhoto = !empty($participant->employee->resortAdmin->profile_picture ?? null);
                                                    $pRole = $participant->participant_role ?? null;
                                                @endphp
                                                <div class="mtg-person">
                                                    <span class="av" style="background:{{ $mtgAvatarColor($pName) }};color:#fff">
                                                        @if($pHasPhoto)
                                                            <img src="{{ Common::getResortUserPicture($participant->employee->Admin_Parent_id) }}" alt="" onerror="this.remove()">
                                                        @endif
                                                        {{ $mtgInitials($pName) }}
                                                    </span>
                                                    <div>
                                                        <div class="n">{{ $pName }}</div>
                                                        @if($pRole)
                                                            <span class="role">{{ $pRole }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="mtg-none">No participants</div>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <span class="mtg-mlabel">External participants</span>
                                    @if($meeting->externalParticipant && count($meeting->externalParticipant))
                                        <div class="mtg-people">
                                            @foreach($meeting->externalParticipant as $ext)
                                                @if(!empty($ext->participant_name))
                                                    <div class="mtg-person">
                                                        <span class="av" style="background:{{ $mtgAvatarColor($ext->participant_name) }};color:#fff">{{ $mtgInitials($ext->participant_name) }}</span>
                                                        <div><div class="n">{{ $ext->participant_name }}</div></div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="mtg-none">None</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mtg-info">
                        <div class="mtg-ihead">Meeting</div>
                        <div class="mtg-ibody">
                            <div class="mtg-r"><span class="l">Reference</span><span class="mtg-ref">#{{ $meeting->incidents->incident_id ?? '-' }}</span></div>
                            <div class="mtg-r"><span class="l">Date</span><span class="v">{{ $meeting->meeting_date ? \Carbon\Carbon::parse($meeting->meeting_date)->format('d M Y') : '-' }}</span></div>
                            <div class="mtg-r"><span class="l">Time</span><span class="v">{{ $meeting->meeting_time ? \Carbon\Carbon::parse($meeting->meeting_time)->format('h:i A') : '-' }}</span></div>
                            <div class="mtg-r"><span class="l">Type</span><span class="pill"><span class="d"></span>{{ $meeting->meeting_type ?: '-' }}</span></div>
                            <div class="mtg-r">
                                <span class="l">Location</span>
                                @if($mtgLocationUrl)
                                    <a class="mtg-link" href="{{ $mtgLocationUrl }}" target="_blank" rel="noopener">{{ $meeting->location }}</a>
                                @else
                                    <span class="v">{{ $meeting->location ?: '-' }}</span>
                                @endif
                            </div>
                            <div class="mtg-r mtg-r-attach">
                                <span class="l">Attachments</span>
                                <div class="mtg-attlist">
                                    @if(count($mtgAttachments))
                                        @foreach($mtgAttachments as $attachment)
                                            <a href="javascript:void(0)" class="mtg-filechip download-link" data-id="{{ base64_encode($attachment['Child_id']) }}" title="{{ $attachment['Filename'] }}">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12l-9 9a5 5 0 01-7-7l9-9a3.5 3.5 0 015 5l-9 9a2 2 0 01-3-3l8-8"/></svg>
                                                <span class="fn">{{ $attachment['Filename'] }}</span>
                                            </a>
                                        @endforeach
                                    @else
                                        <span class="v">No attachments</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Previous notes / findings — connected date-rail --}}
                <section class="mtg-sec">
                    <div class="mtg-sec-h">
                        <h2>Previous notes &amp; findings</h2>
                        <span class="mtg-countchip">{{ count($previousMeetings ?? []) }} {{ Str::plural('meeting', count($previousMeetings ?? [])) }}</span>
                    </div>
                    <div class="mtg-card">
                        @if(!isset($previousMeetings) || $previousMeetings->isEmpty())
                            <div class="mtg-none">No previous meetings have been recorded for this incident.</div>
                        @else
                            <div class="mtg-dl">
                                <div class="mtg-rail-spark"><i></i></div>
                                <span class="mtg-rail-burst"></span>
                                @foreach($previousMeetings as $pm)
                                    <div class="mtg-row">
                                        <div class="mtg-dchip">
                                            <div class="d">{{ \Carbon\Carbon::parse($pm->meeting_date)->format('d M') }}</div>
                                            <div class="t">{{ $pm->meeting_time ? \Carbon\Carbon::parse($pm->meeting_time)->format('h:i A') : '' }}</div>
                                        </div>
                                        <div class="mtg-body">
                                            <div class="tt">{{ $pm->meeting_subject }}</div>
                                            <div class="tn">{{ $pm->meeting_agenda }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>

            </div>
        </div>
    </div>
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
<style>
/* Incident Meeting Detail — restyle only. Reuses the app's real global
   tokens (--teal/--ink/--muted/--faint/--line/--card/--positive/--warning/
   --error, from resorts/layouts/_design_tokens.blade.php, included by the
   base layout on every page) directly rather than inventing a parallel
   token set. Scoped under .mtg-* so nothing here can leak onto other pages. */
.mtg-wrap{width:100%}

.mtg-hero{display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:stretch}
@media(max-width:860px){.mtg-hero{grid-template-columns:1fr}}
.mtg-main{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.05);padding:26px 28px}
.mtg-titleline{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.mtg-main h2{font-size:22px;font-weight:600;letter-spacing:-.4px;color:var(--ink);line-height:1.22;margin:0}
.mtg-block{margin-top:22px;padding-top:20px;border-top:1px solid var(--line)}
.mtg-block-first{margin-top:18px}
.mtg-block .mtg-mlabel{display:block;margin-bottom:8px}
.mtg-agenda{font-size:14px;color:var(--muted);line-height:1.6;max-width:760px}
.mtg-mlabel{font-size:10.5px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:var(--faint);display:block}

.mtg-wrap .av{border-radius:50%;display:grid;place-items:center;font-weight:600;flex:none;position:relative;overflow:hidden;font-size:12px}
.mtg-wrap .av img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block}

.mtg-people{display:flex;flex-wrap:wrap;gap:12px}
.mtg-person{display:flex;align-items:center;gap:11px;border:1px solid var(--line);border-radius:12px;padding:9px 14px 9px 9px;background:var(--card)}
.mtg-person .av{width:38px;height:38px}
.mtg-person .n{font-size:14px;font-weight:500;color:var(--ink)}
.mtg-person .role{font-size:11px;font-weight:600;color:var(--muted);background:var(--neutral-bg);padding:2px 9px;border-radius:20px;display:inline-block;margin-top:3px}
.mtg-none{font-size:14px;color:var(--faint)}
.mtg-cols2{display:grid;grid-template-columns:1fr 1fr;gap:24px}
@media(max-width:600px){.mtg-cols2{grid-template-columns:1fr;gap:18px}}

/* info rail (teal-soft) */
.mtg-info{background:var(--teal-soft);border:1px solid var(--line);border-radius:18px;overflow:hidden}
.mtg-ihead{font-size:14px;font-weight:600;color:var(--ink);padding:15px 20px;border-bottom:1px solid var(--line)}
.mtg-ibody{padding:6px 20px 10px}
.mtg-r{display:flex;justify-content:space-between;gap:14px;padding:12px 0;border-bottom:1px solid var(--line-2);align-items:baseline}
.mtg-r:last-child{border-bottom:none}
.mtg-r .l{font-size:12.5px;color:var(--muted);white-space:nowrap;flex:none}
.mtg-r .v{font-size:14px;font-weight:500;color:var(--ink);text-align:right;font-variant-numeric:tabular-nums}
.mtg-r-attach{align-items:flex-start}
.mtg-attlist{display:flex;flex-direction:column;align-items:flex-end;gap:6px}

.pill{display:inline-flex;align-items:center;gap:6px;font-size:12.5px;font-weight:600;padding:3px 11px;border-radius:20px;background:var(--teal-3);color:var(--teal)}
.pill .d{width:6px;height:6px;border-radius:50%;background:currentColor}
.mtg-link{font-size:14px;font-weight:500;color:var(--teal);text-decoration:none;word-break:break-all;text-align:right}
.mtg-link:hover{text-decoration:underline}
.mtg-filechip{display:inline-flex;align-items:center;gap:7px;font-size:14px;font-weight:500;color:var(--teal);text-decoration:none;max-width:180px}
.mtg-filechip:hover{text-decoration:underline}
.mtg-filechip .fn{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

/* previous notes / findings — timeline */
.mtg-sec{margin-top:26px}
.mtg-sec-h{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.mtg-sec-h h2{font-size:18px;font-weight:600;color:var(--ink);margin:0}
.mtg-sec-h .mtg-countchip{font-size:11px;font-weight:600;color:var(--muted);background:var(--card);border:1px solid var(--line);border-radius:20px;padding:3px 10px}
.mtg-sec-h::after{content:"";flex:1;height:1px;background:var(--line)}
.mtg-card{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 1px 2px rgba(1,70,83,.04),0 10px 26px rgba(1,70,83,.05);padding:24px 26px}

/* previous notes — connected date-rail (date chips strung on a teal thread) */
.mtg-dl{position:relative}
.mtg-dl::before{content:"";position:absolute;left:51px;top:42px;bottom:42px;width:2px;background:rgba(1,70,83,.22);z-index:0}
.mtg-row{display:flex;gap:18px;padding:16px 0;border-bottom:1px solid var(--line-2);position:relative;z-index:1}
.mtg-row:last-child{border-bottom:none}
.mtg-dchip{flex:none;width:104px;background:var(--teal-soft);border:1px solid var(--line);border-radius:12px;padding:10px 8px;text-align:center;align-self:flex-start;position:relative;z-index:1}
.mtg-dchip .d{font-size:13px;font-weight:600;color:var(--teal);font-variant-numeric:tabular-nums}
.mtg-dchip .t{font-size:10.5px;color:var(--muted);margin-top:2px;font-variant-numeric:tabular-nums}
.mtg-body{min-width:0}
.mtg-body .tt{font-size:14px;font-weight:600;color:var(--ink)}
.mtg-body .tn{font-size:14px;color:var(--muted);line-height:1.6;margin-top:4px;max-width:900px}

/* travelling bubble down the thread — pure CSS, one tiny element, reduced-motion aware.
   transform+opacity only (plus a `top` keyframe on this single 6px dot) — no layout thrash. */
.mtg-rail-spark{position:absolute;left:45px;top:42px;bottom:42px;width:12px;z-index:0;pointer-events:none}
.mtg-rail-spark i{position:absolute;left:3px;width:6px;height:6px;border-radius:50%;background:var(--teal);box-shadow:0 0 8px 2px rgba(1,70,83,.4);animation:mtgRailFlow 6s ease-in-out infinite}
@keyframes mtgRailFlow{0%{top:100%;opacity:0}10%{opacity:1}90%{opacity:1}100%{top:0;opacity:0}}
/* arrival ripple at the top chip — blooms once per loop as the bubble lands */
.mtg-rail-burst{position:absolute;left:22px;top:13px;width:60px;height:60px;border-radius:50%;border:2px solid var(--teal);z-index:2;pointer-events:none;opacity:0;transform:scale(.4);transform-origin:center;animation:mtgRailBurst 6s ease-out infinite}
@keyframes mtgRailBurst{0%,84%{opacity:0;transform:scale(.4)}88%{opacity:.6;transform:scale(.7)}100%{opacity:0;transform:scale(1.5)}}
@media(prefers-reduced-motion:reduce){.mtg-rail-spark,.mtg-rail-burst{display:none}}
</style>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function(){
        $('.select2t-none').select2();

        // Resolve a Child_id to a presigned Wasabi URL and open in a new tab.
        $(document).on('click', '.download-link', function (e) {
            e.preventDefault();
            var childId = $(this).data('id');
            if (!childId) return;
            $.ajax({
                url: "{{ route('resort.visa.XpactEmpFileDownload', '') }}/" + childId,
                type: 'GET',
                success: function (response) {
                    if (response && response.NewURLshow) {
                        window.open(response.NewURLshow, '_blank');
                    } else {
                        toastr.error('File not found.', 'Error', { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function () {
                    toastr.error('Failed to open file.', 'Error', { positionClass: 'toast-bottom-right' });
                }
            });
        });

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
                    location.reload(); // Reload page after update
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

    })
</script>
@endsection
