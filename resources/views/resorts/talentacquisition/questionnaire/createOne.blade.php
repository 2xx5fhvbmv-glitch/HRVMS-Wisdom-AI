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
    @csrf
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
                                        <select class="form-select dd-native-select ResortDivision   ResortDivision_1" required data-id="1" name="ResortDivision[]" id="ResortDivision_1" aria-label="Default select example">
                                            <option></option>
                                            @if($ResortDivision->isNotEmpty())

                                                @foreach ($ResortDivision as $d)
                                                    <option value="{{$d->id}}">{{ $d->name}}</option>
                                                @endforeach
                                            @endif

                                        </select>
                                        <div class="dd" data-target="#ResortDivision_1">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">Select Division</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Division">
                                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a division…"></div>
                                                <div class="dd-scroll">
                                                    <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Division</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @if($ResortDivision->isNotEmpty())
                                                        @foreach ($ResortDivision as $d)
                                                        <div class="dd-item" role="option" data-value="{{ $d->id }}"><span class="dd-nm">{{ $d->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <select class="form-select dd-native-select Department Department_1" required  data-id="1" name="Department[]" id="Department_1"  aria-label="Default select example">
                                            <option selected>Select Department</option>

                                        </select>
                                        <div class="dd" data-target="#Department_1">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">Select Department</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Department">
                                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a department…"></div>
                                                <div class="dd-scroll">
                                                    <div class="dd-item active" role="option" data-value="Select Department"><span class="dd-nm">Select Department</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <select class="form-select dd-native-select Position" data-id="1" required name="Position[]"  id="Position_1" aria-label="Default select example">
                                            <option selected>Select Position</option>

                                        </select>
                                        <div class="dd" data-target="#Position_1">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">Select Position</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Position">
                                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                                                <div class="dd-scroll">
                                                    <div class="dd-item active" role="option" data-value="Select Position"><span class="dd-nm">Select Position</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <select class="form-select dd-native-select que_type" name="que_type[]" id="que_type_1" data-id="1" aria-label="Default select example">
                                            <option selected>Question Type</option>
                                            <option value="text">Text</option>
                                            <option value="multiple">Multiple choice questions</option>
                                        </select>
                                        <div class="dd" data-target="#que_type_1">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">Question Type</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Question Type">
                                                <div class="dd-scroll">
                                                    <div class="dd-item active" role="option" data-value="Question Type"><span class="dd-nm">Question Type</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    <div class="dd-item" role="option" data-value="text"><span class="dd-nm">Text</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    <div class="dd-item" role="option" data-value="multiple"><span class="dd-nm">Multiple choice questions</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                </div>
                                            </div>
                                        </div>
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


                        <div class="form-check form-switch form-switchTheme mb-4">
                            <input class="form-check-input" name="VideoQuestion" type="checkbox" role="switch" >
                            <label class="form-check-label" for="flexSwitchCheckDefault">VIDEO QUESTIONS</label>
                        </div>
                        <input type="hidden" id="increment" value="2">
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-themeBlue btn-sm">Submit</button>
                        </div>
                </div>
            </form>
        </div>
    </div>
@include('resorts._emotional_buttons_v2_styles')
@endsection

@section('import-css')
    @include('resorts._dropdown_styles')
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
            $(".AppendHerer").append(` <div class="talentAc-block" id="remove_id_${nos}">
                                <div class="title mb-2">
                                    <h5>QUESTION ${nos}</h5>
                                    <button type="button" class="btn eb-btn-critical btn-sm remove-btn"  data-id="${nos}">Remove</button>
                                </div>
                                <div class="row g-md-4 g-3">
                                    <div class="col-lg-3 col-sm-6">
                                        <select class="form-select dd-native-select ResortDivision   ResortDivision_${nos}" required data-id="${nos}" name="ResortDivision[]" id="ResortDivision_${nos}" aria-label="Default select example">
                                            <option></option>
                                            @if($ResortDivision->isNotEmpty())

                                                @foreach ($ResortDivision as $d)
                                                    <option value="{{$d->id}}">{{ $d->name}}</option>
                                                @endforeach
                                            @endif

                                        </select>
                                        <div class="dd" data-target="#ResortDivision_${nos}">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">Select Division</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Division">
                                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a division…"></div>
                                                <div class="dd-scroll">
                                                    <div class="dd-item active" role="option" data-value=""><span class="dd-nm">Select Division</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    @if($ResortDivision->isNotEmpty())
                                                        @foreach ($ResortDivision as $d)
                                                        <div class="dd-item" role="option" data-value="{{ $d->id }}"><span class="dd-nm">{{ $d->name }}</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <select class="form-select dd-native-select Department Department_${nos}" required  data-id="${nos}" name="Department[]" id="Department_${nos}"  aria-label="Default select example">
                                            <option selected>Select Department</option>

                                        </select>
                                        <div class="dd" data-target="#Department_${nos}">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">Select Department</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Department">
                                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a department…"></div>
                                                <div class="dd-scroll">
                                                    <div class="dd-item active" role="option" data-value="Select Department"><span class="dd-nm">Select Department</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <select class="form-select dd-native-select Position" data-id="${nos}" required name="Position[]"  id="Position_${nos}" aria-label="Default select example">
                                            <option selected>Select Position</option>

                                        </select>
                                        <div class="dd" data-target="#Position_${nos}">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">Select Position</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Position">
                                                <div class="dd-search"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg><input type="text" placeholder="Find a position…"></div>
                                                <div class="dd-scroll">
                                                    <div class="dd-item active" role="option" data-value="Select Position"><span class="dd-nm">Select Position</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <select class="form-select dd-native-select que_type" name="que_type[]" id="que_type_${nos}" data-id="${nos}" aria-label="Default select example">
                                            <option selected>Question Type</option>
                                            <option value="text">Text</option>
                                            <option value="multiple">Multiple choice questions</option>
                                        </select>
                                        <div class="dd" data-target="#que_type_${nos}">
                                            <button type="button" class="dd-trigger" aria-haspopup="listbox" aria-expanded="false">
                                                <span class="dd-lbl">Question Type</span>
                                                <svg class="dd-chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                            </button>
                                            <div class="dd-panel" role="listbox" aria-label="Question Type">
                                                <div class="dd-scroll">
                                                    <div class="dd-item active" role="option" data-value="Question Type"><span class="dd-nm">Question Type</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    <div class="dd-item" role="option" data-value="text"><span class="dd-nm">Text</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                    <div class="dd-item" role="option" data-value="multiple"><span class="dd-nm">Multiple choice questions</span><svg class="dd-tick" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></div>
                                                </div>
                                            </div>
                                        </div>
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

                    nos++
                    $("#increment").val(nos);
        });

            $(document).on('change', '.ResortDivision', function() {
                let l_id = $(this).attr('data-id');


                        $.ajax({
                            url: "{{ route('resort.get.ResortDivision') }}",
                            type: "post",
                            data: {
                                division_id: $(this).val(),
                                "_token": "{{ csrf_token() }}",
                            },
                            success: function(data) {

                                // Clear the dropdown and add a placeholder option
                                $("#Department_"+l_id).html('<option value="">Select Department</option>');
                                window.wisdomDD.rebuild('#Department_'+l_id);
                                console.log(data,".Department_"+l_id);
                                if(data.success == true) {
                                    let string='<option></option>';
                                    // Append new options
                                    $.each(data.data, function(key, value) {

                                        string+='<option value="'+value.id+'">'+value.name+'</option>';
                                    });

                                    $("#Department_"+l_id).html(string);
                                    window.wisdomDD.rebuild('#Department_'+l_id);

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
                window.wisdomDD.rebuild('#Position_'+l_id);
                    $.ajax({
                        url: "{{ route('resort.get.position') }}",
                        type: "post",
                        data: {
                            deptId: deptId,
                            "_token": "{{ csrf_token() }}"
                        },
                        success: function(data) {
                            // Clear the dropdown and add a placeholder option

                            if(data.success == true) {
                                // Append new options

                                $.each(data.data, function(key, value) {
                                    string+='<option value="'+value.id+'">'+value.position_title+'</option>';
                                });
                                $("#Position_"+l_id).html(string);
                                window.wisdomDD.rebuild('#Position_'+l_id);

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

    </script>
    @include('resorts._dropdown_script')
@endsection
