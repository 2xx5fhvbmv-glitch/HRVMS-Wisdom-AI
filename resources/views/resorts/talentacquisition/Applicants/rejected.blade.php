@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

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
                        <a href="javascript:history.back()" class="btn btn-themeLightNew"><i class="fa-solid fa-arrow-left me-1"></i>Back</a>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="row g-md-3 g-2 align-items-center">
                        <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8">
                            <div class="input-group">
                                <input type="search" class="form-control search" placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="list-main">
                    <div class="table-responsive">
                        <table class="table table-collapseNew table-rejected">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Nationality</th>
                                    <th>Email</th>
                                    <th>Rejection Date</th>
                                    <th>Rejection Reason</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="userApplicants-wrapper">
    </div>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function() {
        loadRejectedTable();

        $('.search').on('keyup', function() {
            loadRejectedTable();
        });

        const $userApplicantsWrapper = $(".userApplicants-wrapper");
        $(document).on("click", ".userApplicants-btn", function (e) {
            e.stopPropagation();
            let id = $(this).data("id");
            let url = "{{ route('resort.ta.TaUserApplicantsSideBar', ':id') }}";
            url = url.replace(':id', id);
            $.ajax({
                url: url,
                type: "GET",
                success: function(response) {
                    if (response.success) {
                        $(".userApplicants-wrapper").html(response.view);
                    }
                },
                error: function() {
                    toastr.error('Something went wrong.', { positionClass: 'toast-bottom-right' });
                }
            });
            $userApplicantsWrapper.toggleClass("end-0");
        });

        $(document).on("click", ".closeSlider", function (e) {
            e.preventDefault();
            $userApplicantsWrapper.toggleClass("end-0");
        });

        $(document).on("click", "#document-dismiss", function () {
            $("#bdVisa-iframeModel-modal-lg").modal('hide');
            $("#ViewModeOfFiles").empty();
            $(".downloadLink").attr("href", "");
            $(".userApplicants-wrapper").addClass("end-0");
        });

        $(document).on("click", ".DownloadFile", function () {
            let fileId = $(this).data("id");
            let fileFlag = $(this).data("flag");
            $.ajax({
                url: "{{ route('resort.ta.DownloadFile') }}",
                type: "POST",
                data: { id: fileId, flag: fileFlag },
                success: function(response) {
                    if (response.success) {
                        $("#ViewModeOfFiles").html('<div class="text-center"><p>Loading...</p></div>');
                        $("#bdVisa-iframeModel-modal-lg").modal('show');
                        let fileUrl = response.NewURLshow;
                        $(".downloadLink").attr("href", fileUrl);
                        let mimeType = response.mimeType.toLowerCase();
                        let imageTypes = ['image/jpeg', 'image/png', 'image/gif'];
                        let iframeTypes = ['video/mp4', 'application/pdf', 'text/plain'];
                        if (imageTypes.includes(mimeType)) {
                            $("#ViewModeOfFiles").html('<img src="'+fileUrl+'" class="popupimgFileModule" alt="Preview">');
                        } else if (iframeTypes.includes(mimeType)) {
                            $("#ViewModeOfFiles").html('<iframe style="width:100%;height:100%;" src="'+fileUrl+'" allowfullscreen></iframe>');
                        } else {
                            $("#bdVisa-iframeModel-modal-lg").modal('hide');
                            window.location.href = fileUrl;
                        }
                    }
                }
            });
        });
    });

    function loadRejectedTable() {
        if ($.fn.DataTable.isDataTable('.table-rejected')) {
            $('.table-rejected').DataTable().destroy();
        }
        $('.table-rejected').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 10,
            processing: true,
            serverSide: true,
            order: [[5, 'desc']],
            ajax: {
                url: "{{ route('resort.ta.GetRejectedApplicants') }}",
                type: 'GET',
                data: function(d) {
                    d.searchTerm = $('.search').val();
                }
            },
            columns: [
                { data: 'first_name', name: 'first_name', render: function(data, type, row) {
                    return '<div class="tableUser-block"><div class="img-circle"><img src="'+row.profileImg+'" alt="user"></div><span class="userApplicants-btn" data-id="'+row.applicant_id+'">'+row.name+'</span></div>';
                }},
                { data: 'position_title', name: 'position_title' },
                { data: 'department', name: 'department' },
                { data: 'countryName', name: 'countryName' },
                { data: 'email', name: 'email' },
                { data: 'rejection_date', name: 'rejection_date' },
                { data: 'rejection_reason', name: 'rejection_reason', render: function(data) {
                    return data ? '<span class="text-truncate d-inline-block" style="max-width:200px;" title="'+data+'">'+data+'</span>' : '-';
                }},
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            drawCallback: function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });
    }
</script>
@endsection
