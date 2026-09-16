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
                <form id="AddLiabilityCostForm" novalidate>
                    @csrf
                    <div class="row g-md-4 g-3 mb-md-4 mb-3">
                        <div class="col-md-6 col-sm-6">
                            <label for="cost_date" class="form-label">DATE <span class="red-mark">*</span></label>
                            <input type="text" name="cost_date" id="cost_date" class="form-control" placeholder="Select Date" required>
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <label for="employee_id" class="form-label">EMPLOYEE NAME <span class="red-mark">*</span></label>
                            <select class="form-select dd-native-select" id="employee_id" name="employee_id"
                                aria-label="Default select example" required>
                                <option value="" selected>Enter Employee Name</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ optional($emp->resortAdmin)->full_name ?? $emp->Emp_id }}</option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#employee_id">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Enter Employee Name</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Employee Name">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find an employee…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Enter Employee Name</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @foreach($employees as $emp)
                                            <div class="dd-item" role="option" data-value="{{ $emp->id }}"><span class="dd-nm">{{ optional($emp->resortAdmin)->full_name ?? $emp->Emp_id }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <label for="category" class="form-label">CATEGORY <span class="red-mark">*</span></label>
                            <select class="form-select dd-native-select" id="category" name="category" aria-label="Default select example" required>
                                <option value="" selected>Select category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#category">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select category</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Category">
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select category</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @foreach($categories as $cat)
                                            <div class="dd-item" role="option" data-value="{{ $cat }}"><span class="dd-nm">{{ $cat }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <label for="amount" class="form-label">AMOUNT <span class="red-mark">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control" placeholder="Enter Amount" required>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">DESCRIPTION</label>
                            <textarea rows="3" name="description" id="description" class="form-control" placeholder="Comments"></textarea>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-themeBlue btn-sm" id="AddLiabilityCostSubmit">Submit</button>
                    </div>
                </form>
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
        flatpickr('#cost_date', {
            dateFormat: 'd/m/Y',
            allowInput: true,
            appendTo: document.body
        });
    });

    $('#AddLiabilityCostForm').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#AddLiabilityCostSubmit');
        var costDate = $('#cost_date').val();
        var parsedDate = costDate ? costDate.split('/').reverse().join('-') : '';

        $btn.prop('disabled', true).text('Submitting...');
        $.ajax({
            url: "{{ route('people.liability.storeCost') }}",
            type: "POST",
            data: {
                _token: '{{ csrf_token() }}',
                employee_id: $('#employee_id').val(),
                cost_date: parsedDate,
                category: $('#category').val(),
                amount: $('#amount').val(),
                description: $('#description').val()
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, 'Success', { positionClass: 'toast-bottom-right' });
                    window.location.href = response.redirect_url;
                } else {
                    toastr.error(response.message || 'Failed to add cost', 'Error', { positionClass: 'toast-bottom-right' });
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to add cost';
                toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
            },
            complete: function () {
                $btn.prop('disabled', false).text('Submit');
            }
        });
    });
</script>
@include('resorts._dropdown_script')
@endsection