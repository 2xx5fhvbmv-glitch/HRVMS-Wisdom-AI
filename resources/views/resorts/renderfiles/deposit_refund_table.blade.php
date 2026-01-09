 @if($EmployeeResignation->isNotEmpty())
    @foreach($EmployeeResignation as $resignation)
        <div class="PayReq-Details-box">
            <div class="d-sm-flex justify-content-between ">
                <div class=" d-flex align-items-center">
                    <div class="img-circle"><img src="{{$resignation['profile_pic']}}" alt="user">
                    </div>
                    <div>
                        <h6>{{$resignation['employee_name']}}<span class="badge badge-themeNew">{{$resignation['Emp_id']}}</span> </h6>
                        <p>{{$resignation['department']}} , {{$resignation['position']}} </p>
                    </div>
                </div>
                <div class="d-flex align-items-center mt-sm-0 mt-3 justify-content-end">
                    <div class="form-check me-3">
                        <input class="form-check-input toggle-checkbox Paymentcheck" type="checkbox" data-id="{{$resignation['id']}}" id="Paymentcheck_{{$resignation['id']}}-yes-check"
                            value="Status1" >
                        <label class="form-check-label text-nowrap" for="check">Yes</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input toggle-checkbox PaymentcheckCancle" type="checkbox" data-id="{{$resignation['id']}}"  id="no-check-{{$resignation['id']}}"
                            value="Status1">
                        <label class="form-check-label text-nowrap" for="check">No</label>
                    </div>
                </div>
            </div>
            <div class="border-top d-none" id="Toggle-{{$resignation['id']}}">
                <div class="row gx-md-3 g-2">

                @if($VisaWallets->isNotEmpty())
                    @foreach($VisaWallets as $wallet)
                        <div class="col-lg-4 col-sm-6">
                            <div class="DepRefReq-checkbox d-flex align-items-center justify-content-between">
                                <div>
                                    <p>{{ $wallet->WalletName }}</p>
                                    <span>Current Balance: MVR {{ $wallet->Amt }}</span>
                                </div>
                                <div class="form-check form-check-inline p-0 me-0">
                                    <input 
                                        class="form-check-input" 
                                        type="radio" 
                                        name="wallet_option[{{ $wallet->id}}][{{$resignation['employee_id'] }}]" {{-- Grouping only by employee --}}
                                        id="wallet-radio-{{ $resignation['employee_id'] }}-{{ $loop->index }}" 
                                        value="{{ $wallet->Amt }}"
                                    >
                                    <label class="form-check-label" for="wallet-radio-{{ $resignation['employee_id'] }}-{{ $loop->index }}"></label>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
                
                </div>
            </div>

        </div>
    @endforeach

@else
    <div class="text-center">
        <p>No deposit refund requests found.</p>
    </div>
@endif