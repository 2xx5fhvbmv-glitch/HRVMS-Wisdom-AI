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
                    {{-- <div class="col-auto ms-auto">
                        <a href="{{ route('resort.ta.shortlistedapplicants') }}" class="btn btn-themeLightNew">Shortlisted Applicants To share Link</a>
                    </div> --}}
                    <!-- <div class="col-auto">
                        <div class="d-flex justify-content-end">
                            <a href="#" class="btn btn-theme">Request Manning</a>
                        </div>
                    </div> -->
                </div>
            </div>


            <div class="card">
                <div class="card-title">
                    <h3>All To Do List</h3>
                </div>
                <table class="table"  id="SortlistedApplicants" >
                    <thead>
                        <tr>
                            <th>Applicants</th>
                            <th>Position</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Applied Date</th>
                            <th>Stage</th>
                            <th>Nationality	</th>
                            <th>InterViewDate</th>
                            <th>Malidivan Time</th>
                            <th>Applicant Time</th>
                            <th>Interview Status</th>
                            <th>Link</th>
                        </tr>
                    </thead>


                </table>

            </div>


        </div>
    </div>

</div>
    <div class="modal fade" id="sendRequest-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Send Interview Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="InterviewRequestSentForm">
                    @csrf
                    <div class="modal-body">
                        <label class="form-label mb-8">Select date</label>
                        <div class="modalCalendar-block">
                            <div id="calendarModalSendInterView"></div>


                            <input type="date" class="InterviewDateModel"  id="InterviewDate" name="InterviewDate">

                        </div>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-theme">Submit</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <div class="modal fade" id="TimeSlots-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-small modal-timeSlotsModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Send Interview Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="TimeSlotsForm">
                    @csrf
                    <div class="modal-body">
                        <label class="form-label mb-sm-4 mb-3">SELECT TIME SLOTS</label>
                        <div class="sendRequestTime-main">
                        </div>
                        <input type="text" id="Resort_id" name="Resort_id">
                        <input type="text" id="ApplicantID" name="ApplicantID">
                        <input type="text" id="ApplicantStatus_id" name="ApplicantStatus_id">
                        <input type="text" id="Calender_ta_id" name="ta_id">
                        <input type="date"  id="TimeSlotsFormdate" name="TimeSlotsFormdate">

                    </div>
                    <div class="modal-footer justify-content-center">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-theme">Submit</button>

                    </div>
                </form>

            </div>
        </div>
    </div>


    <div class="modal fade" id="sendRequestFinal-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Review Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pb-0">
                    <div class="table-responsive">
                        <table class="table table-sendRequestFinal w-100">
                            <tbody id="Final_response_data">

                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <a href="javascript:void(0)"  data-bs-dismiss="modal"class="btn btn-theme" >Submit</a>
                </div>

            </div>
        </div>
    </div>
    <div class="modal fade" id="shareMeetLink-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Share Interview Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id='shareMeetLinkForm'>
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="" class="form-label ">PLEASE PROVIDE THE MEETING LINK FOR INTERVIEW</label>
                            <input type="text" class="form-control" name="MeetingLink" placeholder="Meeting Link">
                        </div>
                        <div style="height:180px;"></div>
                        <input type="hidden" name="Interview_id" id="Interview_id">
                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-themeBlue">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endsection

@section('import-css')

@endsection

@section('import-scripts')
<script>

$(document).ready(function() {


    $('#SortlistedApplicants tbody').empty();
        var SortlistedApplicants = $('#SortlistedApplicants').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 6,
            processing: true,
            serverSide: true,
            order:[[12, 'desc']],
            ajax: {
                url: '{{ route("resort.ta.UpcomingApplicants") }}',
                type: 'GET',
            },
            columns: [

                { data: 'Applicants', name: 'Applicants', className: 'text-nowrap' },
                { data: 'Position', name: 'Position', className: 'text-nowrap' },
                { data: 'Email', name: 'Email', className: 'text-nowrap' },
                { data: 'Contact', name: 'Contact', className: 'text-nowrap' },
                { data: 'AppliedDate', name: 'AppliedDate', className: 'text-nowrap' },
                { data: 'Stage', name: 'Stage', className: 'text-nowrap' },
                { data: 'Nationality', name: 'Nationality', className: 'text-nowrap' },
                { data: 'InterViewDate', name: 'InterViewDate', className: 'text-nowrap' },
                { data: 'MalidivanTime', name: 'MalidivanTime', className: 'text-nowrap' },
                { data: 'ApplicantTime', name: 'ApplicantTime', className: 'text-nowrap' },
                { data: 'InterviewStatus', name: 'InterviewStatus', className: 'text-nowrap' },
                { data: 'Link', name: 'Link', className: 'text-nowrap' },
                {data:'created_at', visible:false,searchable:false},

            ]
    });
});
</script>
@endsection

