
@if($NewVacancies->isNotEmpty())
    <div class="row g-4 mb-4">
    @foreach($NewVacancies as $v)
        <div class="col-xxl-cust5 col-lg-4  col-sm-6">
            <div class="vacanciesGrid-block">
                <div class="heading">
                    <h6>{{ $v->positionTitle }} @if($v->is_closed ?? false)<span class="badge badge-themeDanger ms-1">Closed</span>@endif</h6>
                </div>
                <div class="bg">
                    <div>
                        <p>No. of Positions</p>
                        <p>{{ $v->NoOfVacnacy }}</p>
                    </div>
                    <div>
                        <p>Applicants</p>
                        <p>{{ $v->NoOfApplication }}</p>
                    </div>
                </div>
                <table>
                    <tr>
                        <th>Department:</th>
                        <td>{{ $v->Department }}</td>
                    </tr>
                    {{-- <tr>
                        <th>Section:</th>
                        <td>{{ $v->position_title }}</td>
                    </tr>
                    <tr>
                        <th>Section Code:</th>
                        <td>{{ $v->position_title }}</td>
                    </tr> --}}
                    <tr>
                        <th>Job Ad Poster:</th>
                        <td>
                            <i class="fa-solid fa-image" style="font-size:18px;color:#8a8a80;"></i>
                        </td>
                    </tr>
                    <tr>
                        <th>Link Expiry Date:</th>
                        <td>{{ $v->ExpiryDate }}</td>
                    </tr>
                </table>

                <div class="text-center mt-2">
                    <a href="{{route('resort.ta.Applicants',base64_encode( $v->vacancy_id)) }}" class="btn btn-sm ta-btn-secondary me-1" data-bs-toggle="tooltip" data-bs-placement="top" title="View Applicants"><i class="fa-solid fa-eye"></i></a>
                    @if($canSeeAction)
                    @if(!($v->is_closed ?? false))
                    <a href="javascript:void(0)" data-id="{{ $v->vacancy_id }}" data-ExpiryDate="{{ $v->link_Expiry_date }}" data-ApplicationId="{{ $v->application_id }}" class="btn btn-sm ta-btn-attention ExtendJobLink" data-bs-toggle="tooltip" data-bs-placement="top" title="Extend the Job Ad Link"><i class="fa-solid fa-calendar-plus"></i></a>
                    @endif
                    <a href="javascript:void(0)" class="btn btn-sm ta-btn-secondary viewJobAd ms-1" data-vacancy-id="{{ $v->vacancy_id }}" data-position="{{ $v->positionTitle }}" data-joblink="{{ $v->public_link ?? $v->jobAdLink ?? '' }}" data-alljobimages='{{ json_encode($v->allJobAdImages) }}' data-bs-toggle="tooltip" data-bs-placement="top" title="View Job Advertisement"><i class="fa-solid fa-rectangle-ad"></i></a>
                    @if($v->public_link ?? null)
                    <a href="javascript:void(0)" class="btn btn-sm ta-btn-secondary copyApplyLink ms-1" data-link="{{ $v->public_link }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Copy application link"><i class="fa-solid fa-copy"></i></a>
                    @endif
                    @if($v->is_closed ?? false)
                    <a href="javascript:void(0)" class="btn btn-sm ta-btn-attention reopenVacancyBtn ms-1" data-id="{{ $v->vacancy_id }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Reopen vacancy"><i class="fa-solid fa-lock-open"></i></a>
                    @else
                    <a href="javascript:void(0)" class="btn btn-sm ta-btn-secondary closeVacancyBtn ms-1" data-id="{{ $v->vacancy_id }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Close vacancy"><i class="fa-solid fa-lock"></i></a>
                    @endif
                    @endif
                </div>
            </div>
        </div>
    @endforeach
    </div>
@else
<div class="row g-4">
    <div class="col-sm-12">
        <div class="vacanciesGrid-block">
                <h6>No Record Found</h6>
        </div>
    </div>
</div>
@endif


</div>

<nav aria-label="Page navigation example">

    <ul class="pagination justify-content-end">

    </ul>
</nav>
