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
                <div class="row justify-content-between g-3">
                    <div class="col-auto">
                        <div class="page-title">
                            <span>Talent Acquisition</span>
                            <h1>{{ $page_title }}</h1>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex justify-content-end">
                            <a href="Javascritp:void(0)" class="btn btn-theme EmailFreshModal">Add Email Template</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-title">
                    <h3>Select an email template</h3>
                </div>
                <div class="row g-4">
                    @if($emailTamplate)
                        @foreach ($emailTamplate as  $e)
                            <div class="col-xl-3 col-md-4 col-sm-6">
                                <div class="emailDrafting-block ">
                                    <div class="ratio cover overlay-block">
                                        <img src="{{ $resortImg }}" alt="image">
                                        <div class="overlay">
                                            <a href="javascript:void(0)"
                                                class="btn btn-themeSkyblue btn-sm edit-modal"  data-id="{{ $e->id }}" data-id="{{ $e->id }}">Edit</a>
                                        </div>

                                    </div>
                                    <h5 id="template_id_{{ $e->id }}">{{ $e->TempleteName }}</h5>

                                </div>
                            </div>
                        @endforeach
                    @endif

                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <form id="editForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel"> Email Template </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Email Template Name -->
                        <div class="col-md-12">
                            <input 
                                type="text" 
                                class="form-control MailTemplete" 
                                name="TempleteName"  
                                id="TempleteName" 
                                placeholder="Enter Email Template Name">
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <!-- Subject -->
                        <div class="col-md-12">
                            <input 
                                type="text" 
                                class="form-control MailSubject" 
                                name="MailSubject"  
                                id="MailSubject" 
                                placeholder="Enter Email Subject (e.g., Welcome to Our Service)">
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <!-- Email Content -->
                        <div class="col-md-12">
                            <textarea 
                                class="form-control MailTemplete" 
                                name="MailTemplete" 
                                rows="15"  
                                id="editor" 
                                placeholder="Enter the email content here..."></textarea>
                        </div>
                    </div>
                    <input type="hidden" name="Mode" id="Mode" value="">
                    <input type="hidden" name="TemplateId" id="TemplateId">

                    <div class="placeholders">
                        <strong>Available Placeholders:</strong>
                        <ul>
                            <li><code class="insert-placeholder" data-placeholder="@{{user_name}}">@{{candidate_name}}</code> - The name of the candidate</li>
                            <li><code class="insert-placeholder" data-placeholder="@{{position_title}}">@{{position_title}}</code> - The Position Title</li>
                            <li><code class="insert-placeholder" data-placeholder="@{{resort_name}}">@{{resort_name}}</code> - The name of the resort</li>
                            <li><code class="insert-placeholder" data-placeholder="@{{interview_type}}">@{{interview_type}}</code> - The user's Interview type</li>
                            <li><code class="insert-placeholder" data-placeholder="@{{interview_date}}">@{{interview_date}}</code> - The user's Interview date</li>
                            <li><code class="insert-placeholder" data-placeholder="@{{interview_time}}">@{{interview_time}}</code> - The user's Interview time</li>
                            <li><code class="insert-placeholder" data-placeholder="@{{interview_link}}">@{{interview_link}}</code> - The user's Interview Link</li>
                            <li><code class="insert-placeholder" data-placeholder="@{{interview_round}}">@{{interview_round}}</code> - The user's Interview Round</li>
                            <li><code class="insert-placeholder" data-placeholder="@{{department}}">@{{department}}</code> - The Department Name</li>
                            <li><code class="insert-placeholder" data-placeholder="@{{completion_date}}">@{{completion_date}}</code> - Completion Date</li>

                        </ul>
                    </div>
                </div>

                <div class="modal-footer">
                    <!-- <a href="#" class="btn btn-themeBlue">Preview</a> -->
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <button class="btn btn-themeBlue">Submit</button>
                </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
    <!-- <script>
        $("document").ready(function() {
            $('#edit-modal').on('shown.bs.modal', function () {
                if (CKEDITOR.instances['editor']) {
                    CKEDITOR.instances['editor'].destroy();  // Remove existing CKEditor instance if present
                }
                CKEDITOR.replace('editor');  // Initialize CKEditor
            });
            $('#edit-modal').on('hidden.bs.modal', function () {
                if (CKEDITOR.instances['editor']) {
                    CKEDITOR.instances['editor'].destroy();  // Destroy CKEditor instance
                }
                // Manually remove notification area if it still exists
                $('.cke_notifications_area').remove();
            });


            $(".edit-modal").on("click",function()
            {

                let id =  $(this).attr('data-id');

                $("#TemplateId").val(id);
                $("#edit-modal").modal('show');
                let url = "{{ route('resort.ta.EmailTemplate', ':id') }}";
                url = url.replace(':id', id);
                    $.ajax({
                        url: url,
                        method: "GET",
                        success: function(response)
                        {

                            if (CKEDITOR.instances["editor"]) {
                                CKEDITOR.instances["editor"].setData(""); // Clear CKEditor content
                            } else {
                                CKEDITOR.replace("editor"); // Initialize CKEditor
                            }

                            // Set new data in CKEditor
                            CKEDITOR.instances["editor"].setData(response.emailTamplate.MailTemplete);

                            // Set other form values
                            $("#TempleteName").val(response.emailTamplate.TempleteName);
                        },
                        error: function(xhr, status, error) {
                            console.error('Error fetching data:', error);
                        }
                    });




                $(".MailTemplete").val(atob(data));
            });
            let Model = $("#Mode").val();

            $('#editForm').validate({
                rules: {

                    MailTemplete:{
                        required: true,
                    },
                    TempleteName:{
                        required: true,
                    }

                },

                messages: {

                    MailTemplete:{
                        required: "Please  Mail Templete",
                    },  TempleteName:{
                        required: "Please  Enter Email Templeate",
                    }

                },
                submitHandler: function(form)
                {
                    const content = CKEDITOR.instances['editor'].getData(); // Use CKEDITOR to get content

                    let MailTemplete = $("#TempleteName").val();
                    let id =  $("#TemplateId").val();

                    let url = "{{ route('resort.ta.StoreEmailTemplate', ':id') }}";
                    url = url.replace(':id', id);

                    $.ajax({
                        url: url,
                        type: "put",
                        data: {
                            "id": id,
                            "MailTemplete": content,
                            "TempleteName":MailTemplete,
                            "_token": "{{ csrf_token() }}"
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success)
                            {
                                $(".edit-modal").modal('hide');
                                toastr.success(response.message, "Success",
                                {
                                    positionClass: 'toast-bottom-right'
                                });
                                $("#template_id_"+id).text(MailTemplete);
                                location.reload();

                            }
                        },
                        error: function(xhr, status, error) {
                            let errs = '';
                            if (xhr.status === 422)
                            {
                                let response = xhr.responseJSON;
                                if (response.errors) {
                                    $.each(response.errors, function(key, value) {
                                        errs += value[0] + '<br>';
                                    });
                                }

                            } else {
                                errs = 'An unexpected error occurred. Please try again.';
                            }

                            toastr.error(errs, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        },
                        complete: function() {  // Changed from afterComplete to complete
                            $("#jobDesEdit-modal form")[0].reset();
                            $("#jobDesEdit-modal input[type='hidden']").val('');
                            // If you're using any rich text editor, reset it here
                            if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['editor']) {
                                CKEDITOR.instances['editor'].setData('');
                            }
                        }
                    });

                }
            });

            $(".EmailFreshModal").on("click",function(){

                $("#edit-modal").modal('show');
                $("#TemplateId").val("Fresh");
            });
        });
    </script> -->
    <script>
        document.querySelectorAll('.insert-placeholder').forEach(function(placeholder) {
            placeholder.addEventListener('click', function() {
                var placeholderText = placeholder.getAttribute('data-placeholder');
                var editor = document.getElementById('editor');
                
                // Insert the placeholder into the email content
                var currentText = editor.value;
                editor.value = currentText + placeholderText;
            });
        });
        $("document").ready(function() {
            $('#edit-modal').on('shown.bs.modal', function () {
                if (CKEDITOR.instances['editor']) {
                    CKEDITOR.instances['editor'].destroy();  // Remove existing CKEditor instance if present
                }
                CKEDITOR.replace('editor');  // Initialize CKEditor
            });

            $('#edit-modal').on('hidden.bs.modal', function () {
                if (CKEDITOR.instances['editor']) {
                    CKEDITOR.instances['editor'].destroy();  // Destroy CKEditor instance
                }
                $('.cke_notifications_area').remove();
            });

            $(".edit-modal").on("click", function() {
                let id = $(this).attr('data-id');

                // Set modal title for edit
                $(".modal-title").text("Edit Email Template");

                $("#TemplateId").val(id);
                $("#edit-modal").modal('show');

                let url = "{{ route('resort.ta.EmailTemplate', ':id') }}";
                url = url.replace(':id', id);

                $.ajax({
                    url: url,
                    method: "GET",
                    success: function(response) {
                        if (CKEDITOR.instances["editor"]) {
                            CKEDITOR.instances["editor"].setData(""); // Clear CKEditor content
                        } else {
                            CKEDITOR.replace("editor"); // Initialize CKEditor
                        }

                        CKEDITOR.instances["editor"].setData(response.emailTamplate.MailTemplete);
                        $("#TempleteName").val(response.emailTamplate.TempleteName);
                        $("#MailSubject").val(response.emailTamplate.MailSubject);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching data:', error);
                    }
                });
            });

            $(".EmailFreshModal").on("click", function() {
                // Set modal title for add
                $(".modal-title").text("Add Email Template");

                $("#edit-modal").modal('show');
                $("#TemplateId").val("Fresh");

                // Clear form fields
                $("#TempleteName").val("");
                $("#MailSubject").val("");
                if (CKEDITOR.instances["editor"]) {
                    CKEDITOR.instances["editor"].setData(""); // Clear CKEditor content
                } else {
                    CKEDITOR.replace("editor"); // Initialize CKEditor
                }
            });

            $('#editForm').validate({
                rules: {
                    TempleteName: {
                        required: true,
                    },
                    MailSubject: {
                        required: true,
                    },
                    MailTemplete: {
                        required: true,
                    }
                },
                messages: {
                    TempleteName: {
                        required: "Please enter the email template name.",
                    },
                    MailSubject: {
                        required: "Please enter the email subject.",
                    },
                    MailTemplete: {
                        required: "Please provide the email content.",
                    }
                },
                submitHandler: function(form) {
                    const content = CKEDITOR.instances['editor'].getData(); // Get content from CKEditor

                    let TempleteName = $("#TempleteName").val();
                    let MailSubject = $("#MailSubject").val();
                    let id = $("#TemplateId").val();

                    let url = "{{ route('resort.ta.StoreEmailTemplate', ':id') }}";
                    url = url.replace(':id', id);

                    $.ajax({
                        url: url,
                        type: "PUT",
                        data: {
                            "id": id,
                            "TempleteName": TempleteName,
                            "MailSubject": MailSubject,
                            "MailTemplete": content,
                            "_token": "{{ csrf_token() }}"
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $("#edit-modal").modal('hide');
                                toastr.success(response.message, "Success", {
                                    positionClass: 'toast-bottom-right'
                                });
                                $("#template_id_" + id).text(TempleteName);
                                location.reload();
                            }
                        },
                        error: function(xhr, status, error) {
                            let errs = '';
                            if (xhr.status === 422) {
                                let response = xhr.responseJSON;
                                if (response.errors) {
                                    $.each(response.errors, function(key, value) {
                                        errs += value[0] + '<br>';
                                    });
                                }
                            } else {
                                errs = 'An unexpected error occurred. Please try again.';
                            }
                            toastr.error(errs, "Error", {
                                positionClass: 'toast-bottom-right'
                            });
                        },
                        complete: function() {
                            $("#editForm")[0].reset();
                            if (CKEDITOR.instances['editor']) {
                                CKEDITOR.instances['editor'].setData('');
                            }
                        }
                    });
                }
            });
        });
    </script>
@endsection

