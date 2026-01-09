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
                            <span>Peple</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="reviewOnboardIti-block mt-md-4 mt-2">
                <div class="bg-themeGrayLight mb-md-4 mb-3">
                    <div class="card-title">
                        <h3>Selected Employees</h3>
                    </div>
                    <div class="row g-md-3 g-2">
                        <div class="col-xl-4 col-lg-5 col-md-6">
                            <div class="bg-white">
                                <div class="d-flex align-items-center">
                                    <div class="img-circle userImg-block me-md-3 me-2">
                                        <img src="{{Common::getResortUserPicture($itinerary->employee->resortAdmin->id ?? null)}}" alt="user">
                                    </div>
                                    <div>
                                        <h6 class="fw-600"> 
                                            {{ $itinerary->employee->resortAdmin->full_name ?? '-' }}
                                        </h6>
                                        <p>{{$itinerary->employee->department->name}} - {{$itinerary->employee->position->position_title}}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-themeGrayLight mb-md-4 mb-3">
                    <div class="card-title">
                        <h3>Selected Templates</h3>
                    </div>
                    <div class="row g-md-3 g-2">
                        <div class="col-xl-4 col-lg-5 col-md-6">
                            <div class="bg-white">
                                <h6 class="fw-600 mb-1">{{$itinerary->template->name}}</h6>
                                <p class="fw-500">{{$itinerary->template->description}}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class=" bg-themeGrayLight mb-md-4 mb-3">
                    <div class="card-title">
                        <h3>Onboarding</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-lable mb-1">
                            <tr>
                                <th>Greeting Message:</th>
                                <td>{{$itinerary->greeting_message}}</td>
                            </tr>
                            <tr>
                                <th>Arrival Date:</th>
                                <td>{{ \Carbon\Carbon::parse($itinerary->arrival_date)->format('d F Y') }}</td>
                            </tr>
                            <tr>
                                <th>Arrival Time:</th>
                                <td>{{ \Carbon\Carbon::parse($itinerary->arrival_time)->format('h:i a') }}</td>
                            </tr>
                            <tr>
                                <th>Pickup From Airport</th>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle">
                                            <img src="{{Common::getResortUserPicture($itinerary->pickupemployee->resortAdmin->id ?? null)}}"
                                                alt="user">
                                        </div>
                                        <span class="userApplicants-btn">{{$itinerary->pickupemployee->resortAdmin->full_name}}</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Accompany For The Medical Test</th>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle">
                                            <img src="{{Common::getResortUserPicture($itinerary->accompanyMedicalEmployee->resortAdmin->id ?? null)}}"
                                                alt="user">
                                        </div>
                                        <span class="userApplicants-btn">{{$itinerary->accompanyMedicalEmployee->resortAdmin->full_name}}</span>
                                    </div>
                                </td>
                            </tr>
                            @php if($itinerary->resort_transportation_id  == 1)
                                $resortTransporationName = 'Seaplane';
                            elseif($itinerary->resort_transportation_id  == 2)
                                $resortTransporationName = 'Speedboat'; 
                            elseif($itinerary->resort_transportation_id  == 3)
                                $resortTransporationName = 'Domestic Flight';
                            else
                                $resortTransporationName = 'N/A';
                            @endphp
                            
                            <tr>
                                <th>Resort Transportation</th>
                                <td>{{$resortTransporationName}}</td>
                            </tr>

                            @if($itinerary->resort_transportation_id  == 1)
                                <tr>
                                    <th>Seaplane Date</th>
                                    <td>{{$itinerary->seaplane_name}}</td>
                                </tr>
                                <tr>
                                    <th>Seaplane Arrival Time</th>
                                    <td>{{$itinerary->seaplane_number}}</td>
                                </tr>
                                <tr>
                                    <th>Seaplane Departure Time</th>
                                    <td>{{$itinerary->seaplane_number}}</td>
                                </tr>
                            @elseif($itinerary->resort_transportation_id  == 2)
                                <tr>
                                    <th>Speedboat Name</th>
                                    <td>{{$itinerary->speedboat_name}}</td>
                                </tr>
                                <tr>
                                    <th>Captain Number</th>
                                    <td>{{$itinerary->captain_number}}</td>
                                </tr>
                                <tr>
                                    <th>Location</th>
                                    <td>{{$itinerary->captain_number}}</td>
                                </tr>
                                <tr>
                                    <th>Speedboat Date</th>
                                    <td>{{$itinerary->speedboat_date}}</td>
                                </tr>
                                <tr>
                                    <th>Speedboat Arrival Time</th>
                                    <td>{{$itinerary->speedboat_arrival_time}}</td>
                                </tr>
                                <tr>
                                    <th>Speedboat Departure Time</th>
                                    <td>{{$itinerary->speedboat_departure_time}}</td>
                                </tr>
                            @elseif($itinerary->resort_transportation_id  == 3)
                                <tr>
                                    <th>Domestic Flight Date</th>
                                    <td>{{ \Carbon\Carbon::parse($itinerary->domestic_flight_date)->format('d F Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Departure Time</th>
                                    <td>{{ \Carbon\Carbon::parse($itinerary->domestic_departure_time)->format('h:i a') }}</td>
                                </tr>
                                <tr>
                                    <th>Arrival Time</th>
                                    <td>{{ \Carbon\Carbon::parse($itinerary->domestic_arrival_time)->format('h:i a') }}</td>
                                </tr>
                            @endif
                            
                            <tr>
                                <th>Hotel ID</th>
                                <td>{{$itinerary->hotel_id}}</td>
                            </tr>
                            <tr>
                                <th>Hotel Name</th>
                                <td>{{$itinerary->hotel_name}}</td>
                            </tr>
                            <tr>
                                <th>Contact No</th>
                                <td>{{$itinerary->hotel_contact_no}}</td>
                            </tr>
                            <tr>
                                <th>Booking Reference</th>
                                <td>{{$itinerary->booking_reference}}</td>
                            </tr>
                            <tr>
                                <th>Hotel Address</th>
                                <td>{{$itinerary->hotel_address}}</td>
                            </tr>
                            <tr>
                                <th>Medical Center Name</th>
                                <td>{{$itinerary->medical_center_name}}</td>
                            </tr>
                            <tr>
                                <th>Medical Center Contact Number</th>
                                <td>{{$itinerary->medical_center_contact_no}}</td>
                            </tr>
                            <tr>
                                <th>Medical Type</th>
                                <td>{{$itinerary->medical_type}}</td>
                            </tr>
                            <tr>
                                <th>Approx Time</th>
                                <td>{{$itinerary->approx_time}}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class=" bg-themeGrayLight mb-md-4 mb-3">
                    <div class="card-title">
                        <h3>Meeting Schedules</h3>
                    </div>
                    <div class="table-responsive">
                        @if($itinerary->meetings->isEmpty())
                            <p class="text-center">No meetings scheduled.</p>
                        @else
                            @foreach($itinerary->meetings as $meeting)
                                <table class="table table-lable mb-1">
                                    <tr>
                                        <th>Meeting Title</th>
                                        <td>{{ $meeting->meeting_title }}</td>
                                    </tr>
                                    <tr>
                                        <th>Date & Time</th>
                                        <td>{{ \Carbon\Carbon::parse($meeting->meeting_date)->format('d F Y') }} - {{ \Carbon\Carbon::parse($meeting->meeting_time)->format('h:i a') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Meeting Link</th>
                                        <td>{{ $meeting->meeting_link }}</td>
                                    </tr>
                                    @php $participants = explode(',', $meeting->meeting_participant_ids); @endphp
                                    <tr>
                                        <th>Participants</th>
                                        <td>
                                            @foreach($participants as $participantId)
                                                @php 
                                                    $participantId = (int) $participantId;

                                                    $participantDetails = \App\Models\Employee::find($participantId);

                                                @endphp
                                                <span>{{$participantDetails ? $participantDetails->resortAdmin->full_name : 'N/A'}}</span>
                                                <div class="user-ovImg">
                                                    <div class="img-circle">
                                                        <img src="{{ $participantDetails ? Common::getResortUserPicture($participantDetails->Admin_Parent_id) : asset('images/default-user.png') }}" alt="user">
                                                    </div>
                                                </div>
                                            @endforeach
                                        </td>
                                    </tr>   
                                </table>
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
    <script type="text/javascript">
        $(document).ready(function() {
            $(".select2t-none").select2();
            $('#itiernariesTable tbody').empty();
            var viewForm = $('#itiernariesTable').DataTable({
                searching: false,
                bLengthChange: false,
                bFilter: true,
                bInfo: true,
                bAutoWidth: false,
                scrollX: true,
                iDisplayLength: 6,
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("people.onboarding.itinerary.list") }}',
                    type: 'GET',
                    data: function(d) {
                        var searchTerm = $('.search').val();
                        d.searchTerm = searchTerm;
                    }
                },
                columns: [
                    { data: 'employee_name', name: 'employee_name', className: 'text-nowrap'},
                    { data: 'arrival_date', name: 'arrival_date', className: 'text-nowrap'},
                    { data: 'arrival_time', name: 'arrival_time', className: 'text-nowrap'},
                    { data: 'action', name: 'Action', orderable: false, searchable: false }
                ]
            });
  
            $('.search').on('keyup', function() {
                viewForm.ajax.reload();
            });
        });
    </script>
@endsection
