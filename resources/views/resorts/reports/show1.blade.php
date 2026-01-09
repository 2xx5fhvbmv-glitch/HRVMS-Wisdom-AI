@extends('resorts.layouts.app')
@section('page_tab_title', 'Report Details')

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
                        <span>Report</span>
                        <h1>Report Details</h1>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-title">
                <div class="row g-1">
                    <div class="col">
                        <h3>
                            Report: {{ $report->name }}
                            @if($report->description)
                                <p class="text-muted">{{ $report->description }}</p>
                            @endif
                        </h3>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('reports.create') }}" class="btn btn-sm btn-theme">Create Report</a>
                    </div>
                </div>  
                <hr>
               <form id="ExportFormReport" method="get" action="{{route('report.export')}}">

                                            @csrf
                                            <input type="hidden" name="report_id" value="{{ base64_encode($report->id) }}">
                                            <input type="hidden" name="format" id="format" value="">
                                            <input type="hidden" name="Form_formdate" id="Form_formdate" value="">
                                            <input type="hidden" name="Form_todate" id="Form_todate" value="">
                                        </form>
                   <form id="GetReportData" data-parsley-validate>
                        <input type="hidden" name="report_id" value="{{ $report->id }}">
                        <div class="row">
                            <div class="col-xl-3">
                                <label for="todate">To Date</label>
                                <input type="text"
                                    class="form-control datepicker"
                                    name="todate"
                                    id="todate"
                                    placeholder="Select To Date"
                                    required
                                    data-parsley-required-message="Please select To date." />
                            </div>

                            <div class="col-xl-3">
                                <label for="formdate">From Date</label>
                                <input type="text"
                                    class="form-control datepicker"
                                    name="formdate"
                                    id="formdate"
                                    placeholder="Select From Date"
                                    required
                                    data-parsley-gte="#todate"
                                    data-parsley-required-message="Please select From date." />
                            </div>

                            <div class="col-xl-1">
                                <button type="submit" class="btn btn-sm btn-theme SearchReport">Search</button>
                            </div>
                            <div class="col-xl-1 page-hedding">
                                <div class="d-flex">
                                    <div class="d-flex align-items-center">
                                      
                                        <div class="dropdown">
                                        
                                            <button class="btn btn-sm btn-primary dropdown-toggle" disabled type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa fa-download"></i> Export Report
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                                <li>
                                                    <a class="dropdown-item exportReports" href="javascript:void(0)" data-report="{{base64_encode($report->id)}}" data-format='csv'>
                                                        <i class="fa fa-file-csv"></i> CSV
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item exportReports" href="javascript:void(0)" data-report="{{base64_encode($report->id)}}" data-format='excel'>
                                                        <i class="fa fa-file-excel"></i> Excel
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item exportReports" href="javascript:void(0)" data-report="{{base64_encode($report->id)}}" data-format='pdf'>
                                                        <i class="fa fa-file-pdf"></i> PDF
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>                                    
                                    </div>
                                    <div class="d-flex align-items-center ms-2">
                                        <button class="btn btn-sm btn-theme AIInSide" disabled type="button"aria-expanded="false">AI InSide</button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </form>

            </div>  
            
            <div class="card-body">
                <div class="table-responsive" id ="reportTableData">
                    <table class="table  bordered">
                        <thead>
                            <tr>
                                <th colspan="5">No Record Found..</th>
                            </tr>
                        </thead>
                     
                    </table>                    
                </div>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="col">
                        <a href="{{ route('resort.report.index') }}" class="btn btn-sm btn-danger">Back to Reports</a>
                    </div>  
                    <div class="col-auto">
                        <div class="dropdown">
                            
                         
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
<style>
    .relation-info {
        margin-bottom: 0;
        font-size: 0.9rem;
    }
    .relation-item {
        margin-bottom: 4px;
    }
    .relation-item:last-child {
        margin-bottom: 0;
    }
</style>
@endsection
@section('import-scripts')
<script>
$(document).ready(function() {

    $("#GetReportData").parsley();
   
        
        $("#todate").datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,      // Close the picker after selection
            todayHighlight: true  // Highlight today's date
        });
         $("#formdate").datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,      // Close the picker after selection
            todayHighlight: true  // Highlight today's date
        });
     

        function parseDateString(dateStr) 
        {
            const parts = dateStr.split('/');
            if (parts.length !== 3) return null;
            const [day, month, year] = parts;
            return new Date(`${year}-${month}-${day}`);
        }

    // Custom validator: From Date must be <= To Date
    window.Parsley.addValidator('gte', {
        validateString: function(value, requirement) {
            const fromDateStr = $(requirement).val();
            if (!value || !fromDateStr) return true;

            const toDate = parseDateString(value);
            const fromDate = parseDateString(fromDateStr);

            return fromDate <= toDate;
        },
        messages: {
            en: 'From Date must be less than or equal to To Date.'
        }
    });

    // Trigger revalidation when From Date changes
    $('#formdate').on('change', function () {
        
        $(".fromtodate").val($("#todate").val());
        $(".from_Form_date").val($(this).val());
        $('#todate').parsley().validate();


    });

    // Handle form submission
    $('#GetReportData').on('submit', function (e) {
        e.preventDefault();
        if ($(this).parsley().validate()) 
        {
            var formData = $(this).serialize();
            $.ajax({
                type: 'POST',
                url: "{{route('reports.FetchReportData')}}",
                data: formData,
                success: function (response) {
                    $('#reportTableData').empty();
                    $('#reportTableData').append(response.html);
                    if(response.columns != 0)
                    {
                        $(".exportData").removeAttr('disabled');
                        $(".AIInSide").removeAttr('disabled');
                                                $(".dropdown-toggle").removeAttr('disabled');

                        
                    }
                    else
                    {
                        $(".AIInSide").attr('disabled', 'disabled');
                        $(".exportData").attr('disabled', 'disabled');
                        $(".dropdown-toggle").attr('disabled', 'disabled');
                    }
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        }
    });

    
    $(".AIInSide").on("click",function(e)
    {
        e.preventDefault();
        alert("AI InSide is not available yet.");
        return false
    });
 
    $(document).on("click", ".exportReports", function(e) {
        e.preventDefault();
        const reportId = $(this).data('report');
        const format = $(this).data('format');
        $("#format").val(format);
        $("#report_id").val(reportId);
        $("#Form_formdate").val($("#formdate").val());
        $("#Form_todate").val($("#todate").val());
        submitExportForm();  
    });
    function submitExportForm() {
        $("#ExportFormReport").submit();
    }
});
</script>
@endsection