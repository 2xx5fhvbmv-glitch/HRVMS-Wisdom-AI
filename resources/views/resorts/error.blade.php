@extends('resorts.layouts.app')

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding page-appHedding">
            <div class="row justify-content-between g-md-2 g-1">
                <div class="col-auto">
                    <div class="page-title">
                        <span></span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <h3>{{$msg}}</h3>
            </div>
        </div>
    </div>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
<script >

</script>
@endsection
