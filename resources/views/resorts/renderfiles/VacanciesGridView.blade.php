
@if($NewVacancies->isNotEmpty())
    <div class="row g-4 mb-4">
    @foreach($NewVacancies as $v)
        <div class="col-xxl-cust5 col-lg-4  col-sm-6">
            <div class="vacanciesGrid-block">
                <div class="heading">
                    <h6>{{ $v->positionTitle }}</h6>
                    <p>Code: {{ $v->PositonCode }}</p>
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
                    <tr>
                        <th>Department Code:</th>
                        <td>{{ $v->DepartmentCode }}</td>
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
                        <th>job advertisement posters:</th>
                        <td>

                            <img src="{{ $v->image }}" alt="images">
                        </td>
                    </tr>
                    <tr>
                        <th>link expiry date:</th>
                        <td>{{ $v->ExpiryDate }}</td>
                    </tr>
                </table>

                @if($canSeeAction)
                <div class="text-center mt-2">
                    <a href="{{route('resort.ta.Applicants',base64_encode( $v->vacancy_id)) }}" class="btn btn-sm btn-themeBlue me-1" data-bs-toggle="tooltip" data-bs-placement="top" title="View Applicants"><i class="fa-solid fa-eye"></i></a>
                    <a href="javascript:void(0)" data-id="{{ $v->vacancy_id }}" data-ExpiryDate="{{$v->ExpiryDate}}" data-ApplicationId="{{ $v->ApplicationId}}" class="btn btn-sm btn-theme ExtendJobLink" data-bs-toggle="tooltip" data-bs-placement="top" title="Extend The Job Ad Link"><i class="fa-solid fa-link"></i></a>
                    <a href="javascript:void(0)" class="btn btn-sm btn-info viewJobAd ms-1" data-position="{{ $v->positionTitle }}" data-joblink="{{ $v->jobAdLink ?? '' }}" data-alljobimages='{{ json_encode($v->allJobAdImages) }}' data-bs-toggle="tooltip" data-bs-placement="top" title="View Job Advertisement"><i class="fa-solid fa-image"></i></a>
                </div>
                @endif
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
