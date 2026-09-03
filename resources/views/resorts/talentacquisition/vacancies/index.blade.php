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
                    <div class="row justify-content-between g-3">
                        <div class="col-auto">
                            <div class="page-title">
                                <span>Talent Acquisition</span>
                                <h1>{{ $page_title }}</h1>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="d-flex justify-content-end">
                                <a href="#" class="btn btn-theme">Add New Vacancy</a>
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
                                    <select class="form-select dd-native-select" id="vacIndexAllVacancy">
                                        <option selected>All Vacancy</option>
                                        <option value="1">abc</option>
                                        <option value="2">abc</option>
                                    </select>
                                    <div class="dd" data-target="#vacIndexAllVacancy">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="dd-lbl">All Vacancy</span>
                                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Vacancy">
                                            <div class="dd-scroll">
                                                <div class="dd-item active" role="option" data-value=""><span class="dd-nm">All Vacancy</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                <div class="dd-item" role="option" data-value="1"><span class="dd-nm">abc</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                <div class="dd-item" role="option" data-value="2"><span class="dd-nm">abc</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                    <select class="form-select dd-native-select" id="vacIndexDepartment">
                                        <option selected>Department</option>
                                        <option value="1">abc</option>
                                        <option value="2">abc</option>
                                    </select>
                                    <div class="dd" data-target="#vacIndexDepartment">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="dd-lbl">Department</span>
                                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Department">
                                            <div class="dd-scroll">
                                                <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Department</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                <div class="dd-item" role="option" data-value="1"><span class="dd-nm">abc</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                <div class="dd-item" role="option" data-value="2"><span class="dd-nm">abc</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                    <select class="form-select dd-native-select" id="vacIndexPositions">
                                        <option selected>Positions</option>
                                        <option value="1">abc</option>
                                        <option value="2">abc</option>
                                    </select>
                                    <div class="dd" data-target="#vacIndexPositions">
                                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                            <span class="dd-lbl">Positions</span>
                                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                        </button>
                                        <div class="dd-panel" role="listbox" aria-label="Position">
                                            <div class="dd-scroll">
                                                <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Positions</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                <div class="dd-item" role="option" data-value="1"><span class="dd-nm">abc</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                <div class="dd-item" role="option" data-value="2"><span class="dd-nm">abc</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto ms-auto">
                                    <a href="#" class="btn btn-grid"><img src="{{ URL::asset('resorts_assets/images/grid.svg')}}" alt="icon"></a>
                                    <a href="#" class="btn btn-list active"><img src="{{ URL::asset('resorts_assets/images/list.svg')}}"
                                            alt="icon"></a>
                                </div>
                            </div>
                        </div>
                        <div class="list-main d-block">
                            <table id="" class="table data-Table table-Vacancies w-100">
                                <thead>
                                    <tr>
                                        <th>Positions</th>
                                        <th>Department</th>
                                        <th>Section</th>
                                        <th>No. of position</th>
                                        <th>Applicant</th>
                                        <th>Application Date</th>
                                        <th>Expiry Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Assistant Front Desk Manager <span class="badge badge-themeLight">M-415</span>
                                        </td>
                                        <td>Management <span class="badge badge-themeLight">M-415</span></td>
                                        <td>Lorem ipsum <span class="badge badge-themeLight">M-515</span></td>
                                        <td>02</td>
                                        <td>56</td>
                                        <td>29/08/2024</td>
                                        <td>26/09/2024</td>
                                        <td>
                                            <div class="dropdown table-dropdown">
                                                <button class="btn eb-btn-ghost dropdown-toggle dots-link" type="button"
                                                    id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i class="fa-solid fa-ellipsis"></i>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                    <li><a class="dropdown-item" href="#">View Applicants</a></li>
                                                    <li><a class="dropdown-item" href="#">Extend The Job Ad Link</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Chef Concierge <span class="badge badge-themeLight">M-124</span></td>
                                        <td>Management <span class="badge badge-themeLight">M-416</span></td>
                                        <td>Lorem ipsum <span class="badge badge-themeLight">M-415</span></td>
                                        <td>05</td>
                                        <td>103</td>
                                        <td>29/08/2024</td>
                                        <td>26/09/2024</td>
                                        <td>
                                            <div class="dropdown table-dropdown">
                                                <button class="btn eb-btn-ghost dropdown-toggle dots-link" type="button"
                                                    id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i class="fa-solid fa-ellipsis"></i>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                    <li><a class="dropdown-item" href="#">View Applicants</a></li>
                                                    <li><a class="dropdown-item" href="#">Extend The Job Ad Link</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Night Desk Clerk <span class="badge badge-themeLight">M-454</span></td>
                                        <td>Front Office <span class="badge badge-themeLight">M-845</span></td>
                                        <td>Lorem ipsum <span class="badge badge-themeLight">M-416</span></td>
                                        <td>03</td>
                                        <td>29</td>
                                        <td>29/08/2024</td>
                                        <td>26/09/2024</td>
                                        <td>
                                            <div class="dropdown table-dropdown">
                                                <button class="btn eb-btn-ghost dropdown-toggle dots-link" type="button"
                                                    id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i class="fa-solid fa-ellipsis"></i>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                    <li><a class="dropdown-item" href="#">View Applicants</a></li>
                                                    <li><a class="dropdown-item" href="#">Extend The Job Ad Link</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Guest Services Coordinator <span class="badge badge-themeLight">M-415</span>
                                        </td>
                                        <td>Housekeeping <span class="badge badge-themeLight">M-415</span></td>
                                        <td>Lorem ipsum <span class="badge badge-themeLight">M-515</span></td>
                                        <td>02</td>
                                        <td>56</td>
                                        <td>29/08/2024</td>
                                        <td>26/09/2024</td>
                                        <td>
                                            <div class="dropdown table-dropdown">
                                                <button class="btn eb-btn-ghost dropdown-toggle dots-link" type="button"
                                                    id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i class="fa-solid fa-ellipsis"></i>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                    <li><a class="dropdown-item" href="#">View Applicants</a></li>
                                                    <li><a class="dropdown-item" href="#">Extend The Job Ad Link</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Assistant Front Desk Manager <span class="badge badge-themeLight">M-415</span>
                                        </td>
                                        <td>Management <span class="badge badge-themeLight">M-415</span></td>
                                        <td>Lorem ipsum <span class="badge badge-themeLight">M-515</span></td>
                                        <td>02</td>
                                        <td>56</td>
                                        <td>29/08/2024</td>
                                        <td>26/09/2024</td>
                                        <td>
                                            <div class="dropdown table-dropdown">
                                                <button class="btn eb-btn-ghost dropdown-toggle dots-link" type="button"
                                                    id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i class="fa-solid fa-ellipsis"></i>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                    <li><a class="dropdown-item" href="#">View Applicants</a></li>
                                                    <li><a class="dropdown-item" href="#">Extend The Job Ad Link</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Chef Concierge <span class="badge badge-themeLight">M-415</span></td>
                                        <td>Management <span class="badge badge-themeLight">M-415</span></td>
                                        <td>Lorem ipsum <span class="badge badge-themeLight">M-515</span></td>
                                        <td>02</td>
                                        <td>56</td>
                                        <td>29/08/2024</td>
                                        <td>26/09/2024</td>
                                        <td>
                                            <div class="dropdown table-dropdown">
                                                <button class="btn eb-btn-ghost dropdown-toggle dots-link" type="button"
                                                    id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i class="fa-solid fa-ellipsis"></i>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                    <li><a class="dropdown-item" href="#">View Applicants</a></li>
                                                    <li><a class="dropdown-item" href="#">Extend The Job Ad Link</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Assistant Front Desk Manager <span class="badge badge-themeLight">M-415</span>
                                        </td>
                                        <td>Management <span class="badge badge-themeLight">M-415</span></td>
                                        <td>Lorem ipsum <span class="badge badge-themeLight">M-515</span></td>
                                        <td>02</td>
                                        <td>56</td>
                                        <td>29/08/2024</td>
                                        <td>26/09/2024</td>
                                        <td>
                                            <div class="dropdown table-dropdown">
                                                <button class="btn eb-btn-ghost dropdown-toggle dots-link" type="button"
                                                    id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i class="fa-solid fa-ellipsis"></i>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                    <li><a class="dropdown-item" href="#">View Applicants</a></li>
                                                    <li><a class="dropdown-item" href="#">Extend The Job Ad Link</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Chef Concierge <span class="badge badge-themeLight">M-124</span></td>
                                        <td>Management <span class="badge badge-themeLight">M-416</span></td>
                                        <td>Lorem ipsum <span class="badge badge-themeLight">M-415</span></td>
                                        <td>05</td>
                                        <td>103</td>
                                        <td>29/08/2024</td>
                                        <td>26/09/2024</td>
                                        <td>
                                            <div class="dropdown table-dropdown">
                                                <button class="btn eb-btn-ghost dropdown-toggle dots-link" type="button"
                                                    id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i class="fa-solid fa-ellipsis"></i>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                    <li><a class="dropdown-item" href="#">View Applicants</a></li>
                                                    <li><a class="dropdown-item" href="#">Extend The Job Ad Link</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Night Desk Clerk <span class="badge badge-themeLight">M-454</span></td>
                                        <td>Front Office <span class="badge badge-themeLight">M-845</span></td>
                                        <td>Lorem ipsum <span class="badge badge-themeLight">M-416</span></td>
                                        <td>03</td>
                                        <td>29</td>
                                        <td>29/08/2024</td>
                                        <td>26/09/2024</td>
                                        <td>
                                            <div class="dropdown table-dropdown">
                                                <button class="btn eb-btn-ghost dropdown-toggle dots-link" type="button"
                                                    id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i class="fa-solid fa-ellipsis"></i>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                    <li><a class="dropdown-item" href="#">View Applicants</a></li>
                                                    <li><a class="dropdown-item" href="#">Extend The Job Ad Link</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Guest Services Coordinator <span class="badge badge-themeLight">M-415</span>
                                        </td>
                                        <td>Housekeeping <span class="badge badge-themeLight">M-415</span></td>
                                        <td>Lorem ipsum <span class="badge badge-themeLight">M-515</span></td>
                                        <td>02</td>
                                        <td>56</td>
                                        <td>29/08/2024</td>
                                        <td>26/09/2024</td>
                                        <td>
                                            <div class="dropdown table-dropdown">
                                                <button class="btn eb-btn-ghost dropdown-toggle dots-link" type="button"
                                                    id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <i class="fa-solid fa-ellipsis"></i>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                                    <li><a class="dropdown-item" href="#">View Applicants</a></li>
                                                    <li><a class="dropdown-item" href="#">Extend The Job Ad Link</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>

                            </table>
                        </div>
                        <div class="grid-main d-none">
                            <div class="row g-4 mb-4">
                                <div class="col-xxl-cust5 col-lg-4  col-sm-6">
                                    <div class="vacanciesGrid-block">
                                        <div class="heading">
                                            <h6>Assistant Front Desk Manager</h6>
                                            <p>Code: 2653123</p>
                                        </div>
                                        <div class="bg">
                                            <div>
                                                <p>No. of Positions</p>
                                                <p>03</p>
                                            </div>
                                            <div>
                                                <p>Applicants</p>
                                                <p>58</p>
                                            </div>
                                        </div>
                                        <table>
                                            <tr>
                                                <th>Department:</th>
                                                <td>Management</td>
                                            </tr>
                                            <tr>
                                                <th>Department Code:</th>
                                                <td>5444512</td>
                                            </tr>
                                            <tr>
                                                <th>Section:</th>
                                                <td>Lorem Ipsum</td>
                                            </tr>
                                            <tr>
                                                <th>Section Code:</th>
                                                <td>84521</td>
                                            </tr>
                                            <tr>
                                                <th>Job Advertisement Posters:</th>
                                                <td><img src="assets/images/pdf.svg" alt="images"><img
                                                        src="assets/images/pdf.svg" alt="images"> </td>
                                            </tr>
                                            <tr>
                                                <th>Link Expiry Date:</th>
                                                <td>15/10/2024</td>
                                            </tr>
                                        </table>
                                        <div class="text-center"><a href="#" class="a-link">Extend The Job Ad Link</a></div>
                                        <div class="text-center"><a href="#" class="btn btn-themeSkyblue btn-sm">View
                                                Applicants</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xxl-cust5 col-lg-4  col-sm-6">
                                    <div class="vacanciesGrid-block">
                                        <div class="heading">
                                            <h6>Assistant Front Desk Manager</h6>
                                            <p>Code: 2653123</p>
                                        </div>
                                        <div class="bg">
                                            <div>
                                                <p>No. of Positions</p>
                                                <p>03</p>
                                            </div>
                                            <div>
                                                <p>Applicants</p>
                                                <p>58</p>
                                            </div>
                                        </div>
                                        <table>
                                            <tr>
                                                <th>Department:</th>
                                                <td>Management</td>
                                            </tr>
                                            <tr>
                                                <th>Department Code:</th>
                                                <td>5444512</td>
                                            </tr>
                                            <tr>
                                                <th>Section:</th>
                                                <td>Lorem Ipsum</td>
                                            </tr>
                                            <tr>
                                                <th>Section Code:</th>
                                                <td>84521</td>
                                            </tr>
                                            <tr>
                                                <th>Job Advertisement Posters:</th>
                                                <td><img src="assets/images/pdf.svg" alt="images"><img
                                                        src="assets/images/pdf.svg" alt="images"> </td>
                                            </tr>
                                            <tr>
                                                <th>Link Expiry Date:</th>
                                                <td>15/10/2024</td>
                                            </tr>
                                        </table>
                                        <div class="text-center"><a href="#" class="a-link">Extend The Job Ad Link</a></div>
                                        <div class="text-center"><a href="#" class="btn btn-themeSkyblue btn-sm">View
                                                Applicants</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xxl-cust5 col-lg-4  col-sm-6">
                                    <div class="vacanciesGrid-block">
                                        <div class="heading">
                                            <h6>Assistant Front Desk Manager</h6>
                                            <p>Code: 2653123</p>
                                        </div>
                                        <div class="bg">
                                            <div>
                                                <p>No. of Positions</p>
                                                <p>03</p>
                                            </div>
                                            <div>
                                                <p>Applicants</p>
                                                <p>58</p>
                                            </div>
                                        </div>
                                        <table>
                                            <tr>
                                                <th>Department:</th>
                                                <td>Management</td>
                                            </tr>
                                            <tr>
                                                <th>Department Code:</th>
                                                <td>5444512</td>
                                            </tr>
                                            <tr>
                                                <th>Section:</th>
                                                <td>Lorem Ipsum</td>
                                            </tr>
                                            <tr>
                                                <th>Section Code:</th>
                                                <td>84521</td>
                                            </tr>
                                            <tr>
                                                <th>Job Advertisement Posters:</th>
                                                <td><img src="assets/images/pdf.svg" alt="images"><img
                                                        src="assets/images/pdf.svg" alt="images"> </td>
                                            </tr>
                                            <tr>
                                                <th>Link Expiry Date:</th>
                                                <td>15/10/2024</td>
                                            </tr>
                                        </table>
                                        <div class="text-center"><a href="#" class="a-link">Extend The Job Ad Link</a></div>
                                        <div class="text-center"><a href="#" class="btn btn-themeSkyblue btn-sm">View
                                                Applicants</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xxl-cust5 col-lg-4  col-sm-6">
                                    <div class="vacanciesGrid-block">
                                        <div class="heading">
                                            <h6>Assistant Front Desk Manager</h6>
                                            <p>Code: 2653123</p>
                                        </div>
                                        <div class="bg">
                                            <div>
                                                <p>No. of Positions</p>
                                                <p>03</p>
                                            </div>
                                            <div>
                                                <p>Applicants</p>
                                                <p>58</p>
                                            </div>
                                        </div>
                                        <table>
                                            <tr>
                                                <th>Department:</th>
                                                <td>Management</td>
                                            </tr>
                                            <tr>
                                                <th>Department Code:</th>
                                                <td>5444512</td>
                                            </tr>
                                            <tr>
                                                <th>Section:</th>
                                                <td>Lorem Ipsum</td>
                                            </tr>
                                            <tr>
                                                <th>Section Code:</th>
                                                <td>84521</td>
                                            </tr>
                                            <tr>
                                                <th>Job Advertisement Posters:</th>
                                                <td><img src="assets/images/pdf.svg" alt="images"><img
                                                        src="assets/images/pdf.svg" alt="images"> </td>
                                            </tr>
                                            <tr>
                                                <th>Link Expiry Date:</th>
                                                <td>15/10/2024</td>
                                            </tr>
                                        </table>
                                        <div class="text-center"><a href="#" class="a-link">Extend The Job Ad Link</a></div>
                                        <div class="text-center"><a href="#" class="btn btn-themeSkyblue btn-sm">View
                                                Applicants</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xxl-cust5 col-lg-4  col-sm-6">
                                    <div class="vacanciesGrid-block">
                                        <div class="heading">
                                            <h6>Assistant Front Desk Manager</h6>
                                            <p>Code: 2653123</p>
                                        </div>
                                        <div class="bg">
                                            <div>
                                                <p>No. of Positions</p>
                                                <p>03</p>
                                            </div>
                                            <div>
                                                <p>Applicants</p>
                                                <p>58</p>
                                            </div>
                                        </div>
                                        <table>
                                            <tr>
                                                <th>Department:</th>
                                                <td>Management</td>
                                            </tr>
                                            <tr>
                                                <th>Department Code:</th>
                                                <td>5444512</td>
                                            </tr>
                                            <tr>
                                                <th>Section:</th>
                                                <td>Lorem Ipsum</td>
                                            </tr>
                                            <tr>
                                                <th>Section Code:</th>
                                                <td>84521</td>
                                            </tr>
                                            <tr>
                                                <th>Job Advertisement Posters:</th>
                                                <td><img src="assets/images/pdf.svg" alt="images"><img
                                                        src="assets/images/pdf.svg" alt="images"> </td>
                                            </tr>
                                            <tr>
                                                <th>Link Expiry Date:</th>
                                                <td>15/10/2024</td>
                                            </tr>
                                        </table>
                                        <div class="text-center"><a href="#" class="a-link">Extend The Job Ad Link</a></div>
                                        <div class="text-center"><a href="#" class="btn btn-themeSkyblue btn-sm">View
                                                Applicants</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xxl-cust5 col-lg-4  col-sm-6">
                                    <div class="vacanciesGrid-block">
                                        <div class="heading">
                                            <h6>Assistant Front Desk Manager</h6>
                                            <p>Code: 2653123</p>
                                        </div>
                                        <div class="bg">
                                            <div>
                                                <p>No. of Positions</p>
                                                <p>03</p>
                                            </div>
                                            <div>
                                                <p>Applicants</p>
                                                <p>58</p>
                                            </div>
                                        </div>
                                        <table>
                                            <tr>
                                                <th>Department:</th>
                                                <td>Management</td>
                                            </tr>
                                            <tr>
                                                <th>Department Code:</th>
                                                <td>5444512</td>
                                            </tr>
                                            <tr>
                                                <th>Section:</th>
                                                <td>Lorem Ipsum</td>
                                            </tr>
                                            <tr>
                                                <th>Section Code:</th>
                                                <td>84521</td>
                                            </tr>
                                            <tr>
                                                <th>Job Advertisement Posters:</th>
                                                <td><img src="assets/images/pdf.svg" alt="images"><img
                                                        src="assets/images/pdf.svg" alt="images"> </td>
                                            </tr>
                                            <tr>
                                                <th>Link Expiry Date:</th>
                                                <td>15/10/2024</td>
                                            </tr>
                                        </table>
                                        <div class="text-center"><a href="#" class="a-link">Extend The Job Ad Link</a></div>
                                        <div class="text-center"><a href="#" class="btn btn-themeSkyblue btn-sm">View
                                                Applicants</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xxl-cust5 col-lg-4  col-sm-6">
                                    <div class="vacanciesGrid-block">
                                        <div class="heading">
                                            <h6>Assistant Front Desk Manager</h6>
                                            <p>Code: 2653123</p>
                                        </div>
                                        <div class="bg">
                                            <div>
                                                <p>No. of Positions</p>
                                                <p>03</p>
                                            </div>
                                            <div>
                                                <p>Applicants</p>
                                                <p>58</p>
                                            </div>
                                        </div>
                                        <table>
                                            <tr>
                                                <th>Department:</th>
                                                <td>Management</td>
                                            </tr>
                                            <tr>
                                                <th>Department Code:</th>
                                                <td>5444512</td>
                                            </tr>
                                            <tr>
                                                <th>Section:</th>
                                                <td>Lorem Ipsum</td>
                                            </tr>
                                            <tr>
                                                <th>Section Code:</th>
                                                <td>84521</td>
                                            </tr>
                                            <tr>
                                                <th>Job Advertisement Posters:</th>
                                                <td><img src="assets/images/pdf.svg" alt="images"><img
                                                        src="assets/images/pdf.svg" alt="images"> </td>
                                            </tr>
                                            <tr>
                                                <th>Link Expiry Date:</th>
                                                <td>15/10/2024</td>
                                            </tr>
                                        </table>
                                        <div class="text-center"><a href="#" class="a-link">Extend The Job Ad Link</a></div>
                                        <div class="text-center"><a href="#" class="btn btn-themeSkyblue btn-sm">View
                                                Applicants</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xxl-cust5 col-lg-4  col-sm-6">
                                    <div class="vacanciesGrid-block">
                                        <div class="heading">
                                            <h6>Assistant Front Desk Manager</h6>
                                            <p>Code: 2653123</p>
                                        </div>
                                        <div class="bg">
                                            <div>
                                                <p>No. of Positions</p>
                                                <p>03</p>
                                            </div>
                                            <div>
                                                <p>Applicants</p>
                                                <p>58</p>
                                            </div>
                                        </div>
                                        <table>
                                            <tr>
                                                <th>Department:</th>
                                                <td>Management</td>
                                            </tr>
                                            <tr>
                                                <th>Department Code:</th>
                                                <td>5444512</td>
                                            </tr>
                                            <tr>
                                                <th>Section:</th>
                                                <td>Lorem Ipsum</td>
                                            </tr>
                                            <tr>
                                                <th>Section Code:</th>
                                                <td>84521</td>
                                            </tr>
                                            <tr>
                                                <th>Job Advertisement Posters:</th>
                                                <td><img src="assets/images/pdf.svg" alt="images"><img
                                                        src="assets/images/pdf.svg" alt="images"> </td>
                                            </tr>
                                            <tr>
                                                <th>Link Expiry Date:</th>
                                                <td>15/10/2024</td>
                                            </tr>
                                        </table>
                                        <div class="text-center"><a href="#" class="a-link">Extend The Job Ad Link</a></div>
                                        <div class="text-center"><a href="#" class="btn btn-themeSkyblue btn-sm">View
                                                Applicants</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xxl-cust5 col-lg-4  col-sm-6">
                                    <div class="vacanciesGrid-block">
                                        <div class="heading">
                                            <h6>Assistant Front Desk Manager</h6>
                                            <p>Code: 2653123</p>
                                        </div>
                                        <div class="bg">
                                            <div>
                                                <p>No. of Positions</p>
                                                <p>03</p>
                                            </div>
                                            <div>
                                                <p>Applicants</p>
                                                <p>58</p>
                                            </div>
                                        </div>
                                        <table>
                                            <tr>
                                                <th>Department:</th>
                                                <td>Management</td>
                                            </tr>
                                            <tr>
                                                <th>Department Code:</th>
                                                <td>5444512</td>
                                            </tr>
                                            <tr>
                                                <th>Section:</th>
                                                <td>Lorem Ipsum</td>
                                            </tr>
                                            <tr>
                                                <th>Section Code:</th>
                                                <td>84521</td>
                                            </tr>
                                            <tr>
                                                <th>Job Advertisement Posters:</th>
                                                <td><img src="assets/images/pdf.svg" alt="images"><img
                                                        src="assets/images/pdf.svg" alt="images"> </td>
                                            </tr>
                                            <tr>
                                                <th>Link Expiry Date:</th>
                                                <td>15/10/2024</td>
                                            </tr>
                                        </table>
                                        <div class="text-center"><a href="#" class="a-link">Extend The Job Ad Link</a></div>
                                        <div class="text-center"><a href="#" class="btn btn-themeSkyblue btn-sm">View
                                                Applicants</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xxl-cust5 col-lg-4  col-sm-6">
                                    <div class="vacanciesGrid-block">
                                        <div class="heading">
                                            <h6>Assistant Front Desk Manager</h6>
                                            <p>Code: 2653123</p>
                                        </div>
                                        <div class="bg">
                                            <div>
                                                <p>No. of Positions</p>
                                                <p>03</p>
                                            </div>
                                            <div>
                                                <p>Applicants</p>
                                                <p>58</p>
                                            </div>
                                        </div>
                                        <table>
                                            <tr>
                                                <th>Department:</th>
                                                <td>Management</td>
                                            </tr>
                                            <tr>
                                                <th>Department Code:</th>
                                                <td>5444512</td>
                                            </tr>
                                            <tr>
                                                <th>Section:</th>
                                                <td>Lorem Ipsum</td>
                                            </tr>
                                            <tr>
                                                <th>Section Code:</th>
                                                <td>84521</td>
                                            </tr>
                                            <tr>
                                                <th>Job Advertisement Posters:</th>
                                                <td><img src="assets/images/pdf.svg" alt="images"><img
                                                        src="assets/images/pdf.svg" alt="images"> </td>
                                            </tr>
                                            <tr>
                                                <th>Link Expiry Date:</th>
                                                <td>15/10/2024</td>
                                            </tr>
                                        </table>
                                        <div class="text-center"><a href="#" class="a-link">Extend The Job Ad Link</a></div>
                                        <div class="text-center"><a href="#" class="btn btn-themeSkyblue btn-sm">View
                                                Applicants</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <nav aria-label="Page navigation example">
                                <ul class="pagination justify-content-end">
                                    <li class="page-item "><a class="page-link" href="#"><i
                                                class="fa-solid fa-angle-left"></i></a></li>
                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                                    <li class="page-item"><a class="page-link" href="#"><i
                                                class="fa-solid fa-angle-right"></i></a></li>
                                </ul>
                            </nav>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    @include('resorts._emotional_buttons_v2_styles')
@endsection

    @section('import-css')
    @include('resorts._dropdown_styles')
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
            });

        </script>
    @include('resorts._dropdown_script')
    @endsection
