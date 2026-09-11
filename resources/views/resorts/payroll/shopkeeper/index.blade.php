@extends('resorts.layouts.app')
@section('page_tab_title' , $page_title)

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<style>
    #shopkeeper-index-hero { padding-bottom: 40px; }
    @media (max-width: 575.98px) {
        #shopkeeper-index-hero { padding-bottom: 0; }
    }
</style>
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding" id="shopkeeper-index-hero">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Payroll</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto">
                </div>
            </div>
        </div>
        <div class="sk-card">
            <div class="sk-toolbar">
                <div class="sk-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="search" id="search-input" placeholder="Search by name, email or contact">
                </div>
                <span class="sk-spacer-flex"></span>
                <span class="sk-count" id="shopkeeper-count"></span>
                <a href="{{ route('shopkeepers.create') }}" class="sk-pribtn @if(App\Helpers\Common::checkRouteWisePermission('shopkeepers.create',config('settings.resort_permissions.create')) == false) d-none @endif">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>Add shopkeeper
                </a>
            </div>

            <table id="shopkeeper-table" class="sk-tbl">
                <thead>
                    <tr>
                        <th><span class="sk-th-sort">Name <svg class="sk-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg></span></th>
                        <th><span class="sk-th-sort">Email <svg class="sk-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg></span></th>
                        <th><span class="sk-th-sort">Contact <svg class="sk-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg></span></th>
                        <th class="sk-act">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@include('resorts.payroll._payroll_buttons_v2_styles')
@include('resorts.payroll.shopkeeper._shopkeeper_styles')
@endsection

@section('import-scripts')

<script>
    var skTable = null;
    var $skEditingRow = null;
    var $skConfirmingRow = null;

    function skEsc(s) { return (s == null ? '' : ('' + s)).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;'); }
    function skInitials(name) {
        var p = (name || '').trim().split(/\s+/);
        var s = p.length > 1 ? (p[0][0] + p[1][0]) : (name || '').trim().slice(0, 2);
        return (s || '?').toUpperCase();
    }
    var SK_SVG = {
        edit: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>',
        del: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>',
        save: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
        cancel: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>'
    };

    function skRenderName(data, type, row) {
        if (type !== 'display') return data;
        var photo = row.profile_photo ? "{{ asset(config('settings.ShopkeeperProfile_folder')) }}/" + row.profile_photo : null;
        var img = photo ? '<img src="' + skEsc(photo) + '" alt="' + skEsc(row.name) + '" onerror="this.remove()">' : '';
        return '<div class="sk-nm"><span class="sk-av"><span class="sk-av-fallback">' + skEsc(skInitials(row.name)) + '</span>' + img + '</span><span class="sk-t">' + skEsc(row.name) + '</span></div>';
    }

    function skSyncSortIndicators() {
        if (!skTable) return;
        var order = skTable.order();
        var activeCol = order.length ? order[0][0] : null;
        var activeDir = order.length ? order[0][1] : null;
        $('#shopkeeper-table thead .sk-th-sort').removeClass('asc desc');
        if (activeCol !== null) {
            $('#shopkeeper-table thead th').eq(activeCol).find('.sk-th-sort').addClass(activeDir === 'asc' ? 'asc' : 'desc');
        }
    }

    function skCloseEdit($tr) {
        if ($tr && $tr.length && $tr.data('sk-orig-html') !== undefined) {
            $tr.removeClass('sk-editing').html($tr.data('sk-orig-html'));
        }
    }
    function skCloseConfirm($tr) {
        if ($tr && $tr.length && $tr.data('sk-orig-act-html') !== undefined) {
            $tr.find('td.sk-act').html($tr.data('sk-orig-act-html'));
        }
    }

    function datatablelist() {
        if ($.fn.dataTable.isDataTable('#shopkeeper-table')) {
            $('#shopkeeper-table').DataTable().destroy();
        }
        $skEditingRow = null;
        $skConfirmingRow = null;

        skTable = $('#shopkeeper-table').DataTable({
            searching: false,
            lengthChange: false,
            info: true,
            autoWidth: false,
            // scrollX split this table into a head-clone (.dt-scroll-head)
            // + a body table that keeps its OWN <thead> as a zero-height
            // sizing probe — DataTables' default CSS collapses that probe,
            // but the .sk-tbl th padding !important (needed to beat
            // default.css's own !important table padding elsewhere) was
            // overriding that collapse, so the probe rendered as a real
            // second header-striped row above the data. This table only
            // has 4 columns and never needs horizontal scroll, so the
            // simplest correct fix is not enabling the mechanism at all.
            pageLength: 10,
            processing: true,
            serverSide: true,
            // Was order:[[6,'desc']] against a 6-column config that never
            // had a 7th column — an out-of-range sort target left over
            // from an earlier layout. Name ascending (matches the design)
            // is a real, in-range default.
            order: [[0, 'asc']],
            ajax: {
                url: "{{ route('shopkeepers.list') }}",
                type: 'GET',
                data: function (d) {
                    d.searchTerm = $('#search-input').val();
                },
                dataSrc: function (json) {
                    $('#shopkeeper-count').text((json.recordsTotal || 0) + (json.recordsTotal === 1 ? ' shopkeeper' : ' shopkeepers'));
                    return json.data;
                }
            },
            columns: [
                { data: 'name', name: 'name', render: skRenderName },
                { data: 'email', name: 'email', className: 'sk-email' },
                { data: 'contact_no', name: 'contact_no', className: 'sk-num' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'sk-act' },
                { data: 'created_at', visible: false, searchable: false },
            ],
            drawCallback: function () {
                $skEditingRow = null;
                $skConfirmingRow = null;
                skSyncSortIndicators();
            },
        });
    }

    $(document).ready(function() {
        datatablelist();

        $('#search-input').on('keyup', function () {
            datatablelist();
        });

        $('#clearFilter').on('click', function () {
            $('#search-input').val('');
            datatablelist();
        });
    });

    // ── Inline edit ──────────────────────────────────────────────────
    $(document).on('click', '#shopkeeper-table .edit-row-btn', function (e) {
        e.preventDefault();
        var $tr = $(this).closest('tr');
        var d = skTable.row($tr).data();
        if (!d) return;

        if ($skConfirmingRow && $skConfirmingRow.length) { skCloseConfirm($skConfirmingRow); $skConfirmingRow = null; }
        if ($skEditingRow && $skEditingRow.length && $skEditingRow[0] !== $tr[0]) { skCloseEdit($skEditingRow); }

        $tr.data('sk-orig-html', $tr.html());
        $tr.addClass('sk-editing').html(
            '<td><div class="sk-nm"><span class="sk-av"><span class="sk-av-fallback">' + skEsc(skInitials(d.name)) + '</span></span>' +
                '<input class="sk-cellinp f-name" value="' + skEsc(d.name) + '"></div></td>' +
            '<td><input class="sk-cellinp f-email" value="' + skEsc(d.email) + '"></td>' +
            '<td><input class="sk-cellinp f-contact" value="' + skEsc(d.contact_no) + '"></td>' +
            '<td class="sk-act"><div class="sk-acts">' +
                '<button type="button" class="sk-ico save save-row-btn" title="Save" data-shopkeeper-id="' + d.id + '">' + SK_SVG.save + '</button>' +
                '<button type="button" class="sk-ico cancel cancel-row-btn" title="Cancel">' + SK_SVG.cancel + '</button>' +
            '</div></td>'
        );
        $skEditingRow = $tr;
        $tr.find('.f-name').trigger('focus').trigger('select');
    });

    $(document).on('click', '#shopkeeper-table .cancel-row-btn', function (e) {
        e.preventDefault();
        skCloseEdit($(this).closest('tr'));
        $skEditingRow = null;
    });

    $(document).on('click', '#shopkeeper-table .save-row-btn', function (e) {
        e.preventDefault();
        var $tr = $(this).closest('tr');
        var $btn = $(this).prop('disabled', true);
        var id = $btn.data('shopkeeper-id');

        $.ajax({
            url: "{{ route('shopkeeper.inlineUpdate', '') }}/" + id,
            type: 'PUT',
            data: {
                name: $tr.find('.f-name').val(),
                email: $tr.find('.f-email').val(),
                contact_no: $tr.find('.f-contact').val(),
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                    $skEditingRow = null;
                    skTable.ajax.reload(null, false);
                } else {
                    toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                    $btn.prop('disabled', false);
                }
            },
            error: function (xhr) {
                var errors = xhr.responseJSON;
                var msg = 'Failed to update shopkeeper.';
                if (errors && errors.errors) {
                    msg = Object.values(errors.errors).flat().join('<br>');
                } else if (errors && errors.message) {
                    msg = errors.message;
                }
                toastr.error(msg, "Error", { positionClass: 'toast-bottom-right' });
                $btn.prop('disabled', false);
            }
        });
    });

    // ── Inline delete confirm ────────────────────────────────────────
    $(document).on('click', '#shopkeeper-table .delete-row-btn', function (e) {
        e.preventDefault();
        var $tr = $(this).closest('tr');
        var id = $(this).data('shopkeeper-id');

        if ($skEditingRow && $skEditingRow.length) { skCloseEdit($skEditingRow); $skEditingRow = null; }
        if ($skConfirmingRow && $skConfirmingRow.length && $skConfirmingRow[0] !== $tr[0]) { skCloseConfirm($skConfirmingRow); }

        var $act = $tr.find('td.sk-act');
        $tr.data('sk-orig-act-html', $act.html());
        $act.html(
            '<div class="sk-acts"><span class="sk-delq">Delete?</span>' +
            '<button type="button" class="sk-ico confirm-del confirm-delete-btn" title="Confirm delete" data-shopkeeper-id="' + id + '">' + SK_SVG.del + '</button>' +
            '<button type="button" class="sk-ico cancel-del cancel-delete-btn" title="Cancel">' + SK_SVG.cancel + '</button></div>'
        );
        $skConfirmingRow = $tr;
    });

    $(document).on('click', '#shopkeeper-table .cancel-delete-btn', function (e) {
        e.preventDefault();
        skCloseConfirm($(this).closest('tr'));
        $skConfirmingRow = null;
    });

    $(document).on('click', '#shopkeeper-table .confirm-delete-btn', function (e) {
        e.preventDefault();
        var $btn = $(this).prop('disabled', true);
        var id = $btn.data('shopkeeper-id');

        $.ajax({
            type: 'DELETE',
            url: "{{ route('shopkeeper.destroy', '') }}/" + id,
            dataType: 'json',
        }).done(function (result) {
            if (result.success) {
                toastr.success(result.message, "Success", { positionClass: 'toast-bottom-right' });
                $skConfirmingRow = null;
                skTable.ajax.reload(null, false);
            } else {
                toastr.error(result.message, "Error", { positionClass: 'toast-bottom-right' });
                $btn.prop('disabled', false);
            }
        }).fail(function () {
            toastr.error("Something went wrong", "Error", { positionClass: 'toast-bottom-right' });
            $btn.prop('disabled', false);
        });
    });
</script>
@endsection
