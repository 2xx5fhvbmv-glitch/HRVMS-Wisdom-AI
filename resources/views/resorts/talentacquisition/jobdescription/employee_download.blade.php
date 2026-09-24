<html>

<head>
    <title>Pdf</title>
    <style>
        @font-face {
            font-family: Poppins;
            src: url('../fonts/Poppins-Bold.eot');
            src: url('../fonts/Poppins-Bold.eot?#iefix') format('embedded-opentype'), url('../fonts/Poppins-Bold.woff2') format('woff2'), url('../fonts/Poppins-Bold.woff') format('woff'), url('../fonts/Poppins-Bold.ttf') format('truetype'), url('../fonts/Poppins-Bold.svg#Poppins-Bold') format('svg');
            font-weight: 700;
            font-style: normal;
            font-display: swap
        }

        @font-face {
            font-family: Poppins;
            src: url('../fonts/Poppins-Regular.eot');
            src: url('../fonts/Poppins-Regular.eot?#iefix') format('embedded-opentype'), url('../fonts/Poppins-Regular.woff2') format('woff2'), url('../fonts/Poppins-Regular.woff') format('woff'), url('../fonts/Poppins-Regular.ttf') format('truetype'), url('../fonts/Poppins-Regular.svg#Poppins-Regular') format('svg');
            font-weight: 400;
            font-style: normal;
            font-display: swap
        }

        table {
            font-size: 14px;
            font-weight: 400;
            border-collapse: collapse;
        }

        body {
            margin: 0;
            padding: 0;
        }

        @page {
            margin: 12mm 12mm 14mm 12mm;
        }

        .pdf-container {
            width: 100%;
            margin: 0;
            padding: 0;
            background-color: white;
        }

        .jd-section-title {
            background-color: #014653;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            padding: 6px 15px;
        }

        .jd-info-table td {
            padding: 4px 15px;
            font-size: 13px;
            vertical-align: top;
        }

        .jd-body {
            padding: 12px 15px;
            font-size: 13px;
            font-family: 'Poppins', sans-serif;
        }

        .jd-sign-wrap {
            page-break-inside: avoid;
            padding: 12px 15px;
            font-family: 'Poppins', sans-serif;
        }

        .jd-sign-table {
            width: 100%;
            border-collapse: collapse;
        }

        .jd-sign-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 10px 0 0;
            font-size: 12px;
        }

        .jd-sign-heading {
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .jd-sign-img {
            height: 45px;
            max-width: 170px;
            display: block;
        }

        .jd-sign-line {
            border-bottom: 1px solid #333;
            width: 170px;
            height: 45px;
        }

        .jd-sign-row {
            margin-top: 4px;
        }

        .jd-sign-caption {
            font-size: 9px;
            color: #777;
            font-style: italic;
        }

        .jd-info-table td.jd-label {
            width: 180px;
            color: #555;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="pdf-container">
        <table id="tablePrint"
            style="width: 100%;font-family: 'Poppins', sans-serif;    border-spacing: 0;background-color: hsla(190, 98%, 16%, 0.05);">
            <tr>
                <td style="background-color: #014653;padding: 0;">
                    <table style="width: 100%;    border-spacing: 0;">
                        <tr>
                            <td rowspan="2" style="padding: 15px 30px 15px 15px;"><img src="{{ Common::GetResortLogo($ResortData->id) }}"
                                    alt="Logo" style="width: 150px;"></td>
                            <td
                                style="color: #fff;font-size: 30px;font-weight: 300;line-height: 38px;text-transform: capitalize;padding: 10px 15px 3px 20px;text-align: right;">
                                {{$ResortData->resort_name}}
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="color: #fff;font-size: 14px;font-weight: 400;    line-height: 21px;padding:3px 15px 15px  20px;text-align: right;">
                                {{$ResortData->address1}},
                                {{$ResortData->address2}},
                                {{$ResortData->state}},   {{$ResortData->city}},
                                {{$ResortData->zip}},  {{$ResortData->country}}</td>
                        </tr>
                    </table>
                </td>
            </tr>

            {{-- Section 1: Employer Information --}}
            <tr>
                <td class="jd-section-title">1. Employer Information</td>
            </tr>
            <tr>
                <td style="background-color:#fff;padding:0;">
                    <table class="jd-info-table" style="width:100%;">
                        <tr>
                            <td class="jd-label">Name</td>
                            <td>{{ $record->employer_name }}</td>
                        </tr>
                        <tr>
                            <td class="jd-label">Address</td>
                            <td>{{ $record->employer_address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="jd-label">Nationality</td>
                            <td>{{ $record->employer_nationality ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="jd-label">Type of Work</td>
                            <td>{{ $record->employer_type_of_work ?? '-' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>

            {{-- Section 2: Employee Information --}}
            <tr>
                <td class="jd-section-title">2. Employee Information</td>
            </tr>
            <tr>
                <td style="background-color:#fff;padding:0;">
                    <table class="jd-info-table" style="width:100%;">
                        <tr>
                            <td class="jd-label">Full Name</td>
                            <td>{{ $record->employee_full_name }}</td>
                        </tr>
                        <tr>
                            <td class="jd-label">Permanent Address</td>
                            <td>{{ $record->employee_permanent_address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="jd-label">Current Address</td>
                            <td>{{ $record->employee_current_address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="jd-label">Identity Card Number</td>
                            <td>{{ $record->employee_id_card_number ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="jd-label">Date of Birth</td>
                            <td>{{ optional($record->employee_dob)->format('d M Y') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="jd-label">Nationality</td>
                            <td>{{ $record->employee_nationality ?? '-' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>

        </table>

        {{-- Sections 3-7: the resort's own job-title/duties/place/hours content.
             Plain flowing div (not a table cell) so dompdf can split it across pages. --}}
        <div class="jd-body">
            {!! $j->jobdescription !!}
        </div>

        @php
            $employerSigUri = \App\Helpers\Common::signatureImageDataUri($record->employer_signature_path);
            $employeeSigUri = \App\Helpers\Common::signatureImageDataUri($record->employee_signature_path);
            $employerName = $record->employer_signatory_name ?: $record->employer_name;
        @endphp
        <div class="jd-sign-wrap">
            <table class="jd-sign-table">
                <tr>
                    <td>
                        <div class="jd-sign-heading">Employer</div>
                        <div class="jd-sign-row">Sign:</div>
                        @if ($employerSigUri)
                            <img src="{{ $employerSigUri }}" class="jd-sign-img" alt="signature">
                        @else
                            <div class="jd-sign-line"></div>
                        @endif
                        <div class="jd-sign-row">Name: {{ $employerName }}</div>
                        <div class="jd-sign-row">ID Card Number: {{ $record->employer_signatory_id_card_number ?: '-' }}</div>
                        <div class="jd-sign-row">Designation: {{ $record->employer_signatory_designation ?: '-' }}, {{ $record->employer_name }}</div>
                        <div class="jd-sign-row">Date: {{ $employerSigUri && $record->sent_at ? $record->sent_at->format('d M Y, h:i A') : '' }}</div>
                        @if ($employerSigUri)
                            <div class="jd-sign-caption">Electronically signed</div>
                        @endif
                    </td>
                    <td>
                        <div class="jd-sign-heading">Employee</div>
                        <div class="jd-sign-row">Sign:</div>
                        @if ($employeeSigUri)
                            <img src="{{ $employeeSigUri }}" class="jd-sign-img" alt="signature">
                        @else
                            <div class="jd-sign-line"></div>
                        @endif
                        <div class="jd-sign-row">Name: {{ $record->employee_full_name }}</div>
                        <div class="jd-sign-row">ID Card Number: {{ $record->employee_id_card_number ?: '-' }}</div>
                        <div class="jd-sign-row">Permanent Address: {{ $record->employee_permanent_address ?: '-' }}</div>
                        <div class="jd-sign-row">Date: {{ $employeeSigUri && $record->signed_at ? $record->signed_at->format('d M Y, h:i A') : '' }}</div>
                        @if ($employeeSigUri)
                            <div class="jd-sign-caption">Electronically signed</div>
                        @endif
                    </td>
                </tr>
            </table>
            <div class="jd-sign-caption" style="margin-top:10px;">Every page of this document must be signed; the last page must also be fingerprinted / stamped.</div>
        </div>

        <table style="width:100%;border-spacing:0;font-family:'Poppins',sans-serif;">
            <tr>
                <td style="background-color:    #014653;color: #fff;font-size: 14px;font-weight: 400; line-height: 21px;padding:10px 15px 10px 15px ;"> {!!$sitesettings->Footer ?? ''!!}.</td>
            </tr>
        </table>
    </div>
</body>

</html>
