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
                        <span>Performance</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <!-- <div class="col-auto">
                    <div class="d-flex justify-content-end">
                        <a href="#" class="btn btn-theme">Request Manning</a>
                    </div>
                </div> -->
            </div>
        </div>

        <div>
            <div class="row g-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-title">
                            <h3>90 Peformance</h3>
                            <div class="text-end">
                            </div>
                        </div>
                        <div class="row g-md-4 g-3 mb-1 flex-grow-1">
                            <table class="table table-NityPerformanceFormList" id="table-NityPerformanceFormList">
                                <thead>
                                    <tr>
                                        <th>Form Name</th>
                                        <th> Action</th>
                                    </tr>
                                </thead>
                            </table>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="FormTemplete-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <form id="jobDesEditForm" data-parsley-validate>
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Edit Job Description</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="Form_id" id="Form_id" value="">

                    <div class="row">
                        <div class="col-md-3">
                            <label>Form Name</label>
                            <input type="text" name="form_name" id="form_name" class="form-control"
                                required data-parsley-required-message="Form Name is required" value="">
                        </div>
                        <input type="hidden" name="id" id="form_id">
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <div id="form-builder"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button type="button" id="save-form" class="btn btn-themeBlue">Submit</button>
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
      $(document).ready(function ()
{
    ReviewType();
    const BASE_URL = "{{ asset('resorts_assets/lang/en-US.lang') }}";
    const options = {
        disableFields: ['autocomplete', 'button'],
        i18n: {
            locale: 'en-US',
            override: {},
            url: BASE_URL
        }
    };

    // Initialize FormBuilder globally
    var formBuilder = $('#form-builder').formBuilder(options);

    $(document).on("click", ".edit-row-btn", function(){
        var id = $(this).data('id');
        var name = $(this).data('name');

        $("#form_id").val(id);
        $("#FormTemplete-modal").modal('show');

        // Clear existing fields instead of emptying the div
        formBuilder.actions.clearFields();

        $.ajax({
            url: "{{ route('Performance.config.GetNintyPerformanceForm', ['id' => '__ID__']) }}".replace('__ID__', id),
            type: "get",
            data: {
                id: id,
            },
            success: function (response) {
                var d = response.data;
                $("#form_name").val(d[1]);
                $("#Form_id").val(d[2]);
                let existingFormStructure = d[0];


                    formBuilder.actions.setData(existingFormStructure);


                toastr.success(response.message, "Success", {
                    positionClass: 'toast-bottom-right'
                });
            },
            error: function(response)
            {
                var errors = response.responseJSON;
                var errs = '';
                $.each(errors.errors, function(key, error)
                {
                    errs += error + '<br>';
                });
                toastr.error(errs, {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });
    $('#save-form').click(function (e)
    {
        var formname = $('#form_name').val();

        if(!isNaN(formname))
        {
            toastr.error('Please enter  form Name.', "Error", {
                    positionClass: 'toast-bottom-right'
                });

            return false;
        }
        const formStructure = formBuilder.actions.getData('json');
        e.preventDefault();
        $.ajax({
            url: "{{ route('Performance.config.NintyDayPerformanceFormUpdate') }}",
            type: "POST",
            data: {
                FormName: formname,
                form_structure: formStructure,
                Form_id:$("#Form_id").val(),
            },
            success: function (response)
            {

                $("#FormTemplete-modal").modal('hide');
                    toastr.success(response.message, "Success",
                    {
                        positionClass: 'toast-bottom-right'
                    });
                ReviewType();


            },
            error: function(response)
            {
                var errors = response.responseJSON;
                var errs = '';
                $.each(errors.errors, function(key, error)
                {
                    errs += error + '<br>';
                });
                toastr.error(errs, {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });

    $(document).on('click', '.delete-row-btn', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest("tr");

            // Get the division ID from the data attribute
            var main_id = $(this).data('id');

            Swal.fire({
                title: 'Sure want to delete?',
                text: 'This cannot be undone',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: "#DD6B55"
             }).then((result) => {
                if (result.isConfirmed)
                {

                    $.ajax({
                        type: "delete",
                        url: "{{ route('Performance.DestroyNintyDayPerformanceForm','') }}/"+main_id,
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success == true) {
                            $row.remove();

                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });

                            ReviewType();

                        }
                            else {
                            toastr.error(result.message, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        }
                    }).fail(function(error) {
                        toastr.error("Something went wrong", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    });
                }
            });
        });
});



        function ReviewType()
        {
            if ($.fn.dataTable.isDataTable('#table-NityPerformanceFormList'))
            {
                $('#table-NityPerformanceFormList').DataTable().destroy();
            }

            var TableAccomMainten = $('#table-NityPerformanceFormList').DataTable({
                "searching": false,
                "bLengthChange": false,
                "bFilter": true,
                "bInfo": true,
                "bAutoWidth": false,
                "scrollX": true,
                "iDisplayLength": 6,
                processing: true,
                serverSide: true,
                order:[[2,'desc']],
                ajax: {
                    url: '{{ route("Performance.config.NintyPerformanceFormList") }}',
                    type: 'GET',
                    data: function (d) {
                        d.ResortDepartment = $(".ResortDepartment").val();
                    }
                },
                columns: [

                    { data: 'FormName', name: 'FormName', className: 'text-nowrap' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                    {data:'created_at', visible:false,searchable:false},
                ]
            });
        }

</script>
@endsection
