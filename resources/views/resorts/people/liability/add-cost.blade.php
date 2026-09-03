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
                        <input type="text" id="date" class="form-control" placeholder="Select Date">
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <label for="employee_name" class="form-label">EMPLOYEE NAME</label>
                        <select class="form-select dd-native-select" id="employee_name"
                            aria-label="Default select example">
                            <option selected>Enter Employee Name</option>
                            <option value="1">aaa</option>
                            <option value="2">aaa</option>
                        </select>
                        <div class="dd" data-target="#employee_name">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Enter Employee Name</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Employee Name">
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value="Enter Employee Name"><span class="dd-nm">Enter Employee Name</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    <div class="dd-item" role="option" data-value="1"><span class="dd-nm">aaa</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    <div class="dd-item" role="option" data-value="2"><span class="dd-nm">aaa</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <label for="category" class="form-label">CATEGORY</label>
                        <select class="form-select dd-native-select" id="category" aria-label="Default select example">
                            <option selected>Select category</option>
                            <option value="1">aaa</option>
                            <option value="2">aaa</option>
                        </select>
                        <div class="dd" data-target="#category">
                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                <span class="dd-lbl">Select category</span>
                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="dd-panel" role="listbox" aria-label="Category">
                                <div class="dd-scroll">
                                    <div class="dd-item active" role="option" data-value="Select category"><span class="dd-nm">Select category</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    <div class="dd-item" role="option" data-value="1"><span class="dd-nm">aaa</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    <div class="dd-item" role="option" data-value="2"><span class="dd-nm">aaa</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                </div>
                            </div>
                        </div>
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
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        flatpickr('#date', {
            dateFormat: 'd/m/Y',
            allowInput: true,
            appendTo: document.body
        });
    });
</script>
@include('resorts._dropdown_script')
@endsection