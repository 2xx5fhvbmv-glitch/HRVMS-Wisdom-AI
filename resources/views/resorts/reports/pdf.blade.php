@php
    // Every report PDF uses the shared document design. A report with its own layout names it via $layout
    // (passed by the report's export, see PredefinedReportActions::exportComputedReport); everything else is generic.
    $docView = in_array($layout ?? null, ['labor_cost_schedule', 'expat_register'], true) ? $layout : 'generic_report';
@endphp
@include('resorts.partials.documents.' . $docView)
