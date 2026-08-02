@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@section('content')
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Talent Acquisition</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="row g-4">
                    <div class="col-12">
                        <div class="card mb-30">
                            <div class="card-title">
                                <div class="row g-6 align-items-center justify-content-between">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-start align-items-center">
                                            <h2>{{ $form[0]->form_name }} <span class="badge bg-primary">{{ $userRankName }}</span></h2>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                {{-- Other ranks' submitted responses (read-only) --}}
                                @if(!empty($otherSectionsData))
                                    @foreach($otherSectionsData as $otherSection)
                                        <div class="card mb-3" style="border: 1px solid #e0e0e0;">
                                            <div class="card-header" style="background: #f0f0f0;">
                                                <h5 class="mb-0">{{ $otherSection['rankName'] }} Assessment
                                                    <span class="badge bg-success">Submitted</span>
                                                    <small class="text-muted ms-2">by {{ $otherSection['interviewer_name'] ?? '' }} - {{ \Carbon\Carbon::parse($otherSection['submitted_at'])->format('d/M/Y h:i A') }}</small>
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <div id="other-section-{{ $loop->index }}"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                                {{-- Current user's section --}}
                                @if(!empty($filteredStructure))
                                <div class="row g-4">
                                    <form id="interviewForm" method="POST" action="{{ route('interview-assessment.saveResponse', $form[0]->id) }}">
                                        @csrf
                                        <input type="hidden" name="interviewer_id" id="interviewer_id" value="{{ $interviewer_id }}"/>
                                        <input type="hidden" name="interviewee_id" id="interviewee_id" value="{{ $interviewee_id }}"/>

                                        <div id="form-render"></div>
                                        <button type="submit" class="btn ta-btn-primary btn-sm mt-3">Submit</button>
                                    </form>
                                </div>
                                @else
                                <div class="alert alert-info">
                                    No assessment section found for your role ({{ $userRankName }}).
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@include('resorts.talentacquisition._ta_buttons_v2_styles')
@endsection

@section('import-scripts')
    <script src="https://formbuilder.online/assets/js/form-render.min.js"></script>
    <script>
        $(document).ready(function () {
            @if(!empty($filteredStructure))
            // Render the current user's section
            var filteredData = @json($filteredStructure);
            $('#form-render').formRender({
                formData: filteredData
            });

            // ── Per-field role gating: lock + label fields not assigned
            //    to the viewer's role. The controller marks each field with
            //    `_readonly` + `_assigned_to` (a "GM, HR" string). We post-
            //    process the rendered DOM: disable the input AND drop a
            //    small caption telling the viewer who fills it.
            setTimeout(function () {
                filteredData.forEach(function (field) {
                    if (!field || !field._readonly || !field.name) return;
                    var $inputs = $('#form-render').find('[name="' + field.name + '"], [name="' + field.name + '[]"]');
                    if (!$inputs.length) return;
                    $inputs.prop('disabled', true).addClass('is-locked');
                    // Add a one-time caption above the field (or its wrapper).
                    var $wrapper = $inputs.first().closest('.rendered-form > *, .form-group');
                    if ($wrapper.length && !$wrapper.find('.role-lock-note').length) {
                        $wrapper.prepend(
                            '<div class="role-lock-note small text-warning mb-1">' +
                            '<i class="fa-regular fa-lock me-1"></i> To be filled by: ' +
                            field._assigned_to +
                            '</div>'
                        );
                    }
                });
            }, 150);

            // Pre-fill existing responses if already submitted
            @if($existingResponseData)
            var existingResponses = @json($existingResponseData);
            $.each(existingResponses, function (key, value) {
                var inputElement = $('[name="' + key + '"]');
                if (inputElement.is(':radio')) {
                    inputElement.filter('[value="' + value + '"]').prop('checked', true);
                } else if (inputElement.is(':checkbox')) {
                    if (Array.isArray(value)) {
                        value.forEach(function(v) {
                            inputElement.filter('[value="' + v + '"]').prop('checked', true);
                        });
                    } else {
                        inputElement.filter('[value="' + value + '"]').prop('checked', true);
                    }
                } else {
                    inputElement.val(value);
                }
            });
            @endif
            @endif

            // Render other ranks' responses as read-only
            @if(!empty($otherSectionsData))
            @foreach($otherSectionsData as $index => $otherSection)
            (function() {
                var sectionFields = @json($otherSection['fields']);
                var sectionResponses = @json($otherSection['responses']);

                $('#other-section-{{ $index }}').formRender({
                    formData: sectionFields
                });

                // Pre-fill responses
                setTimeout(function() {
                    if (sectionResponses) {
                        $.each(sectionResponses, function (key, value) {
                            var container = $('#other-section-{{ $index }}');
                            var inputElement = container.find('[name="' + key + '"]');
                            if (inputElement.is(':radio')) {
                                inputElement.filter('[value="' + value + '"]').prop('checked', true);
                            } else if (inputElement.is(':checkbox')) {
                                if (Array.isArray(value)) {
                                    value.forEach(function(v) {
                                        inputElement.filter('[value="' + v + '"]').prop('checked', true);
                                    });
                                } else {
                                    inputElement.filter('[value="' + value + '"]').prop('checked', true);
                                }
                            } else {
                                inputElement.val(value);
                            }
                        });
                    }
                    // Disable all fields in other sections
                    $('#other-section-{{ $index }} :input').prop('disabled', true);
                }, 200);
            })();
            @endforeach
            @endif

            // AJAX form submission with toastr
            $('#interviewForm').on('submit', function (e) {
                e.preventDefault();
                var form = $(this);
                var url = form.attr('action');
                var formData = form.serialize();

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function (response) {
                        if (response.success) {
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message || 'Something went wrong.');
                        }
                    },
                    error: function (xhr) {
                        var errorMsg = 'Failed to save the response.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        toastr.error(errorMsg);
                    }
                });
            });
        });
    </script>
@endsection
