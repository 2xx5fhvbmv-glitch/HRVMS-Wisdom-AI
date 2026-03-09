@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding page-appHedding">
            <div class="row align-items-center g-3">
                <div class="col">
                    <div class="page-title">
                        <span>Survey</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('Survey.DownloadQuestionAndAns', base64_encode($parent->id)) }}" class="btn btn-theme DownloadQuestionAndAns" data-id="{{ base64_encode($parent->id) }}">
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
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            @foreach($participantEmp as $e)
                                                <div class="rounded-circle overflow-hidden flex-shrink-0 border border-2 border-white shadow-sm" style="width: 26px; height: 26px;" title="{{ $e->EmployeeName }}">
                                                    <img src="{{ $e->profileImg }}" alt="" class="w-100 h-100 object-fit-cover">
                                                </div>
                                            @endforeach
                                            <span class="fw-500 text-body small">{{ $participantEmp->count() }} participant(s)</span>
                                        </div>
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
                                    <select name="status" class="form-select form-select-sm changeStatus" id="changeStatus" style="min-width: 140px;" data-parsley-required="true" data-parsley-errors-container="#statusError">
                                        <option value="">Select Status</option>
                                        <option value="Publish" {{ $parent->Status == "Publish" ? 'selected' : '' }}>Publish</option>
                                        @if($parent->Status != "SaveAsDraft")
                                            <option value="SaveAsDraft" {{ $parent->Status == "SaveAsDraft" ? 'selected' : '' }}>Save As Draft</option>
                                        @endif
                                    </select>
                                    <span id="statusError" class="text-danger small"></span>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-theme btn-sm" type="submit">Update status</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script type="text/javascript">
   
   $(document).ready(function () {
        $("#changeStatus").select2({
            placeholder: "Select Status",
            allowClear: true // Enables clear button
        });
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

      

    });


    
</script>
@endsection
