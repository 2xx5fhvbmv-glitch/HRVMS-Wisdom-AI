@extends('admin.layouts.app')

@if($isNew == 1)
  @section('page_tab_title' ,"Create Positions")
@else
  @section('page_tab_title' ,"Edit Positions")
@endif

@section('content')
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card ">
        <div class="card-header">
          @if( $isNew == 1 )
            <h1>Create Positions</h1>
          @else
            <h1>Edit Positions</h1>
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
                          <label for="department">Select Department</label>
                          <select name="dept_id" id="dept_id" class="form-control select-2-gen" required data-parsley-required-message="Please select a department.">
                              <option value="">Select Department</option>
                              @foreach($departments as $department)
                                  <option value="{{ $department->id }}" 
                                          @if(isset($data->dept_id) && $data->dept_id == $department->id) selected @endif>
                                      {{ $department->name }}
                                  </option>
                              @endforeach
                          </select>
                      </div>
                  </div>
              </div>

              <div id="positions">
                  <div class="row position">
                      <div class="col-sm-6">
                          <div class="form-group">
                              <label for="position_name">Position Name</label>
                              <input type="text" name="positions[0][name]" class="form-control" placeholder="Position Name" required
                              data-parsley-required-message="Position name is required."
                              data-parsley-pattern="^[A-Za-z\s\/\(\)\-\_]+$"
                              data-parsley-pattern-message="Only letters, spaces, hyphens, and slashes are allowed.">
                          </div>
                      </div>
                      <div class="col-sm-2">
                          <button type="button" class="btn btn-danger removePosition mt-4">Remove</button>
                      </div>
                  </div>
              </div>

              <div class="row">
                  <div class="col-sm-12">
                      <button type="button" id="addPosition" class="btn btn-secondary mt-3">Add Another Position</button>
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
  let positionIndex = 1;
document.getElementById('addPosition').addEventListener('click', function() {
  
    const positionDiv = document.createElement('div');
    positionDiv.classList.add('row', 'position');

    positionDiv.innerHTML = `
        <div class="col-sm-6">
            <div class="form-group">
                <label for="position_name">Position Name</label>
                <input type="text" name="positions[${positionIndex}][name]" class="form-control" placeholder="Position Name" required
                       data-parsley-required-message="Position name is required."
                       data-parsley-pattern="^[A-Za-z\\s\\/\\(\\)\\-]+$"
                       data-parsley-pattern-message="Only letters, spaces, hyphens, and slashes are allowed.">
            </div>
        </div>
        <div class="col-sm-2">
            <button type="button" class="btn btn-danger removePosition mt-4">Remove</button>
        </div>
    `;

    document.getElementById('positions').appendChild(positionDiv);
    positionIndex++;

    // Reinitialize Parsley for the new form elements
    $(positionDiv).find('input').parsley();  // Initialize Parsley for the new input
});

document.addEventListener('click', function(event) {
    if (event.target.classList.contains('removePosition')) {
        event.target.closest('.position').remove();
    }
});

// Form Submission with Explicit Validation Check
$("#formStore").on("submit", function(e) {
    e.preventDefault();

    var form = $(this);

    // Validate the entire form
    if (form.parsley().validate()) {
        var url = form.attr('action');

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

</script>



@endsection