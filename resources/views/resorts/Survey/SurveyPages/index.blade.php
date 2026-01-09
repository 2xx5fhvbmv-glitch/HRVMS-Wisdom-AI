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
                            <span>Survey</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex justify-content-end">
                    
                            <a href="{{ route('Survey.create') }}" class="btn btn-theme me-2 @if(Common::checkRouteWisePermission('Survey.Surveylist',config('settings.resort_permissions.create')) == false) d-none @endif">Create Survey</a>
                   
               
                            <a href="{{ route('Survey.CompleteSurvey') }}" class="btn btn-themeLightNew me-2">Complete Survey</a>
                      
                            <a href="{{ route('Survey.DarftSurvey') }}" class="btn btn-themeLightNew">Draft Survey</a>
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
                                    <input type="search" class="form-control search" placeholder="Search" />
                                    <i class="fa-solid fa-search"></i>
                                </div>
                            </div>
                          
                          
                        </div>
                    </div>
                    <table id="" class="table table-viewJobDes w-100">
                        <thead>
                            <tr>
                                <th>Survey Name</th>
                                <th>No of Applicant Form</th>
                                <th>Privacy</th>
                                <th>Start Date</th>
                                <th>End Date</th>
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
    <div class="modal fade show" id="Surveyparticipant" tabindex="-1" aria-labelledby="exampleModalLabel" aria-modal="true" role="dialog" >
        <div class="modal-dialog modal-dialog-centered modal-small">
            <div class="modal-content">
 

                    <div class="modal-header">
                        <h5 class="modal-title" id="staticBackdropLabel">Survey Participant</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="employee-name-content">
                            <div class="row g-3 AppendinRow">

                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    </div>
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
            order:[[6, 'desc']],
            ajax: {
                url: '{{ route("Survey.Surveylist") }}',
                type: 'GET',
                data: function(d) 
                {
                    d.search = $('.search').val();
                }
            },
            columns: [
                { data: 'SurveyName', name: 'SurveyName', className: 'text-nowrap' },
                { data: 'NoOfApplicant', name: 'NoOfApplicant', className: 'text-nowrap' },
                { data: 'Privacy', name: 'Privacy', className: 'text-nowrap' },
                { data: 'StartDate', name: 'StartDate', className: 'text-nowrap' },
                { data: 'EndDate', name: 'EndDate', className: 'text-nowrap' },
                { data: 'Action', name: 'Action', orderable: false, searchable: false },
                {data:'created_at', visible:false,searchable:false},
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

                      
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching data:', error);
                    }
                });


                $('#jobDesEdit-modal').modal('show');

        });

        $(document).on('click','.delete-row-btn', function() {
            let id = $(this).data('id');
            let url = "{{ route('Survey.Destory', ':id') }}".replace(':id', id);
                Swal.fire({
                        title: 'Sure want to delete?',
                        text: 'This cannot be undone',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes',
                        cancelButtonText: 'No',
                        confirmButtonColor: "#DD6B55"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                type: "DELETE",
                                url: url,
                                dataType: "json",
                            }).done(function(result) {
                                if (result.success) {
                                    
                                    toastr.success(result.message, "Success", {
                                        positionClass: 'toast-bottom-right'
                                    });
                                    $('.table-viewJobDes').DataTable().ajax.reload();

                                } else {
                                    toastr.error(result.message, "Error", {
                                        positionClass: 'toast-bottom-right'
                                    });
                                }
                            }).fail(function(jqXHR) {
                                toastr.error(jqXHR.responseJSON?.message || "An unexpected error occurred.", "Error", {
                                    positionClass: 'toast-bottom-right'
                                });
                            });
                        }
                    });
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
            $('.table-viewJobDes').DataTable().ajax.reload();
        });

        $(document).on('click', '.showTotalapplicant', function() {
            var id = $(this).data('id');

            $("#Surveyparticipant").modal('show');
            $('.AppendinRow').html('No Record Found.     ');
            let url = "{{ route('Survey.TotalApplicant', ':id') }}";
            url = url.replace(':id', id);
            $.ajax({
                url: url,
                method: "GET",
                success: function(response) {
                    $('.AppendinRow').html(response);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching data:', error);       
                }
            });
        });

    </script>
    @endsection
