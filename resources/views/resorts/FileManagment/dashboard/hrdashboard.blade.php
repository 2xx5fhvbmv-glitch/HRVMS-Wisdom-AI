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
                    <!-- <div class="col-12">
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
                    </div> -->
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
            <div class="col-lg-6 @if(Common::checkRouteWisePermission('Categories.Documents',config('settings.resort_permissions.view')) == false) d-none @endif">
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
                                        <input type="text" class="form-control d-none"  />
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
                                <h3> Uploaded Files</h3>
                            </div>
                            <div class="FileProgressbar">
                                <table class="table  ">
                                <tbody class="fileList"></tbody> 
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <a href="#postUploadPrompt-modal" data-bs-toggle="modal" data-bs-dismiss="modal" class="btn btn-themeBlue UpoladInternalFileForm">Upload</a>
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
                        <button  type="button" class="btn btn-themeBlue btn-sm me-md-4 me-2 SaveFiles" data-id="SaveAsStandard" >Save As Standard</button>
                        <button  type="button" class="btn btn-themeGray btn-sm SaveFiles" data-id="EnhanceAndSave">Enhance And Save</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

   

    <div class="modal fade" id="CropImage-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-small modal-CropImage">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Interview Details </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="crop-container ">
                        <h4 id="crop-file-name" class="mb-3"></h4> <!-- Image Name Here -->
                        <img id="crop-image" src="" alt="Crop Image" style="max-width: 100%;" />
                        <br/>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="javascript:vpid(0)" id="close-crop-btn" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Close</a>
                    <a href="javascript:void(0)" id="crop-btn" class="btn btn-themeBlue">Crop</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet"/>

@endsection

@section('import-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script async src="https://docs.opencv.org/4.5.2/opencv.js" type="text/javascript"></script>

<script>


let cropper;
let filesToUpload = [];
let currentCropIndex = null; // ✅ Fix: declare global variable to track which image is being cropped

document.getElementById("file").addEventListener("change", function (event) {
    const fileInput = event.target;
    const fileList = fileInput.files;
    const fileListContainer = document.querySelector('.fileList');
    
    fileListContainer.innerHTML = '';
    filesToUpload = []; // reset the array
    
    Array.from(fileList).forEach((file, index) => {
        const row = document.createElement('tr');
        row.setAttribute('data-index', index);
        
        // Check if file is an image
        const isImage = file.type.startsWith('image/');
        
        // Create buttons column based on file type
        const actionColumn = isImage ? 
            `<td>
                <a href="#" class="btn btn-sm btn-themeSkyblue crop-btn" data-index="${index}">Crop</a>
            </td>` : 
            `<td>-</td>`;
        
        row.innerHTML = `
            <td>${file.name}</td>
            ${actionColumn}
            <td>
                <div class="icon">
                    <i class="fa-solid fa-xmark remove-upload" style="cursor:pointer;" data-index="${index}"></i>
                </div>
            </td>
        `;
        
        fileListContainer.appendChild(row);
        filesToUpload[index] = file;
        
        // Add crop event listener only for image files
        if (isImage) {
            row.querySelector('.crop-btn').addEventListener('click', () => {
                openCropModal(file, index); // Pass file + index to crop
            });
        }
        
        // Remove event listener for all files
        row.querySelector('.remove-upload').addEventListener('click', () => {
            row.remove();
            filesToUpload[index] = null; // Mark for removal
        });
    });
    
    // Reset input to allow same file re-selection
    fileInput.value = '';
});

function openCropModal(file, index) {
    currentCropIndex = index; // ✅ Update which image is being cropped

    const cropModal = document.getElementById('CropImage-modal');
    const cropImage = document.getElementById('crop-image');
    const cropFileName = document.getElementById('crop-file-name');

    // Destroy previous cropper
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }

    cropFileName.textContent = `File: ${file.name}`;
    const imageUrl = URL.createObjectURL(file);
    cropImage.src = imageUrl;

    cropImage.onload = function () {
        cropImage.style.maxHeight = '100%';
        cropImage.style.maxWidth = '100%';

        cropper = new Cropper(cropImage, {
            aspectRatio: NaN,
            viewMode: 0,
            dragMode: 'crop',
            autoCropArea: 1,
            restore: false,
            guides: true,
            center: true,
            highlight: true,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: true,
            minContainerWidth: 300,
            minContainerHeight: 300,
            modalClass: 'cropper-modal-black',
            ready: function () {
                const imageData = cropper.getImageData();
                cropper.setCropBoxData({
                    left: imageData.left,
                    top: imageData.top,
                    width: imageData.width,
                    height: imageData.height
                });
                cropper.resize();
            }
        });

        // Open the Bootstrap modal
        const bsModal = new bootstrap.Modal(cropModal);
        bsModal.show();
    };
}

document.getElementById('crop-btn').addEventListener('click', function () {
    if (cropper && currentCropIndex !== null) {
        cropper.getCroppedCanvas().toBlob(blob => {
            const original = filesToUpload[currentCropIndex];
            const croppedFile = new File([blob], original.name, {
                type: original.type,
                lastModified: Date.now()
            });
            filesToUpload[currentCropIndex] = croppedFile;

            cropper.destroy();
            cropper = null;

            const modalInstance = bootstrap.Modal.getInstance(document.getElementById('CropImage-modal'));
            modalInstance.hide();
        }, filesToUpload[currentCropIndex].type);
    }
});

document.getElementById('close-crop-btn').addEventListener('click', function () {
    const modalInstance = bootstrap.Modal.getInstance(document.getElementById('CropImage-modal'));
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
   $('#CropImage-modal').modal('hide');
});
// after the close 
let cropperInstances = {};
let croppedImages = []; // store cropped blobs

document.getElementById('file').addEventListener('change', function (e) {
    const files = e.target.files;
    const fileListDiv = document.getElementById('fileList');
    fileListDiv.innerHTML = '';
    croppedImages = [];

    Array.from(files).forEach((file, index) => {
        const wrapper = document.createElement('div');

        const filename = document.createElement('p');
        filename.innerHTML = `<strong>File:</strong> ${file.name}`;
        wrapper.appendChild(filename);

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();

            reader.onload = function (event) {
                const img = document.createElement('img');
                img.src = event.target.result;
                img.id = `previewImg_${index}`;

                const cropBtn = document.createElement('button');
                cropBtn.textContent = 'Crop Image';
                cropBtn.className = 'btn btn-primary btn-sm mt-2';

                cropBtn.addEventListener('click', () => {
                    const canvas = cropperInstances[index]?.getCroppedCanvas();
                    if (canvas) {
                        canvas.toBlob(blob => {
                            // Replace original image with cropped version
                            img.src = URL.createObjectURL(blob);
                            croppedImages[index] = new File([blob], file.name, { type: file.type });

                            cropperInstances[index].destroy();
                        }, file.type);
                    }
                });

                wrapper.appendChild(img);
                wrapper.appendChild(cropBtn);
                fileListDiv.appendChild(wrapper);

                cropperInstances[index] = new Cropper(img, {
                    aspectRatio: 1,
                    viewMode: 1
                });
            };

            reader.readAsDataURL(file);
        } else {
            // non-image files
            croppedImages[index] = file;
            fileListDiv.appendChild(wrapper);
        }
    });
});
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
        { 
            $(".uploadedFilesProgress-block").html("");   
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
        $('.UpoladInternalFileForm').on('click', function (event) 
        {
    
            $("#postUploadPrompt-modal").modal('show');
        });


        // Handle remove button clicks
        $(document).on("click", ".remove-upload", function() {
            var index = $(this).data("index");
            $("#file-" + index).remove();
        });


    });

    
    $('.SaveFiles').on('click', async function (event) {
        var flag = $(this).data('id');
        $("#postUploadPrompt-modal").modal('hide');
        $("#uploadDocument-modal").modal('show');

        if (flag === "EnhanceAndSave") 
        {
            await processFilesToUpload(); 
        }

        console.log(filesToUpload);

        $(".FileProgressbar").html("");
        $(".fileList").html("");
        
        const filteredFiles = filesToUpload.filter(f => f instanceof File); // skip deleted
        const dataTransfer = new DataTransfer();
        filteredFiles.forEach(file => dataTransfer.items.add(file));
        document.getElementById('file').files = dataTransfer.files;

        // Begin your existing upload logic...
        var formData = new FormData();
        var fileInput = $('#file')[0];
        var files = fileInput.files;
        var FolderName = $("#FolderName").val();
        var maxFileSize = 100 * 1024 * 1024; // 100MB

        if (!FolderName || files.length === 0) {
            alert("Please select a folder and files to upload.");
            return;
        }

        for (var i = 0; i < files.length; i++) {
            if (files[i].size > maxFileSize) {
                alert("File '" + files[i].name + "' exceeds the maximum allowed size of 100MB.");
                return;
            }
        }

        formData.append("FolderName", FolderName);
        formData.append("_token", $('meta[name="csrf-token"]').attr('content'));
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

        $.ajax({
            url: "{{ route('FileManage.StoreFolderFiles') }}",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            cache: false,
            timeout: 3600000,
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
                $("#uploadDocument-modal").modal('hide');

                toastr.success("File uploaded successfuly", "Success", {
                       positionClass: "toast-bottom-right",
                   });
         
            },
            error: function (xhr, status, error) {
                $(".text-themeYellow").text("Failed").removeClass("text-themeYellow").addClass("text-danger");
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
    $(document).on("click",".AddFolder",function(){
        
        $(".AppendFolder").append(`  <div class="selectFolderLocation-block">
                        <img src="{{ URL::asset('resorts_assets/images/folder.svg')}}" alt="image">
                            <div>
                                <input type="text" class="form-control d-none" />
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
        var name = $(this).attr('data-name') || "";

   
        // Hide the existing text and remove the image
        parentDiv.find("h5").addClass("d-none");
        parentDiv.find("img").remove(); 
        $(this).remove();
        // Replace with input field and submit button
        parentDiv.html(`
        <img src="{{ URL::asset('resorts_assets/images/folder.svg')}}" alt="image">
                            <div>
                <input type="text" class="form-control" name="FolderName" value="${name}"/>
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
            if (FolderName === "") 
            {
                toastr.error("Folder name cannot be empty!", "Error",
                {
                    positionClass: 'toast-bottom-right'
                });
                return false;
            }

            // Check for invalid characters
            if (invalidChars.test(FolderName)) {
        
                toastr.error('Folder name contains invalid characters! Allowed characters: A-Z, a-z, 0-9, _ -', "Error",
                        {
                            positionClass: 'toast-bottom-right'
                        });
               
                return false;
            }

            // Check for reserved names
            if (reservedNames.includes(FolderName.toUpperCase())) {

                toastr.error("This folder name is not allowed in Software!", "Error",
                        {
                            positionClass: 'toast-bottom-right'
                        });
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
    
    async function processFilesToUpload() {
    const canvas = document.createElement("canvas");
    const ctx = canvas.getContext("2d");

    for (let i = 0; i < filesToUpload.length; i++) {
        const file = filesToUpload[i];
        if (!file || !file.type.startsWith("image/")) continue;

        await new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = function (event) {
                const img = new Image();
                img.onload = function () {
                    const mat = cv.imread(img);
                    const processed = enhanceImage(mat);
                    mat.delete();

                    canvas.width = processed.cols;
                    canvas.height = processed.rows;
                    cv.imshow(canvas, processed);
                    processed.delete();

                    canvas.toBlob((blob) => {
                        const newFile = new File([blob], file.name, {
                            type: file.type,
                            lastModified: Date.now(),
                        });
                        filesToUpload[i] = newFile;
                        resolve();
                    }, file.type);
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        });
    }
}
function enhanceImage(src) {
    let dst = new cv.Mat();
    let gray = new cv.Mat();
    let blur = new cv.Mat();
    let sharp = new cv.Mat();
    cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY, 0);
    cv.GaussianBlur(gray, blur, new cv.Size(3, 3), 0);
    cv.addWeighted(gray, 1.4, blur, -0.4, 0, sharp);
    let alpha = 1.5; 
    let beta = -10;  
    sharp.convertTo(dst, -1, alpha, beta);
    gray.delete(); blur.delete(); sharp.delete();
    return dst;
  }
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
        order: [[3, 'desc']],
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
             {data:'created_at',visible:false,searchable:false},
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
        order: [[5, 'desc']],
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
             {data:'created_at',visible:false,searchable:false},
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
        order: [[4, 'desc']],
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
             {data:'created_at',visible:false,searchable:false},
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