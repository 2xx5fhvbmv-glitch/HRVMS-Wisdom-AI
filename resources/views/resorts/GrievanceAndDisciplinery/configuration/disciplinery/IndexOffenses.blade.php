@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #disciplinary-offenses-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #disciplinary-offenses-hero { padding-bottom: 0; }
    }
</style>

<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="disciplinary-offenses-hero">
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

                        <table id="OffensesTable" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Disciplinary Category</th>
                                    <th class="text-nowrap">Offenses Name</th>
                                    <th>Description</th>
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
@include('resorts._emotional_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-css')
@endsection

@section('import-scripts')

<script>
        var DisciplineryCategory = $('#OffensesTable').DataTable({
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
            ajax: '{{ route("GrievanceAndDisciplinery.config.Offensesindex") }}',
            columns: [
                { data: 'disciplinary_cat_id', name: 'disciplinary_cat_id', className: 'text-nowrap' },
                { data: 'OffensesName', name: 'OffensesName', className: 'text-nowrap' },
                { data: 'offensesdescription', name: 'offensesdescription', className: 'text-nowrap' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
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
                        url: "{{ route('GrievanceAndDisciplinery.config.OffensesDestory', ':id') }}".replace(':id', main_id),
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success) {
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#OffensesTable').DataTable().ajax.reload();

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

        $(document).on("click", "#OffensesTable .edit-row-btn", function (event) {
            event.preventDefault(); // Prevent default action

            // Find the parent row
            var $row = $(this).closest("tr");

            var Main_id = $(this).attr('data-cat-id');
            var disciplinary_cat_id = $(this).attr('data-disciplineryid');

            
            var DiscriplineryName = $row.find("td:nth-child(1)").text().trim();
            var Description = $row.find("td:nth-child(2)").text().trim();
            var DisciplinaryCategories = @json($DisciplinaryCategories);
            var tickSvg = '<svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg>';
            var selectedCat = DisciplinaryCategories.find(item => item.id == disciplinary_cat_id);
            var editRowHtml = `
                    <td class="py-1">
                        <div class="form-group">
                            <select class="form-select dd-native-select" name="disciplinary_cat_id" id="disciplinary_cate" aria-label="Disciplinary category">
                                <option value="">Select Category</option>
                                ${DisciplinaryCategories.map(item => `
                                <option value="${item.id}" ${item.id == disciplinary_cat_id ? 'selected' : ''}>
                                    ${item.DisciplinaryCategoryName}
                                </option>
                            `).join('')}
                            </select>
                            <div class="dd" data-target="#disciplinary_cate">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">${selectedCat ? selectedCat.DisciplinaryCategoryName : 'Select Category'}</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Disciplinary category">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a category…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item${disciplinary_cat_id ? '' : ' active'}" role="option" data-value=""><span class="dd-nm">Select Category</span>${tickSvg}</div>
                                        ${DisciplinaryCategories.map(item => `
                                        <div class="dd-item${item.id == disciplinary_cat_id ? ' active' : ''}" role="option" data-value="${item.id}"><span class="dd-nm">${item.DisciplinaryCategoryName}</span>${tickSvg}</div>
                                    `).join('')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="py-1">
                        <div class="form-group">
                        <div class="form-group">
                            <input type="text" class="form-control DisciplinaryCategoryName" value="${DiscriplineryName}" />
                        </div>
                    </td>
                    <td class="py-1">
                        <div class="form-group">
                            <textarea type="text" class="form-control DisciplinaryCategoryName" >${DiscriplineryName}</textarea>
                        </div>
                    </td>
                    <td class="py-1">
                        <a href="javascript:void(0)" class="btn eb-btn-primary update-row-btn_cat" data-cat-id="${Main_id}">Submit</a>
                    </td>
                `;

            // // Replace row content with editable form
            $row.html(editRowHtml);
        });

        $(document).on("click", "#OffensesTable .update-row-btn_cat", function (event) {
            event.preventDefault(); // Prevent default action
            var $row = $(this).closest("tr");
            var Main_id = $(this).attr('data-cat-id');

            var disciplinary_cat_id = $row.find("select").val();
            var OffensesName = $row.find("input").eq(0).val();
            var Description = $row.find("textarea").val();
                   
            $.ajax({
                url: "{{ route('GrievanceAndDisciplinery.config.OffensesinlineUpdate', '') }}/" + Main_id,
                type: "PUT",
                data: {
                    Main_id:Main_id,
                    disciplinary_cat_id:disciplinary_cat_id,
                    OffensesName:OffensesName,
                    offensesdescription:Description,
                },
                success: function(response) {
                    if(response.success == true) { // Ensure response contains a success key

                        $('#OffensesTable').DataTable().ajax.reload();
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
