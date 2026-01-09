@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

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
                            <span>People</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <!-- <div class="col-auto  ms-auto"><a class="btn btn-theme" href="#">Initiate Promotion</a></div> -->
                </div>
            </div>

            <div class="card card-salaryIncrementSum">
                <div class="row gx-xl-4 gy-xl-3 g-md-3 g-2 mb-4">
                    <div class="col-xxl col-lg-3 col-md-4 col-sm-6">
                        <div class="bg-themeGrayLight">
                            <h6>No. of employees impacted</h6><strong>{{$totalEmployees}}</strong>
                        </div>
                    </div>
                    <div class="col-xxl col-lg-3 col-md-4 col-sm-6">
                        <div class="bg-themeGrayLight">
                            <h6>Current Basic Salary</h6><strong>${{$currentBasicSalary}}</strong>
                        </div>
                    </div>
                    <div class="col-xxl col-lg-3 col-md-4 col-sm-6">
                        <div class="bg-themeGrayLight">
                            <h6>New Basic Salary</h6><strong>${{$newSalary}}</strong>
                        </div>
                    </div>
                    <div class="col-xxl col-lg-3 col-md-4 col-sm-6">
                        <div class="bg-themeGrayLight">
                            <h6>Monthly Difference</h6><strong>${{$monthlyDifference}}</strong>
                        </div>
                    </div>
                    <div class="col-xxl col-lg-3 col-md-4 col-sm-6">
                        <div class="bg-themeGrayLight">
                            <h6>Annual Difference</h6><strong>${{$yearlyDifference}}</strong>
                        </div>
                    </div>
                </div>
                <div class="card-title">
                    <h3>Employees Details</h3>
                </div>
                <div class="table-responsive mb-4">
                    <table id="" class="table  table-salaryIncreSummary  w-100 mb-0">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Current Salary</th>
                                <th>New Salary</th>
                                <th>Increment</th>
                                <th>Effective Date</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                         @foreach ($employees_data as $employee)
                              {{-- @dd($employee) --}}
                            <tr>
                                <td>{{$employee['employee_code']}}</td>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="{{$employee['employee_image']}}" alt="user">
                                        </div>
                                        <span class="userApplicants-btn">{{$employee['employee_name']}}</span>
                                    </div>
                                </td>
                                <td>{{$employee['employee_position']}}</td>
                                <td>{{$employee['employee_department']}}</td>
                                <td>${{$employee['previous_salary']}}</td>
                                <td>${{$employee['new_salary']}}</td>
                                <td>@if($employee['pay_increase_type'] == App\Models\PeopleSalaryIncrement::PAY_INCREASE_TYPE_FIXED) $@endif{{$employee['value']}} @if($employee['pay_increase_type'] == App\Models\PeopleSalaryIncrement::PAY_INCREASE_TYPE_PERCENTAGE) % @endif</td>
                                <td>{{$employee['effective_date']}}</td>
                                <td>{{$employee['remark']}}</td>
                            </tr>
                         @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-end">
                    <a href="#" class="btn btn-themeBlue btn-sm" id="submitButton">Submit</a>
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
          $('#submitButton').on('click', function (e) {
               e.preventDefault();
               $.ajax({
                    url: '{{ route("people.salary-increment.bulk-store") }}', // Replace with your route
                    type: 'POST',
                    data: {
                         _token: '{{ csrf_token() }}',
                         employee_data: @json($employees_data)
                    },
                    success: function (response) {
                         if(response.success) {
                               toastr.success(response.message, "Success", {
                                   positionClass: 'toast-bottom-right'
                                   });
                              window.location.href = response.redirect_url;
                         } 
                    },
                    error: function (xhr, status, error) {
                         alert('An error occurred: ' + error);
                    }
               });
          });
     });
</script>
@endsection

