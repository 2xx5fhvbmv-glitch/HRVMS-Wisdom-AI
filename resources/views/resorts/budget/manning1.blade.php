@extends('resorts.layouts.app')
@section('page_tab_title' ,"Consolidated Budget")

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
                        <h1>View Manning</h1>
                    </div>
                </div>
            </div>
        </div>

        <div> 
            <div class="card">
                <table class="table table-viewMannAccording  w-100">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-nowrap">Department</th>
                            <th class="text-nowrap">Positions</th>
                            <th class="text-nowrap">No. of position</th>
                            <th class="text-nowrap">Employee Name</th>
                            <th class="text-nowrap w-120">Rank</th>
                            <th class="text-nowrap w-120">Nation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($departmentsData)
                            @foreach($departmentsData as $deptData)
                                @if($deptData['positions']->isNotEmpty())
                                    @foreach($deptData['positions'] as $pos)
                                        <tr>
                                            <td>{{$deptData['department']->name}}</td>
                                            <td>{{ $pos->position_title }}</td>
                                            <td>{{ $pos->headcount ?? '00' }}</td>
                                            <td colspan="3" class="p-0">
                                                <table class="table m-0 table-borderless">
                                                    @if($pos->employees && count($pos->employees) > 0)
                                                        @foreach($pos->employees as $employee)
                                                            <tr>
                                                                <td>{{ $employee->first_name }} {{ $employee->last_name }}</td>
                                                                <td class="w-120">
                                                                    @php 
                                                                        $Rank = config('settings.Position_Rank');
                                                                        $AvilableRank = array_key_exists($employee->rank, $Rank) ? $Rank[$employee->rank] : '';
                                                                    @endphp
                                                                    {{$AvilableRank}}
                                                                </td>
                                                                <td class="w-120">{{ $employee->nationality }}</td>
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                    @if($pos->vacantcount)
                                                        @for($i=0; $i<$pos->vacantcount; $i++)
                                                            <tr>
                                                                <td colspan="3">
                                                                    <span class="badge bg-success">Vacant</span>
                                                                </td>
                                                            </tr>
                                                        @endfor
                                                    @endif
                                                </table>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Total:</th>
                            <th></th>
                            <th id="total">
                                {{ 0 }} <!-- Calculate total filled count dynamically -->
                            </th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')

@endsection
