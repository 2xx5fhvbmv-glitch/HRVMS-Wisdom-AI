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
            <div class="page-hedding page-appHedding">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Talent Acquisition</span>
                            <h1>Applicants</h1>
                        </div>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="#" class="btn btn-themeLightNew">Shortlisted Applicants</a>
                    </div>
                    <div class="col-auto">
                        <a href="#" class="btn btn-themeLightNew">Upcoming Interviews</a>
                    </div>
                    <div class="col-auto">
                        <a href="#" class="btn btn-themeLightNew">Rejected Applications</a>
                    </div>
                    <div class="col-auto">
                        <a href="#" class="btn btn-themeLightNew">Review Reminders</a>
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
                            <a href="#" class="btn btn-grid"><img src="{{URL::asset('resorts_assets/images/grid.svg')}}" alt="icon"></a>
                            <a href="#" class="btn btn-list active"><img src="{{ URL::asset('resorts_assets/images/list.svg')}}" alt="icon"></a>
                        </div>
                    </div>
                </div>
                <div class="list-main ">
                    <div class="table-responsive">
                        <table class="table table-collapseNew table-applicants">
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

                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="grid-main d-none  ">
                    <div class="row g-md-4 g-3 mb-4">

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
    <input type="text" id="vacancy-id" value="{{ $id }}">

    <div class="modal fade" id="ApplicantsNotes-Model" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="staticBackdropLabel">Note</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="ApplicantNoteForm">
                        @csrf
                        <div class="modal-body">
                            <textarea class="form-control" rows="7"  id="ApplicantNote" name="ApplicantNote" placeholder="Note.."></textarea>
                        </div>
                        <input type="hidden" id="Applicant_id" name="Applicant_id">

                        <div class="modal-footer justify-content-center">
                            <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                            <button type="submit"  class="btn btn-themeBlue">Submit</button>
                        </div>
                    </form>
                </div>

        </div>
    </div>


    {{-- Applicant side bar --}}
    <div class="userApplicants-wrapper ">
        <div class="userApplicants-title">
            <div class="d-flex">
                <div class="progress-container skyblue" data-progress="20">
                    <svg class="progress-circle" viewBox="0 0 120 120">
                        <circle class="progress-background" cx="60" cy="60" r="54"></circle>
                        <circle class="progress" cx="60" cy="60" r="54"></circle>
                    </svg>
                    <div class="img-obj cover"> <img src="assets/images/user-2.svg" alt="image"></div>
                </div>
                <div>
                    <h4>John Doe <span class="badge badge-themeSkyblue">Shortlisted by Wisdom AI</span></h4>
                    <p>Assistant Front Desk Manager</p>
                </div>
            </div>
            <div>
                <a href="#" class="btn btn-themeBlue btn-sm">Send Message</a>
                <a href="#" class="btn btn-themeSkyblue btn-sm">Send Email</a>
            </div>
        </div>
        <div class="userApplicants-body">
            <div class="tab-userApplicants">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab1" data-bs-toggle="tab" data-bs-target="#tabPane1"
                            type="button" role="tab" aria-controls="tabPane1" aria-selected="true">DETAILS</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="#tab2" data-bs-toggle="tab" data-bs-target="#tabPane2"
                            type="button" role="tab" aria-controls="tabPane2" aria-selected="false">DOCUMENTS</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab3" data-bs-toggle="tab" data-bs-target="#tabPane3" type="button"
                            role="tab" aria-controls="tabPane3" aria-selected="false">PROGRESS</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab4" data-bs-toggle="tab" data-bs-target="#tabPane4" type="button"
                            role="tab" aria-controls="tabPane4" aria-selected="false">INTERVIEW</button>
                    </li>
                </ul>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="tabPane1" role="tabpanel" aria-labelledby="tab1"
                        tabindex="0">
                        <div class="table-responsive">
                            <table class="table table-lable mb-0">
                                <tbody>
                                    <tr>
                                        <th>Name:</th>
                                        <td>John Doe</td>
                                    </tr>
                                    <tr>
                                        <th>Email:</th>
                                        <td>John@gmail.com</td>
                                    </tr>
                                    <tr>
                                        <th>Phone:</th>
                                        <td>+91 012 545 1256</td>
                                    </tr>
                                    <tr>
                                        <th>Nationality:</th>
                                        <td>Indian</td>
                                    </tr>
                                    <tr>
                                        <th>DOB:</th>
                                        <td>3rd July 1992 | Age: 32 years</td>
                                    </tr>
                                    <tr>
                                        <th>Experience:</th>
                                        <td>4 year</td>
                                    </tr>
                                    <tr>
                                        <th>Passport No.:</th>
                                        <td>8454151565</td>
                                    </tr>
                                    <tr>
                                        <th>Applied Date:</th>
                                        <td>15/08/2024</td>
                                    </tr>
                                    <tr>
                                        <th>Education:</th>
                                        <td>Graduate</td>
                                    </tr>
                                    <tr>
                                        <th>Current Address:</th>
                                        <td>B 154 Lorem ipsum, Lorem ipsum text, Lorem ipsum 56214</td>
                                    </tr>
                                    <tr>
                                        <th>Permanent Address:</th>
                                        <td>B 154 Lorem ipsum, Lorem ipsum text, Lorem ipsum 56214</td>
                                    </tr>
                                    <tr>
                                        <th>Current Employment Status:</th>
                                        <td>Available</td>
                                    </tr>
                                    <tr>
                                        <th>Notice Period:</th>
                                        <td>30 days</td>
                                    </tr>
                                    <tr>
                                        <th>Salary Expectation:</th>
                                        <td>$1000</td>
                                    </tr>
                                    <tr>
                                        <th>Languages Spoken:</th>
                                        <td>English,Hindi</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tabPane2" role="tabpanel" aria-labelledby="#tab2" tabindex="0">
                        <div class="table-responsive">
                            <table class="table table-lable table-docUserApp">
                                <tbody>
                                    <tr>
                                        <th>Curriculum Vitae</th>
                                        <td><a href="#" class="a-link">Download</a></td>
                                    </tr>
                                    <tr>
                                        <th>Experience Letters</th>
                                        <td><a href="#" class="a-link">Download</a></td>
                                    </tr>
                                    <tr>
                                        <th>Experience Letters</th>
                                        <td><a href="#" class="a-link">Download</a></td>
                                    </tr>
                                    <tr>
                                        <th>Certificates</th>
                                        <td><a href="#" class="a-link">Download</a></td>
                                    </tr>
                                    <tr>
                                        <th>Other supporting documents</th>
                                        <td><a href="#" class="a-link">Download</a></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center"><a href="#" class="a-link">Download All</a></div>
                    </div>
                    <div class="tab-pane fade" id="tabPane3" role="tabpanel" aria-labelledby="tab3" tabindex="0">
                        <div class="overflow-hidden">
                            <div class="row g-lg-4 g-3 mb-lg-4 mb-3">
                                <div class="col-lg-8 col-md-7">
                                    <div class="userApplicants-accordion" id="accordionExample">
                                        <div class="accordion-item active">
                                            <h2 class="accordion-header" id="headingOne">
                                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#collapseOne" aria-expanded="true"
                                                    aria-controls="collapseOne">
                                                    Shortlisted by Wisdom AI
                                                </button>
                                            </h2>
                                            <div id="collapseOne" class="accordion-collapse collapse show"
                                                aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                                <div class="accordion-body">
                                                    <ol>
                                                        <li>HR Round</li>
                                                        <li>HOD Round</li>
                                                        <li>GM Round</li>
                                                    </ol>
                                                    <a href="#" class="btn btn-themeSkyblue btn-sm">Complete HR
                                                        Round</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion-item active">
                                            <h2 class="accordion-header" id="headingTwo">
                                                <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#collapseTwo"
                                                    aria-expanded="false" aria-controls="collapseTwo">
                                                    HR Shortlisted
                                                </button>
                                            </h2>
                                            <div id="collapseTwo" class="accordion-collapse collapse"
                                                aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                                <div class="accordion-body">
                                                    Lorem ipsum dolor sit amet consectetur adipisicing elit. Illum porro
                                                    totam incidunt harum perspiciatis, veniam quo placeat. Rerum,
                                                    reprehenderit voluptas?
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingThree">
                                                <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#collapseThree"
                                                    aria-expanded="false" aria-controls="collapseThree">
                                                    Interview
                                                </button>
                                            </h2>
                                            <div id="collapseThree" class="accordion-collapse collapse"
                                                aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                                                <div class="accordion-body">
                                                    <ol>
                                                        <li>HR Round</li>
                                                        <li class="disabled">HOD Round</li>
                                                        <li class="disabled">GM Round</li>
                                                    </ol>
                                                    <a href="#" class="btn btn-themeSkyblue btn-sm">Complete HR
                                                        Round</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingFour">
                                                <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#collapseFour"
                                                    aria-expanded="false" aria-controls="collapseFour">
                                                    Selected
                                                </button>
                                            </h2>
                                            <div id="collapseFour" class="accordion-collapse collapse"
                                                aria-labelledby="headingFour" data-bs-parent="#accordionExample">
                                                <div class="accordion-body">
                                                    Lorem ipsum dolor sit amet consectetur adipisicing elit. Repellendus
                                                    molestiae perspiciatis doloribus porro, recusandae ipsum totam
                                                    dignissimos incidunt vero harum et omnis autem quaerat consequatur a
                                                    voluptates quam reiciendis odit.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-5">
                                    <div class="card bg userApplicants-bg">
                                        <div class="card-title">
                                            <h3>Add Notes</h3>
                                        </div>
                                        <div class="textarea-icon mt-2 mb-3">
                                            <textarea rows="9" class="form-control" placeholder="Type Here"></textarea>
                                            <img src="assets/images/textarea-icon.svg" alt="icon">
                                        </div>
                                        <div><a href="#" class="btn btn-themeSkyblue btn-sm">Submit</a></div>
                                    </div>
                                </div>
                            </div>
                            <a href="#" class="a-link">View Interview Assessment Form</a>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tabPane4" role="tabpanel" aria-labelledby="tabPane4" tabindex="0">
                        <div class="table-responsive">
                            <table class="table table-lable table-intUserApp">
                                <tbody>
                                    <tr>
                                        <th>Position:</th>
                                        <td>Assistant Front Desk Manager</td>
                                    </tr>
                                    <tr>
                                        <th>Department:</th>
                                        <td>Management</td>
                                    </tr>
                                    <tr>
                                        <th>Interview Date & Time:</th>
                                        <td>05/09/2024 - 10:00am</td>
                                    </tr>
                                    <tr>
                                        <th>interview link:</th>
                                        <td><a href="#" class="a-link">https://loremipsum.com</a></td>
                                    </tr>
                                    <tr>
                                        <th>Interviewer - HR Round:</th>
                                        <td>05/09/2024 - 10:00am
                                            <div class="tableUser-block mt-2">
                                                <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                                                </div>
                                                <span class="userApplicants-btn">John Doe</span>
                                                <div class="ms-2"><img src="assets/images/thumbs-up.svg" alt="icon">
                                                </div>
                                                <div class="ms-auto">
                                                    <a href="#" class="a-link userAppInt-vCommBtn">View Comments</a>
                                                    <a href="#" class="a-link userAppInt-hCommBtn d-none">Hide
                                                        Comments</a>
                                                </div>
                                            </div>
                                            <div class="userAppInt-commBlock">Lorem Ipsum is simply dummy text of
                                                the
                                                printing and
                                                typesetting industry. Lorem Ipsum has been the industry's standard
                                                dummy
                                                text ever since the 1500s, when an unknown printer took a galley of
                                                type
                                                and scrambled it to make a type specimen book.</div>

                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Interviewer - HOD Round:</th>
                                        <td>06/09/2024 - 12:00pm
                                            <div class="tableUser-block mt-2">
                                                <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                                                </div>
                                                <span class="userApplicants-btn">Christian Slatter</span>
                                                <div class="ms-2"><img src="assets/images/thumbs-down.svg" alt="icon">
                                                </div>
                                                <div class="ms-auto">
                                                    <a href="#" class="a-link userAppInt-vCommBtn">View Comments</a>
                                                    <a href="#" class="a-link userAppInt-hCommBtn d-none">Hide
                                                        Comments</a>
                                                </div>
                                            </div>
                                            <div class="userAppInt-commBlock">Lorem Ipsum is simply dummy text of
                                                the
                                                printing and
                                                typesetting industry. Lorem Ipsum has been the industry's standard
                                                dummy
                                                text ever since the 1500s, when an unknown printer took a galley of
                                                type
                                                and scrambled it to make a type specimen book.</div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Interviewer - GM Round:</th>
                                        <td>
                                            <div class="tableUser-block mb-2">
                                                <div class="img-circle"><img src="assets/images/user-2.svg" alt="user">
                                                </div>
                                                <span class="userApplicants-btn">Amayra Wilsom</span>
                                            </div>
                                            <div class="mb-2"><span class="text-medium">Scheduled Interview:</span>
                                                08/09/2024 -
                                                9:00pm</div>
                                            <div class="mb-2"> <a href="#" class="a-link">https://loremipsum.com</a>
                                            </div>
                                            <a href="#" class="btn btn-themeSkyblue btn-sm">Join The Interview</a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="intUserApp-block">
                            <h6>Analyze Of AI:</h6>
                            <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum
                                has been the industry's standard dummy text ever since the 1500s, when an unknown
                                printer took a galley of type and scrambled it to make a type specimen book.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- End of Aplicant side bar --}}
    @endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>

$(document).ready(function() {


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
    // Grid and table view toggle close
$('.table-applicants tbody').empty();
    var divisionTable = $('.table-applicants').DataTable({
        searching: false,
        bLengthChange: false,
        bFilter: true,
        bInfo: true,
        bAutoWidth: false,
        scrollX: true,
        iDisplayLength: 6,
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('resort.ta.getApplicant')}}",
            type: 'GET',
            data: function(d) {
                d.vacanccyId = $("#vacancy-id").val();
            }
        },
        columns: [
            { data: 'first_name', name: 'first_name', render: function (data, type, row) {
                return `<div class="tableUser-block">
                    <div class="img-circle"><img src="${row.profileImg}" alt="user"></div>
                    <span class="userApplicants-btn">${row.name}</span>
                </div>`;
            }},
            { data: 'countryName', name: 'countryName' },
            { data: 'passport_no', name: 'passport_no' },
            { data: 'total_work_exp', name: 'total_work_exp', render: function (data) {
                return `${data} year`;
            }},
            { data: 'email', name: 'email' },
            { data: 'contact', name: 'contact' },
            { data: 'Application_date', name: 'Application_date' },
            { data: 'status', name: 'status', render: function (data) {
                return `<span class="badge badge-themeSkyblue">${data}</span>`;
            }},
            {
            data: 'details-control',
                name: 'details-control',
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return `
                <a class="a-link details-toggle" data-row-id="${row.id}"  data-status="${row.status}"  data-applicant_id="${row.applicant_id}" aria-expanded="false">
                    Interview Details
                </a>`;
                }
            },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]

    });
    $('.table-applicants tbody').on('click', 'a.details-toggle', function () {
            const $link = $(this);
            const rowId = $link.attr('data-row-id');
            const status = $link.attr('data-status');
            const applicant_id = $link.attr('data-applicant_id');
            const $currentRow = $link.closest('tr');

            if ($(`#detailsRow${rowId}`).length )
            {
                $(`#detailsRow${rowId}`).toggle();
            }
            else
            {
                if( status =="Sortlisted By Wisdom AI")
                {
                    let url = "{{ route('resort.ta.ApplicantWiseStatus', ':id') }}";

                    url = url.replace(':id',rowId);
                    $.ajax({
                        url: url,
                        type: "GET",
                        success: function(response)
                        {
                                if (response.success)
                                {
                                    toastr.success(response.message, "Success",
                                            {
                                                positionClass: 'toast-bottom-right'
                                            });
                                }
                        },
                            error: function(response) {
                                var errors = response.responseJSON;
                                var errs = '';
                                console.log(errors.errors);
                                $.each(errors.errors, function(key, error)
                                {
                                    console.log(error);
                                    errs += error + '<br>';
                                });
                                toastr.error(errs, { positionClass: 'toast-bottom-right'});
                            }
                    });

                    const newRow =`
                        <tr id="detailsRow${rowId}" class="details-row">
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
                                            <td>${rowId === '1' ? 'Example Template' : 'N/A'}</td>
                                            <td>${rowId === '1' ? 'HR Round' : 'N/A'}</td>
                                            <td>${rowId === '1' ? 'Introductory' : 'N/A'}</td>
                                            <td>${rowId === '1' ? 'HR Manager' : 'N/A'}</td>
                                            <td><span class="badge badge-info">${rowId === '1' ? 'Slot Booked' : 'N/A'}</span></td>
                                            <td>
                                                <a href="#shareMeetLink-modal" data-bs-toggle="modal" class="btn btn-themeSkyblue btn-small">
                                                    Share Meeting Link
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>`;

                    $currentRow.after(newRow);
                }
            }
    });

    $(document).on("click",".ApplicantsNotes",function(suc){

        let Applicantid =  $(this).data('id');
        let notes = $(this).data('notes');

        $("#ApplicantNote").val(atob(notes));
       $("#ApplicantsNotes-Model").modal('show');
       $("#Applicant_id").val(Applicantid);
    });
    $('#ApplicantNoteForm').validate({
        rules: {
            ApplicantNote: {
                required: true,
            }
        },
        messages :
        {
            ApplicantNote: {
                required: "Please Write Something.",
            }
        },
        submitHandler: function(form) {

            var formData = new FormData(form);
            $.ajax({
                url: "{{ route('resort.ta.ApplicantNote') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#respond-rejectModal').modal('hide');
                    if (response.success)
                    {
                        $("#FreshHiringRequest").html(response.view);

                        toastr.success(response.message, "Success",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                        $("#ApplicantsNotes-Model").modal('hide');
                    }

               },
                error: function(response) {
                    var errors = response.responseJSON;
                    var errs = '';
                    console.log(errors.errors);
                    $.each(errors.errors, function(key, error)
                    {
                        console.log(error);
                        errs += error + '<br>';
                    });
                    toastr.error(errs, { positionClass: 'toast-bottom-right'});
                }
            });
        }
    });

});
</script>
@endsection

