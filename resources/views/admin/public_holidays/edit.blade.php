@extends('admin.layouts.app')

@if($isNew == 1)
  @section('page_tab_title' ,"Create Public Holiday")
@else
  @section('page_tab_title' ,"Edit Public Holiday")
@endif

@section('content')
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card ">
        <div class="card-header">
          @if( $isNew == 1 )
            <h1>Create Public Holiday</h1>
          @else
            <h1>Edit Public Holiday</h1>
          @endif
        </div>
        <form method=" post" name="formStore" id="formStore" enctype="multipart/form-data" @if(empty($data->id)) action="{{ route('admin.public_holidays.store') }}" @else action="{{ route('admin.public_holidays.update', $data->id) }}" @endif >
          @csrf

          @if($data->id != '')
            <input type="hidden" name="id" id="id" value="{{$data->id}}">
          @endif

          <div class="card-body">
            <div class="row">
             
              <div class="col-sm-6">
                <div class="form-group">
                  <label>Holiday Name <span class="red-mark">*</span></label>
                  <input 
                    data-parsley-validate-script 
                    data-parsley-validate-script-message="Script tags are not allowed." 
                    data-parsley-errors-container="#div-name" 
                    required 
                    data-parsley-maxlength="30"
                    data-parsley-maxlength-message="Maximum 30 characters allowed"
                    type="text" 
                    name="name" 
                    id="name" 
                    class="form-control" 
                    placeholder="Holiday Name" 
                    value="{{ $data->name }}" 
                    title="Only letters and spaces are allowed.">
                  <div id="div-name"></div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                    <label>Holiday Date <span class="red-mark">*</span></label>
                    <input data-parsley-errors-container="#div-holiday_date" data-parsley-required-message="Holiday Date is required." type="text" name="holiday_date" id="holiday_date" class="form-control" placeholder="Holiday Date" value="{{$data->holiday_date}}" required>
                    <div id="div-holiday_date"></div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control" data-parsley-validate-script 
                    data-parsley-validate-script-message="Script tags are not allowed." 
                    data-parsley-maxlength="255"
                    data-parsley-maxlength-message="Maximum 255 characters allowed"
                    data-parsley-errors-container="#div-description" >{{ isset($data) ? $data->description : old('description') }}</textarea>
                    <div id="div-description"></div>
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
    $("#holiday_date").datepicker({
        format: dt_format,
        todayHighlight: true
    }).on('changeDate', function() {
        // Hide the datepicker after date selection
        $(this).datepicker('hide');

        // Manually trigger Parsley validation for the field
        $(this).parsley().validate();
    });
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