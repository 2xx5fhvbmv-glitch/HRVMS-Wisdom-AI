@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<style>
    #survey-view-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #survey-view-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding page-appHedding" id="survey-view-hero">
            <div class="row align-items-center g-3">
                <div class="col">
                    <div class="page-title">
                        <span>Survey</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto d-flex gap-2">
                    {{-- Was only reachable from the Complete-survey list's
                         eye-icon and the dashboards' "View Details" link —
                         every other list (main Surveylist, etc.) lands here
                         on Survey.view instead, which has no response data
                         of its own, so there was no way to see who
                         answered what from this page at all. --}}
                    <a href="{{ route('Survey.GetSurveyResults', base64_encode($parent->id)) }}" class="btn btn-themeSkyblue" target="_blank">
                        <i class="fa-regular fa-chart-bar me-1"></i> View Responses
                    </a>
                    <a href="{{ route('Survey.DownloadQuestionAndAns', base64_encode($parent->id)) }}" class="btn eb-btn-secondary DownloadQuestionAndAns" data-id="{{ base64_encode($parent->id) }}">
                        <i class="fa-regular fa-download me-1"></i> Download
                    </a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-md-4">
                {{-- Survey title & status --}}
                <div class="mb-3">
                    <h3 class="h4 mb-2">{{ $parent->Surevey_title }}</h3>
                    <span class="badge {{ $parent->Status == 'Complete' ? 'badge-success' : ($parent->Status == 'OnGoing' ? 'badge-info' : ($parent->Status == 'Publish' ? 'badge-primary' : 'badge-secondary')) }}">{{ $parent->Status }}</span>
                </div>

                {{-- Survey meta strip (redesigned) --}}
                <div class="survey-meta-strip rounded-3 p-3 p-md-4 mb-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f5 100%); border: 1px solid rgba(0,0,0,0.06);">
                    <div class="row g-3 g-md-0">
                        <div class="col-md-3 col-6">
                            <div class="d-flex align-items-center gap-3">
                                <div class="survey-meta-icon rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                                    <i class="fa-regular fa-user text-secondary"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-uppercase text-muted" style="font-size: 0.7rem; letter-spacing: 0.02em;">Created by</div>
                                    <div class="fw-500 text-body text-truncate">{{ $parent->EmployeeName }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="d-flex align-items-center gap-3">
                                <div class="survey-meta-icon rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                                    <i class="fa-regular fa-calendar text-secondary"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-uppercase text-muted" style="font-size: 0.7rem; letter-spacing: 0.02em;">Date range</div>
                                    <div class="fw-500 text-body small">{{ date('d M Y', strtotime($parent->Start_date)) }} – {{ date('d M Y', strtotime($parent->End_date)) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="d-flex align-items-center gap-3">
                                <div class="survey-meta-icon rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                                    <i class="fa-regular fa-lock text-secondary"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-uppercase text-muted" style="font-size: 0.7rem; letter-spacing: 0.02em;">Privacy</div>
                                    <div class="fw-500 text-body">{{ !empty($parent->survey_privacy_type) ? ucfirst($parent->survey_privacy_type) : '—' }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="d-flex align-items-center gap-3">
                                <div class="survey-meta-icon rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                                    <i class="fa-regular fa-users text-secondary"></i>
                                </div>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="text-uppercase text-muted" style="font-size: 0.7rem; letter-spacing: 0.02em;">Participants</div>
                                    @if($participantEmp->isNotEmpty())
                                        <a href="javascript:void(0)" class="d-flex align-items-center gap-2 flex-wrap text-decoration-none showTotalapplicant" data-id="{{ base64_encode($parent->id) }}">
                                            @foreach($participantEmp as $e)
                                                <div class="rounded-circle overflow-hidden flex-shrink-0 border border-2 border-white shadow-sm" style="width: 26px; height: 26px;" title="{{ $e->EmployeeName }}">
                                                    <img src="{{ $e->profileImg }}" alt="" class="w-100 h-100 object-fit-cover">
                                                </div>
                                            @endforeach
                                            <span class="fw-500 text-body small">{{ $participantEmp->count() }} participant(s)</span>
                                        </a>
                                    @else
                                        <div class="fw-500 text-body small text-muted">No participants assigned</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Questions --}}
                @if($Question->isNotEmpty())
                    <h5 class="mb-3">Questions ({{ $Question->count() }})</h5>
                    <div class="row g-3 mb-4">
                        @foreach($Question as $q)
                            <div class="col-12">
                                <div class="card border bg-light rounded-3">
                                    <div class="card-body py-3 px-4">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                            <span class="badge bg-themeSkyblue text-dark">Q{{ $loop->iteration }}</span>
                                            <span class="badge bg-white text-muted border">{{ ucfirst($q->Question_Type ?? 'Text') }}</span>
                                            @if(!empty($q->Question_Complusory) && strtolower($q->Question_Complusory) === 'yes')
                                                <span class="badge bg-warning text-dark">Required</span>
                                            @endif
                                        </div>
                                        <p class="mb-2 fw-500">{{ ucfirst($q->Question_Text) }}</p>
                                        @if(!empty($q->Total_Option_Json))
                                            @php
                                                $options = is_string($q->Total_Option_Json) ? json_decode($q->Total_Option_Json, true) : $q->Total_Option_Json;
                                            @endphp
                                            @if(!empty($options) && (is_array($options) || is_object($options)))
                                                <div class="mt-2 d-flex flex-wrap gap-2">
                                                    @foreach((array)$options as $opt)
                                                        <span class="badge bg-white border text-dark px-3 py-2">{{ is_string($opt) ? $opt : (is_array($opt) ? implode(' ', $opt) : $opt) }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-4">No questions added yet.</p>
                @endif

                {{-- Change status (draft / publish only) --}}
                @if($parent->Status != "OnGoing" && $parent->Status != "Complete")
                    <div class="card bg-themeGrayLight border-0 rounded-3">
                        <div class="card-body py-3 px-4">
                            <form id="changeStatusForm" class="row align-items-end g-3">
                                @csrf
                                <input type="hidden" name="id" value="{{ base64_encode($parent->id) }}">
                                <div class="col-auto">
                                    <label for="changeStatus" class="form-label small text-muted mb-0">Status</label>
                                    <select name="status" class="form-select form-select-sm dd-native-select changeStatus" id="changeStatus" style="min-width: 140px;" data-parsley-required="true" data-parsley-errors-container="#statusError">
                                        <option value="">Select Status</option>
                                        <option value="Publish" {{ $parent->Status == "Publish" ? 'selected' : '' }}>Publish</option>
                                        @if($parent->Status != "SaveAsDraft")
                                            <option value="SaveAsDraft" {{ $parent->Status == "SaveAsDraft" ? 'selected' : '' }}>Save As Draft</option>
                                        @endif
                                    </select>
                                    @php $statusSelected = $parent->Status == 'Publish' ? 'Publish' : ($parent->Status == 'SaveAsDraft' ? 'Save As Draft' : 'Select Status'); @endphp
                                    <div class="dd" data-target="#changeStatus">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="dd-lbl">{{ $statusSelected }}</span>
                                            <svg class="dd-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Status">
                                            <div class="dd-scroll">
                                                <div class="dd-item{{ $parent->Status != 'Publish' && $parent->Status != 'SaveAsDraft' ? ' active' : '' }}" role="option" data-value=""><span class="dd-nm">Select Status</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                <div class="dd-item{{ $parent->Status == 'Publish' ? ' active' : '' }}" role="option" data-value="Publish"><span class="dd-nm">Publish</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @if($parent->Status != "SaveAsDraft")
                                                <div class="dd-item" role="option" data-value="SaveAsDraft"><span class="dd-nm">Save As Draft</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <span id="statusError" class="text-danger small"></span>
                                </div>
                                <div class="col-auto">
                                    <button class="btn eb-btn-primary btn-sm" type="submit">Update status</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@include('resorts._emotional_buttons_v2_styles')

<div class="modal fade show" id="Surveyparticipant" tabindex="-1" aria-labelledby="exampleModalLabel" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Survey Participant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="employee-name-content">
                    <div class="row g-3 AppendinRow">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')
<script type="text/javascript">
   
   $(document).ready(function () {
        $('#changeStatusForm').parsley();

        // Handle form submission
        $("#changeStatusForm").on("submit", function (e) {
                    e.preventDefault(); // Prevent default form submission

                    var form = $(this);
                    if (form.parsley().validate()) {
                        var formData = form.serialize();

                        $.ajax({
                            url: "{{ route('Survey.changeStatus') }}", // Update with actual route
                            type: "POST",
                            data: formData,
                            success: function (response) {
                                if (response.success) {
                                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                                    setTimeout(function () {
                                        window.location.reload();
                                    }, 5000);

                                } else {
                                    toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                                }
                            },
                            error: function (xhr) {
                                toastr.error("An error occurred.", "Error", { positionClass: 'toast-bottom-right' });
                            }
                        });
                    }
        });


        $(document).on('click', '.showTotalapplicant', function() {
            var id = $(this).data('id');

            $("#Surveyparticipant").modal('show');
            $('.AppendinRow').html('No Record Found.     ');
            let url = "{{ route('Survey.TotalApplicant', ':id') }}";
            url = url.replace(':id', id);
            $.ajax({
                url: url,
                method: "GET",
                success: function(response) {
                    $('.AppendinRow').html(response);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching data:', error);
                }
            });
        });

    });



</script>
@include('resorts._dropdown_script')
@endsection
