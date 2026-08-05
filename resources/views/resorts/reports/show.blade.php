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
                        <span>Report</span>
                        <h1>{{ $page_title }}</h1>
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
                        <a href="{{ route('reports.create') }}" class="btn eb-btn-accent @if(Common::checkRouteWisePermission('resort.report.index',config('settings.resort_permissions.create')) == false) d-none @endif">Create Report</a>
                    </div>
                </div>  
                <hr>
                <form id="ExportFormReport" method="get" action="{{route('report.export')}}">

                    @csrf
                    <input type="hidden" id="report_id" name="report_id" value="{{ base64_encode($report->id) }}">
                    <input type="hidden" id="format"  name="format" id="format" value="">
                    <input type="hidden" id="Form_formdate" name="Form_formdate" id="Form_formdate" value="{{$report->from_date}}">
                    <input type="hidden" id="Form_todate" name="Form_todate" id="Form_todate" value="{{$report->to_date}}">
                </form>
                   <form id="GetReportData" data-parsley-validate>
                        <input type="hidden" name="report_id" value="{{ $report->id }}">
                        <div class="row">
                            <div class="col-xl-3">
                                <label for="formdate">From Date <span class="req_span">*</span></label>
                                <input type="text"
                                    disabled
                                    class="form-control datepicker"
                                    name="disabledformdate"
                                    value="{{$form_date}}"
                                    id="formdate"
                                    placeholder="Select From Date"
                                    required
                                    data-parsley-gte="#todate"
                                    data-parsley-required-message="Please select From date." />

                                <input type="hidden" name="formdate" value="{{$form_date}}"/>

                            </div>

                            <div class="col-xl-3">
                                <label for="todate">To Date <span class="req_span">*</span></label>
                                <input type="text"
                                  disabled
                                    class="form-control datepicker"
                                    name="disabledtodate"
                                    value="{{$to_date}}"
                                    id="todate"
                                    placeholder="Select To Date"
                                    required
                                    data-parsley-required-message="Please select To date." />
                                      <input type="hidden" name="todate"value="{{$to_date}}"/>
                            </div>

                            <div class="col-xl-1">
                                <button type="submit" class="btn eb-btn-primary SearchReport">Search</button>
                            </div>
                            <div class="col-xl-1 page-hedding">
                                <div class="d-flex">
                                    <div class="d-flex align-items-center">
                                      
                                        <div class="dropdown">
                                        
                                            <button class="btn eb-btn-accent dropdown-toggle" disabled type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
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
                                        <button class="btn eb-btn-accent AIInSide" disabled type="button"aria-expanded="false">WAI Insights</button>
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
                   <div id="insightsWrapper" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                            <h5 class="mb-0"><i class="fa fa-robot me-1"></i> WAI Insights</h5>
                            <button type="button" class="btn eb-btn-secondary" id="backToReportData">
                                <i class="fa fa-arrow-left me-1"></i> Back to Report Data
                            </button>
                        </div>
                        <div id="jsonContainer" class="wai-insights"></div>
                    </div>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="col">
                        <a href="{{ route('resort.report.index') }}" class="btn eb-btn-secondary">Back to Reports</a>
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
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
<style>
    .wai-insights {
        background-color: #fff;
        border: 1px solid #e3e6ef;
        border-radius: 8px;
        padding: 20px 24px;
        color: #2b2f3a;
        line-height: 1.6;
        font-size: 0.95rem;
    }
    .wai-insights h1 { font-size: 1.5rem; font-weight: 700; margin: 0 0 .75rem; padding-bottom: .4rem; border-bottom: 2px solid #eef0f5; }
    .wai-insights h2 { font-size: 1.25rem; font-weight: 700; margin: 1.4rem 0 .6rem; color: #1f2733; }
    .wai-insights h3 { font-size: 1.05rem; font-weight: 600; margin: 1.1rem 0 .4rem; color: #2b6cb0; }
    .wai-insights h4 { font-size: .98rem; font-weight: 600; margin: .9rem 0 .35rem; }
    .wai-insights p { margin: 0 0 .8rem; }
    .wai-insights ul, .wai-insights ol { margin: 0 0 .9rem; padding-left: 1.4rem; }
    .wai-insights li { margin-bottom: .35rem; }
    .wai-insights strong { color: #1f2733; font-weight: 600; }
    .wai-insights code { background: #f3f4f6; padding: 1px 6px; border-radius: 4px; font-size: .85em; }
    .wai-insights table { width: 100%; border-collapse: collapse; margin: 0 0 1rem; }
    .wai-insights th, .wai-insights td { border: 1px solid #e3e6ef; padding: 6px 10px; text-align: left; }
    .wai-insights th { background: #f7f8fc; }
    .wai-insights hr { border: 0; border-top: 1px solid #eef0f5; margin: 1.2rem 0; }
    .wai-insights > *:first-child { margin-top: 0; }
    .wai-insights > *:last-child { margin-bottom: 0; }
</style>
@endsection
@section('import-scripts')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
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
            console.log("From Date:", fromDate, "To Date:", toDate);

            return toDate <= fromDate;
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
    function fetchReportData() {
        var $form = $('#GetReportData');
        var formData = $form.serialize();
        $.ajax({
            type: 'POST',
            url: "{{route('reports.FetchReportData')}}",
            data: formData,
            success: function (response) {
                $('#reportTableData').empty();
                $('#reportTableData').append(response.html);
                if (response.columns != 0) {
                    $(".exportData").removeAttr('disabled');
                    $(".AIInSide").removeAttr('disabled');
                    $(".dropdown-toggle").removeAttr('disabled');
                } else {
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

    $('#GetReportData').on('submit', function (e) {
        e.preventDefault();
        if ($(this).parsley().validate()) {
            fetchReportData();
        }
    });

    // Auto-fetch once the page loads so data is visible without clicking Search
    fetchReportData();

    
    $(".AIInSide").on("click",function(e)
    {
        $(this).attr('disabled',true).text('WAI Insights working... Please Wait');
        e.preventDefault();
        $.ajax({
                type: 'POST',
                url: "{{route('reports.AiInsideReport')}}",
                data: {
                        "_token": "{{ csrf_token() }}",
                        "report_id": $("#report_id").val(),
                        "todate": $("#todate").val(),
                        "formdate": $("#formdate").val()
                },
                success: function (response)
                {
                    $(".AIInSide").attr('disabled', false).text('WAI Insights');

                    // Render the markdown the AI returns as formatted HTML.
                    var md = response.data || '';
                    var html = (typeof marked !== 'undefined')
                        ? (marked.parse ? marked.parse(md) : marked(md))
                        : $('<div>').text(md).html();
                    $('#jsonContainer').html(html);

                    // Show insights, keep the report data intact behind it so the
                    // user can return to it without reloading.
                    $('#reportTableData').hide();
                    $('#insightsWrapper').show();
                },
                error: function (xhr, status, error) {
                    $(".AIInSide").attr('disabled', false).text('WAI Insights');
                    console.error(xhr.responseText);
                }
            });
    });

    // Return from the insights view back to the report data table.
    $(document).on('click', '#backToReportData', function () {
        $('#insightsWrapper').hide();
        $('#reportTableData').show();
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