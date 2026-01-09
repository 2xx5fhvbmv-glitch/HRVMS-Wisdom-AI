
@if($Applicant_form_data->isNotEmpty())
    <div class="row g-4 mb-4">
    @foreach($Applicant_form_data as $a)
    <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6">
        <div class="talentPoolGrid-block">
            <div class="dropdown table-dropdown ">
                <button class="btn btn-secondary dropdown-toggle dotsV-link" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                    {{-- <li><a class="dropdown-item" href="#">View</a></li> --}}
                    <li><a class="dropdown-item RejactionReason  @if(App\Helpers\Common::checkRouteWisePermission('resort.ta.TalentPool',config('settings.resort_permissions.edit')) == false) d-none @endif" data-Rank="{{ $a->As_ApprovedBy }}" data-applicant_status_id="{{ $a->applicant_status_id }}"  data-Comments="{{ $a->Comments }}" href="javascript:void(0)">Rejection Reason</a></li>
                    <li><a class="dropdown-item destoryApplicant  @if(App\Helpers\Common::checkRouteWisePermission('resort.ta.TalentPool',config('settings.resort_permissions.delete')) == false) d-none @endif" href="javascript:void(0)"    data-location="{{$a->id}}" data-id="{{ base64_encode($a->id) }}">Delete</a></li>
                </ul>
            </div>
            <div class="img-circle"><img src="{{ URL::asset($a->passport_photo) }}" alt="image"></div>
            <h6>{{ ucfirst($a->first_name) }} {{ ucfirst($a->last_name) }}</h6>
            <p>{{ ucfirst($a->position_title) }}</p>
            <a href="#" class="a-link text-success">Check Availability</a>
            <div class="bg">
                <div>
                    <p>AI Ranking</p>
                    <p>{{ $a->AIRanking }}</p>
                </div>
                <div>
                    <p>Scoring</p>
                    <p>{{ $a->Scoring }}</p>
                </div>
            </div>
            <table>
                <tbody>
                    <tr>
                        <th>Department:</th>
                        <td>{{$a->Department}}</td>
                    </tr>
                    <tr>
                        <th>Documents::</th>
                        <td><a target="_blank" href="{{URL::asset($a->curriculum_vitae)  }}" class="a-link">Curroculum Vitae</a>
                            <a target="_blank" href="{{ URL::asset($a->passport_img)}}" class="a-link">Passport</a>
                           </td>
                    </tr>
                    <tr>
                        <th>Consent Expiry Date:</th>
                        <td>{{ $a->data_retention_month }} M/{{ $a->data_retention_year }} Y</td>
                    </tr>

                </tbody>
            </table>

            <div>
                <a href="mailto:{{ $a->email }}" class="btn btn-themeSkyblue btn-sm">Consent Request</a>
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
    {!! $pagination !!}
</nav>
