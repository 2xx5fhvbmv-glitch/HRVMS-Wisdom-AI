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

                        <table id="IndexGrievanceDelegationRule" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap"> Category Name </th>
                                    <th> Delegation Rule</th>
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
        var DisciplineryCategory = $('#IndexGrievanceDelegationRule').DataTable({
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
            ajax: '{{ route("GrievanceAndDisciplinery.config.IndexGrievanceDelegationRule") }}',
            columns: [
                { data: 'Grievance_Cat_id', name: 'Grievance_Cat_id', className: 'text-nowrap' },
                { data: 'delegation_rule', name: 'delegation_rule', className: 'text-nowrap' },
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
                        url: "{{ route('GrievanceAndDisciplinery.config.GrievanceDelegeationRuleDestory', ':id') }}".replace(':id', main_id),
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success) {
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#IndexGrievanceDelegationRule').DataTable().ajax.reload();

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

        $(document).on("click", "#IndexGrievanceDelegationRule .edit-row-btn", function (event) {
            event.preventDefault(); // Prevent default action

            // Find the parent row
            var $row = $(this).closest("tr");

            var Main_id = $(this).attr('data-cat-id');
        

         
            var Grievance_Cat_id = $(this).attr('data-Grievance_Cat_id');
             var DiscriplineryName = $row.find("td:nth-child(1)").text().trim();
             var Description = $row.find("td:nth-child(2)").text().trim();

             var optionsHtml = `<option value=""></option>`;
            @foreach($GrievanceCategory as $item)
                optionsHtml += `<option value="{{ $item->id }}" ${Grievance_Cat_id == "{{ $item->id }}" ? 'selected' : ''}>{{ $item->Category_Name }}</option>`;
            @endforeach

            var editRowHtml = `
                       <td class="py-1">
                           <div class="form-group">
                            <select class="form-select select2t-none" name="Grievance_Cat_id" id="Grievance_Cat_id">
                               ${optionsHtml}
                            </select>
                          </div>
                        </td>
                        <td class="py-1">
                        <div class="form-group">
                            <input type="text" class="form-control" id="delegation_rule" name="delegation_rule" value="${Description}" placeholder="Grievance Sub Category">

                        </div>
                        </td>
                    <td class="py-1">
                         <a href="javascript:void(0)" class="btn btn-theme update-row-btn_cat" data-cat-id="${Main_id}">Submit</a>
                    </td>`;
            $row.html(editRowHtml);
            $("#Grievance_Cat_id").select2({
                placeholder: "Select Greivance Category",
                allowClear: true,
                width: '100%'        
            });
       
        });

        $(document).on("click", "#IndexGrievanceDelegationRule .update-row-btn_cat", function (event) {
            event.preventDefault(); // Prevent default action
            var $row = $(this).closest("tr");
            var Main_id = $(this).attr('data-cat-id');
            var Grievance_Cat_id = $row.find("select").eq(0).val();
            var delegation_rule = $row.find("input").eq(0).val();
            $.ajax({
                url: "{{ route('GrievanceAndDisciplinery.config.GrievanceDelegeationRuleinlineUpdate', '') }}/" + Main_id,
                type: "PUT",
                data: {
                    Main_id:Main_id,
                    Grievance_Cat_id:Grievance_Cat_id,
                    delegation_rule:delegation_rule,
                },
                success: function(response) {
                    if(response.success == true) { // Ensure response contains a success key

                        $('#IndexGrievanceDelegationRule').DataTable().ajax.reload();
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
