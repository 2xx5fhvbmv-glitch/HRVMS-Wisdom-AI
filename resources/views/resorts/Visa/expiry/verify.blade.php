 @extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
<style>
    #visa-verify-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #visa-verify-hero { padding-bottom: 0; }
    }
</style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="visa-verify-hero">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Visa Management</span>
                            <h1>{{$page_title}}</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto ms-auto"><a class="btn btn-theme">btn</a></div> -->
                </div>
            </div>

            <div class="card">
                <div class="card-header border-0 pb-0">


                    <div class="row g-md-3 g-2 align-items-center justify-content-between">
                        <div class="col-auto">
                            <p>Please verify the details extracted from the uploaded screenshot(s) below</p>
                        </div>
                        <div class="col-xl-3 col-lg-4 col-sm-6  ">
                            <div class="input-group">
                                <input type="search" class="form-control Search" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="exp-Date-userbox expiry-dat-box">
                        <div class="row align-items-lg-center">
                               <div class="tab-content" id="myTabContent">
                                    <div class="tab-pane fade show active" id="Quota_Slot_Fee" role="tabpanel" aria-labelledby="tab1">
                                        <table id="ExpiryIndex" class="table">
                                        
                                        </table>
                                    </div>
                                </div>
                        </div>
                    </div>

                   
                  

                </div>
                <div class="card-footer mt-3 d-flex align-items-center justify-content-between">
                    <p class="fw-600 mb-0" id="verifySelectedCount">0 selected</p>
                    <button type="button" class="btn eb-btn-primary btn-sm SubmitVerified">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ExpiryUpdate-modal" tabindex="-1" aria-labelledby="quotaslot-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Payment Type </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="VisaQuotaslot" data-parsley-validate>
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="emp_id" id="QuotaSlot_emp_id" class="form-control" placeholder="Enter Employee ID">
                        <input type="hidden" name="flag" id="QuotaSlot_flag" class="form-control" placeholder="Enter Flag">

                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label d-block">Payment Type<span class="red-mark">*</span></label>
                                <div class="form-check form-check-inline">
                                    <input type="radio" name="payment_type" id="payment_type_lumpsum" class="form-check-input"
                                        value="Lumpsum" required data-parsley-errors-container="#payment_type_error">
                                    <label class="form-check-label" for="payment_type_lumpsum">Lumpsum</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input type="radio" name="payment_type" id="payment_type_installment" class="form-check-input"
                                        value="Installment" required data-parsley-errors-container="#payment_type_error">
                                    <label class="form-check-label" for="payment_type_installment">Installment</label>
                                </div>
                                <div id="payment_type_error" class="text-danger"></div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn eb-btn-neutral ms-auto">Cancel</a>
                        <button type="submit" class="btn eb-btn-primary FileUploadButton" href="javascript:void(0)">Submit</button>
                    </div>
                </form>
            </div>
        </div>
   </div>

   {{-- Per-column edit (Work Permit / Slot / Visa / Insurance): amount + expiry + status --}}
   <div class="modal fade" id="ExpiryEdit-modal" tabindex="-1" aria-hidden="true">
       <div class="modal-dialog modal-dialog-centered">
           <div class="modal-content">
               <div class="modal-header">
                   <h5 class="modal-title"><span id="ExpiryEdit_verb">Edit</span> <span id="ExpiryEdit_typeLabel"></span></h5>
                   <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
               </div>
               <form id="ExpiryEditForm" data-parsley-validate>
                   @csrf
                   <div class="modal-body">
                       <input type="hidden" name="type" id="ExpiryEdit_type">
                       <input type="hidden" name="id" id="ExpiryEdit_id">
                       <input type="hidden" name="emp_id" id="ExpiryEdit_emp">
                       <div class="form-group mb-3">
                           <label class="form-label">Amount (MVR)</label>
                           <input type="number" step="0.01" min="0" name="amount" id="ExpiryEdit_amount" class="form-control" placeholder="0.00">
                       </div>
                       <div class="form-group mb-3">
                           <label class="form-label">Expiry / Due Date</label>
                           <input type="date" name="expiry_date" id="ExpiryEdit_date" class="form-control">
                       </div>
                       <div class="form-group mb-3" id="ExpiryEdit_statusWrap" style="display:none;">
                           <label class="form-label d-block">Status</label>
                           <div id="ExpiryEdit_statusOptions"></div>
                       </div>
                   </div>
                   <div class="modal-footer">
                       <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn eb-btn-neutral ms-auto">Cancel</a>
                       <button type="submit" class="btn eb-btn-primary">Save</button>
                   </div>
               </form>
           </div>
       </div>
   </div>
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>

$(document).ready(function()
{
    // When arriving from the Xpat-sync flow, pre-filter to the employee whose
    // documents were just updated (?search=<name>).
    var _preSearch = new URLSearchParams(window.location.search).get('search');
    if (_preSearch) { $('.Search').val(_preSearch); }
    FetchIndexDate();
    flatpickr('#datepickerXpact', {
        dateFormat: 'Y-m-d',
        allowInput: true,
        appendTo: document.body
    });
    $(document).on('click', '.Categories', function(e) {
        // Remove 'active' from all

        $('.Categories').removeClass('active');

        // Add 'active' to clicked one
        $(this).addClass('active');

        // Fetch flag and call function
        var flag = $(this).data('flag');
        FetchIndexDate(flag);
    });
    $(document).on('keyup', '.Search', function() 
    {
        var flag = 'all';
        FetchIndexDate(flag);
    });

    $(document).on('click',".EditvisaDate",function(e)
    {
        e.preventDefault();
        var id = $(this).data('id');
        $("#ExpiryUpdate-modal").modal('show');
    });

    // --- Per-column edit (amount + expiry + status) ----------------------
    var EXPIRY_STATUS_OPTS = {
        visa: ['Pending', 'Paid'],
        work_permit: ['Paid', 'Unpaid'],
        slot: ['Paid', 'Unpaid'],
        insurance: []
    };
    var EXPIRY_TYPE_LABEL = { visa: 'Visa', work_permit: 'Work Permit', slot: 'Slot Payment', insurance: 'Insurance' };

    $(document).on('click', '.EditExpiry', function () {
        var type = $(this).data('type');
        var id = $(this).data('id');
        var isAdd = !id;
        $('#ExpiryEdit_type').val(type);
        $('#ExpiryEdit_id').val(id || '');
        $('#ExpiryEdit_emp').val($(this).data('emp') || '');
        $('#ExpiryEdit_amount').val($(this).data('amount'));
        $('#ExpiryEdit_date').val($(this).data('date'));
        $('#ExpiryEdit_verb').text(isAdd ? 'Add' : 'Edit');
        $('#ExpiryEdit_typeLabel').text(EXPIRY_TYPE_LABEL[type] || '');

        var opts = EXPIRY_STATUS_OPTS[type] || [];
        var cur = String($(this).data('status') || '');
        if (opts.length) {
            var html = '';
            opts.forEach(function (o) {
                html += '<div class="form-check form-check-inline">'
                      + '<input type="radio" class="form-check-input" name="status" id="ExpEdit_st_' + o + '" value="' + o + '" ' + (cur === o ? 'checked' : '') + '>'
                      + '<label class="form-check-label" for="ExpEdit_st_' + o + '">' + o + '</label></div>';
            });
            $('#ExpiryEdit_statusOptions').html(html);
            $('#ExpiryEdit_statusWrap').show();
        } else {
            $('#ExpiryEdit_statusOptions').html('');
            $('#ExpiryEdit_statusWrap').hide();
        }
        $('#ExpiryEdit-modal').modal('show');
    });

    // --- Select rows + Submit (verified → pushes to employee details page) ---
    $(document).on('change', '.VerifyCheck', function () {
        $('#verifySelectedCount').text($('.VerifyCheck:checked').length + ' selected');
    });

    $(document).on('click', '.SubmitVerified', function () {
        var ids = $('.VerifyCheck:checked').map(function () { return $(this).val(); }).get();
        if (!ids.length) {
            toastr.error('Please select at least one employee.', 'Error', { positionClass: 'toast-bottom-right' });
            return;
        }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: "{{ route('resort.visa.SubmitVerifiedDetails') }}",
            type: 'POST',
            data: { employee_ids: ids, _token: '{{ csrf_token() }}' }
        }).done(function (res) {
            if (res && res.success) {
                toastr.success(res.msg || 'Submitted.', 'Success', { positionClass: 'toast-bottom-right' });
                // Submitted employees are now excluded server-side → they vanish on reload.
                FetchIndexDate($('.Categories.active').data('flag') || 'all');
                $('#verifySelectedCount').text('0 selected');
            } else {
                toastr.error((res && res.errors && res.errors.message) ? res.errors.message : 'Submit failed.', 'Error', { positionClass: 'toast-bottom-right' });
            }
        }).fail(function (xhr) {
            var m = 'Submit failed.';
            try { m = xhr.responseJSON.errors.message || xhr.responseJSON.message || m; } catch (e) {}
            toastr.error(m, 'Error', { positionClass: 'toast-bottom-right' });
        }).always(function () { $btn.prop('disabled', false); });
    });

    $(document).on('submit', '#ExpiryEditForm', function (e) {
        e.preventDefault();
        var $btn = $(this).find('button[type=submit]');
        $btn.prop('disabled', true);
        $.ajax({
            url: "{{ route('resort.visa.UpdateExpiryRecord') }}",
            type: 'POST',
            data: $(this).serialize()
        }).done(function (res) {
            $('#ExpiryEdit-modal').modal('hide');
            if (res && res.success) {
                toastr.success(res.msg || 'Updated.', 'Success', { positionClass: 'toast-bottom-right' });
                FetchIndexDate($('.Categories.active').data('flag') || 'all');
            } else {
                var m = (res && res.errors && res.errors.message) ? res.errors.message : 'Update failed.';
                toastr.error(m, 'Error', { positionClass: 'toast-bottom-right' });
            }
        }).fail(function (xhr) {
            $('#ExpiryEdit-modal').modal('hide');
            var m = 'Update failed.';
            try { m = xhr.responseJSON.errors.message || xhr.responseJSON.message || m; } catch (e) {}
            toastr.error(m, 'Error', { positionClass: 'toast-bottom-right' });
        }).always(function () { $btn.prop('disabled', false); });
    });

});

function FetchIndexDate(flag)
{
        if($.fn.DataTable.isDataTable('#ExpiryIndex'))
        {
            $('#ExpiryIndex').DataTable().destroy();
        }
       var productTable = $('#ExpiryIndex').DataTable({
            searching: false,
            bLengthChange: false,
            bInfo: true,
            bAutoWidth: false,
            scrollX: false,
            iDisplayLength: 15,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('resort.visa.VerifyDetails') }}",
                type: 'GET',
                data: function(d)
                {
                    d.flag = flag||'all';
                    d.search = $('.Search').val();
                    d.status = $("#statusFilter").val();
              
                }
            },
            columns: [
                {
                    data: 'profile_view',
                    name: 'profile_view',
                    orderable: false,
                    searchable: false
                }
            ],
            headerCallback: function(thead, data, start, end, display) {
                // Hide the header row
                $(thead).remove();
            }
        });
}
</script>
@endsection
