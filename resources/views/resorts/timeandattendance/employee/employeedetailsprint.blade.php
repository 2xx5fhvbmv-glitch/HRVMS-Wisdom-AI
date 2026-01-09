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
            <div class="page-hedding page-appHedding">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Time And Attendance</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card" id="tablePrint">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-lg">
                            <div class="empDetails-user">
                                <div class="img-circle"><img src="{{   $employee->profile_picture}}" alt="user"></div>
                                <div>
                                    <h4>{{$employee->name }} <span class="badge badge-themeNew">{{ $employee->Emp_Code }}</span></h4>
                                    <p>{{$employee->Position }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-md-5 col-sm-6 col ms-auto">

                        </div>
                        <div class="col-auto">
                            <a href="#" class="btn btn-themeSkyblue btn-sm" id="printButton">Print</a>
                        </div>
                    </div>
                </div>
                <div class="empDetails-leave mb-4">
                    <div class="card-title">
                        <div class="row g-2 align-items-center">
                            <div class="col">
                                <h3>Leave Balance</h3>
                            </div>
                            <div class="col-auto"><span class="badge badge-themeNew">Total:{{ $TotalSum }}</span></div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th>Used / Allocated Days</th>
                                </tr>
                            </thead>
                            <tbody>
                               @php
                                    // Step 1: Group by leave_type and calculate totals
                                    $grouped = $leave_categories->groupBy('leave_type')->map(function ($items) {
                                        return [
                                            'leave_type' => $items->first()->leave_type,
                                            'used_days' => $items->sum('ThisYearOfused_days'),
                                            'allocated_days' => $items->sum('allocated_days'),
                                        ];
                                    });

                                    // Step 2: Chunk the grouped results into sets of 4
                                    $chunkedLeaveCategories = $grouped->chunk(4);
                                @endphp

                                @foreach($chunkedLeaveCategories as $chunk)
                                    @foreach($chunk as $item)
                                        <tr>
                                            <td>{{ $item['leave_type'] }}</td>
                                            <td>{{ $item['used_days'] }}/{{ $item['allocated_days'] }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col">
                            <div class="card-title pb-0 mb-0 border-0">
                                <h3>Attendance History</h3>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="list-main">
                    <div class="table-responsive">
                        <table class="table table-collapseNew table-applicants">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Shift</th>
                                    <th>Check in Time</th>
                                    <th>Check Out Time</th>
                                    <th>Over Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($AttendanceHistroy->isNotEmpty())
                                    @foreach ($AttendanceHistroy as $item)
                                        <tr>
                                            <td>{{ $item->date }}</td>
                                            <td>{{ $item->shift }}</td>
                                            <td>{{ $item->CheckInTime }}</td>
                                            <td>{{ $item->CheckOutTime }}</td>
                                            <td>{{ $item->OverTime }} </td>
                                            <td>{!! $item->Status !!}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="7" style="text-align: center"> No Records Found.. </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
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

         $(document).ready(function() {
        // When the button is clicked
        $('#printButton').click(function() {


            $("#tablePrint").print();
        });
    });
    </script>

    @endsection
