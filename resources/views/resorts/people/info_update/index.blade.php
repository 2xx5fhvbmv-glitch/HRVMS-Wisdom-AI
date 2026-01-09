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
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select select2" name="department" data-placeholder="Management">
                                <option></option>
                                @foreach($departments as $department)
                                    <option value="{{$department->id}}"  @if(request()->get('department') == $department->id) selected @endif>{{$department->name}} ({{$department->code}})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select select2" name="position" data-placeholder="Position">
                                <option></option>
                                 @foreach($positions as $position)
                                    <option value="{{$position->id}}" @if(request()->get('position') == $position->id) selected @endif>{{$position->position_title}} -({{$position->code}})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select class="form-select select2" name="status" data-placeholder="Status">
                                <option></option>
                                <option value="Pending"  @if(request()->get('status') =='Pending') selected @endif>Pending</option>
                                <option value="Approved" @if(request()->get('status') =='Approved') selected @endif>Approved</option>
                                <option value="Rejected" @if(request()->get('status') =='Rejected') selected @endif>Rejected</option>
                            </select>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" id="confirmRejectBtn" class="btn btn-danger">Reject</button>
            </div>
            
        </div>
    </div>
</div>

@endsection

@section('import-css')
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
                 toastr.error("something went wrong.","Error",{
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
            $form.find('.select2').val(null).trigger('change');
            loadUpdateRequests();
        });
});
</script>
@endsection


