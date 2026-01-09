@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

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
                        <span>WORKFORCE PLANNING</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="#sendRequest-modal" data-bs-toggle="modal" class=" btn btn-sm btn-theme">Request Manning</a>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card">

            <table id="" class="table data-Table table-FilledPositions" style="width:100%">
                <thead>
                    <tr>
                        <th>Positions</th>
                        <th class="text-nowrap">Department</th>
                        <th class="text-nowrap">No. Of Positions</th>
                    </tr>
                </thead>

                <tfoot>
                    <tr>
                        <th>Total:</th>
                        <th></th>
                        <th id="total">
                            {{ 0 }} <!-- Calculate total filled count dynamically -->
                        </th>
                    </tr>
                </tfoot>
            </table>


            </div>
        </div>

    </div>
</div>
<div class="modal fade" id="employee-namemodal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Employee Names</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="employee-name-content">
                    <div class="row g-3" id="employee-names-list">
                        <!-- Employee names will be injected here dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@php
    $msg = config('settings.manning_request');

@endphp
<div class="modal fade" id="sendRequest-modal" tabindex="-1" aria-labelledby="sendRequest-modal" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Request Manning</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
            <form id="RequestManning">
                @csrf
                    <div class="modal-body">
                        <div class="employee-name-content">
                            <div class="row g-12">

                                <div class="col-sm-12">
                                    <div class="d-flex align-items-center employee-name-box">

                                        <textarea placeholder="Enter Request Manning" name="manningRequest" id="manningRequest"class="form-control" >{{ $msg['msg1'] }}</textarea>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-end">
                        <a href="#" class="btn btn-sm btn-themeGray me-2 " data-bs-dismiss="modal" aria-label="Close">Cancel</a>

                        <button type="submit" class="btn btn-sm btn-theme">Submit</button>
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
    $(document).ready(function() {


    if ($.fn.DataTable.isDataTable('.table-FilledPositions')) {
        $('.table-FilledPositions').DataTable().destroy();
    }

        var FilledPositionTable = $('.table-FilledPositions').DataTable({
        "searching": false,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 10,
        processing: true,
        serverSide: true,
        order: [[3, 'desc']],
        ajax: {
            url: '{{ route("workforceplan.filledpositions.data") }}',
            dataSrc: function(json) {
                // ✅ Calculate headcount for current page only
                let total = 0;
                json.data.forEach(function(item) {
                    total += parseInt(item.no_of_employees) || 0;
                });
                $('#total').html(total);
                return json.data;
            }
        },
        columns: [
            { data: 'position_title', name: 'position_title' },
            { data: 'department', name: 'department' },
            { data: 'no_of_employees', name: 'no_of_employees', render: function(data, type, row) {
                return `<a href="#employee-namemodal" data-bs-toggle="modal"
                            class="text-theme fw-500 text-underline"
                            onclick="loadEmployeeNames(${row.id})">${data}</a>`;
            }},
            { data: 'created_at', visible: false, searchable: false },
        ]
    });


});
    function loadEmployeeNames(positionId) {
        // Clear previous content
        $('#employee-names-list').html('');

        // Make AJAX request to fetch employee names
        $.ajax({
            url: '{{ route("workforceplan.employee.names") }}', // Adjust the route if needed
            type: 'GET',
            data: { position_id: positionId },
            success: function(data) {
                let employeeContent = '';
                if (data.length > 0) {
                    data.forEach(function(employee) {
                        employeeContent += `
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center employee-name-box">
                                    <div class="img-box">
                                        <img src="${employee.profile_picture}" alt="image" class="img-fluid">
                                    </div>
                                    <a href="#">${employee.resort_admin.first_name} ${employee.resort_admin.last_name}</a>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    employeeContent = '<p>No employees found for this position.</p>';
                }

                // Inject employee content into the modal
                $('#employee-names-list').html(employeeContent);
            },
            error: function() {
                $('#employee-names-list').html('<p>Error loading employee names. Please try again.</p>');
            }
        });
    }
</script>
@endsection
