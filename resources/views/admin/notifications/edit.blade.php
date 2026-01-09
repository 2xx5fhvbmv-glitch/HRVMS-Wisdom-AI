@extends('admin.layouts.app')

@if($isNew == 1)
  @section('page_tab_title' ,"Create Notification")
@else
  @section('page_tab_title' ,"Edit Notification")
@endif

@section('content')
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          @if( $isNew == 1 )
            <h1>Create Notification</h1>
          @else
            <h1>Edit Notification</h1>
          @endif
        </div>
        <form method="post" name="formStore" id="formStore" enctype="multipart/form-data" @if(empty($data->id)) action="{{ route('admin.notifications.store') }}" @else action="{{ route('admin.notifications.update', $data->id) }}" @endif >
          @csrf

          @if($data->id != '')
            <input type="hidden" name="id" id="id" value="{{$data->id}}">
          @endif

          <div class="card-body">
              <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                    <label> Name <span class="red-mark">*</span></label>
                    <input 
                        data-parsley-validate-script 
                        data-parsley-validate-script-message="Script tags are not allowed." 
                        data-parsley-errors-container="#div-name" 
                        data-parsley-pattern="^[A-Za-z\s\/\(\)\-\_]+$"
                        required 
                                                data-parsley-maxlength="50"
                    data-parsley-maxlength-message="Maximum 50 characters allowed"
                        type="text" 
                        name="name" 
                        id="name" 
                        class="form-control" 
                        placeholder="Name" 
                        value="{{ $data->name }}" 
                        title="Only letters and spaces are allowed.">
                    <div id="div-name"></div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                    <label> Content <span class="red-mark">*</span></label>
                    <textarea 
                        data-parsley-validate-script 
                        data-parsley-validate-script-message="Script tags are not allowed." 
                        data-parsley-errors-container="#div-content" 
                        data-parsley-pattern="^[A-Za-z\s\/\(\)\-\_]+$"
                        required 
                        type="text" 
                        name="content" 
                        id="content" 
                        class="form-control" 
                        placeholder="Content" 
                        title="Only letters and spaces are allowed.">{{ $data->content }}</textarea>
                    <div id="div-content"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group brdrfull">
                        <label>Start Date <span class="red-mark">*</span></label>
                        <input data-parsley-errors-container="#div-start_date" type="text" name="start_date" id="start_date" class="form-control" placeholder="Start Date" value="{{$data->start_date}}" required>
                        <div id="div-start_date"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group brdrfull">
                        <label>End Date <span class="red-mark">*</span></label>
                        <input data-parsley-errors-container="#div-end_date" type="text" name="end_date" id="end_date" class="form-control" placeholder="End Date" value="{{$data->end_date}}" required>
                        <div id="div-end_date"></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group brdrfull">
                        <label>Notice Color<span class="red-mark">*</span></label>
                        <input data-parsley-errors-container="#div-notice_color" type="color" id="notice_color" name="notice_color" value="{{$data->notice_color}}" required class="form-control">
                        <div id="div-notice_color"></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group brdrfull">
                        <label>Font Color<span class="red-mark">*</span></label>
                        <input data-parsley-errors-container="#div-font_color" type="color" id="font_color" name="font_color" value="{{$data->font_color}}" required class="form-control">
                        <div id="div-font_color"></div>
                    </div>
                </div>
                <!-- <div class="col-sm-3">
                    <div class="form-group">
                        <label>Sticky <span class="red-mark">*</span></label>
                        <div class="form-group brdrfull">
                            <div class="row">
                                <div class="col-auto pr-0">
                                    <div class="age-title">
                                        <input type="radio" @if($data->id != '' && $data->sticky == "yes") checked="true" @else checked="true" @endif id="status-yes" name="sticky" value="yes">
                                        <label for="status-yes">Yes</label>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="age-title">
                                        <input type="radio" id="status-no" value="no" name="sticky" @if($data->id != '' && $data->sticky == "no") checked="true" @endif>
                                        <label for="status-no">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="div-status"></div>
                    </div>
                </div> -->
                
                <div class="col-sm-3">
                    <div class="form-group">
                        <label>Resorts <span class="red-mark">*</span></label>
                        <select name="resorts[]" id="resorts" class="select-2-gen form-control" data-parsley-errors-container="#div-resorts" required multiple>
                            <option disabled>Select a value</option>
                            <option value="all">All Resorts</option> <!-- Add this option for 'All Resorts' -->
                            @foreach($resorts as $resort)
                                <option value="{{ $resort->id }}" @if(in_array($resort->id, $data->resorts->pluck('id')->toArray() ?? [])) selected @endif>{{ $resort->resort_name }}</option>
                            @endforeach
                        </select>
                        <div id="div-resorts"></div>
                    </div>
                </div>
                <div class="col-sm-3">
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
    $("#start_date, #end_date").datepicker({
        format: dt_format,
        todayHighlight: true
    }).on('changeDate', function() {
        // Hide the datepicker after date selection
        $(this).datepicker('hide');

        // Manually trigger Parsley validation for the field
        $(this).parsley().validate();
    });

    $('#resorts').on('select2:select', function(e) {
    var selectedValues = $('#resorts').val();
    var allSelected = selectedValues.includes('all');

    // If 'All Resorts' is selected
    if (allSelected) {
        // Loop through each option in the Select2 dropdown
        $('#resorts').find('option').each(function() {
            // Skip 'all' and the disabled 'Select a value' option
            if ($(this).val() !== 'all' && $(this).val() !== '' && !$(this).is(':disabled')) {
                $(this).prop('selected', true); // Select valid resort options
            }
        });

        // Update the Select2 value excluding 'all'
        var validResortValues = $('#resorts').find('option').map(function() {
            // Return the value if it's not 'all' and not disabled
            if ($(this).val() !== 'all' && !$(this).is(':disabled')) {
                return $(this).val();
            }
        }).get();

        $('#resorts').val(validResortValues).trigger('change'); // Set the valid options and trigger update
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