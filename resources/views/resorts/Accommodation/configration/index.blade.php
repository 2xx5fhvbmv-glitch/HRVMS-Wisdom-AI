{{--
    Accommodation > Configuration — buildings, floors & rooms in one flow.
    Presentation only. Data: the variables ConfigrationController@index already passes (types, inventory items, buildings,
    escalation day) plus the existing list endpoints for the building/floor/room tree and room setups (loaded once by
    public/resorts_assets/js/accommodation-config.js). Styles: public/resorts_assets/css/accommodation-config.css
--}}
@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
@php
    $acfg = [
        'urls' => [
            // lists (existing DataTables endpoints, asked for every row at once)
            'floorRooms' => route('resort.accommodation.config.FloorRoomIndex'),
            'setups'     => route('resort.accommodation.AvailableAccommodationIndex'),
            'buildings'  => route('resort.accommodation.config.BuildingIndex'),
            'types'      => route('resort.accommodation.config.AccommodationIndex'),
            'cats'       => route('resort.accommodation.config.InvenotryCatIndex'),
            // writes (existing endpoints; __ID__ is replaced client-side)
            'buildingStore'   => route('resort.accommodation.StoreBuilding'),
            'buildingUpdate'  => route('resort.accommodation.BuildingUpdate'),
            'buildingDestroy' => route('resort.accommodation.BuildingDestory', ['id' => '__ID__']),
            'roomStore'       => route('resort.accommodation.StoreFloorandroom'),
            'roomDestroy'     => route('resort.accommodation.FloorAndRoomDestroy', ['id' => '__ID__']),
            'setupStore'      => route('resort.accommodation.AvailableAccommodationStore'),
            'setupUpdate'     => route('resort.accommodation.AvailableAccommodationUpdate', ['id' => '__ID__']),
            'setupDestroy'    => route('resort.accommodation.AvailableAccommodationDestroy', ['id' => '__ID__']),
            'beds'            => route('resort.accommodation.getBeds', ['id' => '__ID__']),
            'escalation'      => route('resort.accommodation.config.EscalationDay'),
            'typeStore'       => route('resort.accommodation.AccommodationTypeStore'),
            'typeDestroy'     => route('resort.accommodation.Accommodationdestory', ['id' => '__ID__']),
            'catStore'        => route('resort.accommodation.StoreInvCategory'),
            'catDestroy'      => route('resort.accommodation.Catdestory', ['id' => '__ID__']),
            'uploadRooms'     => route('resort.accommodation.AccommodationAvailableFileStore'),
            'uploadQuick'     => route('resort.accommodation.QuickAssignmentFileStore'),
            // links
            'assign'    => route('resort.accommodation.AssignAccommation'),
            'inventory' => route('resort.accommodation.inventory'),
        ],
        'types'     => $AccommodationType->map(fn ($t) => ['id' => $t->id, 'n' => $t->AccommodationName])->values(),
        'buildings' => $BuildingData->map(fn ($b) => ['id' => $b->id, 'n' => $b->BuildingName])->values(),
        // only items that still have stock; "per bed" = the inventory item's own per-person flag
        'items'     => $InventoryModule->map(fn ($i) => [
            'id'    => $i->id,
            'n'     => $i->ItemName,
            'code'  => $i->ItemCode,
            'cat'   => $i->Inv_Cat_id,
            'avail' => max(0, (int) $i->Quantity - (int) ($i->Occupied ?? 0)),
            'per'   => ($i->assignment_type ?? 'per_person') !== 'per_room',
            'dq'    => (int) ($i->default_qty_per_unit ?? 0),
        ])->values(),
        'escalation' => optional($EscalationDay)->EscalationDay,
        'cleaning'   => config('settings.CleaningSchedule'),
    ];
@endphp
<style>
    #accommodation-config-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #accommodation-config-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="accommodation-config-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Accommodation</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

<div id="acfg">
  <div class="tabs" id="tabs" role="tablist" aria-label="Accommodation configuration">
    <span class="ind" id="ind"></span>
    <button type="button" role="tab" class="on" data-t="rooms">Buildings &amp; rooms</button>
    <button type="button" role="tab" data-t="types">Accommodation types</button>
    <button type="button" role="tab" data-t="inv">Inventory categories</button>
    <button type="button" role="tab" data-t="alerts">Alerts &amp; escalation</button>
    <button type="button" role="tab" data-t="bulk">Bulk upload</button>
  </div>

  <!-- Buildings & rooms -->
  <div class="pane" id="p-rooms">
    <div class="acc">
      <section class="ac-card rail" aria-labelledby="blT">
        <div class="ch"><span class="t" id="blT">Buildings</span><span class="cnt" id="bc"></span>
          <button class="ac-btn btn-main sm addtop" type="button" id="addB"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Add building</button></div>
        <label class="bsrch" id="bsW" hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg><input id="bs" placeholder="Search buildings" aria-label="Search buildings" autocomplete="off"></label>
        <div class="bfl" id="bfl" hidden></div>
        <div class="bl" id="bl"><div class="skel"></div></div>
        <div class="rlinks">
          <a href="{{ route('resort.accommodation.config.BuildingIndex') }}">Building list</a>
          <a href="{{ route('resort.accommodation.config.FloorRoomIndex') }}">Floor and room list</a>
          <a href="{{ route('resort.accommodation.AvailableAccommodationIndex') }}">Room setup list</a>
        </div>
      </section>
      <section class="ac-card main" id="main" aria-live="polite"><div class="skel"></div></section>
    </div>
  </div>

  <!-- Accommodation types -->
  <div class="pane" id="p-types" hidden>
    <div class="cols">
      <section class="ac-card">
        <div class="ch"><span class="t">Accommodation types</span><span class="cnt" id="tc"></span><a class="va" href="{{ route('resort.accommodation.config.AccommodationIndex') }}">View all</a></div>
        <div class="sub">The names you use for room sharing, like Single share or Four share. You pick one when you set up rooms, and set the number of beds there.</div>
        <div class="lst" id="tl"></div>
        <div class="addrow"><input class="inp" id="tN" placeholder="e.g. Private, Triple share" maxlength="50" autocomplete="off" aria-label="New accommodation type"><button class="ac-btn btn-main" type="button" id="tAdd">Add type</button></div>
        <div class="err" id="tE"></div>
      </section>
    </div>
  </div>

  <!-- Inventory categories -->
  <div class="pane" id="p-inv" hidden>
    <div class="cols">
      <section class="ac-card">
        <div class="ch"><span class="t">Inventory categories</span><span class="cnt" id="cc"></span><a class="va" href="{{ route('resort.accommodation.inventory') }}">Add items<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></a></div>
        <div class="sub">Groups for the things you put in rooms, like furniture or bedding.</div>
        <div class="chipl" id="cl"></div>
        <div class="addrow"><input class="inp" id="cN" placeholder="e.g. Electronics" maxlength="50" autocomplete="off" aria-label="New inventory category"><button class="ac-btn btn-main" type="button" id="cAdd">Add category</button></div>
        <div class="err" id="cE"></div>
        <div class="rlinks"><a href="{{ route('resort.accommodation.config.InvenotryCatIndex') }}">Category list</a></div>
      </section>
    </div>
  </div>

  <!-- Alerts & escalation -->
  <div class="pane" id="p-alerts" hidden>
    <div class="cols">
      <section class="ac-card">
        <div class="ch"><span class="t">Occupancy alert</span></div>
        <div class="sub">The occupancy alert level new rooms start with. You can change it for each room.</div>
        <div class="rng"><input type="range" min="50" max="100" step="5" value="90" id="aR" aria-label="Default occupancy alert"><b id="aV">90%</b></div>
        <div class="ac-row" style="margin-top:16px"><button class="ac-btn btn-main" type="button" id="aSave">Save</button></div>
      </section>
      <section class="ac-card">
        <div class="ch"><span class="t">Escalation day</span></div>
        <div class="sub">If a maintenance request stays open longer than this, the person it’s assigned to and HR are notified.</div>
        <div class="ac-row"><div class="step" id="eS"><button type="button" data-d="-1" aria-label="Fewer days">−</button><input type="number" value="1" min="1" max="30" aria-label="Days"><button type="button" data-d="1" aria-label="More days">+</button></div><span style="font-size:13px;color:var(--muted)">day(s)</span></div>
        <div class="err" id="eE"></div>
        <div class="ac-row" style="margin-top:16px"><button class="ac-btn btn-main" type="button" id="eSave">Save</button></div>
      </section>
    </div>
  </div>

  <!-- Bulk upload -->
  <div class="pane" id="p-bulk" hidden>
    <div class="cols">
      <section class="ac-card">
        <div class="ch"><span class="t">Rooms from Excel</span></div>
        <div class="sub">Set up many rooms at once: building, floor, room, type, beds, who stays and inventory.</div>
        <div class="up" data-up="rooms"><span class="ui"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M5 20h14"/></svg></span><span class="ut" data-ut>Drop your filled template here<small>.xlsx · one row per room</small></span><input type="file" accept=".csv,.xls,.xlsx" aria-label="Rooms file"><button class="ac-btn btn-main" type="button" data-pick>Upload file</button></div>
        <div class="ac-row" style="margin-top:12px"><a class="ac-btn btn-gh" href="{{ route('resort.accommodation.config.AccommodationTemplete') }}">Download template</a></div>
        <div data-ures></div>
      </section>
      <section class="ac-card">
        <div class="ch"><span class="t">Quick assignment</span></div>
        <div class="sub">Assign staff to beds in bulk from a spreadsheet.</div>
        <div class="up" data-up="quick"><span class="ui"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M5 20h14"/></svg></span><span class="ut" data-ut>Drop your filled template here<small>.xlsx · one row per employee</small></span><input type="file" accept=".csv,.xls,.xlsx" aria-label="Quick assignment file"><button class="ac-btn btn-main" type="button" data-pick>Upload file</button></div>
        <div class="ac-row" style="margin-top:12px"><a class="ac-btn btn-gh" href="{{ route('resort.accommodation.config.QuickAssignmentTemplete') }}">Download template</a></div>
        <div data-ures></div>
      </section>
    </div>
  </div>

  <!-- selection bar -->
  <div class="sbar" id="sbar" role="region" aria-label="Selected rooms">
    <span class="sc" id="sc"></span>
    <button class="ac-btn btn-main" type="button" id="sSet">Set up rooms</button>
    <button class="ac-btn btn-gh" type="button" id="sDel">Remove</button>
    <button class="ib" type="button" id="sClr" aria-label="Clear selection" data-tip="Clear selection"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M7 7l10 10M17 7L7 17"/></svg></button>
  </div>

  <!-- room setup drawer -->
  <div class="scrim" id="scrim"></div>
  <aside class="dr" id="dr" role="dialog" aria-modal="true" aria-labelledby="drT" aria-hidden="true">
    <div class="drh"><div><div class="dt" id="drT"></div><div class="ds" id="drS"></div></div>
      <button class="ib" type="button" id="drX" aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M7 7l10 10M17 7L7 17"/></svg></button></div>
    <div class="drb" id="drb"></div>
    <div class="drf"><span class="why" id="why"></span><button class="ac-btn btn-gh" type="button" id="drC">Cancel</button><button class="ac-btn btn-main btn-lg" type="button" id="drOk"></button></div>
  </aside>

  <!-- add building / floors modal -->
  <div class="md" id="md" role="dialog" aria-modal="true" aria-labelledby="mT" aria-hidden="true">
    <div class="mdb">
      <div class="mt" id="mT"></div><div class="ms" id="mS"></div>
      <div class="fs" id="mNameF"><label for="mName">Building name</label><input class="inp" id="mName" placeholder="e.g. Building C, GM Villa, Sup Block" maxlength="50" autocomplete="off"><div class="err" id="mE"></div></div>
      <div class="mdiv" id="mDiv"></div>
      <div class="grid2" style="align-items:end">
        <div class="fs"><span class="lb">How many floors</span><div class="step" id="mF"><button type="button" data-d="-1" aria-label="Fewer floors">−</button><input type="number" value="3" min="1" max="40" aria-label="Floors"><button type="button" data-d="1" aria-label="More floors">+</button></div></div>
        <label class="tg" id="mGF" style="padding-bottom:7px"><input type="checkbox" id="mG" checked><span class="sw"></span>Starts with a ground floor</label>
      </div>
      <div class="fs" id="mNS" style="margin-top:16px"><span class="lb">Room numbers</span><div class="seg" id="mNSs" role="radiogroup"><button type="button" role="radio" data-ns="F" class="on" aria-checked="true">By floor · 101, 102…</button><button type="button" role="radio" data-ns="C" aria-checked="false">Continuous · 1, 2, 3…</button></div></div>
      <div class="ftab" id="mP" aria-live="polite"></div>
      <div class="mres" id="mR" hidden></div>
      <div class="mf"><span class="msum" id="mSum"></span><button class="ac-btn btn-gh" type="button" id="mC">Cancel</button><button class="ac-btn btn-main btn-lg" type="button" id="mOk"></button></div>
    </div>
  </div>
</div>

    </div>
</div>
@endsection

@section('import-css')
<link href="{{ URL::asset('resorts_assets/css/accommodation-config.css') }}?v={{ @filemtime(public_path('resorts_assets/css/accommodation-config.css')) }}" rel="stylesheet">
@endsection

@section('import-scripts')
<script>window.ACFG = @json($acfg);</script>
<script src="{{ URL::asset('resorts_assets/js/accommodation-config.js') }}?v={{ @filemtime(public_path('resorts_assets/js/accommodation-config.js')) }}"></script>
@endsection
