

@extends('resorts.layouts.app')
@section('page_tab_title',$page_title)

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
                        <span>Accommodation</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        {{-- <div class="col-xl-3 col-lg-4 col-md-5  col-sm-6 ">
                            <input type="text" class="form-control datepicker" placeholder="Select Duration">
                        </div> --}}
                        <div class="col-xl-3 col-lg-4 col-md-5  col-sm-6">
                            <select class="form-select  beds">
                                <option selected value="all">Available  beds</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="accordion-gary" id="accordionAccommodation">
                    @if($BuildingModel->isNotEmpty())
                        @foreach ($BuildingModel as $b)
                            <div class="accordion-item" >
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button GetInternalDetails @if($b->OccupancyLevel =='Alert') thresold-active @endif"  data-id="{{ $b->id }}"type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseOne{{ $b->id }}" aria-expanded="true" aria-controls="collapseOne"> @if($b->OccupancyLevel =='Alert') Threshold Level Hit for @endif
                                        {{ $b->BuildingName }}
                                        <span>Room Capacity: {{ $b->TotalRoom }}   | Rooms Available: {{ $b->AvailableRooms }} | Bed Capacity: {{ $b->BedCapacity }} | Beds Available:
                                            {{ $b->AvailableBed }}</span>
                                    </button>
                                </h2>
                                <div id="collapseOne{{ $b->id }}" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                                    data-bs-parent="#accordionAccommodation">
                                    <div class="bg-white">
                                        <div class="table-responsive">
                                            <table class="table table-accommodation" id="table-accommodation_{{ $b->id }}">
                                                <thead>
                                                    <tr>
                                                        <th>Floor No.</th>
                                                        <th>Room No.</th>
                                                        <th>For</th>
                                                        <th>Bed Capacity</th>
                                                        <th>Employee category</th>
                                                        <th>Room facilities</th>
                                                        <th>Status</th>
                                                        <th>Assign To</th>
                                                    </tr>
                                                </thead>
                                                <tbody>


                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
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
    getCollapesList();

    $(document).on("change",".beds",function(){


        getCollapesList();
    });
    $(document).on("click",".GetInternalDetails",function()
    {
        getCollapesList();
    });
    function getCollapesList()
    {
        $(".GetInternalDetails").each(function () {
        if (!$(this).hasClass('collapsed'))
        {
            var id = $(this).data('id');

            if ($.fn.DataTable.isDataTable('#table-accommodation_' + id)) {
                // Destroy the existing instance
                $('#table-accommodation_' + id).DataTable().destroy();
            }

            var FloorAndRoomIndex = $('#table-accommodation_'+id).DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 6,
            processing: true,
            serverSide: true,
            order:[[8, 'desc']],
            ajax: {
                url: '{{ route("resort.accommodation.GetBuildingWiseCollpasedData") }}',
                type: 'GET',
                data: function (d) {
                    d.id = id;
                    d.beds =$(".beds").val();
                }
            },


            columns: [
                { data: 'FloorNo', name: 'FloorNo', className: 'text-nowrap' },
                { data: 'RoomNo', name: 'RoomNo', className: 'text-nowrap' },
                { data: 'For', name: 'For', className: 'text-nowrap' },
                { data: 'BedCapacity', name: 'BedCapacity', className: 'text-nowrap' },
                { data: 'EmployeeCategory', name: 'EmployeeCategory', className: 'text-nowrap' },
                { data: 'RoomFacilities', name: 'RoomFacilities', className: 'text-nowrap' },
                { data: 'RoomStatus', name: 'RoomStatus', className: 'text-nowrap' },
                { data: 'AssignTo', name: 'AssignTo', className: 'text-nowrap' },
                 {data:'created_at',visible:false,searchable:false},
            ]
        });

        }
    });
    }

</script>
@endsection
