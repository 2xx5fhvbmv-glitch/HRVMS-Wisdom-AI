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
                              <option value="{{ base64_encode($folder->id) }}">{{ $folder->Display_Name ?? $folder->Folder_Name }}</option>
                              @endforeach
                              @endif
                           </select>
                        </div>
                        {{-- Create Folder button hidden — employee folders are
                             auto-created from the EmployeeController save flow
                             (and backfilled), so HR doesn't need to create
                             them by hand from this page. --}}
                        {{-- <div class="col-auto"><a href="javascript:void(0)" id="NewfolderCreate" class=" btn btn-themeBlue btn-sm @if(App\Helpers\Common::checkRouteWisePermission('Employees.Documents',config('settings.resort_permissions.create')) == false) d-none @endif">Create Folder</a></div> --}}
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

                            <div class="d-flex folder-row" >
                                <div class="showStructure" data-unique_id="{{ $folder->Folder_unique_id}}">
                                    <div class="img-circle userImg-block ">
                                    <img src="{{ URL::asset('resorts_assets/images/folder.svg') }}" alt="image">
                                    </div>
                                    <div>
                                    <h6>
                                        {{ $folder->Display_Name ?? $folder->Folder_Name }}
                                        @if(!empty($folder->Is_Shared))
                                            <span class="badge bg-info text-dark ms-1" style="font-size:9px;">SHARED</span>
                                        @endif
                                    </h6>
                                    </div>
                                </div>
                                {{-- Folder 3-dot action menu (Rename / Delete / Share).
                                     Visible on hover so the existing folder row UI
                                     stays clean. Each option fires the same handlers
                                     the file-row action menu uses, with data-target=folder. --}}
                                <div class="dropdown folder-action-dd ms-auto">
                                    <button type="button" class="btn btn-sm folder-action-btn p-1"
                                            data-bs-toggle="dropdown" aria-expanded="false"
                                            data-folder-id="{{ $folder->id }}"
                                            data-folder-unique_id="{{ $folder->Folder_unique_id }}"
                                            data-folder-name="{{ $folder->Folder_Name }}">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end folder-action-menu">
                                        <li><a class="dropdown-item folder-rename-trigger" href="#renameDocument-modal" data-bs-toggle="modal"><i class="fa-solid fa-pen me-2"></i>Rename</a></li>
                                        <li><a class="dropdown-item folder-share-trigger" href="javascript:void(0)"><i class="fa-solid fa-share-nodes me-2"></i>Share</a></li>
                                        <li><a class="dropdown-item text-danger folder-delete-trigger" href="javascript:void(0)"><i class="fa-solid fa-trash-can me-2"></i>Delete</a></li>
                                    </ul>
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
                              <td colspan="6" class="text-center"> No Record Found..</td>
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
      <li><a href="javascript:void(0)" class="passContext-menu" id="contextShareFile">Share</a></li>
      <li><a href="javascript:void(0)" class="passContext-menu text-danger" id="contextDeleteFile">Delete</a></li>
   </ul>
</nav>

{{-- Share modal — generates a temporary signed URL the user can copy. --}}
{{-- Unified Share modal (replaces the old quick-link modal). One modal
     drives both files and folders; data-share-target / data-share-id
     are populated by the trigger handler before the modal opens. --}}
<div class="modal fade" id="shareFile-modal" tabindex="-1" aria-labelledby="shareFileLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="shareModalTitle">Share</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <input type="hidden" id="shareTargetType" value="">
            <input type="hidden" id="shareTargetId" value="">

            <ul class="nav nav-tabs share-tabs mb-3">
               <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#share-internal-tab">Internal</a></li>
               <li class="nav-item"><a class="nav-link disabled" href="javascript:void(0)" tabindex="-1" aria-disabled="true">External <small class="text-muted">(coming soon)</small></a></li>
            </ul>

            <div class="tab-content">
               <div class="tab-pane fade show active" id="share-internal-tab">
                  <div class="share-option mb-2" data-scope="employees">
                     <input type="radio" name="shareScope" id="scopeEmployees" value="employees" checked>
                     <label for="scopeEmployees">
                        <div class="share-option-title">Specific Employees</div>
                        <div class="share-option-desc">Search and select individual employees</div>
                     </label>
                  </div>
                  <div class="share-scope-body" id="scope-employees-body">
                     <div id="selectedEmployeeChips" class="mb-2"></div>
                     <input type="text" class="form-control share-emp-search" id="shareEmpSearch" placeholder="Search by name or Emp ID…" autocomplete="off">
                     <div class="share-search-results" id="shareEmpResults"></div>
                  </div>

                  <div class="share-option mb-2 mt-3" data-scope="departments">
                     <input type="radio" name="shareScope" id="scopeDepartments" value="departments">
                     <label for="scopeDepartments">
                        <div class="share-option-title">Department(s)</div>
                        <div class="share-option-desc">All employees in selected departments</div>
                     </label>
                  </div>
                  <div class="share-scope-body d-none" id="scope-departments-body">
                     <div id="deptCheckboxList" class="dept-checkbox-list"></div>
                  </div>

                  <div class="share-option mb-2 mt-3" data-scope="organization">
                     <input type="radio" name="shareScope" id="scopeOrganization" value="organization">
                     <label for="scopeOrganization">
                        <div class="share-option-title">Entire Organization</div>
                        <div class="share-option-desc">All active employees in your resort will see this</div>
                     </label>
                  </div>
                  <div class="share-scope-body d-none" id="scope-organization-body">
                     <div class="alert alert-info py-2 mb-0">This item will be visible to all active employees in your resort.</div>
                  </div>

                  <hr>
                  <div class="d-flex justify-content-between align-items-center mb-2">
                     <strong>Active shares for this item</strong>
                     <small class="text-muted">Click ✕ on a row to revoke</small>
                  </div>
                  <div id="activeSharesList" class="active-shares-list"><div class="text-muted small py-2">Loading…</div></div>
               </div>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-themeGray me-2" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-themeBlue" id="shareSubmitBtn">Share</button>
         </div>
      </div>
   </div>
</div>

<style>
   .share-option { display: flex; align-items: flex-start; gap: 10px; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; cursor: pointer; }
   .share-option input[type=radio] { margin-top: 4px; }
   .share-option label { cursor: pointer; margin-bottom: 0; flex: 1; }
   .share-option-title { font-weight: 600; color: #014653; font-size: 13.5px; }
   .share-option-desc { font-size: 12px; color: #6c757d; }
   .share-option:has(input:checked) { border-color: #014653; background: #e8f4f4; }
   .share-scope-body { padding: 10px 14px 0; }
   .share-search-results { border: 1px solid #e2e8f0; border-radius: 6px; max-height: 200px; overflow-y: auto; margin-top: 6px; display: none; }
   .share-search-results.show { display: block; }
   .share-search-results .item { padding: 8px 12px; cursor: pointer; font-size: 13px; border-bottom: 1px solid #f1f1f1; }
   .share-search-results .item:hover { background: #f5f9f9; }
   .share-search-results .item .meta { color: #6c757d; font-size: 11px; }
   .emp-chip { display: inline-flex; align-items: center; gap: 6px; background: #e8f4f4; border: 1px solid #014653; border-radius: 16px; padding: 3px 10px; font-size: 12px; color: #014653; margin: 2px; }
   .emp-chip .remove { cursor: pointer; font-weight: 700; }
   .dept-checkbox-list .item { display: flex; align-items: center; gap: 8px; padding: 6px 10px; font-size: 13px; border-radius: 6px; cursor: pointer; }
   .dept-checkbox-list .item input { margin: 0; }
   .dept-checkbox-list .item:has(input:checked) { background: #e8f4f4; }
   .active-shares-list .item { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 6px 10px; border-bottom: 1px solid #f1f1f1; font-size: 13px; }
   .active-shares-list .item:last-child { border-bottom: none; }
   .active-shares-list .recipients { color: #014653; }
   .active-shares-list .revoke { color: #c0392b; cursor: pointer; background: none; border: 0; font-size: 14px; padding: 2px 6px; }
   .folder-row { align-items: center; position: relative; }
   .folder-action-dd { opacity: 0; transition: opacity .15s ease; }
   .folder-row:hover .folder-action-dd, .folder-action-dd.show { opacity: 1; }
   .folder-action-btn { color: #555; background: transparent; border: 0; }
   .folder-action-btn:hover { color: #014653; }
</style>
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
                        <input type="hidden" class="form-control" id="MainFolderType" name="MainFolderType" value="categorized">
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
                <h5 class="modal-title" id="staticBackdropLabel">Select categorized Folder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="CreateFolderForm">
                @csrf
                <div class="modal-body pb-0">
                    <div class="row">
                        <div class="col-md-12">
                            <input type="text" name="Folder_Name" id="Folder_Name" class="form-control" placeholder="Folder Name">
                            <input type="hidden" name="flag" id="FolderType" class="form-control">
                        </div>
                    </div>
                    <div class="AppendFolder mt-2"></div>
                </div> <!-- Added missing closing tag for modal-body -->

                <div class="modal-footer ">
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
                <h5 class="modal-title" id="staticBackdropLabel">Download File</h5>
               
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
   // ── Recipient view: inject "Shared With Me" + shared folders into
   // the left sidebar on page load. Falls through silently if the user
   // has no received shares (no extra UI noise). ─────────────────────
   // Precompute asset URLs OUTSIDE the JS string concat — Blade directives
   // (the double-brace ones) compile to PHP echo statements, and escaped
   // quotes inside a directive that itself lives inside a JS single-quoted
   // string confuse the compiler. Resolving the URLs once in PHP and
   // letting JS reference the variable side-steps the whole problem.
   var __folderIcon = @json(URL::asset('resorts_assets/images/folder.svg'));
   var __pdfIcon    = @json(URL::asset('resorts_assets/images/pdf1.svg'));

   function injectReceivedSharesSidebar() {
       $.get("{{ route('FileShare.received') }}", function (r) {
           if (!r.success) return;
           var hasFiles   = (r.files   || []).length > 0;
           // Folders shared with the user already appear in the regular
           // sidebar via the server-side folder filter (those folders are
           // included in visibleFolderIdsForCurrentUser). Don't inject
           // them again here or you get duplicates.
           //
           // For shared FILES we still prepend the synthetic "Shared
           // With Me" folder — files don't have their own sidebar entry
           // and need a place to live.
           if (!hasFiles) return;

           var html = '<div class="d-flex shared-with-me-row" >'
                    + '  <div class="showStructure" data-unique_id="__shared_with_me__" data-shared-virtual="1">'
                    + '    <div class="img-circle userImg-block" style="background:#fff7e6;">'
                    + '      <img src="' + __folderIcon + '" alt="image">'
                    + '    </div>'
                    + '    <div><h6>Shared With Me <span class="badge bg-warning text-dark ms-1" style="font-size:9px;">SYSTEM</span></h6></div>'
                    + '  </div>'
                    + '</div>';
           $('.ListofFolder').prepend(html);
           window.__receivedSharedFiles = r.files || [];
       });
   }

   // Intercept clicks on the virtual "Shared With Me" folder so we
   // render received files directly (no server fetch needed — the
   // file list was preloaded in injectReceivedSharesSidebar).
   $(document).on('click', '.showStructure[data-shared-virtual="1"]', function () {
       var files = window.__receivedSharedFiles || [];
       var tr = '';
       if (!files.length) {
           tr = '<tr><td colspan="8" style="text-align:center;">No record found</td></tr>';
       } else {
           files.forEach(function (f) {
               tr += '<tr>'
                   + '<td><div class="form-check no-label"><input class="form-check-input internacheck checkCheck d-none" type="checkbox"></div></td>'
                   + '<td><a href="javascript:void(0)" class="OpenFileorFolder" data-unique_id="' + f.unique_id + '" data-url="InternaFile"><img src="' + __pdfIcon + '" alt=""> ' + $('<div>').text(f.name).html() + '</a></td>'
                   + '<td>' + (f.size || 0) + ' KB</td>'
                   + '<td>' + (f.modified ? new Date(f.modified).toLocaleDateString() : '—') + '</td>'
                   + '<td></td>'
                   + '<td><span class="badge bg-warning text-dark" style="font-size:10px;">SHARED · READ-ONLY</span></td>'
                   + '</tr>';
           });
       }
       $('#TableBody').html(tr);
       $(".breadcrumb").html("<li class='breadcrumb-item active'><span>Shared With Me</span></li>");
   });

   $(document).ready(function() {
       injectReceivedSharesSidebar();
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
                           // Refresh BOTH panes:
                           //   - left sidebar folder list (GetTheUpdatedFolder)
                           //   - right file table for whatever folder is open
                           // Without the second call, the renamed file/folder
                           // still shows the old name in the table until you
                           // click away and back.
                           GetTheUpdatedFolder();
                           var activeFolderUid = $('.showStructure.active').data('unique_id');
                           if (activeFolderUid) {
                               GetFileStructureList(activeFolderUid);
                           }
                           toastr.success(response.message, "Success", {
                               positionClass: "toast-bottom-right",
                           });
                           $("#renameDocument-modal").modal('hide');
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
                   url: "{{ route('FileManage.CreateEmployeeFolder') }}", 
                   type: "POST",
                   data: formData,
                   processData: false,
                   contentType: false,
                   success: function(response) 
                   {
                    console.log(response);
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
                "flag":'categorized',
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

   // ── Action menu: Delete ─────────────────────────────────────────
   // Uses the file_id (== ChildFileManagement.unique_id) the
   // contextmenu/click handler stored when the user opened the menu.
   $(document).on('click', '#contextDeleteFile', function () {
       var fileId = $('#file_id').val();
       var fileName = $('#renameFile').val();
       if (!fileId) { return; }
       if (!confirm('Delete "' + fileName + '"? This cannot be undone.')) return;
       $.ajax({
           url: "{{ route('FileManage.DeleteFile') }}",
           type: 'POST',
           data: { _token: "{{ csrf_token() }}", file_id: fileId },
           success: function (r) {
               if (r.success) {
                   toastr.success(r.message || 'File deleted', 'Success', { positionClass: 'toast-bottom-right' });
                   GetTheUpdatedFolder();
                   // Also reload the right-pane file list — otherwise the
                   // deleted file row lingers until the user clicks away.
                   var activeFolderUid = $('.showStructure.active').data('unique_id');
                   if (activeFolderUid) {
                       GetFileStructureList(activeFolderUid);
                   }
               } else {
                   toastr.error(r.message || 'Delete failed', 'Error', { positionClass: 'toast-bottom-right' });
               }
           },
           error: function (xhr) {
               var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed';
               toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
           }
       });
   });

   // ── Unified Internal Share modal ─────────────────────────────────
   // State for the currently-open share modal.
   var __share = {
       targetType: null,   // 'file' | 'folder'
       targetId:   null,   // DB id of the file/folder
       targetName: '',
       selectedEmployees: [],   // [{id, name}]
       selectedDepartments: [], // [id]
   };

   // Open from File action menu (the existing 3-dot context). The
   // context handler stored the file's unique_id in #file_id; we need
   // the file's numeric id for the share record, so we look it up.
   $(document).on('click', '#contextShareFile', function (e) {
       e.preventDefault();
       var uniqueId = $('#file_id').val();
       var fileName = $('#renameFile').val() || 'File';
       if (!uniqueId) return;
       // Find the file's row in the DOM table to extract the DB id.
       // Each file row was rendered with data-unique_id; we don't have
       // the DB id there, so fetch it via the existing file_id endpoint.
       openShareModal('file', null, fileName, uniqueId);
   });

   // Open from Folder action menu.
   $(document).on('click', '.folder-share-trigger', function (e) {
       e.preventDefault();
       var btn = $(this).closest('.folder-action-dd').find('.folder-action-btn');
       var folderId = btn.data('folder-id');
       var folderName = btn.data('folder-name');
       if (!folderId) return;
       openShareModal('folder', folderId, folderName, null);
   });

   // Folder Rename — uses the existing #renameDocument-modal
   $(document).on('click', '.folder-rename-trigger', function () {
       var btn = $(this).closest('.folder-action-dd').find('.folder-action-btn');
       $('#file_id').val(btn.data('folder-unique_id'));
       $('#renameFile').val(btn.data('folder-name'));
   });

   // Folder Delete
   $(document).on('click', '.folder-delete-trigger', function () {
       var btn = $(this).closest('.folder-action-dd').find('.folder-action-btn');
       var folderUid = btn.data('folder-unique_id');
       var folderName = btn.data('folder-name');
       if (!confirm('Delete folder "' + folderName + '"? This cannot be undone.')) return;
       $.ajax({
           url: "{{ route('FileManage.DeleteFile') }}",
           type: 'POST',
           data: { _token: "{{ csrf_token() }}", file_id: folderUid },
           success: function (r) {
               if (r.success) {
                   toastr.success(r.message || 'Folder deleted', 'Success', { positionClass: 'toast-bottom-right' });
                   GetTheUpdatedFolder();
               } else {
                   toastr.error(r.message || 'Delete failed', 'Error', { positionClass: 'toast-bottom-right' });
               }
           },
           error: function (xhr) {
               var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Delete failed';
               toastr.error(msg, 'Error', { positionClass: 'toast-bottom-right' });
           }
       });
   });

   function openShareModal(type, id, name, fallbackUid) {
       __share.targetType = type;
       __share.targetId   = id;
       __share.targetName = name;
       __share.selectedEmployees = [];
       __share.selectedDepartments = [];
       $('#shareTargetType').val(type);
       $('#shareTargetId').val(id || '');
       $('#shareModalTitle').text((type === 'folder' ? 'Share Folder: ' : 'Share: ') + name);
       $('#selectedEmployeeChips').empty();
       $('#shareEmpSearch').val('');
       $('#shareEmpResults').empty().removeClass('show');
       $('#deptCheckboxList').empty();
       $('#scopeEmployees').prop('checked', true);
       toggleScopeBody('employees');
       // If we didn't receive a numeric id (file path: we only had unique_id),
       // resolve it via the existing file rows in the DOM.
       if (type === 'file' && !id && fallbackUid) {
           // Quick lookup against the file table — best-effort. The server
           // also validates ownership so even if this lookup fails we
           // surface the error from the store endpoint.
           var row = $('.OpenFileorFolder[data-unique_id="' + fallbackUid + '"]').first();
           // Files don't carry their DB id in the DOM, so post with unique_id;
           // the controller’s store() supports lookup-by-unique_id via a small
           // helper we add server-side. To keep the controller strictly
           // numeric, we instead skip server lookup and require the id.
           __share.targetUniqueId = fallbackUid; // surface below in submit
       }
       loadDepartments();
       loadActiveShares();
       // Defensive: clear any orphan modal-backdrop from a prior aborted
       // open (e.g. earlier double-open via data-bs-toggle). Bootstrap
       // counts backdrops by show() calls, so a duplicate leaves the
       // second backdrop behind when the modal hides.
       $('.modal-backdrop').remove();
       document.body.classList.remove('modal-open');
       document.body.style.removeProperty('padding-right');
       new bootstrap.Modal(document.getElementById('shareFile-modal')).show();
   }

   function toggleScopeBody(scope) {
       $('.share-scope-body').addClass('d-none');
       $('#scope-' + scope + '-body').removeClass('d-none');
   }
   $(document).on('change', 'input[name="shareScope"]', function () { toggleScopeBody($(this).val()); });

   // Employee typeahead
   var __empSearchTimer;
   $(document).on('input', '#shareEmpSearch', function () {
       clearTimeout(__empSearchTimer);
       var q = $(this).val();
       if (q.length < 2) { $('#shareEmpResults').empty().removeClass('show'); return; }
       __empSearchTimer = setTimeout(function () {
           $.get("{{ route('FileShare.employees') }}", { q: q }, function (r) {
               var html = '';
               (r.results || []).forEach(function (e) {
                   var already = __share.selectedEmployees.some(function (s) { return s.id === e.id; });
                   if (already) return;
                   html += '<div class="item" data-id="' + e.id + '" data-name="' + $('<div>').text(e.name).html() + '">'
                         + '<strong>' + $('<div>').text(e.name).html() + '</strong>'
                         + ' <span class="meta">' + (e.emp_id || '') + (e.dept ? ' · ' + $('<div>').text(e.dept).html() : '') + '</span></div>';
               });
               $('#shareEmpResults').html(html || '<div class="item text-muted">No matches</div>').addClass('show');
           });
       }, 250);
   });
   $(document).on('click', '#shareEmpResults .item[data-id]', function () {
       var id = $(this).data('id');
       var name = $(this).data('name');
       if (__share.selectedEmployees.some(function (s) { return s.id === id; })) return;
       __share.selectedEmployees.push({ id: id, name: name });
       renderEmpChips();
       $('#shareEmpSearch').val('').focus();
       $('#shareEmpResults').empty().removeClass('show');
   });
   function renderEmpChips() {
       var html = '';
       __share.selectedEmployees.forEach(function (e) {
           html += '<span class="emp-chip">' + $('<div>').text(e.name).html() + ' <span class="remove" data-id="' + e.id + '">×</span></span>';
       });
       $('#selectedEmployeeChips').html(html);
   }
   $(document).on('click', '#selectedEmployeeChips .remove', function () {
       var id = $(this).data('id');
       __share.selectedEmployees = __share.selectedEmployees.filter(function (s) { return s.id !== id; });
       renderEmpChips();
   });

   function loadDepartments() {
       $.get("{{ route('FileShare.departments') }}", function (r) {
           var html = '';
           (r.departments || []).forEach(function (d) {
               html += '<label class="item"><input type="checkbox" class="dept-cb" value="' + d.id + '">'
                     + ' <span>' + $('<div>').text(d.name).html() + '</span>'
                     + ' <small class="text-muted ms-auto">' + d.count + ' employees</small></label>';
           });
           $('#deptCheckboxList').html(html || '<div class="text-muted small">No departments</div>');
       });
   }
   $(document).on('change', '.dept-cb', function () {
       var ids = [];
       $('.dept-cb:checked').each(function () { ids.push(parseInt($(this).val(), 10)); });
       __share.selectedDepartments = ids;
   });

   function loadActiveShares() {
       var type = $('#shareTargetType').val();
       var id   = $('#shareTargetId').val();
       var uid  = __share.targetUniqueId;
       if (!id && !uid) { $('#activeSharesList').html('<div class="text-muted small py-2">Save a share first to see it here.</div>'); return; }
       var params = { type: type };
       if (id) params.id = id;
       if (uid) params.unique_id = uid;
       $.get("{{ route('FileShare.index') }}", params, function (r) {
           if (!r.success) { $('#activeSharesList').html('<div class="text-danger small">Could not load shares.</div>'); return; }
           if (!r.shares.length) { $('#activeSharesList').html('<div class="text-muted small py-2">No active shares yet.</div>'); return; }
           var html = '';
           r.shares.forEach(function (s) {
               var recipLabel = '';
               if (s.scope_type === 'organization') recipLabel = '<em>Entire organization</em>';
               else if (s.scope_type === 'departments') recipLabel = s.recipients.map(function (r) { return $('<div>').text(r.name).html(); }).join(', ');
               else recipLabel = s.recipients.map(function (r) { return $('<div>').text(r.name).html(); }).join(', ');
               html += '<div class="item"><div class="recipients"><strong>' + s.scope_type + ':</strong> ' + (recipLabel || '—') + '</div>'
                     + '<button type="button" class="revoke" data-share-id="' + s.id + '" title="Revoke">✕</button></div>';
           });
           $('#activeSharesList').html(html);
       });
   }
   $(document).on('click', '.active-shares-list .revoke', function () {
       var id = $(this).data('share-id');
       if (!confirm('Revoke this share?')) return;
       $.ajax({
           url: "{{ url('resort/file-manage/shares') }}/" + id,
           type: 'DELETE',
           headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
           success: function (r) {
               if (r.success) { toastr.success('Share revoked', '', { positionClass: 'toast-bottom-right' }); loadActiveShares(); }
               else { toastr.error(r.message || 'Revoke failed', 'Error', { positionClass: 'toast-bottom-right' }); }
           },
           error: function (xhr) { toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Revoke failed', 'Error', { positionClass: 'toast-bottom-right' }); }
       });
   });

   // Submit the share
   $(document).on('click', '#shareSubmitBtn', function () {
       var scope = $('input[name="shareScope"]:checked').val();
       var payload = {
           _token: "{{ csrf_token() }}",
           shareable_type: $('#shareTargetType').val(),
           scope_type:     scope,
       };
       // Folder triggers pass the numeric DB id; file triggers only have
       // the unique_id (the file row in the listing doesn't carry the
       // numeric id). Controller accepts either.
       var numericId = $('#shareTargetId').val();
       if (numericId) {
           payload.shareable_id = numericId;
       } else if (__share.targetUniqueId) {
           payload.shareable_unique_id = __share.targetUniqueId;
       } else {
           toastr.error('Unable to identify the item. Refresh and try again.', 'Error', { positionClass: 'toast-bottom-right' });
           return;
       }
       if (scope === 'employees') {
           if (!__share.selectedEmployees.length) { toastr.warning('Pick at least one employee', '', { positionClass: 'toast-bottom-right' }); return; }
           payload.employee_ids = __share.selectedEmployees.map(function (e) { return e.id; });
       } else if (scope === 'departments') {
           if (!__share.selectedDepartments.length) { toastr.warning('Pick at least one department', '', { positionClass: 'toast-bottom-right' }); return; }
           payload.department_ids = __share.selectedDepartments;
       } else if (scope === 'organization') {
           // Org-wide share is a single radio click with no confirmation —
           // a mis-click here silently exposes an item (e.g. Payroll Review)
           // to every active employee at the resort with no expiry.
           if (!confirm('Share "' + __share.targetName + '" with every active employee in your resort? This cannot be undone by anyone but you, and has no expiry.')) {
               return;
           }
       }
       $.post("{{ route('FileShare.store') }}", payload, function (r) {
           if (r.success) {
               var count = (scope === 'employees') ? __share.selectedEmployees.length
                         : (scope === 'departments') ? __share.selectedDepartments.length
                         : 0;
               var label = (scope === 'organization') ? 'your organization'
                         : (count + ' ' + (scope === 'employees' ? 'employee(s)' : 'department(s)'));
               toastr.success('✓ Shared with ' + label, 'Shared', { positionClass: 'toast-bottom-right' });
               // Reset chips/checkboxes and reload active shares list
               __share.selectedEmployees = [];
               __share.selectedDepartments = [];
               renderEmpChips();
               $('.dept-cb').prop('checked', false);
               loadActiveShares();
           } else {
               toastr.error(r.message || 'Could not save share', 'Error', { positionClass: 'toast-bottom-right' });
           }
       }).fail(function (xhr) {
           toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not save share', 'Error', { positionClass: 'toast-bottom-right' });
       });
   });
   $(document).on( "keyup","#Search", function() 
   {
       var Folderselect = $('#Folderselect').val();
       GetTheUpdatedFolder();
   });
   
   $(document).on( "click",".showStructure", function()
   {
        // The virtual "Shared With Me" folder is handled by its own
        // dedicated click handler (registered above), which renders
        // received files locally. Bail here so we don't fire the
        // generic GetFileStructureList AJAX whose empty response would
        // immediately wipe the rendered list.
        if ($(this).attr('data-shared-virtual') === '1') {
            return;
        }

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
                console.log("TEs",response);
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

                    // File types that should be displayed using an <img> tag
                    let imageTypes = ['image/jpeg', 'image/png', 'image/gif'];
      
                    $(".downloadLink").attr('href', fileUrl);

                    $(".downloadLink").attr('data-unique_id', unique_id);
         
                    if (imageTypes.includes(mimeType)) 
                    {
                        $("#ViewModeOfFiles").html(`
                            <img src="${fileUrl}" class="popupimgFileModule" onclick="showImage('${fileUrl}')" alt="Image Preview">`);
                        $("#bd-iframeModel-modal-lg").modal('show');
                    } 
                    // If file type is supported for iframe display
                    else if (iframeTypes.includes(mimeType)) {
                        $("#ViewModeOfFiles").html(`
                            <iframe style="width: 100%; height: 100%;" src="${fileUrl}" allowfullscreen></iframe>
                        `);
                        $("#bd-iframeModel-modal-lg").modal('show');
                    } 
                    // If file is a ZIP or unsupported type → Download it
                    else {
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

    function showImage(src) 
   {
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
           data: {"_token":"{{ csrf_token() }}","id":id,'flag':"categorized"},
       
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
           data: {"_token":"{{ csrf_token() }}","Search":$('.Search').val(),"flag":"categorized"},
       
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
    *
    * The 3-dot action button in each file row (.context-btn) is meant to
    * open the same menu as the right-click contextmenu event. Originally
    * only the contextmenu listener was wired, so left-clicking the
    * ellipsis did nothing — handle that explicitly here.
    */
   function clickListener() {
       document.addEventListener("click", function (e) {
           var clickeElIsLink = clickInsideElement(e, contextMenuLinkClassName);

           if (clickeElIsLink) {
               e.preventDefault();
               menuItemListener(clickeElIsLink);
               return;
           }

           // Left-click on the ellipsis action button → open menu at the
           // button position with the same data attributes the contextmenu
           // path uses (file_id, renameFile).
           var actionTarget = clickInsideElement(e, taskItemClassName);
           if (actionTarget) {
               e.preventDefault();
               e.stopPropagation();
               taskItemInContext = actionTarget;
               $("#file_id").val(actionTarget.getAttribute("data-id") || " ");
               $("#renameFile").val(actionTarget.getAttribute("data-name") || "");
               toggleMenuOn();
               positionMenu(e);
               return;
           }

           var button = e.which || e.button;
           if (button === 1) {
               toggleMenuOff();
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