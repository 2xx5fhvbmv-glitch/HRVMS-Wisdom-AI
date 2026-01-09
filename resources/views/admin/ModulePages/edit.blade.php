@extends('admin.layouts.app')
@php

    $Flag= ( isset($data->id))  ?  'Edit' : 'Create  ';

@endphp
  @section('page_tab_title' , $Flag." Module")


@section('content')
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card ">
        <div class="card-header">
            <h1>{{$Flag}} Module Page</h1>

        </div>
        <form method="post" name="formStore" id="formStore" enctype="multipart/form-data" @if(empty($data->id)) action="{{ route('admin.ModulePages.store') }}" @else action="{{ route('admin.ModulePages.update', $data->id) }}" @endif >
          @csrf

          @if(isset($data->id ) && $data->id != '')
            <input type="hidden" name="id" id="id" value="{{$data->id}}">
          @endif

          <div class="card-body">
            <div class="row">

                <div class="col-sm-3">
                    <div class="form-group">
                      <label>Select Module <span class="red-mark">*</span></label>
                        <select  class="form-control"  name="Module_Id" id="Module_Id">
                            <option></option>
                          @foreach ($ActiveModules as $module)
                            <option value="{{ $module->id }}" @if(isset($data->Module_Id) && $data->Module_Id == $module->id) selected @endif   >{{ $module->module_name }}</option>
                          @endforeach

                        </select>
                      <div id="div-Module_id"></div>
                    </div>
                  </div>
              <div class="col-sm-3">
                <div class="form-group">
                  <label>Page Name <span class="red-mark">*</span></label>
                  <input
                    data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed."
                    data-parsley-errors-container="#div-page_name"
                    data-parsley-pattern="^[A-Za-z0-9\s\/\(\)\-\_]+$"
                    required
                  data-parsley-maxlength="40"
                  data-parsley-maxlength-message="Maximum 40 characters allowed"
                    type="text"
                    name="page_name"
                    id="page_name"
                    class="form-control"
                    placeholder="Module Name"
                    value="{{ old('page_name') ? old('page_name') : ($data->page_name ?? '') }}"
                    title="Only letters, numbers, spaces, and certain special characters are allowed.">
                  <div id="div-page_name"></div>
                </div>
              </div>
         
              <div class="col-sm-3">
                <div class="form-group">
                  <label>Route Type <span class="red-mark">*</span></label>
                  <select
                    data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed."
                    data-parsley-errors-container="#div-internal_route"
                    required
                    name="type"
                    id="type"
                    class="form-control"
                    placeholder="Route Type">
                    <option value="para" @if(isset($data->type) && $data->type =="para") selected @endif >Parameter</option>
                    <option value="normal" @if(isset($data->type) && $data->type == "normal") selected @endif >Normal</option>
                </select>
                  <div id="div-page_name"></div>
                </div>
              </div>
              <div class="col-sm-3">
                <div class="form-group">
                  <label> Type of Page <span class="red-mark">*</span></label>
                  <select
                    data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed."
                    data-parsley-errors-container="#div-internal_route"
                    required
                    name="TypeOfPage"
                    id="type"
                    class="form-control"
                    placeholder="Route Type">
                    <option value="InsideOfPage" @if(isset($data->TypeOfPage) && $data->TypeOfPage =="InsideOfPage") selected @endif>Inside Of Page</option>
                    <option value="InsideOfMenu" @if(isset($data->TypeOfPage) && $data->TypeOfPage =="InsideOfMenu") selected @endif>InsideOfMenu</option>
                </select>
                  <div id="div-page_name"></div>
                </div>
              </div>

              <div class="col-sm-3">
                <div class="form-group">
                  <label>Route Name <span class="red-mark">*</span></label>
                  <input
                    data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed."
                    data-parsley-errors-container="#div-internal_route"
                    required
                    type="text"
                    name="internal_route"
                    id="internal_route"
                    class="form-control"
                    placeholder="Internal Route"
                            data-parsley-maxlength="50"
                    data-parsley-maxlength-message="Maximum 50 characters allowed"
                    value="{{ old('internal_route') ? old('internal_route') : ($data->internal_route ?? '') }}"
                    title="Only letters, numbers, spaces, and certain special characters are allowed.">
                  <div id="div-page_name"></div>
                </div>
              </div>

              <div class="col-sm-3">
                <div class="form-group">
                  <label>Place Order <span class="red-mark">*</span></label>
                  <input
                    data-parsley-validate-script
                    data-parsley-validate-script-message="Script tags are not allowed."
                    data-parsley-errors-container="#div-place_order"
                    required
                    type="number"
                    name="place_order"
                    id="place_order"
                    class="form-control"
                    placeholder="Place Order"
                    value="{{ old('place_order') ? old('place_order') : ($data->place_order ?? '') }}">
                  <div id="div-place_order"></div>
                </div>
              </div>

              <div class="col-sm-3">
                  <div class="form-group">
                      <label>Status <span class="red-mark">*</span></label>
                      <div class="form-group brdrfull">
                          <div class="row">
                              <div class="col-auto pr-0">
                                  <div class="age-title">
                                      <input type="radio" @if(isset($data->id) && $data->id != '' && $data->status == "Active") checked="true" @else checked="true" @endif id="status-yes" name="status" value="Active">
                                      <label for="status-yes">Active</label>
                                  </div>
                              </div>
                              <div class="col-auto">
                                  <div class="age-title">
                                      <input type="radio" id="status-no" value="Inactive" name="status" @if(isset($data->id) && $data->id != '' && $data->status == "Inactive") checked="true" @endif>
                                      <label for="status-no">Inactive</label>
                                  </div>
                              </div>
                          </div>
                      </div>
                      <div id="div-status"></div>
                  </div>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-info">Submit</button>
            <button type="button" onclick="document.getElementById('formStore').reset();"
            class="btn btn-default resetForm">Reset</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</div>
@endsection

@section('import-css')
@endsection
@section('import-scripts')
<script>
  $(document).ready(function() {
    $("#Module_id").select2({
        placeholder: 'Select an Module Name',
        allowClear: true
    });


    $.validator.addMethod("alphabeticSpace", function(value, element) {
        return this.optional(element) || /^[A-Za-z\s\/\(\)\-\_]+$/.test(value);
    }, "Only letters, spaces, and allowed symbols are allowed.");

    $('#formStore').validate({
        errorClass: 'text-danger-custom',
        ignore: [], // Use this to prevent ignoring hidden fields (in case)
        rules: {
            'Module_Id': {
                required: true
            },
            'page_name': {
                required: true,
                alphabeticSpace: true,
                maxlength: 255,
            },
            'status': {
                required: true
            }
        },
        messages: {
            'Module_Id': {
                required: "Please select a module."
            },
            'page_name': {
                required: "The Page name field is required",
                maxlength: "Max length should be 255 characters",
            },
            'status': {
                required: "Please select a status."
            }
        },
        errorPlacement: function(error, element) {
            if (element.attr("name") == "Module_id") {
                error.insertAfter("#div-Module_id");
            } else if (element.attr("name") == "page_name") {
                error.insertAfter("#div-page_name");
            } else if (element.attr("name") == "status") {
                error.insertAfter("#div-status");
            } else {
                error.insertAfter(element);
            }
        },
        errorElement: 'span'
    });


    $("#formStore").on("submit", function(e) {
            e.preventDefault();

            var dataString = $(this).serialize();
            var url = $(this).attr('action');

            $('#formStore').validate({
                errorClass: 'text-danger-custom',
                ignore: [],
                rules: {
                    'name': {
                        required: true,
                        alphabeticSpace: true,
                        maxlength: 255 ,
                    },
                    'status': {
                        required: true
                    }
                },
                messages: {
                    'name': {
                        required: "The name field is required",
                        maxlength: "Max length should be 255 characters" ,
                    },
                    'status': {
                        required: "The status field is required"
                    }
                },
                errorPlacement: function(error, element) {
                    if( element.attr("name") == "name" ) {
                        error.insertAfter( "#module_name" );
                    } else if( element.attr("name") == "status" ) {
                        error.insertAfter( "#div-status" );
                    } else {
                        error.insertAfter(element);
                    }
                },
                errorElement: 'span'
            });


            if ($('#formStore').parsley().isValid()) {
                var dataString = $(this).serialize();
                var url = $(this).attr('action');
                $.ajax({
                type: "POST",
                url: url,
                data: new FormData(this),
                dataType: "JSON",
                contentType: false,
                processData: false,
                beforeSend: function(msg) {
                    HoldOn.open({
                    theme: "sk-cube-grid"
                    });
                },
                success: function(result) {
                    HoldOn.close();
                    if (result.success == true) {
                    toastr.success(result.msg, "Success", {
                        positionClass: 'toast-bottom-right'
                    });

                    window.setTimeout(function() {
                        window.location.href = result.redirect_url;
                    }, 2000);
                    } else {
                    toastr.error(result.msg, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                    }
                },
                error: function(result) {
                    HoldOn.close();
                    toastr.error("Something went wrong", "Error", {
                    positionClass: 'toast-bottom-right'
                    });
                }
                });
            }
            });
  });
</script>

@endsection
