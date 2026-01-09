@extends('resorts.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row  g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Survey</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                <div class="col-auto ms-auto"><a href="{{route('Survey.DownloadQuestionAndAns',base64_encode($parent->id ))}}" class="btn btn-theme DownloadQuestionAndAns" data-id="{{ base64_encode($parent->id )}}">Download</a></div>
            </div>
        </div>

        <div class="card servey-card">
            <div class="bg-themeGrayLight">
                <div class="card-title mb-md-4">
                    <div class="row justify-content-between align-items-center g-md-3 g-1">
                        <div class="col">
                            <h3 class="text-nowrap">{{$parent->Surevey_title}}</h3>
                        </div>
                        <div class="col-auto">
                            <ul class="userDetailList-wrapper">
                                <li><span>CREATED BY:</span>
                                    <div class="d-flex">
                                        <div class="img-circle"><img src="{{$parent->profileImg}}" alt="user">
                                        </div>
                                        {{ $parent->EmployeeName }}
                                    </div>
                                </li>
                                <li><span>START DATE:</span>{{ date("d M Y",strtotime($parent->Start_date)) }}</li>
                                <li><span>END DATE:</span>{{ date("d M Y",strtotime($parent->Start_date)) }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
                @if($Question->isNotEmpty())
                    @foreach($Question as $q)
                        <div class="bg-white mb-3">
                            <div class="table-responsive">
                                <table>
                                    <tr>
                                        <th>QUESTION {{ $loop->iteration }}</th>
                                        <td>{{ ucfirst($q->Question_Text) }}</td>
                                    </tr>
                                    <tr>
                                        <th>TYPE</th>
                                        <td> {{ ucfirst($q->Question_Type) }}</td>
                                    </tr>
                                    <tr>
                                        <th>COMPULSORY</th>
                                        <td>{{ isset($q->Question_Complusory) ? ucfirst($q->Question_Complusory) :"No" }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        @endforeach
                        
                @endif
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th>PRIVACY</th>
                            <td>{{$parent->survey_privacy_type}}</td>
                        </tr>
                      
                    </table>
                </div>
                <hr>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th>PARTICIPANTS</th>
                            <td>

                                <div class="user-ovImg">
                                    @if($participantEmp->isNotEmpty())
                                        @foreach ($participantEmp as $e )
                                            <div class="img-circle">
                                                <img src="{{ $e->profileImg }}" alt="image">
                                            </div>    
                                        @endforeach
                                    @endif
                               
                                    
                                </div>
                            </td>
                        </tr>
                        @if($parent->Status != "OnGoing" && $parent->Status != "Complete")
                            <form id="changeStatusForm">
                                @csrf
                                <table>
                                    <tr>
                                        <th>STATUS</th>
                                        <td>
                                            <select name="status" class="form-control changeStatus" id="changeStatus" data-parsley-required="true" data-parsley-errors-container="#statusError">
                                                <option value="">Select Status</option>
                                                <option value="Publish" {{ ($parent->Status == "Publish")?'selected' :'' }}>Publish</option>
                                                @if($parent->Status != "SaveAsDraft")
                                                    <option value="SaveAsDraft" {{ ($parent->Status == "SaveAsDraft")?'selected' :'' }}>Save As Draft</option>
                                                @endif
                                            
                                            </select>
                                            <span id="statusError" class="text-danger"></span> <!-- Error message container -->
                                        </td>
                                        <td>
                                            <input type="hidden" name="id" value="{{ base64_encode($parent->id) }}">
                                            <button class="btn btn-theme" type="submit"> Change </button>
                                        </td>
                                    </tr>
                                </table>
                            </form>
                        @endif
                        
                            
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script type="text/javascript">
   
   $(document).ready(function () {
        $("#changeStatus").select2({
            placeholder: "Select Status",
            allowClear: true // Enables clear button
        });
        $('#changeStatusForm').parsley();

        // Handle form submission
        $("#changeStatusForm").on("submit", function (e) {
                    e.preventDefault(); // Prevent default form submission

                    var form = $(this);
                    if (form.parsley().validate()) {
                        var formData = form.serialize();

                        $.ajax({
                            url: "{{ route('Survey.changeStatus') }}", // Update with actual route
                            type: "POST",
                            data: formData,
                            success: function (response) {
                                if (response.success) {
                                    toastr.success(response.message, "Success", { positionClass: 'toast-bottom-right' });
                                    setTimeout(function () {
                                        window.location.reload();
                                    }, 5000);

                                } else {
                                    toastr.error(response.message, "Error", { positionClass: 'toast-bottom-right' });
                                }
                            },
                            error: function (xhr) {
                                toastr.error("An error occurred.", "Error", { positionClass: 'toast-bottom-right' });
                            }
                        });
                    }
        });

      

    });


    
</script>
@endsection
