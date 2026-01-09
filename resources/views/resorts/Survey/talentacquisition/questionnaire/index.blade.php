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


            <div class="card">
                <div class="card-title">
                    <h3>All Questions</h3>
                </div>
                <table class="table"  id="qustionner" >
                    <thead>
                        <tr>
                            <th>Division</th>
                            <th>Department</th>
                            <th>Position</th>
                            {{-- <th>questionType</th> --}}
                            {{-- <th>Question</th> --}}
                            <th>Action</th>
                        </tr>
                    </thead>


                </table>

            </div>


        </div>
    </div>
    <div class="modal fade" id="respond-rejectModal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-small">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Respond</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control" rows="7" placeholder="Reason for Rejection"></textarea>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                <a href="#" class="btn btn-themeBlue">Submit</a>
            </div>

        </div>
    </div>
</div>
    @endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    window.resortDivisions = @json($resort_divisions);
    window.resortDepartments = @json($resort_departments);
    window.resort_positions = @json($resort_positions);
    // First, check if DataTable already exists and destroy it if it does
if ($.fn.DataTable.isDataTable('#qustionner')) {
    $('#qustionner').DataTable().destroy();
}

// Clear the table contents
$('#qustionner tbody').empty();
    var divisionTable = $('#qustionner').DataTable({
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
        url: '{{ route("resort.ta.getResortWiseQuestion") }}',
        type: 'GET',
    },
    columns: [
        { data: 'Division', name: 'Division', className: 'text-nowrap' },
        { data: 'Department', name: 'Department', className: 'text-nowrap' },
        { data: 'Position', name: 'Position', className: 'text-nowrap' },
        // {data:'questionType',name:'questionType',className:'text-nowrap'},
      //  { data: 'Question', name: 'Question', className: 'text-nowrap' },
        { data: 'action', name: 'action', orderable: false, searchable: false }
    ]
});



$(document).on('click', '.delete-row-btn', function (e) {
    e.preventDefault();
    var $button = $(this);
    var $row = $button.closest("tr");

    // Get the division ID from the data attribute
    var position_id = $(this).data('dept-id');
    var ParentId =$(this).data('parent-id');
    swal({
        title: 'Sure want to delete?',
        text: 'This cannot be undone',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No',
        confirmButtonColor: "#DD6B55"
    }).then(function(success) {
        if (success) {
            $.ajax({
                type: "post",
                url: "{{ route('resort.ta.destroyQuestions') }}",
                data: {"childId": position_id,"ParentId":ParentId},
                dataType: "json",
            }).done(function(result) {
                if (result.success == true) {
                    $row.remove();

                    toastr.success(result.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });

                    $('#positions-table').DataTable().ajax.reload();
                } else {
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

</script>
@endsection

