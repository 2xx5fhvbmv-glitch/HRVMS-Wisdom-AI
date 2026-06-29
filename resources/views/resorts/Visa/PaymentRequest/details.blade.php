@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
        <div class="container-fluid">
            @if ($message = Session::get('success'))
                <div class="alert alert-success"><p>{{ $message }}</p></div>
            @endif
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
                                    @if($PaymentRequestChild->FullyPaid ?? false)
                                        <span class="badge badge-themeSuccess me-3">Renewed</span>
                                    @else
                                        <a target="_blank" href="{{route('resort.visa.PaymentRequestThrowRenewal',[$id,base64_encode($PaymentRequestChild->id)])}}"  class="btn btn-themeBlue btn-sm btn-xs me-3">Renewal</a>
                                    @endif

                                </div>
                                @if($PaymentRequestChild->WorkPermitDate)
                                    <div class="border-top">
                                        <div class="insurance-Ibox">
                                            <label class="d-block">Work Permit
                                                @if($PaymentRequestChild->WorkPermitPaid ?? false)
                                                    <span class="badge badge-themeSuccess ms-1">Renewed{{ ($PaymentRequestChild->WorkPermitPaidBy ?? null) ? ' by '.$PaymentRequestChild->WorkPermitPaidBy : '' }}{{ ($PaymentRequestChild->WorkPermitPaidAt ?? null) ? ' on '.\Carbon\Carbon::parse($PaymentRequestChild->WorkPermitPaidAt)->format('d M Y') : '' }}</span>
                                                @else
                                                    <span class="badge badge-themeDanger ms-1">Pending</span>
                                                @endif
                                            </label>
                                            <span>Amount: {!! Common::formatMvr($PaymentRequestChild->WorkPermitAmt) !!}</span>
                                            @if(($PaymentRequestChild->WorkPermitMonths ?? 1) > 1)
                                                <span class="d-block text-muted" style="font-size:12px;">Paying {{ $PaymentRequestChild->WorkPermitMonths }} months in advance (through {{ \Carbon\Carbon::parse($PaymentRequestChild->WorkPermitDate)->addMonthsNoOverflow((int) $PaymentRequestChild->WorkPermitMonths)->format('d M Y') }})</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                @if(($PaymentRequestChild->InsurancePrimume ?? 0) > 0)
                                    <div class="border-top">
                                        <div class="insurance-Ibox">
                                            <label class="d-block">Insurance</label>
                                            <span>Amount: {!! Common::formatMvr($PaymentRequestChild->InsurancePrimume) !!}</span>
                                        </div>
                                    </div>
                                @endif
                               
                                @if($PaymentRequestChild->MedicalReportDate)
                                    <div class="border-top">
                                        <div class="insurance-Ibox">
                                            <label class="d-block">Work Permit Medical Test Fee </label>
                                            <span>Amount: {!! Common::formatMvr($PaymentRequestChild->MedicalReportFees) !!}</span>
                                        </div>
                                    </div>
                                @endif
                                @if($PaymentRequestChild->QuotaslotDate)
                                    <div class="border-top">
                                        <div class="insurance-Ibox">
                                            <label class="d-block">Quota Slot
                                                @if($PaymentRequestChild->QuotaslotPaid ?? false)
                                                    <span class="badge badge-themeSuccess ms-1">Renewed{{ ($PaymentRequestChild->QuotaslotPaidBy ?? null) ? ' by '.$PaymentRequestChild->QuotaslotPaidBy : '' }}{{ ($PaymentRequestChild->QuotaslotPaidAt ?? null) ? ' on '.\Carbon\Carbon::parse($PaymentRequestChild->QuotaslotPaidAt)->format('d M Y') : '' }}</span>
                                                @else
                                                    <span class="badge badge-themeDanger ms-1">Pending</span>
                                                @endif
                                            </label>
                                            <span>Amount: {!! Common::formatMvr($PaymentRequestChild->QuotaslotAmt) !!}</span>
                                            @if(($PaymentRequestChild->QuotaslotMonths ?? 1) > 1)
                                                <span class="d-block text-muted" style="font-size:12px;">Paying {{ $PaymentRequestChild->QuotaslotMonths }} months in advance (through {{ \Carbon\Carbon::parse($PaymentRequestChild->QuotaslotDate)->addMonthsNoOverflow((int) $PaymentRequestChild->QuotaslotMonths)->format('d M Y') }})</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                   @if($PaymentRequestChild->VisaDate)
                                    <div class="border-top">
                                        <div class="insurance-Ibox">
                                            <label class="d-block">Visa</label>
                                            <span>Amount: {!! Common::formatMvr($PaymentRequestChild->VisaAmt) !!} </span>
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
            // Gather the markup to print (details boxes + total).
            var bodyHtml = '';
            $('.PayReq-Details-box').each(function() { bodyHtml += this.outerHTML; });
            $('.PayReqprice-bar').each(function() { bodyHtml += this.outerHTML; });

            var w = window.open('', '_blank', 'height=700,width=900');
            var doc = w.document;

            var title = doc.createElement('title');
            title.textContent = 'Payment Request Details';
            doc.head.appendChild(title);

            // Self-contained print styles — the app's stylesheets often haven't
            // loaded in the popup before print() fires, which made the output look
            // unstyled. These inline rules guarantee a clean, consistent layout.
            var style = doc.createElement('style');
            style.textContent =
                'body{font-family:Arial,Helvetica,sans-serif;color:#222;padding:24px;margin:0;}' +
                'h2{text-align:center;margin:0 0 20px;font-size:20px;}' +
                '.PayReq-Details-box{border:1px solid #ddd;border-radius:8px;padding:14px 16px;margin-bottom:14px;page-break-inside:avoid;}' +
                '.PayReq-Details-box h6{margin:0 0 4px;font-size:15px;font-weight:700;display:flex;align-items:center;gap:6px;}' +
                '.PayReq-Details-box p{margin:0;color:#666;font-size:12px;}' +
                '.insurance-Ibox{border-top:1px solid #eee;padding:8px 0;}' +
                '.insurance-Ibox label,.PayReq-Details-box label{display:block;font-weight:700;font-size:13px;margin-bottom:2px;}' +
                '.PayReq-Details-box span{font-size:12px;color:#333;}' +
                '.badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;border:1px solid #bbb;font-weight:600;}' +
                'img{max-height:42px;max-width:42px;border-radius:50%;object-fit:cover;}' +
                '.img-circle{margin-right:8px;}' +
                '.d-sm-flex,.d-flex{display:flex;align-items:center;justify-content:space-between;}' +
                '.PayReqprice-bar{display:flex;justify-content:space-between;align-items:center;border-top:2px solid #333;padding-top:10px;margin-top:14px;font-weight:700;font-size:16px;}' +
                'a,.btn,button{display:none !important;}';
            doc.head.appendChild(style);

            var heading = doc.createElement('h2');
            heading.textContent = 'Payment Request Details';
            doc.body.appendChild(heading);

            var wrap = doc.createElement('div');
            wrap.innerHTML = bodyHtml;
            doc.body.appendChild(wrap);

            doc.close();
            w.focus();
            setTimeout(function() {
                w.print();
                w.onafterprint = function() { w.close(); };
            }, 300);
    });

});
</script>
@endsection