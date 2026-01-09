
@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

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
                        <span>Accommodation</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="mb-4 @if(Common::checkRouteWisePermission('resort.accommodation.Inventory',config('settings.resort_permissions.create')) == false)d-none @endif">
                    <form id="InventoryForm" data-parsley-validate>
                        <div class="AddInventoryItem">
                            <div class="row gx-4 g-3 mb-3">
                                <div class="col-lg-4 col-sm-6">
                                    <label for="xpat" class="form-label">ITEM NAME</label>
                                    <input type="text" name="ItemName[]" class="form-control" id="ItemName_1" placeholder="Item Name"
                                                    data-parsley-required-message="Please enter Item Name."
                                                    data-parsley-maxlength="50"
                                                    data-parsley-maxlength-message="Item Name must not exceed 50 characters."
                                                    data-parsley-pattern="^[^<>]*$"
                                                    data-parsley-pattern-message="Item Name cannot contain script tags or special characters like < and >."
                                                    data-parsley-no-script="true"
                                                  required>
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select select2t-none Inv_Cat_id" id="Inv_Cat_id_1" name="Inv_Cat_id[]" required data-parsley-required-message="Please select a category.">
                                            @if($InventoryCategory->isNotEmpty())
                                                @foreach($InventoryCategory as $ic)
                                                    <option value="{{$ic->id}}">{{$ic->CategoryName}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="quantity" class="form-label">QUANTITY</label>
                                    <input type="number" min="0" name="Quantity[]" class="form-control" id="Quantity_1" placeholder="10"
                                        required data-parsley-required-message="Please enter Quantity."
                                        data-parsley-type="number"
                                        data-parsley-min="1"
                                        data-parsley-min-message="Quantity must be at least 1.">
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="datePurchase" class="form-label">DATE OF PURCHASE</label>
                                    <input type="text" name="PurchageDate[]" class="form-control datePurchase" id="PurchageDate_1"
                                        required data-parsley-required-message="Please select Purchase Date."
                                        data-parsley-date
                                        data-parsley-date-message="Please enter a valid date."
                                       >
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="inventoryCode" class="form-label">INVENTORY CODE</label>
                                    <input type="number" min="0" max="999999" name="ItemCode[]" class="form-control" id="inventoryCode_1" placeholder="451245"
                                        required data-parsley-required-message="Please enter Item Code."

                                                    data-parsley-required-message="Please enter Item Code."
                                                    data-parsley-maxlength="50"
                                                    data-parsley-maxlength-message="Item Code must not exceed 50 characters."
                                                    data-parsley-pattern="^[^<>]*$"
                                                    data-parsley-pattern-message="Item Code cannot contain script tags or special characters like < and >."
                                                    data-parsley-no-script="true"
                                                  required>
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <label for="stockAva" class="form-label">MINIMUM STOCK AVAILABLE</label>
                                    <input type="number" min="0" name="MinStock[]" class="form-control" id="MinStock_1" placeholder="1" required data-parsley-type="number" data-parsley-min="1" data-parsley-min-message="Minimum Stock must be at least 1.">
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="count" id="count" value="1">

                        <div class="card-footer text-end">
                            <a href="javascript:void(0)" class="btn btn-themeSkyblue btn-sm SaveAndAdd">Add Another</a>
                            <button type="submit" href="#" class="btn btn-themeBlue ms-1 btn-sm">Submit</button>
                        </div>
                    </form>



            </div>
            <div class="card card-small bg">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-auto">
                            <div class="card-title border-0 m-0 p-0">
                                <h3>Inventory List</h3>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-5 col-6 ms-auto">
                            <div class="input-group">
                                <input type="search" class="form-control search" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="col-auto">
                            <select class="form-select"  id="ItemsFilter">
                                <option value="All"> All </option>
                                @if($InventoryItems->isNotEmpty())
                                    @foreach($InventoryItems as $ic)
                                        <option value="{{$ic->id}}">{{$ic->ItemName}}</option>
                                    @endforeach
                                @endif

                            </select>
                        </div>
                        <div class="col-auto">
                            <select class="form-select Inv_Cat_id" id="Inv_Cat_idFilter">
                                <option value="All"> All </option>
                                    @if($InventoryCategory->isNotEmpty())
                                        @foreach($InventoryCategory as $ic)
                                            <option value="{{$ic->id}}">{{$ic->CategoryName}}</option>
                                        @endforeach
                                    @endif
                            </select>
                        </div>
                        <!-- <div class="col-auto">
                            <select class="form-select">
                                <option selected>Available</option>
                                <option value="1">abc</option>
                                <option value="2">abc</option>
                            </select>
                        </div> -->
                        <div class="col-auto">
                            <button class="btn btn-themeBlue btn-sm" id="clearFilter">Clear Filter</button>
                        </div>
                    

                        <div class="col-auto ">
                            <div class="d-flex align-items-center">
                                <label for="flexSwitchCheckDefault" class="form-label mb-0 me-1 me-md-3">VIEW
                                    HISTORICAL INVENTORY</label>
                                <div class="form-check form-switch form-switchTheme">
                                    <input class="form-check-input historical_inventory" type="checkbox" role="switch"
                                        id="flexSwitchCheckDefault" >
                                    <label class="form-check-label" for=""></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- data-Table  -->
                <div id="InvenotryIndexDiv"> 
                    <table id="InvenotryIndex" class="table   w-100">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Inventory Code</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Occupied</th>
                                <th>Available</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
                <div id="HisotricalInvenotryIndexDiv" class="d-none">

                    <table id="HisotricalInvenotryIndex" class="table   w-100">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Inventory Code</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Status</th>
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




@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
$(document).ready(function()
{

    $("#Inv_Cat_id_1").select2({
        placeholder: 'Category',
        allowClear: true
    });
    $("#Inv_Cat_idFilter").select2({
        placeholder: 'Category',
        allowClear: true
    });
    $("#ItemsFilter").select2({
        placeholder: 'Item',
        allowClear: true
    });

    window.Parsley.addValidator('noScript', {
        validateString: function(value) {
            // Check for script tags, event handlers, and other potentially harmful patterns
            const scriptPattern = /<script|on\w+\s*=|javascript:|data:|vbscript:/i;
            return !scriptPattern.test(value);
        },
        messages: {
            en: 'This field cannot contain scripts or potentially harmful content.'
        }
    });
    
    // Additional protection when form is submitted
    $('form').on('submit', function() {
        $('input[type="text"], textarea').each(function() {
            // Sanitize input before submission
            let value = $(this).val();
            if (value) {
                // Convert special characters to HTML entities
                value = value.replace(/</g, '&lt;').replace(/>/g, '&gt;');
                $(this).val(value);
            }
        });
    });
    $(".datePurchase").datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,      // Close the picker after selection
        todayHighlight: true, // Highlight today's date
        endDate: new Date(),
    });


    InventoryList();


    $(".historical_inventory").on("change", function() {
        if ( $(".historical_inventory").is(':checked')) 
        {
           $("#InvenotryIndexDiv").addClass("d-none");
           $("#HisotricalInvenotryIndexDiv").removeClass("d-none");
           HisotricalInvenotryIndex();
        }
        else
        {
            $("#InvenotryIndexDiv").removeClass("d-none");
            $("#HisotricalInvenotryIndexDiv").addClass("d-none");
            InventoryList();
        }
    });
    $(document).on("click", "#clearFilter", function() {    

        $(".search").val('');
        $("#Inv_Cat_idFilter").val('').trigger('change');
        $("#ItemsFilter").val('').trigger('change');

    });
        Parsley.addValidator('dateLessThanOrEqual', {
            validateString: function(value, requirement) {
                alert( new Date(value));
                return new Date(value) <= new Date(requirement);
            },
            messages: {
                en: 'Date cannot be in the future.'
            }
        });
        $('#InventoryForm').on('submit', function(e) {
            e.preventDefault();

            if ($(this).parsley().isValid()) {
                var formData = new FormData(this);

                $.ajax({
                    url: "{{ route('resort.accommodation.StoreInventory') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, "Success", {
                                positionClass: 'toast-bottom-right' // Position the toastr at the bottom-right
                            });

                            $('#InvenotryIndex').DataTable().ajax.reload();
                            $('#InventoryForm')[0].reset();
                        } else {
                            toastr.error(response.message, "Error",{
                                 positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(response) {
                        var errors = response.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            toastr.error(value[0]);
                        });
                    }
                });
            }
        });
});

    $(document).on("click",'.SaveAndAdd',function(){

        let counts  =$("#count").val();
        counts++;
        $("#count").val(counts);

        let row=`<div id="row_${counts}">
                    <hr>
                    <div class="row gx-4 g-3 mb-3" >
                        <div class="col-lg-4 col-sm-6">
                            <label for="xpat" class="form-label">ITEM NAME</label>
                            <input type="text" name="ItemName[]" class="form-control" id="ItemName_${counts}" placeholder="Item Name"
                                required data-parsley-required-message="Please enter Item Name."
                                data-parsley-maxlength="50"
                                data-parsley-maxlength-message="Item Name must not exceed 50 characters.">
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select select2t-none Inv_Cat_id" id="Inv_Cat_id_${counts}" name="Inv_Cat_id[]"
                                    required data-parsley-required-message="Please select a category.">
                                <option></option>
                                @if($InventoryCategory->isNotEmpty())
                                    @foreach($InventoryCategory as $ic)
                                        <option value="{{$ic->id}}">{{$ic->CategoryName}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <label for="quantity" class="form-label">QUANTITY</label>
                            <input type="number" name="Quantity[]" class="form-control" id="Quantity_${counts}" placeholder="10"
                                required data-parsley-required-message="Please enter Quantity."
                                data-parsley-type="number"
                                data-parsley-min="1"
                                min="0"
                                data-parsley-min-message="Quantity must be at least 1.">
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <label for="datePurchase" class="form-label">DATE OF PURCHASE</label>
                            <input type="text" name="PurchageDate[]" class="form-control datepicker datePurchase" id="PurchageDate_${counts}"
                                required data-parsley-required-message="Please select Purchase Date."
                                data-parsley-date
                                data-parsley-date-message="Please enter a valid date."
                            >
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <label for="inventoryCode" class="form-label">INVENTORY CODE</label>
                            <input type="text" name="ItemCode[]" class="form-control" id="inventoryCode_${counts}" placeholder="451245"
                                required data-parsley-required-message="Please enter Item Code.">
                        </div>
                        <div class="col-lg-2 col-sm-3">
                            <label for="stockAva" class="form-label">MINIMUM STOCK AVAILABLE</label>
                            <input type="number" min="0" name="MinStock[]" class="form-control" id="MinStock_${counts}" placeholder="4" required data-parsley-type="number" data-parsley-min="1" data-parsley-min-message="Minimum Stock must be at least 1.">

                        </div>
                        <div class="col-lg-2 col-sm-3">
                                <button type="button" style="    margin-top: 32px;" class="btn btn-sm btn-danger remove" data-id="${counts}">Remove</button>
                        </div>
                    </div>
                </div>`;
        $('.AddInventoryItem').append(row);
        datepickeraddmore(counts);

    });

    $(document).on("click",'.remove',function(){

        let counts=$(this).attr('data-id');
        $("#row_"+counts).remove();
        let newcount =$("#count").val();
        newcount--;
        $("#count").val(newcount);


    })
    function datepickeraddmore(counts) {
        $("#PurchageDate_"+counts).datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,      // Close the picker after selection
            todayHighlight: true, // Highlight today's date
            endDate: new Date(),
        });

        $("#Inv_Cat_id_"+counts).select2({
            placeholder: 'Category',
            allowClear: true
        });

    }

    $(document).on("change","#ItemsFilter",function(){

        if ( $(".historical_inventory").is(':checked')) 
                {
                   $("#InvenotryIndexDiv").addClass("d-none");
                   $("#HisotricalInvenotryIndexDiv").removeClass("d-none");
                   HisotricalInvenotryIndex();
                }
                else
                {
                    $("#InvenotryIndexDiv").removeClass("d-none");
                    $("#HisotricalInvenotryIndexDiv").addClass("d-none");
                    InventoryList();
                }
   
    });
    $(document).on("keyup",".search",function(){

if ( $(".historical_inventory").is(':checked')) 
        {
           $("#InvenotryIndexDiv").hide();
           $("#HisotricalInvenotryIndexDiv").show();
           HisotricalInvenotryIndex();
        }
        else
        {
            InventoryList();
        }    
    });
    $(document).on("change","#Inv_Cat_idFilter",function(){
        if ( $(".historical_inventory").is(':checked')) 
        {
           $("#InvenotryIndexDiv").hide();
           $("#HisotricalInvenotryIndexDiv").show();
           HisotricalInvenotryIndex();
        }
        else
        {
            InventoryList();
        }
    });


    $(document).on("click", "#InvenotryIndex .edit-row-btn", function (event) {
            event.preventDefault(); // Prevent default action

            // Find the parent row
            var $row = $(this).closest("tr");

            // Extract division ID
            var invenotry_id = $(this).data('cat-id');
            var ItemName = $row.find("td:nth-child(1)").text().trim();
            var ItemCode = $row.find("td:nth-child(2)").text().trim();
            var Category = $row.find("td:nth-child(3)").text().trim();
            var Qty = $row.find("td:nth-child(4)").text().trim();
            var Occupied = $row.find("td:nth-child(5)").text().trim();
            var Avilable = $row.find("td:nth-child(6)").text().trim();

            var editRowHtml = `
                    <td class="py-1">
                        <div class="form-group">
                           ${ItemName}
                        </div>
                    </td>
                     <td class="py-1">
                        <div class="form-group">
                           ${ItemCode}
                        </div>
                    </td>
                     <td class="py-1">
                        <div class="form-group">
                           ${Category}
                        </div>
                    </td>
                    <td class="py-1">
                        <div class="form-group">
                            <input type="number" class="form-control name" min="${Qty}"  value="${Qty}" />
                        </div>
                    </td>
                    <td class="py-1">
                        <div class="form-group">
                           ${Occupied}
                        </div>
                    </td>
                      <td class="py-1">
                        <div class="form-group">
                           ${Avilable}
                        </div>
                    </td>
                    <td class="py-1">
                        <a href="#" class="btn btn-theme update-row-btn_agent" data-inventory-id="${invenotry_id}">Submit</a>
                    </td>
                `;

            // Replace row content with editable form
            $row.html(editRowHtml);
        });

        $(document).on("click", "#InvenotryIndex .update-row-btn_agent", function (event) {
            event.preventDefault(); // Prevent default action
            var $row = $(this).closest("tr");
            var inventory_id = $(this).data('inventory-id');
            var qty = $row.find("input").eq(0).val();
            $.ajax({
                url: "{{ route('resort.accommodation.Inventoryupdated', '') }}/" + inventory_id,
                type: "PUT",
                data: {
                    qty : qty,
                    inventory_id : inventory_id,
                },
                success: function(response) {
                    if(response.success == true) { // Ensure response contains a success key
                        InventoryList();

                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(xhr) {

                        let errorMessage = '';

                        if (xhr.responseJSON && xhr.responseJSON.errors) {

                            $.each(xhr.responseJSON.errors, function(key, error) {

                            errorMessage += error + "<br>";
                            })
                        }
                        else
                        {
                            errorMessage = "An error occurred while Create or Update."; // Default error message
                        }

                        toastr.error(errorMessage, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
            });


        });

    function InventoryList()
    {
        if ($.fn.dataTable.isDataTable('#InvenotryIndex')) {
            // If initialized, destroy the existing instance
            $('#InvenotryIndex').DataTable().clear().destroy();
        }

        var InvenotryIndex = $('#InvenotryIndex').DataTable({
                "searching": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": true,
                "bAutoWidth": false,
                "scrollX": true,
                "iDisplayLength": 6,
                "order": [[7, 'desc']], // <-- Set default descending order by 1st column (ItemName)
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('resort.accommodation.inventory') }}",
                    type: 'GET',
                    data: function(d) {
                        d.Inv_Cat_id = $("#Inv_Cat_idFilter").val();
                        d.searchTerm = $('.search').val();
                        d.Items_id = $("#ItemsFilter").val();
                    }
                },
                columns: [
                    { data: 'ItemName', name: 'ItemName', className: 'text-nowrap' },
                    { data: 'ItemCode', name: 'InventoryCode', className: 'text-nowrap' },
                    { data: 'Category', name: 'Category', className: 'text-nowrap' },
                    { data: 'Quantity', name: 'Quantity', className: 'text-nowrap' },
                    { data: 'Occupied', name: 'Occupied', className: 'text-nowrap' },
                    { data: 'Available', name: 'Available', className: 'text-nowrap' },
                    { data: 'Action', name: 'Action', className: 'text-nowrap' },
                     {data:'created_at',visible:false,searchable:false},
                ]
            });
    }
    function  HisotricalInvenotryIndex()
     {
        if ($.fn.dataTable.isDataTable('#HisotricalInvenotryIndex')) {
            // If initialized, destroy the existing instance
            $('#HisotricalInvenotryIndex').DataTable().clear().destroy();
        }

        var HisotricalInvenotryIndex = $('#HisotricalInvenotryIndex').DataTable({
                "searching": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": true,
                "bAutoWidth": false,
                "scrollX": true,
                "iDisplayLength": 6,
                "order": [[0, 'desc']], // <-- Set default descending order by 1st column (ItemName)
                processing: true,
                serverSide: true,
                order:[[5, 'desc']],
                ajax: {
                    url: "{{ route('resort.accommodation.HisotricalInvenotry') }}",
                    type: 'GET',
                    data: function(d) {
                        d.Inv_Cat_id = $("#Inv_Cat_idFilter").val();
                        d.searchTerm = $('.search').val();
                        d.Items_id = $("#ItemsFilter").val();
                    }
                },
                columns: [
                    { data: 'ItemName', name: 'ItemName', className: 'text-nowrap' },
                    { data: 'ItemCode', name: 'InventoryCode', className: 'text-nowrap' },
                    { data: 'Category', name: 'Category', className: 'text-nowrap' },
                    { data: 'Quantity', name: 'Quantity', className: 'text-nowrap' },
                    { data: 'Status', name: 'Status', className: 'text-nowrap' },
                     {data:'created_at',visible:false,searchable:false},
                ]
            });
    }

</script>
@endsection
