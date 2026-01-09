@extends('resorts.layouts.app')
@section('page_tab_title' ,"Telent Acquisition ")

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
                        {{-- <div class="d-flex justify-content-end">
                            <a href="#" class="btn btn-theme">Request Manning</a>
                        </div> --}}
                    </div>
                </div>
            </div>

            <div>
                <div class="card">
                    <div class="card-header">
                        <div class="row g-md-3 g-2 align-items-center">
                            <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                                <div class="input-group">
                                    <input type="search" class="form-control search" placeholder="Search" />
                                    <i class="fa-solid fa-search"></i>
                                </div>
                            </div>
                            <div class="col-xl-2 col-md-3 col-sm-4 col-6">
                                <select class="form-select" id="complianceSelect">
                                    <option value="Approved" selected>Compliance Passed</option>
                                    <option value="Rejected">Compliance  Rejeted</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <table id="" class="table table-viewJobDes w-100">
                        <thead>
                            <tr>
                                <th>Division</th>
                                <th>Department</th>
                                <th>Positions</th>
                                <th>Section</th>
                                <th>Job Description</th>
                                <th>Compliance</th>
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

    <div class="modal fade " id="jobDesEdit-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <form id="jobDesEditForm">

                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title" id="staticBackdropLabel">Edit Job Description </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="MainId" id="FormResortMainId">
                        <textarea class="form-control cke_notifications_area" rows="7" name="jobdescription" id="editor">

                        </textarea>

                    </div>
                    <div class="modal-footer">
                        {{-- <a href="#" class="btn btn-themeBlue">Save As Draft</a>
                        <a href="#" class="a-link">View</a> --}}
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                        <button type="submit" class="btn btn-themeBlue">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Show the job description --}}
    <div class="modal fade " id="ViewJobDescription-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">




                    <div class="modal-header">
                        <h5 class="modal-title" id="staticBackdropLabel">Show Job Description </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <textarea class="form-control" readonly rows="15" name="ShowJobDescription" id="ShowJobDescription">

                        </textarea>

                    </div>
                    <div class="modal-footer">
                        <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endsection

    @section('import-css')
    @endsection

    @section('import-scripts')

    <script type="text/javascript">
    $(document).ready(function() {

        $('.table-viewJobDes tbody').empty();
        var viewJobDes = $('.table-viewJobDes').DataTable({
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
                url: '{{ route("resort.ta.jobdescription.GetJobDescList") }}',
                type: 'GET',
                data: function(d) {
                var complianceStatus = $('#complianceSelect').val();
                d.compliance_status = complianceStatus;

                var searchTerm = $('.search').val();
                d.searchTerm = searchTerm;
            }
            },
            columns: [
                { data: 'Division', name: 'Division', className: 'text-nowrap' },
                { data: 'Department', name: 'Department', className: 'text-nowrap' },
                { data: 'Position', name: 'Position', className: 'text-nowrap' },
                { data: 'Section', name: 'Section', className: 'text-nowrap' },
                { data: 'JobDescription', name: 'JobDescription', className: 'text-nowrap' },
                { data: 'Compliance', name: 'Compliance', className: 'text-nowrap' },

                { data: 'action', name: 'Action', orderable: false, searchable: false }
            ]
        });


        $('#jobDesEdit-modal').on('shown.bs.modal', function () {
            if (CKEDITOR.instances['editor']) {
                CKEDITOR.instances['editor'].destroy(); // Destroy existing CKEditor instance if it exists
            }
            CKEDITOR.replace('editor'); // Re-initialize CKEditor
        });

        $(document).on('click', '.edit-row-btn', function() {
            let id =$(this).data('id');

            $("#FormResortMainId").val(id);
            let url = "{{ route('resort.ta.jobdescription.edit', ':id') }}";
            url = url.replace(':id', id);
                $.ajax({
                    url: url,
                    method: "GET",
                    success: function(response) {

                        $('#jobDesEdit-modal').on('shown.bs.modal', function () {
                            // Check if CKEditor is already initialized
                            if (CKEDITOR.instances['editor']) {
                                // If CKEditor is already initialized, clear the content
                                CKEDITOR.instances['editor'].setData(''); // Clear CKEditor content
                            } else {
                                // Initialize CKEditor if it's not initialized
                                CKEDITOR.replace('editor');
                            }

                            CKEDITOR.instances['editor'].setData(response.data);
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching data:', error);
                    }
                });


                $('#jobDesEdit-modal').modal('show');

        });

        $(document).on('click','.delete-row-btn', function() {
            let id =$(this).data('id');

            let url = "{{ route('resort.ta.jobdescription.destroy', ':id') }}";
            url = url.replace(':id', id);
                $.ajax({
                    url: url,
                    method: "delete",
                    success: function(response) {
                        if (response.success) {


                            toastr.success(response.msg, "Success", {
                                positionClass: 'toast-bottom-right'
                            });

                            $('.table-viewJobDes').DataTable().ajax.reload();


                        } else {
                            toastr.error(response.msg, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching data:', error);
                    }
                });




        });
        // View the job description

        $(document).on('click', '.viewJobDesc', function() {
            let id =$(this).data('id');


            $('#ViewJobDescription-modal').modal('show');
            let url = "{{ route('resort.ta.jobdescription.show', ':id') }}";
            url = url.replace(':id', id);
                $.ajax({
                    url: url,
                    method: "GET",
                    success: function(response) {

                        $("#ShowJobDescription").val(response.data);


                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching data:', error);
                    }
                });




        });


        $('#jobDesEditForm').validate({
                rules: {

                    jobdesription:{
                        required: true,
                    }

                },

                messages: {

                    jobdesription:{
                        required: "Please Enter Job desription.",
                    }

                },
                submitHandler: function(form)
                {
                    const content = CKEDITOR.instances['editor'].getData(); // Use CKEDITOR to get content


                    let id =  $("#FormResortMainId").val();
                    let url = "{{ route('resort.ta.jobdescription.update', ':id') }}";
                    url = url.replace(':id', id);

                    $.ajax({
                        url: url,
                        type: "put",
                        data: {
                            "id": id,
                            "jobdescription": content,
                            "_token": "{{ csrf_token() }}"
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                                // Close modal after success
                                $('.table-viewJobDes').DataTable().ajax.reload();
                                $('#jobDesEdit-modal').modal('hide');
                            }
                        },
                        error: function(xhr, status, error) {
                            let errs = '';

                            if (xhr.status === 422) {
                                let response = xhr.responseJSON;
                                if (response.errors) {
                                    $.each(response.errors, function(key, value) {
                                        errs += value[0] + '<br>';
                                    });
                                }
                            } else {
                                errs = 'An unexpected error occurred. Please try again.';
                            }

                            toastr.error(errs, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        },
                        complete: function() {  // Changed from afterComplete to complete
                            $("#jobDesEdit-modal form")[0].reset();
                            $("#jobDesEdit-modal input[type='hidden']").val('');
                            // If you're using any rich text editor, reset it here
                            if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['editor']) {
                                CKEDITOR.instances['editor'].setData('');
                            }
                        }
                    });



                }
        });


        $('#complianceSelect').change(function() {
            viewJobDes.ajax.reload();
        });
        $('.search').on('keyup', function() {
            viewJobDes.ajax.reload();
        });

    });
    </script>
    @endsection
