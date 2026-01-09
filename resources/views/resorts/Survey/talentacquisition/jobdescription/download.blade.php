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
            src: url('../fonts/Poppins-SemiBold.eot');
            src: url('../fonts/Poppins-SemiBold.eot?#iefix') format('embedded-opentype'), url('../fonts/Poppins-SemiBold.woff2') format('woff2'), url('../fonts/Poppins-SemiBold.woff') format('woff'), url('../fonts/Poppins-SemiBold.ttf') format('truetype'), url('../fonts/Poppins-SemiBold.svg#Poppins-SemiBold') format('svg');
            font-weight: 600;
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

        @font-face {
            font-family: Poppins;
            src: url('../fonts/Poppins-Medium.eot');
            src: url('../fonts/Poppins-Medium.eot?#iefix') format('embedded-opentype'), url('../fonts/Poppins-Medium.woff2') format('woff2'), url('../fonts/Poppins-Medium.woff') format('woff'), url('../fonts/Poppins-Medium.ttf') format('truetype'), url('../fonts/Poppins-Medium.svg#Poppins-Medium') format('svg');
            font-weight: 500;
            font-style: normal;
            font-display: swap
        }

        @font-face {
            font-family: Poppins;
            src: url('../fonts/Poppins-Light.eot');
            src: url('../fonts/Poppins-Light.eot?#iefix') format('embedded-opentype'), url('../fonts/Poppins-Light.woff2') format('woff2'), url('../fonts/Poppins-Light.woff') format('woff'), url('../fonts/Poppins-Light.ttf') format('truetype'), url('../fonts/Poppins-Light.svg#Poppins-Light') format('svg');
            font-weight: 300;
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

        .pdf-container {
            width: 210mm;
            margin: 50px auto;
            padding: 0;
            background-color: white;
            border: 1px solid #dcdcdc;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>

    <div class="pdf-container">
        <div style="    color: darkred;
                        margin-bottom: 15px;
                        align-right: revert;
                        margin-left: 86%;
                        margin-top: 15%;">
            <button id="printButton">Print Content</button>

        </div>

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
                                {{$ResortData->address2}},,
                                {{$ResortData->state}},   {{$ResortData->city}},
                                {{$ResortData->zip}},  {{$ResortData->country}}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="background-color: hsla(190, 98%, 16%, 0.05);padding:15px;">
                    <div class="">
                        {!! $j->jobdescription !!}
                    </div>

                </td>
            </tr>
            <tr>
                <td style="background-color:    #014653;color: #fff;font-size: 14px;font-weight: 400; line-height: 21px;padding:10px 15px 10px 15px ;"> {!!$sitesettings->Footer !!}.</td>
            </tr>
        </table>
    </div>
</body>

<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>

<script type="text/JavaScript" src="https://cdnjs.cloudflare.com/ajax/libs/jQuery.print/1.6.0/jQuery.print.js"></script>
<script>
     $(document).ready(function() {
        // When the button is clicked
        $('#printButton').click(function() {


            $("#tablePrint").print();
        });
    });
</script>
</html>


