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
                        <span>Incident</span>
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

                        <div class="card-header">
                            <div class="row g-md-3 g-2 align-items-center">
                                
                                <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                    <select id="categoryFilter" class="form-select select2t-none">
                                        <option value="">All Categories</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                    <select id="priorityFilter" class="form-select select2t-none">
                                        <option value="">All Priority</option>
                                        <option value="Low">Low</option>
                                        <option value="Medium">Medium</option>
                                        <option value="High">High</option>
                                    </select>
                                </div>
                                
                            </div>
                        </div>

                        <table id="IncidentSubCategory" class="table w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Category Name</th>
                                    <th class="text-nowrap">Sub Category Name</th>
                                    <th class="text-nowrap">Priority</th>
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
    $(document).ready(function(){
        $('.select2t-none').select2();
        fecthIncidentSubCategories();

        $('#categoryFilter,#priorityFilter').on('keyup change', function () {
            fecthIncidentSubCategories();
        });
        window.categories = @json($categories);

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
                        url: "{{ route('incident.subcategories.destory', ':id') }}".replace(':id', main_id),
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success) {
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#IncidentSubCategory').DataTable().ajax.reload();

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

        $(document).on("click", "#IncidentSubCategory .edit-row-btn", function (event) {
            $('.select2t-none').select2();
            event.preventDefault(); // Prevent default action
            // Find the parent row
            var $row = $(this).closest("tr");
            var Main_id = $(this).attr('data-cat-id');
            var categoryName = $row.find("td:nth-child(1)").text().trim(); 
            var subcategoryName = $row.find("td:nth-child(2)").text().trim(); 
            var priority = $row.find("td:nth-child(3)").text().trim(); 
            
            // Use the `categories` array from the global scope
            var categoryOptions = '<option value="">Select Category</option>';
            window.categories.forEach(function(cate) {
                var selected = (categoryName === cate.category_name) ? 'selected' : '';
                categoryOptions += `<option value="${cate.id}" ${selected}>${cate.category_name}</option>`;
            });
            
            var editRowHtml = `
                    <td class="py-1">
                        <div class="form-group">
                            <select id="category-select" name="category_id" class="form-select select2t-none">
                                ${categoryOptions}
                            </select>
                        </div>
                    </td>
                    <td class="py-1">
                        <div class="form-group">
                            <input type="text" class="form-control subcategoryName" value="${subcategoryName}" />
                        </div>
                    </td>
                    <td class="py-1">
                        <div class="form-group">
                            <select id="priority-select" name="priority" class="form-select select2t-none">
                                <option value="">Select Priority</option>
                                <option value="Low" ${priority === "Low" ? "selected" : ""}>Low</option>
                                <option value="Medium" ${priority === "Medium" ? "selected" : ""}>Medium</option>
                                <option value="High" ${priority === "High" ? "selected" : ""}>High</option>
                            </select>
                        </div>
                    </td>
                    <td class="py-1">
                        <a href="javascript:void(0)" class="btn btn-theme update-row-btn_cat" data-cat-id="${Main_id}">Submit</a>
                    </td>
                `;

            // // Replace row content with editable form
            $row.html(editRowHtml);


            // Reinitialize Select2 after replacing the row
            $row.find(".select2t-none").select2();
        
        });

        $(document).on("click", "#IncidentSubCategory .update-row-btn_cat", function (event) {
            event.preventDefault();
            var $row = $(this).closest("tr");
            var Main_id = $(this).attr('data-cat-id'); 
            var categoryId = $row.find("select").eq(0).val();
            var Subcategory_Name = $row.find("input").eq(0).val();
            var priority = $row.find("select").eq(1).val();

            $.ajax({
                url: "{{ route('incident.subcategories.inlineUpdate', '') }}/" + Main_id,
                type: "PUT",
                data: {
                    Main_id: Main_id, 
                    category_id: categoryId,
                    subcategory_name: Subcategory_Name,
                    priority: priority
                },
                success: function(response) {
                    if (response.success) { 
                        $('#IncidentSubCategory').DataTable().ajax.reload();
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
                    if (xhr.responseJSON?.errors) {
                        $.each(xhr.responseJSON.errors, function(key, error) {
                            errorMessage += error + "<br>";
                        });
                    } else {
                        errorMessage = xhr.responseJSON?.message || "An error occurred while updating.";
                    }
                    toastr.error(errorMessage, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

    });

    function fecthIncidentSubCategories(){
        if ($.fn.DataTable.isDataTable('#IncidentSubCategory')) {
            $('#IncidentSubCategory').DataTable().destroy();
        }
        var IncidentSubCategory = $('#IncidentSubCategory').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 10,
            processing: true,
            serverSide: true,
            order:[[4, 'desc']],
            ajax: {
                url: "{{ route('incident.subcategories.list') }}",
                data: function (d) {
                    d.category = $('#categoryFilter').val();
                    d.priority = $('#priorityFilter').val();
                },
                type: "GET",
            },
            columns: [
                { data: 'category_name', name: 'category_name', className: 'text-nowrap' },
                { data: 'subcategory_name', name: 'subcategory_name', className: 'text-nowrap' },
                { data: 'priority', name: 'priority', className: 'text-nowrap' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
                {data:'created_at',visible:false,searchable:false},
            ]
        });
    }
</script>
@endsection
