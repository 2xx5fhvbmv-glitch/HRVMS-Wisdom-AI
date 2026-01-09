@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')

<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Resort Config Pages</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                </div>
            </div>
        </div>
        <div>
            <div class="row g-30">
                <div class="col-xxl-12 col-xl-12 col-lg-12">
                <!-- <div class="col-xxl-6 col-xl-6 col-lg-6"> -->
                    <div class="card">
                        <div class="card-title">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <h3>{{ $page_title }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="IndexAction" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Disciplinary Category Name</th>
                                    <th class="text-nowrap">Offenses Category Name</th>
                                    <th class="text-nowrap">Action Name</th>
                                    <th class="text-nowrap">Severity Name</th>
                                    <th class="text-nowrap">Action</th>
                                </tr>
                            </thead>

                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
{{-- Model --}}
<div class="modal fade" id="CodeOfConduct-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Code of Counduct</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="CodeOdfConductform">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <label for="disciplinary_cate" class="form-label">DISCIPLINARY CATEGORIES</label>
                            <select class="form-select select2t-none Code_of_disciplinary_cate" 
                                    id="Code_of_disciplinary_cate_1" 
                                    data-id="1" 
                                    name="Deciplinery_cat_id[]" 
                                    aria-label="Default select example"
                                    required
                                    data-parsley-required-message="Please select a disciplinary category">
                                <option value=""></option>
                                @if($DisciplinaryCategories->isNotEmpty())
                                    @foreach($DisciplinaryCategories as $item)
                                        <option value="{{base64_encode($item->id)}}">{{$item->DisciplinaryCategoryName}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
        
                        <div class="col-sm-6">
                            <label for="offenses" class="form-label">OFFENSES</label>
                            <select class="form-select select2t-none" 
                                    name="Offenses_id[]" 
                                    data-id="1" 
                                    id="offenses_1" 
                                    aria-label="Default select example"
                                    required
                                    data-parsley-required-message="Please select an offense">
                                <option value=""></option>

                            </select>
                        </div>
        
                        <div class="col-sm-6">
                            <label for="actions" class="form-label">ACTIONS</label>
                            <select class="form-select select2t-none" 
                                    id="actions_1"  
                                    data-id="1" 
                                    name="Action_id[]" 
                                    aria-label="Default select example"
                                    required
                                    data-parsley-required-message="Please select an action">
                                <option value=""></option>
                                @if($ActionStore->isNotEmpty())
                                    @foreach($ActionStore as $item)
                                        <option value="{{base64_encode($item->id)}}">{{$item->ActionName}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
        
                        <div class="col-sm-6">
                            <label for="severity" class="form-label">SEVERITY</label>
                            <select class="form-select select2t-none" name="Severity_id[]"  data-id="1"      id="severity_1" aria-label="Default select example" required data-parsley-required-message="Please select a severity level">
                                <option value=""></option>
                                @if($SeverityStore->isNotEmpty())
                                    @foreach($SeverityStore as $item)
                                        <option value="{{base64_encode($item->id)}}">{{$item->SeverityName}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <input type="hidden" name="Self_id" id="Self_id">
                        <input type="hidden" name="Offence_selected_id" id="Offence_selected_id">
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection
@section('import-scripts')

<script>

       
    $('#CodeOdfConductform').parsley();

      
        var DisciplineryCategory = $('#IndexAction').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 6,
            processing: true,
            serverSide: true,
            order:[[5, 'desc']],
            ajax: '{{ route("GrievanceAndDisciplinery.config.IndexCodeOfCounduct") }}',
            columns: [
                { data: 'DisciplinaryCategoryName', name: 'DisciplinaryCategoryName', className: 'text-nowrap' },
                { data: 'OffensesCategoryName', name: 'OffensesCategoryName', className: 'text-nowrap' },
                { data: 'ActionName', name: 'ActionName', orderable: false, searchable: false },
                { data: 'SeverityName', name: 'SeverityName', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
                {data:'created_at',visible:false,searchable:false},
            ]
        });
        $(document).on('click', '.delete-row-btn', function (e) {
            e.preventDefault();
            var $button = $(this);
     
            var main_id = $(this).attr('data-Self_id');
            Swal.fire({
                title: 'Sure want to delete?',
                text: 'This cannot be undone',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: "#DD6B55"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ route('GrievanceAndDisciplinery.config.CodeOfConductDestory', ':id') }}".replace(':id', main_id),
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success) {
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#IndexAction').DataTable().ajax.reload();

                        } else {
                            toastr.error(result.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }).fail(function(jqXHR) {
                        toastr.error(jqXHR.responseJSON?.message || "An unexpected error occurred.", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    });
                }
            });
        });

        $(document).on("click", ".edit-row-btn", function (event) {
            event.preventDefault(); 
            $("#CodeOfConduct-modal").modal('show');
            var Disciplinery_cat_id  =  $(this).attr('data-disciplinery_cat_id');
            var Offenses_id  =  $(this).attr('data-offenses_id');
            var Action_id  =  $(this).attr('data-action_id');
            var Severity_id  =  $(this).attr('data-severity_id');
            var Self_id  =  $(this).attr('data-self_id');
            $("#Offence_selected_id").val(Offenses_id);
            // Set the values for all Select2 fields
            $("#Code_of_disciplinary_cate_1").val(Disciplinery_cat_id).trigger('change');
            $("#actions_1").val(Action_id).trigger('change');
            $("#severity_1").val(Severity_id).trigger('change');
            $("#Self_id").val(Self_id);

            // Initialize Select2 for each dropdown
            $("#Code_of_disciplinary_cate_1").select2({
                placeholder: "Select Category",
                allowClear: true,
                width: '100%'        
            });
            $("#offenses_1").select2({
                placeholder: "Select Offense",
                allowClear: true,
                width: '100%'        
            });
            $("#actions_1").select2({
                placeholder: "Select Action",
                allowClear: true,
                width: '100%'        
            });
            $("#severity_1").select2({
                placeholder: "Select Severity",
                allowClear: true,
                width: '100%'        
            });
        });
        $(document).on('change', '#Code_of_disciplinary_cate_1', function() {
            var id = $(this).val();
            var Offence_selected_id = parseInt($("#Offence_selected_id").val());
            $.ajax({
                url: "{{ route('GrievanceAndDisciplinery.config.GetCategoryOffenses') }}",
                type: "POST",
                data: { "_token": "{{ csrf_token() }}", "id": id },
                success: function(response) {
                    if (response.success) {
                        var option = '<option></option>'; // Default empty option

                        $.each(response.data, function(i, v) {
                            var encodedId = v.id; 
                            var selected = (encodedId === Offence_selected_id) ? 'selected' : '';
                            option += '<option value="' + encodedId + '" ' + selected + '>' + v.OffensesName + '</option>';
                        });

                        $("#offenses_1").html(option);

                        // Re-enable the change event listener after the dropdown is updated
                        $("#offenses_1").on('change', function() {
                            // Handle the change event for #offenses_1
                            var selectedValue = $(this).val();
                            console.log('Offense selected:', selectedValue);
                        });

                        // Optionally, trigger change event if necessary after populating options
                        $("#offenses_1").trigger('change');
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    var errs = '';
                    $.each(errors.errors, function(key, error) {
                        errs += error + '<br>';
                    });
                    toastr.error(errs, {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

        $('#CodeOdfConductform').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                if (form.parsley().isValid()) {
                    let formData = new FormData(this);
                    
                    $.ajax({
                        url: "{{ route('GrievanceAndDisciplinery.config.CodeOfCounductUpdate') }}", 
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            console.log(response.success);
                            if (response.success) {
                                // Show success message
                                toastr.success(response.message,"Success",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                                $('#IndexAction').DataTable().ajax.reload();  // Correct way
                                $("#CodeOfConduct-modal").modal('hide');
                            } else {
                                  toastr.error(response.message, "Error",
                                    {
                                        positionClass: 'toast-bottom-right'
                                    });
                            }
                        },
                        error: function(response) 
                        {
                            var errors = response.responseJSON;

                            if (errors.error) { 
                                // If it's a duplicate entry error
                                toastr.error(errors.error, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            } else {
                                // If it's a validation error
                                var errs = '';
                                $.each(errors.errors, function(key, error) {
                                    errs += error + '<br>';
                                });

                                toastr.error(errs, "Validation Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        }

                    });
                }
            });

</script>
@endsection
