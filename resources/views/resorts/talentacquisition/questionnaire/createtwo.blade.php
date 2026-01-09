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
                </div>
            </div>
            <form id="StoreQuestionnaire">
                <div class="card">
                    <div class="card-title">
                        <h3>Add Questionnaire For Interview</h3>
                    </div>
                    <div class="AppendHerer">
                        <div class="talentAc-main">
                            <div class="talentAc-block">
                                <div class="title mb-2">
                                    <h5>QUESTION 1</h5>
                                    <button  type="button" class="btn btn-themeSkyblue btn-sm add-btn AddMore">Add More</button>
                                </div>
                                <div class="row g-md-4 g-3">
                                    <div class="col-lg-3 col-sm-6">
                                        <select class="form-select ResortDivision   ResortDivision_1" required data-id="1" name="ResortDivision[]" id="ResortDivision_1" aria-label="Default select example">
                                            <option></option>
                                            @if($ResortDivision->isNotEmpty())

                                                @foreach ($ResortDivision as $d)
                                                    <option value="{{$d->id}}">{{ $d->name}}</option>
                                                @endforeach
                                            @endif

                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <select class="form-select Department Department_1" required  data-id="1" name="Department[]" id="Department_1"  aria-label="Default select example">
                                            <option selected>Select Department</option>

                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <select class="form-select Position" data-id="1" required name="Position[]"  id="Position_1" aria-label="Default select example">
                                            <option selected>Select Position</option>

                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <select class="form-select que_type" name="que_type[]"  data-id="1" aria-label="Default select example">
                                            <option selected>Question Type</option>
                                            <option value="text">Text</option>
                                            <option value="multiple">Multiple choice questions</option>
                                        </select>
                                    </div>
                                    <div class="col-12 select_option select_text_1" style="display:none">
                                        <input type="text"  name="AddQuestion[]" required class="form-control" placeholder="Add Question">
                                    </div>
                                    <div class="col-12 select_option select_multiple_1" style="display:none">
                                        <div class="row gx-md-6 gx-3 g-2">
                                            <div class="col-md-6">
                                                <input type="text" class="form-control" placeholder="Question" name=AddQuestion[]>
                                            </div>
                                            <div class="col-lg-4 col-md-4 col-sm-10">
                                                <input type="number" class="form-control total-options" data-id="1"
                                                    placeholder="Total option number" >
                                                <ol class="listingNo-wrapper wrapper_1 mt-2 d-none"></ol>
                                            </div>
                                            <div class="col-lg-2 col-md-2 col-sm-2">
                                                <label class="form-label mb-0">  <input type="number" name="ans[]" class="form-control total-options_1"
                                                    placeholder="Ans No"></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-check form-switch form-switchTheme videoQuestions-switch mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckDefault">
                        <label class="form-check-label" for="flexSwitchCheckDefault">VIDEO QUESTIONS</label>
                    </div>
                    <div class="videoQuestions-main d-none">
                        <div class="videoQuestions-block">
                            <div class="title mb-2 d-flex justify-content-between align-items-center">
                                <h6>Language 1</h6>
                                <a href="#" class="btn btn-themeSkyblue btn-sm addVideo-btn">Add More</a>
                            </div>
                            <div class="row  AppendVideoHerer g-md-4 g-3">
                                <div class=" col-sm-6">
                                    <select class="form-select" name="language[]" id="Language_1" aria-label="Default select example">
                                        <option > </option>
                                        @if($ResortLanguages->isNotEmpty())
                                            @foreach ($ResortLanguages as $l)
                                            <option value="{{$l->id}}">{{$l->name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class=" col-sm-6">
                                    <input type="text" class="form-control" placeholder="Question" name="VideoQuestion[]">
                                </div>
                            </div>
                        </div>
                    </div>
                        <input type="hidden" id="increment" value="2">
                        <input type="hidden" id="incrementVideo" value="2">

                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                        </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('import-css')
    <style>
        .talentAc-block .title h5{
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }
    </style>
@endsection

@section('import-scripts')
    <script>
        $(document).ready(function() {

            $(document).on('change', '.que_type', function () {
                const block = $(this).closest('.talentAc-block');
                const selectedValue = $(this).val();
                const location_id = $(this).data('id');

                block.find('.select_option').hide();

                if (selectedValue) {
                    block.find('.select_' + selectedValue+'_'+location_id).show();
                }
            });
            $(document).on('input', '.total-options', function () {
                const location_id = $(this).data('id');
                const olElement = $(this).next('.wrapper_'+location_id);
                const totalOptions = parseInt($(this).val());

                if (!isNaN(totalOptions) && totalOptions > 0) {
                    olElement.removeClass('d-none').empty();

                    for (let i = 0; i < totalOptions; i++) {
                        const li = $('<li>');
                        const input = $('<input>', {
                            type: 'text',
                            class: 'form-control',
                            name:`option[${location_id}][]`,
                            placeholder: `Option ${i + 1}`
                        });
                        li.append(input);
                        olElement.append(li);
                    }
                } else {
                    olElement.addClass('d-none').empty();
                }
            });

            $(document).on("click",".AddMore",function(){

            var nos  =$("#increment").val();


            $(".ResortDivision_"+nos).select2({
            'placeholder':'Select ResortDivision',
            });
            $(".Department_"+nos).select2({
            'placeholder':'Select Department',
            });
            $("#Position_"+nos).select2({
            'placeholder':'Select Position',
            });
            $(".AppendHerer").append(` <div class="talentAc-block" id="remove_id_${nos}">
                                <div class="title mb-2">
                                    <h5>QUESTION ${nos}</h5>
                                    <button type="button" class="btn btn-danger btn-sm remove-btn"  data-id="${nos}">Remove</button>
                                </div>
                                <div class="row g-md-4 g-3">
                                    <div class="col-lg-3 col-sm-6">
                                        <select class="form-select ResortDivision   ResortDivision_${nos}" required data-id="${nos}" name="ResortDivision[]" id="ResortDivision_${nos}" aria-label="Default select example">
                                            <option></option>
                                            @if($ResortDivision->isNotEmpty())

                                                @foreach ($ResortDivision as $d)
                                                    <option value="{{$d->id}}">{{ $d->name}}</option>
                                                @endforeach
                                            @endif

                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <select class="form-select Department Department_${nos}" required  data-id="${nos}" name="Department[]" id="Department_${nos}"  aria-label="Default select example">
                                            <option selected>Select Department</option>

                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <select class="form-select Position" data-id="${nos}" required name="Position[]"  id="Position_${nos}" aria-label="Default select example">
                                            <option selected>Select Position</option>

                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <select class="form-select que_type" name="que_type[]" data-id="${nos}" aria-label="Default select example">
                                            <option selected>Question Type</option>
                                            <option value="text">Text</option>
                                            <option value="multiple">Multiple choice questions</option>
                                        </select>
                                    </div>
                                    <div class="col-12 select_option select_text_${nos}" style="display:none">
                                        <input type="text"  name="AddQuestion[]" required class="form-control" placeholder="Add Question">
                                    </div>
                                    <div class="col-12 select_option select_multiple_${nos}" style="display:none">
                                        <div class="row gx-md-6 gx-3 g-2">
                                            <div class="col-md-6">
                                                <input type="text" class="form-control" placeholder="Question" name=AddQuestion[]>
                                            </div>
                                            <div class="col-lg-4 col-md-4 col-sm-10">
                                                <input type="number" class="form-control total-options" data-id="${nos}"
                                                    placeholder="Total option number">
                                                <ol class="listingNo-wrapper wrapper_${nos} mt-2 d-none"></ol>
                                            </div>
                                            <div class="col-lg-2 col-md-2 col-sm-2">
                                                <label class="form-label mb-0">  <input type="number" name="ans[]" class="form-control total-options_${nos}"
                                                    placeholder="Ans No"></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>`);

                    $("#ResortDivision_"+nos).select2({
                    'placeholder':'Select ResortDivision',
                    });
                    $("#Department_"+nos).select2({
                    'placeholder':'Select Department',
                    });
                    $("#Position_"+nos).select2({
                    'placeholder':'Select Position',
                    });

                    nos++
                    $("#increment").val(nos);
        });
            $(".ResortDivision_1").select2({
            'placeholder':'Select ResortDivision',
            });
            $("#Department_1").select2({
            'placeholder':'Select Department',
            });
            $("#Position_1").select2({
            'placeholder':'Select Position',
            });
            $("#Language_1").select2({
            'placeholder':'Select Language',
            });


            $(document).on('change', '.ResortDivision', function() {
                let l_id = $(this).attr('data-id');


                        $.ajax({
                            url: "{{ route('resort.get.ResortDivision') }}",
                            type: "post",
                            data: {
                                division_id: $(this).val(),
                            },
                            success: function(data) {

                                // Clear the dropdown and add a placeholder option
                                $("#Department_"+l_id).html('<option value="">Select Department</option>');
                                console.log(data,".Department_"+l_id);
                                if(data.success == true) {
                                    let string='<option></option>';
                                    // Append new options
                                    $.each(data.data, function(key, value) {

                                        string+='<option value="'+value.id+'">'+value.name+'</option>';
                                    });

                                    $("#Department_"+l_id).html(string);

                                } else {
                                    let string='<option></option>';
                                }
                            },
                            error: function(response) {
                                toastr.error("Department Not Found", { positionClass: 'toast-bottom-right' });
                            }
                        });
            });

            $(document).on('change', '.Department', function() {
                var deptId = $(this).val();
                let l_id = $(this).attr('data-id');
                let currentDepartment = $(this).val();
                let isDuplicate = false;

                let string='<option></option>';
                $("#Position_"+l_id).html(string);
                    $.ajax({
                        url: "{{ route('resort.get.position') }}",
                        type: "post",
                        data: {
                            deptId: deptId
                        },
                        success: function(data) {
                            // Clear the dropdown and add a placeholder option

                            if(data.success == true) {
                                // Append new options

                                $.each(data.data, function(key, value) {
                                    string+='<option value="'+value.id+'">'+value.position_title+'</option>';
                                });
                                $("#Position_"+l_id).html(string);

                            }
                        },
                        error: function(response) {
                            toastr.error("Position Not Found", { positionClass: 'toast-bottom-right' });
                        }
                    });
            });
            $('#StoreQuestionnaire').validate({
                rules: {
                    "ResortDivision[]": { required: true },
                    "Department[]": { required: true },
                    "Position[]": { required: true },
                    "AddQuestion[]": { required: true },
                    "que_type[]": { required: true },
                    "ans[]": { required: true },
                    // "VideoQuestion":{required:true},
                },
                messages: {
                    "ResortDivision[]": { required: "Please select at least one resort division." },
                    "Department[]": { required: "Please select at least one department." },
                    "Position[]": { required: "Please select at least one position." },
                    "AddQuestion[]": { required: "Please enter at least one question." },
                    "que_type[]": { required: "Please select question type." },
                    "ans[]": { required: "Please enter which option is correct." },

                    // "VideoQuestion":{required:"Please Select a video Qustion ."},
                },
                submitHandler: function(form) {
                    // Create FormData object
                    var formData = new FormData(form);


                    // Filter out null or empty values from 'AddQuestion' and 'ans' fields
                    var addQuestionValues = formData.getAll('AddQuestion[]').filter(value => value.trim() !== "");
                    var ansValues = formData.getAll('ans[]').filter(value => value.trim() !== "");

                    // Clear and re-append filtered values
                    formData.delete('AddQuestion[]');
                    formData.delete('ans[]');
                    addQuestionValues.forEach(value => formData.append('AddQuestion[]', value));
                    ansValues.forEach(value => formData.append('ans[]', value));

                    // Perform AJAX request
                    $.ajax({
                        url: "{{ route('resort.ta.store.Questionnaire') }}", // Ensure route is correct
                        type: "POST",
                        data: formData,
                        processData: false, // Important for FormData
                        contentType: false, // Important for FormData
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.msg, "Success", { positionClass: 'toast-bottom-right' });
                                $('#StoreQuestionnaire').get(0).reset();
                            } else {
                                toastr.error(response.msg, "Error", { positionClass: 'toast-bottom-right' });
                            }
                        },
                        error: function(response) {
                            var errors = response.responseJSON;
                            var errs = '';
                            $.each(errors.errors, function(key, error) {
                                errs += error + '<br>';
                            });
                            toastr.error(errs, { positionClass: 'toast-bottom-right' });
                        }
                    });
                },
                errorPlacement: function(error, element) {
                    // Correctly handle Select2 error placement
                    if (element.hasClass("select2-hidden-accessible")) {
                        error.insertAfter(element.next('.select2')); // Adjust this line
                    } else {
                        error.insertAfter(element);
                    }
                },
                highlight: function(element) {
                    // Highlight the Select2 elements properly
                    if ($(element).hasClass("select2-hidden-accessible")) {
                        $(element).next('.select2').find('.select2-selection').addClass('is-invalid');
                    } else {
                        $(element).addClass('is-invalid');
                    }
                },
                unhighlight: function(element) {
                    // Remove highlight from Select2 elements
                    if ($(element).hasClass("select2-hidden-accessible")) {
                        $(element).next('.select2').find('.select2-selection').removeClass('is-invalid');
                    } else {
                        $(element).removeClass('is-invalid');
                    }
                }
            });

        });


        $(document).on("click",".remove-btn",function(suc){
            let id = $(this).data('id');

                $("#remove_id_"+id).remove();
                idnew = id - 1;
                if(idnew == 1)
                {
                    idnew = 2;
                }
                $("#increment").val(idnew);
        });

          $('.videoQuestions-switch input').change(function () {
                if ($(this).is(':checked')) {
                    $('.videoQuestions-main').removeClass('d-none'); // Show the div
                } else {
                    $('.videoQuestions-main').addClass('d-none'); // Hide the div
                }
            });
            $(document).on('click', '.addVideo-btn', function (e) {
                e.preventDefault();

                var nos1  =$("#incrementVideo").val();


                let AppendVideoHerer =`  <div class="videoQuestions-block">
                                            <div class="title mb-2 d-flex justify-content-between align-items-center">
                                            <h6>Language ${nos1}</h6>
                                                    <button type="button" class="btn btn-danger remove-btn btn-sm " data-id="${nos1}" >Remove</button>                                                </div>
                                                <div class="row   g-md-4 g-3">
                                                    <div class=" col-sm-6">
                                                        <select class="form-select" name="language[]" id="Language_${nos1}" aria-label="Default select example">
                                                            <option > </option>
                                                            @if($ResortLanguages->isNotEmpty())
                                                                @foreach ($ResortLanguages as $l)
                                                                <option value="{{$l->id}}">{{$l->name}}</option>
                                                                @endforeach
                                                            @endif
                                                        </select>
                                                    </div>
                                                    <div class=" col-sm-6">
                                                        <input type="text" class="form-control" placeholder="Question" name="VideoQuestion[]">
                                                    </div>
                                                </div>
                                            </div>
                                            </div>`;


                $('.videoQuestions-main').append(AppendVideoHerer);
                $("#Language_"+nos1).select2({
                    'placeholder':'Select Language',
                    });
                nos1++
                alert(nos1);

                $("#incrementVideo").val(nos1);
            });
    </script>
@endsection
