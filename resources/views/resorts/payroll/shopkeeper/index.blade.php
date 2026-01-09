@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')

<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Payroll</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                </div>
            </div>
        </div>
        <div>
            <div class="row g-30">
                <div class="col-xxl-12 col-xl-12 col-lg-12">
                <!-- <div class="col-xxl-6 col-xl-6 col-lg-6"> -->
                    <div class="card">
                        <div class="card-header">
                            <div class="row g-md-3 g-2 align-items-center">
                                <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-5 col-sm-4 col-6">
                                    <div class="input-group">
                                        <input type="search" class="form-control" id="search-input" placeholder="Search" />
                                        <i class="fa-solid fa-search"></i>
                                    </div>
                                </div>
                                                                            
                                <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                    <button class="btn btn-themeBlue btn-sm" id="clearFilter">Clear Filter</button>
                                </div>
                                
                            </div>
                        </div>
                        <div class="card-title">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <h3>Shopkeepers</h3>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex justify-content-sm-end align-items-center">
                                        <a href="{{route('shopkeepers.create')}}" class="btn btn-sm btn-theme @if(App\Helpers\Common::checkRouteWisePermission('shopkeepers.create',config('settings.resort_permissions.create')) == false) d-none @endif">
                                            Add New
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="shopkeeper-table" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Name</th>
                                    <th class="text-nowrap">Email</th>
                                    <th class="text-nowrap">Contact</th>
                                    <th class="text-nowrap">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')

<script>
    $(document).ready(function() {
        datatablelist();

        $('#search-input').on('keyup', function () {
            var searchValue = $(this).val();
            datatablelist();
        });

        $('#clearFilter').on('click', function () {
            $('#search-input').val('');
            datatablelist();
        });
    });

    function datatablelist() {
        if ($.fn.dataTable.isDataTable('#shopkeeper-table')) {
            $('#shopkeeper-table').DataTable().destroy();
        }

        $('#shopkeeper-table').DataTable({
            searching: false,
            lengthChange: false,
            info: true,
            autoWidth: false,
            scrollX: true,
            pageLength: 10,
            processing: true,
            serverSide: true,
            order:[[5, 'desc']],
            ajax: {
            url: "{{ route('shopkeepers.list') }}",
            type: 'GET',
                data: function (d) {
                    d.searchTerm = $('#search-input').val();
                }
            },
            columns: [
                {data: 'name', name: 'name'},
                {data: 'email', name: 'email'},
                {data: 'contact_no', name: 'contact_no'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
                {data: 'created_at', visible: false, searchable: false},
            ],
            
        });
    }

    $(document).on("click", "#shopkeeper-table .edit-row-btn", function (event) {
        event.preventDefault();

        var $row = $(this).closest("tr");
        var shopkeeperId = $(this).data('shopkeeper-id');

        var currentName = $row.find("td:nth-child(1)").text().trim();
        var currentEmail = $row.find("td:nth-child(2)").text().trim();
        var currentContact = $row.find("td:nth-child(3)").text().trim();

        var editRowHtml = `
            <td class="py-1">
                <div class="form-group">
                    <input type="text" name="name" class="form-control" value="${currentName}" />
                </div>
            </td>
            <td class="py-1">
                <div class="form-group">
                    <input type="text" name="email" class="form-control" value="${currentEmail}" />
                </div>
            </td>
            <td class="py-1">
                <input type="text" name="contact_no" class="form-control" value="${currentContact}" />
            </td>
            <td class="py-1">
                <a href="#" class="btn btn-theme update-row-btn" data-shopkeeper-id="${shopkeeperId}">Submit</a>
            </td>
        `;

        $row.html(editRowHtml);
    });

    // Handle click on update button
    $(document).on("click", "#shopkeeper-table .update-row-btn", function (event) {
        event.preventDefault(); // Prevent default action

        var $row = $(this).closest("tr");

        // Get updated values
        var shopkeeperId = $(this).data('shopkeeper-id');
        var updatedName = $row.find("input").eq(0).val();
        var updatedEmail = $row.find("input").eq(1).val();
        var updatedContact = $row.find("input").eq(2).val();

        // Perform the AJAX update request here
        $.ajax({
            url: "{{ route('shopkeeper.inlineUpdate', '') }}/" + shopkeeperId,
            type: "PUT",
            data: {
                name: updatedName,
                email: updatedEmail,
                contact_no: updatedContact,
            },
            success: function(response) {
                if(response.success == true) { // Ensure response contains a success key
                    // Update the row with new values
                    var updatedRowHtml = `
                        <td class="text-nowrap">${updatedName}</td>
                        <td class="text-nowrap">${updatedEmail}</td>
                        <td class="text-nowrap">${updatedContact}</td>
                        <td class="text-nowrap">
                            <div class="d-flex align-items-center">
                                <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-shopkeeper-id="${shopkeeperId}">
                                    <img src="{{ asset('resorts_assets/images/edit.svg') }}" alt="" class="img-fluid" />
                                </a>
                                <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn" data-shopkeeper-id="${shopkeeperId}">
                                    <img src="{{ asset('resorts_assets/images/trash-red.svg') }}" alt="" class="img-fluid" />
                                </a>
                            </div>
                        </td>
                    `;

                    $row.html(updatedRowHtml);

                    toastr.success(response.msg, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                    datatablelist();
                } else {
                    toastr.error(response.msg, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function(xhr) {
                toastr.error("An error occurred while updating the division.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });

    });

    // Confirmation dialog before delete
    $(document).on('click', '#shopkeeper-table .delete-row-btn', function (e) {
        e.preventDefault();

       Swal.fire({
            title: 'Sure want to delete?',
            text: 'This cannot be undone',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            confirmButtonColor: "#DD6B55"
            }).then((result) => {
                if (result.isConfirmed) {
                    // Perform the AJAX request
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('shopkeeper.destroy', '') }}/" + shopkeeperId,
                    dataType: "json",
                    success: function(response) {
                        // Show success message
                        Swal.fire(
                            `${action.charAt(0).toUpperCase() + action.slice(1)}!`,
                            `The OT has been successfully ${action}.`,
                            'success'
                        );
                        datatablelist();
                        // Optional: Update the UI (e.g., remove the item or update status)
                    },
                    error: function(xhr, status, error) {
                        // Show error message
                        Swal.fire(
                            'Error!',
                            'An error occurred while processing the request.',
                            'error'
                        );

                        console.error(error);
                    }
                });
            } else {
                console.log('Action canceled');
            }
        });
        // Get the division ID from the data attribute
        var shopkeeperId = $(this).data('shopkeeper-id');
        Swal.fire({
            title: 'Sure want to delete?',
            text: 'This cannot be undone',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            confirmButtonColor: "#DD6B55"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('shopkeeper.destroy', '') }}/" + shopkeeperId,
                    dataType: "json",
                }).done(function(result) {
                    if (result.success == true) {
                        toastr.success(result.msg, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                       datatablelist();
                    } else {
                        toastr.error(result.msg, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }).fail(function(error) {
                    toastr.error("Something went wrong", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                });
            }
        });
    });
</script>
@endsection
