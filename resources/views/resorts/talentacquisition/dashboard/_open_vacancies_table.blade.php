{{--
    Shared "Open Vacancies" table — admin/hr dashboards both @include this
    with the SAME $NewVacancies collection they already compute (see
    TalentAcquisitionDashboardController::admin_dashboard()/hr_dashboard()).
    No new queries, same "View" action, same destination. hoddashboard.blade.php
    has its own simpler 2-column table (Position/No. of Vacancy — it never
    receives $NewVacancies), restyled separately with the same .vac-table-v2
    classes for a consistent look.
--}}
<div class="card h-auto" id="card-vac">
    <div class="card-title">
        <div class="row justify-content-between align-items-center g-">
            <div class="col">
                <h3>Vacancies</h3>
            </div>
            <div class="col-auto">
                <a href="{{ route('resort.vacancies.FreshApplicant') }}" class="a-link">View all</a>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-collapse table-vacRec vac-table-v2">
            <thead>
                <tr>
                    <th>Position</th>
                    <th>Department</th>
                    <th class="vac-col-action">Vac.</th>
                    <th class="vac-col-action">Applic.</th>
                    <th class="vac-col-action">Action</th>
                </tr>
            </thead>
            <tbody>
            @if(isset($NewVacancies) && $NewVacancies->isNotEmpty())
                @foreach ($NewVacancies as $vac)
                    <tr>
                        <td>{{ $vac->positionTitle }}
                            <span class="badge badge-themeLight">{{ $vac->PositonCode }}</span>
                        </td>
                        <td>{{ $vac->Department }}</td>
                        <td class="vac-col-num">{{ $vac->NoOfVacnacy }}</td>
                        <td class="vac-col-num @if(!$vac->NoOfApplication) vac-col-muted @endif">{{ $vac->NoOfApplication }}</td>
                        <td class="vac-col-action"><a href="{{ route('resort.ta.Applicants', base64_encode($vac->vacancy_id)) }}" class="eye-btn"><i class="fa-regular fa-eye"></i></a>
                        </td>
                    </tr>
                @endforeach
            @endif
            </tbody>
        </table>
    </div>
</div>
