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
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Talent Acquisition</span>
                            <h1>Shortlisted Applicants</h1>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex justify-content-end">
                            <a href="#" class="btn btn-theme">Request Manning</a>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="card">
                    <div class="card-header">
                        <div class="row g-md-3 g-2 align-items-center">
                            <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                                <div class="input-group">
                                    <input type="search" class="form-control " placeholder="Search" />
                                    <i class="fa-solid fa-search"></i>
                                </div>
                            </div>
                            <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                <select class="form-select">
                                    <option selected>All Department</option>
                                    <option value="1">abc</option>
                                    <option value="2">abc</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <table id="" class="table data-Table table-shortApp w-100">
                        <thead>
                            <tr>
                                <th>Applicants</th>
                                <th>Rank</th>
                                <th>Score</th>
                                <th>Nation</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Positions</th>
                                <th>Department</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                                        </div>
                                        <span class="userApplicants-btn">John Doe</span>
                                    </div>
                                </td>
                                <td>1</td>
                                <td>97%</td>
                                <td>Indian</td>
                                <td>johndoe@lorem.com</td>
                                <td>+ 91 541 245 4546</td>
                                <td>Assistant Front Desk Manager</td>
                                <td>Management</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                                        </div>
                                        <span class="userApplicants-btn">Christian Slater</span>
                                    </div>
                                </td>
                                <td>3</td>
                                <td>96%</td>
                                <td>Maldivian</td>
                                <td>dummy@lorem.com</td>
                                <td>+ 91 541 245 4546</td>
                                <td>Chef Concierge</td>
                                <td>Management</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                                        </div>
                                        <span class="userApplicants-btn">Brijesh Pandey</span>
                                    </div>
                                </td>
                                <td>4</td>
                                <td>90%</td>
                                <td>Maldivian</td>
                                <td>dummy@lorem.com</td>
                                <td>+ 91 541 245 4546</td>
                                <td>Night Desk Clerk (Former Night Auditor)</td>
                                <td>Front Office</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                                        </div>
                                        <span class="userApplicants-btn">Seerish Yadav</span>
                                    </div>
                                </td>
                                <td>2</td>
                                <td>80%</td>
                                <td>Maldivian</td>
                                <td>dummy@lorem.com</td>
                                <td>+ 91 541 245 4546</td>
                                <td>Guest Services Coordinator</td>
                                <td>Housekeeping</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                                        </div>
                                        <span class="userApplicants-btn">John Doe</span>
                                    </div>
                                </td>
                                <td>1</td>
                                <td>97%</td>
                                <td>Indian</td>
                                <td>johndoe@lorem.com</td>
                                <td>+ 91 541 245 4546</td>
                                <td>Assistant Front Desk Manager</td>
                                <td>Management</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                                        </div>
                                        <span class="userApplicants-btn">Christian Slater</span>
                                    </div>
                                </td>
                                <td>3</td>
                                <td>96%</td>
                                <td>Maldivian</td>
                                <td>dummy@lorem.com</td>
                                <td>+ 91 541 245 4546</td>
                                <td>Chef Concierge</td>
                                <td>Management</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                                        </div>
                                        <span class="userApplicants-btn">Brijesh Pandey</span>
                                    </div>
                                </td>
                                <td>4</td>
                                <td>90%</td>
                                <td>Maldivian</td>
                                <td>dummy@lorem.com</td>
                                <td>+ 91 541 245 4546</td>
                                <td>Night Desk Clerk (Former Night Auditor)</td>
                                <td>Front Office</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                                        </div>
                                        <span class="userApplicants-btn">Seerish Yadav</span>
                                    </div>
                                </td>
                                <td>2</td>
                                <td>80%</td>
                                <td>Maldivian</td>
                                <td>dummy@lorem.com</td>
                                <td>+ 91 541 245 4546</td>
                                <td>Guest Services Coordinator</td>
                                <td>Housekeeping</td>
                            </tr>


                        </tbody>

                    </table>


                </div>
            </div>

        </div>
    </div>

    @endsection
    @section('import-css')
    @endsection

@section('import-scripts')
<script>


</script>
@endsection
