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
                            <span>People</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                </div>
            </div>
           
               <div>
                    <div class="card">
                         <div class="card-body">
                              <div class="mb-4">
                                   <h4 class="mb-2">Category Name</h4>
                                   <p class="form-control-plaintext">{{ $facilityTourCategory->name }}</p>
                              </div>
                              <div class="mb-4">
                                   <h4 class="mb-2">Thumbnail Image</h4>
                                  
                                   @if($facilityTourCategory->thumbnail_image)
                                   @php
                                       $file = App\Models\ChildFileManagement::where('file_path', $facilityTourCategory->thumbnail_image)->first();
                                        $thumbnailImagePath = $file ? App\Helpers\Common::GetAWSFile($file->id,$resort->resort_id) : '';
                                   @endphp
                                   
                                      <div class="d-inline-block position-relative" style="max-width: 200px;">
                                             <a href="{{ $thumbnailImagePath['NewURLshow'] }}" target="_blank">
                                                   <img src="{{ $thumbnailImagePath['NewURLshow'] }}" alt="Thumbnail Image" class="img-thumbnail" style="max-width: 200px;">
                                             </a>
                                      </div>
                                      <div class="mt-2 d-flex gap-2">
                                              <a href="javascript:void();" class="btn-link btn-icon btn-themeSuccess btn-sm" onclick="openEditModal('{{ $facilityTourCategory->id }}', 'thumbnail', 'null')">
                                                     <i class="fas fa-edit "></i>
                                              </a>
                                             <!-- Delete Icon -->
                                             <a href="javascript:void();" class=" btn-link btn-icon btn-themeDanger btn-sm" onclick="confirmDelete('{{ $facilityTourCategory->id }}', 'thumbnail')">
                                                   <i class="fas fa-trash"></i>
                                             </a>
                                      </div>

                                   @else
                                        <p class="text-muted">No image available.</p>
                                   @endif
                              </div>
                              <div>
                                   <h4 class="mb-2">Facility Tour Images</h4>
                                   @if($facilityTourCategory->facilityTourImages && count($facilityTourCategory->facilityTourImages))
                                        <div class="row">
                                             @foreach($facilityTourCategory->facilityTourImages as $facilityTourImage)
                                                    @php
                                                          $file = App\Models\ChildFileManagement::where('file_path', $facilityTourImage->image)->first();
                                                          $thumbnailImagePath = $file ? App\Helpers\Common::GetAWSFile($file->id,$resort->resort_id) : '';
                                                    @endphp
                                                    <div class="col-md-3 col-6 mb-3">
                                                          <a href="{{ $thumbnailImagePath['NewURLshow'] }}" target="_blank">
                                                                 <img src="{{ $thumbnailImagePath['NewURLshow'] }}" alt="Facility Tour Image" class="img-fluid rounded border">
                                                          </a>
                                                          <div class="mt-2 d-flex gap-2">
                                                                  <a href="javascript:void(0);" class="btn-link btn-icon btn-themeSuccess btn-sm" onclick="openEditModal('{{ $facilityTourCategory->id }}', 'tour_image', '{{ $facilityTourImage->id }}')">
                                                                         <i class="fas fa-edit"></i>
                                                                  </a>
                                                                 <a href="javascript:void(0);" class="btn-link btn-icon btn-themeDanger btn-sm" onclick="confirmDelete('{{ $facilityTourImage->id }}','tour_image')">
                                                                       <i class="fas fa-trash"></i>
                                                                 </a>
                                                          </div>
                                                    </div>
                                             @endforeach
                                        </div>
                                   @else
                                        <p class="text-muted">No facility tour images available.</p>
                                   @endif
                              </div>
                         </div>
                    
                    </div>
               </div>
        </div>
    </div>

    <!-- Edit Modal -->
<div class="modal fade" id="editThumbnailModal" tabindex="-1" aria-labelledby="editThumbnailModal" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered modal-xl">
          <div class="modal-content">
               <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Edit Thumbnail Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
               </div>
               <form id="editThumbnailForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="editId" name="id">
                    <input type="hidden" id="type" name="type">
                    <input type="hidden" id="tourImageId" name="image_id" value="">
                    <div class="modal-body">
                         <div class="col-12">
                              <label class="form-label">Category Icon</label>
                              <div class="input-group">
                                   <input type="file" class="form-control" id="thumbnail_image" name="image" accept="image/*" required>
                                   <label class="input-group-text" for="thumbnail_image">Upload</label>
                              </div>
                              <div id="imagePreview" class="mt-2"></div>
                         </div>
                    </div>
                    <div class="modal-footer justify-content-end">
                         <a href="#" class="btn btn-sm btn-themeGray me-2 " data-bs-dismiss="modal" aria-label="Close">Cancel</a>
                         <button type="submit" class="btn btn-sm btn-theme">Update</button>
                    </div>
               </form>
          </div>
     </div>
</div>

@endsection

@section('import-css')
    
@endsection

@section('import-scripts')
<script>
     function openEditModal(fileId,type, tourImageId = null) {
          document.getElementById('editId').value = fileId;
          document.getElementById('type').value = type;
          if (tourImageId) {
               document.getElementById('tourImageId').value = tourImageId;
          }
          var editModal = new bootstrap.Modal(document.getElementById('editThumbnailModal'));
          editModal.show();
     }
     
     document.getElementById('thumbnail_image').addEventListener('change', function(e) {
     const preview = document.getElementById('imagePreview');
     preview.innerHTML = '';
     if (this.files && this.files[0]) {
          const reader = new FileReader();
          reader.onload = function(e) {
               preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-width:150px;">`;
          }
          reader.readAsDataURL(this.files[0]);
     }
});

$('#editThumbnailForm').on('submit', function(e) {
     e.preventDefault();
     var formData = new FormData(this);

     $.ajax({
          url: "{{ route('people.onboarding.facility-tour-categories.image-update') }}",
          type: "POST",
          data: formData,
          processData: false,
          contentType: false,
          headers: {
               'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          success: function(response) {
               if (response.success === true) {
                    $('#editThumbnailModal').modal('hide');
                    toastr.success(response.message, "Success", {
                         positionClass: 'toast-bottom-right'
                    });
                    setTimeout(function() {
                         location.reload();
                    }, 2000);
               } else {
                    toastr.error(response.message, "Error", {
                         positionClass: 'toast-bottom-right'
                    });
               }
          },
          error: function() {
               toastr.error('Image upload failed.', "Error", {
                    positionClass: 'toast-bottom-right'
               });
          }
     });
});


function confirmDelete(id, type) {
     let url = "{{ route('people.onboarding.facility-tour-categories.image-delete') }}";

     Swal.fire({
          title: 'Are you sure you want to delete?',
          text: 'This action cannot be undone.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes',
          cancelButtonText: 'No',
          confirmButtonColor: '#DD6B55',
     }).then((result) => {
          if (result.isConfirmed) {
               $.ajax({
                    url: url,
                    method: "DELETE",
                    data: { 
                         type: type,
                         id: id,
                         _token: "{{ csrf_token() }}"
                     },
                    success: function(response) {
                         if (response.success) {
                              toastr.success(response.message, "Success", {
                                   positionClass: 'toast-bottom-right',
                              });
                              location.reload();
                         } else {
                              toastr.error(response.message, "Error", {
                                   positionClass: 'toast-bottom-right',
                              });
                         }
                    },
                    error: function(xhr, status, error) {
                         toastr.error('An error occurred while processing your request.', "Error", {
                              positionClass: 'toast-bottom-right',
                         });
                         console.error('Error:', error);
                    },
               });
          }
     });
}

</script>
@endsection
