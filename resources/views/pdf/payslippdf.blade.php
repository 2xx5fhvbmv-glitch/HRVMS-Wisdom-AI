<html>

<head>
    <title>Pdf</title>
    <style>
        @font-face {
            font-family: Poppins;
            src: url('{{ public_path('Poppins-Bold.eot') }}');   
            src: url('{{ public_path('fonts/Poppins-Bold.eot?#iefix') }}') format('embedded-opentype'),url('{{ public_path('fonts/Poppins-Bold.woff2') }}') format('woff2'),url('{{ public_path('fonts/Poppins-Bold.woff') }}') format('woff'),url('{{ public_path('fonts/Poppins-Bold.ttf') }}') format('truetype'),url('{{ public_path('fonts/Poppins-Bold.svg#Poppins-Bold') }}') format('svg');
            font-weight: 700;
            font-style: normal;
            font-display: swap
        }

        @font-face {
            font-family: Poppins;
            src: url('{{ public_path('Poppins-SemiBold.eot') }}');
            src: url('{{ public_path('Poppins-SemiBold.eot?#iefix') }}') format('embedded-opentype'),url('{{ public_path('fonts/Poppins-Bold.woff2') }}') format('woff2'),url('{{ public_path('fonts/Poppins-SemiBold.woff') }}') format('woff'),url('{{ public_path('fonts/PPoppins-SemiBold.ttf') }}') format('truetype'),url('{{ public_path('fonts/Poppins-SemiBold.svg#Poppins-SemiBold') }}') format('svg');
            font-weight: 600;
            font-style: normal;
            font-display: swap
        }

        @font-face {
            font-family: Poppins;
            src: url('{{ public_path('Poppins-Regular.eot') }}');
            src: url('{{ public_path('Poppins-Regular.eot?#iefix') }}') format('embedded-opentype'),url('{{ public_path('fonts/Poppins-Regular.woff2') }}') format('woff2'),url('{{ public_path('fonts/Poppins-Regular.woff') }}') format('woff'),url('{{ public_path('fonts/PPoppins-Regular.ttf') }}') format('truetype'),url('{{ public_path('fonts/Poppins-Regular.svg#Poppins-Regular') }}') format('svg');
            font-weight: 400;
            font-style: normal;
            font-display: swap
        }

        @font-face {
            font-family: Poppins;
            src: url('{{ public_path('Poppins-Medium.eot') }}');
            src: url('{{ public_path('Poppins-Medium.eot?#iefix') }}') format('embedded-opentype'),url('{{ public_path('fonts/Poppins-Medium.woff2') }}') format('woff2'),url('{{ public_path('fonts/Poppins-Medium.woff') }}') format('woff'),url('{{ public_path('fonts/PPoppins-Medium.ttf') }}') format('truetype'),url('{{ public_path('fonts/Poppins-Medium.svg#Poppins-Medium') }}') format('svg');
            font-weight: 500;
            font-style: normal;
            font-display: swap
        }

        @font-face {
            font-family: Poppins;
            src: url('{{ public_path('Poppins-Light.eot') }}');
            src: url('{{ public_path('Poppins-Light.eot?#iefix') }}') format('embedded-opentype'),url('{{ public_path('fonts/Poppins-Light.woff2') }}') format('woff2'),url('{{ public_path('fonts/Poppins-Light.woff') }}') format('woff'),url('{{ public_path('fonts/PPoppins-Light.ttf') }}') format('truetype'),url('{{ public_path('fonts/Poppins-Light.svg#Poppins-Light') }}') format('svg');
            font-weight: 300;
            font-style: normal;
            font-display: swap
        }

        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        table {
            color: #303030;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.5;
            border-collapse: collapse;
            letter-spacing: 1.4px;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        .pdf-container {
            width: 430px;
            margin: 50px auto;
            padding: 0;
            /* background-color: white;
            border: 1px solid #dcdcdc;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); */
        }
    </style>
</head>

<body>
    <div class="pdf-container">
        <table style="width: 100%;font-family: 'Poppins', sans-serif;border-spacing: 0;background-color:#F5F8F8;">
            <tr>
                <td style="padding: 25px;">
                    <div style="width: 100%;background-color: #fff;margin: 0 0 24px 0;border-radius: 20px;overflow: hidden;">
                        <table style="width: 100%;border-spacing: 0;">
                            <tr>
                                <td style="padding: 24px 26px 24px 26px;">
                                    <table style="width: 100%;border-spacing: 0;">
                                        <tr>
                                            <td colspan="2" style="padding: 0 0 20px 0;border-bottom: 1px solid #3030301A">
                                                <table style="width: 100%;border-spacing: 0;padding: 0;">
                                                    <tr>
                                                        <td rowspan="3" style="width: 70px;">
                                                            <div style="width: 70px;height: 70px;border-radius: 50%;overflow: hidden;">   
                                                                <img src="{{ $payrollArray['profile_image'] }}" alt="user" style="width: 70px; height: 70px;">
                                                            </div>
                                                        </td>
                                                        <td style="padding: 0 0 3px 13px;font-size: 16px;font-weight: 600;">
                                                            {{$payrollArray['employee']['first_name']}} {{$payrollArray['employee']['last_name']}} 
                                                            <small style="opacity: .6;;font-size: 11px;">({{$payrollArray['employee']['Emp_id']}})</small>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="font-size: 11px;font-weight: 500;opacity: .8;padding: 0 0 2px 13px;">
                                                            {{$payrollArray['employee']['position']}}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="font-size: 11px;font-weight: 500;opacity: .3;;padding: 0 0 0 13px;">
                                                            {{ \Carbon\Carbon::parse($payrollArray['employee']['start_date'])->format('d M Y') }} To 
                                                            {{ \Carbon\Carbon::parse($payrollArray['employee']['end_date'])->format('d M Y') }}
                                                        </td>

                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 500;padding: 16px 4px 16px 0;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                Department:
                                            </td>

                                            <td  style="padding: 16px 0 16px 4px;text-align: right;opacity: .6;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                {{$payrollArray['employee']['department']}}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td
                                                style="font-weight: 500;padding: 16px 4px 16px 0;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                Joining Date:
                                            </td>

                                            <td style="padding: 16px 0 16px 4px;text-align: right;opacity: .6;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                {{ \Carbon\Carbon::parse($payrollArray['employee']['joining_date'])->format('d M Y') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 500;padding: 16px 4px 0 0;">
                                                Day Work:
                                            </td>
                                            
                                            <td style="padding: 16px 0 0 4px;text-align: right;opacity: .6;">
                                                {{$payrollArray['employee']['daywork']}}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div style="width: 100%;background-color: #0147531A;margin: 0 0 20px 0;border-radius: 20px;overflow: hidden;">
                        <table style="width: 100%;border-spacing: 0;">
                            <tr>
                                <td style="padding: 24px 26px 24px 26px;">
                                    <table style="width: 100%;border-spacing: 0;color: #014753;">
                                        <tr>
                                            <td style="font-weight: 500;padding: 0 4px 16px 0;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                Bank:
                                            </td>

                                            <td style="padding: 0 0 16px 4px;text-align: right;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                Bank Of Maldives
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 500;padding: 16px 4px 16px 0;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                Account No:
                                            </td>

                                            <td style="padding: 16px 0 16px 4px;text-align: right;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                1542112454214
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 500;padding: 16px 4px 16px 0;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                Total Amount:
                                            </td>

                                            <td style="padding: 16px 0 16px 4px;text-align: right;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                {{ !empty($payrollArray['bank_details']['total_amount']) ? '$' . $payrollArray['bank_details']['total_amount'] : '' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 500;padding: 16px 4px 0 0;white-space: nowrap;vertical-align: top;">
                                                Total Amount in word:
                                            </td>

                                            <td style="padding: 16px 0 0 4px;text-align: right;vertical-align: top;">
                                                Nine Hundred USD Dollars
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <table style="width: 100%;border-spacing: 0;margin: 0 0 12px 0;">
                        <tr>
                            <td style="font-size: 18px;font-weight: 500;letter-spacing: normal;">Earnings Breakdown
                            </td>
                        </tr>
                    </table>
                    <div style="width: 100%;background-color: #fff;margin: 0 0 20px 0;border-radius: 20px;overflow: hidden;">
                        <table style="width: 100%;border-spacing: 0;">
                            <tr>
                                <td style="padding: 24px 26px 24px 26px;">
                                    <table style="width: 100%;border-spacing: 0;">
                                        <tr>
                                            <td style="font-weight: 500;padding: 0 4px 16px 0;vertical-align: top;white-space: nowrap;border-bottom: 1px solid #3030301A">
                                                Basic Salary:
                                            </td>

                                            <td style="padding: 0 0 16px 4px;text-align: right;opacity: .6;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                {{ !empty($payrollArray['earning_details']['basic_pay']) ? '$' . $payrollArray['earning_details']['basic_pay'] : '' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 500;padding: 16px 4px 16px 0;vertical-align: top;white-space: nowrap;border-bottom: 1px solid #3030301A">
                                                Allowances:
                                            </td>

                                            <td style="padding: 16px 0 16px 4px;text-align: right;opacity: .6;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                {{ !empty($payrollArray['earning_details']['allowance']) ? '$' . $payrollArray['earning_details']['allowance'] : '' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 500;padding: 16px 4px 16px 0;vertical-align: top;white-space: nowrap;border-bottom: 1px solid #3030301A">
                                                Bonuses:
                                            </td>

                                            <td style="padding: 16px 0 16px 4px;text-align: right;opacity: .6;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                {{ !empty($payrollArray['earning_details']['bonus']) ? '$' . $payrollArray['earning_details']['bonus'] : '' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600;padding: 16px 4px 0 0; vertical-align: top; white-space: nowrap;">
                                                Total Earnings:
                                            </td>

                                            <td style="font-weight: 500;padding: 16px 0 0 4px;text-align: right;vertical-align: top;">
                                                ${{$payrollArray['earning_details']['earning_total_amount']}}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <table style="width: 100%;border-spacing: 0;margin: 0 0 12px 0;">
                        <tr>
                            <td style="font-size: 18px;font-weight: 500;letter-spacing: normal;">Deductions</td>
                        </tr>
                    </table>
                    <div style="width: 100%;background-color: #fff;margin: 0 0 20px 0;border-radius: 20px;overflow: hidden;">
                        <table style="width: 100%;border-spacing: 0;">
                            <tr>
                                <td style="padding: 24px 26px 24px 26px;">
                                    <table style="width: 100%;border-spacing: 0;">
                                        <tr>
                                            <td style="font-weight: 500;padding: 0 4px 16px 0;vertical-align: top;white-space: nowrap;border-bottom: 1px solid #3030301A">
                                                Monthly Tax Deduction:
                                            </td>

                                            <td style="padding: 0 0 16px 4px;text-align: right;opacity: .6;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                {{ !empty($payrollArray['deductions_details']['monthly_tax_deduction']) ? '$' . $payrollArray['deductions_details']['monthly_tax_deduction'] : '' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 500;padding: 16px 4px 16px 0;vertical-align: top;white-space: nowrap;border-bottom: 1px solid #3030301A">
                                                Insurance:
                                            </td>

                                            <td style="padding: 16px 0 16px 4px;text-align: right;opacity: .6;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                {{ !empty($payrollArray['deductions_details']['insurance']) ? '$' . $payrollArray['deductions_details']['insurance'] : '' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 500;padding: 16px 4px 16px 0;vertical-align: top;white-space: nowrap;border-bottom: 1px solid #3030301A">
                                                Loans:
                                            </td>

                                            <td style="padding: 16px 0 16px 4px;text-align: right;opacity: .6;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                {{ !empty($payrollArray['deductions_details']['loans']) ? '$' . $payrollArray['deductions_details']['loans'] : '' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 500;padding: 16px 4px 16px 0;vertical-align: top;white-space: nowrap;border-bottom: 1px solid #3030301A">
                                                City Ledger
                                            </td>

                                            <td style="padding: 16px 0 16px 4px;text-align: right;opacity: .6;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                {{ !empty($payrollArray['deductions_details']['city_ledger']) ? '$' . $payrollArray['deductions_details']['city_ledger'] : '' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600;padding: 16px 4px 0 0; vertical-align: top; white-space: nowrap;">
                                                Total Deductions
                                            </td>

                                            <td style="font-weight: 500;padding: 16px 0 0 4px;text-align: right;vertical-align: top;">
                                                {{ !empty($payrollArray['deductions_details']['total_deductions']) ? '$' . $payrollArray['deductions_details']['total_deductions'] : '' }}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div style="width: 100%;background-color: #fff;margin: 0 ;border-radius: 20px;overflow: hidden;">
                        <table style="width: 100%;border-spacing: 0;">

                            <td style="padding: 24px 26px 24px 26px;">
                                <table style="width: 100%;border-spacing: 0;">
                                    <tr>
                                        <td style="font-weight: 600;padding: 0 4px 0 0; vertical-align: top; white-space: nowrap;">
                                            Net Salary
                                        </td>

                                        <td style="font-weight: 500;padding: 0 0 0 4px;text-align: right;vertical-align: top;">
                                            ${{$payrollArray['net_salary']}}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>

