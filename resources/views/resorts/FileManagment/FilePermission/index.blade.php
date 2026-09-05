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
                            <span>People</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto  ms-auto"><a class="btn btn-theme" href="#">Add New Employee</a></div> -->
                </div>
            </div>
            <div class="card">
                
                <form id="FilePermisson">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                        <select class="form-select dd-native-select" name="ResortDivision" id="ResortDivision">
                                    <option ></option>
                                    @if($ResortDivision->isNotEmpty())
                                        @foreach ($ResortDivision as $d)
                                            <option value="{{ $d->id }}">{{ $d->name }}</option>

                                        @endforeach
                                    @endif

                                </select>
                        <div class="dd" data-target="#ResortDivision">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select Division</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Division">
                                <div class="dd-scroll">
                                    @if($ResortDivision->isNotEmpty())
                                        @foreach ($ResortDivision as $d)
                                            <div class="dd-item" role="option" data-value="{{ $d->id }}"><span class="dd-nm">{{ $d->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">

                            <select class="form-control form-select dd-native-select" name="department" id="department">
                                <option></option>
                            </select>
                            <div class="dd" data-target="#department">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Department</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Department">
                                    <div class="dd-scroll"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-auto col-6">
                        <select class="form-control form-select" multiple name="position[]" id="position">
                        <option></option>

                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select Select_folder" name="Select_folder" id='Select_folder' data-placeholder="Select Folder">
                                <option></option>
                                @if($FolderList->isNotEmpty())
                                    @foreach($FolderList as $f)
                                        <option value="{{$f->Folder_unique_id}}">{{$f->Folder_Name}}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="dd" data-target="#Select_folder">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Folder</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Folder">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a folder…"></div>
                                    <div class="dd-scroll">
                                        @if($FolderList->isNotEmpty())
                                            @foreach($FolderList as $f)
                                                <div class="dd-item" role="option" data-value="{{ $f->Folder_unique_id }}"><span class="dd-nm">{{ $f->Folder_Name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-3 col-xl-2 col-lg-5 col-md-7 col-sm-5 ">
                            <div class="input-group">
                                <input type="search" class="form-control search" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                    </div>
                </div>
                    <div class="card-title">
                        <h3>Folder Name</h3>
                    </div>
                    <div class="table-responsive mb-3">
                        <table class="table table-lable table-filePermission mb-1">
                            <thead>
                                <tr>
                                    <th>
                                        <div class="form-check">
                                            <input class="form-check-input parent_checkbox"   type="checkbox"></th>
                                        </div>
                                    <th>File Name</th>
                                    <th>Size</th>
                                    <th>Last Modified</th>
                                </tr>
                            </thead>
                            <tbody id="appendFiles">
                                
                            
                            </tbody>

                        </table>
                    </div>
              
                    <div class="card-footer">
                        <button type="submit" class="btn eb-btn-primary btn-sm"> Submit</button>
                    </div>
                </form>
            </div>
            

        </div>
    </div>
@include('resorts._emotional_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-css')

@endsection

@section('import-scripts')
<script>
$(document).ready(function() {

    $('#Folderselect').select2({
        placeholder: "Select Folder",
        allowClear: true
    });

    $("#position").select2({
    'placeholder':'Select position',
    });
    $(document).on('change', '.parent_checkbox', function() {
            if ($(this).is(':checked')) {
                $(".Resort_parent_checkbox").prop('checked', true);
            } else {
                $(".Resort_parent_checkbox").prop('checked', false);
            }
        });
    $(document).on('change', '#ResortDivision', function() {
        $.ajax({
            url: "{{ route('resort.get.ResortDivision') }}",
            type: "post",
            data: {
                division_id: $(this).val(),
                _token: "{{ csrf_token() }}"
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
                wisdomDD.rebuild('#department');
            },
            error: function(response) {
               // toastr.error("Position Not Found", { positionClass: 'toast-bottom-right' });
            }
        });
    });
    $(document).on('change', '#department', function() {
        var deptId = $(this).val();

        $.ajax({
            url: "{{ route('resort.get.position') }}",
            type: "post",
            data: {
                deptId: deptId,
                _token: "{{ csrf_token() }}"
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


    $(document).on("change","#position",function(){

        updatePermission();

    });
    $(document).on("change","#Select_folder",function(){
   
        updatePermission();
       
    });
    $('#FilePermisson').validate({
            rules: {
                department: 
                {
                    required: true,
                },
                "Permission[]": 
                {
                    required: true,
                }
            },
            messages: {
                department: 
                {
                    required: "Please select a department.",
                },
                "Permission[]": 
                {
                    required: "Please select a position.",
                }
        },
        submitHandler: function(form) {
            $.ajax({
                url: "{{ route('FileManage.StoreFilePermission') }}", // Ensure route is correct
                type: "POST",
                data: $(form).serialize(),
                success: function(response) 
                {
                    if (response.success) 
                    {
                          toastr.success(response.message, "Success", 
                        {
                            positionClass: 'toast-bottom-right'
                        });
                    } 
                    else 
                    {
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

    $(".search").on("keyup",function(){
        $.ajax({
            url: "{{ route('FileManage.SearchPermissionfile') }}",
            type: "post",
            data: {
                folder_id: $("#Select_folder").val(),
                "department":$("#department").val(),
                "position":$("#position").val(),
                "filename":$(this).val(),
                "_token":"{{csrf_token()}}"
            },
            success: function(data) {
                // Clear the dropdown and add a placeholder option

                if(data.success == true) {
                  $("#appendFiles").html(data.d);
                } else {
                    $("#department").empty().append('<option value="">Select Department</option>');
                }
            },
            error: function(response) {
                toastr.error("Position Not Found", { positionClass: 'toast-bottom-right' });
            }
        });
    });
});
function updatePermission()
{
    $.ajax({
            url: "{{ route('FileManage.GetPermissionfile') }}",
            type: "post",
            data: {
                folder_id: $("#Select_folder").val(),
                "department":$("#department").val(),
                "position":$("#position").val(),
                "_token":"{{csrf_token()}}"
            },
            success: function(data) {
                // Clear the dropdown and add a placeholder option

                if(data.success == true) 
                {
                  $("#appendFiles").html(data.d);
                } else {
                    $("#department").empty().append('<option value="">Select Department</option>');
                }
            },
            error: function(response) {
                // toastr.error("Position Not Found", { positionClass: 'toast-bottom-right' });
            }
        });
}



</script>
@endsection 