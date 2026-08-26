{{--
    Shared "Incident List" card widget — one CSS block + one JS render
    function, included by every dashboard that shows this card (currently
    HR and HOD; EXCOM reuses the HOD view; Admin/GM dashboard doesn't have
    this card at all). Data comes from the existing incident.todoList
    endpoint (DashboardController@getIncidentTodoList) — untouched query/
    ordering/scope, only a `category` field was added to its existing map().

    Markup/CSS match incident_list_reference.html: two lines per row
    (title + relative time, then category + "View details →"), no avatar
    tile, no status pill. Title's first letter is capitalized visually via
    ::first-letter — the stored incident_name is never mutated.

--}}
<style>
    /* Scoped under .inc-row (never bare .top/.bot/.cat/etc) — those are
       common enough names to collide with something else on a dashboard
       already packed with other widgets. */
    .inc-row { padding: 14px 0; border-bottom: 1px solid var(--line-2, #EEF4F4); }
    .inc-row:first-child { padding-top: 10px; }
    .inc-row:last-child { border-bottom: none; padding-bottom: 2px; }
    .inc-row .inc-top { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; }
    .inc-row .inc-title { font-size: 13.5px; font-weight: 500; color: var(--ink, #14232A); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .inc-row .inc-title::first-letter { text-transform: uppercase; }
    .inc-row .inc-time { font-size: 11px; color: var(--faint, #93A4A9); white-space: nowrap; flex: none; }
    .inc-row .inc-bottom { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 4px; }
    .inc-row .inc-cat { font-size: 11.5px; color: var(--muted, #6B7378); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .inc-row .inc-view { font-size: 11.5px; font-weight: 600; color: var(--teal, #014653); text-decoration: none; white-space: nowrap; flex: none; }
    .inc-row .inc-view:hover { text-decoration: underline; }
</style>
<script>
    const incidentDetailBaseUrl = "{{ route('incident.view', ['id' => 'INCIDENT_ID']) }}";

    function loadIncidentTodoList() {
        $.ajax({
            url: '{{ route("incident.todoList") }}',
            method: 'GET',
            success: function (data) {
                var html = '';
                if (!data || data.length === 0) {
                    html = '<div class="text-center py-3">No incidents found.</div>';
                } else {
                    data.forEach(function (incident) {
                        var href = incidentDetailBaseUrl.replace('INCIDENT_ID', btoa(incident.id));
                        html +=
                            '<div class="inc-row">' +
                                '<div class="inc-top">' +
                                    '<span class="inc-title">' + $('<div>').text(incident.title).html() + '</span>' +
                                    '<span class="inc-time">' + $('<div>').text(incident.time_ago).html() + '</span>' +
                                '</div>' +
                                '<div class="inc-bottom">' +
                                    '<span class="inc-cat">' + $('<div>').text(incident.category).html() + '</span>' +
                                    '<a href="' + href + '" class="inc-view">View details &rarr;</a>' +
                                '</div>' +
                            '</div>';
                    });
                }
                $('#incidentTodoList').html(html);
            },
            error: function () {
                $('#incidentTodoList').html('<div class="text-danger py-3 text-center">Error loading data.</div>');
            }
        });
    }
</script>
