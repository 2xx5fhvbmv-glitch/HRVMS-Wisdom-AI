@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #notice-period-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #notice-period-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="notice-period-hero">
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
@include('resorts._dropdown_styles')
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

        wisdomConfirm({
            role: 'destructive',
            title: 'Sure want to delete?',
            text: 'This cannot be undone',
            confirmText: 'Yes',
            cancelText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "DELETE",
                    url: "{{ route('people.notice-period.destroy', ':id') }}".replace(':id', main_id),
                    // Missing CSRF token → 419 on strict-CSRF prod.
                    // Same id-vs-Main_id payload fix as the update handler:
                    // controller reads $request->id to base64_decode().
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { id: main_id },
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
     var titleItemsHtml = '';
     var selectedTitleLbl = 'Select Task';
     tasks.forEach(function(t) {
     var isSelected = t.position_title == title;
     optionsHtml += `<option value="${t.position_title}" ${isSelected ? 'selected' : ''}>${t.position_title}</option>`;
     titleItemsHtml += `<div class="dd-item${isSelected ? ' active' : ''}" role="option" data-value="${t.position_title}"><span class="dd-nm">${t.position_title}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>`;
     if (isSelected) selectedTitleLbl = t.position_title;
     });

    var titleSelectId = 'notice-title-' + Main_id;
    var releaseSelectId = 'notice-release-' + Main_id;

    var editRowHtml = `
        <td class="py-1">
            <div class="form-group">
                <select class="form-control title dd-native-select" id="${titleSelectId}">
                    ${optionsHtml}
                </select>
                <div class="dd" data-target="#${titleSelectId}">
                    <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                        <span class="dd-lbl">${selectedTitleLbl}</span>
                        <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div class="dd-panel" role="listbox" aria-label="Task">
                        <div class="dd-scroll">${titleItemsHtml}</div>
                    </div>
                </div>
            </div>
        </td>
        <td class="py-1">
            <div class="form-group">
                <input type="text" class="form-control days" value="${period}" />
            </div>
        </td>
        <td class="py-1">
            <div class="form-group">
                    <select class="form-control immediate_release dd-native-select" id="${releaseSelectId}">
                         <option value="1" ${immediate_release === 'Yes' ? 'selected' : ''}>Yes</option>
                         <option value="0" ${immediate_release === 'No' ? 'selected' : ''}>No</option>
                    </select>
                    <div class="dd" data-target="#${releaseSelectId}">
                        <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="dd-lbl">${immediate_release === 'No' ? 'No' : 'Yes'}</span>
                            <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div class="dd-panel" role="listbox" aria-label="Immediate Release">
                            <div class="dd-scroll">
                                <div class="dd-item ${immediate_release === 'No' ? '' : 'active'}" role="option" data-value="1"><span class="dd-nm">Yes</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                <div class="dd-item ${immediate_release === 'No' ? 'active' : ''}" role="option" data-value="0"><span class="dd-nm">No</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                            </div>
                        </div>
                    </div>
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

        // BUG FIX:
        //   1. The controller reads `$request->id` to find the row,
        //      not `Main_id` — the old payload silently failed
        //      (base64_decode(null) → "" → find("") → null → no save).
        //   2. Missing X-CSRF-TOKEN header — would 419 on strict CSRF
        //      middleware. Added below.
        //   3. immediate_release='1' must clear period server-side
        //      (controller already does); we still send the value so
        //      validation runs.
        $.ajax({
            url: "{{ route('people.notice-period.update', '') }}/" + Main_id,
            type: "POST",
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: {
                id: Main_id,
                title: title,
                period: period,
                immediate_release: immediate_release,
                "_token": "{{ csrf_token() }}"
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
@include('resorts._dropdown_script')
@endsection
