@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<style>
    #learning-program-show-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #learning-program-show-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="lp-wrap">
            <div class="page-hedding" id="learning-program-show-hero">
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Learning &amp; Development</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('learning.programs.index') }}" class="btn lnd-btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            {{-- HERO --}}
            <div class="lp-hero">
                <div class="lp-hero-in">
                    <div class="lp-cat">{{ optional($program->category)->category ?? 'Uncategorised' }}</div>
                    <h1>{{ $program->name }}</h1>
                    <div class="lp-metastrip">
                        <div class="mi">
                            <div class="ml">Delivery</div>
                            <div class="mv"><span class="mode"><span class="d {{ $program->delivery_mode }}"></span>{{ ucfirst($program->delivery_mode) }}</span></div>
                        </div>
                        <div class="mi">
                            <div class="ml">Duration</div>
                            @php
                                $parts = [];
                                if (!empty($program->days))  $parts[] = $program->days . ' Day' . ($program->days > 1 ? 's' : '');
                                if (!empty($program->hours)) $parts[] = $program->hours . ' hrs';
                            @endphp
                            <div class="mv tnum">{{ $parts ? implode(' · ', $parts) : '-' }}</div>
                        </div>
                        <div class="mi">
                            <div class="ml">Frequency</div>
                            <div class="mv">{{ ucfirst($program->frequency) }}{{ $program->frequency_day ? ' — Day ' . $program->frequency_day : '' }}</div>
                        </div>
                        <div class="mi">
                            <div class="ml">Trainer</div>
                            <div class="mv">
                                @if($trainer && $trainer->resortAdmin)
                                    <img class="trainer-av" src="{{ App\Helpers\Common::getResortUserPicture($trainer->resortAdmin->id) }}" alt="">
                                    {{ trim($trainer->resortAdmin->first_name.' '.$trainer->resortAdmin->last_name) }}
                                @elseif($program->external_training || $program->external_trainer_company)
                                    @if($program->trainer_image)
                                        <img class="trainer-av" src="{{ route('learning.programs.trainerImage', base64_encode($program->id)) }}" alt="">
                                    @else
                                        <span class="trainer-av"></span>
                                    @endif
                                    {{ $program->external_training ?: $program->external_trainer_company }}
                                @else
                                    <span style="color:var(--faint)">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="mi">
                            <div class="ml">Audience type</div>
                            <div class="mv">{{ ucfirst($program->audience_type ?? '-') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- BODY --}}
            <div class="lp-body">
                <div class="lp-stack">
                    <div class="lp-card">
                        <div class="sec-t">Description</div>
                        <p class="prose">{!! nl2br(e($program->description)) !!}</p>
                    </div>

                    <div class="lp-card">
                        <div class="sec-t">Objectives &amp; Goals</div>
                        @php
                            // Split directly on the bullet/dash/asterisk marker itself (not \n) —
                            // stored objectives use tabs between bullets, not newlines, and the
                            // /u modifier is required since • is multi-byte UTF-8: without it,
                            // preg_replace strips only the first byte, corrupting the string so
                            // it renders blank.
                            $objectiveLines = collect(preg_split('/[•\-\*]/u', (string) $program->objectives))
                                ->map(fn($line) => trim($line))
                                ->filter(fn($line) => $line !== '')
                                ->values();
                        @endphp
                        @if($objectiveLines->isNotEmpty())
                            <ul class="obj">
                                @foreach($objectiveLines as $line)
                                    <li><span class="n">{{ $loop->iteration }}</span><span>{{ $line }}</span></li>
                                @endforeach
                            </ul>
                        @else
                            <div class="pq none">No objectives listed.</div>
                        @endif
                    </div>

                    <div class="lp-card">
                        <div class="sec-t">Prior Qualification</div>
                        @if($program->prior_qualification)
                            <div class="pq">{!! nl2br(e($program->prior_qualification)) !!}</div>
                        @else
                            <div class="pq none">None required</div>
                        @endif
                    </div>
                </div>

                <div class="lp-rail">
                    <div class="lp-card">
                        <div class="sec-t">Target Audience</div>
                        @if(count($audienceLabels) > 0)
                            <div class="aud-list">
                                @foreach($audienceLabels as $label)
                                    <div class="a"><span class="b"></span>{{ $label }}</div>
                                @endforeach
                            </div>
                        @else
                            <div class="pq none">No audience configured.</div>
                        @endif
                    </div>

                    <div class="lp-card">
                        <div class="sec-t">Learning Materials</div>
                        @if(isset($materials) && $materials->count() > 0)
                            @foreach($materials as $m)
                                @php $fname = preg_replace('/^\d+_/', '', basename($m->file_path)); @endphp
                                <a class="mat" href="{{ route('learning.programs.material', base64_encode($m->id)) }}">
                                    <span class="fic">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                    </span>
                                    <div style="flex:1;min-width:0">
                                        <div class="fn">{{ $fname }}</div>
                                        <div class="fm">{{ strtoupper(pathinfo($fname, PATHINFO_EXTENSION)) ?: 'FILE' }}</div>
                                    </div>
                                    <span class="dl">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                                    </span>
                                </a>
                            @endforeach
                        @else
                            <div class="pq none">No materials uploaded.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@include('resorts.learning._learning_buttons_v2_styles')
@endsection

@section('import-css')
<style>
    .lp-hero { background: #fff; border: 1px solid var(--line); border-radius: 25px; box-shadow: 0 1px 2px rgba(var(--teal-rgb),.05), 0 8px 24px rgba(var(--teal-rgb),.05); overflow: hidden; margin-bottom: 16px; }
    .lp-hero-in { padding: 28px 30px 26px; background: linear-gradient(180deg, var(--teal-soft), #fff); }
    .lp-cat { font-size: 11px; font-weight: 600; letter-spacing: .7px; text-transform: uppercase; color: var(--teal); }
    .lp-hero h1 { font-size: 25px; font-weight: 500; letter-spacing: -.02em; margin-top: 7px; color: var(--ink); line-height: 1.2; }
    .lp-metastrip { display: flex; flex-wrap: wrap; gap: 0; margin-top: 22px; border: 1px solid var(--line); border-radius: 14px; background: rgba(255,255,255,.6); overflow: hidden; }
    .lp-metastrip .mi { flex: 1; min-width: 150px; padding: 13px 16px; border-right: 1px solid var(--line); }
    .lp-metastrip .mi:last-child { border-right: none; }
    .mi .ml { font-size: 9.5px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: var(--faint); margin-bottom: 4px; }
    .mi .mv { font-size: 13px; color: var(--ink); font-weight: 400; display: flex; align-items: center; gap: 7px; }
    .lp-hero .mode { display: inline-flex; align-items: center; gap: 6px; }
    .lp-hero .mode .d { width: 7px; height: 7px; border-radius: 50%; background: var(--teal); }
    .lp-hero .mode .d.hybrid { background: var(--teal-bright, #2EACB3); }
    .lp-hero .mode .d.online { background: var(--positive); }
    .trainer-av { width: 20px; height: 20px; border-radius: 50%; background: var(--line); object-fit: cover; flex: none; }

    .lp-body { display: grid; grid-template-columns: 1fr 300px; gap: 16px; align-items: start; }
    .lp-card { background: #fff; border: 1px solid var(--line); border-radius: 25px; box-shadow: 0 1px 2px rgba(var(--teal-rgb),.05), 0 8px 24px rgba(var(--teal-rgb),.05); padding: 24px 26px; }
    .lp-stack { display: flex; flex-direction: column; gap: 16px; }
    .lp-rail .lp-card + .lp-card { margin-top: 16px; }
    .sec-t { font-size: 11px; font-weight: 600; letter-spacing: .8px; text-transform: uppercase; color: var(--muted); margin-bottom: 14px; }
    .prose { font-size: 14px; color: var(--ink); line-height: 1.65; }

    .obj { list-style: none; display: flex; flex-direction: column; gap: 12px; }
    .obj li { display: flex; gap: 12px; font-size: 14px; color: var(--ink); line-height: 1.5; }
    .obj li .n { flex: none; width: 22px; height: 22px; border-radius: 7px; background: var(--teal-3); color: var(--teal); font-size: 11px; font-weight: 600; display: grid; place-items: center; margin-top: 1px; }

    .pq { font-size: 14px; color: var(--muted); }
    .pq.none { color: var(--faint); font-style: italic; }

    .aud-list { display: flex; flex-direction: column; gap: 11px; }
    .aud-list .a { display: flex; align-items: center; gap: 10px; font-size: 13.5px; color: var(--ink); font-weight: 400; }
    .aud-list .a .b { width: 5px; height: 5px; border-radius: 50%; background: var(--teal); opacity: .7; flex: none; }

    .mat { display: flex; align-items: center; gap: 12px; padding: 12px; border: 1px solid var(--line); border-radius: 12px; text-decoration: none; transition: .15s; }
    .mat + .mat { margin-top: 10px; }
    .mat:hover { border-color: var(--teal); background: var(--teal-soft); }
    .mat .fic { flex: none; width: 36px; height: 36px; border-radius: 9px; background: var(--teal-3); color: var(--teal); display: grid; place-items: center; }
    .mat .fn { font-size: 13px; color: var(--ink); font-weight: 400; word-break: break-word; }
    .mat .fm { font-size: 11px; color: var(--faint); margin-top: 1px; }
    .mat .dl { flex: none; color: var(--faint); }

    @media(max-width: 820px) { .lp-body { grid-template-columns: 1fr; } }
</style>
@endsection
