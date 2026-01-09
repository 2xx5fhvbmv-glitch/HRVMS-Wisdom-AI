@extends('shopkeeper.layouts.app')
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
                        <span>&nbsp;</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8">
                        <div class="input-group">
                            <input type="search" id="search-box" class="form-control search" placeholder="Search" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                </div>

            </div>
            <!-- data-Table  -->
            <table class="table table-staffProdListing  w-100" id="productTable">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Price </th>
                        <th>QR Code</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="view-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small modal-viewStaffShop">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-md-3 mb-2">
                    <table>
                        <tr>
                            <th>Product Name:</th>
                            <td></td>
                        </tr>
                        <tr>
                            <th>Product Price:</th>
                            <td></td>
                        </tr>
                    </table>
                </div>
                <div class="bg-themeGrayLight text-center mb-md-3 mb-2">
                    <p>Scan this QR Code</p>
                    <img src="" alt="Qr code" style="width: 128px; height: 128px;">
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
    $(document).ready(function () {
        ProductList();
        // Filters for search, department, and position
        $(document).on('keyup', '.search', function() {
            ProductList();
        });
     
        $(document).on('click', '.btn-tableIcon.btnIcon-orange', function (e) {
            e.preventDefault();

            // Get the product ID from the button's data attribute
            const routeTemplate = `{{ route('shopkeeper.products.show', ['id' => '__ID__']) }}`;
            const productId = $(this).data('product-id');
            const finalUrl = routeTemplate.replace('__ID__', productId);

            // Fetch product details via AJAX
            $.ajax({
                url: finalUrl,
                method: 'GET',
                success: function (response) {
                    // Populate the modal with product details
                    $('#view-modal').find('th:contains("Product Name:")').next().text(response.name);
                    $('#view-modal').find('th:contains("Product Price:")').next().text(`$${response.price}`);

                    // Set QR code in the modal
                    if (response.qr_code) {
                        $('#view-modal').find('img[alt="Qr code"]').attr('src', `data:image/png;base64,${response.qr_code}`);
                    } else {
                        $('#view-modal').find('img[alt="Qr code"]').attr('src', ''); // Fallback if no QR code
                    }

                    // Show the modal
                    $('#view-modal').modal('show');
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching product details:", error);
                    alert("Failed to load product details.");
                }
            });
        });

        $(document).on('click', '#productTable .delete-row-btn', function (e) {
            e.preventDefault();
            const productId = $(this).data('product-id');

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
                        url: "{{ route('shopkeeper.products.destroy', '') }}/" + productId,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Ensure CSRF token is included
                        },
                        success: function (response) {
                            if (response.success) {
                                toastr.success(response.msg, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });

                                // Reload the DataTable
                                ProductList();
                            } else {
                                toastr.error(response.msg, "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            }
                        },
                        error: function () {
                            toastr.error("Something went wrong. Please try again.", "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    });
                }
            });
        });

        $(document).on("click", "#productTable .edit-row-btn", function (event) {
            event.preventDefault();

            var $row = $(this).closest("tr");
            var productId = $(this).data('product-id');

            // Fetch current values from the table cells
            var currentName = $row.find("td:nth-child(1)").text().trim();
            var currentPrice = $row.find("td:nth-child(2)").text().trim();

            // Replace current row with editable inputs
            var editRowHtml = `
                <td>
                    <input type="text" name="name" class="form-control" value="${currentName}" />
                </td>
                <td>
                    <input type="text" name="price" class="form-control" value="${currentPrice}" />
                </td>
                <td>
                    <div class="qr-code-container"></div>
                </td>
                <td>
                    <button class="btn btn-sm btn-success update-row-btn" data-product-id="${productId}">Update</button>
                    <button class="btn btn-sm btn-secondary cancel-row-btn">Cancel</button>
                </td>
            `;
            $row.html(editRowHtml);

            // Optionally regenerate a placeholder QR Code while editing
            var placeholderText = `Editing: ${currentName} - $${currentPrice}`;
            var qrCodeContainer = $row.find('.qr-code-container')[0];
            generateQRCode(placeholderText, qrCodeContainer);
        });

        // Update product on update button click
        $(document).on("click", "#productTable .update-row-btn", function (event) {
            event.preventDefault();

            var $row = $(this).closest("tr");
            var productId = $(this).data('product-id');

            // Get updated values from the inputs
            var updatedName = $row.find("input[name='name']").val();
            var updatedPrice = $row.find("input[name='price']").val();

            // Generate updated QR Code (using the updated name and price)
            var qrCodeText = `${updatedName} - $${updatedPrice}`;
            var qrCodeContainer = $row.find('.qr-code-container')[0];
            generateQRCode(qrCodeText, qrCodeContainer);

            // Get the Base64 QR code image (after generating)
            var qrCanvas = qrCodeContainer.querySelector('canvas');
            var qrCodeBase64 = qrCanvas ? qrCanvas.toDataURL() : null;

            // AJAX call to update the product
            $.ajax({
                url: "{{ route('shopkeeper.products.inlineUpdate', '') }}/" + productId,
                type: "PUT",
                data: {
                    name: updatedName,
                    price: updatedPrice,
                    qr_code: qrCodeBase64,  // Send the QR code base64 string
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        // Replace the row with updated static data
                        var updatedRowHtml = `
                            <td>${updatedName}</td>
                            <td>${updatedPrice}</td>
                            <td>
                                <div class="qr-code-container"></div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="#" class="btn-lg-icon icon-bg-green me-1 edit-row-btn" data-product-id="${productId}">
                                        <img src="{{ asset('resorts_assets/images/edit.svg') }}" alt="" class="img-fluid" />
                                    </a>
                                    <a href="#" class="btn-lg-icon icon-bg-red delete-row-btn" data-product-id="${productId}">
                                        <img src="{{ asset('resorts_assets/images/trash-red.svg') }}" alt="" class="img-fluid" />
                                    </a>
                                </div>
                            </td>
                        `;
                        $row.html(updatedRowHtml);

                        // Regenerate QR code after replacing the row
                        var newQrCodeContainer = $row.find('.qr-code-container')[0];
                        generateQRCode(qrCodeText, newQrCodeContainer);

                        toastr.success(response.msg, "Success", { positionClass: 'toast-bottom-right' });
                          ProductList(); // Reload table to revert edits

                    } else {
                        toastr.error(response.msg, "Error", { positionClass: 'toast-bottom-right' });
                    }
                },
                error: function(xhr) {
                    toastr.error("An error occurred while updating the product.", "Error", { positionClass: 'toast-bottom-right'});
                }
            });
        });

        // Cancel edit
        $(document).on("click", "#productTable .cancel-row-btn", function (event) {
            event.preventDefault();
            ProductList(); // Reload table to revert edits
        });
    })
    
    function generateQRCode(text, containerElement) {
        try {
            const sanitizedText = text.replace(/[^\x20-\x7E]/g, ''); // Remove invalid characters
            containerElement.innerHTML = ''; // Clear any previous QR code
            new QRCode(containerElement, {
                text: sanitizedText,
                width: 128,
                height: 128,
                colorDark: "#000000",
                colorLight: "#ffffff",
            });
        } catch (error) {
            console.error('Error generating QR Code:', error);
        }
    }

    function ProductList()
    {
        if ($.fn.DataTable.isDataTable('#productTable'))
        {
            $('#productTable').DataTable().destroy();
        }

        var productTable = $('#productTable').DataTable({
            "searching": false,  // Disable the default search box
            "bLengthChange": false, // Disable page length dropdown
            "bInfo": true,  // Show table information
            "bAutoWidth": false, // Auto width disabled
            "scrollX": true,  // Enable horizontal scrolling
            "iDisplayLength": 15,  // Set the initial number of records per page
            processing: true, // Show processing indicator
            serverSide: true, // Enable server-side processing
            order:[[4, 'desc']],
            ajax: {
                url: "{{ route('shopkeeper.products.list') }}",
                type: 'GET',
                data: function(d) {
                    d.searchTerm = $('.search').val();
                }
            },
            columns: [
                { data: 'name', name: 'name', className: 'text-nowrap' },
                { data: 'price', name: 'price', className: 'text-nowrap' },
                { 
                    data: 'qr_code', 
                    name: 'qr_code', 
                    orderable: false, 
                    searchable: false, 
                    className: 'text-center',
                    render: function(data, type, row) {
                        return data ? data : 'No QR Code'; // Render QR code or fallback text
                    }
                },
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'created_at', visible: false, searchable: false}
            ]
        });

    }
</script>
@endsection
