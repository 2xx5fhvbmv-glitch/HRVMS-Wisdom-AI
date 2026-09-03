@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #disciplinary-code-of-conduct-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #disciplinary-code-of-conduct-hero { padding-bottom: 0; }
    }
</style>

<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="disciplinary-code-of-conduct-hero">
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
                <h5 class="modal-title" id="staticBackdropLabel">Code of Conduct</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="CodeOdfConductform">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <label for="disciplinary_cate" class="form-label">DISCIPLINARY CATEGORIES</label>
                            <select class="form-select dd-native-select Code_of_disciplinary_cate"
                                    id="Code_of_disciplinary_cate_1"
                                    data-id="1"
                                    name="Deciplinery_cat_id[]"
                                    aria-label="Disciplinary category"
                                    required
                                    data-parsley-required-message="Please select a disciplinary category">
                                <option value=""></option>
                                @if($DisciplinaryCategories->isNotEmpty())
                                    @foreach($DisciplinaryCategories as $item)
                                        <option value="{{base64_encode($item->id)}}">{{$item->DisciplinaryCategoryName}}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="dd" data-target="#Code_of_disciplinary_cate_1">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Category</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Disciplinary category">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a category…"></div>
                                    <div class="dd-scroll">
                                        @foreach($DisciplinaryCategories as $item)
                                            <div class="dd-item" role="option" data-value="{{ base64_encode($item->id) }}"><span class="dd-nm">{{ $item->DisciplinaryCategoryName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <label for="offenses" class="form-label">OFFENSES</label>
                            <select class="form-select dd-native-select"
                                    name="Offenses_id[]"
                                    data-id="1"
                                    id="offenses_1"
                                    aria-label="Offense"
                                    required
                                    data-parsley-required-message="Please select an offense">
                                <option value=""></option>

                            </select>
                            <div class="dd" data-target="#offenses_1">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Offense</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Offense">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an offense…"></div>
                                    <div class="dd-scroll"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <label for="actions" class="form-label">ACTIONS</label>
                            <select class="form-select dd-native-select"
                                    id="actions_1"
                                    data-id="1"
                                    name="Action_id[]"
                                    aria-label="Action"
                                    required
                                    data-parsley-required-message="Please select an action">
                                <option value=""></option>
                                @if($ActionStore->isNotEmpty())
                                    @foreach($ActionStore as $item)
                                        <option value="{{base64_encode($item->id)}}">{{$item->ActionName}}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="dd" data-target="#actions_1">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Action</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Action">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an action…"></div>
                                    <div class="dd-scroll">
                                        @foreach($ActionStore as $item)
                                            <div class="dd-item" role="option" data-value="{{ base64_encode($item->id) }}"><span class="dd-nm">{{ $item->ActionName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <label for="severity" class="form-label">SEVERITY</label>
                            <select class="form-select dd-native-select" name="Severity_id[]" data-id="1" id="severity_1" aria-label="Severity" required data-parsley-required-message="Please select a severity level">
                                <option value=""></option>
                                @if($SeverityStore->isNotEmpty())
                                    @foreach($SeverityStore as $item)
                                        <option value="{{base64_encode($item->id)}}">{{$item->SeverityName}}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="dd" data-target="#severity_1">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Severity</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Severity">
                                    <div class="dd-scroll">
                                        @foreach($SeverityStore as $item)
                                            <div class="dd-item" role="option" data-value="{{ base64_encode($item->id) }}"><span class="dd-nm">{{ $item->SeverityName }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="Self_id" id="Self_id">
                        <input type="hidden" name="Offence_selected_id" id="Offence_selected_id">
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn eb-btn-neutral" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn eb-btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@include('resorts._emotional_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
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
            wisdomConfirm({
                role: 'destructive',
                title: 'Sure want to delete?',
                text: 'This cannot be undone',
                confirmText: 'Yes',
                cancelText: 'No'
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
            // Set the values for all fields, then resync each dropdown's
            // visual trigger/list to match (the AJAX cascade below rebuilds
            // #offenses_1's own <option>s and syncs it once they're in).
            $("#Code_of_disciplinary_cate_1").val(Disciplinery_cat_id).trigger('change');
            $("#actions_1").val(Action_id).trigger('change');
            $("#severity_1").val(Severity_id).trigger('change');
            $("#Self_id").val(Self_id);
            wisdomDD.sync('#Code_of_disciplinary_cate_1');
            wisdomDD.sync('#actions_1');
            wisdomDD.sync('#severity_1');
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
                        wisdomDD.rebuild('#offenses_1');

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
