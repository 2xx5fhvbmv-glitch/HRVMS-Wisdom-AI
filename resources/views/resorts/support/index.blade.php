@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    /* Same requested push as the other module dashboards/pages (Payroll /
       Talent Acquisition / People / Time and Attendance / Leave /
       Performance / Learning / Accommodation / Incident / Survey /
       Reports) — extra breathing room between the hero and the content
       card below it, scoped to this page (.page-hedding's own
       margin-bottom is shared by every page's hero). padding-bottom, not
       margin: adjacent sibling margins collapse to the larger of the two
       rather than summing. Below Bootstrap's sm breakpoint the extra
       padding pushes the card into the teal hero curve's rounded
       bottom-left corner (body::before, border-radius 0 0 50px 50px) —
       same collision found on Payroll — neutralized below 576px. */
    #support-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #support-hero { padding-bottom: 0; }
    }
</style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="support-hero">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Support</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto ms-auto">
                        <a class="btn eb-btn-accent @if(Common::checkRouteWisePermission('support.index',config('settings.resort_permissions.create')) == false) d-none @endif" href="#raiseATicket-modal" data-bs-toggle="modal">
                            Raise a Ticket
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control " placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        {{-- Date filter hidden per request — keep markup commented so it's
                             easy to restore. The init JS still references #dateFilter so
                             we keep a hidden input around to avoid undefined-val errors. --}}
                        {{--
                        <div class="col-xl-2 col-lg-4 col-md-5  col-6">
                            <input type="text" name="dateFilter" id="dateFilter" class="form-control datepicker"/>
                        </div>
                        --}}
                        <input type="hidden" name="dateFilter" id="dateFilter" value=""/>
                        <div class="col-xl-2 col-md-4 col-sm-4 col-6">
                            <select class="form-select select2t-none" id="statusFilter">
                                <option value="">Select Status</option>
                                <option value="New">New</option>
                                <option value="On Hold">On Hold</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Close">Close</option>
                            </select>
                        </div>
                    </div>
                </div>
                <!-- data-Table  -->
                <table id="table-billingInvoiceSupportList" class="table data-Table  table-billingInvoiceSupportList w-100">
                    <thead>
                        <tr>
                            <th>Ticket Id</th>
                            <th>Customer Name</th>
                            <th>Category</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                       
                    </tbody>
                </table>
            </div>
        </div>
    </div>

   <!-- Raise a Ticket Modal -->
    <div class="modal fade" id="raiseATicket-modal" tabindex="-1" aria-labelledby="raiseATicketLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-raiseATicket">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Raise a Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="raiseTicketForm" enctype="multipart/form-data">
    @csrf
                    <div class="modal-body pb-md-4">
                        <div class="row g-md-4 g-3">
                            {{-- Support Preference hidden per request — keep markup commented
                                 so it's easy to restore later. --}}
                            {{--
                            <div class="col-12">
                                <label for="category" class="form-label">Support Preference </label>
                                @foreach($supportPreferences as $key => $preference)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="supportPreference" value="{{$preference}}"
                                            id="supportPreference-{{$key}}" required>
                                        <label class="form-check-label" for="supportPreference-{{$key}}">
                                            {{$preference}}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            --}}
                            <div class="col-12">
                                <label for="category" class="form-label">Category <span class="req_span">*</span></label>
                                <select name="category" id="category" class="form-select select2t-modal" required>
                                    <option value="">Select Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{$category->id}}">{{$category->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="subject" class="form-label">Subject <span class="req_span">*</span></label>
                                <input type="text" class="form-control" id="subject" name="subject" placeholder="Enter subject" required>
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">Description <span class="req_span">*</span></label>
                                <textarea class="form-control" id="description" name="description" placeholder="Type Here..." rows="3" required></textarea>
                            </div>
                            <div class="col-12">
                                <div class="bg-themeGrayLight">
                                    <div class="uploadFileNew-block" id="dropzone-area">
                                        <img src="{{ URL::asset('resorts_assets/images/upload.svg')}}" alt="icon">
                                        <h5>Upload Scanned Documents</h5>
                                        <p>Browse or Drag the file here</p>
                                        <input type="file" name="attachments[]" id="attachments" multiple hidden>
                                    </div>
                                    <div class="file-list mt-2 ps-3" id="fileList"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn eb-btn-neutral ms-auto" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn eb-btn-primary">Submit</button>
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
    $(document).ready(function () {
        $('.select2t-none').select2();
        $('.select2t-modal').select2();
        flatpickr('#dateFilter', {
            dateFormat: 'd/m/Y',
            allowInput: true,
            appendTo: document.body
        });
        loadSupportlist();
        $('#searchInput, #dateFilter, #statusFilter').on('keyup change', function () {
            loadSupportlist();
        });

        $('#raiseTicketForm').submit(function(e) {
            e.preventDefault(); // Prevent default form submission

            let formData = new FormData(this);
            formData.append('_token', '{{ csrf_token() }}');

            $.ajax({
                url: "{{ route('support.store') }}", // Ensure this route is defined in web.php
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('.eb-btn-primary').prop('disabled', true).text('Submitting...');
                },
                success: function(response) {
                    if (response.success) {
                        // Defer table reload until AFTER the modal hide animation
                        // completes, otherwise focus/scroll churn can suppress
                        // the AJAX or interleave with select2 teardown.
                        $('#raiseATicket-modal').one('hidden.bs.modal', function () {
                            $('#raiseTicketForm')[0].reset();
                            $('#raiseTicketForm .select2t-modal').val('').trigger('change');
                            loadSupportlist();
                        });
                        $('#raiseATicket-modal').modal('hide');

                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error(response.message || 'Failed to submit ticket.', 'Error', {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr) {
                    let errors = xhr.responseJSON?.errors;
                    let errorMsg = "Something went wrong!";
                    
                      
                    if (errors) {
                        $.each(errors, function(field, messages) {
                            // Clean field name (e.g., attachments.0 → attachments)
                            let cleanField = field.replace(/\.\d+$/, '');

                            $.each(messages, function(index, message) {
                                // Replace field name in message if needed
                                let cleanMessage = message.replace(field, cleanField);
                                
                                toastr.error(cleanMessage, "Validation Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            });
                        });
                    } else {
                        toastr.error(errorMsg, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                complete: function() {
                    $('.eb-btn-primary').prop('disabled', false).text('Submit');
                }
            });
        });
    });
    function loadSupportlist() {
        if ($.fn.DataTable.isDataTable('#table-billingInvoiceSupportList')) {
            $('#table-billingInvoiceSupportList').DataTable().destroy();
        }

        var table = $('#table-billingInvoiceSupportList').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 10,
            processing: true,
            serverSide: true,
            ordering: true, // Ensure ordering is enabled
            order: [[4, 'desc']], // Default sort order
            ajax: {
                url: "{{ route('support.get.data') }}",
                type: 'GET',
                cache: false, // prevent the browser from serving the previous list
                data: function(d) {
                    d.searchTerm = $('#searchInput').val();
                    d.status = $('#statusFilter').val();

                    let selectedDate = $('#dateFilter').val();
                    if (selectedDate) {
                        let parts = selectedDate.split('/');
                        d.date = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                    } else {
                        d.date = '';
                    }
                },
                error: function (xhr) {
                    // Surface backend errors instead of a silent "No data".
                    console.error('Support list AJAX failed', xhr.status, xhr.responseText);
                    toastr.error(
                        'Failed to load support tickets (' + xhr.status + '). Check console for details.',
                        'Error',
                        { positionClass: 'toast-bottom-right' }
                    );
                }
            },
            columns: [
                { data: 'ticketID', name: 'ticketID', orderable: true },
                { data: 'employee_name', name: 'employee_name', orderable: false, searchable: false },
                { data: 'category', name: 'category', orderable: true },
                { data: 'subject', name: 'subject', orderable: true },
                { data: 'created_at', name: 'created_at', orderable: true },
                { data: 'status', name: 'status', orderable: true },
                { data: 'action', name: 'action', orderable: false, searchable: false },
                

            ],
        });
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dropzone = document.getElementById('dropzone-area');
        const fileInput = document.getElementById('attachments');
        const fileList = document.getElementById('fileList');

        // Style on drag over
        dropzone.addEventListener('dragover', function (e) {
            e.preventDefault();
            dropzone.classList.add('drag-over');
        });

        dropzone.addEventListener('dragleave', function (e) {
            dropzone.classList.remove('drag-over');
        });

        // Handle drop
        dropzone.addEventListener('drop', function (e) {
            e.preventDefault();
            dropzone.classList.remove('drag-over');

            const files = e.dataTransfer.files;
            fileInput.files = files; // This lets the <input> submit them
            displayFileNames(files);
        });

        // Handle normal file selection
        fileInput.addEventListener('change', function () {
            displayFileNames(fileInput.files);
        });

        // Show uploaded file names
        function displayFileNames(files) {
            fileList.innerHTML = '';
            Array.from(files).forEach(file => {
                const li = document.createElement('div');
                li.textContent = `📎 ${file.name}`;
                fileList.appendChild(li);
            });
        }

        // Trigger file input when clicked
        dropzone.addEventListener('click', () => fileInput.click());
    });
</script>
@endsection