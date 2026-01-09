@extends('resorts.layouts.app')
@section('page_tab_title' ,"Dashboard")

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
                            <span>Total Incidents</span>
                            <h1>Dashboard</h1>
                        </div>
                    </div>
                        <div class="col-lg-2 col-md-3 col-auto ms-auto">
                            <select class="form-select select2t-none" id="checkYearStatus"aria-label="Default select example">
                                <option value="Weekly">Weekly</option>
                                <option selected value="Monthly">Monthly</option>
                                <option value="Quarterly">Quarterly</option>
                                <option value="Semiannual">Semiannual</option> <!-- 6 month -->
                                <option value="Yearly">Yearly</option>
                            </select>
                        </div>
                </div>
            </div>

            <div class="row g-3 g-xxl-4 card-heigth card-incidentHr">
                   @if($VisaWallets->isNotEmpty())
                    @foreach($VisaWallets as $VisaWallet)
                        <div class="col-lg-3 col-sm-6">
                            <div class="card dashboard-boxcard timeAttend-boxcard">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="mb-0  fw-500">{{$VisaWallet->WalletName}}</p>
                                        <strong>MVR {{$VisaWallet->Amt}}</strong>
                                    </div>
                                    <a href="#">
                                        <img src="assets/images/arrow-right-circle.svg" alt="" class="img-fluid">
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
                <div class="col-xl-6">
                    <div class=" card card-visa-management ">
                        <div class=" card-title pb-0">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item VisaModuleLink" data-id="QuotaSlot" role="presentation" >
                                    <button class="nav-link active"  id="tab1" data-bs-toggle="tab" 
                                    data-bs-target="#QuotaSlot" type="button" role="tab" aria-controls="QuotaSlot" aria-selected="true">
                                    Quota Slot Fee</button>
                                </li>
                                <li class="nav-item VisaModuleLink"  data-id="WorkPermitFee"role="presentation">
                                    <button class="nav-link " id="#tab2" data-bs-toggle="tab"
                                     data-bs-target="#tabPane2" type="button" role="tab" aria-controls="tabPane2" 
                                     aria-selected="false">Work Permit Fee</button>
                                </li>
                                <li class="nav-item VisaModuleLink"  data-id="Insurance" role="presentation">
                                    <button class="nav-link " id="#tab3" data-bs-toggle="tab" data-bs-target="#tabPane3"
                                        type="button" role="tab" aria-controls="tabPane3" aria-selected="false">Insurance</button>
                                </li>
                                <li class="nav-item VisaModuleLink"  data-id="PermitMedicalFee"  role="presentation">
                                    <button class="nav-link "id="#tab4" data-bs-toggle="tab" data-bs-target="#tabPane4"
                                        type="button" role="tab" aria-controls="tabPane4" aria-selected="false">Work
                                        Permit Medical Fee</button>
                                </li>
                                <li class="nav-item VisaModuleLink"  data-id="WorkVisa" role="presentation">
                                    <button class="nav-link" id="#tab4" data-bs-toggle="tab" data-bs-target="#tabPane4"
                                        type="button" role="tab" aria-controls="tabPane4" aria-selected="false">Work Visa
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <div class="tab-content" id="myTabContent">
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 @if(Common::checkRouteWisePermission('resort.visa.PaymentRequestIndex',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="card card-incidentSeverity">
                        <div class="card-title">
                            <h3>Payment Request Tracker</h3>
                        </div>
                        <div class="leaveUser-main">
                            
                            <div class="leaveUser-bgBlock">
                                <h6>Pending</h6>
                                <strong>{{$DetermineSeverity['Pending']}}</strong>
                            </div>
                            <div class="leaveUser-bgBlock">
                                <h6>Complete</h6>
                                <strong>{{$DetermineSeverity['Complete']}}</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                    <div class="card">
                        <div class="card-title">
                            <h3>Nationality-wise breakdown</h3>
                        </div>
                        <div class="incident-chart mb-3">
                            <canvas id="myDoughnutChart"></canvas>
                        </div>
                        <div class="row g-2 justify-content-center  myDoughnutChartLabel">
                         
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 @if(Common::checkRouteWisePermission('resort.visa.Expiry',config('settings.resort_permissions.view')) == false) d-none @endif">
                    <div class="card">
                        <div class="card-title">
                            <div class="row justify-content-between align-items-center g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">Expiry Dates Overview</h3>
                                </div>
                                <div class="col-auto">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="form-group">
                                            <input type="text" class="form-control datepicker" id="expiryDate" placeholder="Select date">
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <a href="{{route('resort.visa.Expiry')}}" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="expiry-dates-overview-content">
                            <div class="row  align-items-center">
                                <table id="ExpiryOverView-table" class="table w-100">
                                    <thead>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                              

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="card card-comParticipation " id="card-comParticipation">
                        <div class="card-title mb-md-3">

                            <div class="row justify-content-between align-items-center g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">Liability Breakdown</h3>
                                </div>
                                <div class="col-auto">

                                    <select name="liabilityYear" id="liabilityYear" class="form-select">
                                      
                                        <?php
                                            $currentYear = date('Y');
                                            $startingYear = $currentYear - 4; // Adjusted to show 5 years including current
                                        ?>
                                        @for($i = $startingYear; $i < $currentYear; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                        <option selected value="{{ $currentYear }}">{{ $currentYear }} </option>
                                    </select>
                                  
                                </div>
                            </div>
                        </div>
                        <div class="row g-md-4 g-2 align-items-center">
                            <div class="col-xxl-8 col-xl-12 col-md-9">
                                <!-- <canvas id="myStackedBarChart" width="544" height="326"></canvas> -->
                                <canvas id="myStackedBarChart" width="544" height="326"></canvas>
                            </div>
                            <div class="col-xxl-4 col-xl-auto col-md-3">
                                <div class="row g-2 doughnut-labelTop AppendMyStackBarChart">
                                    <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                        <div class="doughnut-label">
                                            <span class="bg-theme"></span>Workpermit - MVR 10,000
                                        </div>
                                    </div>
                                    <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                        <div class="doughnut-label">
                                            <span class="bg-themeSkyblueLight"></span>Slot Fee - MVR 25,000
                                        </div>
                                    </div>
                                    <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                        <div class="doughnut-label">
                                            <span class="bg-themeYellow"></span>Insurance - MVR 25,000
                                        </div>
                                    </div>
                                    <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                        <div class="doughnut-label">
                                            <span class="bg-themeSkyblue"></span>Work Permit Medical - MVR
                                            25,000
                                        </div>
                                    </div>
                                    <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                        <div class="doughnut-label">
                                            <span class=" bg-themeGray"></span>Photo - MVR 25,000
                                        </div>
                                    </div>
                                    <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                        <div class="doughnut-label fw-bold">
                                            Total: MVR 1,10,000
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-9 col-lg-8">
                    <div class="card card-visa-manDepositRe">
                        <div class=" card-title">
                            <div class="row justify-content-between align-items-center g-md-2 g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">Deposit Refund Requests</h3>
                                </div>
                                <div class="col-6">
                                    <div class="row justify-content-end g-md-3 g-2 align-items-center">
                                        <div class="col-xl-4 col-md-4 col-sm-4 col-6">
                                            <select class="form-select" name="position" id="position">
                                                <option ></option>
                                                @if($Position->isNotEmpty())
                                                    @foreach($Position as $pos)
                                                        <option value="{{ base64_encode($pos->id) }}">{{ $pos->position_title }}</option>
                                                    @endforeach 
                                                @endif
                                      
                                            </select>
                                        </div>
                                        <div class="col-xl-4 col-md-4 col-sm-4 col-6">

                                            <input type="text" id="DepositeDate" class="form-control form-control-sm datepicker"placeholder="Select Date">

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <table  class="table  w-100 depositWallet-table" id="depositWallet-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Nationality</th>
                                    <th>Deposit Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                             
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4">
                    <div class="card card-wiINsightPayroll card-visa-manINsightPayroll" id="card-wiINsightPayroll">
                        <div class=" card-title">
                            <div class="row justify-content-between align-items-center g-md-3 g-1">
                                <div class="col">
                                    <h3 class="text-nowrap">WI Insight's</h3>
                                </div>
                                <div class="col-auto">
                                    <a href="#" class="a-link">View All</a>
                                </div>
                            </div>
                        </div>
                        <div class="leaveUser-main">
                            <div class="leaveUser-block">
                                <div class="img">
                                    <img src="assets/images/wisdom-ai-small.svg" alt="image">
                                </div>
                                <div>
                                    <h6>Lorem Ipsum is dummy text</h6>
                                    <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting.
                                    </p>
                                    <div>
                                        <a href="#" class="a-linkTheme">View Details</a>
                                    </div>
                                </div>
                            </div>
                            <div class="leaveUser-block">
                                <div class="img">
                                    <img src="assets/images/wisdom-ai-small.svg" alt="image">
                                </div>
                                <div>
                                    <h6>Lorem Ipsum is dummy text</h6>
                                    <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting.
                                    </p>
                                    <div>
                                        <a href="#" class="a-linkTheme">View Details</a>
                                    </div>
                                </div>
                            </div>
                            <div class="leaveUser-block">
                                <div class="img">
                                    <img src="assets/images/wisdom-ai-small.svg" alt="image">
                                </div>
                                <div>
                                    <h6>Lorem Ipsum is dummy text</h6>
                                    <p>Lorem ipsum is simply dummy text of the typesetting industry Lorem typesetting.
                                    </p>
                                    <div>
                                        <a href="#" class="a-linkTheme">View Details</a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-3 g-xxl-4">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-title">
                                    <h3>Transfer Betweem Wallet</h3>
                                </div>
                           <form id="TransferAmountform"  data-parsley-validate enctype="multipart/form-data">
                                @csrf
                                <div class="row g-md-4 g-3">
                                    <div class="col-sm-6">
                                        <label for="from-wallet" class="form-label">FROM WALLET</label>
                                        <select class="form-select select2t-none" id="from-wallet" name="from_wallet" required data-parsley-errors-container="#from-wallet-error" aria-label="Default select example">
                                            <option value=""></option>
                                            @if($VisaWallets->isNotEmpty())
                                                @foreach($VisaWallets as $VisaWallet)
                                                    <option value="{{ base64_encode($VisaWallet->id) }}">{{ $VisaWallet->WalletName }}</option>
                                                @endforeach 
                                            @endif
                                        </select>
                                        <div id="from-wallet-error" class="text-danger mt-1"></div>
                                    </div>

                                    <div class="col-sm-6">
                                        <label for="to-wallet" class="form-label">TO WALLET</label>
                                        <select class="form-select select2t-none" id="to-wallet" name="to_wallet" required data-parsley-notequal="#from-wallet" data-parsley-errors-container="#to-wallet-error" aria-label="Default select example">
                                            <option value=""></option>
                                            @if($VisaWallets->isNotEmpty())
                                                @foreach($VisaWallets as $VisaWallet)
                                                    <option value="{{ base64_encode($VisaWallet->id) }}">{{ $VisaWallet->WalletName }}</option>
                                                @endforeach 
                                            @endif
                                        </select>
                                        <div id="to-wallet-error" class="text-danger mt-1"></div>
                                    </div>

                                    <div class="col-12">
                                        <label for="amount" class="form-label">AMOUNT (MVR)</label>
                                        <input type="text" class="form-control" name="Amt" id="amount" placeholder="Amount" required data-parsley-type="number" data-parsley-min="1" />
                                    </div>

                                    <div class="col-md-6">
                                        <label for="comments" class="form-label">COMMENTS</label>
                                        <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="attachments" class="form-label">ATTACHMENTS</label>
                                        <div class="uploadFile-block">
                                            <div class="uploadFile-btn">
                                                <a href="javascript:void(0)" class="btn btn-themeBlue btn-sm">Upload Files</a>
                                                <input type="file" name="transectionFile" id="uploadFile" name="attachment">
                                            </div>
                                            <div class="uploadFile-text"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer text-end mt-xl-4 mt-3">
                                    <button type="submit" class="btn btn-themeBlue btn-sm">Transfer Amount</button>
                                </div>
                            </form>




                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="card reconciliation-card">
                                <div class="card-title">
                                    <h3>Reconciliation</h3>
                                </div>
                                <div class="row g-md-4 g-3">
                                    <div class="col-md-6">
                                        <h4>Total: Four Season's</h4>
                                        <div class="row g-2">
                                            @if($VisaWallets->isNotEmpty())
                                                @foreach($VisaWallets as $VisaWallet)
                                                    <div class="col-xl-6 col-lg-12 col-6">
                                                        <div class="reconciliation-block">
                                                            <div>
                                                                <h6>{{$VisaWallet->WalletName}}</h6>
                                                                <strong>MVR {{number_format($VisaWallet->Amt,2)}}</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else   
                                                <div class="col-12">
                                                    <p class="text-center">No wallets available.</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h4>Total: Xpats</h4>
                                        <div class="row g-2"  id="VisaXpactAmounts">
                                            @if($VisaXpactAmounts->isNotEmpty())
                                                @foreach($VisaXpactAmounts as $VisaWallet)
                                                    <div class="col-xl-6 col-lg-12 col-6">
                                                         
                                                        <div class="reconciliation-block">
                                                            <div>
                                                                <div class="d-flex align-items-center">
                                                                       <h6>{{$VisaWallet->Xpact_WalletName}}   <a href="javascript:void(0)" class="edit-visa-wallet"  data-amt="{{base64_encode($VisaWallet->Xpact_Amt)}}" data-name="{{base64_encode($VisaWallet->Xpact_WalletName)}}" data-id="{{ base64_encode($VisaWallet->id) }}" class="me-2">
                                                                        <img src="{{URL::asset('resorts_assets/images/edit.svg')}}" alt="icon">
                                                                    </a> </h6>
                                                                </div>
                                                            
                                                                <strong>MVR {{number_format($VisaWallet->Xpact_Amt,2)}}</strong>
                                                            </div>
                                                            
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else   
                                                <div class="col-12">
                                                    <p class="text-center">No wallets available.</p>
                                                </div>
                                            @endif
                                    
                                        </div>

                                    </div>
                                    <div class="col-12">
                                        @if(!empty($reconiliation))
                                            @foreach($reconiliation as $r)

                                            <div class="RecoDiff-block    mb-1 d-flex align-items-center">
                                               {{$r['status']}} In {{$r['wallet_name']}}
                                            </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
                <div class="col-lg-6">
                    <div class="row g-3 g-xxl-4">
                        <div class="col-lg-12">
                            <div class="card Nat-wise-Brecard">
                                <div class=" card-title">
                                    <div class="row justify-content-between align-items-center g-1">
                                        <div class="col">
                                            <h3 class="text-nowrap">Nationality-wise Breakdown</h3>
                                        </div>
                                        <div class="col-auto">
                                            <a href="{{route('resort.visa.NatioanlityWiseEmployeeDepositAndCountDetails')}}" class="a-link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div >
                                    <table id="NatioanlityWiseEmployeeDepositAndCount" class="table Nat-wise-BreTable w-100">
                                        <thead>
                                            <tr>
                                                <th>Nationality</th>
                                                <th>Deposit Amount</th>
                                                <th>Employees</th>
                                                <th>Action</th>

                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>




                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="card">
                                <div class=" card-title">
                                    <h3 class="text-nowrap">Transaction History</h3>

                                </div>
                                <div class="">
                                    <table id="transaction-history-table" class="table transaction-history-table w-100">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>From Wallet</th>
                                                <th>To Wallet</th>
                                                <th>Amount</th>
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
            </div>
        </div>
    </div>
    <div class="modal fade" id="VisaXpactEditAmt-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Xpact Amount</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="VisaXpactEditAmtForm" data-parsley-validate>
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <input type="hidden" class="form-control" id="Xpact_id" name="id">

                        <div class="mt-3 mb-3">
                            <label class="form-label">Xpact Wallet Name <span class="red-mark">*</span></label>
                            <input type="text" readonly class="form-control" id="Xpact_WalletName" name="Xpact_WalletName"
                                placeholder="Wallet Name"
                                required
                                data-parsley-required-message="Wallet name is required.">
                        </div>

                        <div class="mt-3 mb-3">
                            <label class="form-label">Xpact Wallet Amount <span class="red-mark">*</span></label>
                            <input type="number" min="1" class="form-control" id="Xpact_WalletAmt" name="Xpact_WalletAmt"
                                placeholder="Wallet Amount"
                                required
                                data-parsley-required-message="Amount is required."
                                data-parsley-type="number"
                                data-parsley-type-message="Please enter a valid number."
                                data-parsley-min="1"
                                data-parsley-min-message="Amount must be at least 1.">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="NatioanlityWiseEmployee-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
              
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
                <div class="modal-body">
                    <div class="row">
                        <table  class="table  w-100 NatioanlityWiseEmployee-table" >
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Departmeent</th>
                                </tr>
                            </thead>
                            <tbody id="NatioanlityWiseEmployee-table">
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
          
        </div>
    </div>
</div>
@endsection

@section('import-css')

@endsection

@section('import-scripts')
<script>
$(document).ready(function () 
{

    $("#checkYearStatus").select2({
        placeholder: "Select Year",
        allowClear: true,
    });
    DepositRequest();

    var $visaNavLink = $('.VisaModuleLink .nav-link');

    if ($visaNavLink.hasClass('active')) 
    {
        var triggerPoint = $visaNavLink.filter('.active').closest('.VisaModuleLink').data("id") || $visaNavLink.filter('.active').data("id");
        DasbhoardFlagWiseGetData(triggerPoint,null);
    } 

   
    $(document).on("click", ".VisaModuleLink", function () {
        var triggerPoint = $visaNavLink.filter('.active').closest('.VisaModuleLink').data("id") || $visaNavLink.filter('.active').data("id");
        DasbhoardFlagWiseGetData(triggerPoint,null);
    });
  
    
   

       $(document).on("change", "#hiddenInput",function()
       {
         
            var formattedDate = $(this).val();
            var triggerPoint = $visaNavLink.filter('.active').closest('.VisaModuleLink').data("id") || $visaNavLink.filter('.active').data("id");

            if ($visaNavLink.hasClass('active')) 
            { 
                DasbhoardFlagWiseGetData(triggerPoint, formattedDate);
            } 
       });
    function DasbhoardFlagWiseGetData(triggerPoint, formattedDate ) 
    {
        $.ajax({
            url: "{{ route('resort.visa.DasbhoardFlagWiseGetData') }}",
            type: "get",
            data: {
                _token: "{{ csrf_token() }}",
                triggerPoint: triggerPoint,
                "checkYearStatus":$("#checkYearStatus").val(),
                "formattedDate": formattedDate
            },
            success: function (response) {
                $("#myTabContent").html(response.html);

                // Parse dates from response or use the provided ISO dates
                var StartDate = moment(response.StartDate);
                var EndDate = moment(response.EndDate);

                console.log("Using dates:", StartDate.format('YYYY-MM-DD'), EndDate.format('YYYY-MM-DD'));

                // Check if hiddenInput exists, if not create it
                if ($("#hiddenInput").length === 0) {
                    $("body").append('<input type="text" id="hiddenInput" style="display:none;">');
                }

                $("#hiddenInput").daterangepicker({
                    autoApply: true,
                    startDate: StartDate,
                    endDate: EndDate,
                    opens: 'right',
                    parentEl: 'body', // Use body as parent element instead of #datapicker
                    alwaysShowCalendars: true,
                    linkedCalendars: false,
                    locale: {
                        format: "DD/MM/YYYY" // Ensure the format matches your date parsing logic
                    }
                });
            },
            error: function (xhr, status, error) {
                toastr.error("An error occurred while processing your request.", "Error", { positionClass: 'toast-bottom-right' });
            }
        });
    }
    $("#liabilityYear").select2({
        placeholder: "Select Year",
        allowClear: true,   
    });
      loadLiabilityBreakdown( $('#liabilityYear').val());
     $('#liabilityYear').on('change', function () {
        const dateRange = $(this).val();
        loadLiabilityBreakdown(dateRange);
    });
    $("#VisaXpactEditAmtForm").parsley();
    TransectionHistory();
    $("#uploadFile").on("change", function() {
        var fileName = $(this).val().split("\\").pop();
        $(".uploadFile-text").text(fileName || "Photos, Documents");
    });
    document.getElementById('uploadFile').addEventListener('change', function () {

        const maxSizeMB = 5; // max file size in MB
        const file = this.files[0];

        if (file) {
            const allowedExtensions = ['pdf', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp'];
            const fileExtension = file.name.split('.').pop().toLowerCase();

            if (!allowedExtensions.includes(fileExtension)) {
            toastr.error('Only PDF, Excel, and image files are allowed.', "Error", { positionClass: 'toast-bottom-right'});           
            this.value = '';
            } else if (file.size > maxSizeMB * 1024 * 1024) {
                    toastr.error(`File size must not exceed ${maxSizeMB} MB.`, "Error", { positionClass: 'toast-bottom-right'});
                this.value = '';
            }
        }
    });
     window.Parsley.addValidator('notequal', {
        requirementType: 'string',
        validateString: function(value, otherFieldSelector) {
            return value !== $(otherFieldSelector).val();
        },
        messages: {
            en: 'From Wallet and To Wallet cannot be the same.'
        }
    });
    $('#to-wallet').attr('data-parsley-notequal', '#from-wallet');

     window.Parsley.addValidator('notequal', {
        requirementType: 'string',
        validateString: function(value, otherFieldSelector) {
            return value !== $(otherFieldSelector).val();
        },
        messages: {
            en: 'From Wallet and To Wallet cannot be the same.'
        }
    });

    // Initialize Parsley on the form
    $('#TransferAmountform').parsley();

    $("#from-wallet").select2({
        placeholder: "Select From Wallet",
        allowClear: true,
    });
     $("#to-wallet").select2({
        placeholder: "Select To Wallet",
        allowClear: true,
    });
   $("#position").select2({
        placeholder: "Select To Wallet",
        allowClear: true,
    });

    $("#DepositeDate").datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true, // Close after selecting a date
        todayHighlight: true,
        clearBtn: true,
    });
    $("#expiryDate").datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true, // Close after selecting a date
        todayHighlight: true,
        clearBtn: true,
    });
    $("#expiryDate").on("change",function()
    {
        FetchExpiryOverviewIndex();
    });
    $("#position").on("change",function(){
        DepositRequest();
    });
     $("#DepositeDate").on("change",function(){
        DepositRequest();
    });

    $("#TransferAmountform").on("submit", function(e) {
        e.preventDefault(); // Prevent normal form submission
        var form = $(this);
        if (form.parsley().isValid()) 
        {
            let formData = new FormData(this); // Collect files + fields

            $.ajax({
                url: "{{ route('resort.visa.VisaWalletToWalletTransfer') }}",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                cache: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        TransectionHistory();
                    } else {
                        toastr.error(response.msg, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    console.log(errors);
                    var errs = '';
                    if (errors && errors.errors) {
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });
                        toastr.error(errs, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error("An unexpected error occurred", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }
            });
        } 
        else
        {
            form.parsley().validate();
        }
    });
    let selectedDates = [];

    function formatDate(date) {
        const options = { day: 'numeric', month: 'short', year: 'numeric' };
        return new Intl.DateTimeFormat('en-GB', options).format(date); // Format: "1 Jan 2025"
    }

    $('#date-range').datepicker({
        format: 'dd M yyyy',
        autoclose: false, // Do not close after selecting a date
        todayHighlight: true,
        clearBtn: true,
        multidate: true, // Allow multiple selections (2 for range)
    }).on('changeDate', function (e) {
        selectedDates = e.dates;

        if (selectedDates.length === 2) {
            let startDate = formatDate(selectedDates[0]);
            let endDate = formatDate(selectedDates[1]);
            $(this).val(startDate + " - " + endDate); // Display formatted date range
        }
    });

    // Close Datepicker when clicking outside
    $(document).on('click', function (event) {
        let datepickerContainer = $('.datepicker');
        let inputField = $('#date-range');

        if (!datepickerContainer.is(event.target) && datepickerContainer.has(event.target).length === 0 &&
            !inputField.is(event.target)) {
            $('#date-range').datepicker('hide');
        }
    });

    $(document).on('click', '.edit-visa-wallet', function (e) {
   
        let walletId = $(this).attr('data-id'); 
        
        let name = atob($(this).attr('data-name')); 
        let amt = atob($(this).attr('data-amt')); 

        $("#VisaXpactEditAmtForm")[0].reset(); 
        $("#Xpact_id").val(walletId);
        $("#Xpact_WalletName").val(name);
        $("#Xpact_WalletAmt").val(amt);
        $("#VisaXpactEditAmt-modal").modal('show');
     
    });
    $(document).on('submit', '#VisaXpactEditAmtForm', function (e) {
        e.preventDefault(); // Prevent normal form submission
        let form = $(this);
        if (form.parsley().isValid()) 
        {
            let formData = new FormData(this); // Collect files + fields

            $.ajax({
                url: "{{ route('resort.visa.VisaXpactEditAmt') }}",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                cache: false,
                success: function(response) 
                {
                    if (response.success == true) 
                    {
                        toastr.success(response.msg, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        $("#VisaXpactEditAmt-modal").modal('hide');

                        $("#VisaXpactAmounts").html(response.html);

                    } else {
                        toastr.error(response.msg, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    console.log(errors);
                    var errs = '';
                    if (errors && errors.errors) {
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });
                        toastr.error(errs, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error("An unexpected error occurred", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }
            });
        }
        else
        {
            
            form.parsley().validate();
        }
    });

    $(document).on("click",".OpenNatioanlityWiseEmployee",function(){
        let id=  $(this).attr('data-cat-id');
        $.ajax({
            url: "{{ route('resort.visa.NatioanlityWiseEmployeeList') }}",
            type: "GET",
            data: { id: id,"_token":"{{csrf_token()}}" },
            success: function(response) {
                if (response.success) 
                {
                    $("#NatioanlityWiseEmployee-modal").modal('show');
                    $("#NatioanlityWiseEmployee-table").empty();
                    $("#NatioanlityWiseEmployee-table").html(response.html);
                } 
                else 
                {
                    toastr.error(response.msg, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function(response) {
                var errors = response.responseJSON;
                console.log(errors);
                var errs = '';
                if (errors && errors.errors) {
                    $.each(errors.errors, function(key, error) {
                        errs += error + '<br>';
                    });
                    toastr.error(errs, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error("An unexpected error occurred", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            }
        });

    });
    FetchExpiryOverviewIndex();
    NatioanlityWiseEmployeeDepositAndCount();
    DoughtnutChart();
});



        // Generic function to equalize heights of two or more elements based on a reference element
        function equalizeHeights(referenceId, targetIds) {
            // Get the reference element
            const reference = document.getElementById(referenceId);

            // Check if the reference element exists
            if (reference) {
                // Get the height of the reference element
                const referenceHeight = reference.offsetHeight;

                // Loop through target element IDs and set their height
                targetIds.forEach(targetId => {
                    const target = document.getElementById(targetId);
                    if (target) {
                        target.style.height = referenceHeight + 'px';
                    }
                });
            }
        }

        // Adjust heights on page load and window resize
        function adjustHeights() {
            equalizeHeights('card-incidentResolTime', ['card-upMeetIncident']);
        }

        window.onload = adjustHeights; // Initial height adjustment
        window.onresize = adjustHeights; // Adjust heights on window resize


        // progress 
        const radius = 54; // Circle radius
        const circumference = 2 * Math.PI * radius; // The circumference of the circle
        // Select all progress containers
        const progressContainers = document.querySelectorAll('.progress-container');

        progressContainers.forEach(container => {
            const progressCircle = container.querySelector('.progress');
            // const progressText = container.querySelector('.progress-text');
            const progressValue = container.getAttribute('data-progress'); // Get the progress value from the container's data attribute
            const offset = circumference - (progressValue / 100 * circumference); // Calculate the offset

            // Set the initial stroke-dashoffset to the full circumference
            progressCircle.style.strokeDashoffset = circumference;

            // Use a small timeout to allow the browser to render the initial state before applying the offset (to trigger the animation)
            setTimeout(() => {
                // Apply the calculated offset to the progress bar with animation
                progressCircle.style.strokeDashoffset = offset;

                // Update the text inside the circle
                // progressText.textContent = `${progressValue}%`;
            }, 100); // A small delay to trigger the animation smoothly
        });

        document.addEventListener("DOMContentLoaded", function () {
            const progressBars = document.querySelectorAll('.progress.progress-custom .progress-bar'); // Ensure parent has progress-custom class

            progressBars.forEach((progressBar) => {
                const valueNow = parseInt(progressBar.getAttribute('aria-valuenow'), 10);
                const parentProgress = progressBar.closest('.progress'); // Get the parent .progress element

                // Add specific classes to the parent based on aria-valuenow
                if (valueNow === 100) {
                    parentProgress.classList.add('value-100');
                } else if (valueNow === 0) {
                    parentProgress.classList.add('value-0');
                }
            });

            // full-calendar   
            $(function () {

                var todayDate = moment().startOf('day');
                var YM = todayDate.format('YYYY-MM');
                var YESTERDAY = todayDate.clone().subtract(1, 'day').format('YYYY-MM-DD');
                var TODAY = todayDate.format('YYYY-MM-DD');
                var TOMORROW = todayDate.clone().add(1, 'day').format('YYYY-MM-DD');

                var cal = $('#calendar').fullCalendar({
                    header: {
                        left: 'prev ',
                        center: 'title',
                        right: 'next'
                    },
                    editable: true,
                    eventLimit: 0, // allow "more" link when too many events
                    navLinks: true,
                    dayRender: function (a) {
                        //console.log(a)
                    }
                });

            });


        });
        function NatioanlityWiseEmployeeDepositAndCount()
         {
                if($.fn.DataTable.isDataTable('#NatioanlityWiseEmployeeDepositAndCount'))
                {
                    $('#NatioanlityWiseEmployeeDepositAndCount').DataTable().destroy();
                }
                var productTable = $('#NatioanlityWiseEmployeeDepositAndCount').DataTable({
                    searching: false,
                    bLengthChange: false,
                    bInfo: true,
                    bAutoWidth: false,
                    scrollX: false,
                    iDisplayLength: 15,
                    processing: true,
                    serverSide: true,
                    ajax:{
                            url: "{{ route('resort.visa.NatioanlityWiseEmployeeDepositAndCount') }}",
                            type: 'GET',
                        },
                     columns: 
                     [
                            { data: 'Nationality', name: 'Nationality', className: 'text-nowrap' },
                            { data: 'DepositAmount', name: 'DepositAmount', className: 'text-nowrap' },
                            { data: 'Employeee', name: 'Employeee', className: 'text-nowrap' },
                            { data: 'Action', name: 'Action', className: 'text-nowrap' },
                    ]   
                    
                });
        }
        function FetchExpiryOverviewIndex()
        {
                if($.fn.DataTable.isDataTable('#ExpiryOverView-table'))
                {
                    $('#ExpiryOverView-table').DataTable().destroy();
                }
            var productTable = $('#ExpiryOverView-table').DataTable({
                    searching: false,
                    bLengthChange: false,
                    bInfo: true,
                    bAutoWidth: false,
                    scrollX: false,
                    iDisplayLength: 15,
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('resort.visa.OrverviewDashbordExpiry') }}",
                        type: 'GET',
                        data: function(d)
                        {
                        
                            d.date = $("#expiryDate").val();
                        }
                    },
                    columns: [
                        {
                            data: 'profile_view',
                            name: 'profile_view',
                            orderable: false,
                            searchable: false
                        }
                    ],
                    headerCallback: function(thead, data, start, end, display) {
                        // Hide the header row
                        $(thead).remove();
                    }
                });
        }
        function DepositRequest()
        {

            
            if ($.fn.DataTable.isDataTable('#depositWallet-table')) 
            {
                $('#depositWallet-table').DataTable().clear().destroy(); // Destroy existing instance
            }
            $('#depositWallet-table tbody').empty(); 
                var hiringsource = $('#depositWallet-table').DataTable({
                searching: false,
                bLengthChange: false,
                bFilter: true,
                bInfo: true,
                bAutoWidth: false,
                scrollX: true,
                iDisplayLength: 6,
                processing: true,
                serverSide: true,
                order: [[0, 'desc']],
                ajax: {
                    url: '{{ route("visa.deposit.DashboardDepositRequest") }}',
                    type: 'GET',
                    data: function (d) {
                        d.position = $('#position').val(); // Send the selected position
                        d.date = $('#DepositeDate').val(); // Send the selected position

                    }
                },
                columns: [
                    { data: 'ID', name: 'ID', className: 'text-nowrap' },
                    { data: 'Name', name: 'Name', className: 'text-nowrap' },
                    { data: 'Nationality', name: 'Nationality', className: 'text-nowrap' },
                    { data: 'DepositAmount', name: 'DepositAmount', className: 'text-nowrap' },
                    { data: 'Status', name: 'Status', className: 'text-nowrap' },
                ]    
            });
     
        }
        function TransectionHistory()
        {
            if ($.fn.DataTable.isDataTable('#transaction-history-table')) 
            {
                $('#transaction-history-table').DataTable().clear().destroy(); // Destroy existing instance
            }
            $('#transaction-history-table tbody').empty(); 
                var hiringsource = $('#transaction-history-table').DataTable({
                searching: false,
                bLengthChange: false,
                bFilter: true,
                bInfo: true,
                bAutoWidth: false,
                scrollX: true,
                iDisplayLength: 6,
                processing: true,
                serverSide: true,
                order: [[4, 'desc']],
                ajax: {
                    url: '{{ route("resort.visa.TransectionHistory") }}',
                    type: 'GET',
                },
                columns: [
                    { data: 'Date', name: 'Date', className: 'text-nowrap' },
                    { data: 'FromWallet', name: 'FromWallet', className: 'text-nowrap' },
                    { data: 'ToWallet', name: 'ToWallet', className: 'text-nowrap' },
                    { data: 'Amount', name: 'Amount', className: 'text-nowrap' },
                    {data:'created_at', visible:false,searchable:false},
                ]    
            });
        }
        function loadLiabilityBreakdown(dateRange) 
        {
            $.ajax({
                url: "{{ route('resort.visa.LiabilityBreakDown') }}",  // Replace with actual route
                type: "GET",
                data: {
                    NatioanlityWiseBreakDownRang: dateRange
                },
                success: function(response) {
                        if (response.success) 
                        {
                            let data = response.data;

                            // Example of calculating total liability dynamically
                            let total = 0;
                            let workpermit = 0;
                            let slot_fee = 0;
                            let insurance = 0;
                            let medical = 0;
                            let Visa = 0;
                            for (let i = 0; i < data.labels.length; i++) {
                                total += (data.workpermit[i] || 0) +(data.slot_fee[i] || 0) +(data.insurance[i] || 0) + (data.medical[i] || 0) +(data.Visa[i] || 0);
                                workpermit += (data.workpermit[i] || 0);
                                slot_fee += (data.slot_fee[i] || 0);
                                insurance += (data.insurance[i] || 0);
                                medical += (data.medical[i] || 0);
                                Visa += (data.Visa[i] || 0);
                            }

                            // Update chart
                            myStackedBarChart.data.labels = data.labels;
                            myStackedBarChart.data.datasets[0].data = data.workpermit;
                            myStackedBarChart.data.datasets[1].data = data.slot_fee;
                            myStackedBarChart.data.datasets[2].data = data.insurance;
                            myStackedBarChart.data.datasets[3].data = data.medical;
                            myStackedBarChart.data.datasets[4].data = data.Visa;
                            myStackedBarChart.update();

                            
                            
                            let row=  `<div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                    <div class="doughnut-label">
                                        <span class="bg-theme"></span>Workpermit - MVR ${workpermit.toLocaleString('en-IN')}
                                    </div>
                                </div>
                                <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                    <div class="doughnut-label">
                                        <span class="bg-themeSkyblueLight"></span>Slot Fee - MVR ${slot_fee.toLocaleString('en-IN')}
                                    </div>
                                </div>
                                <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                    <div class="doughnut-label">
                                        <span class="bg-themeYellow"></span>Insurance - MVR ${insurance.toLocaleString('en-IN')}
                                    </div>
                                </div>
                                <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                    <div class="doughnut-label">
                                        <span class="bg-themeSkyblue"></span>Work Permit Medical - MVR
                                        ${medical.toLocaleString('en-IN')}
                                    </div>
                                </div>
                                <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                    <div class="doughnut-label">
                                        <span class=" bg-themeGray"></span>Visa - MVR ${Visa.toLocaleString('en-IN')}
                                    </div>
                                </div>
                                <div class="col-xxl-12 col-xl-auto col-md-12 col-auto">
                                    <div class="doughnut-label fw-bold">
                                        Total: MVR ${total.toLocaleString('en-IN')}
                                    </div>
                                </div>`

                                $(".AppendMyStackBarChart").html(row);
                        
                    }
                },
                error: function() {
                    alert("Error fetching data!");
                }
            });
        }
        var ctx = document.getElementById('myDoughnutChart').getContext('2d');
        // Custom plugin only registered for this chart
        const doughnutLabelsInside = {
            id: 'doughnutLabelsInside',
            afterDraw: function (chart) {
                var ctx = chart.ctx; // Corrected
                chart.data.datasets.forEach(function (dataset, i) {
                    var meta = chart.getDatasetMeta(i);
                    if (!meta.hidden) {
                        meta.data.forEach(function (element, index) {
                            var dataValue = dataset.data[index];
                            var total = dataset.data.reduce(function (acc, val) {
                                return acc + val;
                            }, 0);
                            var percentage = ((dataValue / total) * 100).toFixed(0) + '%';

                            var position = element.tooltipPosition();

                            ctx.fillStyle = '#fff';
                            ctx.font = 'normal 14px Poppins';
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';

                            ctx.fillText(percentage, position.x, position.y);
                        });
                    }
                });
            }
        };

        function DoughtnutChart()
        {
             $.ajax({
                url: "{{ route('resort.visa.NatioanlityWiseEmployeeBreakDownChart') }}",
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        let labels = response.chartData.labels; // Example: ['India', 'Nepal', ...]
                        let data = response.chartData.data;     // Example: [30, 20, 50]
                        let depositPercent = response.chartData.deposit_percent; // Example: [30, 20, 50]
                        let paymentDates = response.chartData.payment_dates; // Example: optional array if returned

                        // Use the predefined colors instead of random ones
                        // Define both original colors and their lighter versions
                        const colorPalette = [
                            '#014653', '#176d7d',  // Dark teal and lighter teal
                            '#53CAFF', '#7ad6ff',  // Blue and lighter blue
                            '#EFB408', '#ffc93c',  // Yellow and lighter yellow
                            '#2EACB3', '#5dccd3',  // Turquoise and lighter turquoise
                            '#333333', '#666666',  // Dark gray and lighter gray
                            '#8DC9C9', '#b0e0e0',  // Light teal and lighter teal
                            '#FED049', '#fee78c'   // Gold and lighter gold
                        ];

                        // Map labels to colors, cycling through the colorPalette if needed
                        let colors = labels.map((_, index) => {
                            return colorPalette[index % colorPalette.length];
                        });

                       
                        let totalDeposit = depositPercent.reduce((a, b) => a + b, 0);
                        $('.doughnut-label.fw-bold').html("Total Deposit %: " + totalDeposit.toFixed(2) + '%');

                        if(window.myDoughnutChart) {
                            window.myDoughnutChart.destroy();
                        }

                        window.myDoughnutChart = new Chart(document.getElementById('myDoughnutChart').getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: labels.map((label, idx) => {
                                    // Optionally append payment date if provided
                                    if (paymentDates && paymentDates[idx]) {
                                        return `${label} (${paymentDates[idx]})`;
                                    }
                                    return label;
                                }),
                                datasets: [{
                                    data: data,
                                    backgroundColor: colors,
                                    borderColor: '#fff',
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                plugins: {
                                    legend: {
                                        position: 'right'
                                    }
                                }
                            },
                            plugins: [doughnutLabelsInside] // Your custom plugin for inside labels
                        });

                        // 🏷️ Update legend text in DOM (optional)
                        let legendHtml = '';
                        labels.forEach((label, i) => {
                            legendHtml += `<div class="doughnut-label">
                                <span style="background:${colors[i]}; width:12px; height:12px; display:inline-block;"></span>
                                ${label} - ${depositPercent[i].toFixed(2)}%
                            </div>`;
                        });
                        $('.myDoughnutChartLabel').before(legendHtml); // Insert before total
                    }
                }
            });
        }
      
        // Custom plugin for center text
        var myDoughnutChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Lorem Ipsum', 'Lorem Ipsum', 'Lorem Ipsum'],
                datasets: [{
                    data: [40, 25, 45],
                    backgroundColor: ['#8DC9C9', '#50b9bf', '#014653'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    doughnutLabelsInside: true, // Enable the custom plugin
                    legend: {
                        display: false
                    }
                },
                layout: {
                    padding: {
                        top: 10,
                        bottom: 10,
                        left: 0,
                        right: 0
                    }
                },
                // hoverOffset: 30
            },
            plugins: [doughnutLabelsInside] // Attach the plugin to this chart only
        });




        var ctz = document.getElementById('myStackedBarChart').getContext('2d');

            var myStackedBarChart = new Chart(ctz, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Workpermit',
                            data: [],
                            backgroundColor: '#014653',
                            borderColor: '#fff',
                            borderWidth: 2,
                            borderRadius: 10,
                        },
                        {
                            label: 'Slot Fee',
                            data: [],
                            backgroundColor: '#2EACB3',
                            borderColor: '#fff',
                            borderWidth: 2,
                            borderRadius: 10,
                        },
                        {
                            label: 'Insurance',
                            data: [],
                            backgroundColor: '#FED049',
                            borderColor: '#fff',
                            borderWidth: 2,
                            borderRadius: 10,
                        },
                        {
                            label: 'Work Permit Medical',
                            data: [],
                            backgroundColor: '#8DC9C9',
                            borderColor: '#fff',
                            borderWidth: 2,
                            borderRadius: 10,
                        },
                        {
                            label: 'Visa',
                            data: [],
                            backgroundColor: '#333333',
                            borderColor: '#fff',
                            borderWidth: 2,
                            borderRadius: 10,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: true }
                    },
                    hover: { mode: false },
                    layout: { padding: 0 },
                    scales: {
                        x: {
                            stacked: true,
                            grid: { display: false }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            grid: { display: false },
                            ticks: { stepSize: 20 }
                        }
                    }
                }
            });

           
// Function to load data via AJAX and update chart
    
    </script>
@endsection