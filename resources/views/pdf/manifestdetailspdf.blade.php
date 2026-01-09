<html>

<head>
    <title>Pdf</title>
    <style>
        @font-face {
            font-family: Poppins;
            src: url('fonts/Poppins-Bold.eot');
            src: url('fonts/Poppins-Bold.eot?#iefix') format('embedded-opentype'), url('fonts/Poppins-Bold.woff2') format('woff2'), url('fonts/Poppins-Bold.woff') format('woff'), url('fonts/Poppins-Bold.ttf') format('truetype'), url('fonts/Poppins-Bold.svg#Poppins-Bold') format('svg');
            font-weight: 700;
            font-style: normal;
            font-display: swap
        }

        @font-face {
            font-family: Poppins;
            src: url('fonts/Poppins-SemiBold.eot');
            src: url('fonts/Poppins-SemiBold.eot?#iefix') format('embedded-opentype'), url('fonts/Poppins-SemiBold.woff2') format('woff2'), url('fonts/Poppins-SemiBold.woff') format('woff'), url('fonts/Poppins-SemiBold.ttf') format('truetype'), url('fonts/Poppins-SemiBold.svg#Poppins-SemiBold') format('svg');
            font-weight: 600;
            font-style: normal;
            font-display: swap
        }

        @font-face {
            font-family: Poppins;
            src: url('fonts/Poppins-Regular.eot');
            src: url('fonts/Poppins-Regular.eot?#iefix') format('embedded-opentype'), url('fonts/Poppins-Regular.woff2') format('woff2'), url('fonts/Poppins-Regular.woff') format('woff'), url('fonts/Poppins-Regular.ttf') format('truetype'), url('fonts/Poppins-Regular.svg#Poppins-Regular') format('svg');
            font-weight: 400;
            font-style: normal;
            font-display: swap
        }

        @font-face {
            font-family: Poppins;
            src: url('fonts/Poppins-Medium.eot');
            src: url('fonts/Poppins-Medium.eot?#iefix') format('embedded-opentype'), url('fonts/Poppins-Medium.woff2') format('woff2'), url('fonts/Poppins-Medium.woff') format('woff'), url('fonts/Poppins-Medium.ttf') format('truetype'), url('fonts/Poppins-Medium.svg#Poppins-Medium') format('svg');
            font-weight: 500;
            font-style: normal;
            font-display: swap
        }

        @font-face {
            font-family: Poppins;
            src: url('fonts/Poppins-Light.eot');
            src: url('fonts/Poppins-Light.eot?#iefix') format('embedded-opentype'), url('fonts/Poppins-Light.woff2') format('woff2'), url('fonts/Poppins-Light.woff') format('woff'), url('fonts/Poppins-Light.ttf') format('truetype'), url('fonts/Poppins-Light.svg#Poppins-Light') format('svg');
            font-weight: 300;
            font-style: normal;
            font-display: swap
        }

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
                    <div
                        style="width: 100%;background-color: #fff;margin: 0 0 24px 0;border-radius: 20px;overflow: hidden;">
                        <table style="width: 100%;border-spacing: 0;">
                            <tr>
                                <td style="padding: 24px 26px 24px 26px;">
                                    <table style="width: 100%;border-spacing: 0;">
                                        <tr>
                                            <td
                                                style="font-weight: 500;padding: 0px 4px 16px 0;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                Manifest type:</td>
                                            <td
                                                style="padding: 0px 0 16px 4px;text-align: right;opacity: .6;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                {{ $ManifestListing->manifest_type }}</td>
                                        </tr>
                                        <tr>
                                            <td
                                                style="font-weight: 500;padding: 16px 4px 16px 0;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                Transportation Mode</td>
                                            <td
                                                style="padding: 16px 0 16px 4px;text-align: right;opacity: .6;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                {{ $ManifestListing->transportationMode->transportation_option }}</td>
                                        </tr>
                                        <tr>
                                            <td
                                                style="font-weight: 500;padding: 16px 4px 16px 0;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                Transportation name</td>
                                            <td
                                                style="padding: 16px 0 16px 4px;text-align: right;opacity: .6;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                {{ $ManifestListing->transportation_name }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 500;padding: 16px 4px 16px 0;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                Date</td>
                                            <td style="padding: 16px 0 16px 4px;text-align: right;opacity: .6;vertical-align: top;border-bottom: 1px solid #3030301A">
                                                {{ $ManifestListing->date }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 500;padding: 16px 4px 0 0;">
                                                Time</td>
                                            <td style="padding: 16px 0 0 4px;text-align: right;opacity: .6;">
                                                {{ $ManifestListing->time }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <table style="width: 100%;border-spacing: 0;margin: 0 0 12px 0;">
                        <tr>
                            <td style="font-size: 18px;font-weight: 500;letter-spacing: normal;">Employees
                            </td>
                        </tr>
                    </table>
                    <div
                        style="width: 100%;background-color: #fff;margin: 0 0 20px 0;border-radius: 20px;overflow: hidden;">
                        <table style="width: 100%;border-spacing: 0;">
                            @foreach ($ManifestListing->employees  as $item)
                            <tr>
                                <td style="padding: 10px 26px 10px 26px;">
                                    <table style="width: 100%;border-spacing: 0;">
                                        <tr>
                                            <td colspan="2" style="padding: 15px 0 15px 0;border-bottom: 1px solid #3030301A">
                                                <table style="width: 100%;border-spacing: 0;padding: 0;">
                                                    <tr>
                                                        <td rowspan="3" style="width: 50px;height: 50px;"><img
                                                                src="{{$item->employee->resortAdmin->profile_picture}}" alt="user"
                                                                style="width: 70px; height: 70px; border-radius: 50px">
                                                        </td>
                                                        <td style="padding: 0 0 3px 13px;font-size: 16px;font-weight: 600;">
                                                            {{ $item->employee->resortAdmin->full_name }}
                                                            <small style="opacity: .6;;font-size: 11px;">({{ $item->employee->Emp_id }})</small>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td
                                                            style="font-size: 11px;font-weight: 500;opacity: .8;padding: 0 0 2px 13px;">
                                                             {{ $item->employee->position->position_title }}</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            @endforeach
                        </table>
                    </div>

                    <table style="width: 100%;border-spacing: 0;margin: 0 0 12px 0;">
                        <tr>
                            <td style="font-size: 18px;font-weight: 500;letter-spacing: normal;">Visitors
                            </td>
                        </tr>
                    </table>
                    <div
                        style="width: 100%;background-color: #fff;margin: 0 0 20px 0;border-radius: 20px;overflow: hidden;">
                        <table style="width: 100%;border-spacing: 0;">
                            <tr>
                                <td style="padding: 10px 26px 10px 26px;">
                                    <table style="width: 100%;border-spacing: 0;">
                                        @foreach ($ManifestListing->visitors as $item)
                                        <tr>
                                            <td colspan="2" style="padding: 15px 0 15px 0;border-bottom: 1px solid #3030301A">
                                                <table style="width: 100%;border-spacing: 0;padding: 0;">
                                                    <tr>
                                                        <td style="padding: 0 0 3px 13px;font-size: 16px;font-weight: 600;">
                                                          {{ $item->visitor_name }}
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        @endforeach
                                        

                                        
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                </td>
            </tr>
        </table>
      
    </div>
</body>

</html>



