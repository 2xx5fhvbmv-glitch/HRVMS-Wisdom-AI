@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')   
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>SOS</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card ">
                <div class="row g-lg-4 g-3 sosteamActivity-header mb-md-4 mb-3">
                    <div class="col-lg-3 col-sm-6">
                        <div class="d-flex bg-themeGrayLight">
                            <h6>Total Members</h6><strong>{{ $totalMemebersCount }}</strong>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="d-flex bg-themeGrayLight">
                            <h6>Acknowledged</h6><strong>{{ $onlyAckSosCount }}</strong>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="d-flex bg-themeGrayLight">
                            <h6>Pending</h6><strong>{{ $pendingSosCount }}</strong>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 text-end">
                        <span class="badge badge-dangerNew mb-2">SOS Active : {{$sosDetails->getSos->name}}</span>
                        <p><i class="fa-regular fa-location-dot"></i> {{$sosDetails->location}}</p>
                    </div>
                </div>
                <input type="hidden" id="sos_history_id" value="{{ $sosDetails->id }}">
                <div class="card-title  mb-md-4 mb-3">
                    <div class="row align-items-center g-md-3 g-2">
                        <div class="col-xl-2 col-md-4 col-sm-5 col-auto">
                            <div class="form-group">
                            <select class="form-select dd-native-select" aria-label="Default select example" name="associated_teams" id="teamFilter">
                                <option value="">All Teams</option>
                                @foreach($getAllTeams as $team)
                                    <option value="{{ $team->team->id }}">{{ $team->team->name }}</option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#teamFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">All Teams</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Team">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">All Teams</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @foreach($getAllTeams as $team)
                                            <div class="dd-item" role="option" data-value="{{ $team->team->id }}"><span class="dd-nm">{{ $team->team->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="form-check form-switch form-switchTheme switch-blue">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    id="unAckFilter">
                                <label class="form-check-label" for="unAckFilter">Unacknowledged
                                    Only</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="teamActivitySection">
                    @include('resorts.renderfiles.SosTeamMembersActivityList', ['teamMembers' => $teamMembers])
                </div>
                

                {{-- <div class="sosteamActivity-emp">
                        @foreach ($teamMembers as $teamMember)
                        <div class="d-flex {{ $teamMember->status == 'Unacknowledged' ? 'unsafe' : '' }}">
                            <div class="img-circle">
                            @if ($teamMember->resortAdmin && $teamMember->resortAdmin->id)
                                <img src="{{ Common::getResortUserPicture($teamMember->resortAdmin->id) }}" alt="user">
                            @else
                                <img src="{{ url(config('settings.default_picture')) }}" alt="No user">
                            @endif
                            </div>
                            <div>
                                <h6>{{ $teamMember->resortAdmin->full_name }} <span class="badge badge-themeNew">
                                @php
                                    $employee = $teamMember->resortAdmin->GetEmployee ?? null;
                                @endphp

                                @if($employee)
                                    {{ $employee->Emp_id }} 
                                @endif
                                </span> </h6>
                                <p>{{ $teamMember->memberRole->name}} • {{ $teamMember->team->name ?? 'No Team Assigned' }}</p>
                            </div>
                            <div>
                                <ul>
                                    <li><i class="fa-regular fa-location-dot"></i>{{ $sosDetails->location }}</li>
                                    
                                    @if($teamMember->status == 'Acknowledged')
                                    <li class="text-themeSuccess">
                                        <img src="{{ URL::asset('resorts_assets/images/check-circle-green.svg') }}" alt="Check Circle" style="width: 16px; margin-right: 5px;" />
                                        Acknowledged
                                    </li>
                                    @else
                                    <li class="text-themeDanger">
                                            <i class="fa-regular fa-circle-exclamation"></i> Not Acknowledged
                                        </li>
                                    @endif

                                </ul>
                                <span>{{ $teamMember->updated_at ? \Carbon\Carbon::flexible($teamMember->updated_at)->diffForHumans() : '-' }}</span>
                            </div>
                        </div>
                        @endforeach
                    <div id="paginationLinks">
                        {{ $members->links() }}
                    </div> 
                </div> --}}
            </div>

        </div>
    </div>
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        $('#teamFilter, #unAckFilter').change(function () {
            let sosHistoryId = $('#sos_history_id').val();
            let teamId = $("#teamFilter").val();
            let show_unack = $('#unAckFilter').is(':checked');

            $.ajax({
                url: "{{ route('sos.filterTeamActivityDetails', '') }}/" + sosHistoryId,
                type: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                    teamId: teamId,
                    show_status: show_unack
                },
                success: function(data) {
                    if (data.success) {
                        $("#teamActivitySection").html(data.html);
                    } else {
                        toastr.error("Something went wrong.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    var errs = '';

                    if(errors && errors.errors) {
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });
                    } else {
                        errs = "An unexpected error occurred.";
                    }

                    toastr.error(errs, "Error", { positionClass: 'toast-bottom-right' });
                }
            });
        });
    });
</script>
@endsection