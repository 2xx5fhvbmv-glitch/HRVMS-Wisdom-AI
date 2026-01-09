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

            <div>
                <form id="addPayemnt">
                    <div class="card">
                        <div class="row g-md-4 g-3  mb-3">
                            <div class="col-xl-4 col-md-6">
                                <input type="hidden" name="shop_name" id="shop_name" value="{{$shopkeeper->name}}"/>
                                <label for="select_emp" class="form-label">SELECT EMPLOYEE OR EMPLOYEE ID</label>
                                <select class="form-select select2t-none" name="emp_id" id="select_emp" onchange="getEmpdetails(this.value)">
                                    @if($employees)
                                        <option value="">Select Employee</option>
                                        @foreach($employees as $emp)
                                            <option value="{{$emp->id}}">{{$emp->Emp_id}} / {{$emp->resortAdmin->first_name}} {{$emp->resortAdmin->last_name}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-xl-8 col-md-6" id="empDetailsContainer">
                                
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <label for="select_product" class="form-label">SELECT PRODUCT</label>
                                <select class="form-select select2t-none" name="product_id" id="select_product">
                                    @if($products)
                                        <option value="">Select Product</option>
                                        @foreach($products as $prod)
                                            <option value="{{$prod->id}}">{{$prod->name}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <label for="quantity" class="form-label">QUANTITY</label>
                                <input type="number" name="quantity" id="quantity" class="form-control" min="1" max="999999" placeholder="QUANTITY"/>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <label for="price" class="form-label">PRICE</label>
                                <input type="text" id="price" name="price" class="form-control" min="0" max="9999999" placeholder="PRICE" disabled>
                            </div>
                        </div>
                        <div class="col-xl-8 col-md-6" id="payment_QRCode">
                                
                        </div>
                        <div class="d-none d-md-block" style="height: 426px;"></div>
                        <div class="card-footer text-end">
                            <button type="submit" id="submitButton" class="btn btn-themeBlue btn-sm">Consent Sent</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script type="text/javascript">
    let productPrice = 0; // Store selected product price

    function generateQRCode(text, containerElement) {
        try {
            containerElement.innerHTML = ''; // Clear existing QR
            new QRCode(containerElement, {
                text: text,
                width: 128,
                height: 128,
                colorDark: "#000000",
                colorLight: "#ffffff"
            });
        } catch (error) {
            console.error("QR Code Generation Failed:", error);
        }
    }

    function generateQRCodeForPayment() {
        let empId = $("#select_emp").val();
        let productId = $("#select_product").val();
        let quantity = $("#quantity").val();
        let price = $("#price").val();
        let shopName = $('#shop_name').val();

        if (empId && productId && quantity && price) {
            const qrData = {
                employee_id: empId,
                product_id: productId,
                quantity: quantity,
                price: price,
                shopName: shopName
            };
            generateQRCode(JSON.stringify(qrData), document.getElementById("payment_QRCode"));

            // // Convert the generated QR code to base64
            // let qrCodeImg = document.getElementById("payment_QRCode").querySelector('img');
            // if (qrCodeImg) {
            //     let qrCodeBase64 = qrCodeImg.src;

            //     // Send the QR code base64 along with other payment data
            //     storeQRCodeInDB(qrCodeBase64);
            // }
        } else {
            document.getElementById("payment_QRCode").innerHTML = ''; // Clear QR if data incomplete
        }
    }

    $(document).ready(function () {
        // When product changes, fetch price
        $("#select_product").change(function () {
            let productId = $(this).val();
            if (!productId) {
                $("#price").val("");
                generateQRCodeForPayment();
                return;
            }

            $.ajax({
                url: "{{ route('getProductPrice') }}",
                type: "GET",
                data: { product_id: productId },
                success: function (response) {
                    if (response.success) {
                        productPrice = parseFloat(response.price);
                        $("#quantity").trigger("input"); // Recalculate price
                        $("#price").val(productPrice.toFixed(2)); // Set initial price
                        generateQRCodeForPayment();
                    } else {
                        alert("Product price not found.");
                    }
                },
                error: function (xhr) {
                    console.error(xhr.responseText);
                    alert("Error fetching product price.");
                }
            });
        });

        // When quantity is entered
        $("#quantity").on("input", function () {
            let qty = parseInt($(this).val());
            if (isNaN(qty) || qty <= 0) {
                $("#price").val("");
                generateQRCodeForPayment();
                return;
            }
            let totalPrice = (productPrice * qty).toFixed(2);
            $("#price").val(totalPrice);
            generateQRCodeForPayment();
        });

        // Update QR when employee is selected
        $("#select_emp").change(function () {
            getEmpdetails(this.value); // existing logic
            generateQRCodeForPayment();
        });

        // Update QR when product is changed
        $("#select_product").change(generateQRCodeForPayment);

        $('#addPayemnt').validate({
            rules: {
                emp_id: { required: true },
                product_id: { required: true },
                quantity: { required: true, digits: true, min: 1 } // added validation for digits and min value of 1
            },
            messages: {
                emp_id: { required: "Please select employee" },
                product_id: { required: "Please select product" },
                quantity: { 
                    required: "Please enter quantity", 
                    digits: "Please enter a valid number", 
                    min: "Quantity must be at least 1" 
                }
            },
            submitHandler: function (form) {
                let formData = {
                    emp_id: $("#select_emp").val(),
                    product_id: $("#select_product").val(),
                    quantity: $("#quantity").val(), // fixed typo here
                    price: $("#price").val(),
                    shopkeeper_name: $("#shopkeeper_name").val(),
                    qr_code: $("#payment_QRCode img").attr("src"), // Add QR code to form data
                    _token: "{{ csrf_token() }}"
                };

                $('#submitButton')
                        .prop('disabled', true)
                        .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...');

                $.ajax({
                    url: "{{ route('shopkeeper.payment.store') }}", 
                    type: "POST",
                    data: formData,
                    success: function (response) {
                        if (response.success) {
                            toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                            $("#addPayemnt")[0].reset(); // Reset form

                            if (response.qr_code_base64) {
                                $("#qrCodeContainer").html(`
                                    <img src="${response.qr_code_base64}" alt="QR Code" style="max-width: 200px;">
                                    <p class="mt-2">${response.qr_code_text}</p>
                                `);
                            }

                            setTimeout(() => {
                                window.location.href = response.redirect_url || "{{ route('shopkeeper.dashboard') }}"; // Redirect to dashboard
                            }, timeout = 2000);
                        } else {
                            toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                        }
                    },
                    error: function (response) {
                        if (response.responseJSON) {
                            var errors = response.responseJSON.errors || {};
                            var errorMessage = '';
                            $.each(errors, function (key, error) {
                                errorMessage += error + '<br>';
                            });
                            toastr.error(errorMessage, { positionClass: 'toast-bottom-right' });
                        }
                    },
                    complete: function () {
                        $('#submitButton').prop('disabled', false); // re-enable the button after request completes
                    }
                });
            },
            errorPlacement: function(error, element) {
                if (element.hasClass("select2-hidden-accessible")) {
                    error.insertAfter(element.next('.select2'));
                } else {
                    error.insertAfter(element);
                }
            },
            highlight: function(element) {
                if ($(element).hasClass("select2-hidden-accessible")) {
                    $(element).next('.select2').find('.select2-selection').addClass('is-invalid');
                } else {
                    $(element).addClass('is-invalid');
                }
            },
            unhighlight: function(element) {
                if ($(element).hasClass("select2-hidden-accessible")) {
                    $(element).next('.select2').find('.select2-selection').removeClass('is-invalid');
                } else {
                    $(element).removeClass('is-invalid');
                }
            }
        });
    });

    function getEmpdetails(empId) {
        if (!empId) {
            $("#empDetailsContainer").html("");
            return;
        }
        $.ajax({
            url: "{{ route('employees.details.get', '') }}/" + empId,
            type: 'GET',
            success: function(response) {
                if (response.error) {
                    $("#empDetailsContainer").html("<p class='text-danger'>Employee not found.</p>");
                    return;
                }

                let empDetailsHtml = `
                    <div class="empDetails-user">
                        <div class="img-circle">
                            <img src="${response.profile_picture}" alt="user">
                        </div>
                        <div>
                            <h4>${response.name} 
                                <span class="badge badge-themeNew">#${response.emp_id}</span>
                            </h4>
                        </div>
                    </div>`;

                $("#empDetailsContainer").html(empDetailsHtml);
            },
            error: function(xhr, status, error) {
                $("#empDetailsContainer").html("<p class='text-danger'>Error loading employee details.</p>");
            }
        });
    }
</script>
@endsection