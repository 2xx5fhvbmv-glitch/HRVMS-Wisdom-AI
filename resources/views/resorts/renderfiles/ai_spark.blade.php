{{-- Shared "AI / magic" sparkle glyph — the one AI indicator icon used
     everywhere in the app (WAI Insights, suggested-fix callouts, AI action
     buttons). Single-color via currentColor; set size/color via CSS on
     this element or a wrapper, never edit the paths. Kept identical to
     ComplianceController::AI_SPARK_SVG for the PHP-string-rendered
     DataTables cells that can't include a Blade partial per row. --}}
<svg class="ai-spark {{ $class ?? '' }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
    <path d="M12 2c.4 3.4 1.6 4.6 5 5-3.4.4-4.6 1.6-5 5-.4-3.4-1.6-4.6-5-5 3.4-.4 4.6-1.6 5-5z"/>
    <path d="M19 13c.2 1.7.8 2.3 2.5 2.5-1.7.2-2.3.8-2.5 2.5-.2-1.7-.8-2.3-2.5-2.5 1.7-.2 2.3-.8 2.5-2.5z"/>
</svg>
