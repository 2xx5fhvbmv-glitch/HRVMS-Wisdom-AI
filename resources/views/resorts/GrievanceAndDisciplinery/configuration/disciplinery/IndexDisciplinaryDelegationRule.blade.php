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

                        <table id="DisciplineryCategory" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Category Name</th>
                                    <th>Rule Name</th>
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
        var DisciplineryCategory = $('#DisciplineryCategory').DataTable({
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
            ajax: '{{ route("GrievanceAndDisciplinery.config.IndexDisciplinaryDelegationRule") }}',
            columns: [
                { data: 'CategoryName', name: 'CategoryName', className: 'text-nowrap' },
                { data: 'RuleName', name: 'RuleName', className: 'text-nowrap' },
                { data: 'Action', name: 'Action', orderable: false, searchable: false },
                {data:'created_at',visible:false,searchable:false},
            ]
        });

        $(document).on('click', '.delete-row-btn', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest("tr");
            var main_id = $(this).data('self_id');

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
                        url: "{{ route('GrievanceAndDisciplinery.config.DisciplineryDeletegationRuleDestory', ':id') }}".replace(':id', main_id),
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success) {
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#DisciplineryCategory').DataTable().ajax.reload();

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

        $(document).on("click", "#DisciplineryCategory .edit-row-btn", function (event) {
            event.preventDefault(); // Prevent default action

            // Find the parent row
            var $row = $(this).closest("tr");

            var Main_id = $(this).attr('data-cat-id');
            var Del_cat_id = $(this).attr('data-del_cat_id');
            
            var DiscriplineryName = $row.find("td:nth-child(1)").text().trim();
            var Description = $row.find("td:nth-child(2)").text().trim();
            var editRowHtml = `
                    <td class="py-1">
                        <div class="form-group">
                            <select class="form-select select2t-none DelegationRuleCategory" 
                                        id="DelegationRuleCategory_1"  data-id="1" name="Del_cat_id"  aria-label="Default select example" required  data-parsley-required-message="Please select a disciplinary category">
                                    <option value=""></option>
                                    @if($DisciplinaryCategories->isNotEmpty())
                                       @foreach($DisciplinaryCategories as $item)
                                            <option value="{{ base64_encode($item->id) }}" 
                                                ${Del_cat_id === "{{ base64_encode($item->id) }}" ? 'selected' : ''}>
                                                {{ $item->DisciplinaryCategoryName }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                        </div>
                    </td>
                    <td class="py-1">
                        <div class="form-group">
                            <input type="text" class="form-control" id="DelegationRuleName" value="${Description}"  required data-parsley-required-message="Please Enter Rule"  name="Del_Rule" placeholder="Set Rule">
                        </div>
                    </td>
                    <td class="py-1">
                        <a href="javascript:void(0)" class="btn btn-theme update-row-btn_cat" data-cat-id="${Main_id}">Submit</a>
                    </td>
                `;

            // Replace row content with editable form
            $row.html(editRowHtml);
                $("#DelegationRuleCategory_1").select2({
                placeholder: "Select category",
                allowClear: true,
                width: '100%'        
            });
        });

        $(document).on("click", "#DisciplineryCategory .update-row-btn_cat", function (event) {
            event.preventDefault(); // Prevent default action
            var $row = $(this).closest("tr");
            var Main_id = $(this).attr('data-cat-id');
            var Del_cat_id = $row.find("select").val();
            var Del_Rule = $row.find("input").val();

            $.ajax({
                url: "{{ route('GrievanceAndDisciplinery.config.DisciplineryDeletgationRuleinlineUpdate', '') }}/" + Main_id,
                type: "PUT",
                data: {
                    Main_id:Main_id,
                    Del_cat_id:Del_cat_id,
                    Del_Rule:Del_Rule,
                },
                success: function(response) {
                    if(response.success == true) { // Ensure response contains a success key

                        $('#DisciplineryCategory').DataTable().ajax.reload();
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
