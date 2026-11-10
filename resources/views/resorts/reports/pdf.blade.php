@php
    // Every report PDF uses the shared document design. Two reports have their own layouts; all others use the generic one.
    $docView = 'generic_report';
    if (($report->name ?? '') === 'Annual Workforce Plan' && in_array('Total Budget', $columns ?? [], true) && in_array('Planned Headcount', $columns, true)) {
        $docView = 'labor_cost_schedule';
    } elseif (($report->name ?? '') === 'Expat Employee Register' && in_array('Employee ID', $columns ?? [], true) && in_array('Nationality', $columns, true)) {
        $docView = 'expat_register';
    }
@endphp
@include('resorts.partials.documents.' . $docView)
