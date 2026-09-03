@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #grievance-subcategory-index-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #grievance-subcategory-index-hero { padding-bottom: 0; }
    }
</style>

<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="grievance-subcategory-index-hero">
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

                        <table id="IndexGrievanceSubCategory" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap"> Category Name  </th>
                                    <th class="text-nowrap"> Sub Category Name </th>
                                    <th class="text-nowrap"> Action </th>
                                </tr>
                            </thead>

                        </table>
                    </div>
                </div>

            </div>
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
        var DisciplineryCategory = $('#IndexGrievanceSubCategory').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 6,
            processing: true,
            serverSide: true,
            order:[[3, 'desc']],
            ajax: '{{ route("GrievanceAndDisciplinery.config.IndexGrievanceSubCategory") }}',
            columns: [
                { data: 'Grievance_Cat_id', name: 'Category_Name', className: 'text-nowrap' },
                { data: 'Sub_Category_Name', name: 'Category_Description', className: 'text-nowrap' },
                { data: 'Action', name: 'Action', orderable: false, searchable: false },
                {data:'created_at',visible:false,searchable:false},
            ]
        });





        $(document).on('click', '.delete-row-btn', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest("tr");
            var main_id = $(this).data('cat-id');

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
                        url: "{{ route('GrievanceAndDisciplinery.config.GrievanceSubCategoryDestory', ':id') }}".replace(':id', main_id),
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success) {
                            
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#IndexGrievanceSubCategory').DataTable().ajax.reload();

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

        $(document).on("click", "#IndexGrievanceSubCategory .edit-row-btn", function (event) {
            event.preventDefault(); // Prevent default action

            // Find the parent row
            var $row = $(this).closest("tr");

            var Main_id = $(this).attr('data-cat-id');
            var Grievance_Cat_id = $(this).attr('data-Grievance_Cat_id');
             var DiscriplineryName = $row.find("td:nth-child(1)").text().trim();
             var Description = $row.find("td:nth-child(2)").text().trim();

            var GrievanceCategory = @json($GrievanceCategory);
            var tickSvg = '<svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg>';
            var selectedCat = GrievanceCategory.find(item => item.id == Grievance_Cat_id);

            var editRowHtml = `
                       <td class="py-1">
                           <div class="form-group">
                            <select class="form-select dd-native-select" name="Grievance_Cat_id" id="Grievance_Cat_id">
                                <option value="">Select Grievance Category</option>
                                ${GrievanceCategory.map(item => `
                                <option value="${item.id}" ${item.id == Grievance_Cat_id ? 'selected' : ''}>${item.Category_Name}</option>
                            `).join('')}
                            </select>
                            <div class="dd" data-target="#Grievance_Cat_id">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">${selectedCat ? selectedCat.Category_Name : 'Select Grievance Category'}</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Grievance category">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a category…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item${Grievance_Cat_id ? '' : ' active'}" role="option" data-value=""><span class="dd-nm">Select Grievance Category</span>${tickSvg}</div>
                                        ${GrievanceCategory.map(item => `
                                        <div class="dd-item${item.id == Grievance_Cat_id ? ' active' : ''}" role="option" data-value="${item.id}"><span class="dd-nm">${item.Category_Name}</span>${tickSvg}</div>
                                    `).join('')}
                                    </div>
                                </div>
                            </div>
                          </div>
                        </td>
                        <td class="py-1">
                        <div class="form-group">
                            <input type="text" class="form-control" id="Sub_Category_Name" name="Sub_Category_Name" value="${Description}" placeholder="Grievance Sub Category">

                        </div>
                        </td>
                    <td class="py-1">
                         <a href="javascript:void(0)" class="btn eb-btn-primary update-row-btn_cat" data-cat-id="${Main_id}">Submit</a>
                    </td>`;
            $row.html(editRowHtml);
        });

        $(document).on("click", "#IndexGrievanceSubCategory .update-row-btn_cat", function (event) {
            event.preventDefault(); // Prevent default action
            var $row = $(this).closest("tr");
            var Main_id = $(this).attr('data-cat-id');
            var Grievance_Cat_id = $row.find("select").eq(0).val();
            var Sub_Category_Name = $row.find("input").eq(0).val();
            $.ajax({
                url: "{{ route('GrievanceAndDisciplinery.config.GrievanceSubCategorinlineUpdate', '') }}/" + Main_id,
                type: "PUT",
                data: {
                    Main_id:Main_id,
                    Grievance_Cat_id:Grievance_Cat_id,
                    Sub_Category_Name:Sub_Category_Name,
                },
                success: function(response) {
                    if(response.success == true) { // Ensure response contains a success key

                        $('#IndexGrievanceSubCategory').DataTable().ajax.reload();
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
</script>
@endsection
