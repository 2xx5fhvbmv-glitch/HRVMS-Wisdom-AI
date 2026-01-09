@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title ." Dashboard")

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row  g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>{{ $page_title }}</span>
                        <h1>Dashboard</h1>
                    </div>
                </div>
                <div class="col-auto  ms-auto"><a class="btn btn-theme UploadDocumentbutton " href="javascript:void(0)"  >Upload Document</a></div>
            </div>
        </div>

        <div class="row g-3 g-xxl-4 card-heigth">
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-title">
                        <h3>Filles</h3>
                    </div>
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-12 col-sm-6">
                            <div class="fileManagement-chart">
                                <canvas id="myDoughnutChart"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-12 col-sm-6">
                            <div class="row g-2  doughnut-labelTop mb-md-4 mb-3">
                                @if($FolderFiles->isNotEmpty())
                                    @foreach($FolderFiles as $f)
                                    <div class="col-xl-4 col-sm-6 col-auto">
                                        <div class="doughnut-label">
                                            <span style="background-color:{{$f->color}}"></span>{{$f->Folder_Name}} <br>{{$f->Folder_Files_count}}
                                        </div>
                                    </div>
                                    @endforeach
                                @endif
                            </div>
                            <div class="text-center">
                                <p class="fw-500">Total: {{$TotalDocument}} Documents</p>
                            </div>
                        </div>       
                    </div>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="row g-3 g-xxl-4">
                    <div class="col-sm-4">
                        <div class="card dashboard-boxcard timeAttend-boxcard">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="mb-0  fw-500">Total Documents</p>
                                    <strong>{{$TotalDocument}}</strong>
                                </div>
                                <!-- <a href="#">
                                    <img src="{{ URL::asset('resorts_assets/images/arrow-right')}}-circle.svg" alt="" class="img-fluid">
                                </a> -->
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card dashboard-boxcard timeAttend-boxcard">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="mb-0  fw-500">Unassigned Documents</p>
                                    <strong>{{$UnassignedDocumentsCounts}}</strong>
                                </div>
                                <!-- <a href="#">
                                    <img src="{{ URL::asset('resorts_assets/images/arrow-right')}}-circle.svg" alt="" class="img-fluid">
                                </a> -->
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card dashboard-boxcard timeAttend-boxcard">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="mb-0  fw-500">Total Folders Created</p>
                                    <strong>{{$FolderCount}}</strong>
                                </div>
                                <!-- <a href="#">
                                    <img src="{{ URL::asset('resorts_assets/images/arrow-right')}}-circle.svg" alt="" class="img-fluid">
                                </a> -->
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class=" card">
                            <div class=" card-title">
                                <h3>Documents Expiring Soon</h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table-lableNew table-docExpiring  w-100">
                                    <tr>
                                        <th>Document Type</th>
                                        <th>No. Of Document</th>
                                        <th>Employee Name</th>
                                        <th>Expiry Date</th>
                                        <th>Days Left</th>
                                    </tr>
                                    <tr>
                                        <td>Visa</td>
                                        <td>10</td>
                                        <td>
                                            <div class="user-ovImg user-ovImgTable">
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-2')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-3')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-4')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-5')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-2')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-3')}}.svg" alt="image">
                                                </div>
                                            </div>
                                        </td>
                                        <td>15 Mar 2025</td>
                                        <td>68 Days</td>
                                    </tr>
                                    <tr>
                                        <td>Passport</td>
                                        <td>8</td>
                                        <td>
                                            <div class="user-ovImg user-ovImgTable">
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-2')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-3')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-4')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-5')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-2')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-3')}}.svg" alt="image">
                                                </div>
                                            </div>
                                        </td>
                                        <td>10 Feb 2025</td>
                                        <td>32 Days</td>
                                    </tr>
                                    <tr>
                                        <td>Contract</td>
                                        <td>12</td>
                                        <td>
                                            <div class="user-ovImg user-ovImgTable">
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-2')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-3')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-4')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-5')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-2')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-3')}}.svg" alt="image">
                                                </div>
                                            </div>
                                        </td>
                                        <td>20 Jan 2025</td>
                                        <td>11 Days</td>
                                    </tr>
                                    <tr>
                                        <td>Work Permit</td>
                                        <td>15</td>
                                        <td>
                                            <div class="user-ovImg user-ovImgTable">
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-2')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-3')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-4')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-5')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-2')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-3')}}.svg" alt="image">
                                                </div>
                                            </div>
                                        </td>
                                        <td>05 Apr 2025</td>
                                        <td>86 Days</td>
                                    </tr>
                                    <tr>
                                        <td>Visa</td>
                                        <td>15</td>
                                        <td>
                                            <div class="user-ovImg user-ovImgTable">
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-2')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-3')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-4')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-5')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-2')}}.svg" alt="image">
                                                </div>
                                                <div class="img-circle">
                                                    <img src="{{ URL::asset('resorts_assets/images/user-3')}}.svg" alt="image">
                                                </div>
                                            </div>
                                        </td>
                                        <td>15 Mar 2025</td>
                                        <td>68 Days</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="col-lg-6">
                <div class=" card">
                    <div class=" card-title">
                        <h3>Employee Document Completion Status</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table-lableNew w-100">
                            <tr>
                                <th>Employee Name</th>
                                <th>Completed Uploads</th>
                                <th>Missing Documents</th>
                                <th>Status</th>
                            </tr>
                            <tr>
                                <td>John Doe</td>
                                <td>5/5</td>
                                <td>None</td>
                                <td><span class="badge badge-themeSuccess">Complete</span></td>
                            </tr>
                            <tr>
                                <td>Jane Smith</td>
                                <td>3/5</td>
                                <td>Visa, ID</td>
                                <td><span class="badge badge-themeYellow">Progress</span></td>
                            </tr>
                            <tr>
                                <td>Michael Brown</td>
                                <td>4/6</td>
                                <td>Passport, Insurance</td>
                                <td><span class="badge badge-themeYellow">Progress</span></td>
                            </tr>
                            <tr>
                                <td>Lisa Johnson</td>
                                <td>6/6</td>
                                <td>None</td>
                                <td><span class="badge badge-themeSuccess">Complete</span></td>
                            </tr>
                            <tr>
                                <td>Jane Brown</td>
                                <td>6/6</td>
                                <td>None</td>
                                <td><span class="badge badge-themeSuccess">Complete</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div> -->
            <div class="col-lg-6">
                <div class=" card">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Uncategorized Documents</h3>
                            </div>
                            <div class="col-auto">
                                <!-- <a href="#" class="a-link">View All</a> -->
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table-lableNew table-uncateDocuments w-100">
                            <thead>
                                <tr>
                                    <th>File Name</th>
                                    <th>Upload Date</th>
                                    <th>Permission</th>
                                </tr>
                            </thead>


                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class=" card">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-1">
                            <div class="col">
                                <h3 class="text-nowrap">Audit Logs</h3>
                            </div>
                            <div class="col-auto">
                                <a href="{{route('FileManage.AuditLogsList')}}" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table-lableNew  table-AuditLogsList  w-100">
                            <thead>
                                <tr>
                                    <th>Action Type</th>
                                    <th>File Name</th>
                                    <th>Modified By</th>
                                    <th>Last Modified</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                            
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class=" card">
                    <div class=" card-title">
                        <div class="row justify-content-between align-items-center g-1">
                            <div class="col">
                            <h3>File version history</h3>
                            </div>
                            <div class="col-auto">
                                <a href="{{route('FileManage.FileVersionList')}}" class="a-link">View All</a>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table-lableNew  table-FileVersionDashboardList w-100">
                            <thead>
                                <tr>
                                    <th>File Name</th>
                                    <th>Modified By</th>
                                    <th>Timestamp</th>
                                    <th>Size</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


    <!-- modal -->
  

    <div class="modal fade" id="selectFolderLocation-modal" tabindex="-1" aria-labelledby="selectFolderLocationLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Select Uncategorized Folder </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body ">
                    <div class="text-end mb-2"><a href="javascript:void(0)" class="btn btn-themeSkyblue btn-sm AddFolder">+ Add Folder</a>
                    </div>
                    <div class="AppendFolder">

                    </div>
                
                    <div class="appendAfterSuc AppendaftersucScroll">
                        @if($FolderList->isNotEmpty())
                            @foreach ($FolderList as $f)
                                <div class="selectFolderLocation-block">
                                    <img src="{{ URL::asset('resorts_assets/images/folder.svg') }}" alt="image">
                                    <div>
                                        <input type="text" class="form-control d-none" placeholder="New Folder |" />
                                        <h5>{{ $f->Folder_Name}}</h5>
                                    </div>
                                    <a href="javascript:void(0)" class="btn-lg-icon icon-bg-green selFolLoc-edit"   data-name="{{ $f->Folder_Name}}" data-id="{{  base64_encode($f->id)  }}">
                                        <img src="{{ URL::asset('resorts_assets/images/edit.svg')}}" alt="" class="img-fluid" />
                                    </a>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <a class="btn btn-themeBlue FileUploadButton" href="javascript:void(0)">Upload File</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uploadDocument-modal" tabindex="-1" aria-labelledby="uploadDocumentLabel"aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-uploadFile">
        <form class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="selectFolderLocation-modal">Upload Document sdfds</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
         
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12">
                            <select class="form-select select2t-none" name="FolderName" id="FolderName" required data-parsley-required-message="Please select a folder.">
                                @if($FolderList->isNotEmpty())
                                    @foreach ($FolderList as $f)
                                        <option value="{{ base64_encode($f->id) }}">{{$f->Folder_Name}}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-lg-12 mt-3">
                            <div class="bg-themeGrayLight mb-md-4 mb-3">
                                <div class="uploadFileNew-block">
                                    <img src="{{ URL::asset('resorts_assets/images/upload.svg')}}" alt="icon">
                                    <h5>Upload Scanned Documents</h5>
                                    <p>Browse or Drag the file here</p>
                                    <input type="file" id="file" multiple  name="FolderFlies[]"
                                                accept="image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip,video/*">
                                </div>
                            </div>
                            <div class="card-title">
                            <h3>Scanned Image Preview</h3>
                            <canvas id="canvasInput"></canvas>
                            <canvas id="canvasOutput"></canvas>
                                <h3>Uploaded Files</h3>
                            </div>
                            <div class="FileProgressbar"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button type="button" class="btn btn-themeBlue UpoladInternalFileForm">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
    <div class="modal fade" id="postUploadPrompt-modal" tabindex="-1" aria-labelledby="selectFolderLocationLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small modal-postUploadPrompt ">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <!-- <h5 class="modal-title" id="staticBackdropLabel">Select Folder Location </h5> -->
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <img src="{{ URL::asset('resorts_assets/images/scan.svg')}}" alt="image">
                    <h4>Would You Like To Process This File As A Scanned Document For Enhanced Clarity?</h4>
                    <div>
                        <a href="#" class="btn btn-themeBlue btn-sm me-md-4 me-2">Save As Standard</a>
                        <a href="#" class="btn btn-themeGray btn-sm">Enhance And Save</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')

@endsection

@section('import-scripts')
<script async src="https://docs.opencv.org/4.x/opencv.js" type="text/javascript"></script>

<script>
    $(document).ready(function () {
        $("#FolderName").select2({
            placeholder: "Select Folder",
            allowClear: true
        });
        AuditLogsList();
        UncategorizeDoc();
        FileVersionDashboardList();
        $(document).on("click",".UploadDocumentbutton",function(){
            $(".AppendFolder").html("");

            $("#selectFolderLocation-modal").modal("show");
        });
        
        $(document).on("click",".FileUploadButton",function()
        {    $(".uploadedFilesProgress-block").html("");   

            $("#selectFolderLocation-modal").modal("hide");
            $("#uploadDocument-modal").modal("show");

            $.ajax({
                    type: "POST",
                    url: "{{ route('FileManage.FolderList') }}",
                    dataType: "json",
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function (response) {
                        if (response.success === true) {
                            $("#FolderName").html(response.data);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                    }
                });
        });
        $('.UpoladInternalFileForm').on('click', function (event) {
            event.preventDefault();
            $(".FileProgressbar").html(""); // Clear previous progress UI
            
            var formData = new FormData();
            var fileInput = $('#file')[0];
            var files = fileInput.files; // Get selected files
            var FolderName = $("#FolderName").val(); // Get folder name
            
            // Define maximum file size (100MB in bytes)
            var maxFileSize = 100 * 1024 * 1024; // 100MB
            
            if (!FolderName || files.length === 0) {
                alert("Please select a folder and files to upload.");
                return;
            }
            
            // Validate file sizes before proceeding
            var filesValid = true;
            for (var i = 0; i < files.length; i++) {
                if (files[i].size > maxFileSize) {
                    alert("File '" + files[i].name + "' exceeds the maximum allowed size of 100MB.");
                    filesValid = false;
                    break;
                }
            }
            
            if (!filesValid) {
                return; // Stop if any file is too large
            }
            
            // Append folder name and token
            formData.append("FolderName", FolderName);
            formData.append("_token", $('meta[name="csrf-token"]').attr('content'));
            
            // Append each file
            for (var i = 0; i < files.length; i++) {
                formData.append("FolderFiles[]", files[i]);
                
                var fileSizeMB = Math.round(files[i].size / 1024 / 1024);
                var progressHTML = `
                    <div class="uploadedFilesProgress-block" id="file-${i}">
                        <div class="bg">${files[i].name.split('.').pop().toUpperCase()}</div>
                        <div>
                            <h5>${files[i].name}</h5>
                            <div>
                                <span class="upload-progress-text">0 MB / ${fileSizeMB} MB</span>
                                <span class="dot"></span> <span class="text-themeYellow">Uploading...</span>
                            </div>
                            <div class="progress progress-custom progress-themeBlue">
                                <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <div class="icon"><i class="fa-solid fa-xmark remove-upload" data-index="${i}"></i></div>
                    </div>`;
                $(".FileProgressbar").append(progressHTML);
            }
            
            // Do the AJAX upload with extended timeout
            $.ajax({
                url: "{{ route('FileManage.StoreFolderFiles') }}", // Change this to your actual upload URL
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                cache: false,
                timeout: 3600000, // 1 hour timeout for large uploads
                xhr: function () {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function (e) {
                        if (e.lengthComputable) {
                            var percentComplete = (e.loaded / e.total) * 100;
                            $(".progress-bar").css("width", percentComplete + "%");
                            
                            for (var i = 0; i < files.length; i++) {
                                var uploadedMB = Math.round((files[i].size * percentComplete / 100) / 1024 / 1024);
                                var totalMB = Math.round(files[i].size / 1024 / 1024);
                                $("#file-" + i + " .upload-progress-text").text(uploadedMB + " MB / " + totalMB + " MB");
                            }
                        }
                    }, false);
                    return xhr;
                },
                success: function (response) {
                    $(".text-themeYellow").text("Uploaded").removeClass("text-themeYellow").addClass("text-success");
                    console.log("Upload successful:", response);
                },
                error: function (xhr, status, error) {
                    $(".text-themeYellow").text("Failed").removeClass("text-themeYellow").addClass("text-danger");
                    console.error("Upload failed:", error);
                    
                    // More detailed error info
                    if (xhr.responseText) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            console.error("Server response:", response);
                        } catch (e) {
                            console.error("Server response (raw):", xhr.responseText);
                        }
                    }
                }
            });
        });

        // Handle remove button clicks
        $(document).on("click", ".remove-upload", function() {
            var index = $(this).data("index");
            $("#file-" + index).remove();
        });


    });

    
 
  
    $(document).on("click",".AddFolder",function(){
        
        $(".AppendFolder").append(`  <div class="selectFolderLocation-block">
                        <img src="{{ URL::asset('resorts_assets/images/folder.svg')}}" alt="image">
                            <div>
                                <input type="text" class="form-control d-none" placeholder="New Folder |" />
                                <h5>New Folder</h5>
                            </div>
                            <a href="#" class="btn-lg-icon icon-bg-green selFolLoc-edit">
                                <img src="{{ URL::asset('resorts_assets/images/edit.svg')}}" alt="" class="img-fluid" />
                            </a>
                    </div>`);
    });
    $(document).on("click", ".selFolLoc-edit", function () {
        const parentDiv = $(this).parent("div");
        var id = $(this).attr('data-id');
        var name = $(this).attr('data-name') || "New Folder";

   
        // Hide the existing text and remove the image
        parentDiv.find("h5").addClass("d-none");
        parentDiv.find("img").remove(); 
        $(this).remove();
        // Replace with input field and submit button
        parentDiv.html(`
        <img src="{{ URL::asset('resorts_assets/images/folder.svg')}}" alt="image">
                            <div>
                <input type="text" class="form-control" name="FolderName" value="${name}" placeholder="New Folder " />
                            </div>
                                        <a href="javascript:void(0)" class="btn btn-theme update-row-btn SubmitFolder" data-id="${id}">Submit</a>

        `);
       
    });
    $(document).on("click", ".SubmitFolder", function () {
        const parentDiv = $(this).parent("div");
        const FolderName = parentDiv.find("input").val().trim();
        const id = $(this).attr('data-id');
            // Windows disallowed characters: \ / : * ? " < > |
            const invalidChars = /[\\/:*?"<>|]/;

            // Windows reserved names (case insensitive)
            const reservedNames = ["CON", "PRN", "AUX", "NUL", "COM1", "COM2", "COM3", "COM4", "COM5", "COM6", "COM7", "COM8", "COM9", 
                                "LPT1", "LPT2", "LPT3", "LPT4", "LPT5", "LPT6", "LPT7", "LPT8", "LPT9"];

            // Check if folder name is empty
            if (FolderName === "") {
                toastr.error("Folder name cannot be empty!");
                return false;
            }

            // Check for invalid characters
            if (invalidChars.test(FolderName)) {
                toastr.error('Folder name contains invalid characters! Allowed characters: A-Z, a-z, 0-9, _ -');
                return false;
            }

            // Check for reserved names
            if (reservedNames.includes(FolderName.toUpperCase())) {
                toastr.error("This folder name is not allowed in Software!");
                return false;
            }

        $.ajax({
                url: "{{ route('FileManage.CreateFolder') }}", 
                type: 'POST',
                data: {"_token":"{{ csrf_token() }}","Folder_Name":FolderName,"id":id,"flag":"Main"},
                success: function(response) 
                {
                    if (response.success) 
                    {
                        toastr.success(response.message,"Success",
                        {
                            positionClass: 'toast-bottom-right'
                        });
                        $(".appendAfterSuc").html("");
                        $(".appendAfterSuc").html(response.data);
                        $(".AppendFolder").html("");
                    } 
                    else 
                    {
                        toastr.error(response.message, "Error",
                        {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) 
                {
                    var errors = response.responseJSON;

                    if (errors.error) { 
                        // If it's a duplicate entry error
                        toastr.error(errors.error, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        // If it's a validation error
                        var errs = '';
                        $.each(errors.errors, function(key, error) {
                            errs += error + '<br>';
                        });

                        toastr.error(errs, "Validation Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }

            });
    });
    
function UncategorizeDoc()
{
    if ($.fn.dataTable.isDataTable('.table-uncateDocuments')) {
        $('.table-uncateDocuments').DataTable().destroy();
    }

    var TableAccomMainten = $('.table-uncateDocuments').DataTable({
        "searching": false,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 6,
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("FileManage.GetUncategorizedDoc") }}',
            type: 'GET',
            data: function (d) {
                d.ResortDepartment = $(".ResortDepartment").val();
            }
        },
        columns: [
            { data: 'FileName', name: 'FileName', className: 'text-nowrap' },
            { data: 'UploadDate', name: 'UploadDate', className: 'text-nowrap' },
            { data: 'Permission', name: 'Permission', className: 'text-nowrap' },
        ]
    });
}
    
function AuditLogsList()
{
    if ($.fn.dataTable.isDataTable('.table-AuditLogsList')) 
    {
        $('.table-AuditLogsList').DataTable().destroy();
    }

    var TableAccomMainten = $('.table-AuditLogsList').DataTable({
        "searching": false,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 6,
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("FileManage.AuditLogsDashboardList") }}',
            type: 'GET',
            data: function (d) {
                d.ResortDepartment ="Dashboard";
            }
        },
        columns: [
            { data: 'ActionType', name: 'ActionType', className: 'text-nowrap' },
            { data: 'FileName', name: 'FileName', className: 'text-nowrap' },
            { data: 'ModifiedBy', name: 'ModifiedBy', className: 'text-nowrap' },
            { data: 'LastModified', name: 'LastModified', className: 'text-nowrap' },
            { data: 'Time', name: 'Time', className: 'text-nowrap' },
        ]
    });
}
function FileVersionDashboardList()
{
    if ($.fn.dataTable.isDataTable('.table-FileVersionDashboardList')) 
    {
        $('.table-FileVersionDashboardList').DataTable().destroy();
    }

    var TableAccomMainten = $('.table-FileVersionDashboardList').DataTable({
        "searching": false,
        "bLengthChange": false,
        "bFilter": true,
        "bInfo": true,
        "bAutoWidth": false,
        "scrollX": true,
        "iDisplayLength": 6,
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("FileManage.FileVersionDashboardList") }}',
            type: 'GET',
            data: function (d) {
                d.ResortDepartment ="Dashboard";
            }
        },
        columns: [
            { data: 'FileName', name: 'FileName', className: 'text-nowrap' },
            { data: 'ModifiedBy', name: 'ModifiedBy', className: 'text-nowrap' },
            { data: 'Timestamp', name: 'Timestamp', className: 'text-nowrap' },
            { data: 'Size', name: 'Size', className: 'text-nowrap' },
        ]
    });
}

</script>
<script type="module">
  document.addEventListener("DOMContentLoaded", function () {
    // Extracting data dynamically from Blade template
    var folderNames = [];
    var folderCounts = [];
    var FoloderColor =[];
    
    @foreach($FolderFiles as $f)
        folderNames.push("{{ $f->Folder_Name }}");
        folderCounts.push({{ $f->Folder_Files_count }});
        FoloderColor.push("{{ $f->color }}");
    @endforeach
    
    var ctx = document.getElementById('myDoughnutChart').getContext('2d');
    
    const doughnutLabelsInside = {
        id: 'doughnutLabelsInside',
        afterDraw: function (chart) {
            var ctx = chart.ctx;
            chart.data.datasets.forEach(function (dataset, i) {
                var meta = chart.getDatasetMeta(i);
                if (!meta.hidden) {
                    meta.data.forEach(function (element, index) {
                        var dataValue = dataset.data[index];
                        var total = dataset.data.reduce((acc, val) => acc + val, 0);
                        var percentage = ((dataValue / total) * 100).toFixed(0) + '%';

                        var position = element.tooltipPosition();

                        ctx.fillStyle = '#fff';
                        ctx.font = 'normal 14px Poppins';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(percentage, position.x, position.y);
                    });
                }
            });
        }
    };
    
 let adjustedFolderCounts = folderCounts.map(value => value === 0 ? 0.01 : value);

var myDoughnutChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: folderNames,
        datasets: [{
            data: adjustedFolderCounts,  // Use adjusted counts
            backgroundColor: FoloderColor,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        plugins: {
            doughnutLabelsInside: true,
            legend: {
                display: false
            }
        },
        layout: {
            padding: {
                top: 10,
                bottom: 10,
                left: 0,
                right: 0
            }
        }
    },
    plugins: [doughnutLabelsInside]
});
});


</script>
@endsection