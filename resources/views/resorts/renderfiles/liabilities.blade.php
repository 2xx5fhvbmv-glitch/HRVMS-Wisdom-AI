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
                    <strong>MVR {{number_format($TotalLiabilites,2)}}</strong>
                </div>

            </div>
        </div>
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="empDetail-block">
                <div>
                    <h6>Paid Liability</h6>
                    <strong>MVR {{number_format($TotalPaidLaibilites,2)}} </strong>
                </div>

            </div>
        </div>
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
            <div class="empDetail-block">
                <div>
                    <h6>Balance Liability</h6>
                    <strong>MVR {{number_format($TotalBalanceLiability,2)}}</strong>
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
                    <li>Total Liability: MVR {{number_format($TotalBudgetWorkPermitFees,2)}}</li>
                    <li>Paid Liability: MVR {{number_format($totalPermit,2)}}</li>
                    <li>Balance Liability: MVR {{number_format($TotalBalanceWorkPermitfees,2)}}</li>
                </ul>

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
                    <li>Total Liability: MVR {{number_format($TotalBudgetQuotaSlotDeposit,2)}}</li>
                    <li>Paid Liability: MVR {{number_format($totalQuota,2)}}</li>
                    <li>Balance Liability: MVR {{number_format($TotalBalanceQuotaSlotDeposit,2)}}</li>
                </ul>

            </div>
        </div>
        <div class="d-lg-flex align-items-center">
            <div>
                <h5 class ="findEmploees" data-flag="Visa" >Visa</h5>
                <p>{{$TotalVisaEmployee}} Employees
                   
                </p>
            </div>
            <div>
                <ul class="mb-0">
                    <li>Total Liability: MVR {{number_format($TotalBudgetVisaFees,2)}}</li>
                    <li>Paid Liability: MVR {{number_format($totalVisa,2)}}</li>
                    <li>Balance Liability: MVR {{number_format($TotalBalanceBudgetVisaFees,2)}}</li>
                </ul>

            </div>
        </div>
        <div class="d-lg-flex align-items-center">
            <div>
                <h5 class ="findEmploees" data-flag="Insurance" >Insurance</h5>
                <p>{{$totalInsuranceEmployee}} Employees
                     
                </p>
            </div>
            <div>
                <ul class="mb-0">
                    <li>Total Liability: MVR {{number_format($TotalBudgetMedicalInsuranceInternational,2)}}</li>
                    <li>Paid Liability: MVR {{number_format($totalInsurance,2)}}</li>
                    <li>Balance Liability: MVR {{number_format($TotalBalanceMedicalInsuranceInternational,2)}}</li>
                </ul>

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
                    <li>Total Liability: MVR {{number_format($TotalBudgetWorkPermitMedicalTestFee,2)}}</li>
                    <li>Paid Liability: MVR {{number_format($totalMedical,2)}}</li>
                    <li>Balance Liability: MVR {{number_format($TotalBalanceWorkPermitMedicalTestFee,2)}}</li>
                </ul>

            </div>
        </div>
    </div>