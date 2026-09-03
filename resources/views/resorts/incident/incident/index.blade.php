@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    <style>
        #incident-index-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #incident-index-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="incident-index-hero">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Incident</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-incidentListing">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-sm-6 ">
                            <div class="input-group">
                                <input type="search" class="form-control" id="searchInput" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                            <select id="categoryFilter" class="form-select dd-native-select">
                                <option value="">All Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                @endforeach
                            </select>
                            <div class="dd" data-target="#categoryFilter">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">All Category</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Category">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a category…"></div>
                                    <div class="dd-scroll">
                                        <div class="dd-item active" role="option" data-value=""><span class="dd-nm">All Category</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @foreach($categories as $category)
                                            <div class="dd-item" role="option" data-value="{{ $category->id }}"><span class="dd-nm">{{ $category->category_name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-5  col-6">
                            <input type="text" name="dateFilter" id="dateFilter" class="form-control datepicker"/>
                        </div>
                        <div class="col-auto ms-auto">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="tab1" data-bs-toggle="tab"
                                        data-bs-target="#tabPane1" type="button" role="tab" aria-controls="tabPane1"
                                        aria-selected="true">Active Incidents</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tab2" data-bs-toggle="tab" data-bs-target="#tabPane2"
                                        type="button" role="tab" aria-controls="tabPane2" aria-selected="false">
                                        Resolved Incidents
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="tabPane1" role="tabpanel" aria-labelledby="tab1"
                        tabindex="0">
                        <!-- data-Table -->
                        <div class="table-responsive mb-md-3 mb-2">
                            <table id="table-incidentListing" class="table  table-incidentListing w-100 mb-0">
                                <thead>
                                    <tr>
                                        <th>Incident ID</th>
                                        <th>Incident Name</th>
                                        <th>Location</th>
                                        <th>Category</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Witness</th>
                                        <th>Involved Employee</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tabPane2" role="tabpanel" aria-labelledby="tab2" tabindex="0">
                        <div class="table-responsive mb-md-3 mb-2">
                            <table id="table-resolvedIncident" class="table  table-resolvedIncident w-100 mb-0">
                                <thead>
                                    <tr>
                                        <th>Incident ID</th>
                                        <th>Incident Name</th>
                                        <th>Location</th>
                                        <th>Category</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Witness</th>
                                        <th>Involved Employee</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@include('resorts._emotional_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        flatpickr('#dateFilter', {
            dateFormat: 'd/m/Y',
            allowInput: true,
            appendTo: document.body
        });
        getIncidents();

        // Trigger search on input change
        $('#searchInput,#categoryFilter,#dateFilter').on('keyup change', function () {
            let activeTab = $('.nav-tabs .nav-link.active').attr('id'); // Get active tab ID
            if (activeTab === 'tab1') {
                getIncidents(); // Reload active incidents
            } else if (activeTab === 'tab2') {
                getResolvedIncidents(); // Reload resolved incidents
            }
        });

        // Load Resolved Incidents only when the tab is clicked
        $('#tab2').on('click', function () {
            getResolvedIncidents();
        });

        // Load Active Incidents when switching back to tab1
        $('#tab1').on('click', function () {
            getIncidents();
        });

        $(document).on('click', '.correct-btn, .reject-btn', function () {
            let id = $(this).data('id');
            let isApprove = $(this).hasClass('correct-btn');

            wisdomConfirm({
                role: 'confirm',
                title: isApprove ? 'Approve Report' : 'Reject Report',
                confirmText: isApprove ? 'Approve' : 'Reject',
                extra: {
                    input: 'textarea',
                    inputLabel: 'Remarks',
                    preConfirm: (remarks) => {
                        return $.ajax({
                            url: "{{route('incident.investigation.approvedorreject')}}",
                            method: 'POST',
                            data: {
                                id: id,
                                remarks: remarks,
                                status: isApprove ? 'approved' : 'rejected',
                                _token: $('meta[name="csrf-token"]').attr('content')
                            }
                        });
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    toastr.success('Incident ' + (isApprove ? 'approved' : 'rejected') + ' successfully', "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    $('#table-incidentListing').DataTable().ajax.reload();
                }
            });
        });

        $(document).on('click', '.delete-row-btn', function (e) {
            e.preventDefault();
            let id = $(this).data('id');

            wisdomConfirm({
                role: 'destructive',
                title: 'Are you sure?',
                text: 'This action cannot be undone.',
                confirmText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{route('incident.delete')}}",
                        method: 'POST',
                        data: {
                            id: id,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (response) {
                            if (response.success) {
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                                $('#table-monthlyCheck').DataTable().ajax.reload();
                            } else {
                                toastr.error(response.message || 'Something went wrong', "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function (xhr) {
                            toastr.error('Server error. Please try again.', "Error", "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
            });
        });
     });

    function getIncidents() {
        if ($.fn.DataTable.isDataTable("#table-incidentListing")) {
            $("#table-incidentListing").DataTable().destroy();
        }

        $('#table-incidentListing').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            scrollX: true,
            iDisplayLength: 10,
            order:[[10, 'desc']],
            ajax: {
                url: "{{ route('incident.list') }}",
                type: "GET",
                data: function (d) {
                    d.searchTerm = $('#searchInput').val();
                    d.category = $('#categoryFilter').val();
                    let selectedDate = $('.datepicker').val();
                    if (selectedDate) {
                        let parts = selectedDate.split('/');
                        d.date = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                    } else {
                        d.date = '';
                    }
                }
            },
            columns: [
                { data: 'incident_id', className: 'text-nowrap' },
                { data: 'incident_name', className: 'text-nowrap' },
                { data: 'location', className: 'text-nowrap' },
                { data: 'category', className: 'text-nowrap' },
                { data: 'date', className: 'text-nowrap' },
                { data: 'time', className: 'text-nowrap' },
                { data: 'isWitness', className: 'text-nowrap' },
                { data: 'involved_employees', className: 'text-nowrap' },
                { data: 'status', className: 'text-nowrap' },
                { data: 'action', orderable: false, searchable: false },
                { data: 'created_at', visible: false, searchable: false },
            ],
            rowCallback: function (row, data) {
                if (data.danger_flag) {
                    $(row).addClass('danger-tr');
                }
            },
            error: function (xhr) {
                console.log(xhr.responseText);
            }
        });
    }


    function getResolvedIncidents() {
        if ($.fn.DataTable.isDataTable("#table-resolvedIncident")) {
            $("#table-resolvedIncident").DataTable().destroy(); // Destroy previous instance
        }

        $('#table-resolvedIncident').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 10,
            processing: true,
            serverSide: true,
            order:[[10, 'desc']],
            ajax: {
                url: "{{ route('incident.resolved.list') }}",
                data: function (d) {
                    d.searchTerm = $('#searchInput').val(); // Pass search term
                    d.category = $('#categoryFilter').val();
                    let selectedDate = $('.datepicker').val();
                    if (selectedDate) {
                        let parts = selectedDate.split('/');
                        d.date = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`; // YYYY-MM-DD format
                    } else {
                        d.date = '';
                    }
                },
                type: "GET",
            },
            columns: [
                { data: 'incident_id', name: 'incident_id', className: 'text-nowrap' },
                { data: 'incident_name', name: 'incident_name', className: 'text-nowrap' },
                { data: 'location', name: 'location', className: 'text-nowrap' },
                { data: 'category', name: 'category', className: 'text-nowrap' },
                { data: 'date', name: 'date', className: 'text-nowrap' },
                { data: 'time', name: 'time', className: 'text-nowrap' },
                { data: 'isWitness', name: 'isWitness', className: 'text-nowrap' },
                { data: 'involved_employees', name: 'involved_employees', className: 'text-nowrap' },
                { data: 'status', name: 'status', className: 'text-nowrap' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
                {data:'created_at',visible:false,searchable:false},
            ],
            rowCallback: function (row, data) {
                if (data.danger_flag) {
                    $(row).addClass('danger-tr');
                }
            },
            error: function (xhr, error, code) {
                console.log(xhr.responseText);
            }
        });
    }
</script>
@endsection