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
                            <span>VISA MANAGEMENT</span>
                            <h1>{{$page_title}}</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto">
                        <div class="d-flex justify-content-end">
                            <a href="#" class="btn btn-theme" disabled="disabled">Sent To Finance</a>
                            <a href="#revise-budgetmodal" data-bs-toggle="modal" class="btn btn-white ms-3">Revise
                                Budget</a>
                        </div>
                    </div> -->
                </div>
            </div>

            <div>
                <div class="card">
                    <div class="card-title">
                        <div class="row g-3 justify-content-between align-items-center">
                            <div class="col-auto">
                                <div class="d-flex justify-content-start align-items-center">
                                    <h3>Payment Request Details</h3>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="d-flex justify-content-sm-end align-items-center">
                                    <a href="javascript:void(0)" class="PrintPaymentRequest btn btn-themeNeon btn-xs me-3 ">Print</a>
                                    <a href="{{route('resort.visa.DownloadPymentRequest',$id)}}"  class="btn btn-themeSkyblue btn-xs  me-3">Download</a>
                            
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($PaymentRequestChildren->isNotEmpty())
                        @foreach($PaymentRequestChildren as $PaymentRequestChild)
                        <div class="mt-2">
                            <div class="PayReq-Details-box">
                                <div class="d-sm-flex justify-content-between ">
                                    <div class=" d-flex align-items-center">
                                        <div class="img-circle"><img src="{{$PaymentRequestChild->ProfilePic}}" alt="user">
                                        </div>
                                        <div>
                                            <h6>{{$PaymentRequestChild->Emp_name}}<span class="badge badge-themeNew">{{ $PaymentRequestChild->Emp_id}}</span> </h6>
                                            <p><b>Department</b>{{$PaymentRequestChild->Department_name}} . <b>Position</b> {{ $PaymentRequestChild->Position_name}}</p>
                                        </div>
                                    </div>
                                    <strong class="d-block text-end mt-sm-0 mt-2">Amount : {!! Common::formatMvr($PaymentRequestChild->TotalAmount) !!}</strong>
                                    <a target="_blank" href="{{route('resort.visa.PaymentRequestThrowRenewal',[$id,base64_encode($PaymentRequestChild->id)])}}"  class="btn btn-themeBlue btn-sm btn-xs me-3">Renewal</a>

                                </div>
                                @if($PaymentRequestChild->WorkPermitDate)
                                    <div class="border-top">
                                        <div class="insurance-Ibox">
                                            <label class="d-block">Work Permit
                                                @if($PaymentRequestChild->WorkPermitPaid ?? false)
                                                    <span class="badge badge-themeNeon ms-1">Renewed{{ ($PaymentRequestChild->WorkPermitPaidBy ?? null) ? ' by '.$PaymentRequestChild->WorkPermitPaidBy : '' }}{{ ($PaymentRequestChild->WorkPermitPaidAt ?? null) ? ' on '.\Carbon\Carbon::parse($PaymentRequestChild->WorkPermitPaidAt)->format('d M Y') : '' }}</span>
                                                @else
                                                    <span class="badge badge-themeDanger ms-1">Pending</span>
                                                @endif
                                            </label>
                                            <span>Last Paid: {{$PaymentRequestChild->LastWorkPermitDate ?? 'N/A'}} | Due Date: {{$PaymentRequestChild->WorkPermitDate ?? 'N/A'}} | Amount: {!! Common::formatMvr($PaymentRequestChild->WorkPermitAmt) !!}
                                                @if(($PaymentRequestChild->WorkPermitMonths ?? 1) > 1)<span class="badge badge-themeNew ms-1">{{ $PaymentRequestChild->WorkPermitMonths }} months</span>@endif
                                            </span>
                                        </div>
                                    </div>
                                @endif
                                @if($PaymentRequestChild->InsuranceDate)
                                    <div class="border-top">
                                        <div class="insurance-Ibox">
                                            <label class="d-block">Insurance</label>
                                            <span>Last Paid: {{$PaymentRequestChild->LastInsuranceDate ?? 'N/A'}} | Due Date:  {{$PaymentRequestChild->InsuranceDate ?? 'N/A'}} | Amount: {!! Common::formatMvr($PaymentRequestChild->InsurancePrimume) !!}</span>
                                        </div>
                                    </div>
                                @endif
                               
                                @if($PaymentRequestChild->MedicalReportDate)
                                    <div class="border-top">
                                        <div class="insurance-Ibox">
                                            <label class="d-block">Work Permit Medical Test Fee </label>
                                            <span>Last Paid: {{$PaymentRequestChild->LastMedicalReportDate?? 'N/A'}} | Due Date: {{$PaymentRequestChild->MedicalReportDate?? 'N/A'}} | Amount: {!! Common::formatMvr($PaymentRequestChild->MedicalReportFees) !!}</span>
                                        </div>
                                    </div>
                                @endif
                                @if($PaymentRequestChild->QuotaslotDate)
                                    <div class="border-top">
                                        <div class="insurance-Ibox">
                                            <label class="d-block">Quota Slot
                                                @if($PaymentRequestChild->QuotaslotPaid ?? false)
                                                    <span class="badge badge-themeNeon ms-1">Renewed{{ ($PaymentRequestChild->QuotaslotPaidBy ?? null) ? ' by '.$PaymentRequestChild->QuotaslotPaidBy : '' }}{{ ($PaymentRequestChild->QuotaslotPaidAt ?? null) ? ' on '.\Carbon\Carbon::parse($PaymentRequestChild->QuotaslotPaidAt)->format('d M Y') : '' }}</span>
                                                @else
                                                    <span class="badge badge-themeDanger ms-1">Pending</span>
                                                @endif
                                            </label>
                                            <span>Last Paid: {{$PaymentRequestChild->LastQuotaslotDate?? 'N/A'}} | Due Date: {{$PaymentRequestChild->QuotaslotDate?? 'N/A'}} | Amount: {!! Common::formatMvr($PaymentRequestChild->QuotaslotAmt) !!}
                                                @if(($PaymentRequestChild->QuotaslotMonths ?? 1) > 1)<span class="badge badge-themeNew ms-1">{{ $PaymentRequestChild->QuotaslotMonths }} months</span>@endif
                                            </span>
                                        </div>
                                    </div>
                                @endif
                                   @if($PaymentRequestChild->VisaDate)
                                    <div class="border-top">
                                        <div class="insurance-Ibox">
                                            <label class="d-block">Visa</label>
                                            <span>Last Paid: {{$PaymentRequestChild->LastVisaDate?? 'N/A'}} | Due Date: {{$PaymentRequestChild->VisaDate?? 'N/A'}} | Amount: {!! Common::formatMvr($PaymentRequestChild->VisaAmt) !!} </span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            
                           
                        </div>
                        @endforeach
                    @endif
                        <div class="mt-3 PayReqprice-bar d-flex align-items-center justify-content-between">
                                <span>Total Amount</span>
                                <label>{!! Common::formatMvr($GrandTotal ?? 0) !!}</label>
                            </div>
                            <div class="d-none d-md-block" style="height: 136px;"></div>

        
                </div>
            </div>

        </div>
    </div>
  
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
$(document).ready(function(){

    $(document).on("click",".PrintPaymentRequest",function(){
            var printContents = $('<div>');
            // Clone the payment request details and total
            $('.PayReq-Details-box').each(function() {
                printContents.append($(this).clone());
            });
            printContents.append($('.PayReqprice-bar').clone());

            // Create a new window for printing
            var printWindow = window.open('', '_blank', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Payment Request Details</title>');

            // Include CSS stylesheets
            $('link[rel="stylesheet"]').each(function() {
                printWindow.document.write('<link rel="stylesheet" href="' + $(this).attr('href') + '">');
            });

            // Add print-specific styles
            // printWindow.document.write('<style>@media print { body { padding: 20px; } .btn { display: none !important; } }</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<div class="container-fluid">');
            printWindow.document.write('<h2 class="text-center mb-4">Payment Request Details</h2>');
            printWindow.document.write(printContents.html());
            printWindow.document.write('</div></body></html>');

            printWindow.document.close();
            printWindow.focus();
            setTimeout(function() {
                printWindow.print();
                printWindow.onafterprint = function() {
                    printWindow.close();
                };
            }, 500);
    });

});
</script>
@endsection