@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

@section('content')
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding page-appHedding">
                <div class="row justify-content-between g-md-2 g-1">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Accommodation</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <p class="text-muted mb-3">Inactive employees whose Full &amp; Final Settlement is finalized but who still hold a bed / room / villa.
                        <strong>Yes</strong> vacates it now; <strong>Keep</strong> leaves it and asks again in 3 days.</p>
                    @if($decisions->isEmpty())
                        <p class="mb-0">Nothing waiting for a decision.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-collapseNew">
                                <thead><tr><th>Employee</th><th>Department</th><th>Holding</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                    @foreach($decisions as $d)
                                        <tr>
                                            <td>{{ optional(optional($d->employee)->resortAdmin)->full_name ?? '—' }} ({{ optional($d->employee)->Emp_id }})</td>
                                            <td>{{ optional(optional($d->employee)->department)->name ?? '—' }}</td>
                                            <td>{{ $d->units ?: '—' }}</td>
                                            <td>
                                                {{ $d->decision === 'keep' ? 'Kept, ask again ' . optional($d->next_check_on)->format('d M Y') : 'Waiting' }}
                                                @if($d->last_error)<br><small class="text-danger">Last release failed: {{ $d->last_error }}</small>@endif
                                            </td>
                                            <td class="text-nowrap">
                                                <a href="javascript:void(0)" class="btn btn-themeBlue btn-sm release-decide" data-id="{{ $d->id }}" data-action="vacate">Yes, vacate</a>
                                                <a href="javascript:void(0)" class="btn btn-themeSkyblue btn-sm release-decide" data-id="{{ $d->id }}" data-action="keep">Keep</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-scripts')
<script>
    $(document).on('click', '.release-decide', function () {
        var action = $(this).data('action');
        if (action === 'vacate' && !confirm('Vacate this employee\'s bed / room / villa now?')) return;
        $.post("{{ url('resort/accommodation/release-decisions') }}/" + $(this).data('id'),
            { _token: "{{ csrf_token() }}", action: action })
            .done(function (r) {
                toastr.success(r.message || 'Done.', 'Success', { positionClass: 'toast-bottom-right' });
                setTimeout(function () { location.reload(); }, 500);
            })
            .fail(function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Something went wrong', 'Error', { positionClass: 'toast-bottom-right' });
            });
    });
</script>
@endsection
