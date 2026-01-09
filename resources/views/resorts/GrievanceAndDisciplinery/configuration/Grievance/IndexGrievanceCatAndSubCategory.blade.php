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

                        <table id="IndexGrievanceCatAndSuBcat" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Category Name</th>
                                    <th>Sub Category Name</th>
                                    <th>Priority Level</th>
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
@endsection

@section('import-css')
@endsection

@section('import-scripts')

<script>

            $(".Grievance_Cat_idMain").select2({
                placeholder: "Select Greivance Category",
                allowClear: true,
                width: '100%'        
            });
            $(".GrievanceSubcategories").select2({
                placeholder: `Select Grieveance Sub Category `,
                allowClear: true,
                width: '100%'        
            });
            $(".priority_level").select2({
                placeholder: `Select Priority Level `,
                allowClear: true,
                width: '100%'        
            });
        var DisciplineryCategory = $('#IndexGrievanceCatAndSuBcat').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 6,
            processing: true,
            serverSide: true,
            order:[[4, 'desc']],
            ajax: '{{ route("GrievanceAndDisciplinery.config.IndexGrievanceCatAndSubCategory") }}',
            columns: [
                { data: 'Grievance_Cat_id', name: 'Grievance_Cat_id', className: 'text-nowrap' },
                { data: 'Gri_Sub_cat_id', name: 'Gri_Sub_cat_id', className: 'text-nowrap' },
                { data: 'Priority_Level', name: 'Priority_Level', className: 'text-nowrap' },
                { data: 'Action', name: 'Action', orderable: false, searchable: false },
                {data:'created_at',visible:false,searchable:false},
            ]
        });





        $(document).on('click', '.delete-row-btn', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest("tr");
            var main_id = $(this).data('cat-id');

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
                        url: "{{ route('GrievanceAndDisciplinery.config.GrievanceCatAndSubCategoryDestory', ':id') }}".replace(':id', main_id),
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success) {
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#IndexGrievanceCatAndSuBcat').DataTable().ajax.reload();

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

        $(document).on("click", "#IndexGrievanceCatAndSuBcat .edit-row-btn", function (event) {
            event.preventDefault(); // Prevent default action

            // Find the parent row
            var $row = $(this).closest("tr");

            var Main_id = $(this).attr('data-cat-id');
            var Grievance_Cat_id = $(this).attr('data-Grievance_Cat_id');
            var Gri_Sub_cat_id = $(this).attr('data-Gri_Sub_cat_id');
            var Priority_Level = $(this).attr('data-Priority_Level');
           
            var editRowHtml = `
                <td class="py-1">
                    <div class="form-group">
                        <select class="form-select select2t-none Grievance_Cat_idMain"
                            data-parsley-required="true"
                            data-parsley-error-message="Please select a grievance category Grievance_Cat_idMain"
                            name="Grievance_Cat_id" id="Grievance_Cat_idMain_1" data-id="1" aria-label="Default select example">
                            <option value=""></option>
                            @if($GrievanceCategory->isNotEmpty())
                                @foreach($GrievanceCategory as $item)
                                    <option value="{{$item->id}}" ${Grievance_Cat_id == '{{$item->id}}' ? 'selected' : ''}>
                                        {{$item->Category_Name}}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </td>
                <td class="py-1">
                    <div class="form-group">
                        <select class="form-select select2t-none GrievanceSubcategories" aria-label="Select Subcategory" 
                            required
                            data-parsley-required="true"
                            data-parsley-error-message="Please select a subcategory"
                            name="Gri_Sub_cat_id" id="GrievanceSubcategories_1" data-id="1" aria-label="Default select example">
                            <option value=""></option>
                           
                        </select>
                    </div>
                </td>
                <td>
                    <div class="form-group">
                        <select class="form-select select2t-none priority_level" name="priority_level[]" id="priority_level_1" data-id="1" aria-label="Select Priority Level"
                            required
                            data-parsley-required="true"
                            data-parsley-error-message="Please select a priority level">
                            <option value="High" ${Priority_Level == 'High' ? 'selected' : ''}>High</option>
                            <option value="Medium" ${Priority_Level == 'Medium' ? 'selected' : ''}>Medium</option>
                            <option value="Low" ${Priority_Level == 'Low' ? 'selected' : ''}>Low</option>
                        </select>
                    </div>    
                </td>
                <td class="py-1">
                    <a href="javascript:void(0)" class="btn btn-theme update-row-btn_cat" data-cat-id="${Main_id}">Submit</a>
                </td>
            `;
            $row.html(editRowHtml);
            
                $.ajax({
                    url: "{{ route('GrievanceAndDisciplinery.config.GrievanceCategoryWiseSubCategoryData') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        Grievance_Cat_idMain: Grievance_Cat_id,
                    },
                    success: function (response) {
                        var option = '<option value="">Select Subcategory</option>';
                        $.each(response.data.GrievanceSubcategory, function (i, v) {
                            option += `<option value="${v.id}" ${Gri_Sub_cat_id == v.id ? 'selected' : ''}>${v.Sub_Category_Name}</option>`;
                        });
                        $row.find('select[name="Gri_Sub_cat_id"]').html(option);                   
                    },
                    error: function(response) {
                        console.error("Error fetching subcategories", response);
                    }
                });
                
            $("#Grievance_Cat_idMain_1").select2({
                placeholder: "Select Greivance Category",
                allowClear: true,
                width: '100%'        
            });
            $("#GrievanceSubcategories_1").select2({
                placeholder: `Select Grieveance Sub Category `,
                allowClear: true,
                width: '100%'        
            });
            $("#priority_level_1").select2({
                placeholder: `Select Priority Level `,
                allowClear: true,
                width: '100%'        
            });
        });

        $(document).on("click", "#IndexGrievanceCatAndSuBcat .update-row-btn_cat", function (event) {
            event.preventDefault(); 
            var $row = $(this).closest("tr");
            var Main_id = $(this).attr('data-cat-id');
            var Grievance_Cat_id = $row.find("select").eq(0).val();
            var Gri_Sub_cat_id = $row.find("select").eq(1).val();
            var Priority_Level = $row.find("select").eq(2).val();
            $.ajax({
                url: "{{ route('GrievanceAndDisciplinery.config.GrievanceCatAndSubCategoryinlineUpdate', '') }}/" + Main_id,
                type: "PUT",
                data: {
                    Main_id:Main_id,
                    Grievance_Cat_id:Grievance_Cat_id,
                    Gri_Sub_cat_id:Gri_Sub_cat_id,
                    Priority_Level:Priority_Level,
                },
                success: function(response) {
                    if(response.success == true)
                    { 

                        $('#IndexGrievanceCatAndSuBcat').DataTable().ajax.reload();
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr) {

                        let errorMessage = '';

                        if (xhr.responseJSON && xhr.responseJSON.errors) {

                            $.each(xhr.responseJSON.errors, function(key, error) {

                            errorMessage += error + "<br>";
                            })
                        }
                        else
                        {
                            errorMessage = "An error occurred while Create or Update."; // Default error message
                        }

                        toastr.error(errorMessage, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
            });
        });


        $(document).on("change", "#IndexGrievanceCatAndSuBcat .Grievance_Cat_idMain", function () {
            var id = $(this).val();
            var $row = $(this).closest("tr");

            $.ajax({
                    url: "{{ route('GrievanceAndDisciplinery.config.GrievanceCategoryWiseSubCategoryData') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        Grievance_Cat_idMain: id,
                    },
                    success: function (response) {
                        var option = '<option value="">Select Subcategory</option>';
                        $.each(response.data.GrievanceSubcategory, function (i, v) {
                            option += `<option value="${v.id}" >${v.Sub_Category_Name}</option>`;
                        });
                        $row.find('select[name="Gri_Sub_cat_id"]').html(option);                   
                    },
                    error: function(response) {
                        console.error("Error fetching subcategories", response);
                    }
                });

                $row.find('select[name="Gri_Sub_cat_id"]').select2({
                    placeholder: `Select Grieveance Sub Category `,
                    allowClear: true,
                    width: '100%'        
                });
        });

</script>
@endsection
