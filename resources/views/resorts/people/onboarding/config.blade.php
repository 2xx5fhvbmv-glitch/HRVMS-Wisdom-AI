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
                <div class="row g-4">
                    <div class="col-lg-6 ">
                        <div class="card mb-30">
                            <div class="card-title">
                                <div class="row g-2">
                                    <div class="col">
                                        <h3 class="text-nowrap">Itinerary Template Management</h3>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{route('onboarding.itinerary-template.list')}}" class="a-link">View Existing</a>
                                    </div>
                                </div>
                            </div>
                            <form class="mb-1" id="templateForm">
                                <div class="itineraryTemplateManage-main">
                                    <div class="itineraryTemplateManage-block">
                                        <div class="row g-2  mb-md-3 mb-2">
                                            <div class="col"> 
                                                <a href="{{route('onboarding.itinerary-template.create')}}" class="btn btn-themeSkyblue btn-sm">Create Itinerary Template</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <div class="card">
                            <div class="card-title">
                                <div class="row g-3 align-items-center justify-content-between">
                                    <div class="col-auto">
                                        <div class="d-flex justify-content-start align-items-center">
                                            <h3>Cultural Insights</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form id="cultural_insights_form">
                                @csrf
                                <div class="row align-items-top  g-3 mb-3">
                                    <div class="col-lg-12">
                                        <div class="ticketBook-form">
                                            <textarea class="form-control cke_notifications_area" rows="7" name="cultural_insights" id="cultural_insights">{{ $termsAndCondition->cultural_insights ?? '' }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <button type="submit" class="btn btn-themeSkyblue AddAgent mt-3">Save</button>
                                    </div>
                                </div>
                            </form>

                        </div>
                        
                   </div>
                    <div class="col-lg-6 ">
                        <div class="card mb-30">
                            <div class="card-title">
                                <div class="row g-2">
                                    <div class="col">
                                        <h3 class="text-nowrap">Notification Events</h3>
                                    </div>
                                    <div class="col-auto"><a href="{{route('onboarding.events')}}" class="a-link">View Existing</a></div>
                                </div>
                            </div>
                            <form id="eventForm">
                                <div id="event-container">
                                    <div class="event-row row g-2 mb-md-3 mb-2">
                                        <div class="col">
                                            <input type="text" name="events[]" class="form-control" placeholder="Event Name">
                                        </div>
                                        <div class="col-sm-6">
                                            <select class="form-select select2t-none" id="notification_timing"
                                                aria-label="Default select example" name="notification_timing[]">
                                                <option value="">Notification Time</option>
                                                @if($notificationTimings)
                                                    @foreach ($notificationTimings as $key => $value)
                                                        <option value="{{ $key }}">{{ $value }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <button type="button" class="btn btn-sm btn-danger remove-task">Remove</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="button" id="addMoreTask" class="btn btn-sm btn-themeSkyblue">+ Add Task</button>
                                </div>
                                <div class="card-footer text-end mt-3">
                                    <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                                </div>
                            </form>                        
                        </div>

                        <div class="card mb-30">
                            <div class="card-title">
                                <div class="row g-2">
                                    <div class="col">
                                        <h3 class="text-nowrap">Facility Tour Categories</h3>
                                    </div>
                                    <div class="col-auto"><a href="{{route('people.onboarding.facility-tour-categories.index')}}" class="a-link">View Existing</a></div>
                                </div>
                            </div>
                            <form id="facilityTourForm">
                                <div id="facilityTour-container">
                                    <div class="facilityTour-row row g-3 mb-3">
                                        <div class="col-12">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" id="facilityTourName" name="facilityTourName" placeholder="Facility Tour Category Name" required>
                                                <label for="facilityTourName">Category Name</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Category Icon</label>
                                            <div class="input-group">
                                                <input type="file" class="form-control" id="thumbnail_image" name="thumbnail_image" accept="image/*" required>
                                                <label class="input-group-text" for="thumbnail_image">Upload</label>
                                            </div>
                                            <div id="imagePreview" class="mt-2"></div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Additional Images</label>
                                            <div class="input-group">
                                                <input type="file" class="form-control" id="facilityTourImages" name="facilityTourImgs[]" accept="image/*" multiple required>
                                                <label class="input-group-text" for="facilityTourImages">Upload Multiple</label>
                                            </div>
                                            <div id="imagesPreview" class="mt-2 d-flex flex-wrap gap-2"></div>
                                        </div>
                                    </div>
                                </div>
                                
                               
                                {{-- <div class="card-footer text-end mt-3"> --}}
                                     <div class="card-footer text-end">
                                        <a href="javascript:void(0);" class="btn btn-themeBlue btn-sm" id="facilityTourFormSubmit">
                                            <span class="facility-btn-text">Submit</span>
                                            <span class="facility-btn-loader spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                        </a>
                                    </div>
                                    {{-- <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                                </div> --}}
                            </form>                        
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script>
    // Preview for single image (Category Icon)
    document.getElementById('thumbnail_image').addEventListener('change', function (e) {
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (ev) {
                const img = document.createElement('img');
                img.src = ev.target.result;
                img.className = 'img-thumbnail';
                img.style.maxWidth = '120px';
                img.style.maxHeight = '120px';
                preview.appendChild(img);
            };
            reader.readAsDataURL(file);
        }
    });

    // Preview for multiple images (Additional Images)
    document.getElementById('facilityTourImages').addEventListener('change', function (e) {
        const preview = document.getElementById('imagesPreview');
        preview.innerHTML = '';
        const files = e.target.files;
        if (files && files.length > 0) {
            Array.from(files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function (ev) {
                    const img = document.createElement('img');
                    img.src = ev.target.result;
                    img.className = 'img-thumbnail';
                    img.style.maxWidth = '100px';
                    img.style.maxHeight = '100px';
                    img.style.marginRight = '8px';
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        }
    });
</script>

<script type="text/javascript">
    $(document).ready(function () {
        // Initialize Select2
        $('.select2t-none').select2();

        CKEDITOR.replace('cultural_insights');
        
        $('#cultural_insights_form').on('submit', function (e) {
            e.preventDefault();

            // Sync CKEditor content to textarea
            for (var instanceName in CKEDITOR.instances) {
                CKEDITOR.instances[instanceName].updateElement();
            }

            let formData = $(this).serialize();

            $.ajax({
                url: "{{ route('onboarding.cultural_insights.storeOrUpdate') }}", // Use the correct route
                method: "POST",
                data: formData,
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });

                        // Optionally update the CKEditor content with the saved data
                        CKEDITOR.instances['cultural_insights'].setData(response.data.cultural_insights);
                    } else {
                        toastr.success(response.message, "Success", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                },
                error: function(response) {
                    var errors = response.responseJSON;
                    console.log(errors);
                    var errs = '';
                    $.each(errors.errors, function(key, error) { // Adjust according to your response format
                        errs += error + '<br>';
                    });
                    toastr.error(errs, {  positionClass: 'toast-bottom-right' });
                }
            });
        });
    });
    document.addEventListener('DOMContentLoaded', function () {
        // Add More Task
        document.getElementById('addMoreTask').addEventListener('click', function () {
            const container = document.getElementById('event-container');
            const row = document.createElement('div');
            row.className = 'event-row row g-2 mb-md-3 mb-2';
            row.innerHTML = `
                <div class="col">
                    <input type="text" name="events[]" class="form-control" placeholder="Event Name">
                </div>
                <div class="col-sm-6">
                    <select class="form-select select2t-none" id="notification_timing" name="notification_timing[]"
                        aria-label="Default select example">
                        <option value="">Notification Time</option>
                        @if($notificationTimings)
                            @foreach ($notificationTimings as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-sm btn-danger remove-task">Remove</button>
                </div>
            `;
            container.appendChild(row);
            $('.select2t-none').select2(); // Reinitialize Select2
        });

        // Remove Task Row
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-task')) {
                e.target.closest('.event-row').remove();
            }
        });

        // Submit Event Form
        document.getElementById('eventForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch("{{ route('onboarding.events.store') }}", {
                method: "POST",
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    toastr.success(data.message, "Success", {
                        positionClass: 'toast-bottom-right',
                    });
                    // reset form
                    document.getElementById('eventForm').reset();
                }else{
                    toastr.error(data.message, "Error", {
                        positionClass: 'toast-bottom-right',
                    });
                    document.getElementById('eventForm').reset();
                }
                window.setTimeout(function() {
                        window.location.href = data.redirect_url;
                    }, 2000);
            });
        });

        // Facility Tour Category Form Submit
        $('#facilityTourFormSubmit').on('click', function (e) {
            e.preventDefault();
            const $btn = $(this);
            $btn.find('.facility-btn-loader').removeClass('d-none');
            $btn.find('.facility-btn-text').addClass('d-none');
            const formData = new FormData($('#facilityTourForm')[0]);

            $.ajax({
                url: "{{ route('people.onboarding.facility-tour-categories.store') }}",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data) {
                     $btn.find('.facility-btn-loader').addClass('d-none');
                    $btn.find('.facility-btn-text').removeClass('d-none');
                    toastr.success(data.message, "Success", {
                        positionClass: 'toast-bottom-right',
                    });
                    $('#facilityTourForm')[0].reset();
                },
                error: function(xhr) {
                    $btn.find('.facility-btn-loader').addClass('d-none');
                    $btn.find('.facility-btn-text').removeClass('d-none');

                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errorMessages = '';
                        $.each(xhr.responseJSON.errors, function(key, value) {
                            errorMessages += value + '<br>';
                        });
                        toastr.error(errorMessages, "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    } else {
                        toastr.error("Something went wrong!", "Error", {
                            positionClass: 'toast-bottom-right'
                        });
                    }
                }
            });
        });

        // Inline Save
        document.querySelectorAll('.save-inline').forEach(button => {
            button.addEventListener('click', function () {
                const li = this.closest('li');
                const id = li.dataset.id;
                const value = li.querySelector('.event-name').value;

                fetch(`/people/onboarding/events/update/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ name: value })
                })
                .then(res => res.json())
                .then(data => alert(data.message));
            });
        });

        // Inline Delete
        document.querySelectorAll('.delete-event').forEach(button => {
            button.addEventListener('click', function () {
                if (!confirm("Are you sure you want to delete this event?")) return;
                const li = this.closest('li');
                const id = li.dataset.id;

                fetch(`/people/onboarding/events/destroy/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    li.remove();
                });
            });
        });
    });
</script>
@endsection