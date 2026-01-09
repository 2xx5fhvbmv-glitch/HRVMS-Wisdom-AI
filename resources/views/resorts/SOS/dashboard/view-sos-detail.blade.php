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
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>SOS</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card incident-card">
                <div class="bg-themeGrayLight mb-md-4 mb-3">
                    <div class="card-title pb-md-3 mb-md-4">
                        <div class="row justify-content-between align-items-center g-md-3 g-1">
                            <div class="col">
                                <h3 class="text-nowrap mb-1">{{$getSOSDetails->getSos->name}}</h3>
                                {{-- <p>{{$getSOSDetails->getSos->description}}</p> --}}
                            </div>
                            <div class="col-auto">
                                <ul class="userDetailList-wrapper">
                                    <li><span>DATE:</span>{{date('d M Y', strtotime($getSOSDetails->date))}}</li>
                                    <li><span>TIME:</span>{{date('h:i A', strtotime($getSOSDetails->time))}}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="row g-lg-4 g-3 mb-3">
                        <div class="col-xl-4 col-md-6">
                            <div class="bg-white">
                                <h6>INITIATED BY:</h6>
                                <div class="d-flex align-items-center">
                                    <div class="img-circle userImg-block me-2">
                                        <img src="{{Common::getResortUserPicture($getSOSDetails->employee->Admin_Parent_id)}}" alt="user">
                                    </div>
                                    <div>
                                        <h5 class="fw-600">{{$getSOSDetails->employee->resortAdmin->full_name}}<span class="badge badge-themeNew">#{{$getSOSDetails->employee->Emp_id}}</span>
                                        </h5>
                                        <p>{{$getSOSDetails->employee->position->position_title}}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-md-4 pb-md-1 mb-3">
                        <h6 class="mb-2">DESCRIPTION:</h6>
                        <p>{{$getSOSDetails->getSos->description}}</p>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <tr>
                                <th>LOCATION:</th>
                                <td>{{$getSOSDetails->location}}</td>
                            </tr>
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

@endsection