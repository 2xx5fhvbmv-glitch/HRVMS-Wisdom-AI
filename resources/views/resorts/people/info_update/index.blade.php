@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #info-update-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #info-update-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="info-update-hero">
            <div class="row  g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>People</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>
        <div class="card card-infoUpdatePeopleEmpPage">
            <div class="card-header">
                <form id="filterForm" method="GET" action="{{route('people.info-update.list')}}">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-2 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" name="search" class="form-control "
                                    placeholder="Search Employee by name, ID" />
                                <i class="fa-solid fa-search" style="top:23px!important"></i>
                            </div>
                        </div>
                        @php
                            $iuSelectedDept = $departments->firstWhere('id', request()->get('department'));
                            $iuSelectedPos = $positions->firstWhere('id', request()->get('position'));
                            $iuStatusLabels = ['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected'];
                            $iuSelectedStatusLbl = $iuStatusLabels[request()->get('status')] ?? null;
                        @endphp
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="info-update-department" name="department" data-placeholder="Management">
                                <option></option>
                                @foreach($departments as $department)
                                    <option value="{{$department->id}}"  @if(request()->get('department') == $department->id) selected @endif>{{$department->name}} ({{$department->code}})</option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#info-update-department">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">{{ $iuSelectedDept ? $iuSelectedDept->name.' ('.$iuSelectedDept->code.')' : 'Management' }}</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Department">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a department…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item{{ $iuSelectedDept ? '' : ' active' }}" role="option" data-value=""><span class="dd-nm">Management</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @foreach($departments as $department)
                                            <div class="dd-item{{ request()->get('department') == $department->id ? ' active' : '' }}" role="option" data-value="{{$department->id}}"><span class="dd-nm">{{$department->name}} ({{$department->code}})</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="info-update-position" name="position" data-placeholder="Position">
                                <option></option>
                                 @foreach($positions as $position)
                                    <option value="{{$position->id}}" @if(request()->get('position') == $position->id) selected @endif>{{$position->position_title}} -({{$position->code}})</option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#info-update-position">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">{{ $iuSelectedPos ? $iuSelectedPos->position_title.' -('.$iuSelectedPos->code.')' : 'Position' }}</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Position">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item{{ $iuSelectedPos ? '' : ' active' }}" role="option" data-value=""><span class="dd-nm">Position</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @foreach($positions as $position)
                                            <div class="dd-item{{ request()->get('position') == $position->id ? ' active' : '' }}" role="option" data-value="{{$position->id}}"><span class="dd-nm">{{$position->position_title}} -({{$position->code}})</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select dd-native-select" id="info-update-status" name="status" data-placeholder="Status">
                                <option></option>
                                <option value="Pending"  @if(request()->get('status') =='Pending') selected @endif>Pending</option>
                                <option value="Approved" @if(request()->get('status') =='Approved') selected @endif>Approved</option>
                                <option value="Rejected" @if(request()->get('status') =='Rejected') selected @endif>Rejected</option>
                            </select>
                            <div class="dd" data-target="#info-update-status">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">{{ $iuSelectedStatusLbl ?? 'Status' }}</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Status">
                                    <div class="dd-scroll">
                                        <div class="dd-item{{ $iuSelectedStatusLbl ? '' : ' active' }}" role="option" data-value=""><span class="dd-nm">Status</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item{{ request()->get('status') == 'Pending' ? ' active' : '' }}" role="option" data-value="Pending"><span class="dd-nm">Pending</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item{{ request()->get('status') == 'Approved' ? ' active' : '' }}" role="option" data-value="Approved"><span class="dd-nm">Approved</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        <div class="dd-item{{ request()->get('status') == 'Rejected' ? ' active' : '' }}" role="option" data-value="Rejected"><span class="dd-nm">Rejected</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <input type="text" class="form-control datepicker" id="from_date1" placeholder="DD/MM/YYYY" name="date" data-parsley-required="true"  data-parsley-errors-container="#from-date-error1">
                        </div>
                        <div class="col-xl-2 col-md-2 col-sm-3 col-6">
                            <button class="btn btn-themeBlue btn-sm" id="clearFilter">Clear Filter</button>
                        </div>
                    </div>
                </form>
            </div>
           <div id="ajax-data-wrapper"></div>
        </div>

    </div>
</div>

<div class="modal fade" id="reqApproval-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-reqApproval">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Request Approval</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="ajax-modal-body">
                
            </div>
            
        </div>
    </div>
</div>

<div class="modal fade" id="reqReject-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-reqReject">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reason for Rejection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{route('people.info-update.request-rejected')}}" id="requestRejected">
                    @csrf
                    <input type="hidden" name="id" value="">
                    <input type="hidden" name="status" value="rejected">
                    <textarea id="rejectionReason" class="form-control" name="reject_reason" rows="3" placeholder="Enter a reason (required)" required></textarea>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn eb-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" id="confirmRejectBtn" class="btn eb-btn-critical">Reject</button>
            </div>
            
        </div>
    </div>
</div>

@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')

<script>
    $(document).on('click', '.open-ajax-modal', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                if(response.status == 'success'){
                    $('#ajax-modal-body').html(response.html);
                }
            },
            error: function() {
                 toastr.error("Something went wrong.","Error",{
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        var rejectModal = document.getElementById('reqReject-modal');

        rejectModal.addEventListener('show.bs.modal', function (event) {

            var button = event.relatedTarget; 
            var empId = button.getAttribute('data-id'); 

            var approvalModal = bootstrap.Modal.getInstance(document.getElementById('reqApproval-modal'));
            if (approvalModal) {
                approvalModal.hide();
            }

            var idInput = rejectModal.querySelector('input[name="id"]');
            if (idInput) {
                idInput.value = empId;
            }
        });
    });
    
   $(document).on('click', '#confirmRejectBtn', function () {
        var $form = $('#requestRejected');

        // Validate the reason is filled
        
        var reason = $form.find('#rejectionReason').val().trim();

        if (reason === null || reason === '') {
            toastr.error("Rejection reason is required.","Error",{
                    positionClass: 'toast-bottom-right'
                });
            return;
        }
        
        var formData = new FormData($form[0]);
        var url = $form.attr('action');
        $.ajax({
            url: url,
            type: "POST",
            data: formData,
            processData: false, 
            contentType: false,
            success: function (result) {
                $('#reqReject-modal').modal('hide');
                loadUpdateRequests();
                toastr.success("Request rejected successfully.", "Success", {
                    positionClass: 'toast-bottom-right'
                });
            },
            error: function () {
                toastr.error("Something went wrong", "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });

   $(document).on('click', '#update-info-btn', function () {
        var url = $(this).data('url');
        $.ajax({
            type: "GET",
            url: url,
            success: function (response) {
                $('#reqApproval-modal').modal('hide');
                toastr.success(response.message, "Success", {
                    positionClass: 'toast-bottom-right'
                });
                loadUpdateRequests();
            },
            error: function () {
                toastr.error("Something went wrong", "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
});
</script>

<script>

    function loadUpdateRequests(page = 1) {
        const $form = $('#filterForm');
        const data = $form.serialize() + '&page=' + page;

        $.ajax({
            url: "{{ route('people.info-update.list') }}",
            method: 'GET',
            data: data,
            success: function (res) {
                if (res.status === 'success') {
                    $('#ajax-data-wrapper').html(res.html);
                }
            },
            error: function () {
                $('#ajax-data-wrapper').html('<div class="alert alert-danger">Failed to load data.</div>');
            }
        });
    }

    // NEW: Listen to pagination link clicks
    $(document).on('click', '.pagination a', function (e) {
        e.preventDefault();
        const url = new URL($(this).attr('href'), window.location.origin);
        const page = url.searchParams.get('page') || 1;

        loadUpdateRequests(page);
    });

  $(document).ready(function () {
    const $form = $('#filterForm');

    loadUpdateRequests();
     initDatePicker();

    $form.on('submit', function (e) {
        e.preventDefault(); 
        loadUpdateRequests(); 
    });

    $form.find('input[type="search"]').on('keyup', function () {
        $form.submit(); 
    });


    $form.find('select, input.datepicker').on('change', function () {
        $form.submit();
    });

      function initDatePicker() {
            if ($.fn.datepicker) {
                $('.datepicker').datepicker({
                    format: 'dd/mm/yyyy',
                    autoclose: true
                }).on('changeDate', function () {
                    $(this).parsley().validate(); // Trigger validation on date change
                });
            }
        }


        $('#clearFilter').on('click', function() {
            const $form = $('#filterForm');
            $form[0].reset();
            $form.find('.dd-native-select').val(null).trigger('change');
            loadUpdateRequests();
        });
});
</script>
@include('resorts._dropdown_script')
@endsection


