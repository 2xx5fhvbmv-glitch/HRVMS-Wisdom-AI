@extends('admin.layouts.app')

@if($isNew == 1)
  @section('page_tab_title' ,"Create EWT Brackets")
@else
  @section('page_tab_title' ,"Edit EWT Brackets")
@endif

@section('content')
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card ">
        <div class="card-header">
          @if( $isNew == 1 )
            <h1>Create EWT Brackets</h1>
          @else
            <h1>Edit EWT Brackets</h1>
          @endif
        </div>
        <form method="post" name="formStore" id="formStore" enctype="multipart/form-data" 
  @if(empty($data->id)) action="{{ route('admin.ewt_brackets.store') }}" 
  @else action="{{ route('admin.ewt_brackets.update', $data->id) }}" @endif>
  
  @csrf

  @if(!empty($data->id))
    <input type="hidden" name="id" id="id" value="{{$data->id}}">
  @endif

  <div class="card-body">
    <div class="row">
      <!-- Min Salary -->
      <div class="col-sm-6">
        <div class="form-group">
          <label>Min Salary <span class="red-mark">*</span></label>
          <input 
            data-parsley-type="number" 
            data-parsley-min="0" 
            data-parsley-required="true"
            data-parsley-errors-container="#div-min_salary" 
            required 
            type="number" 
            step="0.01" 
            name="min_salary" 
            id="min_salary" 
            class="form-control" 
            placeholder="Min Salary" 
            value="{{ old('min_salary', $data->min_salary ?? '') }}">
          <div id="div-min_salary"></div>
        </div>
      </div>

      <!-- Max Salary -->
      <div class="col-sm-6">
        <div class="form-group">
          <label>Max Salary <span class="red-mark">*</span></label>
          <input 
            data-parsley-type="number" 
            data-parsley-min="0"
            data-parsley-errors-container="#div-max_salary"  
            type="number" 
            step="0.01" 
            name="max_salary" 
            id="max_salary" 
            class="form-control" 
            placeholder="Max Salary" 
            value="{{ old('max_salary', $data->max_salary ?? '') }}">
          <div id="div-max_salary"></div>
        </div>
      </div>

      <!-- Tax Rate -->
      <div class="col-sm-6">
        <div class="form-group">
          <label>Tax Rate (%) <span class="red-mark">*</span></label>
          <input 
            data-parsley-type="number" 
            data-parsley-min="0" 
            data-parsley-max="100"
            data-parsley-required="true"
            data-parsley-errors-container="#div-tax_rate" 
            required 
            type="number" 
            step="0.01" 
            name="tax_rate" 
            id="tax_rate" 
            class="form-control" 
            placeholder="Tax Rate (%)" 
            value="{{ old('tax_rate', $data->tax_rate ?? '') }}">
          <div id="div-tax_rate"></div>
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