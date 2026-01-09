@extends('admin.layouts.app')

@if($isNew == 1)
  @section('page_tab_title' ,"Create Division")
@else
  @section('page_tab_title' ,"Edit Division")
@endif

@section('content')
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card ">
        <div class="card-header">
          @if( $isNew == 1 )
            <h1>Create Division</h1>
          @else
            <h1>Edit Division</h1>
          @endif
        </div>
        <form method=" post" name="formStore" id="formStore" enctype="multipart/form-data" @if(empty($data->id)) action="{{ route('admin.divisions.store') }}" @else action="{{ route('admin.divisions.update', $data->id) }}" @endif >
          @csrf

          @if($data->id != '')
            <input type="hidden" name="id" id="id" value="{{$data->id}}">
          @endif

          <div class="card-body">
            <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  <label>Division Code <span class="red-mark">*</span></label>
                  <input 
                    data-parsley-validate-script 
                    data-parsley-validate-script-message="Script tags are not allowed." 
                    data-parsley-errors-container="#div-division_code" 
                    data-parsley-maxlength="30"
                    data-parsley-maxlength-message="Maximum 30 characters allowed" 
                    required 
                    type="text" 
                    name="code" 
                    id="code" 
                    class="form-control" 
                    placeholder="Division Code" 
                    value="{{ $data->code }}" 
                    title="Only letters and spaces are allowed.">
                  <div id="div-division_code"></div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label>Division Name <span class="red-mark">*</span></label>
                  <input 
                    data-parsley-validate-script 
                    data-parsley-validate-script-message="Script tags are not allowed." 
                    data-parsley-errors-container="#div-division_name" 
                    data-parsley-pattern="^[A-Za-z\s\/\(\)\-\_]+$"
                    data-parsley-maxlength="30"
                    data-parsley-maxlength-message="Maximum 30 characters allowed" 
                    required 
                    type="text" 
                    name="name" 
                    id="name" 
                    class="form-control" 
                    placeholder="Division Name" 
                    value="{{ $data->name }}" 
                    title="Only letters and spaces are allowed.">
                  <div id="div-division_name"></div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label>Division Short Name <span class="red-mark">*</span></label>
                  <input 
                    data-parsley-validate-script 
                    data-parsley-validate-script-message="Script tags are not allowed." 
                    data-parsley-errors-container="#div-division_short_name" 
                    data-parsley-pattern="^[A-Za-z\s\/\(\)\-\_]+$"
                    required 
                    data-parsley-maxlength="30"
                    data-parsley-maxlength-message="Maximum 30 characters allowed" 
                    type="text" 
                    name="short_name" 
                    id="short_name" 
                    class="form-control" 
                    placeholder="Division Short Name" 
                    value="{{ $data->short_name }}" 
                    title="Only letters and spaces are allowed.">
                  <div id="div-division_short_name"></div>
                </div>
              </div>
              <div class="col-sm-6">
                  <div class="form-group">
                      <label>Status <span class="red-mark">*</span></label>
                      <div class="form-group brdrfull">
                          <div class="row">
                              <div class="col-auto pr-0">
                                  <div class="age-title">
                                      <input type="radio" id="status-yes" value="active" name="status" @if($data->id != '' && $data->status == "active") checked="true" @else checked="true" @endif   >
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

    var url = "{{route('admin.divisions.list')}}";

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