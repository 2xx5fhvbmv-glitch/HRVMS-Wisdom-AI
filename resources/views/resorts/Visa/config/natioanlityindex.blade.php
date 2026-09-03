@extends('resorts.layouts.app')
@section('page_tab_title',$page_title)

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
<style>
    #visa-nationality-index-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #visa-nationality-index-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="visa-nationality-index-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Visa Management</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <!-- <div class="d-flex justify-content-end">
                        <a href="{{ route('GrievanceAndDisciplinery.grivance.CreateGrivance') }}" class=" btn btn-sm btn-theme">Create Grivance</a>
                    </div> -->
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
                                        <h3>{{ $page_title }}</h3>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex justify-content-end align-items-center">
                                        <input type="text" id="NationalitySearch" class="form-control" placeholder="Search nationality...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="IndexNationality" class="table  w-100">
                            <thead>
                                <tr>
                                    <th class="text-nowrap"> Nationality Name </th>
                                    <th class="text-nowrap"> Amount </th>
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

@include('resorts._emotional_buttons_v2_styles')
@include('resorts._dropdown_styles')
@include('resorts._dropdown_script')
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
$(document).ready(function(){
    $('#IndexNationality tbody').empty();

        var hiringsource = $('#IndexNationality').DataTable({
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
            url: '{{ route("resort.visa.NationalityIndex") }}',
            type: 'GET',
            data: function (d) {
                d.search_text = $('#NationalitySearch').val();
            }
        },
        columns: [
            { data: 'nationality', name: 'nationality', className: 'text-nowrap' },
            { data: 'Amount', name: 'Amount', className: 'text-nowrap' },
            { data: 'Action', name: 'Action', orderable: false, searchable: false }
        ]
    });

    var nationalitySearchTimer;
    $('#NationalitySearch').on('keyup', function () {
        clearTimeout(nationalitySearchTimer);
        nationalitySearchTimer = setTimeout(function () {
            hiringsource.ajax.reload();
        }, 400);
    });

});
$(document).on('click', '.delete-row-btn', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest("tr");
            var main_id = $(this).attr('data-cat-id');

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
                        url: "{{ route('resort.visa.nationality.destroy', ':id') }}".replace(':id', main_id),
                        dataType: "json",
                    }).done(function(result) {
                        if (result.success) {
                            toastr.success(result.message, "Success", {
                                positionClass: 'toast-bottom-right'
                            });
                            $('#IndexNationality').DataTable().ajax.reload();

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

        $(document).on("click", "#IndexNationality .edit-row-btn", function (event) {
            event.preventDefault(); // Prevent default action

            // Find the parent row
            var $row = $(this).closest("tr");

            var Main_id = $(this).attr('data-cat-id');
            var Del_cat_id = $(this).attr('data-del_cat_id');
            
            var DiscriplineryName = $row.find("td:nth-child(1)").text().trim();
            // Use the RAW amount (data-amt), not the formatted "MVR 4,000.00" cell text,
            // so the edit input holds a clean number that submits correctly.
            var Description = $(this).attr('data-amt') || '';
            var tickSvg = '<svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg>';
            var nationalityList = @json($nationality ?? []);
            var editRowHtml = `
                    <td class="py-1">
                        <div class="form-group">
                            <select class="form-select dd-native-select nationality"
                                        id="nationality_1"  data-id="1" name="Del_cat_id"  aria-label="Nationality" required  data-parsley-required-message="Please select a nationality">
                                    <option value=""></option>
                                    ${nationalityList.map(item => `<option value="${item}" ${Del_cat_id === item ? 'selected' : ''}>${item}</option>`).join('')}
                                </select>
                            <div class="dd" data-target="#nationality_1">
                                <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dd-lbl">Select Nationality</span>
                                    <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="dd-panel" role="listbox" aria-label="Nationality">
                                    <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a nationality…"></div>
                                    <div class="dd-scroll">
                                        ${nationalityList.map(item => `<div class="dd-item${Del_cat_id === item ? ' active' : ''}" role="option" data-value="${item}"><span class="dd-nm">${item}</span>${tickSvg}</div>`).join('')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="py-1">
                        <div class="form-group">
                            <input type="text" class="form-control" id="DelegationRuleName" value="${Description}"  required data-parsley-required-message="Please Enter Rule"  name="Del_Rule" placeholder="Set Rule">
                        </div>
                    </td>
                    <td class="py-1">
                        <a href="javascript:void(0)" class="btn eb-btn-primary update-row-btn_cat" data-cat-id="${Main_id}">Submit</a>
                    </td>
                `;

            // Replace row content with editable form
            $row.html(editRowHtml);
            wisdomDD.sync('#nationality_1');
        });
        $(document).on("click", "#IndexNationality .update-row-btn_cat", function (event) 
        {
            event.preventDefault(); // Prevent default action
            var $row = $(this).closest("tr");
            var Main_id = $(this).attr('data-cat-id');
            var Nationality = $row.find("select").val();
            var Amount = $row.find("input").val();
            $.ajax({
                url: "{{ route('resort.visa.nationality.update','')}}/" + Main_id,
                type: "PUT",
                data: {
                    Main_id:Main_id,
                    nationality:Nationality,
                    amt:Amount,
                },
                success: function(response) {
                    if(response.success == true) { // Ensure response contains a success key

                        $('#IndexNationality').DataTable().ajax.reload();
                        toastr.success(response.msg, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error(response.msg, "Error", {
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