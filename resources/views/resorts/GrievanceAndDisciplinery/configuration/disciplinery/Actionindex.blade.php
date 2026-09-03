@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #disciplinary-action-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #disciplinary-action-hero { padding-bottom: 0; }
    }
</style>

<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="disciplinary-action-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Resort Config Pages</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                </div>
            </div>
        </div>
        <div>
            <div class="row g-30">
                <div class="col-xxl-12 col-xl-12 col-lg-12">
                    <div class="card ac-card">
                        <div class="ac-toolbar">
                            <div class="input-group ac-search">
                                <input type="search" class="form-control" id="action-search" placeholder="Search actions…" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                        <div class="ac-thwrap">
                        <table id="IndexAction" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap ac-th-name">Action Name</th>
                                    <th>Description</th>
                                    <th class="text-nowrap ac-th-act">Action</th>
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
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
@include('resorts.GrievanceAndDisciplinery.configuration.disciplinery._action_config_styles')
@endsection

@section('import-scripts')

<script>
        var DisciplineryCategory = $('#IndexAction').DataTable({
            "dom": "rtip", // no default search box — the custom #action-search input drives .search() instead
            "bLengthChange": false,
            "bInfo": true,
            "bAutoWidth": false,
            "scrollX": true,
            "iDisplayLength": 6,
            processing: true,
            serverSide: true,
            order:[[3, 'desc']],
            ajax: '{{ route("GrievanceAndDisciplinery.config.IndexAction") }}',
            columns: [
                { data: 'ActionName', name: 'ActionName', className: 'text-nowrap ac-td-name' },
                { data: 'description', name: 'description', className: 'ac-td-desc',
                    render: function (data) { return '<div class="ac-desc">' + (data == null ? '' : data) + '</div>'; } },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'ac-actcell' },
                 {data:'created_at',visible:false,searchable:false},
            ]
        });

        $('#action-search').on('keyup search input', function () {
            DisciplineryCategory.search(this.value).draw();
        });





        $(document).on('click', '.delete-row-btn', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest("tr");
            var main_id = $(this).data('cat-id');

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
                        url: "{{ route('GrievanceAndDisciplinery.config.ActionDestory', ':id') }}".replace(':id', main_id),
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success) {
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#IndexAction').DataTable().ajax.reload();

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

        function restoreRow($row) {
            var original = $row.data('original-html');
            if (original !== undefined) {
                $row.removeClass('editing').html(original);
            }
        }

        $(document).on("click", "#IndexAction .edit-row-btn", function (event) {
            event.preventDefault(); // Prevent default action

            // Only one row editable at a time.
            $('#IndexAction tbody tr.editing').each(function () { restoreRow($(this)); });

            // Find the parent row
            var $row = $(this).closest("tr");

            var Main_id = $(this).attr('data-cat-id');

            var Action = $row.find("td:nth-child(1)").text().trim();
            var Description = $row.find("td:nth-child(2)").text().trim();

            $row.data('original-html', $row.html());

            var editRowHtml = `
                    <td class="py-1">
                        <div class="ac-ef">
                            <span class="ac-lbl">Action name</span>
                            <input type="text" class="form-control ac-inp DisciplinaryCategoryName" value="${Action}" />
                        </div>
                    </td>
                    <td class="py-1">
                        <div class="ac-ef">
                            <span class="ac-lbl">Description</span>
                            <textarea class="form-control ac-inp DisciplinaryCategoryName">${Description}</textarea>
                        </div>
                    </td>
                    <td class="py-1 ac-actcell">
                        <div class="ac-rowbtns">
                            <a href="javascript:void(0)" class="btn ac-rowbtn ac-cancel cancel-row-btn">Cancel</a>
                            <a href="javascript:void(0)" class="btn ac-rowbtn ac-save update-row-btn_cat" data-cat-id="${Main_id}">Save</a>
                        </div>
                    </td>
                `;

            // Replace row content with editable form
            $row.addClass('editing').html(editRowHtml);

        });

        $(document).on("click", "#IndexAction .cancel-row-btn", function (event) {
            event.preventDefault();
            restoreRow($(this).closest("tr"));
        });

        $(document).on("click", "#IndexAction .update-row-btn_cat", function (event) {
            event.preventDefault(); // Prevent default action
            var $row = $(this).closest("tr");
            var Main_id = $(this).attr('data-cat-id');
            var ActionName = $row.find("input").eq(0).val();
            var Description = $row.find("textarea").val();

                   
            $.ajax({
                url: "{{ route('GrievanceAndDisciplinery.config.ActioninlineUpdate', '') }}/" + Main_id,
                type: "PUT",
                data: {
                    Main_id:Main_id,
                    ActionName:ActionName,
                    description:Description,
                },
                success: function(response) {
                    if(response.success == true) { // Ensure response contains a success key

                        $('#IndexAction').DataTable().ajax.reload();
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
                            })
                        }
                        else
                        {
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
