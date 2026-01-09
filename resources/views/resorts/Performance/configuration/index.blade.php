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
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Performance</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <!-- <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="#" class="btn btn-theme">Request Manning</a>
                    </div>
                </div> -->
            </div>
        </div>

        <div>
            <div class="row g-4">
                <div class="col-lg-6 ">
                    <div class="card">
                        <div class="card-title">
                            <h3>Form Templates</h3>
                            <div class="text-end">
                                <a href="{{ route('Performance.config.PerformanceTemplateFormList') }}" class="a-link">View All</a>
                            </div>
                        </div>
                        <form id="FormTemplate">
                            @csrf

                            <div class="mb-2">
                                <select class="form-select  ResortDivision" id="ResortDivision" name='ResortDivision' aria-label="Default select example">
                                    <option  > </option>
                                    @if($resort_divisions->isNotEmpty())
                                        @foreach($resort_divisions as $resort_division)
                                            <option value="{{ $resort_division->id }}">{{ $resort_division->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="mb-2">
                                <select class="form-select select2 Department" aria-label="Default select example">
                                    <option ></option>

                                </select>
                            </div>
                            <div class="mb-2">
                                <select class="form-select  Section" aria-label="Default select example">
                                    <option ></option>

                                </select>
                            </div>
                            <div class="mb-3">
                                <select class="form-select select2 Position" aria-label="Default select example">
                                    <option ></option>

                                </select>
                            </div>
                            <div class="mb-3">
                                <button type="button" class="btn btn-themeSkyblue FormTemplate" data-flag="simple" disabled>Create Template</button>
                            </div>

                        <div class="card-footer text-end"><a href="#" class="btn btn-themeBlue btn-sm">Submit</a></div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-6 ">
                    <div class="card h-100">
                        <div class="card-title">
                            <h3>Review Types</h3>
                        </div>
                        <div class="mb-3">
                            <button type="button" class="btn btn-themeSkyblue AddReviewCategories">Add Review Categories</button>
                        </div>
                            <form id="reviewForm" data-parsley-validate>
                                @csrf
                                <div class="AppendReviewCategories">
                                    <div class="row g-md-4 g-3 mb-1 flex-grow-1">
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control" name="category_title[]" placeholder="Category Title" required data-parsley-required-message="Category title is required">
                                        </div>
                                        <div class="col-sm-6">
                                            <input type="number" class="form-control" placeholder="Weightage" name="category_weightage[]" required data-parsley-type="number" data-parsley-required-message="Weightage is required">
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="review_categoriesCounts" name="review_categoriesCounts" value="1">

                                <div class="card-footer text-end">
                                        <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                                </div>
                            </form>
                            <div class="row g-md-4 g-3 mb-1 flex-grow-1">
                                <table class="table table-ReviewType" id="ReviewType">
                                    <thead>
                                        <tr>
                                            <th>Category Title</th>
                                            <th>Wedightage</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>

                            </div>



                    </div>
                </div>




                <div class="col-lg-6 ">
                    <div class="card ">
                        <div class="card-title">
                            <h3>Professional Development Form</h3>
                            <div class="text-end">
                                <a href="{{ route('Performance.config.ProfessionalFormList') }}" class="a-link">View All</a>
                            </div>
                        </div>
                        <div class="mb-3">
                            <button type="button" class="btn btn-themeSkyblue " data-flag="ProfessionalForm" id="ProfessionalForm">Create Template</button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 ">
                    <div class="card ">
                        <div class="card-title">
                            <h3>90 Day Apprasial Form</h3>
                            <div class="text-end">
                                <a href="{{ route('Performance.config.NintyPerformanceFormList') }}" class="a-link">View All</a>
                            </div>
                        </div>
                        <div class="mb-3">
                            <button type="button" class="btn btn-themeSkyblue " data-flag="nintyDayForm" id="nintyDayForm">Create Template</button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6  ">
                    <div class="card h-100">
                        <div class="card-title">
                            <h3>Performance- Meeting Email</h3>

                        </div>
                            <form id="ScheduleMeetingEmailForm">
                                @csrf

                                <div class="mb-3">

                                    <textarea class="form-control ScheduleMeetingEmail" rows="7" name="ScheduleMeetingEmail" id="ScheduleMeetingEmail">
                                        {!! isset($PerformanceMeetingContent->content) ? $PerformanceMeetingContent->content : ''  !!}
                                    </textarea>
                                </div>

                                <div class="modal-footer">
                                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                                    <button type="submit"class="btn btn-themeBlue">Submit</button>
                                </div>
                            </form>
                            <hr>
                            <div class="placeholders">
                                <strong>Available Placeholders:</strong>
                                <ul>
                                    <li><code class="insert-placeholder" data-placeholder="user_name">{Employee_name}</code> - EmployeeName</li>
                                    <li><code class="insert-placeholder" data-placeholder="Meeting_Link">{Title}</code> - Title</li>
                                    <li><code class="insert-placeholder" data-placeholder="Meeting_Link">{Description}</code> - Description</li>
                                    <li><code class="insert-placeholder" data-placeholder="Meeting_Link">{Meeting_Link}</code> - MeetingLink</li>
                                    <li><code class="insert-placeholder" data-placeholder="Meeting_Time">{Meeting_Date}</code> - MeetingDate</li>
                                    <li><code class="insert-placeholder" data-placeholder="Meeting_Time">{Meeting_Time}</code> - MeetingTime</li>
                                    <li><code class="insert-placeholder" data-placeholder="Meeting_Time">{Meeting_Location}</code> - Meeting Location</li>

                                    <li><code class="insert-placeholder" data-placeholder="Your_Name">{Your_Name}</code> - YourName</li>
                                    <li><code class="insert-placeholder" data-placeholder="Your_Designation">{Your_Designation}</code>  - Your Designation</li>
                                    <li><code class="insert-placeholder" data-placeholder="Resort_Name">{Resort_Name}</code> - ResortName</li>

                                </ul>
                            </div>
                    </div>


                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="FormTemplete-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <form id="jobDesEditForm" data-parsley-validate>
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel_performance"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="flag" id="flag">
                    <input type="hidden" name="Division_id" id="FormResortDivision">
                    <input type="hidden" name="Department_id" id="FormResortDepartment">
                    <input type="hidden" name="Section_id" id="FormResortSeciton">
                    <input type="hidden" name="Position_id" id="FormResortPosition">
                    <div class="row">
                        <div class="col-md-3">
                            <label>Form Name</label>
                            <input type="text" name="form_name" id="form_name" class="form-control"
                                required data-parsley-required-message="Form Name is required">
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <div id="form-builder"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button type="button" id="save-form" class="btn btn-themeBlue">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script src="https://formbuilder.online/assets/js/form-builder.min.js"></script>
<script src="https://formbuilder.online/assets/js/form-render.min.js"></script>
<script>

       $(document).ready(function ()
       {
                // CKEDITOR.replace('ScheduleMeetingEmail');

                //         CKEDITOR.instances['ScheduleMeetingEmail'].destroy(); // Destroy existing CKEditor instance if it exists

            CKEDITOR.replace('ScheduleMeetingEmail'); // Re-initialize CKEditor

            const BASE_URL = "{{ asset('resorts_assets/lang/en-US.lang') }}";
            const options = {
                    disableFields: ['autocomplete', 'button'],
                    i18n: {
                        locale: 'en-US',
                        override: {},
                        url: BASE_URL
                    }
                };

            const formBuilder =$('#form-builder').formBuilder(options);
            $('#save-form').click(function (e) {
                var formname = $('#form_name').val();
                var flag= $('#flag').val();

                if(!isNaN(formname))
                {
                    toastr.error('Please enter  form Name.', "Error", {
                            positionClass: 'toast-bottom-right'
                        });

                    return false;
                }
                const formStructure = formBuilder.actions.getData('json');
                e.preventDefault();
                if(flag =="nintyDayForm")
                {
                    $.ajax({
                        url: "{{ route('Performance.config.NintyDayPeformanceFormStore') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            FormName: formname, // Example, make dynamic
                            form_structure: formStructure
                        },
                        success: function (response) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $("#jobDesEditForm")[0].reset();
                            $("#FormTemplete-modal").modal('hide');

                            $("#ResortDivision").val(null).trigger('change');
                            $(".Department").val(null).trigger('change');
                            $(".Section").val(null).trigger('change');
                            $(".Position").val(null).trigger('change');
                            formBuilder.actions.setData([]);
                            $(".FormTemplate").attr('disabled', true);
                        },
                        error: function(response)
                        {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error)
                            {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
                else if(flag =="ProfessionalForm")
                {
                    $.ajax({
                        url: "{{ route('Performance.config.ProfessionalFormStore') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            FormName: formname, // Example, make dynamic
                            form_structure: formStructure
                        },
                        success: function (response) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $("#jobDesEditForm")[0].reset();
                            $("#FormTemplete-modal").modal('hide');

                            $("#ResortDivision").val(null).trigger('change');
                            $(".Department").val(null).trigger('change');
                            $(".Section").val(null).trigger('change');
                            $(".Position").val(null).trigger('change');
                            formBuilder.actions.setData([]);
                            $(".FormTemplate").attr('disabled', true);
                        },
                        error: function(response)
                        {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error)
                            {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
                else
                {
                    $.ajax({
                        url: "{{ route('Performance.config.PerformanceTemplateFormStore') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            Division_id : $('#FormResortDivision').val(),
                            Department_id : $('#FormResortDepartment').val(),
                            Section_id : $('#FormResortSeciton').val(),
                            Position_id : $('#FormResortPosition').val(),
                            FormName: formname, // Example, make dynamic
                            form_structure: formStructure
                        },
                        success: function (response)
                        {
                            toastr.success('Form saved successfully!', "Success",
                            {
                                positionClass: 'toast-bottom-right'
                            });
                            $("#jobDesEditForm")[0].reset();
                            $("#FormTemplete-modal").modal('hide');
                            $("#ResortDivision").val(null).trigger('change');
                            $(".Department").val(null).trigger('change');
                            $(".Section").val(null).trigger('change');
                            $(".Position").val(null).trigger('change');
                            formBuilder.actions.setData([]);
                            $(".FormTemplate").attr('disabled', true);
                        },
                        error: function(response)
                        {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error)
                            {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }

            });
            $("#ResortDivision").select2({placeholder: "Select Division"});
            $(".Department").select2({placeholder: "Select Department"});
            $(".Section").select2({placeholder: "select Section"});
            $(".Position").select2({placeholder: "select Position"});
             ReviewType();
            $('#reviewForm').parsley();
            $('#reviewForm').on('submit', function (e) {
                e.preventDefault(); // Prevent form submission

                if ($(this).parsley().isValid()) {
                    $.ajax({
                        url: "{{ route('Performance.config.ReviewTypes') }}",  // Update with your Laravel route
                        type: "POST",
                        data: $(this).serialize(),
                        dataType: "json",
                        beforeSend: function () {
                        },
                        success: function (response) {
                            if (response.success) {
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                                $('#reviewForm')[0].reset(); // Reset form
                                $('#reviewForm').parsley().reset(); // Reset Parsley validation
                                ReviewType();
                            } else {
                                toastr.error("Failed to save deductions.", "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function (xhr) {
                            if (xhr.responseJSON) {
                                const errors = xhr.responseJSON.errors;
                                let errorMessages = '';
                                Object.keys(errors).forEach(function (key) {
                                    errorMessages += `${errors[key]}<br>`;
                                });
                                toastr.error(errorMessages, "Validation Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        }
                    });
                }
            });
            $(".Position , .Section").on("change",function(){
                $(".FormTemplate").attr('disabled', false);
                $("#FormResortDivision").val($('#ResortDivision').val());
                $("#FormResortDepartment").val($('.Department').val());
                $("#FormResortSeciton").val($('.Section').val());
                $("#FormResortPosition").val($('.Position').val());
            });




        });

            $('#ScheduleMeetingEmailForm').validate({
                rules: {
                    ScheduleMeetingEmail: {
                        required: true,
                    }
                },
                messages: {
                    ScheduleMeetingEmail: {
                        required: "Please Enter Meeting Content",
                    }
                },
                submitHandler: function(form) {
                    const content = CKEDITOR.instances['ScheduleMeetingEmail'].getData(); // Get CKEditor content
                    $.ajax({
                        url: "{{ route('Performance.config.ScheduleMeetingEmail') }}",
                        type: "POST",
                        data: {
                            "ScheduleMeetingEmail": content, // Send CKEditor content
                            "_token": "{{ csrf_token() }}"
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                                // Close modal after success
                                $('#jobDesEdit-modal').modal('hide');
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
                            $("#jobDesEdit-modal form")[0].reset();
                            $("#jobDesEdit-modal input[type='hidden']").val('');

                            // Reset CKEditor
                            if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['ScheduleMeetingEmail']) {
                                CKEDITOR.instances['ScheduleMeetingEmail'].setData('');
                            }
                        }
                    });
                }
            });
        $(document).on('click', '.FormTemplate , #nintyDayForm ,#ProfessionalForm',"#ProfessionalForm", function()
        {
            var flag= $(this).data('flag');
            $("#flag").val(flag);
            $('#form_name').val(' ');
            $("#FormTemplete-modal").modal('show');
            var header='';
            if(flag =="ProfessionalForm")
            {
                header ="Professional Form";
            }
            else if(flag =="nintyDayForm")
            {
                header ="90 Day Form";
            }
            else
            {
                header ="Form Template";
            }
            $("#staticBackdropLabel_performance").text(header);
        });
        $(document).on('change', '.ResortDivision', function() {
            $.ajax({
                url: "{{ route('resort.get.ResortDivision') }}",
                type: "post",
                data: {
                    division_id: $(this).val(),
                },
                success: function(data) {

                    // Clear the dropdown and add a placeholder option
                    $(".Department").html('<option value="">Select Department</option>');
                    if(data.success == true) {
                        let string='<option></option>';
                        // Append new options
                        $.each(data.data, function(key, value) {

                            string+='<option value="'+value.id+'">'+value.name+'</option>';
                        });

                        $(".Department").html(string);

                    } else {
                        let string='<option></option>';
                    }
                },
                error: function(response) {
                    toastr.error("Department Not Found", { positionClass: 'toast-bottom-right' });
                }
            });
        });

        $(document).on('change', '.Department', function() {
            var deptId = $(this).val();
            $.ajax({
                url: "{{ route('resort.ta.PositionSections') }}",
                type: "post",
                data: {
                    deptId: deptId
                },
                success: function(d) {
                    // Clear the dropdown and add a placeholder option


                    if(d.success == true) {

                        let string='<option></option>';
                        $.each(d.data.ResortPosition, function(key, value) {
                            string+='<option value="'+value.id+'">'+value.position_title+'</option>';
                        });
                        $(".Position").html(string);

                        let string1='<option></option>';
                        $.each(d.data.ResortSection, function(key, value) {
                            string1+='<option value="'+value.id+'">'+value.name+'</option>';
                        });
                        $(".Section").html(string1);

                    }
                },
                error: function(response) {
                    toastr.error("Position Not Found", { positionClass: 'toast-bottom-right' });
                }
            });
        });
        $(document).on('click', '.AddReviewCategories', function () {

            id = $("#review_categoriesCounts").val();

            id = parseInt(id) + 1;
            $(".AppendReviewCategories").append(`
                            <div class="row g-md-4 g-3 mb-1 remove_id_${id} flex-grow-1">
                                <hr>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control" name="category_title[]" placeholder="Category Title" required data-parsley-required-message="Category title is required">
                                        </div>
                                        <div class="col-sm-4">
                                            <input type="number" class="form-control" placeholder="Weightage" name="category_weightage[]" required data-parsley-type="number" data-parsley-required-message="Weightage is required">
                                        </div>

                                    <div class="col-sm-2">
                                        <button type="button" data-id="${id}"  class=" btn btn-sm btn-danger removeReviewCategories"><i class="fa fa-trash"></i></button>
                                    </div>
                            </div>
                            `);
            $("#review_categoriesCounts").val(id);

        });
        $(document).on('click', '.removeReviewCategories', function () {
            var locaiton = $(this).data('id');
            $(".remove_id_" + locaiton).remove();

            id = parseInt(locaiton) - 1;

          $("#review_categoriesCounts").val(id);

        });


        $(document).on("click", "#ReviewType .edit-row-btn", function (event) {
            event.preventDefault(); // Prevent default action

            // Find the parent row
            var $row = $(this).closest("tr");

            // Extract division ID
            var id = $(this).data('id');

            var category_title = $row.find("td:nth-child(1)").text().trim();
            var category_weightage = $row.find("td:nth-child(2)").text().trim();

            var editRowHtml = `
                    <td class="py-1">
                        <div class="form-group">
                            <input type="text" class="form-control category_title" value="${category_title}" />
                        </div>
                    </td>
                    <td class="py-1">
                        <div class="form-group">
                            <input type="number" class="form-control category_weightage" value="${category_weightage}" />
                        </div>
                    </td>
                    <td class="py-1">
                        <a href="#" class="btn btn-theme update-row-btn_ReviewType" data-id="${id}">Submit</a>
                    </td>
                `;

            // Replace row content with editable form
            $row.html(editRowHtml);
        });
        $(document).on("click", "#ReviewType .update-row-btn_ReviewType", function (event) {
            event.preventDefault(); // Prevent default action
            var $row = $(this).closest("tr");
            var id = $(this).data('id');
            var category_title = $row.find("input").eq(0).val();
            var category_weightage = $row.find("input").eq(1).val();

            $.ajax({
                url: "{{ route('Performance.config.ReviewTypes.inlineUpdate', '') }}/" + id,
                type: "PUT",
                data: {
                    category_title : category_title,
                    category_weightage : category_weightage,
                },
                success: function(response) {
                    if(response.success == true) { // Ensure response contains a success key
                        // Update the row with new values
                        var updatedRowHtml = `
                            <td class="text-nowrap">${category_title}</td>
                            <td class="text-nowrap">${category_weightage}</td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center">
                                    <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-id="${id}">
                                        <img src="{{ asset('resorts_assets/images/edit.svg') }}" alt="" class="img-fluid" />
                                    </a>
                                    <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn" data-id="${id}">
                                        <img src="{{ asset('resorts_assets/images/trash-red.svg') }}" alt="" class="img-fluid" />
                                    </a>
                                </div>
                            </td>
                        `;

                        $row.html(updatedRowHtml);

                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr) {

                        let errorMessage = '';

                        if (xhr.responseJSON && xhr.responseJSON.errors) {

                            $.each(xhr.responseJSON.errors, function(key, error) {

                            errorMessage += error + "<br>";
                            })
                        }
                        else
                        {
                            errorMessage = "An error occurred while Create or Update."; // Default error message
                        }

                        toastr.error(errorMessage, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
            });


        });


        $(document).on('click', '.delete-row-btn', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest("tr");

            // Get the division ID from the data attribute
            var main_id = $(this).data('id');

            Swal.fire({
                title: 'Sure want to delete?',
                text: 'This cannot be undone',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: "#DD6B55"
             }).then((result) => {
                if (result.isConfirmed)
                {

                    $.ajax({
                        type: "delete",
                        url: "{{ route('Performance.DestroyReviewTypes','') }}/"+main_id,
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success == true) {
                            $row.remove();

                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });

                            ReviewType();

                        }
                            else {
                            toastr.error(result.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }).fail(function(error) {
                        toastr.error("Something went wrong", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    });
                }
            });
        });

        function ReviewType()
        {
            if ($.fn.dataTable.isDataTable('#ReviewType'))
            {
                $('#ReviewType').DataTable().destroy();
            }

            var TableAccomMainten = $('#ReviewType').DataTable({
                "searching": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": true,
                "bAutoWidth": false,
                "scrollX": true,
                "iDisplayLength": 6,
                processing: true,
                serverSide: true,
                order:[[3,'desc']],
                ajax: {
                    url: '{{ route("Performance.configuration") }}',
                    type: 'GET',
                    data: function (d) {
                        d.ResortDepartment = $(".ResortDepartment").val();
                    }
                },
                columns: [
                    { data: 'category_title', name: 'category_title', className: 'text-nowrap' },
                    { data: 'category_weightage', name: 'category_weightage', className: 'text-nowrap' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                    {data:'created_at', visible:false,searchable:false},
                ]
            });
        }
</script>
@endsection
