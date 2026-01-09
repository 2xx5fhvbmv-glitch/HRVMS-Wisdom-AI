@extends('resorts.layouts.app')
@section('page_tab_title', "Permissions")

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Site Settings</span>
                        <h1>Permissions</h1>
                    </div>
                </div>
                <!-- <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="#" class="btn btn-theme">Request Manning</a>
                    </div>
                </div> -->
            </div>
        </div>

        <form method="POST" id="ResortInteranlPemissionForm" class="form-horizontal">
            <div class="card">
                <div class="card-header">


                        <div class="row g-md-3 g-2 align-items-center">

                            <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                                <select class="form-select" name="ResortDivision" id="ResortDivision">
                                    <option ></option>
                                    @if($ResortDivision->isNotEmpty())
                                        @foreach ($ResortDivision as $d)
                                            <option value="{{ $d->id }}">{{ $d->name }}</option>

                                        @endforeach
                                    @endif

                                </select>
                            </div>
                            <div class="col-xl-2 col-md-4 col-sm-4 col-6">

                                    <select class="form-control form-select" name="department" id="department">
                                        <option></option>
                                    </select>

                            </div>
                            <div class="col-xl-2 col-md-4 col-sm-4 col-6">

                                <select class="form-control form-select" name="position" id="position">
                                        <option></option>

                                    </select>
                            </div>
                            <div class="col-xl-3 col-lg-5 col-md-8 col-sm-8 ">
                                <div class="input-group">
                                    <input type="sh" class="form-control searchPermission" placeholder="Search">
                                    <i class="fa-solid fa-search"></i>
                                </div>
                            </div>
                        </div>

                </div>

                <div class="permissions-accordion" id="accordionExample">

                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-end mt-3"></div>
                            <button type="submit" class="btn btn-theme">Save</button>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>


@endSection
@section('import-css')
@endsection

@section('import-scripts')

<script>
$(document).ready(function() {

    $("#ResortDivision").select2({
    'placeholder':'Select ResortDivision',
    });
    $("#department").select2({
    'placeholder':'Select Department',
    });
    $("#position").select2({
    'placeholder':'Select position',
    });


    $(document).on('change', '#ResortDivision', function() {


        $.ajax({
            url: "{{ route('resort.get.ResortDivision') }}",
            type: "post",
            data: {
                division_id: $(this).val(),
            },
            success: function(data) {
                // Clear the dropdown and add a placeholder option
                $("#department").empty().append('<option value="">Select Department</option>');

                if(data.success == true) {
                    // Append new options
                    $.each(data.data, function(key, value) {

                        $("#department").append('<option value="'+value.id+'">'+value.name+'</option>');
                    });
                    $("#accordionExample").html(`<div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne1">
                                    <button class="accordion-button " type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne1">
                                        Select Department ..!
                                    </button>
                                </h2>
                            </div>`);
                } else {
                    // If no data, just keep the placeholder
                    $("#department").empty().append('<option value="">Select Department</option>');
                }
            },
            error: function(response) {
                toastr.error("Position Not Found", { positionClass: 'toast-bottom-right' });
            }
        });
    });
    $(document).on('change', '#department', function() {
        var deptId = $(this).val();

            $.ajax({
                url: "{{ route('resort.get.position') }}",
                type: "post",
                data: {
                    deptId: deptId
                },
                success: function(data) {
                    // Clear the dropdown and add a placeholder option
                    $("#position").empty().append('<option value="">Select Position</option>');

                    if(data.success == true) {
                        // Append new options
                        $.each(data.data, function(key, value) {
                            $("#position").append('<option value="'+value.id+'">'+value.position_title+'</option>');
                        });
                        $("#accordionExample").html(`<div class="accordion-item">
                                    <h2 class="accordion-header" id="headingOne1">
                                        <button class="accordion-button " type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne1">
                                            Select Position..!
                                        </button>
                                    </h2>
                                </div>`);
                    } else {
                        // If no data, just keep the placeholder
                        $("#position").empty().append('<option value="">Select Position</option>');
                    }
                },
                error: function(response) {
                    toastr.error("Position Not Found", { positionClass: 'toast-bottom-right' });
                }
            });
        });

            $('#ResortInteranlPemissionForm').validate({
                    rules: {
                        department: {
                            required: true,

                        },
                        position: {
                            required: true,

                        }
                    },
                    messages: {
                        department: {
                            required: "Please select a department.",
                        },
                        position: {
                            required: "Please Select a position.",

                        }
                },
                submitHandler: function(form) {
                    $.ajax({
                        url: "{{ route('resorts.update_internal_permissions') }}", // Ensure route is correct
                        type: "POST",
                        data: $(form).serialize(),
                        success: function(response) {
                            if (response.success) {

                                toastr.success(response.msg, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                                $('#AddoccupancyForm').get(0).reset();
                            } else {
                                toastr.error(response.msg, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) { // Adjust according to your response format
                                errs += error + '<br>';
                            });
                            toastr.error(errs, { positionClass: 'toast-bottom-right' });
                        }
                    });
                },
                errorPlacement: function(error, element) {
                    if (element.hasClass("select2-hidden-accessible")) {
                        // For Select2, insert the error after the select2 container
                        error.insertAfter(element.next('.select2'));
                    } else {
                        error.insertAfter(element);
                    }
                },
                highlight: function(element) {
                    if ($(element).hasClass("select2-hidden-accessible")) {
                        $(element).next('.select2').find('.select2-selection').addClass('is-invalid');
                    } else {
                        $(element).addClass('is-invalid');
                    }
                },
                unhighlight: function(element) {
                    if ($(element).hasClass("select2-hidden-accessible")) {
                        $(element).next('.select2').find('.select2-selection').removeClass('is-invalid');
                    } else {
                        $(element).removeClass('is-invalid');
                    }
                }
            });


    });
    $(document).on('keyup', '.searchPermission', function() {

        let divisionId = $("#ResortDivision").val();
        let department = $("#department").val();
        let position = $("#position").val();


        $.ajax({
                    url: "{{ route('resorts.searchPermissions') }}", // Ensure route is correct
                    type: "POST",
                    data: {"_token":"{{ csrf_token() }}",SearchValue:$(this).val(),divisionId:divisionId,department:department,position:position},
                    success: function(data) {
                        if (data.success) {
                            if(data.success== true )
                            {
                                $("#accordionExample").html(data.html);
                            }
                            else
                            {
                                $("#accordionExample").html(data.html);
                            }

                        } else {
                            toastr.error(data.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(response) {
                        var errors = response.responseJSON;
                        var errs = '';

                        $.each(errors.errors, function(key, error) { // Adjust according to your response format
                            errs += error + '<br>';
                        });
                        toastr.error(errs, { positionClass: 'toast-bottom-right' });
                    }
                });



    });
        $(document).on('change', '.Resort_parent_checkbox', function() {
            var moduleId = $(this).data('id');
            if ($(this).is(':checked')) {
                $(".child_parent_" + moduleId).prop('checked', true);
            } else {
                $(".child_parent_" + moduleId).prop('checked', false);
            }
        });

$(document).on('change', '#position', function() {
        var position = $(this).val();

        var Departid = $("#department").val();

        $.ajax({
            url: "{{ route('resort.get.InternalPermissiones') }}",
            type: "post",
            data: {
                deptId: Departid,
                position_id:position,
            },
            success: function(data) {

                if(data.success== true )
                {
                    $("#accordionExample").html(data.html);
                }
                else
                {
                    $("#accordionExample").html(data.html);
                    $("#position").empty().append('');
                }



            },
            error: function(response) {
                // $("#accordionExample").html(response.html);
            toastr.error("Posistion Not Found ", { positionClass: 'toast-bottom-right' });
            }
        });
});

    // Child checkbox change
    $(document).on('change', '.child-checkbox', function() {

        var moduleId = $(this).attr('data-id').split('_')[0];

        // Check if all child checkboxes are checked
        if ($('.child-checkbox.child_parent_' + moduleId + ':checked').length === $('.child-checkbox.child_parent_' + moduleId).length) {
            $('#parent_' + moduleId).prop('checked', true);
        } else {
            $('#parent_' + moduleId).prop('checked', false);
        }
    });



    //

</script>

@endsection
