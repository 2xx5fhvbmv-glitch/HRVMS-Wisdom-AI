@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)

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
                            <a href="{{ route('resort.ta.Questionnaire') }}" class="btn btn-theme">Back to List</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-30">
                <div class="card-title">
                    <h3>Questionnaire Details</h3>
                </div>
                <div class="card-body">
                    <div class="row g-md-4 g-3 mb-4">
                        <div class="col-lg-4 col-sm-6">
                            <label class="form-label fw-bold">Division</label>
                            <p class="form-control-plaintext">{{ $Division->name ?? '-' }}</p>
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <label class="form-label fw-bold">Department</label>
                            <p class="form-control-plaintext">{{ $Department->name ?? '-' }}</p>
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <label class="form-label fw-bold">Position</label>
                            <p class="form-control-plaintext">{{ $Position->position_title ?? '-' }}</p>
                        </div>
                    </div>

                    @if($Questionnaire->questionnaireChildren->isNotEmpty())
                        <h5 class="mb-3">Questions</h5>
                        @php $qNo = 1; @endphp
                        @foreach($Questionnaire->questionnaireChildren as $item)
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0">Question {{ $qNo }}</h6>
                                        @if($item->questionType == 'multiple')
                                            <span class="badge bg-info">Multiple Choice</span>
                                        @elseif($item->questionType == 'Radio')
                                            <span class="badge bg-warning">Radio</span>
                                        @else
                                            <span class="badge bg-success">Text</span>
                                        @endif
                                    </div>
                                    <p class="mb-2">{{ $item->Question }}</p>

                                    @if($item->questionType == 'multiple' || $item->questionType == 'Radio')
                                        @php $options = json_decode($item->options); @endphp
                                        @if(!empty($options))
                                            <div class="ms-3">
                                                <small class="text-muted">Options:</small>
                                                <ol class="mb-1">
                                                    @foreach($options as $index => $option)
                                                        <li @if($item->ans && $item->ans == ($index + 1)) class="fw-bold text-success" @endif>
                                                            {{ $option }}
                                                            @if($item->ans && $item->ans == ($index + 1))
                                                                <i class="fa-solid fa-check ms-1"></i>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ol>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                            @php $qNo++; @endphp
                        @endforeach
                    @else
                        <p class="text-muted">No questions found.</p>
                    @endif

                    @if($Questionnaire->video == "Yes" && $Questionnaire->questionnaireVideo->isNotEmpty())
                        <h5 class="mt-4 mb-3">Video Questions</h5>
                        @foreach($Questionnaire->questionnaireVideo as $v)
                            <div class="card mb-2">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <small class="text-muted">Language:</small>
                                            @if($v->foreign_language)
                                                <p class="mb-0">{{ config('settings.foreign_languages.'.$v->foreign_language, $v->foreign_language) }}</p>
                                            @else
                                                @php $lang = $ResortLanguages->firstWhere('id', $v->lang_id); @endphp
                                                <p class="mb-0">{{ $lang->name ?? '-' }}</p>
                                            @endif
                                        </div>
                                        <div class="col-sm-8">
                                            <small class="text-muted">Question:</small>
                                            <p class="mb-0">{{ $v->VideoQuestion }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
@endsection
