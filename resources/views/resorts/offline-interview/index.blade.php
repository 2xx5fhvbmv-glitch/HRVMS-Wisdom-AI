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
        <div class="page-hedding page-appHedding">
            <div class="row justify-content-between g-md-2 g-1">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Talent Acquisition</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('offline-interview.create') }}" class=" btn btn-sm btn-theme">Add Applicants</a>
                </div>
            </div>
        </div>


        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                        <div class="input-group">
                            <input type="search" class="form-control " placeholder="Search" />
                            <i class="fa-solid fa-search"></i>
                        </div>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="#" class="btn btn-grid"><img src="assets/images/grid.svg" alt="icon"></a>
                        <a href="#" class="btn btn-list active"><img src="assets/images/list.svg" alt="icon"></a>
                    </div>
                </div>
            </div>
            <div class="list-main ">
                <div class="table-responsive">
                    <table class="table table-collapseNew table-applicants  ">
                        <thead>
                            <tr>
                                <th>Applicants<i class="fa-solid fa-caret-down"></i></th>
                                <th>Nationality<i class="fa-solid fa-caret-up"></i></th>
                                <th>Passport No.</th>
                                <th>Experience<i class="fa-solid fa-caret-up"></i></th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Applied Date<i class="fa-solid fa-caret-down"></i></th>
                                <th>Stage<i class="fa-solid fa-caret-down"></i></th>
                                <th></th>
                                <th>Action</th>
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
                                <td>Indian</td>
                                <td>255646121456</td>
                                <td>4 year</td>
                                <td>johndoe@lorem.com</td>
                                <td>+ 91 541 245 4546</td>
                                <td>15/08/2024</td>
                                <td><span class="badge badge-themeSkyblue">Shortlisted by Wisdom AI</span></td>
                                <td><a class="a-link collapsed" data-bs-toggle="collapse" data-bs-target="#johndue"
                                        aria-expanded="false" aria-controls="johndue">Interview
                                        Details</a>
                                </td>
                                <td>
                                    <div class="dropdown table-dropdown">
                                        <button class="btn btn-secondary dropdown-toggle dots-link" type="button"
                                            id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="fa-solid fa-ellipsis"></i>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                            <li><a class="dropdown-item" href="#">View</a></li>
                                            <li><a class="dropdown-item" href="#">Notes</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse" id="johndue">
                                <td colspan="10">
                                    <div class="bg">
                                        <table class="w-100">
                                            <tr>
                                                <th>Interview Template</th>
                                                <th>Round</th>
                                                <th>Interview Type</th>
                                                <th>Interviewer</th>
                                                <th>Interview Status</th>
                                                <th></th>
                                            </tr>
                                            <tr>
                                                <td>Lorem ipsum</td>
                                                <td>HR Round</td>
                                                <td>Introductory</td>
                                                <td>HR</td>
                                                <td><span class="badge badge-info">Slot Booked</span></td>
                                                <td><a href="#shareMeetLink-modal" data-bs-toggle="modal"
                                                        class="btn btn-themeSkyblue btn-small">Share Meeting
                                                        Link</a></td>
                                            </tr>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                                        </div>
                                        <span>Christian Slater</span>
                                    </div>
                                </td>
                                <td>Maldivian</td>
                                <td>545612314654</td>
                                <td>2 year</td>
                                <td>dummy@lorem.com</td>
                                <td>+ 91 541 245 4546</td>
                                <td>15/08/2024</td>
                                <td><span class="badge badge-themeBlue">HR Shortlisted</span></td>
                                <td><a class="a-link collapsed" data-bs-toggle="collapse"
                                        data-bs-target="#christianSlater" aria-expanded="false"
                                        aria-controls="christianSlater">Interview
                                        Details</a>
                                </td>
                                <td>
                                    <div class="dropdown table-dropdown">
                                        <button class="btn btn-secondary dropdown-toggle dots-link" type="button"
                                            id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="fa-solid fa-ellipsis"></i>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                            <li><a class="dropdown-item" href="#">View</a></li>
                                            <li><a class="dropdown-item" href="#">Notes</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse" id="christianSlater">
                                <td colspan="10">
                                    <div class="bg">
                                        <table class="w-100">
                                            <tr>
                                                <th>Interview Template</th>
                                                <th>Round</th>
                                                <th>Interview Type</th>
                                                <th>Interviewer</th>
                                                <th>Interview Status</th>
                                                <th></th>
                                            </tr>
                                            <tr>
                                                <td>Lorem ipsum</td>
                                                <td>HR Round</td>
                                                <td>Introductory</td>
                                                <td>HR</td>
                                                <td><span class="badge badge-info">Slot Booked</span></td>
                                                <td><a href="#shareMeetLink-modal" data-bs-toggle="modal"
                                                        class="btn btn-themeSkyblue btn-small">Share Meeting
                                                        Link</a></td>
                                            </tr>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                                        </div>
                                        <span>Brijesh Pandey</span>
                                    </div>
                                </td>
                                <td>Maldivian</td>
                                <td>545612314654</td>
                                <td>2 year</td>
                                <td>dummy@lorem.com</td>
                                <td>+ 91 541 245 4546</td>
                                <td>15/08/2024</td>
                                <td><span class="badge badge-themeSuccess">Selected</span></td>
                                <td><a class="a-link collapsed" data-bs-toggle="collapse"
                                        data-bs-target="#brijeshPandey" aria-expanded="false"
                                        aria-controls="brijeshPandey">Interview
                                        Details</a>
                                </td>
                                <td>
                                    <div class="dropdown table-dropdown">
                                        <button class="btn btn-secondary dropdown-toggle dots-link" type="button"
                                            id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="fa-solid fa-ellipsis"></i>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                            <li><a class="dropdown-item" href="#">View</a></li>
                                            <li><a class="dropdown-item" href="#">Notes</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse" id="brijeshPandey">
                                <td colspan="10">
                                    <div class="bg">
                                        <table class="w-100">
                                            <tr>
                                                <th>Interview Template</th>
                                                <th>Round</th>
                                                <th>Interview Type</th>
                                                <th>Interviewer</th>
                                                <th>Interview Status</th>
                                                <th></th>
                                            </tr>
                                            <tr>
                                                <td>Lorem ipsum</td>
                                                <td>HR Round</td>
                                                <td>Introductory</td>
                                                <td>HR</td>
                                                <td><span class="badge badge-info">Slot Booked</span></td>
                                                <td><a href="#shareMeetLink-modal" data-bs-toggle="modal"
                                                        class="btn btn-themeSkyblue btn-small">Share Meeting
                                                        Link</a></td>
                                            </tr>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                                        </div>
                                        <span>Seerish Yadav</span>
                                    </div>
                                </td>
                                <td>Indian</td>
                                <td>255646121456</td>
                                <td>4 year</td>
                                <td>johndoe@lorem.com</td>
                                <td>+ 91 541 245 4546</td>
                                <td>15/08/2024</td>
                                <td><span class="badge badge-themeWarning">HR Round</span></td>
                                <td><a class="a-link collapsed" data-bs-toggle="collapse"
                                        data-bs-target="#seerishYadav" aria-expanded="false"
                                        aria-controls="seerishYadav">Interview
                                        Details</a>
                                </td>
                                <td>
                                    <div class="dropdown table-dropdown">
                                        <button class="btn btn-secondary dropdown-toggle dots-link" type="button"
                                            id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="fa-solid fa-ellipsis"></i>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                            <li><a class="dropdown-item" href="#">View</a></li>
                                            <li><a class="dropdown-item" href="#">Notes</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse" id="seerishYadav">
                                <td colspan="10">
                                    <div class="bg">
                                        <table class="w-100">
                                            <tr>
                                                <th>Interview Template</th>
                                                <th>Round</th>
                                                <th>Interview Type</th>
                                                <th>Interviewer</th>
                                                <th>Interview Status</th>
                                                <th></th>
                                            </tr>
                                            <tr>
                                                <td>Lorem ipsum</td>
                                                <td>HR Round</td>
                                                <td>Introductory</td>
                                                <td>HR</td>
                                                <td><span class="badge badge-info">Slot Booked</span></td>
                                                <td><a href="#shareMeetLink-modal" data-bs-toggle="modal"
                                                        class="btn btn-themeSkyblue btn-small">Share Meeting
                                                        Link</a></td>
                                            </tr>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                                        </div>
                                        <span>John Doe</span>
                                    </div>
                                </td>
                                <td>Indian</td>
                                <td>255646121456</td>
                                <td>4 year</td>
                                <td>johndoe@lorem.com</td>
                                <td>+ 91 541 245 4546</td>
                                <td>15/08/2024</td>
                                <td><span class="badge badge-themePurple">HOD Round</span></td>
                                <td><a class="a-link collapsed" data-bs-toggle="collapse" data-bs-target="#johndue1"
                                        aria-expanded="false" aria-controls="johndue1">Interview
                                        Details</a>
                                </td>
                                <td>
                                    <div class="dropdown table-dropdown">
                                        <button class="btn btn-secondary dropdown-toggle dots-link" type="button"
                                            id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="fa-solid fa-ellipsis"></i>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                            <li><a class="dropdown-item" href="#">View</a></li>
                                            <li><a class="dropdown-item" href="#">Notes</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse" id="johndue1">
                                <td colspan="10">
                                    <div class="bg">
                                        <table class="w-100">
                                            <tr>
                                                <th>Interview Template</th>
                                                <th>Round</th>
                                                <th>Interview Type</th>
                                                <th>Interviewer</th>
                                                <th>Interview Status</th>
                                                <th></th>
                                            </tr>
                                            <tr>
                                                <td>Lorem ipsum</td>
                                                <td>HR Round</td>
                                                <td>Introductory</td>
                                                <td>HR</td>
                                                <td><span class="badge badge-info">Slot Booked</span></td>
                                                <td><a href="#shareMeetLink-modal" data-bs-toggle="modal"
                                                        class="btn btn-themeSkyblue btn-small">Share Meeting
                                                        Link</a></td>
                                            </tr>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="tableUser-block">
                                        <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                                        </div>
                                        <span>Christian Slater</span>
                                    </div>
                                </td>
                                <td>Maldivian</td>
                                <td>545612314654</td>
                                <td>2 year</td>
                                <td>dummy@lorem.com</td>
                                <td>+ 91 541 245 4546</td>
                                <td>15/08/2024</td>
                                <td><span class="badge badge-themePink">GM Round</span></td>
                                <td><a class="a-link collapsed" data-bs-toggle="collapse"
                                        data-bs-target="#christianSlater1" aria-expanded="false"
                                        aria-controls="christianSlater1">Interview
                                        Details</a>
                                </td>
                                <td>
                                    <div class="dropdown table-dropdown">
                                        <button class="btn btn-secondary dropdown-toggle dots-link" type="button"
                                            id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="fa-solid fa-ellipsis"></i>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                            <li><a class="dropdown-item" href="#">View</a></li>
                                            <li><a class="dropdown-item" href="#">Notes</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse" id="christianSlater1">
                                <td colspan="10">
                                    <div class="bg">
                                        <table class="w-100">
                                            <tr>
                                                <th>Interview Template</th>
                                                <th>Round</th>
                                                <th>Interview Type</th>
                                                <th>Interviewer</th>
                                                <th>Interview Status</th>
                                                <th></th>
                                            </tr>
                                            <tr>
                                                <td>Lorem ipsum</td>
                                                <td>HR Round</td>
                                                <td>Introductory</td>
                                                <td>HR</td>
                                                <td><span class="badge badge-info">Slot Booked</span></td>
                                                <td><a href="#shareMeetLink-modal" data-bs-toggle="modal"
                                                        class="btn btn-themeSkyblue btn-small">Share Meeting
                                                        Link</a></td>
                                            </tr>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="grid-main d-none  ">
                <div class="row g-md-4 g-3 mb-4">
                    <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                        <div class="applicantsGrid-block">
                            <div class="dropdown table-dropdown ">
                                <button class="btn btn-secondary dropdown-toggle dotsV-link" type="button"
                                    id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                                    <li><a class="dropdown-item" href="#intDetail-modal"
                                            data-bs-toggle="modal">Interview
                                            Details</a></li>
                                </ul>
                            </div>
                            <!-- <a href="#" class="dotsV-link"><i class="fa-solid fa-ellipsis-vertical"></i></a> -->
                            <div class="progress-container skyblue" data-progress="20">
                                <svg class="progress-circle" viewBox="0 0 120 120">
                                    <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress" cx="60" cy="60" r="54"></circle>
                                </svg>
                                <div class="img-obj cover"> <img src="assets/images/user-2.svg" alt="image"></div>
                            </div>
                            <h6>John Doe</h6>
                            <p>Assistant Front Desk Manager</p>
                            <span class="badge badge-themeSkyblue">Shortlisted by Wisdom AI</span>
                            <div class="bg">
                                <p><i class="fa-regular fa-envelope"></i>johndoe@lorem.com</p>
                                <p><i class="fa-regular fa-phone"></i>+ 91 541 245 4546</p>
                            </div>
                            <div class="bg bg-table">
                                <table>
                                    <tr>
                                        <th>Experience:</th>
                                        <td>5 years</td>
                                    </tr>
                                    <tr>
                                        <th>Applied On:</th>
                                        <td>15/08/2024</td>
                                    </tr>
                                    <tr>
                                        <th>Passport No.</th>
                                        <td>56451545646</td>
                                    </tr>
                                    <tr>
                                        <th>Current Employment Status :</th>
                                        <td>Employed</td>
                                    </tr>
                                    <tr>
                                        <th>Current position:</th>
                                        <td>Manager</td>
                                    </tr>
                                </table>
                            </div>
                            <div>
                                <a href="#" class="btn btn-themeBlue btn-sm">Notes</a>
                                <a href="#" class="btn btn-themeSkyblue btn-sm userApplicants-btn">View</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                        <div class="applicantsGrid-block">
                            <div class="dropdown table-dropdown ">
                                <button class="btn btn-secondary dropdown-toggle dotsV-link" type="button"
                                    id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                                    <li><a class="dropdown-item" href="#intDetail-modal"
                                            data-bs-toggle="modal">Interview
                                            Details</a></li>
                                </ul>
                            </div>
                            <div class="progress-container blue" data-progress="40">
                                <svg class="progress-circle" viewBox="0 0 120 120">
                                    <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress" cx="60" cy="60" r="54"></circle>
                                </svg>
                                <div class="img-obj cover"> <img src="assets/images/user-2.svg" alt="image"></div>
                            </div>
                            <h6>Christian Slatter</h6>
                            <p>Assistant Front Desk Manager</p>
                            <span class="badge badge-themeBlue">HR Shortlisted</span>
                            <div class="bg">
                                <p><i class="fa-regular fa-envelope"></i>johndoe@lorem.com</p>
                                <p><i class="fa-regular fa-phone"></i>+ 91 541 245 4546</p>
                            </div>
                            <div class="bg bg-table">
                                <table>
                                    <tr>
                                        <th>Experience:</th>
                                        <td>5 years</td>
                                    </tr>
                                    <tr>
                                        <th>Applied On:</th>
                                        <td>15/08/2024</td>
                                    </tr>
                                    <tr>
                                        <th>Passport No.</th>
                                        <td>56451545646</td>
                                    </tr>
                                    <tr>
                                        <th>Current Employment Status :</th>
                                        <td>Employed</td>
                                    </tr>
                                    <tr>
                                        <th>Current position:</th>
                                        <td>Manager</td>
                                    </tr>
                                </table>
                            </div>
                            <div>
                                <a href="#" class="btn btn-themeBlue btn-sm">Notes</a>
                                <a href="#" class="btn btn-themeSkyblue btn-sm userApplicants-btn">View</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                        <div class="applicantsGrid-block">
                            <div class="dropdown table-dropdown ">
                                <button class="btn btn-secondary dropdown-toggle dotsV-link" type="button"
                                    id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                                    <li><a class="dropdown-item" href="#intDetail-modal"
                                            data-bs-toggle="modal">Interview
                                            Details</a></li>
                                </ul>
                            </div>
                            <div class="progress-container success" data-progress="100">
                                <svg class="progress-circle" viewBox="0 0 120 120">
                                    <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress" cx="60" cy="60" r="54"></circle>
                                </svg>
                                <div class="img-obj cover"> <img src="assets/images/user-2.svg" alt="image"></div>
                            </div>
                            <h6>Emily Bell</h6>
                            <p>Assistant Front Desk Manager</p>
                            <span class="badge badge-themeSuccess">Selected</span>
                            <div class="bg">
                                <p><i class="fa-regular fa-envelope"></i>johndoe@lorem.com</p>
                                <p><i class="fa-regular fa-phone"></i>+ 91 541 245 4546</p>
                            </div>
                            <div class="bg bg-table">
                                <table>
                                    <tr>
                                        <th>Experience:</th>
                                        <td>5 years</td>
                                    </tr>
                                    <tr>
                                        <th>Applied On:</th>
                                        <td>15/08/2024</td>
                                    </tr>
                                    <tr>
                                        <th>Passport No.</th>
                                        <td>56451545646</td>
                                    </tr>
                                    <tr>
                                        <th>Current Employment Status :</th>
                                        <td>Employed</td>
                                    </tr>
                                    <tr>
                                        <th>Current position:</th>
                                        <td>Manager</td>
                                    </tr>
                                </table>
                            </div>
                            <div>
                                <a href="#" class="btn btn-themeBlue btn-sm">Notes</a>
                                <a href="#" class="btn btn-themeSkyblue btn-sm userApplicants-btn">View</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                        <div class="applicantsGrid-block">
                            <div class="dropdown table-dropdown ">
                                <button class="btn btn-secondary dropdown-toggle dotsV-link" type="button"
                                    id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                                    <li><a class="dropdown-item" href="#intDetail-modal"
                                            data-bs-toggle="modal">Interview
                                            Details</a></li>
                                </ul>
                            </div>
                            <div class="progress-container warning" data-progress="70">
                                <svg class="progress-circle" viewBox="0 0 120 120">
                                    <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress" cx="60" cy="60" r="54"></circle>
                                </svg>
                                <div class="img-obj cover"> <img src="assets/images/user-2.svg" alt="image"></div>
                            </div>
                            <h6>Olivia Butller</h6>
                            <p>Assistant Front Desk Manager</p>
                            <span class="badge badge-themeWarning">HR Round</span>
                            <div class="bg">
                                <p><i class="fa-regular fa-envelope"></i>johndoe@lorem.com</p>
                                <p><i class="fa-regular fa-phone"></i>+ 91 541 245 4546</p>
                            </div>
                            <div class="bg bg-table">
                                <table>
                                    <tr>
                                        <th>Experience:</th>
                                        <td>5 years</td>
                                    </tr>
                                    <tr>
                                        <th>Applied On:</th>
                                        <td>15/08/2024</td>
                                    </tr>
                                    <tr>
                                        <th>Passport No.</th>
                                        <td>56451545646</td>
                                    </tr>
                                    <tr>
                                        <th>Current Employment Status :</th>
                                        <td>Employed</td>
                                    </tr>
                                    <tr>
                                        <th>Current position:</th>
                                        <td>Manager</td>
                                    </tr>
                                </table>
                            </div>
                            <div>
                                <a href="#" class="btn btn-themeBlue btn-sm">Notes</a>
                                <a href="#" class="btn btn-themeSkyblue btn-sm userApplicants-btn">View</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                        <div class="applicantsGrid-block">
                            <div class="dropdown table-dropdown ">
                                <button class="btn btn-secondary dropdown-toggle dotsV-link" type="button"
                                    id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                                    <li><a class="dropdown-item" href="#intDetail-modal"
                                            data-bs-toggle="modal">Interview
                                            Details</a></li>
                                </ul>
                            </div>
                            <div class="progress-container purple" data-progress="70">
                                <svg class="progress-circle" viewBox="0 0 120 120">
                                    <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress" cx="60" cy="60" r="54"></circle>
                                </svg>
                                <div class="img-obj cover"> <img src="assets/images/user-2.svg" alt="image"></div>
                            </div>
                            <h6>John Doe</h6>
                            <p>Assistant Front Desk Manager</p>
                            <span class="badge badge-themePurple">HOD Round</span>
                            <div class="bg">
                                <p><i class="fa-regular fa-envelope"></i>johndoe@lorem.com</p>
                                <p><i class="fa-regular fa-phone"></i>+ 91 541 245 4546</p>
                            </div>
                            <div class="bg bg-table">
                                <table>
                                    <tr>
                                        <th>Experience:</th>
                                        <td>5 years</td>
                                    </tr>
                                    <tr>
                                        <th>Applied On:</th>
                                        <td>15/08/2024</td>
                                    </tr>
                                    <tr>
                                        <th>Passport No.</th>
                                        <td>56451545646</td>
                                    </tr>
                                    <tr>
                                        <th>Current Employment Status :</th>
                                        <td>Employed</td>
                                    </tr>
                                    <tr>
                                        <th>Current position:</th>
                                        <td>Manager</td>
                                    </tr>
                                </table>
                            </div>
                            <div>
                                <a href="#" class="btn btn-themeBlue btn-sm">Notes</a>
                                <a href="#" class="btn btn-themeSkyblue btn-sm userApplicants-btn">View</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                        <div class="applicantsGrid-block">
                            <div class="dropdown table-dropdown ">
                                <button class="btn btn-secondary dropdown-toggle dotsV-link" type="button"
                                    id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                                    <li><a class="dropdown-item" href="#intDetail-modal"
                                            data-bs-toggle="modal">Interview
                                            Details</a></li>
                                </ul>
                            </div>
                            <div class="progress-container pink" data-progress="70">
                                <svg class="progress-circle" viewBox="0 0 120 120">
                                    <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress" cx="60" cy="60" r="54"></circle>
                                </svg>
                                <div class="img-obj cover"> <img src="assets/images/user-2.svg" alt="image"></div>
                            </div>
                            <h6>John Doe</h6>
                            <p>Assistant Front Desk Manager</p>
                            <span class="badge badge-themePink">GM Round</span>
                            <div class="bg">
                                <p><i class="fa-regular fa-envelope"></i>johndoe@lorem.com</p>
                                <p><i class="fa-regular fa-phone"></i>+ 91 541 245 4546</p>
                            </div>
                            <div class="bg bg-table">
                                <table>
                                    <tr>
                                        <th>Experience:</th>
                                        <td>5 years</td>
                                    </tr>
                                    <tr>
                                        <th>Applied On:</th>
                                        <td>15/08/2024</td>
                                    </tr>
                                    <tr>
                                        <th>Passport No.</th>
                                        <td>56451545646</td>
                                    </tr>
                                    <tr>
                                        <th>Current Employment Status :</th>
                                        <td>Employed</td>
                                    </tr>
                                    <tr>
                                        <th>Current position:</th>
                                        <td>Manager</td>
                                    </tr>
                                </table>
                            </div>
                            <div>
                                <a href="#" class="btn btn-themeBlue btn-sm">Notes</a>
                                <a href="#" class="btn btn-themeSkyblue btn-sm userApplicants-btn">View</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                        <div class="applicantsGrid-block">
                            <div class="dropdown table-dropdown ">
                                <button class="btn btn-secondary dropdown-toggle dotsV-link" type="button"
                                    id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                                    <li><a class="dropdown-item" href="#intDetail-modal"
                                            data-bs-toggle="modal">Interview
                                            Details</a></li>
                                </ul>
                            </div>
                            <!-- <a href="#" class="dotsV-link"><i class="fa-solid fa-ellipsis-vertical"></i></a> -->
                            <div class="progress-container skyblue" data-progress="20">
                                <svg class="progress-circle" viewBox="0 0 120 120">
                                    <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress" cx="60" cy="60" r="54"></circle>
                                </svg>
                                <div class="img-obj cover"> <img src="assets/images/user-2.svg" alt="image"></div>
                            </div>
                            <h6>John Doe</h6>
                            <p>Assistant Front Desk Manager</p>
                            <span class="badge badge-themeSkyblue">Shortlisted by Wisdom AI</span>
                            <div class="bg">
                                <p><i class="fa-regular fa-envelope"></i>johndoe@lorem.com</p>
                                <p><i class="fa-regular fa-phone"></i>+ 91 541 245 4546</p>
                            </div>
                            <div class="bg bg-table">
                                <table>
                                    <tr>
                                        <th>Experience:</th>
                                        <td>5 years</td>
                                    </tr>
                                    <tr>
                                        <th>Applied On:</th>
                                        <td>15/08/2024</td>
                                    </tr>
                                    <tr>
                                        <th>Passport No.</th>
                                        <td>56451545646</td>
                                    </tr>
                                    <tr>
                                        <th>Current Employment Status :</th>
                                        <td>Employed</td>
                                    </tr>
                                    <tr>
                                        <th>Current position:</th>
                                        <td>Manager</td>
                                    </tr>
                                </table>
                            </div>
                            <div>
                                <a href="#" class="btn btn-themeBlue btn-sm">Notes</a>
                                <a href="#" class="btn btn-themeSkyblue btn-sm userApplicants-btn">View</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                        <div class="applicantsGrid-block">
                            <div class="dropdown table-dropdown ">
                                <button class="btn btn-secondary dropdown-toggle dotsV-link" type="button"
                                    id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                                    <li><a class="dropdown-item" href="#intDetail-modal"
                                            data-bs-toggle="modal">Interview
                                            Details</a></li>
                                </ul>
                            </div>
                            <div class="progress-container blue" data-progress="40">
                                <svg class="progress-circle" viewBox="0 0 120 120">
                                    <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress" cx="60" cy="60" r="54"></circle>
                                </svg>
                                <div class="img-obj cover"> <img src="assets/images/user-2.svg" alt="image"></div>
                            </div>
                            <h6>Christian Slatter</h6>
                            <p>Assistant Front Desk Manager</p>
                            <span class="badge badge-themeBlue">HR Shortlisted</span>
                            <div class="bg">
                                <p><i class="fa-regular fa-envelope"></i>johndoe@lorem.com</p>
                                <p><i class="fa-regular fa-phone"></i>+ 91 541 245 4546</p>
                            </div>
                            <div class="bg bg-table">
                                <table>
                                    <tr>
                                        <th>Experience:</th>
                                        <td>5 years</td>
                                    </tr>
                                    <tr>
                                        <th>Applied On:</th>
                                        <td>15/08/2024</td>
                                    </tr>
                                    <tr>
                                        <th>Passport No.</th>
                                        <td>56451545646</td>
                                    </tr>
                                    <tr>
                                        <th>Current Employment Status :</th>
                                        <td>Employed</td>
                                    </tr>
                                    <tr>
                                        <th>Current position:</th>
                                        <td>Manager</td>
                                    </tr>
                                </table>
                            </div>
                            <div>
                                <a href="#" class="btn btn-themeBlue btn-sm">Notes</a>
                                <a href="#" class="btn btn-themeSkyblue btn-sm userApplicants-btn">View</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                        <div class="applicantsGrid-block">
                            <div class="dropdown table-dropdown ">
                                <button class="btn btn-secondary dropdown-toggle dotsV-link" type="button"
                                    id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                                    <li><a class="dropdown-item" href="#intDetail-modal"
                                            data-bs-toggle="modal">Interview
                                            Details</a></li>
                                </ul>
                            </div>
                            <div class="progress-container success" data-progress="100">
                                <svg class="progress-circle" viewBox="0 0 120 120">
                                    <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress" cx="60" cy="60" r="54"></circle>
                                </svg>
                                <div class="img-obj cover"> <img src="assets/images/user-2.svg" alt="image"></div>
                            </div>
                            <h6>Emily Bell</h6>
                            <p>Assistant Front Desk Manager</p>
                            <span class="badge badge-themeSuccess">Selected</span>
                            <div class="bg">
                                <p><i class="fa-regular fa-envelope"></i>johndoe@lorem.com</p>
                                <p><i class="fa-regular fa-phone"></i>+ 91 541 245 4546</p>
                            </div>
                            <div class="bg bg-table">
                                <table>
                                    <tr>
                                        <th>Experience:</th>
                                        <td>5 years</td>
                                    </tr>
                                    <tr>
                                        <th>Applied On:</th>
                                        <td>15/08/2024</td>
                                    </tr>
                                    <tr>
                                        <th>Passport No.</th>
                                        <td>56451545646</td>
                                    </tr>
                                    <tr>
                                        <th>Current Employment Status :</th>
                                        <td>Employed</td>
                                    </tr>
                                    <tr>
                                        <th>Current position:</th>
                                        <td>Manager</td>
                                    </tr>
                                </table>
                            </div>
                            <div>
                                <a href="#" class="btn btn-themeBlue btn-sm">Notes</a>
                                <a href="#" class="btn btn-themeSkyblue btn-sm userApplicants-btn">View</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
                        <div class="applicantsGrid-block">
                            <div class="dropdown table-dropdown ">
                                <button class="btn btn-secondary dropdown-toggle dotsV-link" type="button"
                                    id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                                    <li><a class="dropdown-item" href="#intDetail-modal"
                                            data-bs-toggle="modal">Interview
                                            Details</a></li>
                                </ul>
                            </div>
                            <div class="progress-container warning" data-progress="70">
                                <svg class="progress-circle" viewBox="0 0 120 120">
                                    <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                                    <circle class="progress" cx="60" cy="60" r="54"></circle>
                                </svg>
                                <div class="img-obj cover"> <img src="assets/images/user-2.svg" alt="image"></div>
                            </div>
                            <h6>Olivia Butller</h6>
                            <p>Assistant Front Desk Manager</p>
                            <span class="badge badge-themeWarning">HR Round</span>
                            <div class="bg">
                                <p><i class="fa-regular fa-envelope"></i>johndoe@lorem.com</p>
                                <p><i class="fa-regular fa-phone"></i>+ 91 541 245 4546</p>
                            </div>
                            <div class="bg bg-table">
                                <table>
                                    <tr>
                                        <th>Experience:</th>
                                        <td>5 years</td>
                                    </tr>
                                    <tr>
                                        <th>Applied On:</th>
                                        <td>15/08/2024</td>
                                    </tr>
                                    <tr>
                                        <th>Passport No.</th>
                                        <td>56451545646</td>
                                    </tr>
                                    <tr>
                                        <th>Current Employment Status :</th>
                                        <td>Employed</td>
                                    </tr>
                                    <tr>
                                        <th>Current position:</th>
                                        <td>Manager</td>
                                    </tr>
                                </table>
                            </div>
                            <div>
                                <a href="#" class="btn btn-themeBlue btn-sm">Notes</a>
                                <a href="#" class="btn btn-themeSkyblue btn-sm userApplicants-btn">View</a>
                            </div>
                        </div>
                    </div>
                </div>
                <nav aria-label="Page navigation example">
                    <ul class="pagination justify-content-end">
                        <li class="page-item "><a class="page-link" href="#"><i
                                    class="fa-solid fa-angle-left"></i></a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item"><a class="page-link" href="#"><i
                                    class="fa-solid fa-angle-right"></i></a>
                        </li>
                    </ul>
                </nav>
            </div>

        </div>


    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
    <script type="text/javascript">
        // new DataTable('#example');
        $(document).ready(function () {
            $('.data-Table').dataTable({
                "searching": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": false,
                "bAutoWidth": false,
                scrollX: true,
                "iDisplayLength": 10,
            });

            $(".btn-grid").click(function () {
                $(this).addClass("active");
                $(".grid-main").addClass("d-block");
                $(".grid-main").removeClass("d-none");
                $(".btn-list").removeClass("active");
                $(".list-main").addClass("d-none");
                $(".list-main").removeClass("d-block");
            });
            $(".btn-list").click(function () {
                $(this).addClass("active");
                $(".list-main").addClass("d-block");
                $(".list-main").removeClass("d-none");
                $(".btn-grid").removeClass("active");
                $(".grid-main").addClass("d-none");
                $(".grid-main").addClass("d-block");
            });

            const $userApplicantsBtn = $(".userApplicants-btn");
            const $userApplicantsWrapper = $(".userApplicants-wrapper");

            // Toggle 'end-0' class when userApplicants button is clicked
            $userApplicantsBtn.on("click", function (e) {
                e.stopPropagation(); // Prevent event from bubbling up to document click
                $userApplicantsWrapper.toggleClass("end-0");
            });

            // Remove 'end-0' class when clicking outside userApplicants-btn and userApplicants-wrapper
            $(document).on("click", function (e) {
                if (
                    !$userApplicantsWrapper.is(e.target) &&
                    !$userApplicantsBtn.is(e.target) &&
                    $userApplicantsWrapper.has(e.target).length === 0 &&
                    $userApplicantsBtn.has(e.target).length === 0
                ) {
                    $userApplicantsWrapper.removeClass("end-0");
                }
            });


            $(".userAppInt-vCommBtn").click(function () {
                $(this).addClass("d-none");
                $(this).removeClass("d-block");
                $(this).siblings(".userAppInt-hCommBtn").addClass("d-block");
                $(this).siblings(".userAppInt-hCommBtn").removeClass("d-none");
                $(this).parents(".tableUser-block").siblings(".userAppInt-commBlock").addClass("d-block");
            });
            $(".userAppInt-hCommBtn").click(function () {
                $(this).addClass("d-none");
                $(this).removeClass("d-block");
                $(this).siblings(".userAppInt-vCommBtn").addClass("d-block");
                $(this).siblings(".userAppInt-vCommBtn").removeClass("d-none");
                $(this).parents(".tableUser-block").siblings(".userAppInt-commBlock").removeClass("d-block");
            });
        });

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
    </script>
@endsection