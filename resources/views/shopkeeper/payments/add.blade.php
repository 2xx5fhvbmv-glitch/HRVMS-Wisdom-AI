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
                                            <option value="{{$emp->id}}" data-emp-code="{{$emp->Emp_id}}" data-emp-name="{{$emp->resortAdmin->first_name}} {{$emp->resortAdmin->last_name}}">{{$emp->Emp_id}} / {{$emp->resortAdmin->first_name}} {{$emp->resortAdmin->last_name}}</option>
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
                                            <option value="{{$prod->id}}" data-product-name="{{$prod->name}}" data-product-currency="{{$prod->currency_type ?? 'USD'}}">{{$prod->name}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <label for="quantity" class="form-label">QUANTITY</label>
                                <input type="number" name="quantity" id="quantity" class="form-control" min="1" max="999999" placeholder="QUANTITY"/>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <label for="price" class="form-label">PRICE</label>
                                <input type="text" id="price" name="price" class="form-control" min="0" max="9999999" placeholder="PRICE" disabled>
                            </div>
                            <div class="col-xl-1 col-md-6">
                                <label class="form-label">CURRENCY</label>
                                <div id="product_currency_display" class="form-control-plaintext fw-semibold text-muted" style="min-height: 38px; line-height: 38px;">—</div>
                            </div>
                        </div>
                        <div class="col-xl-8 col-md-6" id="payment_QRCode">
                                
                        </div>
                        <div class="d-none d-md-block" style="height: 426px;"></div>
                        <div class="card-footer text-end">
                            <button type="submit" id="submitButton" class="btn btn-themeBlue btn-sm">Consent Send</button>
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
    // Cached display values (Select2 can make option:selected / data() unreliable when building QR)
    let cachedShopName = $('#shop_name').val() || '';
    let cachedProductName = '';
    let cachedProductCurrency = 'USD';
    let cachedEmpCode = '';
    let cachedEmpName = '';

    function generateQRCode(text, containerElement) {
        if (!containerElement) return;
        try {
            containerElement.innerHTML = ''; // Clear existing QR
            new QRCode(containerElement, {
                text: text,
                width: 128,
                height: 128,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: (typeof QRCode !== 'undefined' && QRCode.CorrectLevel) ? QRCode.CorrectLevel.L : 0
            });
        } catch (error) {
            console.error("QR Code Generation Failed:", error);
        }
    }

    function getSelectOptionData(selectId, value, dataAttr) {
        if (!value) return '';
        var opt = $('#' + selectId + ' option[value="' + value + '"]');
        return opt.length ? (opt.attr('data-' + dataAttr) || opt.text() || '') : '';
    }

    function generateQRCodeForPayment() {
        let empId = $("#select_emp").val();
        let productId = $("#select_product").val();
        let quantity = $("#quantity").val();
        let price = $("#price").val();
        let shopName = ($('#shop_name').val() || cachedShopName || '').toString().trim();

        // Use cached display values; fallback to reading from option by value (works with Select2)
        let empCode = cachedEmpCode || getSelectOptionData('select_emp', empId, 'emp-code');
        let empName = cachedEmpName || getSelectOptionData('select_emp', empId, 'emp-name');
        let productName = (cachedProductName || getSelectOptionData('select_product', productId, 'product-name')).toString().trim();
        if (!productName && productId) {
            productName = $("#select_product option[value='" + productId + "']").text() || '';
        }
        let currencyType = cachedProductCurrency || getSelectOptionData('select_product', productId, 'product-currency') || 'USD';

        // Date of purchase (same moment QR is generated)
        let now = new Date();
        let purchaseDate = now.toISOString();
        let purchase_date_display = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0') + ' ' +
            String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ':' + String(now.getSeconds()).padStart(2, '0');

        if (empId && productId && quantity && price) {
            const qrData = {
                employee_id: parseInt(empId, 10),
                product_id: parseInt(productId, 10),
                quantity: parseInt(quantity, 10),
                price: parseFloat(price) || parseFloat(String(price).replace(/[^0-9.-]/g, '')),
                currency_type: currencyType,
                currency: currencyType === 'MVR' ? 'MVR' : 'USD',
                shop_name: shopName,
                shopName: shopName,
                product_name: productName,
                employee_name: empName,
                emp_code: empCode,
                purchase_date: purchaseDate,
                date_of_purchase: purchase_date_display,
                dateOfPurchase: purchase_date_display
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
        cachedShopName = ($('#shop_name').val() || '').toString().trim();

        // When product changes, fetch price/currency and cache for QR
        $("#select_product").change(function () {
            let productId = $(this).val();
            if (productId) {
                var opt = $("#select_product option[value='" + productId + "']");
                cachedProductName = (opt.attr('data-product-name') || opt.text() || '').toString().trim();
                cachedProductCurrency = opt.attr('data-product-currency') || 'USD';
                $("#product_currency_display").text(cachedProductCurrency === 'MVR' ? 'MVR' : 'Dollar');
            } else {
                cachedProductName = '';
                cachedProductCurrency = 'USD';
                $("#product_currency_display").text('—');
            }
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
                        if (response.currency_type) cachedProductCurrency = response.currency_type;
                        $("#product_currency_display").text(response.currency_label || (cachedProductCurrency === 'MVR' ? 'MVR' : 'Dollar'));
                        var qty = parseInt($("#quantity").val(), 10);
                        if (!isNaN(qty) && qty > 0) {
                            $("#price").val((productPrice * qty).toFixed(2));
                        } else {
                            $("#price").val(productPrice.toFixed(2));
                        }
                        $("#quantity").trigger("input"); // Re-run quantity logic and QR
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
            let qty = parseInt($(this).val(), 10);
            if (isNaN(qty) || qty <= 0) {
                $("#price").val(productPrice > 0 ? productPrice.toFixed(2) : "");
                generateQRCodeForPayment();
                return;
            }
            let totalPrice = (productPrice * qty).toFixed(2);
            $("#price").val(totalPrice);
            generateQRCodeForPayment();
        });

        // Update QR when employee is selected (cache display values for QR)
        $("#select_emp").change(function () {
            var val = $(this).val();
            if (val) {
                var opt = $("#select_emp option[value='" + val + "']");
                cachedEmpCode = opt.attr('data-emp-code') || '';
                cachedEmpName = opt.attr('data-emp-name') || '';
            } else {
                cachedEmpCode = '';
                cachedEmpName = '';
            }
            getEmpdetails(val);
            generateQRCodeForPayment();
        });

        // QR is updated from product change handler above and quantity input below

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