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
                            <span>People</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto  ms-auto"><a class="btn btn-theme" href="#">Add New Employee</a></div> -->
                </div>
            </div>
            <div class="card">
                <div class="row g-md-4 g-3 mb-md-4 mb-3">
                    <div class="col-md-6 col-sm-6">
                        <label for="date" class="form-label">DATE</label>
                        <input type="text" id="date" class="form-control datepicker" placeholder="Select Date">
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <label for="employee_name" class="form-label">EMPLOYEE NAME</label>
                        <select class="form-select select2t-none" id="employee_name"
                            aria-label="Default select example">
                            <option selected>Enter Employee Name</option>
                            <option value="1">aaa</option>
                            <option value="2">aaa</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <label for="category" class="form-label">CATEGORY</label>
                        <select class="form-select select2t-none" id="category" aria-label="Default select example">
                            <option selected>Select category</option>
                            <option value="1">aaa</option>
                            <option value="2">aaa</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <label for="amount" class="form-label">AMOUNT</label>
                        <input type="text" id="amount" class="form-control" placeholder="Enter Amount">
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">DESCRIPTION</label>
                        <textarea rows="3" id="description" class="form-control" placeholder="Comments"></textarea>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="#" class="btn  btn-themeBlue btn-sm">Submit</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        });

        $('.select2t-none').select2({
        });
    });
</script>
@endsection