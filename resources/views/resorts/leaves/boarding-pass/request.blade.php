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

    {{-- View Boarding Pass Detail Modal --}}
    <div class="modal fade" id="boardingPassViewModal" tabindex="-1" aria-labelledby="boardingPassViewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="boardingPassViewModalLabel">Boarding Pass Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="boardingPassViewContent">
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-muted">Employee ID</div>
                            <div class="col-7" id="view-emp-id">—</div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-muted">Employee Name</div>
                            <div class="col-7" id="view-employee-name">—</div>
                        </div>
                        <hr class="my-3">
                        <h6 class="text-muted mb-2">Departure</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-muted">Date</div>
                            <div class="col-7" id="view-departure-date">—</div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-muted">Time</div>
                            <div class="col-7" id="view-departure-time">—</div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-muted">Transportation</div>
                            <div class="col-7" id="view-departure-transportation">—</div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-muted">Reason</div>
                            <div class="col-7" id="view-departure-reason">—</div>
                        </div>
                        <hr class="my-3">
                        <h6 class="text-muted mb-2">Arrival</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-muted">Date</div>
                            <div class="col-7" id="view-arrival-date">—</div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-muted">Time</div>
                            <div class="col-7" id="view-arrival-time">—</div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-muted">Transportation</div>
                            <div class="col-7" id="view-arrival-transportation">—</div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-muted">Reason</div>
                            <div class="col-7" id="view-arrival-reason">—</div>
                        </div>
                        <hr class="my-3">
                        <div class="row g-2 mb-2">
                            <div class="col-5 text-muted">Status</div>
                            <div class="col-7"><span id="view-status" class="badge">—</span></div>
                        </div>
                    </div>
                    <div id="boardingPassViewError" class="alert alert-danger d-none"></div>
                </div>
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

        $(document).on("click", ".view-boarding-pass-detail", function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $row = $btn.closest("tr");
            // Column order: EmpID(0), EmployeeName(1), Transportation(2), ArrivalDate(3), ArrivalTime(4), DepartureDate(5), DepartureTime(6), Status(7), Action(8)
            var rowArrivalTime = $row.find("td:eq(4)").text().trim();
            var rowDepartureTime = $row.find("td:eq(6)").text().trim();
            var rowTransportation = $row.find("td:eq(2)").text().trim();
            var id = $btn.attr("data-pass-id") || $btn.data("pass-id");
            if (!id) return;
            var url = "{{ route('resort.boardingpass.detail') }}?id=" + encodeURIComponent(id);
            $("#boardingPassViewError").addClass("d-none").text("");
            $("#boardingPassViewContent").show();
            $.ajax({
                url: url,
                type: "GET",
                dataType: "json",
                success: function (res) {
                    if (res && res.success && res.data) {
                        var d = res.data;
                        $("#view-emp-id").text(d.emp_id != null ? d.emp_id : "—");
                        $("#view-employee-name").text(d.employee_name != null ? d.employee_name : "—");
                        $("#view-departure-date").text(d.departure_date != null ? d.departure_date : "—");
                        $("#view-departure-time").text((d.departure_time && d.departure_time !== "—") ? d.departure_time : (rowDepartureTime || "—"));
                        $("#view-departure-transportation").text((d.departure_transportation && d.departure_transportation !== "—") ? d.departure_transportation : (rowTransportation && rowTransportation !== "-" ? rowTransportation : "—"));
                        $("#view-departure-reason").text(d.departure_reason != null ? d.departure_reason : "—");
                        $("#view-arrival-date").text(d.arrival_date != null ? d.arrival_date : "—");
                        $("#view-arrival-time").text((d.arrival_time && d.arrival_time !== "—") ? d.arrival_time : (rowArrivalTime || "—"));
                        $("#view-arrival-transportation").text((d.arrival_transportation && d.arrival_transportation !== "—") ? d.arrival_transportation : (rowTransportation && rowTransportation !== "-" ? rowTransportation : "—"));
                        $("#view-arrival-reason").text(d.arrival_reason != null ? d.arrival_reason : "—");
                        var badgeClass = (d.status == "Approved") ? "badge-success" : ((d.status == "Rejected") ? "badge-danger" : "badge-warning");
                        $("#view-status").attr("class", "badge " + badgeClass).text(d.status != null ? d.status : "—");
                    } else {
                        $("#boardingPassViewError").removeClass("d-none").text((res && res.message) ? res.message : "Failed to load details.");
                    }
                    var modalEl = document.getElementById("boardingPassViewModal");
                    if (modalEl && typeof bootstrap !== "undefined") {
                        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modal.show();
                    } else {
                        $("#boardingPassViewModal").modal("show");
                    }
                },
                error: function (xhr) {
                    var msg = "An error occurred while loading details.";
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    else if (xhr.status === 404) msg = "Boarding pass not found.";
                    else if (xhr.status === 500) msg = "Server error. Please try again.";
                    $("#boardingPassViewError").removeClass("d-none").text(msg);
                    $("#boardingPassViewContent").hide();
                    var modalEl = document.getElementById("boardingPassViewModal");
                    if (modalEl && typeof bootstrap !== "undefined") {
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    } else {
                        $("#boardingPassViewModal").modal("show");
                    }
                }
            });
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
