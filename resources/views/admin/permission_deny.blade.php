@extends('admin.layouts.app')
@section('page_tab_title' ,"Permission Denied")

@if ($message = Session::get('success'))
<div class="alert alert-success">
	<p>{{ $message }}</p>
</div>
@endif

@section('content')
<div class="content-wrapper">
	<div class="content-header">
		<div class="container-fluid">
			<div class="row mb-2">
				<div class="col-sm-6">
					<h1 class="m-0">Permission Denied</h1>
				</div>
				<div class="col-sm-6">
					<ol class="breadcrumb float-sm-right">
						<li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Home</a></li>
						<li class="breadcrumb-item active">Permission Denied</li>
					</ol>
				</div>
			</div>
		</div>
	</div>

	<!-- Main content -->
	<section class="content">
		<div class="error-page">
			<h2 class="headline text-danger">500</h2>
			<div class="error-content">
				<h3><i class="fas fa-exclamation-triangle text-danger"></i> Oops! Something went wrong.</h3>
				<p>You do not have a permission to access this page.</p>
			</div>
		</div>
	</section>
</div>
@endsection

@section('import-css')
@endsection

@section('import-scripts')
@endsection