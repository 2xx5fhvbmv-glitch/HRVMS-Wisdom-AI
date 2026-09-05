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
                            <span>Time And Attendance</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="card">
                    <div class="card-header">
                        <div class="row g-md-3 g-2 align-items-center">
                            <div class="col-xl-3 col-lg-5 col-md-8 col-sm-8 ">
                                <div class="input-group">
                                    <input type="search" class="form-control Search" placeholder="Search" />
                                    <i class="fa-solid fa-search"></i>
                                </div>
                            </div>
                            {{-- <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                                <select class="form-select">
                                    <option selected>Department</option>
                                    <option value="1">abc</option>
                                    <option value="2">abc</option>
                                </select>
                            </div>
                            <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                                <select class="form-select">
                                    <option selected>Section</option>
                                    <option value="1">abc</option>
                                    <option value="2">abc</option>
                                </select>
                            </div> --}}
                            <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                                <select class="form-select ResortPosition dd-native-select" id="ResortPosition">
                                    <option></option>
                                    @if($ResortPosition->isNotEmpty())
                                        @foreach ($ResortPosition as $p)
                                        <option value="{{ $p->id }}">{{ $p->position_title }}</option>

                                        @endforeach
                                    @endif
                                </select>
                                <div class="dd" data-target="#ResortPosition">
                                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dd-lbl">Select a Position</span>
                                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                    <div class="dd-panel" role="listbox" aria-label="Position">
                                        <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                                        <div class="dd-scroll">
                                            <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select a Position</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            @if($ResortPosition->isNotEmpty())
                                                @foreach ($ResortPosition as $p)
                                                <div class="dd-item" role="option" data-value="{{ $p->id }}"><span class="dd-nm">{{ $p->position_title }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                                <input type="text" class="form-control datepicker" id="datepicker"  placeholder="Select Duration">
                            </div>
                        </div>
                    </div>
                    <table id="table-locationHistory" class="table  table-locationHistory  w-100">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Positions</th>
                                <th>Department</th>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                {{-- <th>Action</th> --}}
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>

                    </table>


                </div>
            </div>

        </div>
    </div>
    <div class="modal fade" id="viewMapLocationHistory-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Map View</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body ">

                    <iframe  width="1075" height="450" style="border:0;" id="ModalIframe" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>

                 </div>
            </div>
        </div>
    </div>
    @endsection
    @section('import-css')
    @include('resorts._dropdown_styles')
    @endsection

    @section('import-scripts')

    <script type="text/javascript">



$(document).ready(function() {

    flatpickr('.datepicker', {
        dateFormat: 'd/m/Y',
        allowInput: true,
        appendTo: document.body
    })
    GetLocationHistory();
});
$(document).on("click", ".LocationHistoryData", function()
{
    let location1 = $(this).attr('data-location');
    let type =$(this).data('id');
    if (!location1 || location1.trim() === "")
    {
        toastr.error(type+" data not avilable", "Validation Error", {
            positionClass: 'toast-bottom-right'
        });
        return false;
    }
    $("#viewMapLocationHistory-modal").modal('show');
    $("#ModalIframe").attr("src", location1);


});
$(document).on("change", ".ResortPosition", function()
{
    GetLocationHistory();

});
$(document).on("keyup", ".Search", function()
{
    GetLocationHistory();
});

$(document).on("change", ".datepicker", function()
{
    GetLocationHistory();
});

function GetLocationHistory()
{

    $('#table-locationHistory tbody').empty();
    if ($.fn.DataTable.isDataTable('#table-locationHistory'))
    {
    $('#table-locationHistory').DataTable().destroy();
    }
    var locationHistory = $('#table-locationHistory').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 6,
            processing: true,
            serverSide: true,
            order: [[6, 'desc']],
            ajax:
            {
                    url: "{{ route('resort.timeandattendance.LocationHistory') }}",
                    type: 'get',
                    data: function(d)
                    {
                                let Department = $('#ResortPosition').val();
                                let searchTerm = $('.Search').val();
                                let date = $('.datepicker').val();
                                d.searchTerm = searchTerm;
                                d.position= Department;
                                d.date= date;
                    }
                },
            columns: [

                { data: 'EmployeeName', name: 'EmployeeName', className: 'text-nowrap' },
                { data: 'Position', name: 'Position', className: 'text-nowrap' },
                { data: 'Department', name: 'Department', className: 'text-nowrap' },
                { data: 'date', name: 'Date', className: 'text-nowrap' },
                { data: 'CheckIn', name: 'CheckIn', className: 'text-nowrap' },
                { data: 'CheckOut', name: 'CheckOut', className: 'text-nowrap' },
                // { data: 'action', name: 'action', className: 'text-nowrap' },
                {data:'created_at', visible:false,searchable:false},

            ]
    });
}




    </script>
    @include('resorts._dropdown_script')
    @endsection
