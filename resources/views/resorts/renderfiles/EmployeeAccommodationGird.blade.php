@if($data->isNotEmpty())
    @foreach ($data as $d)
        <div class="col-xxl-cust5 col-xl-3 col-lg-4 col-sm-6" >

            <div class="empAccomGrid-block">
                <div class="img-circle"><img src="{{ $d->profileImg }}" alt="image"></div>
                <h6>{{ $d->EmployeeName }}</h6>
                <span class="badge badge-themeNew">{{ $d->Emp_id }}</span>
                <p>{{ $d->position_title }}</p>
                <div class="position">{{ $d->DepartmentName }}</div>
                <div class="bg">

                    @if($d->effected_date)
                    Since {{ $d->effected_date}}

                @endif
                </div>
                <table>
                    <tbody>
                        <tr>
                            <th>Type:</th>
                            <td>Room In Building</td>
                        </tr>
                        <tr>
                            <th>Location:</th>
                            <td>{{ $d->BName }}</td>
                        </tr>
                        <tr>
                            <th>Floor:</th>
                            <td>{{ $d->Floor }}</td>
                        </tr>
                        <tr>
                            <th>Room No.</th>
                            <td>{{ $d->Room }}</td>
                        </tr>
                        <tr>
                            <th>Room Type:</th>
                            <td>{{ $d->AccommodationName }}
                                {{-- <div class="user-ovImg">
                                    <div class="img-circle">
                                        <img src="{{ URL::asset('resorts_assets/images/user-2') }}.svg" alt="image">
                                    </div>
                                    <div class="img-circle">
                                        <img src="{{ URL::asset('resorts_assets/images/user-3') }}.svg" alt="image">
                                    </div>
                                </div> --}}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div>
                    <a href="   " class="btn btn-themeSkyblue btn-sm">View Details</a>
                </div>
            </div>

        </div>
    @endforeach
@else
<div class="col-md-12">
    <p style="text-align: center"> No Recored Found..!</p>
</div>
@endif
