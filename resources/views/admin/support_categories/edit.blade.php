@extends('admin.layouts.app')
@php

    $Flag= ( isset($data->id))  ?  'Edit' : 'Create  ';

@endphp
  @section('page_tab_title' , $Flag." Support Categories")


@section('content')
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card ">
        <div class="card-header">
            <h1>{{$Flag}} Support Categories</h1>

        </div>
        <form method=" post" name="formStore" id="formStore" enctype="multipart/form-data" @if(empty($data->id)) action="{{ route('admin.support_categories.store') }}" @else action="{{ route('admin.support_categories.update', $data->id) }}" @endif >
          @csrf

          @if(isset($data->id ) && $data->id != '')
            <input type="hidden" name="id" id="id" value="{{$data->id}}">
          @endif

          <div class="card-body">
            <div class="row">


              <div class="col-sm-6">
                <div class="form-group">
                  <label>Category Name <span class="red-mark">*</span></label>
                  <input
                  data-parsley-validate-script
                  data-parsley-validate-script-message="Script tags are not allowed."
                  data-parsley-errors-container="#div-name"
                  data-parsley-pattern="^[A-Za-z0-9\s\/\(\)\-\_]+$"
                  required
                  type="text"
                  name="name"
                  id="name"
                  class="form-control"
                          data-parsley-maxlength="50"
                    data-parsley-maxlength-message="Maximum 50 characters allowed"
                  placeholder="Category Name"
                  value="{{ (old('name')) ? old('name') : ($data->name ?? '') }}"
                  title="Only letters, numbers, spaces, and certain special characters are allowed.">
                <div id="div-name"></div>

                </div>
              </div>

              <div class="col-sm-6">
                  <div class="form-group">
                      <label>Status <span class="red-mark">*</span></label>
                      <div class="form-group brdrfull">
                          <div class="row">
                              <div class="col-auto pr-0">
                                  <div class="age-title">
                                      <input type="radio" @if(isset($data->id) && $data->id != '' && $data->status == "Active") checked="true" @else checked="true" @endif id="status-yes" name="status" value="active">
                                      <label for="status-yes">Active</label>
                                  </div>
                              </div>
                              <div class="col-auto">
                                  <div class="age-title">
                                      <input type="radio" id="status-no" value="inactive" name="status" @if(isset($data->id) && $data->id != '' && $data->status == "Inactive") checked="true" @endif>
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
    $.validator.addMethod("alphabeticSpace", function(value, element) {
        return this.optional(element) || /^[A-Za-z\s\/\(\)\-\_]+$/.test(value);
    }, "Only letters, spaces, and allowed symbols are allowed.");

            $("#formStore").parsley();

            var id = $("#id").val();

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
