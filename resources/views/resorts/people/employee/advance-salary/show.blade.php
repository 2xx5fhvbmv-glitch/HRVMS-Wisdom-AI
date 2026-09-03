@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
    <style>
        #advance-salary-show-hero { padding-bottom: 40px; }
        @media (max-width: 575.98px) {
            #advance-salary-show-hero { padding-bottom: 0; }
        }
    </style>
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding" id="advance-salary-show-hero">
                <div class="row  g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>People</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto  ms-auto"><a class="btn btn-theme" href="#">Add New Employee</a></div> -->
                </div>
            </div>
            <div class="card card-salaryAdvanceReqPeopleEmpHr">
                <div class="row g-xxl-4 g-3 mb-md-4 mb-3">
                    <div class="col-md-6">
                        <div class="bg-themeGrayLight h-100">
                            <div class="card-title mb-0">
                                <h3>Employee Details</h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-lable mb-1">
                                    <tbody>
                                        <tr>
                                            <th>Name:</th>
                                            <td>
                                                <div class="tableUser-block">
                                                    <div class="img-circle"><img src="{{App\Helpers\Common::getResortUserPicture($advance_salary->employee->Admin_Parent_id)}}" alt="user">
                                                    </div>
                                                    <span class="userApplicants-btn">{{$advance_salary->employee->resortAdmin->full_name}}</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Employee ID:</th>
                                            <td>{{$advance_salary->employee->Emp_id}}</td>
                                        </tr>
                                        <tr>
                                            <th>Department:</th>
                                            <td>{{$advance_salary->employee->department->name ?? 'N/A'}}</td>
                                        </tr>
                                        <tr>
                                            <th>Position:</th>
                                            <td>{{$advance_salary->employee->position->position_title ?? 'N/A'}}</td>
                                        </tr>
                                        <tr>
                                            <th>Monthly Salary:</th>
                                            <td>{{ Common::formatCurrency($advance_salary->employee->basic_salary, $advance_salary->employee->basic_salary_currency ?? 'USD') }}</td>
                                        </tr>
                                        @if($advance_salary->status == 'Rejected')
                                            <tr>
                                                <th>Status:</th>
                                                <td><span class="badge badge-themeDanger">Rejected</span></td>
                                            </tr>
                                        @endif

                                        @if($advance_salary->status == 'Rejected' && $advance_salary->rejected_reason != null)
                                            <tr>
                                                <th>Reason:</th>
                                                <td>{{$advance_salary->rejected_reason}}</td>
                                            </tr>
                                        @endif
                                            
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-themeGrayLight h-100">
                            <div class="card-title mb-0">
                                <h3>Request Details</h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-lable mb-1">
                                    <tbody>
                                        <tr>
                                            <th>Request Type:</th>
                                            <td>{{$advance_salary->request_type}}</td>
                                        </tr>
                                        <tr>
                                            <th>Requested Amount:</th>
                                            <td>{{ Common::formatRequestCurrency($advance_salary->request_amount, $advance_salary->currency ?: 'USD') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Request Date:</th>
                                            <td>{{Carbon\Carbon::parse($advance_salary->request_date)->format('d M Y')}}</td>
                                        </tr>
                                        <tr>
                                            <th>Purpose:</th>
                                            <td>{{$advance_salary->pourpose}}</td>
                                        </tr>
                                        @foreach($advance_salary->guarantors as $guarantor)
                                             @php
                                                  $color = 'themeWarning';
                                                  if($guarantor->status == 'Pending'){
                                                       $color = 'themeWarning';
                                                  }elseif($guarantor->status == 'Rejected'){
                                                       $color = 'themeDanger';
                                                  }
                                                  elseif($guarantor->status == 'Approved'){
                                                       $color = 'themeSuccess';
                                                  }
                                                  elseif($guarantor->status == 'Hold'){
                                                       $color = 'themeInfo';
                                                  }
                                             @endphp
                                             <tr>

                                                  <th>Guarantor Name:</th>
                                                  <td>
                                                       <div class="tableUser-block">
                                                            <div class="img-circle"><img src="{{App\Helpers\Common::getResortUserPicture($guarantor->employee->Admin_Parent_id)}}"
                                                                      alt="user">
                                                            </div>
                                                            <span class="userApplicants-btn">{{$guarantor->employee->resortAdmin->full_name}}</span>
                                                       </div>
                                                  </td>
                                             </tr>
                                             <tr>
                                                  <th>Position:</th>
                                                  <td>{{$guarantor->employee->position->position_title ?? 'N/A'}}</td>
                                             </tr>
                                             <tr>
                                                  <th>Guarantor Status:</th>
                                                  <td><span class="badge badge-{{$color}}">{{$guarantor->status}}</span></td>
                                             </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                              @php
                                  $attachmentFiles = $advance_salary->PayrollAdvanceAttachment;
                              @endphp

                    <div class="col-12 @if($attachmentFiles->count() <= 0) d-none @endif" >
                        <div class="bg-themeGrayLight h-100">
                            <div class="card-title mb-md-3">
                                <h3>Attachments</h3>
                            </div>

                            <div class="row g-1">
                              
                              @if($attachmentFiles->count() > 0)
                                   @foreach($attachmentFiles as $attachmentFile)
                                        <div class="col-auto">
                                              <div class="attachPdf-block">
                                                    @php
                                                          $fileExtension = pathinfo($attachmentFile->attachments, PATHINFO_EXTENSION);
                                                          $iconPath = asset('assets/icons/default-icon.svg'); // Default icon path
                                                          switch (strtolower($fileExtension)) {
                                                                  case 'pdf':
                                                                         $iconPath = asset('assets/icons/file-pdf.svg');
                                                                         break;
                                                                  case 'doc':
                                                                  case 'docx':
                                                                         $iconPath = asset('assets/icons/file-word.svg');
                                                                         break;
                                                                  case 'xls':
                                                                  case 'xlsx':
                                                                         $iconPath = asset('assets/icons/file-excel.svg');
                                                                         break;
                                                                  case 'jpg':
                                                                  case 'jpeg':
                                                                  case 'png':
                                                                         $iconPath = asset('assets/icons/file-image.svg');
                                                                       break;
                                                                 // Add more cases for other file types as needed
                                                          }
                                                    @endphp
                                                    <a href="{{ url($attechment_path.'/' . $attachmentFile->attachments) }}" target="_blank">
                                                          <img src="{{ $iconPath }}" alt="icon">
                                                          {{ $attachmentFile->attachments }}
                                                    </a>
                                              </div>
                                        </div>
                                   @endforeach
                              @endif
                            </div>
                        </div>
                    </div>


                    @if($recovery_schedule->count() > 0 && $advance_salary->hr_status == 'Approved')
                         <div class="col-12">
                              <div class="bg-themeGrayLight h-100">
                                        <div class="card-title mb-0">
                                             <h3>Repayment Schedule</h3>
                                        </div>
                                        <div class=" my-lg-4  my-3 ">
                                             <label for="select_total" class="form-label mb-0">TOTAL MONTHS TO DIVIDED:
                                                  {{$recovery_schedule->count()}}</label>
                                        </div>
                                   <div class="row g-md-4 g-3" >
                                        <div class="col-xl-9 col-lg-8">
                                             <div class="table-responsive">
                                                  <table class="table table-lable table-repaySchedPeopleEmp mb-1">
                                                  <thead>
                                                       <tr>
                                                            <th>Payroll Month</th>
                                                            <th>Amount</th>
                                                            <th>Interest (%)</th>
                                                            <th>Remaining Balance</th>
                                                       </tr>
                                                  </thead>
                                                  <tbody>

                                                       @foreach($recovery_schedule as $key => $value)
                                                            @php
                                                                 $month = Carbon\Carbon::createFromFormat('Y-m-d', $value->repayment_date)->format('F Y');
                                                            @endphp
                                                            <tr>
                                                                 <td>
                                                                      @if($value->status === 'Pending')
                                                                           {{-- Still-unpaid installment — HR/Finance can move it to a
                                                                                different upcoming month (e.g. April instead of March).
                                                                                Server blocks picking a month another row already uses. --}}
                                                                           @php
                                                                                // A real <select> falls back to its FIRST option when
                                                                                // $month (the DB's repayment_date) isn't among
                                                                                // $availableMonths (e.g. an overdue installment whose
                                                                                // month has already passed) — no <option> ends up
                                                                                // `selected`, so the browser just shows option #1.
                                                                                // Mirror that exact fallback here instead of trusting
                                                                                // $month, or the .dd's initial label would show a value
                                                                                // the real select was never actually going to submit.
                                                                                $monthInList = in_array($month, $availableMonths, true);
                                                                                $monthDisplay = $monthInList ? $month : ($availableMonths[0] ?? $month);
                                                                           @endphp
                                                                           <select class="form-select dd-native-select month-select" id="month-select-{{ $value->id }}" data-schedule-id="{{ $value->id }}" data-amount="{{ $value->amount }}" data-original="{{ $month }}" aria-label="Payroll month">
                                                                                @foreach($availableMonths as $m)
                                                                                     <option value="{{ $m }}" @if($m == $month) selected @endif>{{ $m }}</option>
                                                                                @endforeach
                                                                           </select>
                                                                           <div class="dd" data-target="#month-select-{{ $value->id }}">
                                                                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                                                     <span class="dd-lbl">{{ $monthDisplay }}</span>
                                                                                     <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                                                                </button>
                                                                                <div class="dd-panel" role="listbox" aria-label="Payroll month">
                                                                                     <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a month…"></div>
                                                                                     <div class="dd-scroll">
                                                                                          @foreach($availableMonths as $m)
                                                                                          <div class="dd-item{{ ($monthInList ? $m == $month : $loop->first) ? ' active' : '' }}" role="option" data-value="{{ $m }}"><span class="dd-nm">{{ $m }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                                                          @endforeach
                                                                                     </div>
                                                                                </div>
                                                                           </div>
                                                                      @else
                                                                           <select class="form-select dd-native-select" id="month-fixed-{{ $value->id }}" aria-label="Payroll month" disabled>
                                                                                <option selected value="{{$month}}">{{ $month }}</option>
                                                                           </select>
                                                                           <div class="dd" data-target="#month-fixed-{{ $value->id }}">
                                                                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false" disabled>
                                                                                     <span class="dd-lbl">{{ $month }}</span>
                                                                                     <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                                                                </button>
                                                                                <div class="dd-panel" role="listbox" aria-label="Payroll month">
                                                                                     <div class="dd-scroll">
                                                                                          <div class="dd-item active" role="option" data-value="{{ $month }}"><span class="dd-nm">{{ $month }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                                                     </div>
                                                                                </div>
                                                                           </div>
                                                                      @endif
                                                                 </td>
                                                                 <td>{{ Common::formatRequestCurrency($value->amount, $advance_salary->currency ?: 'USD') }}</td>
                                                                 <td>
                                                                      <div class="position-relative">
                                                                           <input type="text" class="form-control" 
                                                                                placeholder="Enter Interest Value" readonly value="{{ $value->interest}}">
                                                                           <i class="fa-solid fa-percent"></i>
                                                                      </div>
                                                                 </td>
                                                                 <td>{{ Common::formatRequestCurrency($value->remaining_balance, $advance_salary->currency ?: 'USD') }}</td>
                                                            </tr>
                                                       @endforeach

                                                  </tbody>
                                                  </table>
                                             </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-4">
                                             <div class="row g-md-3 g-2">
                                                  <div class="col-lg-12 col-sm-6">
                                                       <div class="bg-white">
                                                            <h6>Total Requested Amount</h6>
                                                            <strong>{{ Common::formatRequestCurrency($actual_amount, $advance_salary->currency ?: 'USD') }}</strong>
                                                       </div>
                                                  </div>
                                                  <div class="col-lg-12 col-sm-6">
                                                       <div class="bg-white">
                                                            <h6>Interest</h6>
                                                            <strong >{{ Common::formatRequestCurrency($total_interest, $advance_salary->currency ?: 'USD') }}</strong>
                                                       </div>
                                                  </div>
                                                  <div class="col-lg-12 col-sm-6">
                                                       <div class="bg-white">
                                                            <h6>Total Repayment Amount</h6>
                                                            <strong >{{ Common::formatRequestCurrency($total_recovery, $advance_salary->currency ?: 'USD') }}</strong>
                                                       </div>
                                                  </div>
                                             </div>
                                        </div>
                                   </div>
                              </div>
                         </div>
                    @elseif($advance_salary->hr_status == 'Approved')
                         <div class="col-12">
                              <div class="bg-themeGrayLight h-100">
                                        <div class="card-title mb-0">
                                             <h3>Repayment Schedule</h3>
                                        </div>
                                        <form action="javascript:void(0);" class="monthlyRepaymentForm" id="monthlyRepaymentForm">
                                             <input type="hidden" name="advance_salary_id" value="{{$advance_salary->id}}">
                                             <div class="row g-md-3 g-2 align-items-end mt-lg-3 mb-lg-4 mt-2 mb-3">
                                                  <div class="col-xl-3 col-lg-5 col-md-6 col-sm-8 col">
                                                       <label for="select_total" class="form-label">SELECT TOTAL MONTHS TO DIVIDE</label>
                                                       <select class="form-select dd-native-select" id="select_total"
                                                            aria-label="Default select example">
                                                            <option selected disabled>Select Month</option>
                                                            @for ($i = 1; $i <= 60; $i++)
                                                                 <option value="{{$i}}">{{$i}}</option>
                                                            @endfor
                                                       </select>
                                                       <div class="dd" data-target="#select_total">
                                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                                 <span class="dd-lbl">Select Month</span>
                                                                 <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                                            </button>
                                                            <div class="dd-panel" role="listbox" aria-label="Total Months">
                                                                 <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a month…"></div>
                                                                 <div class="dd-scroll">
                                                                      <div class="dd-item active" role="option" data-value="" aria-disabled="true"><span class="dd-nm">Select Month</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                                      @for ($i = 1; $i <= 60; $i++)
                                                                      <div class="dd-item" role="option" data-value="{{ $i }}"><span class="dd-nm">{{ $i }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                                      @endfor
                                                                 </div>
                                                            </div>
                                                       </div>
                                                  </div>
                                                  <div class="col-auto"><button type="submit" class="btn btn-themeBlue">Submit</button></div>
                                             </div>
                                        </form>
                                   <div class="row g-md-4 g-3 d-none" id="repaySchedule">
                                        <div class="col-xl-9 col-lg-8">
                                             <div class="table-responsive">
                                                  <table class="table table-lable table-repaySchedPeopleEmp mb-1">
                                                  <thead>
                                                       <tr>
                                                            <th>Payroll Month</th>
                                                            <th>Amount</th>
                                                            <th>Interest (%)</th>
                                                            <th>Remaining Balance</th>
                                                       </tr>
                                                  </thead>
                                                  <tbody>
                                                  </tbody>
                                                  </table>
                                             </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-4">
                                             <div class="row g-md-3 g-2">
                                                  <div class="col-lg-12 col-sm-6">
                                                  <div class="bg-white">
                                                       <h6>Total Requested Amount</h6>
                                                       <strong>{{ Common::formatRequestCurrency($advance_salary->request_amount, $advance_salary->currency ?: 'USD') }}</strong>
                                                  </div>
                                                  </div>
                                                  <div class="col-lg-12 col-sm-6">
                                                  <div class="bg-white">
                                                       <h6>Interest</h6>
                                                       <strong id="interest_amount">{{ ($advance_salary->currency ?: 'USD') === 'MVR' ? 'MVR' : '$' }} 0</strong>
                                                  </div>
                                                  </div>
                                                  <div class="col-lg-12 col-sm-6">
                                                  <div class="bg-white">
                                                       <h6>Total Repayment Amount</h6>
                                                       <strong id="total_amount">{{ Common::formatRequestCurrency($advance_salary->request_amount, $advance_salary->currency ?: 'USD') }}</strong>
                                                  </div>
                                                  </div>
                                             </div>
                                        </div>
                                   </div>
                              </div>
                         </div>
                    @endif 
                </div>
                    <div class="@if(Common::checkRouteWisePermission('people.advance-salary.index',config('settings.resort_permissions.edit')) == false) d-none @endif">
                         @if($advance_salary->status == 'Pending' && $advance_salary->hr_status == 'Pending' && $isHR == true)
                              <div class="card-footer">
                                   <div class="row  g-2 justify-content-end">
                                        <div class="col-auto"><a href="javascript:void(0);" class="btn  btn-themeBlue btn-sm actionBtn" data-status='Approved' data-action_by='hr'>Approve</a></div>
                                        <div class="col-auto"><a href="javascript:void(0);" class="btn  btn-themeDanger btn-sm actionBtn" data-status='Rejected' data-action_by='hr'>Reject</a></div>
                                   </div>
                              </div>
                         @elseif($advance_salary->status == 'In-Progress' &&  $advance_salary->hr_status == 'Approved' && $isHR == true && $recovery_schedule->count() <= 0 && $isHR == true && $advance_salary->finance_status == 'Pending')
                              <div class="card-footer">
                                   <div class="row  g-2 justify-content-end">
                                        <div class="col-auto "><a href="javascript:void(0);" class="btn  btn-themeSkyblue btn-sm saveReSchedule"> Repayment Schedule Submit</a></div>
                                   </div>
                              </div>
                         @elseif($advance_salary->status == 'In-Progress' && $advance_salary->hr_status == 'Approved' && $isFinance == true && $recovery_schedule->count() > 0 && $advance_salary->finance_status == 'Pending')
                              <div class="card-footer">
                                   <div class="row  g-2 justify-content-end">
                                        <div class="col-auto"><a href="javascript:void(0);" class="btn  btn-themeBlue btn-sm actionBtn" data-status='Approved' data-action_by='finance'>Approve</a></div>
                                        <div class="col-auto"><a href="javascript:void(0);" class="btn  btn-themeDanger btn-sm actionBtn" data-status='Rejected' data-action_by='finance'>Reject</a></div>
                                   </div>
                              </div>
                         @elseif($advance_salary->status == 'In-Progress' && $advance_salary->hr_status == 'Approved' && $isGM == true && $recovery_schedule->count() > 0 && $advance_salary->finance_status == 'Approved' && $advance_salary->gm_status == 'Pending')
                              <div class="card-footer">
                                   <div class="row  g-2 justify-content-end">
                                        <div class="col-auto"><a href="javascript:void(0);" class="btn  btn-themeBlue btn-sm actionBtn" data-status='Approved' data-action_by='gm'>Approve</a></div>
                                        <div class="col-auto"><a href="javascript:void(0);" class="btn  btn-themeDanger btn-sm actionBtn" data-status='Rejected' data-action_by='gm'>Reject</a></div>
                                   </div>
                              </div>
                         @endif
                    </div>
            </div>
        </div>
    </div>

    
<div class="modal fade" id="rejected-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-rejected">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Reason</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{route('people.salary-increment.request-change')}}" id="rejectedForm">
                     @csrf
                     <input type="hidden" name="advance_salary_id" value="{{$advance_salary->id}}">
                    <textarea id="reject_reason" class="form-control" name="reject_reason" rows="3" placeholder="Write your comment (required)" required></textarea>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-themeDanger" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" id="submitBtn" class="btn btn-themeBlue ">Submit</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@include('resorts._dropdown_styles')
@endsection

@section('import-scripts')
<script>
$(document).on('change', '.month-select', function () {
     let $select = $(this);
     let newMonth = $select.val();
     let previousMonth = $select.data('original');
     if (newMonth === previousMonth) {
          return;
     }

     $select.prop('disabled', true);
     $.ajax({
          url: "{{ route('people.advance-salary-repayment-tracker.update') }}",
          type: 'POST',
          data: {
               _token: '{{ csrf_token() }}',
               schedule_id: $select.data('schedule-id'),
               repayment_date: newMonth,
               amount: $select.data('amount'),
          },
          success: function (response) {
               $select.prop('disabled', false);
               if (response.success) {
                    $select.data('original', newMonth);
                    toastr.success(response.message, "Success", {
                         positionClass: 'toast-bottom-right'
                    });
               } else {
                    $select.val(previousMonth);
                    window.wisdomDD.sync('#' + $select.attr('id'));
                    toastr.error(response.message, "Error", {
                         positionClass: 'toast-bottom-right'
                    });
               }
          },
          error: function () {
               $select.prop('disabled', false);
               $select.val(previousMonth);
               window.wisdomDD.sync('#' + $select.attr('id'));
               toastr.error("Something went wrong while updating the repayment month.", "Error", {
                    positionClass: 'toast-bottom-right'
               });
          }
     });
});

$(document).ready(function () {
     $('#monthlyRepaymentForm').on('submit', function (e) {
          e.preventDefault();
          let selectedMonths = $('#select_total').val();
          let advanceSalaryId = $('input[name="advance_salary_id"]').val();
          if (!selectedMonths) {
               alert('Please select a month.');
               return;
          }

          $.ajax({
               url: "{{ route('people.advance-salary.payment-reschedule') }}",
               type: 'POST',
               data: {
                    _token: '{{ csrf_token() }}',
                    months: selectedMonths,
                    advance_salary_id: advanceSalaryId
               },
               success: function (response) {
                    if(response.success) {
                         $('#repaySchedule').removeClass('d-none');
                         $('.table-repaySchedPeopleEmp tbody').html(response.html);
                    }else{
                         toastr.error(response.message, "Error", {
                              positionClass: 'toast-bottom-right'
                         });
                         return;
                    }

               },
               error: function (xhr) {
                    toastr.error(response.message, "Error", {
                         positionClass: 'toast-bottom-right'
                    });
                    return;
               }
          });
     });
});

$(document).on('keyup', '.interest-input', function () {
     let allData = [];

     $('.interest-input').each(function () {
          let month = $(this).data('month');
          let interest = $(this).val() || 0; // Default to 0 if no interest is entered
          let installment = $(this).data('installment');
          let remainingBalance = $(this).data('remaining');
          let payroll_advance_id = $(this).data('payroll_advance_id');

          allData.push({
               month: month,
               interest: interest,
               installment: installment,
               remaining_balance: remainingBalance,
               payrollAdvanceId: payroll_advance_id,
          });
     });

     $.ajax({
          url: "{{ route('people.advance-salary.payment-interest-calculate') }}",
          type: 'POST',
          data: {
               _token: '{{ csrf_token() }}',
               data: allData
          },
          success: function (response) {
               $('.table-repaySchedPeopleEmp tbody').html(response.html);
               $('#interest_amount').text(currencySymbol + ' ' + Number(response.total_interest).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
               $('#total_amount').text(currencySymbol + ' ' + Number(response.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

          },
          error: function (xhr) {
               console.error('Error:', xhr.responseText);
          }
     });
});

$(document).on('click', '.saveReSchedule', function (e) {
     e.preventDefault();
     let allData = []; 
     $('.interest-input').each(function () {
          let month = $(this).data('month');
          let interest = $(this).val() || 0;
          let installment = $(this).data('installment');
          let remainingBalance = $(this).data('remaining');
          let payroll_advance_id = $(this).data('payroll_advance_id');

          allData.push({
               month: month,
               interest: interest,
               installment: installment,
               remaining_balance: remainingBalance,
               payrollAdvanceId: payroll_advance_id,
          });
     });

     if (allData.length === 0) {
          toastr.error("No data available to save.", "Error", {
               positionClass: 'toast-bottom-right'
          });
          return;
     }

     $.ajax({
          url: "{{ route('people.advance-salary.payment-reschedule-store') }}",
          type: 'POST',
          data: {
               _token: '{{ csrf_token() }}',
               data: allData  
          },   
          success: function (response) {
               if (response.success) {
                    toastr.success(response.message, "Success", {
                          positionClass: 'toast-bottom-right'
                    });
                    window.location.href = response.redirect_url;
               }
          },
          error: function (xhr) {
               toastr.error("Something went wrong. Please try again.", "Error", {
                    positionClass: 'toast-bottom-right'
               });
          }
     });
});



$(document).on('click', '.actionBtn', function (e) {
     e.preventDefault();
     let status = $(this).data('status');
     let action_by = $(this).data('action_by');
     let advance_salary_id = "{{$advance_salary->id}}";
     let url = "{{ route('people.advance-salary.update-status') }}";

     if (status === 'Rejected') {
          $('#rejected-modal').modal('show');
          $('#submitBtn').off('click').on('click', function () {
               let reject_reason = $('#reject_reason').val();
               if (!reject_reason.trim()) {
                    toastr.error("Please provide a reason for rejection.", "Error", {
                         positionClass: 'toast-bottom-right'
                    });
                    return;
               }
               submitStatusChange(status, action_by, advance_salary_id, reject_reason, url);
          });
     } else {
          submitStatusChange(status, action_by, advance_salary_id, null, url);
     }
});

function submitStatusChange(status, action_by, advance_salary_id, reject_reason, url) {
     let data = {
          _token: '{{ csrf_token() }}',
          status: status,
          action_by: action_by,
          advance_salary_id: advance_salary_id,
          reject_reason: reject_reason
     };

     $.ajax({
          url: url,
          type: 'POST',
          data: data,
          success: function (response) {
               if (response.success) {
                    toastr.success(response.message, "Success", {
                         positionClass: 'toast-bottom-right'
                    });
                    window.location.reload();
               } else {
                    toastr.error(response.message, "Error", {
                         positionClass: 'toast-bottom-right'
                    });
               }
          },
          error: function (xhr) {
               toastr.error("Something went wrong. Please try again.", "Error", {
                    positionClass: 'toast-bottom-right'
               });
          }
     });
}

</script>
@include('resorts._dropdown_script')
@endsection

