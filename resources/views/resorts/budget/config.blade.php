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
                        <span>WORKFORCE PLANNING</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="row g-4">
                <div class="col-lg-8 col-md-7">
                    <div class="card">
                        @php
                            $BudgetConfig= Common::GetBudgetConfigLinks(Auth::guard('resort-admin')->user()->resort_id);
                        @endphp
                        <form id="BudgetConfigFiles">
                           @csrf
                            <div class="row g-md-4 g-3 mb-4">
                                <div class="col-12">
                                <label for="xpat" class="form-label">XPAT LOCAL RATIO</label>
                                </div>
                                <div class="col-xl-3 col-lg-4 col-sm-5 col">
                                    <label for="xpat" class="form-label">XPAT <span class="req_span">*</span></label>
                                    <input type="number" min="0" class="form-control" id="xpat" name="xpat" value="{{ old('xpat',(isset( $BudgetConfig['xpat'])) ?  $BudgetConfig['xpat'] : '') }}" placeholder="Xpat">
                                </div>
                                <div class="col-auto">
                                    <label for="xpat" class="form-label">&nbsp;</label>
                                    <div class="h-55 d-flex align-items-center">:</div>
                                </div>
                                <div class="col-xl-3 col-lg-4 col-sm-5 col">
                                    <label for="xpat" class="form-label">LOCAL <span class="req_span">*</span></label>
                                    <input type="number" min="0" class="form-control" id="local" name="local" value="{{ old('local',(isset( $BudgetConfig['local'])) ?  $BudgetConfig['local'] : '') }}" placeholder="Local">
                                </div>
                                <div class="col-xl-7 col-lg-9 col-12">
                                    <div class="row g-1 mb-2 justify-content-between align-items-center">
                                        <div class="col-auto">
                                            <label for="uploadCon" class="form-label">UPLOAD PAST YEAR's CONSOLIDATED BUDGET</label>
                                        </div>
                                        <div class="col-auto">
                                            <a href="{{ route('resort.budget.GetConsolidateFile') }}" class="btn btn-theme btn-small consolidatedBudget">Download Template</a>                                        </div>
                                        </div>
                                    <div class="row g-1 mb-3 justify-content-between align-items-center">
                                        <select class="form-control" name="consolidatdebudget_Year" id="year">
                                            <option value="">Select Year</option>
                                        </select>
                                    </div>
                                    <div class="uploadFile-block @if(App\Helpers\Common::checkRouteWisePermission('resort.budget.config',config('settings.resort_permissions.create')) == false) d-none @endif">
                                        <div class="uploadFile-btn">
                                            <a href="#" class="btn btn-themeBlue btn-sm">Upload File</a>
                                            <input type="file" name="consolidatedbudget" id="consolidatedbudget"
                                                accept=".xls,.xlsx">
                                        </div>
                                        <div class="uploadFile-text"> </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" form="BudgetConfigFiles"
                                    class="btn btn-theme btn-sm ConsolidateBudget @if(App\Helpers\Common::checkRouteWisePermission('resort.budget.config',config('settings.resort_permissions.create')) == false) d-none @endif">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-4 col-md-5">
                    <div class="card h-100 card-confingLink">
                        <ul class="listing-wrapper">
                            
                            <li class="@if(App\Helpers\Common::checkRouteWisePermission('resort.benifitgrid.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                                <a href="{{route('resort.benifitgrid.index')}}" class="a-link ">
                                    Benefit Grid List
                                </a>
                            </li>
                            <li class=" @if(App\Helpers\Common::checkRouteWisePermission('resort.benifitgrid.index',config('settings.resort_permissions.create')) == false) d-none @endif">
                                <a href="{{route('resort.benifitgrid.create')}}" class="a-link">
                                    Add Benefit Grids
                                </a>
                            </li>
                            <li class="@if(App\Helpers\Common::checkRouteWisePermission('resort.budget.manning',config('settings.resort_permissions.view')) == false) d-none @endif">
                                <a href="{{route('resort.manning.index')}}" class="a-link">
                                    Resort Configuration
                                </a>
                            </li>
                            <li class="@if(App\Helpers\Common::checkRouteWisePermission('resort.budget.index',config('settings.resort_permissions.view')) == false) d-none @endif">
                                <a href="{{route('resort.budget.index')}}" class="a-link ">
                                    Cost Configuration
                                </a>
                            </li>
                            <li>
                                <a href="{{route('resort.Add.Employee')}}" class="a-link ">
                                    Add Employee 
                                </a>
                            </li>
                        </ul>
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
    $(document).ready(function () {
        // Initialize form validation
        $('#BudgetConfigFiles').validate({
            rules: {
                xpat: {
                    required: true
                },
                local: {
                    required: true
                },
                consolidatdebudget_Year: {
                    required: function() {
                        // Make year required only if a file is selected
                        return $('#consolidatedbudget').get(0)?.files?.length > 0;
                    }
                },
            },
            messages: {
                xpat: {
                    required: "Please enter xpat."
                },
                local: {
                    required: "Please enter local."
                },
                consolidatdebudget_Year: {
                    required: "Please select Year when uploading a file."
                }
            },
            submitHandler: function (form) {
                // Create a new FormData object for file uploads
                var formData = new FormData(form);
                $(".ConsolidateBudget").prop('disabled', true); 
                $.ajax({
                    url: "{{ route('resort.budget.UploadconfigFiles') }}", // Ensure correct route is specified
                    type: "POST",
                    data: formData,
                    contentType: false,  // Important for file uploads
                    processData: false,  // Important for file uploads
                    success: function (response) {
                        if (response.success) {
                            // Update the consolidatedBudget link
                            $(".consolidatedBudget").attr('href', response.data[0]);
                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            
                            $(".ConsolidateBudget").prop('disabled', false); 
                        } else {
                            toastr.error(response.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                            
                            $(".ConsolidateBudget").prop('disabled', false);
                        }
                    },
                    error: function (response) {
                        if (response.status === 422) {
                            var errors = response.responseJSON.errors; // This is where Laravel stores validation errors
                            var errs = '';
                            // Loop through each validation error
                            $.each(errors, function (key, error) {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, "Validation Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        } else {
                            toastr.error(response.responseJSON.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }
                });
            },
            errorPlacement: function (error, element) {
                if (element.hasClass("select2-hidden-accessible")) {
                    error.insertAfter(element.next('.select2')); // For Select2, place error after its container
                } else {
                    error.insertAfter(element); // Default error placement
                }
            },
            highlight: function (element) {
                if ($(element).hasClass("select2-hidden-accessible")) {
                    $(element).next('.select2').find('.select2-selection').addClass('is-invalid');
                } else {
                    $(element).addClass('is-invalid');
                }
            },
            unhighlight: function (element) {
                if ($(element).hasClass("select2-hidden-accessible")) {
                    $(element).next('.select2').find('.select2-selection').removeClass('is-invalid');
                } else {
                    $(element).removeClass('is-invalid');
                }
            }
        });
        // Optional: Add file change listener to trigger validation when file is selected
        $('#consolidatedbudget').on('change', function() {
            $('#BudgetConfigFiles').validate().element('[name="consolidatdebudget_Year"]');
        });
        $('.data-Table').dataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": false,
            "bAutoWidth": false,
            scrollX: true,
            "iDisplayLength": 10,
        });
    });
   function populateYears() {
    const yearSelect = document.getElementById('year');
    const currentYear = new Date().getFullYear() - 1;
    const startYear = currentYear - 10; // Last 10 years + current year

    // Clear existing options except the first one
    while (yearSelect.options.length > 1) {
        yearSelect.remove(1);
    }

    // Add years to select dropdown
    for (let year = currentYear; year >= startYear; year--) {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        yearSelect.appendChild(option);
    }
}

    // Call the function when document is loaded
    document.addEventListener('DOMContentLoaded', populateYears);
</script>
@endsection