@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)
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
                  <span>File Management</span>
                  <h1>{{ $page_title }}</h1>
               </div>
            </div>
            <!-- <div class="col-auto  ms-auto"><a class="btn btn-theme">Upload Document</a></div> -->
         </div>
      </div>
      <div class="card">
         <div class="row g-md-4 g-3">
            <div class="col-xl-4 col-lg-5">
               <div class="bg-themeGrayLight fileDocument-block fileManage-block">
                  <div class="card-title mb-md-3">
                     <div class="row g-xxl-3 g-2 align-items-center">
                        <div class="col">
                           <h3 class="text-nowrap">My Drive</h3>
                        </div>
                        <div class="col-auto">
                           <select class="form-select " name="Folderselect" id="Folderselect">
                              <option value="Main">Folder</option>
                              @if($AllFolderList->isNotEmpty())
                              @foreach($AllFolderList as $folder)
                              <option value="{{ base64_encode($folder->id) }}">{{ $folder->Folder_Name }}</option>
                              @endforeach
                              @endif
                           </select>
                        </div>
                        <div class="col-auto"><a href="javascript:void(0)" id="NewfolderCreate" class=" btn btn-themeBlue btn-sm  @if(App\Helpers\Common::checkRouteWisePermission('Employees.Documents',config('settings.resort_permissions.create')) == false) d-none @endif">Create
                           Folder</a>
                        </div>
                     </div>
                  </div>
                  <div class="search-document mb-3">
                     <input type="search" class="form-control Search" id="Search" placeholder="Search">
                     <div>
                        <i class="fa-regular fa-magnifying-glass"></i>
                        <a href="#advancedSearch-modal" class="btn-icon" data-bs-toggle="modal"><i
                           class="fa-regular fa-bars-staggered" data-bs-toggle="tooltip"
                           data-bs-placement="bottom" title="Advanced Search"></i></a>
                     </div>
                  </div>
                  <div class="overflow-auto pe-1 ListofFolder ">
                     @if($FolderList->isNotEmpty())
                        @foreach($FolderList as $folder)

                            <div class="d-flex" >
                                <div class="showStructure" data-unique_id="{{ $folder->Folder_unique_id}}">
                                    <div class="img-circle userImg-block ">
                                    <img src="{{ URL::asset('resorts_assets/images/folder.svg') }}" alt="image">
                                    </div>
                                    <div>
                                    <h6>{{ $folder->Folder_Name }}</h6>
                                    </div>
                                </div>
                                <div class="form-check no-label">
                                <input class="form-check-input FolderName internacheck d-none" type="checkbox" name="FolderName[]"  data-id="{{ $folder->Folder_unique_id}}" value="{{ $folder->Folder_unique_id}}" >
                                </div>
                            </div>
                        @endforeach
                     @endif
                  </div>
               </div>
            </div>
            <div class="col-lg-7 col-xl-8 d-flex flex-column">
               <div class="card-title">
                  <div class="row g-3 align-items-center justify-content-between">
                     <div class="col-auto">
                        <h3>Documents View</h3>
                     </div>
                     <div class="col-auto ms-auto">
                        <nav aria-label="breadcrumb	 ">
                           <ol class="breadcrumb breadcrumb-theme ">
                           </ol>
                        </nav>
                     </div>
                     <div class="col-auto">
                        <button type="button" class="btn btn-themeBlue btn-sm" id="MoveDoc">Move</button>
                     </div>
                  </div>
               </div>
               <div class="flex-grow-1 mb-md-4 mb-3">
                  <div class="table-responsive">
                     <table class="table-lableNew  table-fileDocView table-fileDocViewDevloper w-100">
                        <thead>
                           <tr>
                              <th></th>
                              <th>File Name</th>
                              <th>Size</th>
                              <th>Last Modified</th>
                              <th>Members</th>
                              <th>Action</th>
                           </tr>
                        </thead>
                        <tbody id="TableBody">
                           <tr>
                              <td colspan="6" class="text-center">No Record Found..</td>
                           </tr>
                        </tbody>
                     </table>
                  </div>
               </div>
               <div class="card-footer text-end"><a href="javascript:void(0)" class="MoveAllFiles btn btn-themeBlue btn-sm" >Submit</a></div>
            </div>
         </div>
      </div>
   </div>
</div>
<nav id="context-menu" class="context-menu">
   <ul>
      <li><a href="#renameDocument-modal" class="passContext-menu" data-bs-toggle="modal">Rename</a></li>
   </ul>
</nav>
<!-- modal -->
<div class="modal fade" id="renameDocument-modal" tabindex="-1" aria-labelledby="renameDocumentLabel"
   aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-small">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="staticBackdropLabel">Rename Document </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <form id="RemameFileForm" >
            @csrf
            <input type="hidden" name="file_id" id="file_id">
            <div class="modal-body"> <label for="rename" class="form-label">RENAME</label>
               <input type="text" class="form-control" name="renameFile" id="renameFile" placeholder="Visa.pdf">
            </div>
            <div class="modal-footer">
               <a href="javascritpt:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
               <button type="submit" class="btn btn-themeBlue">Submit</button>
            </div>
         </form>
      </div>
   </div>
</div>
<div class="modal fade" id="advancedSearch-modal" tabindex="-1" aria-labelledby="advancedSearchLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-small">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="staticBackdropLabel">Advanced Search </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <form id="AdvancedSearchForm">
                @csrf
                <div class="row g-md-3 g-2">
                    <div class="col-12">
                        <label for="file_name" class="form-label">FILE NAME</label>
                        <input type="text" class="form-control" id="file_name" name="file_name" placeholder="Visa.pdf">
                    </div>
                    <div class="col-12">
                        <label for="file_type" class="form-label">FILE TYPE</label>
                        <input type="text" class="form-control" name="file_type" id="file_type">
                        <input type="hidden" class="form-control" id="MainFolderType" name="MainFolderType" values="uncategorized">
                    </div>
                    <!-- <div class="col-12">
                        <label for="employee_name" class="form-label">EMPLOYEE NAME</label>
                        <select class="form-select select2t-none" name="employee_name" id="employee_name" aria-label="Default select example">
                            <option selected>Select </option>
                            <option value="1">aaa</option>
                        </select>
                    </div> -->
                    
                    <div class="col-12">
                        <label for="date_modified" class="form-label">DATE MODIFIED</label>
                        <select class="form-select select2t-none" id="date_modified" name="date_modified"
                            aria-label="Default select example">
                            <option ></option>
                            @for($i=1; $i<=90; $i++)    
                            <option value="{{$i}}">Last {{$i}} days </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="department" class="form-label">DEPARTMENT</label>
                        <select class="form-select select2t-none" name="department" id="department"aria-label="Default select example">
                            <option >Select Department </option>
                            @if($department->isNotEmpty())
                                @foreach($department as $d)
                                    <option value="{{$d->id}}">{{$d->name}}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="javascript:void(0)"  class="btn btn-themeGray ms-auto RestAdvancedfilter">Reset</a>
                    <button type="button"  class="btn btn-themeBlue SubmutAdvancefilter">Search</a>
                </div>
            </form>
      </div>
   </div>
</div>
<div class="modal fade" id="AddFolder-modal" tabindex="-1" aria-labelledby="selectFolderLocationLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Select Uncategorized Folder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="CreateFolderForm">
                @csrf
                <div class="modal-body pb-0">
                    <div class="row">
                        <div class="col-md-12">
                            <input type="text" name="Folder_Name" id="Folder_Name" class="form-control"
                                placeholder="Folder Name">
                            <input type="hidden" name="flag" id="FolderType" class="form-control" values="uncategorized">
                        </div>
                    </div>

                    <div class="AppendFolder mt-2"></div>
                </div> <!-- Added missing closing tag for modal-body -->

                <div class="modal-footer">
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button type="submit" class="btn btn-themeBlue">Submit</button>
                </div>
            </form> <!-- Closing form correctly -->
        </div>
    </div>
</div> <!-- Closing first modal properly -->

<!-- Second Modal (Separate from the First One) -->
<div class="modal fade" id="bd-iframeModel-modal-lg" tabindex="-1" aria-labelledby="myLargeModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Select Uncategorized Folder</h5>
                <a href="" class="btn btn-smbtn-primary downloadLink" target="_blank"> Download</a>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
                <div class="modal-body">
                 
                        <div class=" ratio ratio-21x9" id="ViewModeOfFiles">

                        </div>
                   
                </div>
                <div class="modal-footer">
                    <a href="javascript:void(0)" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button type="submit" class="btn btn-themeBlue">Submit</button>
                </div>
   
        </div>
    </div>
</div>
<div class="overlayFileModule" id="overlay" onclick="hideImage()">
        <span class="closeFileModule" onclick="hideImage()">&times;</span>
        <img id="largeImage" src="" alt="Large View">
    </div>

@endsection
@section('import-css')

@endsection
@section('import-scripts')
<script>
   $(document).ready(function() {
            $.validator.addMethod("validFolderName", function(value, element) {
            // Disallow characters that are invalid in folder names or potentially dangerous
            // This regex blocks: < > : " / \ | ? * and control characters
            return this.optional(element) || /^[^<>:"/\\|?*\x00-\x1F]+$/.test(value);
        }, "Folder name contains invalid characters.");

       $('#Folderselect').select2({
           placeholder: "Select Folder", allowClear: true
       });
       $('#department').select2({
           placeholder: "Select department", allowClear: true
       });
       $('#date_modified').select2({
           placeholder: "Select Date Modified", sallowClear: true
       });
       $('#RemameFileForm').validate({
           rules: {
               renameFile: {
                   required: true,
               }
           },
           messages: {
               renameFile: {
                   required: "Please Enter File Name.",
               }
           },
           submitHandler: function(form) 
           {
               var formData = new FormData(form);
               $.ajax({
                   url: "{{ route('FileManage.RenameFile') }}", 
                   type: "POST",
                   data: formData,
                   processData: false,
                   contentType: false,
                   success: function(response) 
                   {
                       if(response.success == true)
                       {
                           form.reset();
                           GetTheUpdatedFolder();
                           let activeElement1 = $('.d-flex.active').find('.showStructure.active');
                           $("#renameDocument-modal").modal('hide');

                            if (activeElement1.length > 0)
                            {  
                                let id = activeElement1.data("unique_id");
                                GetFileStructureList(id);

                                $("#renameDocument-modal").modal('hide');
                            }
                           toastr.success(response.message, "Success", {
                               positionClass: "toast-bottom-right",
                           });
                       } 
                       else
                       {
                           toastr.error(response.message, "Error", {
                               positionClass: "toast-bottom-right",
                           });
                       }
                   },
                   error: function(xhr, status, error) 
                   {
                       try {
                           const response = xhr.responseJSON;
                           
                           if (response && response.success === false) {
                               toastr.error(response.message, "Error", {
                                   positionClass: 'toast-bottom-right'
                               });
                           } else if (response && response.errors) {
                               const errorMessages = Object.values(response.errors).flat().join('<br>');
                               toastr.error(errorMessages, "Error", {
                                   positionClass: 'toast-bottom-right'
                               });
                           } else {
                               toastr.error("An unexpected error occurred", "Error", {
                                   positionClass: 'toast-bottom-right'
                               });
                           }
                       } 
                       catch (e) 
                       {
                           toastr.error("An unexpected error occurred", "Error", {
                               positionClass: 'toast-bottom-right'
                           });
                       }
                   }
               });
           }
       });
       $('#CreateFolderForm').validate({
            rules: {
                Folder_Name: {
                    required: true,
                    validFolderName: true,
                    maxlength: 25 // Standard max length for folder names
                }
            },
            messages: {
                Folder_Name: {
                    required: "Please enter your folder name.",
                    validFolderName: "Folder name cannot contain these characters: < > : \" / \\ | ? *",
                    maxlength: "Folder name must be less than 25 characters."
                }
            },
           submitHandler: function(form) 
           {
               var formData = new FormData(form);
               $.ajax({
                   url: "{{ route('FileManage.CreateFolder') }}", 
                   type: "POST",
                   data: formData,
                   processData: false,
                   contentType: false,
                   success: function(response) 
                   {
                       if(response.success == true)
                       {
                           form.reset();
                           GetTheUpdatedFolder();
                           let activeElement =    $(".showStructure").parent("div").addClass("active");
                           // Add class
                           if (activeElement.length > 0) {  
                              
                               GetTheUpdatedFolder() ;                       
                           } 
                           $("#AddFolder-modal").modal('hide');
                           toastr.success(response.message, "Success", {
                               positionClass: "toast-bottom-right",
                           });
                       } 
                       else
                       {
                           toastr.error(response.message, "Error", {
                               positionClass: "toast-bottom-right",
                           });
                       }
                   },
                   error: function(xhr, status, error) 
                   {
                    
                    let errorMessage = '';

                        if (xhr.responseJSON && xhr.responseJSON.errors) {

                            $.each(xhr.responseJSON.errors, function(key, error) {

                            errorMessage += error + "<br>";
                            })
                        }
                        else
                        {
                            errorMessage = "An error occurred while Create or Update."; // Default error message
                        }

                        toastr.error(errorMessage, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                   }
               });
           }
       });

        $('.RestAdvancedfilter').on('click', function() {
            // Reset all input fields
            $('#file_name').val('');
            $('#file_type').val('');
            
            // Reset select elements
            $('#date_modified').val('').trigger('change');
            $('#department').val('Select Department').trigger('change');
            
            // If you're using select2 for your dropdowns
            if ($.fn.select2) {
                $('#date_modified').select2('val', '');
                $('#department').select2('val', 'Select Department');
            }
            
            // Reset any hidden fields
            $('#MainFolderType').val('uncategorized');
            
            // If you have any other form elements that need resetting, add them here
            
            // Optional: Close the modal if needed
            // $('#yourModalId').modal('hide');
        });
        
   });
   
   $(document).on( "click",".SubmutAdvancefilter", function(e) 
   {   
        e.preventDefault(); 
        var activeElement = $('.breadcrumb-item.active');

        if(activeElement.length > 0) 
        {
            var dataId = activeElement.find('a.OpenFileorFolder').attr('data-unique_id');
        }
        else
        {
            toastr.error("Please Select Folder ", "Error", 
            {
                positionClass: "toast-bottom-right",
            });
            return false;
        }
        var file_name = $("#file_name").val();
        var file_type = $("#file_type").val();
        var date_modified = $("#date_modified").val();
        var department = $("#department").val();
        var MainFolderType = $("#MainFolderType").val();
        if (!file_name && !file_type && !date_modified && !department)
        {
            toastr.error("Please select at least one filter before submitting.");
            return false; 
        }
        $.ajax({
           url: "{{ route('FileManage.AdvanceSearch') }}", // Your route for file upload
           type: "post",
           data: {"_token":"{{ csrf_token() }}",
                "flag":'uncategorized',
                'Folder_id':dataId,
                "file_name":file_name,
                "MainFolderType":MainFolderType,
                'file_type':file_type,
                'date_modified':date_modified,
                'department':department},
       
           success: function(response) {
               if(response.success == true)
               {
                    // $(".breadcrumb").html(response.breadcrumb);
                    $("#TableBody").html(response.data);
                    $("#advancedSearch-modal").modal('hide');
               } 
               else 
               {
   
                   toastr.error(response.message, "Error", {
                       positionClass: "toast-bottom-right",
                   });
               }
   
           },
           error: function(xhr, status, error) 
           {
               try {
                   const response = xhr.responseJSON;
                   
                   if (response && response.success === false) {
                       toastr.error(response.message, "Error", {
                           positionClass: 'toast-bottom-right'
                       });
                   } else if (response && response.errors) 
                   {
                       const errorMessages = Object.values(response.errors).flat().join('<br>');
                       toastr.error(errorMessages, "Error", {
                           positionClass: 'toast-bottom-right'
                       });
                   } 
                   else 
                   {
                       toastr.error("An unexpected error occurred", "Error", {
                           positionClass: 'toast-bottom-right'
                       });
                   }
               } catch (e) {
                   toastr.error("An unexpected error occurred", "Error", {
                       positionClass: 'toast-bottom-right'
                   });
               }
           }
       });
        
   });
   $(document).on( "click","#MoveDoc", function() 
   {   
       $('.internacheck').prop('checked',false);
       $(".internacheck").toggleClass('d-none');   
   });
//    $(document).on( "click",".checkCheck", function() 
//    {   
   
//        if(($("input[name='FolderName[]']:checked").length > 0) == false)
//        {
//            $(this).prop('checked', false);
           
//            toastr.error("Please Select Folder", "Error", {
//                positionClass: "toast-bottom-right",
//            });
//        }
//    });
   $(document).on( "click","#NewfolderCreate", function() 
   {
   
       $("#AddFolder-modal").modal('show');
       var Folderselect = $('#Folderselect').val();
       $('#FolderType').val(Folderselect);
       
   });
   $(document).on( "keyup","#Search", function() 
   {
       var Folderselect = $('#Folderselect').val();
       GetTheUpdatedFolder();
   });
   
   $(document).on( "click",".showStructure", function() 
   {
       
      let activeElement =  $(this).addClass('active')
            .parent('.d-flex')
            .addClass('active')
            .siblings('.d-flex') 
            .removeClass('active')
            .find('.showStructure') 
            .removeClass('active'); 
 

            let activeElement1 = $('.d-flex.active').find('.showStructure.active');

        if (activeElement1.length > 0)
        {  
            let id = activeElement1.data("unique_id");
            GetFileStructureList(id);
        }
    $("#Search").val("");
    if (!$(".internacheck").hasClass("d-none")) 
    {  
        $(".internacheck").addClass("d-none");  
    } 
      
   });
   $(document).on( "click",".OpenFileorFolder", function() 
    {
        var Location = $(this).attr('data-url');
        var unique_id = $(this).attr('data-unique_id');
        
        $.ajax({
            url: "{{ route('FileManage.ShowthefolderWiseData') }}", // Your route for file upload
            type: "post",
            data: {"_token":"{{ csrf_token() }}","unique_id":unique_id,"Location":Location},
            success: function(response) 
            {
                if (response.success) 
                {
                if(response.newUrL == "No")
                {
                    $(".breadcrumb").html(response.breadcrumb);

                    $("#TableBody").html(response.data);
                }
                else
                {

                    let fileUrl = response.NewURLshow;
                    let mimeType = response.mimeType.toLowerCase();

                    // File types that should be displayed inside an <iframe>
                    let iframeTypes = [
                        'video/mp4', 'video/quicktime', 'video/x-msvideo', // Videos
                        'application/pdf', 'text/plain',                   // PDF & Text
                        'application/msword', 'application/vnd.ms-excel'   // Word & Excel
                    ];
                    let imageTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $(".downloadLink").attr('href', fileUrl);
                    $(".downloadLink").attr('data-unique_id', unique_id);
                    if (imageTypes.includes(mimeType)) 
                    {
                        $("#ViewModeOfFiles").html(`
                            <img src="${fileUrl}" class="popupimgFileModule" onclick="showImage('${fileUrl}')" alt="Image Preview">
                        `);
                        $("#bd-iframeModel-modal-lg").modal('show');
                    } 
                    else if (iframeTypes.includes(mimeType)) 
                    {
                        $("#ViewModeOfFiles").html(`<iframe style="width: 100%; height: 100%;" src="${fileUrl}" allowfullscreen></iframe>`);
                        $("#bd-iframeModel-modal-lg").modal('show');
                    } 
                    else 
                    {
                        window.location.href = fileUrl; // Triggers download automatically
                    }
                }
                } else {
                    toastr.error(response.message, "Error", {
                        positionClass: "toast-bottom-right",
                    });
                }
            },
            error: function(xhr, status, error) 
            {
                try {
                    const response = xhr.responseJSON;
                    
                    if (response && response.success === false) {
                        toastr.error(response.message, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else if (response && response.errors) {
                        const errorMessages = Object.values(response.errors).flat().join('<br>');
                        toastr.error(errorMessages, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error("An unexpected error occurred", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                } catch (e) {
                    toastr.error("An unexpected error occurred", "Error", {
                        positionClass: 'toast-bottom-right'
                    });
                }
            }
        });

    });
   
    
    $(document).on( "click",".downloadLink", function() 
    {
        var unqiue_id = $(this).data('unique_id');
        $.ajax({
           url: "{{ route('FileManage.AuditlogStore') }}", // Your route for file upload
           type: "post",
           data: {"_token":"{{ csrf_token() }}","unqiue_id":unqiue_id},
       
           success: function(response) {
             
   
           },
           error: function(xhr, status, error) 
           {
               try {
                   const response = xhr.responseJSON;
                   
                   if (response && response.success === false) 
                   {
                       toastr.error(response.message, "Error", {
                           positionClass: 'toast-bottom-right'
                       });
                   } else if (response && response.errors) {
                       const errorMessages = Object.values(response.errors).flat().join('<br>');
                       toastr.error(errorMessages, "Error", {
                           positionClass: 'toast-bottom-right'
                       });
                   } else {
                       toastr.error("An unexpected error occurred", "Error", {
                           positionClass: 'toast-bottom-right'
                       });
                   }
               } catch (e) {
                   toastr.error("An unexpected error occurred", "Error", {
                       positionClass: 'toast-bottom-right'
                   });
               }
           }
       });
    });
    $(document).on( "click",".MoveAllFiles", function() 
   {


        let FolderName = $("input[name='FolderName[]']:checked").map(function() {
                        return $(this).val();
                    }).get();

        if(FolderName.length>1)
        {
            toastr.error("Please Select Only One Folder", "Error", {
                positionClass: "toast-bottom-right",
            });
            return false;
        }

        let selectedFiles = [];
        let Parent_id = 0;
        $("input[name='FilesName[]']:checked").each(function() {
            selectedFiles.push($(this).val());
            Parent_id = $(this).attr('data-id');
        });
        $.ajax({
           url: "{{ route('FileManage.MoveFolder') }}", // Your route for file upload
           type: "post",
           data: {"_token":"{{ csrf_token() }}","FilesName":selectedFiles,'FolderName':FolderName},
       
           success: function(response) {
               if(response.success == true)
               {
                   $(".breadcrumb").html(response.breadcrumb);
                   $("#TableBody").html(response.data);

                   GetFileStructureList(Parent_id);
                   GetTheUpdatedFolder();
                   toastr.success(response.message, "Success", {
                       positionClass: "toast-bottom-right",
                   });
               
               } else {
   
                   toastr.error(response.message, "Error", {
                       positionClass: "toast-bottom-right",
                   });
               }
   
           },
           error: function(xhr, status, error) 
           {
               try {
                   const response = xhr.responseJSON;
                   
                   if (response && response.success === false) 
                   {
                       toastr.error(response.message, "Error",{
                           positionClass: 'toast-bottom-right'
                       });
                   } else if (response && response.errors) {
                       const errorMessages = Object.values(response.errors).flat().join('<br>');
                       toastr.error(errorMessages, "Error", {
                           positionClass: 'toast-bottom-right'
                       });
                   } else {
                       toastr.error("An unexpected error occurred", "Error", {
                           positionClass: 'toast-bottom-right'
                       });
                   }
               } catch (e) {
                   toastr.error("An unexpected error occurred", "Error", {
                       positionClass: 'toast-bottom-right'
                   });
               }
           }
       });
   });
   function showImage(src) {
            document.getElementById('largeImage').src = src;
            document.getElementById('overlay').style.display = 'flex';
        }

        function hideImage() {
            document.getElementById('overlay').style.display = 'none';
        }
   
   function GetFileStructureList(id)
   {
 
       $.ajax({
           url: "{{ route('FileManage.GetFolderFiles') }}", // Your route for file upload
           type: "post",
           data: {"_token":"{{ csrf_token() }}","id":id,'flag':"uncategorized"},
       
           success: function(response) {
               if(response.success == true)
               {
                   $(".breadcrumb").html(response.breadcrumb);
                   $("#TableBody").html(response.data);
               
               } else {
   
                   toastr.error(response.message, "Error", {
                       positionClass: "toast-bottom-right",
                   });
               }
   
           },
           error: function(xhr, status, error) 
           {
               try {
                   const response = xhr.responseJSON;
                   
                   if (response && response.success === false) {
                       toastr.error(response.message, "Error", {
                           positionClass: 'toast-bottom-right'
                       });
                   } else if (response && response.errors) 
                   {
                       const errorMessages = Object.values(response.errors).flat().join('<br>');
                       toastr.error(errorMessages, "Error", {
                           positionClass: 'toast-bottom-right'
                       });
                   } 
                   else 
                   {
                       toastr.error("An unexpected error occurred", "Error", {
                           positionClass: 'toast-bottom-right'
                       });
                   }
               } catch (e) {
                   toastr.error("An unexpected error occurred", "Error", {
                       positionClass: 'toast-bottom-right'
                   });
               }
           }
       });
   }
   function GetTheUpdatedFolder()
   {
       $.ajax({
           url: "{{ route('FileManage.GetFolder') }}", // Your route for file upload
           type: "get",
           data: {"_token":"{{ csrf_token() }}","Search":$('.Search').val(),"flag":"uncategorized"},
       
           success: function(response) {
               if(response.success == true)
               {
               $(".ListofFolder").html(response.data);
                   
               } else {
   
                   toastr.error(response.message, "Error", {
                       positionClass: "toast-bottom-right",
                   });
               }
   
           },
           error: function(xhr, status, error) 
           {
               try {
                   const response = xhr.responseJSON;
                   
                   if (response && response.success === false) {
                       toastr.error(response.message, "Error", {
                           positionClass: 'toast-bottom-right'
                       });
                   } else if (response && response.errors) {
                       const errorMessages = Object.values(response.errors).flat().join('<br>');
                       toastr.error(errorMessages, "Error", {
                           positionClass: 'toast-bottom-right'
                       });
                   } else {
                       toastr.error("An unexpected error occurred", "Error", {
                           positionClass: 'toast-bottom-right'
                       });
                   }
               } catch (e) {
                   toastr.error("An unexpected error occurred", "Error", {
                       positionClass: 'toast-bottom-right'
                   });
               }
           }
       });
   }
   
   
   (function () {
   
   "use strict";
   
   
   /*********************************************** Context Menu Function Only ********************************/
   function clickInsideElement(e, className) {
       var el = e.srcElement || e.target;
       if (el.classList.contains(className)) {
           return el;
       } else {
           while (el = el.parentNode) {
               if (el.classList && el.classList.contains(className)) {
                   return el;
               }
           }
       }
       return false;
   }
   
   function getPosition(e) {
       var posx = 0,
           posy = 0;
       if (!e) var e = window.event;
       if (e.pageX || e.pageY) {
           posx = e.pageX;
           posy = e.pageY;
       } else if (e.clientX || e.clientY) {
           posx = e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
           posy = e.clientY + document.body.scrollTop + document.documentElement.scrollTop;
       }
       return {
           x: posx,
           y: posy
       }
   }
   
   // Your Menu Class Name
   var taskItemClassName = "context-btn";
   var contextMenuClassName = "context-menu",
       contextMenuItemClassName = "context-menu__item",
       contextMenuLinkClassName = "context-menu__link",
       contextMenuActive = "context-menu--active";
   var taskItemInContext, clickCoords, clickCoordsX, clickCoordsY, menu = document.querySelector("#context-menu"),
       menuItems = menu.querySelectorAll(".context-menu__item");
   var menuState = 0,
       menuWidth, menuHeight, menuPosition, menuPositionX, menuPositionY, windowWidth, windowHeight;
   
   function initMenuFunction() {
       contextListener();
       clickListener();
       keyupListener();
       resizeListener();
   }
   
   /**
    * Listens for contextmenu events.
    */
   function contextListener() {
       document.addEventListener("contextmenu", function (e) {
           taskItemInContext = clickInsideElement(e, taskItemClassName);
           $("#file_id").val(" "); // Add data-id to the context menu
   
           if (taskItemInContext) {
               var dataId = taskItemInContext.getAttribute("data-id");
               var renameFile = taskItemInContext.getAttribute("data-name");
   
               
               $("#file_id").val( dataId); // Add data-id to the context menu
               
               $("#renameFile").val( renameFile); // Add data-id to the context menu
   
               e.preventDefault(); 
               toggleMenuOn();
               
               positionMenu(e);
           } else {
               taskItemInContext = null;
               toggleMenuOff();
           }
       });
   }
   
   /**
    * Listens for click events.
    */
   function clickListener() {
       document.addEventListener("click", function (e) {
           var clickeElIsLink = clickInsideElement(e, contextMenuLinkClassName);
   
           if (clickeElIsLink) {
               e.preventDefault();
               menuItemListener(clickeElIsLink);
           } else {
               var button = e.which || e.button;
               if (button === 1) {
                   toggleMenuOff();
               }
           }
       });
   }
   
   /**
    * Listens for keyup events.
    */
   function keyupListener() {
       window.onkeyup = function (e) {
           if (e.keyCode === 27) {
               toggleMenuOff();
           }
       }
   }
   
   /**
       * Window resize event listener
       */
   function resizeListener() {
       window.onresize = function (e) {
           toggleMenuOff();
       };
   }
   
   /**
    * Turns the custom context menu on.
    */
   function toggleMenuOn(dataId) {
       if (menuState !== 1) {
           menuState = 1;
           menu.classList.add(contextMenuActive);
   
       }
   }
   
   /**
    * Turns the custom context menu off.
    */
   function toggleMenuOff() {
       if (menuState !== 0) {
           menuState = 0;
           menu.classList.remove(contextMenuActive);
       }
   }
   
   function positionMenu(e) {
       clickCoords = getPosition(e);
       clickCoordsX = clickCoords.x;
       clickCoordsY = clickCoords.y;
       menuWidth = menu.offsetWidth + 4;
       menuHeight = menu.offsetHeight + 4;
   
       windowWidth = window.innerWidth;
       windowHeight = window.innerHeight;
   
       if ((windowWidth - clickCoordsX) < menuWidth) {
           menu.style.left = (windowWidth - menuWidth) - 0 + "px";
       } else {
           menu.style.left = clickCoordsX - 0 + "px";
       }
   
       // menu.style.top = clickCoordsY + "px";
   
       if (Math.abs(windowHeight - clickCoordsY) < menuHeight) {
           menu.style.top = (windowHeight - menuHeight) - 0 + "px";
       } else {
           menu.style.top = clickCoordsY - 0 + "px";
       }
   }
   
   
   function menuItemListener(link) {
       var td = taskItemInContext.getAttribute("data-id");
   
   
       var menuSelectedPhotoId = taskItemInContext.getAttribute("data-id");
       console.log('Your Selected Photo: ' + menuSelectedPhotoId)
       var moveToAlbumSelectedId = link.getAttribute("data-action");
       if (moveToAlbumSelectedId == 'remove') {
           console.log('You Clicked the remove button')
       } else if (moveToAlbumSelectedId && moveToAlbumSelectedId.length > 7) {
           console.log('Clicked Album Name: ' + moveToAlbumSelectedId);
       }
       toggleMenuOff();
   }
   initMenuFunction();
   
   })();
</script>
@endsection