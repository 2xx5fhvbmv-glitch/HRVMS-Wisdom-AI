@extends('admin.layouts.app')

@if($isNew == 1)
  @section('page_tab_title' ,"Create Position")
@else
  @section('page_tab_title' ,"Edit Position")
@endif

@section('content')
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card ">
      <div class="card-header">
          @if( $isNew == 1 )
            <h1>Create Position</h1>
          @else
            <h1>Edit Position</h1>
          @endif
        </div>
        
        <form method=" post" name="formStore" id="formStore" enctype="multipart/form-data" @if(empty($data->id)) action="{{ route('admin.positions.store') }}" @else action="{{ route('admin.positions.update', $data->id) }}" @endif >

          @csrf

          @if($data->id != '')
            <input type="hidden" name="id" id="id" value="{{$data->id}}">
          @endif

          <div class="card-body">
              <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="department">Select Department <span class="red-mark">*</span></label>
                            <select name="dept_id" id="dept_id" class="form-control select-2-gen"  
                            required data-parsley-required-message="Please select a department."
                            
                            data-parsley-errors-container="#dept_id_error"  >
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
                            <label for="position_title">Position Code <span class="red-mark">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="Position Code" required value="{{$data->code}}"
                                data-parsley-required-message="Position code is required."
                                data-parsley-maxlength="40"
                                data-parsley-maxlength-message="Maximum 40 characters allowed" >
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="position_title">Position Title <span class="red-mark">*</span></label>
                            <input type="text" name="position_title" class="form-control" placeholder="Position Title" required value="{{$data->position_title}}"
                                data-parsley-required-message="Position title is required."
                                data-parsley-pattern="^[A-Za-z\s\/\(\)\-\_,']+$"
                                data-parsley-maxlength="40"
                                data-parsley-maxlength-message="Maximum 40 characters allowed" 
                                data-parsley-pattern-message="Only letters and spaces are allowed.">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="short_title">Short Title <span class="red-mark">*</span></label>
                            <input type="text" name="short_title" class="form-control" placeholder="Short Title" required value="{{$data->short_title}}"
                                data-parsley-required-message="Short title is required."
                                data-parsley-pattern="^[A-Za-z\s\/\(\)\-\_,']+$"
                                data-parsley-maxlength="40"
                                data-parsley-maxlength-message="Maximum 40 characters allowed" 
                                data-parsley-pattern-message="Only letters and spaces are allowed.">
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
                    <div class="col-sm-6">
                      <div class="form-group">
                          <label>Reserved For Local <span class="red-mark">*</span></label>
                          <div class="form-group brdrfull">
                              <div class="row">
                                  <div class="col-auto pr-0">
                                      <div class="age-title">
                                          <input type="radio" @if($data->id != '' && $data->is_reserved == "Yes") checked="true" @else checked="true" @endif id="is_reserved-yes" name="is_reserved" value="Yes">
                                          <label for="is_reserved-yes">Yes</label>
                                      </div>
                                  </div>
                                  <div class="col-auto">
                                      <div class="age-title">
                                          <input type="radio" id="is_reserved-no" value="No" name="is_reserved" @if($data->id != '' && $data->is_reserved == "No") checked="true" @endif>
                                          <label for="is_reserved-no">No</label>
                                      </div>
                                  </div>
                              </div>
                          </div>
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

<!-- Upload image modal -->
<div class="customscroll showpop">
  <div class="modal fade" id="uploadImgModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 60%;">
      <div class="modal-content">
        <div class="popup-marg">
          <div class="modal-header">
            <h5 class="modal-title text-center">CROP & UPLOAD IMAGE</h5>
            <button type="button" class="btn close-modal-btn" data-dismiss="modal"
            aria-label="Close">X</button>
          </div>
          <div class="modal-body p-0">
            <div class="row">
              <div class="col-md-12 text-center mt-20">
                <div id="profile_preview"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="justify-content: center;">
          <button class="crop-picture mt-0 btn btn-info">Crop & Save</button>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Upload image modal -->
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

    var url = "{{route('admin.resorts.list')}}";

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