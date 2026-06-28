<div class="row g-lg-4 g-3 mb-4">
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="empDetail-block">
                <div>
                    <h6>Total Xpat Employees</h6>
                    <strong>{{$TotalExpactEmployee}}
                        <a href="javascript:void(0)" class="text-decoration-none text-dark findEmploees" data-flag="All">
                            <img src="{{URL::asset('resorts_assets/images/arrow-right-circle.svg')}}" alt="" class="img-fluid">
                        </a>
                        </strong>
                </div>

            </div>
        </div>
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="empDetail-block">
                <div>
                    <h6>Total Liability</h6>
                    <strong>{!! Common::formatCurrency($TotalLiabilites, 'USD') !!}</strong>
                </div>

            </div>
        </div>
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="empDetail-block">
                <div>
                    <h6>Paid Liability</h6>
                    <strong>{!! Common::formatCurrency($TotalPaidLaibilites, 'USD') !!} </strong>
                </div>

            </div>
        </div>
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="empDetail-block">
                <div>
                    <h6>Balance Liability</h6>
                    <strong>{!! Common::formatCurrency($TotalBalanceLiability, 'USD') !!}</strong>
                </div>

            </div>
        </div>

    </div>
    <div class="total-breakdown-visa">
        <div class="d-lg-flex align-items-center">
            <div>
                <h5 class=" findEmploees" data-flag="WorkPermit">Work Permit Fee</h5>
                <p>{{$totalPermitEmployee}} Employees
             
                  

                </p>
            </div>
            <div>
                <ul class="mb-0">
                    <li>Total Liability: {!! Common::formatCurrency($TotalBudgetWorkPermitFees, 'USD') !!}</li>
                    <li>Paid Liability: {!! Common::formatCurrency($totalPermit, 'USD') !!}</li>
                    <li>Balance Liability: {!! Common::formatCurrency($TotalBalanceWorkPermitfees, 'USD') !!}</li>
                </ul>
                <a href="javascript:void(0)" class="a-link liabilityBreakdown" data-flag="WorkPermit"><i class="fa-solid fa-list-ul me-1"></i>View breakdown</a>
            </div>
        </div>
        <div class="d-lg-flex align-items-center">
            <div>
                <h5 class ="findEmploees" data-flag="QuotaSlot" >Slot Payment</h5>
                <p>{{$totalQuotaEmployee}} Employees
                    

                </p>
            </div>
            <div>
                <ul class="mb-0">
                    <li>Total Liability: {!! Common::formatCurrency($TotalBudgetQuotaSlotDeposit, 'USD') !!}</li>
                    <li>Paid Liability: {!! Common::formatCurrency($totalQuota, 'USD') !!}</li>
                    <li>Balance Liability: {!! Common::formatCurrency($TotalBalanceQuotaSlotDeposit, 'USD') !!}</li>
                </ul>
                <a href="javascript:void(0)" class="a-link liabilityBreakdown" data-flag="QuotaSlot"><i class="fa-solid fa-list-ul me-1"></i>View breakdown</a>
            </div>
        </div>
        {{-- <div class="d-lg-flex align-items-center">
            <div>
                <h5 class ="findEmploees" data-flag="Visa" >Visa</h5>
                <p>{{$TotalVisaEmployee}} Employees

                </p>
            </div>
            <div>
                <ul class="mb-0">
                    <li>Total Liability: {!! Common::formatCurrency($TotalBudgetVisaFees, 'USD') !!}</li>
                    <li>Paid Liability: {!! Common::formatCurrency($totalVisa, 'USD') !!}</li>
                    <li>Balance Liability: {!! Common::formatCurrency($TotalBalanceBudgetVisaFees, 'USD') !!}</li>
                </ul>

            </div>
        </div> --}}
        <div class="d-lg-flex align-items-center">
            <div>
                <h5 class ="findEmploees" data-flag="Insurance" >Insurance</h5>
                <p>{{$totalInsuranceEmployee}} Employees
                     
                </p>
            </div>
            <div>
                <ul class="mb-0">
                    <li>Total Liability: {!! Common::formatCurrency($TotalBudgetMedicalInsuranceInternational, 'USD') !!}</li>
                    <li>Paid Liability: {!! Common::formatCurrency($totalInsurance, 'USD') !!}</li>
                    <li>Balance Liability: {!! Common::formatCurrency($TotalBalanceMedicalInsuranceInternational, 'USD') !!}</li>
                </ul>
                <a href="javascript:void(0)" class="a-link liabilityBreakdown" data-flag="Insurance"><i class="fa-solid fa-list-ul me-1"></i>View breakdown</a>
            </div>
        </div>
        <div class="d-lg-flex align-items-center">
            <div>
                <h5 class ="findEmploees" data-flag="Medical" >Medical</h5>
                <p>{{$totalMedicalEmployee}} Employees
                  
                </p>
            </div>
            <div>
                <ul class="mb-0">
                    <li>Total Liability: {!! Common::formatCurrency($TotalBudgetWorkPermitMedicalTestFee, 'USD') !!}</li>
                    <li>Paid Liability: {!! Common::formatCurrency($totalMedical, 'USD') !!}</li>
                    <li>Balance Liability: {!! Common::formatCurrency($TotalBalanceWorkPermitMedicalTestFee, 'USD') !!}</li>
                </ul>
                <a href="javascript:void(0)" class="a-link liabilityBreakdown" data-flag="Medical"><i class="fa-solid fa-list-ul me-1"></i>View breakdown</a>
            </div>
        </div>
    </div>