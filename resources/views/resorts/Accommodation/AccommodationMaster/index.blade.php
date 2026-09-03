

@extends('resorts.layouts.app')
@section('page_tab_title',$page_title)

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
<style>
    #accommodation-master-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #accommodation-master-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="accommodation-master-hero">
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
                            <select class="form-select dd-native-select beds" id="bedsFilter">
                                <option selected value="all">Available  beds</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                            <div class="dd" data-target="#bedsFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Available beds</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Beds">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value="all"><span class="dd-nm">Available beds</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Male"><span class="dd-nm">Male</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item" role="option" data-value="Female"><span class="dd-nm">Female</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    </div>
                                </div>
                            </div>
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
@include('resorts._dropdown_styles')
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
@include('resorts._dropdown_script')
@endsection
