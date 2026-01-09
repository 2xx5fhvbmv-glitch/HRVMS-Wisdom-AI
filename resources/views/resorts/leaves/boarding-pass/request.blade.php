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
            <div class="page-hedding page-appHedding">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Leave </span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-sm-6 ">
                            <div class="input-group">
                                <input type="search" class="form-control" id="searchInput" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-5  col-6">
                            <input type="text" name="dateFilter" id="datefilter" class="form-control datepicker"/>
                        </div>
                    </div>
                </div>
                <div class="table-responsive mb-md-3 mb-2">
                    <table id="table-GetApprovedBoradingPasses" class="table table-incidentInvesMeet w-100 mb-0">
                        <thead>
                            <tr>
                                <th>EmpID</th>
                                <th>Employee Name</th>
                                <th>Transportation</th>
                                <th>Arrival Date</th>
                                <th>Arrival Time</th>
                                <th>Departure Date</th>
                                <th>Departure Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>
    
    <div class="modal fade" id="RejactBoardingPass-modal" tabindex="-1" aria-labelledby="RejactBoardingPass-modal" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Rejection Reason</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
            <form id="BoardingPassRejact">
                @csrf
                    <div class="modal-body">
                        <div class="employee-name-content">
                            <div class="row g-12">

                                <div class="col-sm-12">
                                    <div class="d-flex align-items-center employee-name-box">
                                        <textarea placeholder="Enter Reason" name="reason" id="reason"class="form-control" ></textarea>
                                    </div>
                                </div>
                                <input type="hidden" id="Formflag" name="flag">

                                <input type="hidden" id="pass_id" name="pass_id">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-end">
                        <a href="#" class="btn btn-sm btn-danger " data-bs-dismiss="modal" aria-label="Close">Cancel</a>

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
<script type="text/javascript">
    // new DataTable('#example');
$(document).ready(function () {
    $("#datefilter").datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,      // Close the picker after selection
        todayHighlight: true, // Highlight today's date
        endDate: new Date(),
    });
    GetApprovedBoradingPasses()

  


        $(document).on("click", "#table-GetApprovedBoradingPasses .edit-row-btn", function (event) {
            event.preventDefault(); // Prevent default action

            // Find the parent row
            var $row = $(this).closest("tr");

            // Extract division ID
            var id = $(this).data('id');

            var arrival_time   = $(this).data('arrival_time');
            var departure_time = $(this).data('departure_time');

            
            var EmpID = $row.find("td:nth-child(1)").text().trim();
            var Emp_name = $row.find("td:nth-child(2)").text().trim();
            var Transportation = $row.find("td:nth-child(3)").text().trim();
            var a_date = $row.find("td:nth-child(4)").text().trim();
            var a_time = $row.find("td:nth-child(5)").text().trim();
            var d_date = $row.find("td:nth-child(6)").text().trim();
            var d_time = $row.find("td:nth-child(7)").text().trim();
            var status = $row.find("td:nth-child(8)").text().trim();

            var editRowHtml = `
                    <td class="py-1">
                        <div class="form-group">
                           ${EmpID}
                        </div>
                    </td>
                    <td class="py-1">
                        <div class="form-group">
                           ${Emp_name}
                        </div>
                    </td>
                     <td class="py-1">
                        <div class="form-group">
                           ${Transportation}
                        </div>
                    </td>
                     <td class="py-1">
                        <div class="form-group">
                           ${a_date}
                        </div>
                    </td>
                    <td class="py-1">
                        <div class="form-group">
                            <input type="time" class="form-control name"   value="${arrival_time}" />
                        </div>
                    </td>
                    <td class="py-1">
                        <div class="form-group">
                           ${d_date}
                        </div>
                    </td>
                    <td class="py-1">
                        <div class="form-group">
                            <input type="time" class="form-control name"   value="${departure_time}" />
                        </div>
                    </td>
                       <td class="py-1">
                        <div class="form-group">
                         <span class="badge badge-success">  ${status}</span>
                        </div>
                    </td>
                    <td class="py-1">
                        <a href="javascript:void(0)" class="btn  btn-sm btn-theme update-row-btn_agent" data-flag="Approved" data-id="${id}">Submit</a>
                        <a href="javascript:void(0)" class="btn btn-sm btn-danger Cancel">Reset</a>
                       '
                    </td>
                `;

            // Replace row content with editable form
            $row.html(editRowHtml);
        });
        
        $(document).on("click", "#table-GetApprovedBoradingPasses .Cancel", function (event) {
            GetApprovedBoradingPasses();
        });
        $(document).on("click", "#table-GetApprovedBoradingPasses .SendPass", function (event) {
            
            var flag = $(this).data('flag');
            var id = $(this).data('id');
            $("#RejactBoardingPass-modal").modal("show");
            $("#pass_id").val(id);
            $("#Formflag").val(flag);
            
      
        });
        $(document).on("submit", "#BoardingPassRejact", function (event) {
            event.preventDefault();
            var reason = $("#reason").val();
            var flag = $("#Formflag").val();
            var id = $("#pass_id").val();
            var a_time ="";
            var d_time ="";
            BoardingPassStatusUpdate(flag,id,a_time,d_time,reason);
            $("#RejactBoardingPass-modal").modal("hide");

        });

        
        $(document).on("click", "#table-GetApprovedBoradingPasses .update-row-btn_agent", function (event) {
            event.preventDefault(); // Prevent default action
            var $row = $(this).closest("tr");
            var flag = $(this).data('flag');
            var id = $(this).data('id');
            var a_time = $row.find('input[type="time"]').eq(0).val();
            var d_time = $row.find('input[type="time"]').eq(1).val();
            reason="";
            BoardingPassStatusUpdate(flag,id,a_time,d_time,reason);

        });
        $("#datefilter").on("change", function (event) {
           
            GetApprovedBoradingPasses();
        });
        $("#searchInput").on("keyup", function (event) {
           
           GetApprovedBoradingPasses();
       });
        function BoardingPassStatusUpdate(flag,id,a_time,d_time,reason)
        {
         
            $.ajax({
                url: "{{ route('resort.BoardingStatusUpdate')}}",
                type: "POST",
                data: {
                    flag : flag,
                    id : id,
                    'a_time':a_time,
                    'd_time':d_time,
                    "reason":reason,
                },
                success: function(response) {


                    GetApprovedBoradingPasses();
                    console.log(response.success ,response.success == true);
                    if(response.success == true) 
                    {
                        
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
        }
        function GetApprovedBoradingPasses()
        {   
            if ($.fn.dataTable.isDataTable('#table-GetApprovedBoradingPasses')) {
                // If initialized, destroy the existing instance
                $('#table-GetApprovedBoradingPasses').DataTable().clear().destroy();
            }

            var InvenotryIndex = $('#table-GetApprovedBoradingPasses').DataTable({
                "searching": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": true,
                "bAutoWidth": false,
                "scrollX": true,
                "iDisplayLength": 6,
                processing: true,
                serverSide: true,
                ajax: {
                        url: "{{ route('resort.boardingpass.list') }}",
                        type: 'GET',
                        data: function(d) {
                            d.searchInput = $("#searchInput").val();
                            d.datefilter = $("#datefilter").val();
                        }
                    },
                columns: [
                    { data: 'EmpId', name: 'EmpId', className: 'text-nowrap' },
                    { data: 'EmployeeName', name: 'EmployeeName', className: 'text-nowrap' },
                    { data: 'Transportation', name: 'Transportation', className: 'text-nowrap' },
                    { data: 'ArrivalDate', name: 'ArrivalDate', className: 'text-nowrap' },
                    { data: 'ArrivalTime', name: 'ArrivalTime', className: 'text-nowrap' },
                    { data: 'DepartureDate', name: 'DepartureDate', className: 'text-nowrap' },
                    { data: 'DepartureTime', name: 'DepartureTime', className: 'text-nowrap' },
                    { data: 'Status', name: 'Status   ', className: 'text-nowrap' },
                    { data: 'Action', name: 'Action', className: 'text-nowrap' },

                ]
            });
        }
});

</script>
@endsection
