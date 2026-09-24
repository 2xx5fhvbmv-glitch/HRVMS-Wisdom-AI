@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
    <div class="body-wrapper pb-5">
        <div class="container-fluid">
            <div class="page-hedding">
                <div class="row g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>People</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto ms-auto">
                        <a class="btn eb-btn-hero" href="{{ route('people.compliance.index') }}">Back to Compliance</a>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div id="complianceCalendar"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-scripts')
<script>
    $(document).ready(function () {
        $('#complianceCalendar').fullCalendar({
            header: { left: 'prev,next today', center: 'title', right: 'month,listMonth' },
            events: @json($compliances),
            eventRender: function (event, element) {
                element.attr('title', (event.employee_name || '') + ' - ' + (event.status || '') +
                    (event.description ? ' - ' + event.description : ''));
            }
        });
    });
</script>
@endsection
