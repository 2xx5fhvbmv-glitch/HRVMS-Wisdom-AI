@extends('admin.layouts.app')

@if($isNew == 1)
  @section('page_tab_title' ,"Create Section")
@else
  @section('page_tab_title' ,"Edit Section")
@endif

@section('content')
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card ">
        <div class="card-header">
          @if( $isNew == 1 )
            <h1>Create Section</h1>
          @else
            <h1>Edit Section</h1>
          @endif
        </div>
        
        <form method=" post" name="formStore" id="formStore" enctype="multipart/form-data" @if(empty($data->id)) action="{{ route('admin.sections.store') }}" @else action="{{ route('admin.sections.update', $data->id) }}" @endif >

          @csrf

          @if($data->id != '')
            <input type="hidden" name="id" id="id" value="{{$data->id}}">
          @endif

          <div class="card-body">
              <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        <label for="department">Select Department</label>
                        <select name="dept_id" id="dept_id" class="form-control select-2-gen"  data-parsley-errors-container="#dept_id_error" required data-parsley-required-message="Please select a department.">
                            <option value="">Select Department</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" 
                                        @if(isset($data->dept_id) && $data->dept_id == $department->id) selected @endif>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                        <div id="dept_id_error" class="text-danger"></div>

                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        <label for="code">Section Code</label>
                        <input type="text" name="code" class="form-control" placeholder="Section Code" 
                        data-parsley-maxlength="40"
                        data-parsley-maxlength-message="Maximum 40 characters allowed" 
                          required value="{{$data->code}}"
                            data-parsley-required-message="Section Code is required.">
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        <label for="position_title">Section Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Section Name"    required value="{{$data->name}}"
                            data-parsley-required-message="Section Name is required."
                            data-parsley-pattern="^[A-Za-z\s\/\(\)\-\_]+$"
                            data-parsley-pattern-message="Only letters and spaces are allowed."
                            data-parsley-maxlength="40"
                            data-parsley-maxlength-message="Maximum 40 characters allowed" 
                            >
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        <label for="short_name">Section Short Name</label>
                        <input type="text" name="short_name" class="form-control" placeholder="Section Short Name" required value="{{$data->short_name}}"
                            data-parsley-required-message="Section Short Name is required."
                            data-parsley-pattern="^[A-Za-z\s\/\(\)\-\_]+$"
                            data-parsley-pattern-message="Only letters and spaces are allowed."
                            data-parsley-maxlength="40"
                            data-parsley-maxlength-message="Maximum 40 characters allowed" >
                    </div>
                </div>
                <div class="col-sm-6">
                  <div class="form-group">
                      <label>Status <span class="red-mark">*</span></label>
                      <div class="form-group brdrfull">
                          <div class="row">
                              <div class="col-auto pr-0">
                                  <div class="age-title">
                                      <input type="radio" @if($data->id != '' && $data->status == "active") checked="true" @else checked="true" @endif id="status-yes" name="status" value="active">
                                      <label for="status-yes">Active</label>
                                  </div>
                              </div>
                              <div class="col-auto">
                                  <div class="age-title">
                                      <input type="radio" id="status-no" value="inactive" name="status" @if($data->id != '' && $data->status == "inactive") checked="true" @endif>
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
    window.Parsley.addValidator('validateScript', {
        validateString: function(value) {
            // Pattern to match any <script> tags, even with attributes or content
            const scriptTagPattern = /<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/gi;
            return !scriptTagPattern.test(value);  // Return true if no script tags are found, false otherwise
        },
        messages: {
            en: 'Script tags are not allowed.'
        }
    });
    $("#formStore").parsley();

    var id = $("#id").val();

    $("#formStore").on("submit", function(e) {
      e.preventDefault();

      var dataString = $(this).serialize();
      var url = $(this).attr('action');

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