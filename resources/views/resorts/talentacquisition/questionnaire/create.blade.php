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
                        <div class="talentAc-main talentAcQues-block">
                            <div class="talentAc-block">
                                <div class="title mb-2">
                                    <h5></h5>
                                     </div>
                                <div class="row g-md-4 g-3">

                                    <div class="col-lg-4 col-sm-6">
                                        <select class="form-select ResortDivision   ResortDivision" required data-id="1" name="ResortDivision" id="ResortDivision" aria-label="Default select example">
                                            <option></option>
                                            @if($ResortDivision->isNotEmpty())

                                                @foreach ($ResortDivision as $d)
                                                    <option value="{{$d->id}}">{{ $d->name}}</option>
                                                @endforeach
                                            @endif

                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <select class="form-select Department Department" required  data-id="1" name="Department" id="Department"  aria-label="Default select example">
                                            <option selected>Select Department</option>

                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <select class="form-select Position" data-id="1" required name="Position"  id="Position" aria-label="Default select example">
                                            <option selected>Select Position</option>

                                        </select>
                                    </div>

                                </div>

                            </div>
                            <div class="row g-md-4 g-3 ">
                                <div class="col-lg-3 col-sm-6 align-items-left">
                                        <select class="form-select que_type" name="que_type"  data-id="1" aria-label="Default select example">
                                            <option selected></option>
                                            <option value="text">Text</option>
                                            <option value="multiple">Check Box Button</option>
                                            <option value="Radio">Radio Button</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-sm-6 align-items-left">
                                        <button  type="button" class="btn btn-themeSkyblue btn-sm add-btn AddMore">Add More</button>
                                    </div>
                            </div>
                            <div id="options-container"></div>

                        </div>

                    </div>
                    <div class="form-check form-switch form-switchTheme videoQuestions-switch mb-4 mt-3">
                        <input class="form-check-input" type="checkbox" name="video" role="switch" id="flexSwitchCheckDefault">
                        <label class="form-check-label" for="flexSwitchCheckDefault">VIDEO QUESTIONS</label>
                    </div>
                    <div class="videoQuestions-main d-none">
                        <div class="videoQuestions-block mb-3">
                            <div class=" mb-2 d-flex justify-content-between align-items-center">
                                <h6>Language 1</h6>
                                <a href="#" class="btn btn-themeSkyblue btn-sm addVideo-btn">Add More</a>
                            </div>
                            <div class="row  AppendVideoHerer g-md-4 g-3">
                                <div class=" col-sm-6">
                                    <select class="form-select" name="language[]" id="Language_1" aria-label="Default select example">
                                        <option > </option>
                                        @if($ResortLanguages->isNotEmpty())
                                            <optgroup label="Resort Languages">
                                            @foreach ($ResortLanguages as $l)
                                            <option value="{{$l->id}}">{{$l->name}}</option>
                                            @endforeach
                                            </optgroup>
                                        @endif
                                        @if(!empty($foreignLanguages))
                                            <optgroup label="Foreign Languages">
                                            @foreach ($foreignLanguages as $flKey => $flLabel)
                                            <option value="foreign_{{$flKey}}">{{$flLabel}}</option>
                                            @endforeach
                                            </optgroup>
                                        @endif
                                    </select>
                                </div>
                                <div class=" col-sm-6">
                                    <input type="text" class="form-control" placeholder="Question" name="VideoQuestion[]">
                                </div>
                            </div>
                        </div>
                    </div>
                        <input type="hidden" id="increment" value="1">
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
            $(".que_type").select2({
                "placeholder":'Select Question Type'
            });
            $(".ResortDivision").select2({
                "placeholder":'Select Division Type'
            });
            $(".Department").select2({
                "placeholder":'Select Department'
            });
            $(".Position").select2({
                "placeholder":'Select Position'
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
                                $("#Department").html('<option value="">Select Department</option>');
                                if(data.success == true) {
                                    let string='<option></option>';
                                    // Append new options
                                    $.each(data.data, function(key, value) {

                                        string+='<option value="'+value.id+'">'+value.name+'</option>';
                                    });

                                    $("#Department").html(string);

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
                let currentDepartment = $(this).val();
                let isDuplicate = false;

                let string='<option></option>';
                $("#Position").html(string);
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
                                $("#Position").html(string);

                            }
                        },
                        error: function(response) {
                            toastr.error("Position Not Found", { positionClass: 'toast-bottom-right' });
                        }
                    });
            });

            $(document).on("click", ".AddMore", function() {
                var que_type = $(".que_type").val();
                var nos  =$("#increment").val();

                if(!isNaN(que_type))
                {
                    Swal.fire({
                            title: 'Error!',
                            text: "Please Select Option Type",
                            icon: 'error'
                        })
                }
                else
                {
                    let appendstring='';
                    if(que_type=="text")
                    {
                        appendstring = `<div class="col-12 select_option select_text " data-id="${nos}">
                            <input type="text" class="form-control" placeholder="Question" name="AddQuestion[${nos}][]" fdprocessedid="bjkgk">
                        </div>`;
                    }
                    else if(que_type == "multiple")
                    {
                        appendstring =`<div class="col-12 select_option select_multiple" style="">
                                            <div class="row gx-md-4 gx-3 g-2">
                                                <div class="col-md-6">
                                                    <input type="text" class="form-control" placeholder="Question" name="AddQuestion[${nos}][]" fdprocessedid="bjkgk">
                                                </div>
                                                <div class="col-lg-5 col-md-4 col-sm-10">
                                                    <input type="number" class="form-control total-options" data-id="${nos}"
                                                    placeholder="Total option number">
                                                <ol class="listingNo-wrapper wrapper_${nos} mt-2 d-none"></ol>
                                                </div>
                                                <div class="col-lg-1 col-md-2 col-sm-2">
                                                    <input type="number" name="ans[]" required class="form-control total-options_${nos}"
                                                    placeholder="Ans No">
                                                </div>
                                            </div>
                                        </div>
                                       `;
                    }
                    else if(que_type =="Radio")
                    {
                        appendstring =`<div class="col-12 select_option select_multiple" style="">
                                            <div class="row gx-md-4 gx-3 g-2">
                                                <div class="col-md-6">
                                                    <input type="text" class="form-control" placeholder="Question" name="AddQuestion[${nos}][]" fdprocessedid="bjkgk">
                                                </div>
                                                <div class="col-lg-5 col-md-4 col-sm-10">
                                                    <input type="number" class="form-control total-options" data-id="${nos}"
                                                    placeholder="Total Radio option number" required>
                                                <ol class="listingNo-wrapper wrapper_${nos} mt-2 d-none"></ol>
                                                </div>
                                                <div class="col-lg-1 col-md-2 col-sm-2">
                                                    <input type="number" name="ans[]" required class="form-control total-options_${nos}"
                                                    placeholder="Ans No">
                                                </div>
                                            </div>
                                        </div>
                                       `;
                    }


                     $(".AppendHerer").append(` <div class="talentAc-block" id="remove_id_${nos}">
                                                    <div class="title mb-2">
                                                        <h5>QUESTION ${nos}</h5>
                                                        <button type="button" class="btn btn-danger btn-sm remove-btn"  data-id="${nos}">Remove</button>
                                                    </div> ${appendstring}
                                                </div>`);
                            nos++
                            $("#increment").val(nos);
}

            });

            $(document).on("click",".remove-btn",function(suc){
                let id = $(this).data('id');

                    $("#remove_id_"+id).remove();
                    idnew = id - 1;
                    if(idnew == 0)
                    {
                        idnew = 1;
                    }

                    $(".que_type").val('').trigger('change');
                    $("#increment").val(idnew);
            });

            // video Question


            $('.videoQuestions-switch input').change(function () {
                if ($(this).is(':checked')) {
                    $('.videoQuestions-main').removeClass('d-none'); // Show the div
                } else {
                    $('.videoQuestions-main').addClass('d-none'); // Hide the div
                }
            });

            $("#Language_1").select2({
                        'placeholder':'Select Language',
            });

            $(document).on('click', '.addVideo-btn', function (e) {
                e.preventDefault();

                var nos1  =$("#incrementVideo").val();


                let AppendVideoHerer =`  <div class="videoQuestions-block mb-3" id="Video_remove_id_${nos1}">
                                            <div class=" mb-2 d-flex justify-content-between align-items-center">
                                            <h6>Language ${nos1}</h6>
                                                    <button type="button" class="btn btn-danger removeVideo-btn btn-sm " data-id="${nos1}" >Remove</button>                                                </div>
                                                <div class="row   g-md-4 g-3">
                                                    <div class=" col-sm-6">
                                                        <select class="form-select" name="language[]" id="Language_${nos1}" aria-label="Default select example">
                                                            <option > </option>
                                                            @if($ResortLanguages->isNotEmpty())
                                                                <optgroup label="Resort Languages">
                                                                @foreach ($ResortLanguages as $l)
                                                                <option value="{{$l->id}}">{{$l->name}}</option>
                                                                @endforeach
                                                                </optgroup>
                                                            @endif
                                                            @if(!empty($foreignLanguages))
                                                                <optgroup label="Foreign Languages">
                                                                @foreach ($foreignLanguages as $flKey => $flLabel)
                                                                <option value="foreign_{{$flKey}}">{{$flLabel}}</option>
                                                                @endforeach
                                                                </optgroup>
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


                $("#incrementVideo").val(nos1);
            });
            // End of video question rado


            //  Valdiaton
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

                                window.location.href = "{{ route('resort.ta.Questionnaire') }}";
                            } else {
                                toastr.error(response.msg, "Error", { positionClass: 'toast-bottom-right' });
                            }
                        }
                        // ,
                        // error: function(response) {
                        //     var errors = response.responseJSON;
                        //     var errs = '';
                        //     $.each(errors.errors, function(key, error) {
                        //         errs += error + '<br>';
                        //     });
                        //     toastr.error(errs, { positionClass: 'toast-bottom-right' });
                        // }
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

        $(document).on('input', '.total-options', function () {

const location_id = $(this).data('id');
var que_type = $(".que_type").val();
const olElement = $(this).next('.wrapper_'+location_id);
const totalOptions = parseInt($(this).val());
var que_type = $(".que_type").val();

if (!isNaN(totalOptions) && totalOptions > 0) {
    olElement.removeClass('d-none').empty();

    if(que_type=="Radio")
    {
        for (let i = 0; i < totalOptions; i++)
        {
            const li = $('<li>');
            const input = $('<input>', {
                type: 'text',
                class: 'form-control',
                name:`RadioOption[${location_id}][]`,
                placeholder: `Radio Option ${i + 1}`
            });
            li.append(input);
            olElement.append(li);
        }
    }
    if (que_type=="multiple")
    {
        for (let i = 0; i < totalOptions; i++)
        {
            const li = $('<li>');
            const input = $('<input>', {
                type: 'text',
                class: 'form-control',
                name:`CheckBoxOption[${location_id}][]`,
                placeholder: `Check Box  Option ${i + 1}`
            });
            li.append(input);
            olElement.append(li);
        }
    }

    } else {
        olElement.addClass('d-none').empty();
    }
    });
    $(document).on("click",".removeVideo-btn",function(suc){
                let id = $(this).data('id');

                    $("#Video_remove_id_"+id).remove();
                    idnew1 = id - 1;
                    if(idnew1 == 0)
                    {
                        idnew1 = 1;
                    }

                    $(".que_type").val('').trigger('change');
                    $("#incrementVideo").val(idnew1);
            });

</script>
@endsection

