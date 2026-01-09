@extends('shopkeeper.layouts.app')
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
                            <span>&nbsp;</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-30">
                <div class="card-title">
                    <h3>Add Your Products</h3>
                </div>
                <div class="row g-2 mb-md-4 mb-3">
                    <div class="col-auto">
                        <a href="{{route('shopkeeper.products.download')}}" class="btn btn-themeSkyblue btn-sm">Download Template</a>
                    </div>
                    <form id="ImportProductsForm">
                        @csrf
                        <div class="col-auto">
                            <div class="uploadFile-btn me-0">
                                <a href="javascript:void(0)" class="btn btn-themeBlue btn-sm"
                                onclick="document.getElementById('ImportProducts').click();">
                                    Upload File
                                </a>
                                <input type="file" id="ImportProducts" name="ImportProducts"
                                accept=".csv,.xls,.xlsx,.ods,.xlsb,.xlt,.xltx,.xltm"
                                style="opacity: 0; position: absolute; z-index: -1;"
                                onchange="displayProductImportFileName()">

                                <div id="productImportFile" style="margin-top: 10px; color: #333;"></div>
                                                    <input type="hidden" name="products" id="productsData">

                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" id="submitButton" class="btn btn-themeBlue">Submit</button>
                        </div>
                    </form>
                </div>
                <!-- <div id="previewContainer"></div>

                <form id="finalSubmitForm">
                    @csrf
                    <input type="hidden" name="products" id="productsData">
                    <button type="submit" class="btn btn-theme">Import All Products</button>
                </form> -->
                <hr>
                <form id="productForm" data-parsley-validate>
                    <div>
                        <div class="prodConfig-main mb-md-4 mb-3">
                            <div class="prodConfig-block">
                                <div class="row align-items-end g-md-4 g-2 product-row">
                                    <div class="col-xl-4 col-md-4 col-sm-6">
                                        <label class="form-label">PRODUCT NAME<span class="red-mark">*</span></label>
                                        <input type="text" class="form-control product_name" placeholder="PRODUCT NAME" name="product_name[]" required>
                                    </div>
                                    <div class="col-xl-4 col-md-4 col-sm-6">
                                        <label class="form-label">PRODUCT PRICE<span class="red-mark">*</span></label>
                                        <input type="text" class="form-control product_price" placeholder="PRODUCT PRICE" name="product_price[]" required>
                                    </div>
                                    <div class="col">
                                        <label class="form-label">QR CODE<span class="red-mark">*</span></label><br>
                                        <div class="qr-code-container"></div>
                                    </div>
                                    <!-- <div class="col-auto d-flex align-items-center">
                                        <a href="#" class="btn btn-danger btn-sm remove-prodConfig">Remove</a>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                        <a href="#" class="btn btn-themeSkyblue btn-sm mb-3 add-prodConfig">Add More</a>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
<style>
    #ImportProducts-error{
        color:red!important;
    }
</style>
@endsection

@section('import-scripts')
<script type="text/javascript">
    $(document).ready(function () {
        // Function to display the selected file name
        $.validator.addMethod(
            "extension",
            function (value, element, param) {
                param = typeof param === "string" ? param.replace(/,/g, "|") : "png|jpe?g|gif";
                return (
                    this.optional(element) || value.match(new RegExp("\\.(" + param + ")$", "i"))
                );
            },
            "Please upload a file with a valid extension."
        );

        // Attach function to file input change
        $('#ImportProducts').on('change', displayProductImportFileName);

        // Validation setup for product import form
        // Form validation for product import
        $('#ImportProductsForm').validate({
            rules: {
                ImportProducts: {
                    required: true,
                    extension: "csv|xls|xlsx|ods|xlsb|xlt|xltx|xltm"
                }
            },
            messages: {
                ImportProducts: {
                    required: "Please select a file to upload",
                    extension: "Please upload a valid spreadsheet file"
                }
            },
            errorPlacement: function(error, element) {
                error.insertAfter(element.closest('.uploadFile-btn'));
            }
        });

        // Form submit handler
        $('#ImportProductsForm').submit(function (e) {
            e.preventDefault();
            
            // Check if form is valid before proceeding
            if(!$(this).valid()) {
                return false;
            }
            
            let formData = new FormData(this);
            formData.append('fileUpload', $('#ImportProducts')[0].files[0]);

            $.ajax({
                url: "{{ route('shopkeeper.products.import.preview') }}",
                method: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    const productsData = [];
                    let done = 0;

                    response.products.forEach((product, index) => {
                        // Create temp DOM to render QR
                        let tempContainer = document.createElement('div');
                        tempContainer.id = `qr-${index}`;
                        document.body.appendChild(tempContainer);

                        new QRCode(tempContainer, {
                            text: product.product_name + ' - $' + product.product_price,
                            width: 60,
                            height: 60,
                            correctLevel: QRCode.CorrectLevel.L, // Lower error correction = more data space
                            version: 10 // Increase this if still not enough (1–40)
                        });

                        setTimeout(() => {
                            const canvas = tempContainer.querySelector('canvas');
                            const qrBase64 = canvas ? canvas.toDataURL() : '';
                            
                            productsData.push({
                                product_name: product.product_name,
                                product_price: product.product_price,
                                qr_code: qrBase64,
                            });

                            document.body.removeChild(tempContainer); // Clean up

                            done++;
                            if (done === response.products.length) {
                                // All QR codes ready, submit to DB
                                $.ajax({
                                    url: "{{ route('shopkeeper.products.import.submit') }}",
                                    method: "POST",
                                    data: {
                                        _token: "{{ csrf_token() }}",
                                        products: productsData,
                                    },
                                    success: function (res) {
                                        toastr.success(res.message, "Success", {
                                            positionClass: 'toast-bottom-right'
                                        });
                                        setTimeout(() => {
                                            window.location.href = "{{ route('shopkeeper.products') }}";
                                        }, 2000);
                                    },
                                    error: function () {
                                        toastr.error("Saving products failed.", "Error", {
                                            positionClass: 'toast-bottom-right'
                                        });
                                    }
                                });
                            }
                        }, 2000);
                    });
                },
                error: function () {
                    toastr.error("File processing failed.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            });
        });

    });

    function displayProductImportFileName() {
        const file = $('#ImportProducts')[0].files[0];
        if (file) {
            $('#productImportFile').text('Selected file: ' + file.name);
        } else {
            $('#productImportFile').text('');
        }
    }
    document.addEventListener('DOMContentLoaded', function () {
        const productsMain = document.querySelector('.prodConfig-main');
        const addMoreBtn = document.querySelector('.add-prodConfig');
        const productForm = document.querySelector('#productForm');

        // QR Code Generation Function
        function generateQRCode(text, containerElement) {
            if (!containerElement) {
                console.warn('QR Code container element is null or undefined.');
                return;
            }

            console.log('Generating QR Code for:', text);

            try {
                containerElement.innerHTML = ''; // Clear any existing QR code
                new QRCode(containerElement, {
                    text: text,
                    width: 128,
                    height: 128,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                });
                console.log('QR Code generated successfully for:', text);
            } catch (error) {
                console.error('Error in generateQRCode:', error);
            }
        }

        // Attach event listeners to a product row
        function attachEventListeners(row) {
            const productNameInput = row.querySelector('.product_name');
            const productPriceInput = row.querySelector('.product_price');
            const qrContainer = row.querySelector('.qr-code-container');

            if (!qrContainer) {
                console.error('QR Code container not found in row:', row);
                return;
            }

            [productNameInput, productPriceInput].forEach(input => {
                input.addEventListener('input', function () {
                    const productName = productNameInput.value.trim();
                    const productPrice = productPriceInput.value.trim();
                    if (productName && productPrice) {
                        generateQRCode(`${productName} - $${productPrice}`, qrContainer);
                    }
                });
            });
        }

        // Add More Fields
        addMoreBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const newRow = document.createElement('div');
            newRow.className = ' prodConfig-block ';
            newRow.innerHTML = `<div class="row align-items-end g-md-4 g-2 product-row">
                <div class="col-xl-4 col-md-4 col-sm-6">
                    <label class="form-label">PRODUCT NAME<span class="red-mark">*</span></label>
                    <input type="text" class="form-control product_name" placeholder="PRODUCT NAME" name="product_name[]" data-parsley-required="true">
                </div>
                <div class="col-xl-4 col-md-4 col-sm-6">
                    <label class="form-label">PRODUCT PRICE<span class="red-mark">*</span></label>
                    <input type="text" class="form-control product_price" placeholder="PRODUCT PRICE" name="product_price[]" data-parsley-required="true" data-parsley-type="number">
                </div>
                <div class="col">
                    <label class="form-label">QR CODE<span class="red-mark">*</span></label><br>
                    <div class="qr-code-container"></div>
                </div>
                <div class="col-auto d-flex align-items-center">
                    <a href="#" class="btn btn-danger btn-sm remove-prodConfig">Remove</a>
                </div></div>
            `;

            // Attach event listeners for the new row
            attachEventListeners(newRow);

            // Append the new row
            productsMain.appendChild(newRow);

            $('#productForm').parsley().destroy();
            $('#productForm').parsley();
        });

        // Remove Fields
        productsMain.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-prodConfig')) {
                e.preventDefault();
                const rows = document.querySelectorAll('.product-row');
                if (rows.length > 1) {
                    e.target.closest('.product-row').remove();
                } else {
                    alert("At least one product is required.");
                }
            }
        });

        // Submit Form
        productForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Trigger parsley validation
            if (!$('#productForm').parsley().validate()) {
                return; // Stop if validation fails
            }

            const rows = document.querySelectorAll('.product-row');
            const productsData = [];

            rows.forEach(row => {
                const productName = row.querySelector('.product_name').value.trim();
                const productPrice = row.querySelector('.product_price').value.trim();
                const qrContainer = row.querySelector('.qr-code-container');

                // Convert QR container to Base64 image
                const qrCanvas = qrContainer.querySelector('canvas');
                const qrCodeBase64 = qrCanvas ? qrCanvas.toDataURL() : null;

                if (productName && productPrice) {
                    productsData.push({
                        product_name: productName,
                        product_price: productPrice,
                        qr_code: qrCodeBase64,
                    });
                }
            });

            // Validate data
            if (productsData.length === 0) {
                // alert("Please fill in at least one product.");
                toastr.error("Please fill in at least one product.", "Error", {
                    positionClass: 'toast-bottom-right'
                });
                return;
            }

            // Submit via AJAX
            fetch("{{ route('products.submit') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ products: productsData })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // alert("Products added successfully!");
                    toastr.success("Products added successfully!", "Success", {
                        positionClass: 'toast-bottom-right'
                    });

                    setTimeout(() => {
                        window.location.href = "{{ route('shopkeeper.products') }}";
                    }, 2000);
                    
                    
                } else {
                    // alert("Failed to save products.");
                    toastr.error("Failed to save products.", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred while submitting the form.");
            });
        });

        // Attach event listeners to the default first row
        const initialRow = document.querySelector('.product-row');
        if (initialRow) {
            attachEventListeners(initialRow);
        }
    });
</script>
@endsection