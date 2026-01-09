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
                        <span>People</span>
                        <h1>{{$page_title}}</h1>
                    </div>
                </div>
                <div class="col-auto">
                </div>
            </div>
        </div>
        <div>
            <div class="row g-30">
                <div class="col-xxl-12 col-xl-12 col-lg-12">
                <!-- <div class="col-xxl-6 col-xl-6 col-lg-6"> -->
                    <div class="card">
                        <div class="card-title">
                            <div class="row g-3 align-items-center justify-content-between">
                                <div class="col-auto">
                                    <div class="d-flex justify-content-start align-items-center">
                                        <h3>{{$page_title}}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="ReminderTable" class="table w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">Title</th>
                                    <th class="text-nowrap">Notice Period</th>
                                    <th class="text-nowrap">Immediate Release</th>
                                    <th class="text-nowrap">Action</th>
                                </tr>
                            </thead>

                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')

<script>
    $(document).ready(function(){
        fecth();
    });

    function fecth(){
        if ($.fn.dataTable.isDataTable('#ReminderTable')) {
            $('#ReminderTable').DataTable().destroy();
        }
        var ReminderTable = $('#ReminderTable').DataTable({
            "searching": false,
            "bLengthChange": false,
            "bFilter": true,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 10,
            processing: true,
            serverSide: true,
            order:[[4, 'desc']],
            ajax: '{{ route("people.notice-period.list") }}',
            columns: [
                { data: 'title', name: 'title', className: 'text-nowrap' }, 
                { data: 'period', name: 'period'}, 
                { data: 'immediate_release', name: 'immediate_release'}, 
                { data: 'action', name: 'action', orderable: false, searchable: false }, // Fixed 'Action' to 'action'
                {data:'created_at',visible:false,searchable:false},
            ],
            error: function(xhr, error, code) {
                console.log(xhr.responseText);
            }
        });
    }
        
    $(document).on('click', '.delete-row-btn', function (e) {
        e.preventDefault();
        var $button = $(this);
        var $row = $button.closest("tr");
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
            if (result.isConfirmed) {
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('people.notice-period.destroy', ':id') }}".replace(':id', main_id),
                    dataType: "json",
                }).done(function(result) {
                    if (result.success) {
                        toastr.success(result.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                        $('#ReminderTable').DataTable().ajax.reload();

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

   $(document).on("click", "#ReminderTable .edit-row-btn", function (event) {
    event.preventDefault(); // Prevent default action
    // Find the parent row
    var $row = $(this).closest("tr");
    var Main_id = $(this).attr('data-id');
    var title = $row.find("td:nth-child(1)").text().trim();
    var period = $row.find("td:nth-child(2)").text().trim();
    var immediate_release = $row.find("td:nth-child(3)").text().trim();
   
     var rawGrades = @json($emp_grade);

     var tasks = [{ id: 0, position_title: "Select Task" }];

     for (const id in rawGrades) {
     tasks.push({
          id: id,
          position_title: rawGrades[id]
     });
     }

     // Assume 'title' holds the selected value (id)
     var optionsHtml = '';
     tasks.forEach(function(t) {
     optionsHtml += `<option value="${t.position_title}" ${t.position_title == title ? 'selected' : ''}>${t.position_title}</option>`;
     });


    var editRowHtml = `
        <td class="py-1">
            <div class="form-group">
                <select class="form-control select2 title">
                    ${optionsHtml}
                </select>
            </div>
        </td>
        <td class="py-1">
            <div class="form-group">
                <input type="text" class="form-control days" value="${period}" />
            </div>
        </td>
        <td class="py-1">
            <div class="form-group">
                    <select class="form-control immediate_release">
                         <option value="1" ${immediate_release === 'Yes' ? 'selected' : ''}>Yes</option>
                         <option value="0" ${immediate_release === 'No' ? 'selected' : ''}>No</option>
                    </select>
            </div>
        </td>
        <td class="py-1">
            <a href="javascript:void(0)" class="btn btn-theme update-row-btn" data-id="${Main_id}">Submit</a>
        </td>
    `;

    $row.html(editRowHtml);
});
    $(document).on("click", "#ReminderTable .update-row-btn", function (event) {
        event.preventDefault(); // Prevent default action
        var $row = $(this).closest("tr");
        var Main_id = $(this).attr('data-id');
        var title = $row.find("select.title").val();
        var period = $row.find("input.days").val();
        var immediate_release = $row.find("select.immediate_release").val();

        $.ajax({
            url: "{{ route('people.notice-period.update', '') }}/" + Main_id,
            type: "POST",
            data: {
                Main_id: Main_id,
                title: title,
                period: period,
                immediate_release: immediate_release
            },
            success: function(response) {
                if (response.success == true) { 
                    $('#ReminderTable').DataTable().ajax.reload();
                    toastr.success(response.message, "Success", {
                        positionClass: 'toast-bottom-right'
                    });
                } else {
                    toastr.error(response.message, "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = '';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function(key, error) {
                        errorMessage += error + "<br>";
                    });
                } else {
                    errorMessage = "An error occurred while Create or Update."; // Default error message
                }
                toastr.error(errorMessage, "Error", {
                    positionClass: 'toast-bottom-right'
                });
            }
        });
    });
</script>
@endsection
