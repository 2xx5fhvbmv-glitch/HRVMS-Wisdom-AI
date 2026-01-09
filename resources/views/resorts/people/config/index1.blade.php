@extends('resorts.layouts.app')
@section('page_tab_title' ,"Payroll Config")

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
                            <span>People</span>
                            <h1>Configuration</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto  ms-auto"><a class="btn btn-theme" href="#">Add New Employee</a></div> -->
                </div>
            </div>
            <div>
                <div class="row g-4">
                    <div class="col-lg-6 ">
                        <div class="card mb-30">
                            <div class="card-title">
                                <div class="row g-2">
                                    <div class="col">
                                        <h3 class="text-nowrap">Resignation Reasons</h3>
                                    </div>
                                    <div class="col-auto"><a href="{{route('people.resignation-reason.index')}}" class="a-link">View Existing</a></div>
                                </div>
                            </div>

                            <form class="mb-1" id="resignationReasonsForm" method="POST" action="{{route('people.resignation-reasons.store')}}">
                                @csrf
                                <div class="resignationReasons-main" id="resignationReason-block-wrapper">
                                    <div class="resignationReasons-block">
                                       <div class="row g-2 mb-md-3 mb-2 repeater-item">
                                            <div class="col-sm-6">
                                                <label class="form-label">REASON NAME</label>
                                                <input type="text" class="form-control" name="reasons[0][reason]" placeholder="e.g. Better Opportunity" required>
                                            </div>
                                            <div class="col-sm-4">
                                                <label class="form-label">STATUS</label>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="reasons[0][status]" value="1" checked>
                                                    <label class="form-check-label">Active</label>
                                                </div>
                                            </div>
                                            <div class="col-sm-2 d-flex align-items-end">
                                                <a href="#" class="btn btn-danger btn-sm removeReason d-none">Remove</a>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <a href="#" class="btn btn-themeSkyblue btn-sm" id="addReasons">Add Reason</a>
                            </form>
                            <div class="card-footer text-end"><a href="#" class="btn btn-themeBlue btn-sm resignation_reason_form_submit">Submit</a>
                            </div>
                        </div>

                        <div class="card mb-30">
                            <div class="card-title">
                                <div class="row g-2">
                                    <div class="col">
                                        <h3 class="text-nowrap">Reminders</h3>
                                    </div>
                                    <div class="col-auto"><a href="{{route('people.reminders.index')}}" class="a-link">View Existing</a></div>
                                </div>
                            </div>

                            <form class="mb-1" id="remindersForm" action="{{ route('people.reminders.store') }}" method="POST">
                                @csrf
                                <div class="reminders-main" id="reminders-block-wrapper">
                                    <div class="row g-2 mb-md-3 mb-2 repeater-item">
                                        <div class="col-sm-6">
                                            <label class="form-label">TASK</label>
                                            <input type="text" class="form-control" name="reminders[0][task]" placeholder="Exit Interview" required>
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label">REMINDER (DAYS BEFORE)</label>
                                            <input type="number" class="form-control" name="reminders[0][days]" placeholder="5" required>
                                        </div>
                                        <div class="col-sm-2 d-flex align-items-end">
                                            <a href="#" class="btn btn-danger btn-sm removeReminder d-none">Remove</a>
                                        </div>
                                    </div>
                                </div>
                                <a href="#" class="btn btn-themeSkyblue btn-sm" id="addReminder">Add Reminder</a>
                            </form>

                            <div class="card-footer text-end">
                                <a href="#" class="btn btn-themeBlue btn-sm" id="reminder-form-submit">Submit</a>
                            </div>
                        </div>

                        <div class="card mb-30 card-resigSettPeopleEmp">
                            <div class="card-title">
                                <div class="row g-2">
                                    <div class="col">
                                        <h3 class="text-nowrap">Resignation Withdrawal Settings</h3>
                                    </div>
                                    <div class="col-auto"><a href="#" class="a-link">View Existing</a></div>
                                </div>
                            </div>
                            <form class="mb-1" id="resignationConfigForm" action="{{route('people.config.resignation-withdrawal-config')}}" method="POST">
                                <div class="row align-items-center g-1 mb-md-3 mb-2">
                                    <div class="col-auto">
                                        <h6>Enable Resignation Withdrawal</h6>
                                    </div>
                                    <div class="col-auto ms-auto">
                                        <div class="form-check form-switch form-switchTheme switch-blue">
                                            <input type="hidden" name="enable_resignation_withdrawal" value="0">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                id="flexSwitchCheckDefault" name="enable_resignation_withdrawal" value="1" @if(@$emp_resignation_withdrawal_config->enable_resignation_withdrawal == 1) checked @endif>
                                            <label class="form-check-label" for="flexSwitchCheckDefault"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row align-items-center g-1 mb-3">
                                    <div class="col-auto">
                                        <h6>Require Withdrawal Reason</h6>
                                    </div>
                                    <div class="col-auto ms-auto">
                                        <div class="form-check form-switch form-switchTheme switch-blue">
                                            <input type="hidden" name="required_resignation_withdrawal_reason" value="0">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                id="flexSwitchCheckDefault1" name="required_resignation_withdrawal_reason" value="1" @if(@$emp_resignation_withdrawal_config->required_resignation_withdrawal_reason == 1) checked @endif>
                                            <label class="form-check-label" for="flexSwitchCheckDefault1"></label>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            
                            <div class="card-footer text-end"><a href="#" class="btn btn-themeBlue btn-sm" id="resignation_withdrawal_config_form">Submit</a>
                            </div>
                        </div>

                        <div class="card mb-30">
                            <div class="card-title">
                                <div class="row g-2">
                                    <div class="col">
                                        <h3 class="text-nowrap">Announcement Category</h3>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{ route('announcement.categories') }}" class="a-link">View Existing</a>
                                    </div>
                                </div>
                            </div>

                            <form class="mb-1" id="announcement-category-form">
                                <div class="resignationReasons-main" id="announcement-category-block">
                                    <div class="announcement-block">
                                        <div class="row g-2 mb-md-3 mb-2">
                                            <div class="col-sm-6">
                                                <label for="category_name" class="form-label">Category Name</label>
                                                <input type="text" class="form-control" name="category_name[]" placeholder="Category Name">
                                            </div>
                                            <div class="col d-flex align-items-end">
                                                <a href="#" class="btn btn-themeSkyblue btn-sm" id="addCategory">Add Category</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer text-end">
                                    <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 ">
                        <div class="card mb-30">
                            <div class="card-title">
                                <div class="row g-2">
                                    <div class="col">
                                        <h3 class="text-nowrap">Notice Period Settings</h3>
                                    </div>
                                    <div class="col-auto"><a href="{{route('people.notice-period.index')}}" class="a-link">View Existing</a></div>
                                </div>
                            </div>
                            <form id="noticePeriodForm" action="{{ route('people.notice-period.store') }}">
                           
                            @csrf
                            <div class="noticePeriodSettings-main" id="noticePeriod-block-wrapper">
                                <div class="noticePeriodSettings-block repeater-item">
                                    <div class="row g-2 mb-md-3 mb-2">
                                        <div class="col-sm-4">
                                            <label class="form-label">TITLE</label>
                                            <select class="form-select select2" name="notice_periods[0][title]" required>
                                                <option value="">Select Title</option>
                                               @foreach($emp_grade as $key => $value)
                                                    <option value="{{$value}}">{{$value}}</option>
                                               @endforeach
                                            </select>
                                        </div>
                                        <div class="col-sm-3">
                                            <label class="form-label">NOTICE PERIOD (DAYS)</label>
                                            <input type="number" class="form-control" name="notice_periods[0][days]" placeholder="Enter Number Of Days" required>
                                        </div>
                                        <div class="col-sm-3">
                                            <label class="form-label d-block">IMMEDIATE RELEASE</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="notice_periods[0][immediate_release]" value="1">
                                                <label class="form-check-label">Enable</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-2 d-flex align-items-end">
                                            <a href="#" class="btn btn-danger btn-sm removeNotice d-none">Remove</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <a href="#" class="btn btn-themeSkyblue btn-sm" id="addNotice">Add Row</a>
                            </div>
                        </form>

                        <div class="card-footer text-end">
                            <a href="#" class="btn btn-themeBlue btn-sm" id="notice-form-submit">Submit</a>
                        </div>


                        </div>
                        <div class="card card-exitProcessCustom  mb-30">
                            <div class="card-title">
                                <h3>Exit Process Customization</h3>
                            </div>
                            <form class="mb-1">
                                <div class="row align-items-center g-1 mb-md-3 mb-2">
                                    <div class="col-auto">
                                        <h6>1. Exit Interview</h6>
                                        <a href="#" class="a-link">Manage Form</a>
                                    </div>
                                    <div class="col-auto ms-auto">
                                        <div class="form-check form-switch form-switchTheme switch-blue">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                id="flexSwitchCheckDefault" checked>
                                            <label class="form-check-label" for="flexSwitchCheckDefault"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row align-items-center g-1 mb-md-3 mb-2">
                                    <div class="col-auto">
                                        <h6>2. Handover</h6>
                                        <a href="#" class="a-link">Manage Form</a>
                                    </div>
                                    <div class="col-auto ms-auto">
                                        <div class="form-check form-switch form-switchTheme switch-blue">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                id="flexSwitchCheckDefault1">
                                            <label class="form-check-label" for="flexSwitchCheckDefault1"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row align-items-center g-1 mb-md-3 mb-2">
                                    <div class="col-auto">
                                        <h6>3. Clearance Form</h6>
                                        <a href="#" class="a-link">Manage Form</a>
                                    </div>
                                    <div class="col-auto ms-auto">
                                        <div class="form-check form-switch form-switchTheme switch-blue">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                id="flexSwitchCheckDefault1">
                                            <label class="form-check-label" for="flexSwitchCheckDefault1"></label>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <div class="card-footer text-end"><a href="#" class="btn btn-themeBlue btn-sm">Submit</a>
                            </div>
                        </div>
                  
                        <div class="card mb-30">
                            <div class="card-title">
                                <h3>Letter Templates</h3>
                            </div>
                            <div class="mb-md-4 mb-3">
                                <div class="row">
                                    
                                    <div class="col-md-12">
                                        <label class="form-label"></label>
                                        <a href="javascript:void(0)"  class="btn btn-themeSkyblue btn-sm EmailFreshModal">Create Letter Template</a>
                                    
                                    </div>
                                    <div class="col-md-12">
                                        <div class="table">
                                            <table class="table ProbationEmailTempleteIndex w-100" id="ProbationEmailTempleteIndex">
                                                <thead>
                                                    <tr>
                                                        <th>Subject</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>

                        <div class="card">
                            <div class="card-title">
                                <div class="row g-2">
                                    <div class="col">
                                        <h3 class="text-nowrap">Exit Clearance Forms</h3>
                                    </div>
                                    <div class="col-auto"><a href="{{route('people.exit-clearance.index')}}" class="a-link">View Existing</a></div>
                                </div>
                            </div>
                            <div class="row g-2 mb-md-4 mb-3">
                                <div class="col-12">
                                    <a href="{{route('people.exit-clearance.create')}}" class="btn btn-themeSkyblue btn-sm">Create Exit Clearance Form Template</a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <form id="editForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="staticBackdropLabel"> Email Template </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Email Template Name -->
                            <div class="col-md-12">
                                <label for="type" class="form-label">Select Type</label>
                                <select class="form-select select2t-modal" aria-label="Default select example" required name="type" id="type">
                                    <option value="">Select Type</option>
                                    <option value="success">Probation Successful Letter</option>
                                    <option value="failed">Probation Unsuccessful Letter</option>
                                    <option value="promotion">Promotion Letter</option>
                                    <option value="offer">Offer Letter</option>
                                    <option value="experiance">Experiance\Employment Letter</option>
                                </select>
                            </div>
                            <div id="div-type"></div>
                        </div>
                        <br>
                        <div class="row">
                            <!-- Subject -->
                            <div class="col-md-12">
                                <input 
                                    type="text" 
                                    class="form-control MailSubject" 
                                    name="MailSubject"  
                                    id="MailSubject" 
                                    placeholder="Enter Email Subject (e.g., Welcome to Our Service)">
                            </div>
                            <div id="div-MailSubject"></div>
                        </div>
                        <br>
                        <div class="row">
                            <!-- Email Content -->
                            <div class="col-md-12">
                                <textarea 
                                    class="form-control MailTemplete" 
                                    name="MailTemplete" 
                                    rows="15"  
                                    id="editor" 
                                    placeholder="Enter the email content here..."></textarea>
                            </div>
                            <div id="div-MailTemplete"></div>
                        </div>
                        <input type="hidden" name="Mode" id="Mode" value="">
                        <input type="hidden" name="TemplateId" id="TemplateId">

                        <div class="placeholders">
                            <strong>Available Placeholders:</strong>
                            <ul>
                                <li><code class="insert-placeholder" data-placeholder="@{{employee_name}}">@{{employee_name}}</code> - Employee Name</li>
                                <li><code class="insert-placeholder" data-placeholder="@{{employee_code}}">@{{employee_code}}</code> - Employee Code</li>
                                <li><code class="insert-placeholder" data-placeholder="@{{date}}">@{{date}}</code> - Date</li>
                                <li><code class="insert-placeholder" data-placeholder="@{{Department_title}}">@{{Department_title}}</code> - The Department Name</li>
                                <li><code class="insert-placeholder" data-placeholder="@{{position_title}}">@{{position_title}}</code> - The Position Name</li>
                                <li><code class="insert-placeholder" data-placeholder="@{{resort_name}}">@{{resort_name}}</code> - The name of the resort</li>
                                <li><code class="insert-placeholder" data-placeholder="@{{probation_end_date}}">@{{probation_end_date}}</code> - Probation End date</li>
                                <li><code class="insert-placeholder" data-placeholder="@{{employment_type}}">@{{employment_type}}</code> - Employment Type</li>
                                <li><code class="insert-placeholder" data-placeholder="@{{new_position}}">@{{new_position}}</code> - New Position</li>
                                <li><code class="insert-placeholder" data-placeholder="@{{current_department}}">@{{current_department}}</code> - Current Department</li>
                                <li><code class="insert-placeholder" data-placeholder="@{{new_department}}">@{{new_department}}</code> - New Department</li>
                                <li><code class="insert-placeholder" data-placeholder="@{{new_level}}">@{{new_level}}</code> - New Level</li>
                                <li><code class="insert-placeholder" data-placeholder="@{{effective_date}}">@{{effective_date}}</code> - Effective Date</li>
                            </ul>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <!-- <a href="#" class="btn btn-themeBlue">Preview</a> -->
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button class="btn btn-themeBlue">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
    <script type="text/javascript">
        $(document).ready(function () {
            $('#addCategory').click(function (e) {
                e.preventDefault();
                const newField = `
                    <div class="announcement-block">
                        <div class="row g-2 mb-md-3 mb-2">
                            <div class="col-sm-6">
                                <input type="text" class="form-control" name="category_name[]" placeholder="Category Name">
                            </div>
                            <div class="col d-flex align-items-end">
                                <a href="#" class="btn btn-danger btn-sm removeCategory">Remove</a>
                            </div>
                        </div>
                    </div>
                `;
                $('#announcement-category-block').append(newField);
            });

            // Remove a category block
            $(document).on('click', '.removeCategory', function (e) {
                e.preventDefault();
                $(this).closest('.announcement-block').remove();
            });

            // Handle form submission
            $('#announcement-category-form').submit(function (e) {
                e.preventDefault();

                $.ajax({
                    url: '{{ route("announcement.category.store") }}', // Adjust to your actual route
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function (response) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        setTimeout(function() {
                            window.location.href = response.redirect_url;
                        }, 2000);
                        $('#announcement-category-form')[0].reset();
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            for (const [field, messages] of Object.entries(errors)) {
                                const index = field.match(/\d+/)[0];
                                // $(`input[name='category_name[]']`).eq(index).addClass('is-invalid');
                                toastr.error(messages[0], "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        }
                    }

                });
            });

            $(".EmailFreshModal").on("click", function() {
                // Set modal title for add
                $(".modal-title").text("Add Email Template");

                $("#edit-modal").modal('show');
                $("#TemplateId").val("");
                $("#Mode").val("Fresh");

                // Clear form fields
                $("#TempleteName").val("");
                $("#MailSubject").val("");
                if (CKEDITOR.instances["editor"]) {
                    CKEDITOR.instances["editor"].setData(""); // Clear CKEditor content
                } else {
                    CKEDITOR.replace("editor"); // Initialize CKEditor
                }
            });

            ProbationEmailTempleteIndex();
                    
            $('#editForm').validate({
                rules: {
                    type: {
                        required: true,
                    },
                    MailSubject: {
                        required: true,
                    },
                    MailTemplete: {
                        required: true,
                    }
                },
                messages: {
                    type: {
                        required: "Please Select Type .",
                    },
                    MailSubject: {
                        required: "Please enter the email subject.",
                    },
                    MailTemplete: {
                        required: "Please provide the email content.",
                    }
                },
                submitHandler: function(form) {
                    const content = CKEDITOR.instances['editor'].getData(); // Get content from CKEditor
                    let type = $("#type").val();
                    let MailSubject = $("#MailSubject").val();
                    let id = $("#TemplateId").val();
                    let Mode = $("#Mode").val();
                    let TemplateId = $("#TemplateId").val();
                    $.ajax({
                        url:  "{{ route('probation.letter-template')}}",
                        type: "post",
                        data: {
                            "id": id,
                            "type":type,
                            "subject": MailSubject,
                            "content": content,
                            "TemplateId":TemplateId,
                            "Mode":Mode,
                            "_token": "{{ csrf_token() }}"
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $("#edit-modal").modal('hide');
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                                $("#template_id_" + id).text(TempleteName);
                                ProbationEmailTempleteIndex();                        
                            }
                        },
                        error: function(xhr, status, error) {
                            let errs = '';
                            if (xhr.status === 422) {
                                let response = xhr.responseJSON;
                                if (response.errors) {
                                    $.each(response.errors, function(key, value) {
                                        errs += value[0] + '<br>';
                                    });
                                }
                            } else {
                                errs = 'An unexpected error occurred. Please try again.';
                            }
                            toastr.error(errs, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        },
                        complete: function() {
                            $("#editForm")[0].reset();
                            if (CKEDITOR.instances['editor']) {
                                CKEDITOR.instances['editor'].setData('');
                            }
                        }
                    });
                },
                errorPlacement: function(error, element) {
                    if( element.attr("name") == "type" ) {
                        error.insertAfter( "#div-type" );
                    } else if( element.attr("name") == "MailSubject" ) {
                        error.insertAfter( "#div-MailSubject" );
                    } else if( element.attr("name") == "MailTemplete" ) {
                        error.insertAfter( "#div-MailTemplete" );
                    } else {
                        error.insertAfter(element);
                    }
                },
                errorElement: 'span'
            });   
            
            $(document).on('click','.edit-row-btn',function(e)
            {
                var id=$(this).data('cat-id');
                $("#edit-modal").modal('show');
                $.ajax({
                    url: "{{ route('probation.GetEmailTamplate') }}", 
                    type: 'POST',
                    data: {"_token":"{{csrf_token()}}","id":id},
                    success: function(response) {
                        if (response.success) 
                        {
            
                            var data= response.data;
                    
                            $("#type").val(data.type).trigger('change');
                            $("#MailSubject").val(data.subject);
                            $("#Mode").val("edit");
                            $("#TemplateId").val(data.id);
                            

                            if (CKEDITOR.instances["editor"]) {
                                CKEDITOR.instances["editor"].destroy(true);
                            }
                            CKEDITOR.replace("editor");

                            CKEDITOR.instances["editor"].on('instanceReady', function () {
                                CKEDITOR.instances["editor"].setData(data.content);
                            });
                        } 
                        else
                        {
                                toastr.error(response.message, "Error",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                        }
                    },
                    error: function(response) 
                    {
                        var errors = response.responseJSON;

                        if (errors.error) { 
                            // If it's a duplicate entry error
                            toastr.error(errors.error, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        } else {
                            // If it's a validation error
                            var errs = '';
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });

                            toastr.error(errs, "Validation Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }

                });
            });
        });
        
        function ProbationEmailTempleteIndex()
        {
            if ($.fn.DataTable.isDataTable('.ProbationEmailTempleteIndex'))
            {
                $('.ProbationEmailTempleteIndex').DataTable().destroy();
            }
            var MailTable = $('.ProbationEmailTempleteIndex').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 120,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('probation.EmailTamplateIndex') }}",
                type: 'GET',
            },
            columns: [
                { data: 'subject', name: 'subject',},
                { data: 'action', name: 'action' },
            ]
            });
        }



        $(document).ready(function () {
            // Add Reasons Repeator
           function updateRepeaterIndexes() {
                $('#resignationReason-block-wrapper .repeater-item').each(function (index) {
                    $(this).find('input[name^="reasons"]').each(function () {
                        const name = $(this).attr('name');
                        const field = name.includes('[reason]') ? 'reason' : 'status';
                        $(this).attr('name', `reasons[${index}][${field}]`);
                    });
                });
            }

            $('#addReasons').click(function (e) {
                e.preventDefault();
                const $last = $('#resignationReason-block-wrapper .repeater-item').last();
                const $clone = $last.clone();

                $clone.find('input[type="text"]').val('');
                $clone.find('input[type="checkbox"]').prop('checked', true);
                $clone.find('.removeReason').removeClass('d-none');

                $('#resignationReason-block-wrapper').append($clone);
                updateRepeaterIndexes();
            });

            $(document).on('click', '.removeReason', function (e) {
                e.preventDefault();
                $(this).closest('.repeater-item').remove();
                updateRepeaterIndexes();
            });

            $('.resignation_reason_form_submit').click(function (e) {
                e.preventDefault();

                const $form = $('#resignationReasonsForm');
                const formData = $form.serialize();

                $.ajax({
                    url: $form.attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function (response) {
                        if (response.status === 'success') {

                            $('#resignationReason-block-wrapper').html($form.find('.repeater-item').first());
                            $form[0].reset();
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                        } 
                    },
                    error: function (xhr) {
                        toastr.error(errs, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                    }
                });
            });

            // Reminder Repeator
            function updateReminderIndexes() {
                $('#reminders-block-wrapper .repeater-item').each(function (index) {
                    $(this).find('input[name]').each(function () {
                        const field = $(this).attr('name').includes('task') ? 'task' : 'days';
                        $(this).attr('name', `reminders[${index}][${field}]`);
                    });
                });
            }

            $('#addReminder').on('click', function (e) {
                e.preventDefault();
                const $lastItem = $('#reminders-block-wrapper .repeater-item').last();
                const $clone = $lastItem.clone();

                $clone.find('input').val('');
                $clone.find('.removeReminder').removeClass('d-none');
                $('#reminders-block-wrapper').append($clone);
                updateReminderIndexes();
            });

            $(document).on('click', '.removeReminder', function (e) {
                e.preventDefault();
                $(this).closest('.repeater-item').remove();
                updateReminderIndexes();
            });

            $('#reminder-form-submit').on('click', function (e) {
                e.preventDefault();
                const $form = $('#remindersForm');
                $.ajax({
                    url: $form.attr('action'),
                    method: 'POST',
                    data: $form.serialize(),
                    success: function (response) {
                        toastr.success(response.message, "Success", {
                                   positionClass: 'toast-bottom-right'
                               });
                        $('#reminders-block-wrapper').html($form.find('.repeater-item').first());
                        $form[0].reset();
                    },
                    error: function () {
                        toastr.error(errs, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                    }
                });
            });

            // Notice Period
                bindToggleEvents($('#noticePeriod-block-wrapper'));
                function updateNoticeIndexes() {
                    $('#noticePeriod-block-wrapper .repeater-item').each(function (index) {
                        $(this).find('select, input').each(function () {
                            const input = $(this);
                            const type = input.attr('type');
                            let baseName = input.attr('name');

                            if (!baseName) return;

                            // Extract the field name (e.g. title, days, immediate_release)
                            const fieldMatch = baseName.match(/\[([a-z_]+)\]$/);
                            if (!fieldMatch) return;

                            const field = fieldMatch[1];
                            const newName = `notice_periods[${index}][${field}]`;

                            input.attr('name', newName);
                        });
                    });
                }

                function bindToggleEvents($container) {
                    $container.find('input[name*="[days]"]').on('input', function () {
                        const $row = $(this).closest('.repeater-item');
                        const hasValue = $(this).val().trim() !== '';
                        $row.find('input[name*="[immediate_release]"]').prop('disabled', hasValue);
                    });

                    $container.find('input[name*="[immediate_release]"]').on('change', function () {
                        const $row = $(this).closest('.repeater-item');
                        const isChecked = $(this).is(':checked');
                        $row.find('input[name*="[days]"]').prop('disabled', isChecked);
                    });
                }


                $('#addNotice').on('click', function (e) {
                    e.preventDefault();

                    const $last = $('#noticePeriod-block-wrapper .repeater-item').last();
                    const $clone = $last.clone();

                    $clone.find('input[type="number"]').val('').prop('disabled', false);
                    $clone.find('input[type="checkbox"]').prop('checked', false).prop('disabled', false);
                    
                    $clone.find('select.select2').each(function () {
                        const $select = $(this);

                        $select.val('').removeAttr('data-select2-id').removeAttr('aria-hidden').removeAttr('tabindex');
                        $select.next('.select2-container').remove(); 

                        $select.select2();
                        $select.val('').trigger('change');
                    });

                    $('#noticePeriod-block-wrapper').append($clone);

                    $clone.find('.select2').select2();
                    $clone.find('select.select2').val('').trigger('change');
                    $clone.find('.removeNotice').removeClass('d-none');
                    updateNoticeIndexes();
                    bindToggleEvents($clone);
                });

          

                $(document).on('click', '.removeNotice', function (e) {
                    e.preventDefault();
                    $(this).closest('.repeater-item').remove();
                    updateNoticeIndexes();
                });

                $('#notice-form-submit').on('click', function (e) {
                    e.preventDefault();
                    const $form = $('#noticePeriodForm');
                    $.ajax({
                        url: $form.attr('action'),
                        method: "POST",
                        data: $form.serialize(),
                        success: function (response) {
                            $('#noticePeriod-block-wrapper').html($form.find('.repeater-item').first());
                            $form[0].reset();
                            toastr.success(response.message, "Success", {
                                        positionClass: 'toast-bottom-right'
                                    });
                        },
                        error: function () {
                        toastr.error(errs, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                        }
                    });
                });

                $('#resignation_withdrawal_config_form').on('click', function (e) {
                    e.preventDefault();
                    const $form = $('#resignationConfigForm');
                    $.ajax({
                        url: $form.attr('action'),
                        method: "POST",
                        data: $form.serialize(),
                        success: function (response) {
                            toastr.success(response.message, "Success", {
                                        positionClass: 'toast-bottom-right'
                                    });
                        },
                        error: function () {
                        toastr.error(errs, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                        }
                    });
                });

        });
    </script>
@endsection