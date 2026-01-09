<html>

<head>
    <title>Benefit Grid PDF</title>
    <style>
    @font-face {
        font-family: 'Poppins';
        src: url('{{ 'file:///' . str_replace("\\", "/", public_path('resort_assets/fonts/Poppins-Bold.ttf')) }}') format('truetype');
        font-weight: 700;
        font-style: normal;
    }

    @font-face {
        font-family: 'Poppins';
        src: url('{{ 'file:///' . str_replace("\\", "/", public_path('resort_assets/fonts/Poppins-SemiBold.ttf')) }}') format('truetype');
        font-weight: 600;
        font-style: normal;
    }

    @font-face {
        font-family: 'Poppins';
        src: url('{{ 'file:///' . str_replace("\\", "/", public_path('resort_assets/fonts/Poppins-Regular.ttf')) }}') format('truetype');
        font-weight: 400;
        font-style: normal;
    }

    @font-face {
        font-family: 'Poppins';
        src: url('{{ 'file:///' . str_replace("\\", "/", public_path('resort_assets/fonts/Poppins-Medium.ttf')) }}') format('truetype');
        font-weight: 500;
        font-style: normal;
    }

    @font-face {
        font-family: 'Poppins';
        src: url('{{ 'file:///' . str_replace("\\", "/", public_path('resort_assets/fonts/Poppins-Light.ttf')) }}') format('truetype');
        font-weight: 300;
        font-style: normal;
    }

    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 0;
    }

    table {
        font-size: 14px;
        font-weight: 400;
        border-collapse: collapse;
        width: 100%;
    }

    .pdf-container {
        width: 210mm;
        margin: 50px auto;
        padding: 0;
        background-color: white;
        border: 1px solid #dcdcdc;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    /* Optional table styling */
    table, th, td {
        padding: 8px;
    }

    th {
        background-color: #f2f2f2;
        text-align: left;
    }
</style>


</head>


<body>
    <div class="pdf-container">
        <table
            style="width: 100%;font-family: 'Poppins', sans-serif;border-spacing: 0;background-color: hsla(190, 98%, 16%, 0.05);border-collapse: collapse;border-spacing: 0px;display: table;">
             <tr>
                <td
                    style="background-image: url(assets/images/favicon-32x32.png);background-repeat: repeat-x; background-position: center center; padding: 8px;">
                </td>
            </tr>
            <tr>
                <td style="background-color: #014653;padding: 0;vertical-align: text-top;">
                    <table
                        style="width: 100%;border-collapse: collapse;border-spacing: 0;display: table;vertical-align: text-top;">
                        <tr>
                            <td style="padding: 15px 15px 15px 15px; vertical-align: top;" rowspan="2">
                                <img src="{{ Common::GetResortLogo($ResortData->id) }}"
                                    alt="Logo" style="width: 150px;"></td>
                             <td
                                style="color: #fff;font-size: 20px;font-weight: 400;line-height: 1.4;text-transform: capitalize;padding: 15px ;text-align: right;vertical-align: top;">
                                {{$ResortData->resort_name}}
                                <span
                                    style="color: #fff;font-size: 14px;font-weight: 400;    line-height: 1.5;text-align: right;display: block; padding-top: 4px;">
                                        {{$ResortData->address1}},{{$ResortData->address2}},</br>
                                        {{$ResortData->state}} - {{$ResortData->city}}, {{$ResortData->zip}}, </br>
                                        {{$ResortData->country}}
                                </span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
              <tr>
                <td style="background-color: hsla(190, 98%, 16%, 0.05);padding:10px;vertical-align: text-top;">
                    <table
                        style="width: 100%;border-collapse: collapse;border-spacing: 0;display: table;vertical-align: text-top;">
                       
                        <tr>
                            <td style="background-color: #fff;padding:8px 12px 8px 16px;width: 50%" valign="top">
                                <table
                                    style="width: 100%;border-collapse: collapse;border-spacing: 0;display: table;vertical-align: text-top;">
                                    <tr>
                                        <th
                                        style="font-size: 20px;font-weight: 600;text-align: center;">
                                            BENEFIT GRID PDF
                                        </th>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>   
            <tr>
                <td style="background-color: hsla(190, 98%, 16%, 0.05);padding:10px;vertical-align: text-top;">
                    <table
                        style="width: 100%;border-collapse: collapse;border-spacing: 0;display: table;vertical-align: text-top;">
                        <tr>
                            <td style="background-color: #fff;padding:12px 16px 12px 12px;width: 50%;" valign="top">
                                <table
                                    style="width: 100%; border-spacing: 0;font-size: 12px;font-weight: 400;vertical-align: text-top; ">
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:0px 4px 6px; width: 70%;">
                                            Grade of the Benefit Grid
                                        </td>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:0px 4px 6px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->emp_grade}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Salary Paid In:
                                        </td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->salary_paid_in}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Effective Date:</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                                {{$benefit_grid->effective_date}}                                
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Day Off Per Week (In Days)</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->day_off_per_week}}
                                        </td>
                                    </tr>
                                     <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Public Holiday Per Year (In Days)</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->public_holiday_per_year}}
                                        </td>
                                    </tr>
                                    @if($benefitGridChildren)
                                        @foreach($benefitGridChildren as $index => $child)
                                            <!-- Start a new row for every two items -->
                                            <tr>
                                                <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                                    {{ $child->leave_category_name ?? 'N/A' }} (In Days)
                                                </td>
                                                <td
                                                    style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                                    {{ $child->allocated_days ?? 'N/A' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Rate For Paid Worked Public Holiday and Friday</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->paid_worked_public_holiday_and_friday}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Accommodation Status</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->accommodation_status}}
                                        </td>
                                    </tr>
                                     <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Housekeeping</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->housekeeping}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Linen
                                        </td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                                {{$benefit_grid->linen}}                                        
                                        </td>
                                    </tr>
                                      <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Uniform</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->uniform}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Telephone</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->telephone}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Laundry</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->laundry}}
                                        </td>
                                    </tr>
                                     <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Relocation Tickets to Maldives</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->relocation_ticket}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">Annual Leave ticket to/from POH</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->annual_leave_ticket}}
                                        </td>
                                    </tr>
                                 </table>
                            </td>
                            <td style="background-color: #fff;padding:12px 12px 12px 16px;width: 50%" valign="top">
                                <table style="width: 100%; border-spacing: 0;font-size: 12px;font-weight: 400; ">
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:0px 4px 6px; width: 70%;">
                                            Salary Period
                                        </td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:0px 4px 6px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->salary_period}}
                                        </td>
                                    </tr>
                                     <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Contract Status</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->contract_status}}
                                        </td>
                                    </tr>
                                     <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Overtime</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->overtime}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Working Hours per week (In Hours)</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->working_hrs_per_week}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Incentives Service Charge</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->service_charge == 1 ? 'Yes' : 'No'}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Furnitures and Fixtures</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->furniture_and_fixtures}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Internet Access</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->internet_access}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Staff Loan & salary advance</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->loan_and_salary_advanced}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Health Care & Insurance
                                        </td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->health_care_insurance}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Ramadan Bonus (In MVR)</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->ramadan_bonus}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Ramadan Eligibilty</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->ramadan_bonus_eligibility}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">Meals Per Day</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->meals_per_day}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Alchoholic Beverages Discount(In %)</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->alchoholic_beverages_discount}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">Spa Discount(In %)</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->spa_discount}}
                                        </td>
                                    </tr>
                                     <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">Dive Center Discount(In %)</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->dive_center_discount}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">Water Sports Discount(In %)</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->water_sports_discount}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">Food And Beverages Discount(In %)</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->food_and_beverages_discount}}
                                        </td>
                                    </tr>
                                  
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            SPORTS, RECREATION & ENTERTAINMENT FACILITIES</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->sports_and_entertainment_facilities}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">Staff  Rate (For Single)</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->standard_staff_rate_for_single}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">Staff  Rate (For Double)</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                           {{$benefit_grid->standard_staff_rate_for_double}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Friends With Benefit Discount(In %)</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->friends_with_benefit_discount}}
                                        </td>
                                    </tr>
                                     <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">
                                            Staff rate (seaplane) to/from Male (In Dollars)</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->staff_rate_for_seaplane_male}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">Ticket upon termination</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->ticket_upon_termination}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">Free return flight to Male Per Year(In Number)</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->free_return_flight_to_male_per_year}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">MALE Subsistence Allowance(In Dollars)</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->male_subsistence_allowance}}
                                        </td>
                                    </tr>
                                     <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">Maximum Excess luggage relocation expenses (In Dollars)</td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->max_excess_luggage_relocation_expense}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 70%;">Status
                                        </td>
                                        <td
                                            style="border-bottom: 1px solid #E7E7E7;padding:6px 4px; width: 130px;text-align: right;font-weight: 500">
                                            {{$benefit_grid->status}}
                                        </td>
                                    </tr>
                                </table>
                            </td>    
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="background-color: hsla(190, 98%, 16%, 0.05);padding:10px;vertical-align: text-top;">
                    <table
                        style="width: 100%;border-collapse: collapse;border-spacing: 0;display: table;vertical-align: text-top;">
                        <tr>
                            <td style="background-color: #fff;padding:12px 16px 12px 12px;width: 50%;" valign="top">
                                <table style="width: 100%; border-spacing: 0;font-size: 12px;font-weight: 400;">
                                </table>
                            </td>
                            <td style="background-color: #fff;padding:12px 12px 12px 16px;width: 50%" valign="top">
                                <table style="width: 100%; border-spacing: 0;font-size: 12px;font-weight: 400;">
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td
                    style="background-color:    #014653;color: #fff;font-size: 12px;font-weight: 400; line-height: 21px;padding:8px 12px 8px 12px ;text-align: center;"> {!!$sitesettings->Footer !!}.
                </td>
            </tr>
        </table>
    </div>
</body>
<script>
    window.onload = function () {
        window.print();
    }
</script>
</html>