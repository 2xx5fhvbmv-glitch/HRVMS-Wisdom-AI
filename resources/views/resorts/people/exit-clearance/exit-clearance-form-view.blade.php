@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
 <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>People</span>
                            <h1>{{$page_title}}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card card-exitInterviewResPeopleEmp">
                <div class="card-header">
                    <div class="row g-md-4 g-3">
                        <div class="col-sm">
                            <div class="heading-title mb-3">
                                <h6 class="fw-900 mb-1">Employee Name</h6>
                            </div>
                            <div class="d-flex">
                                <div class="img-circle userImg-block me-lg-4 me-md-3 me-2"><img
                                        src="{{Common::getResortUserPicture($employeeResignation->employee->Admin_Parent_id ?? null) }}" alt="user">
                                </div>
                                <div>
                                    <h4 class="fw-600 mb-1">{{$employeeResignation->employee->resortAdmin->full_name}}<span class="badge badge-themeNew">{{$employeeResignation->employee->Emp_id}}</span>
                                    </h4>
                                    <p>{{$employeeResignation->employee->department->name}} - {{$employeeResignation->employee->position->position_title}}</p>
                                </div>
                            </div>
                        </div>
                        @if($is_submitted == false)
                            <div class="col-sm">
                                <div class="heading-title mb-3">
                                    <h6 class="fw-700 mb-1">Submitted By</h6>
                                </div>
                                <div class="d-flex">
                                    <div class="img-circle userImg-block me-lg-4 me-md-3 me-2"><img
                                            src="{{Common::getResortUserPicture($employee->Admin_Parent_id ?? null) }}" alt="user">
                                    </div>
                                    <div>
                                        <h4 class="fw-600 mb-1">{{$employee->resortAdmin->full_name}}<span class="badge badge-themeNew">{{$employee->Emp_id}}</span>
                                        </h4>
                                        <p>{{$employee->department->name}} - {{$employee->position->position_title}}</p>

                                    @if($is_submitted == false)
                                        @if( $response_has == true)
                                                <span class="badge badge-themeSuccess">Response Submitted</span>
                                        @else
                                                <span class="badge badge-themeWarning">Response Not Submitted</span>
                                        @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

               
               <div class="card-body"> 
                    <div class="row g-4">
                         <form id="view-interview-form" method="POST" enctype="multipart/form-data">
                              <input type="hidden" name="exit_clearance_assignment_id" value="{{$exitClearanceFormAssignment->id}}">
                              <div id="form-render"></div>

                              @if($is_submitted == true)
                                <div class="d-flex justify-content-end align-items-center mt-3">
                                        <button type="submit" class="btn btn-themeSkyblue" id="submit-form">Submit</button>
                                </div>

                              @endif
                         </form>
                    </div>
               </div>

            </div>

        </div>
    </div>
                        
                    
@endsection

@section('import-css')
<style>
     .rendered-form img {
        border-radius: 4px;
        margin-top: 5px;
    }
</style>
@endsection


@section('import-scripts')
<script>
$('#submit-form').on('click', function (e) {
    e.preventDefault();

    let form = $('#view-interview-form')[0];
    let formData = new FormData(form); // Capture file + input data

    // Pick the correct endpoint by assignment type. The old code always
    // hit department-response-store, which silently rejected employee
    // assignments (firstOrFail on assigned_to_type='department') — that's
    // why employees couldn't submit their exit interview from the web.
    @php
        $assignmentType = strtolower((string) $exitClearanceFormAssignment->assigned_to_type);
    @endphp
    let submitUrl = @json($assignmentType === 'employee'
        ? route('people.exit-clearance.employee-response-store')
        : route('people.exit-clearance.department-response-store'));

    $.ajax({
        url: submitUrl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function (response) {
            if (response.success) {
                toastr.success(response.message || "Form submitted successfully!");
                // Re-render in read-only mode after a successful submit so the
                // user can't double-submit and the page state matches the new
                // assignment.status='Completed'.
                $('#form-render :input').prop('disabled', true);
                $('#submit-form').prop('disabled', true).hide();
            } else {
                toastr.error(response.message || "An error occurred while submitting the form.");
            }
        },
        error: function (xhr) {
            let msg = 'An error occurred while submitting the form.';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            toastr.error(msg);
        }
    });
});
</script>

<script src="https://formbuilder.online/assets/js/form-render.min.js"></script>
<script>
    $(document).ready(function () {
        @if (session('success'))
            toastr.success("{{ session('success') }}");
        @endif

        @if (session('error'))
            toastr.error("{{ session('error') }}");
        @endif

        const formStructure = @json($formStructure);
        const formResponses = @json($responses);

          $('#form-render').formRender({
               formData: JSON.stringify(formStructure),
          });

          $.each(formResponses, function (key, value) {
               $(`[name="${key}"]`).each(function () {
                    const input = $(this);

                    if (input.is(':radio') || input.is(':checkbox')) {
                    if (Array.isArray(value)) {
                         if (value.includes(input.val())) {
                              input.prop('checked', true);
                         }
                    } else {
                         if (input.val() == value) {
                              input.prop('checked', true);
                         }
                    }
                    } else if (input.attr('type') === 'file' && value) {
                        let previews = '';
                        function buildUrl(filePath) {
                            return '{{ url('/') }}/' + filePath.replace(/^\/+/, '');
                        }
                        
                        if (Array.isArray(value)) {
                            value.forEach(function (fileUrl) {
                                if (fileUrl) { // Check for null or undefined
                                    const fullUrl = buildUrl(fileUrl);
                                    previews += `<div class="mt-2"><a href="${fullUrl}" target="_blank"><img src="${fullUrl}" alt="Uploaded File" style="max-height: 100px;"></a></div>`;
                                }
                            });
                        } else if (value) { // Check for null or undefined
                            const fullUrl = buildUrl(value);
                            previews = `<div class="mt-2"><a href="${fullUrl}" target="_blank"><img src="${fullUrl}" alt="Uploaded File" style="max-height: 100px;"></a></div>`;
                        }

                        input.closest('.form-group, .rendered-form-group').append(previews);
                    } else {
                         input.val(value);
                    }
               });
          });

        // Use a Blade conditional below. The literal JS form
        // `if($is_submitted == false)` would throw ReferenceError at
        // runtime because Blade variables don't exist on the client.
        @if(empty($is_submitted))
            $('#form-render :input').prop('disabled', true);
        @endif
    });
</script>
@endsection
