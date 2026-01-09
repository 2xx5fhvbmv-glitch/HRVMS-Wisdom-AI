@extends('resorts.layouts.app')
@section('page_tab_title' ,"Positions")

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>WORKFORCE PLANNING</span>
                        <h1>Positions</h1>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-collapse   ">
                    <thead>
                        <tr>
                            <th>Positions</th>
                            <th>No. of Vacancy</th>
                            <th>Rank</th>
                            <th>Nation</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($vacant_positions)
                            @foreach($vacant_positions as $pos)
                                <tr id="row-{{$pos->id}}" class="position-row"> <!-- Add a unique id for each main row -->
                                    <td>{{ $pos->position_title }}</td>
                                    <td> {{ $pos->headcount ?? '00' }} <span class="badge bg-vac">{{ $pos->vacantcount ?? '00' }} Vacant Available</span></td>
                                    <td></td>
                                    <td></td>
                                    <td>
                                        <button class="table-icon collapsed" data-bs-toggle="collapse"
                                            data-bs-target="#collapse-{{$pos->id}}" aria-expanded="false"
                                            aria-controls="collapse-{{$pos->id}}">
                                            <i class="fa-solid fa-angle-down"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Collapsed row for employees -->
                                @if($pos->employees && count($pos->employees) > 0)
                                    @foreach($pos->employees as $employee)
                                        <tr class="collapse employee-row" id="collapse-{{$pos->id}}" data-parent="#row-{{$pos->id}}">
                                            <td></td>
                                            <td>
                                                <div class="tableUser-block">
                                                    <div class="img-circle">
                                                        <img src="{{ Common::getResortUserPicture($employee->Admin_Parent_id);}}" alt="image">
                                                    </div>
                                                    <span>{{ $employee->first_name }} {{ $employee->last_name }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                @php $Rank = config( 'settings.Position_Rank');
                                                     $AvilableRank = array_key_exists($employee->rank, $Rank) ? $Rank[$employee->rank] : '';    
                                                @endphp 
                                                {{$AvilableRank}}
                                            </td>
                                            <td>{{ $employee->nationality }}</td>
                                            <td></td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Listen for collapse events
        document.querySelectorAll('.collapse').forEach(function(collapseRow) {
            const mainRowId = collapseRow.getAttribute('data-parent'); // Get the parent row id
            console.log(mainRowId);

            collapseRow.addEventListener('show.bs.collapse', function () {
                // Add the `in` class to the main row when the collapse is shown
                document.querySelector(`${mainRowId}`).classList.add('in');
            });

            collapseRow.addEventListener('hide.bs.collapse', function () {
                // Remove the `in` class from the main row when the collapse is hidden
                document.querySelector(`${mainRowId}`).classList.remove('in');
            });
        });
    });
</script>
@endsection
