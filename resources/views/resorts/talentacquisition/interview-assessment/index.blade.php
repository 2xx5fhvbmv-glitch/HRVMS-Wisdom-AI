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
                                <select name="positions" id="positions" class="select2t-none">
                                    <option value="">Select Positions</option>
                                    @if($positions)
                                        @foreach($positions as $pos)
                                            <option value="{{$pos->id}}">{{$pos->position_title}}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <table id="" class="table table-inteview-assessment w-100">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Form Name</th>
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
    @endsection

    @section('import-css')
    @endsection

    @section('import-scripts')

    <script type="text/javascript">
    $(document).ready(function() {
        $(".select2t-none").select2();
        $('.table-inteview-assessment tbody').empty();
        var viewForm = $('.table-inteview-assessment').DataTable({
            searching: false,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false,
            scrollX: true,
            iDisplayLength: 6,
            processing: true,
            serverSide: true,
            order:[[3, 'desc']],
            ajax: {
                url: '{{ route("interview-assessment.list") }}',
                type: 'GET',
                data: function(d) {
                    var position = $('#positions').val();
                    d.position = position;

                    var searchTerm = $('.search').val();
                    d.searchTerm = searchTerm;
                }
            },
            columns: [
                { data: 'Position', name: 'Position', className: 'text-nowrap' },
                { data: 'form_name', name: 'Form Name', className: 'text-nowrap' },
                { data: 'action', name: 'Action', orderable: false, searchable: false },
            ]
        });

        $(document).on('click', '.delete-row-btn', function() {
            let id = $(this).data('id');
            let url = "{{ route('interview-assessment.delete', ':id') }}";
            url = url.replace(':id', id);

            Swal.fire({
                title: 'Are you sure you want to delete?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: '#DD6B55',
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        method: "DELETE",
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.msg, "Success", {
                                    positionClass: 'toast-bottom-right',
                                });
                                $('.table-inteview-assessment').DataTable().ajax.reload();
                            } else {
                                toastr.error(response.msg, "Error", {
                                    positionClass: 'toast-bottom-right',
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            toastr.error('An error occurred while processing your request.', "Error", {
                                positionClass: 'toast-bottom-right',
                            });
                            console.error('Error:', error);
                        },
                    });
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


        $('#positions').change(function() {
            viewForm.ajax.reload();
        });
        $('.search').on('keyup', function() {
            viewForm.ajax.reload();
        });

    });
    </script>
    @endsection
