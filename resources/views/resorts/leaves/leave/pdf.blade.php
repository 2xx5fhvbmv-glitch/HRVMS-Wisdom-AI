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
        .card-header {
            padding: 0 0 20px;
            margin-bottom: 30px;
            background: transparent;
            border-bottom: 1px solid #E7E7E7;
        }
        .empDetails-user .img-circle {
            width: 100px;
            height: 100px;
            min-width: 100px;
            margin-right: 20px;
            border-radius: 50%;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="pdf-container">
        <div class="card-header">
            <div class="row g-md-3 g-2 align-items-center">
                <div class="col-lg">
                    <div class="empDetails-user" style="display: flex;align-items: center;margin-left:5px 5px">
                        <div class="img-circle">
                            <img style="width:100px;height:100px;" src="{{$leaveUsage[0]->profile_picture}}" alt="user">
                        </div>
                        <div>
                            <h4 style="font-weight: 600;margin-bottom: 5px;">{{$leaveUsage[0]->first_name}} {{$leaveUsage[0]->last_name}}</h4>
                            <p class="badge badge-themeNew">{{$leaveUsage[0]->Emp_Code }}</p>
                            <p>{{$leaveUsage[0]->position_title }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="empDetails-leave mb-4">
            <div class="card-title">
                <div class="row g-2 align-items-center">
                        @php $total_leaves_allocated = 0 ;$total_taken_laves = 0; @endphp
                        @if($leaveBalances)
                            @foreach($leaveBalances as $leaves)
                                @php 
                                    $total_leaves_allocated = $total_leaves_allocated +  $leaves->allocated_days;
                                    $total_taken_laves = $total_taken_laves +  $leaves->available_days;
                                @endphp   
                            @endforeach
                        @endif
                        <div class="col-auto ms-auto" style="margin-left: auto !important;text-align: right;">
                            <div style="padding: 12px 14px;background: #F5F8F8; border-radius: 15px;display:ruby-text;">
                            <p>Total Leave Balance: {{ $total_taken_laves }}/<span>{{ $total_leaves_allocated }}</span></p>
                            </div>
                        </div>
                </div>
            </div>
           
            <table style="width: 100%;font-family: 'Poppins', sans-serif; border-spacing: 0;background-color: hsla(190, 98%, 16%, 0.05);">
                <thead>
                    <tr>
                        <th style="border-bottom: 1px solid #E7E7E7;padding:10px 10px 10px 4px;">Leave Type</th>
                        <th style="border-bottom: 1px solid #E7E7E7;padding:10px 10px 10px 4px;">Used / Allocated Days</th>
                        <th style="border-bottom: 1px solid #E7E7E7;padding:10px 10px 10px 4px;">Leave Type</th>
                        <th style="border-bottom: 1px solid #E7E7E7;padding:10px 10px 10px 4px;">Used / Allocated Days</th>
                    </tr>
                </thead>
                <tbody>
                    @if($leaveBalances)
                        @foreach($leaveBalances as $index => $child)
                            <!-- Start a new row for every two items -->
                            @if($index % 2 == 0)
                                <tr>
                            @endif
                            
                            <!-- First leave type and allocated days -->
                            <th style="border-bottom: 1px solid #E7E7E7;padding:10px 10px 10px 4px;">{{ $child->leave_type ?? 'N/A' }}</th>
                            <th style="border-bottom: 1px solid #E7E7E7;padding:10px 10px 10px 4px;">{{ $child->used_days }} / {{ $child->allocated_days }}</td>

                            <!-- If we reach the second item, close the row -->
                            @if($index % 2 == 1 || $index == count($leaveBalances) - 1)
                                </tr>
                            @endif
                        @endforeach
                    @endif
                    
                </tbody>
            </table> 
        </div>

        <div class="card-title">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h3 style="padding:10px 10px 10px 4px;">Leave History</h3>
                </div>
                <div class="col-auto"><span class="badge badge-themeNew"></span></div>
            </div>
        </div>
        
        <table style="width: 100%;font-family: 'Poppins', sans-serif; border-spacing: 0;background-color: hsla(190, 98%, 16%, 0.05);">
            <thead>
                <tr>
                    <th style="border-bottom: 1px solid #E7E7E7;padding:10px 10px 10px 4px;">Leave Category</th>
                    <th style="border-bottom: 1px solid #E7E7E7;padding:10px 10px 10px 4px;">Reason</th>
                    <th style="border-bottom: 1px solid #E7E7E7;padding:10px 10px 10px 4px;">From</th>
                    <th style="border-bottom: 1px solid #E7E7E7;padding:10px 10px 10px 4px;">To</th>
                    <th style="border-bottom: 1px solid #E7E7E7;padding:10px 10px 10px 4px;">Total Days</th>
                    <th style="border-bottom: 1px solid #E7E7E7;padding:10px 10px 10px 4px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @if($leaveUsage->isNotEmpty())
                    @foreach ($leaveUsage as $leave)
                        <tr>
                            <td style="border-bottom: 1px solid #E7E7E7;padding:10px 10px 10px 4px;">{{ $leave->leave_category }}</td>
                            <td style="border-bottom: 1px solid #E7E7E7;padding:10px 10px 10px 4px;">{{ $leave->reason }}</td>
                            <td style="border-bottom: 1px solid #E7E7E7;padding:10px 10px 10px 4px;">{{ $leave->from_date }}</td>
                            <td style="border-bottom: 1px solid #E7E7E7;padding:10px 10px 10px 4px;">{{ $leave->to_date }}</td>
                            <td style="border-bottom: 1px solid #E7E7E7;padding:10px 10px 10px 4px;">{{ $leave->total_days }}</td>
                            <td style="border-bottom: 1px solid #E7E7E7;padding:10px 10px 10px 4px;">{{ $leave->status }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" style="text-align: center"> No Records Found.. </td>
                    </tr>
                @endif
            </tbody>
        </table>
        
    </div> 
</body>
<script>
    window.onload = function () {
        window.print();
    }
</script>
</html>
