@extends('admin.layouts.app')

@if($isNew == 1)
  @section('page_tab_title' ,"Create Sections")
@else
  @section('page_tab_title' ,"Edit Sections")
@endif

@section('content')
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card ">
        <div class="card-header">
          @if( $isNew == 1 )
            <h1>Create Sections</h1>
          @else
            <h1>Edit Sections</h1>
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
                          <select name="dept_id" id="dept_id" class="form-control select-2-gen" data-parsley-errors-container="#dept_id_error" required data-parsley-required-message="Please select a department.">
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
              </div>

              <div id="sections">
                  <div class="row section">
                      <div class="col-sm-6">
                          <div class="form-group">
                              <label for="section_name">Section Name</label>
                              <input type="text" name="sections[0][name]" class="form-control" placeholder="Section Name" required
                                    data-parsley-required-message="Section Name is required."
                                    data-parsley-pattern="^[A-Za-z\s\/\(\)\-\_]+$"
                                    data-parsley-pattern-message="Only letters and spaces are allowed."
                                    data-parsley-maxlength="40"
                                    data-parsley-maxlength-message="Maximum 40 characters allowed" 
                                    >
                          </div>
                      </div>
                      <div class="col-sm-2">
                          <button type="button" class="btn btn-danger removePosition mt-4">Remove</button>
                      </div>
                  </div>
              </div>

              <div class="row">
                  <div class="col-sm-12">
                      <button type="button" id="addSection" class="btn btn-secondary mt-3">Add Another Section</button>
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
    $("#formStore").parsley();

    var id = $("#id").val();

    var url = "{{route('admin.sections.list')}}";

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
<script>
    let sectionIndex = 1;

    // Add new position
    document.getElementById('addSection').addEventListener('click', function() {
        const sectionDiv = document.createElement('div');
        sectionDiv.classList.add('row', 'section');

        sectionDiv.innerHTML = `
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="section_name">Section Name</label>
                    <input type="text" name="sections[${sectionIndex}][name]" class="form-control" placeholder="Section Name" required
                           data-parsley-required-message="Section name is required."
                           data-parsley-pattern="^[a-zA-Z\\s]*$"
                           data-parsley-pattern-message="Only letters and spaces are allowed.">
                </div>
            </div>
            <div class="col-sm-2">
                <button type="button" class="btn btn-danger removeSection mt-4">Remove</button>
            </div>
        `;

        document.getElementById('sections').appendChild(sectionDiv);
        sectionIndex++;
    });

    // Remove position
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('removeSection')) {
            event.target.closest('.section').remove();
        }
    });
</script>
@endsection