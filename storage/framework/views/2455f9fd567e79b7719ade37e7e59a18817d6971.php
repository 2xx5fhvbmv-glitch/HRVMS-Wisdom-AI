
<?php $__env->startSection('page_tab_title' ,"Roles"); ?>

<?php if($message = Session::get('success')): ?>
<div class="alert alert-success">
	<p><?php echo e($message); ?></p>
</div>
<?php endif; ?>

<?php $__env->startSection('content'); ?>
<div class="content-wrapper">
	<div class="content-header">
		<div class="container-fluid">
			<div class="row mb-2">
				<div class="col-sm-6">
					<h1 class="m-0">Roles</h1>
				</div>
				<div class="col-sm-6">
					<ol class="breadcrumb float-sm-right">
						<li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Home</a></li>
						<li class="breadcrumb-item active">Roles</li>
					</ol>
				</div>
			</div>
		</div>
	</div>

	<!-- Main content -->
	<section class="content">
		<div class="container-fluid">
			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header">
							<?php if(Common::hasPermission(config('settings.admin_modules.roles_permissions'),config('settings.permissions.create'))): ?>
								<a href="<?php echo e(route('admin.role.create')); ?>" class="btn btn-primary float-right"><i class="fas fa-plus"></i> Add</a>
							<?php endif; ?>

							<?php if(Common::hasPermission(config('settings.admin_modules.roles_permissions'),config('settings.permissions.delete'))): ?>
								<a href="javascript:void(0)" id="bulk_delete" class="btn btn-danger float-right mr-2"><i class="fas fa-minus"></i> Delete</a>
							<?php endif; ?>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table id="datatable" class="table table-bordered table-hover dt-responsive">
									<thead>
										<tr>
											<th><input type="checkbox" name="all_chk" id="all_chk"></th>
											<th>Name</th>
											<th>Status</th>
											<th>Created At</th>
											<th>Last Updated</th>
											<th>Action</th>
										</tr>
									</thead>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-css'); ?>
	<link rel="stylesheet" href="<?php echo e(URL::asset('admin_assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(URL::asset('admin_assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css')); ?>">
  <link rel="stylesheet" href="<?php echo e(URL::asset('admin_assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css')); ?>">
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-scripts'); ?>
<!-- DataTables  & Plugins -->
<script src="<?php echo e(URL::asset('admin_assets/plugins/datatables/jquery.dataTables.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/datatables-responsive/js/dataTables.responsive.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/datatables-buttons/js/dataTables.buttons.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/jszip/jszip.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/pdfmake/pdfmake.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/pdfmake/vfs_fonts.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/datatables-buttons/js/buttons.html5.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/datatables-buttons/js/buttons.print.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/datatables-buttons/js/buttons.colVis.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('assets/js/jquery.redirect.js')); ?>"></script>
<script>
	$(document).ready(function() {
		var url = "<?php echo e(route('admin.role.list')); ?>";

    var table = $('#datatable').DataTable({
			processing: false,
			ordering: true,
			serverSide: true,
			ajax: url,
			columns: [
				{
					data:'checkbox',
					name:'checkbox',
					orderable: false,
					searchable: false
				},
				{ 
					data: 'name',
					name: 'name',
				},
				{ 
					data: 'status',
					name: 'status',
				},
				{
					data: 'created_at',
					name: 'created_at',
				},
				{
					data: 'updated_at',
					name: 'updated_at',
				},
				{
					data: 'action',
					name: 'action',
					searchable: false,
					orderable: false,
				}
			],
			order: [[3, 'desc']],
			columnDefs: [{
				responsivePriority: 1,
				targets: 0
			}, {
				responsivePriority: 1,
				targets: -1
			}]
		});

    $("#all_chk").click(function () {
    	if ($(this).is(':checked')) {
    		var $view = $('.student_checkbox').prop("checked", true);
    	} else {
    		var $view = $('.student_checkbox').prop("checked", false);
    	}
    });

    $(document).on( 'click', '.student_checkbox', function() {
    	var numberOfChecked = $('.student_checkbox').filter(':checked').length;
    	var totalCheckboxes = $('.student_checkbox').length;

    	if (numberOfChecked == totalCheckboxes) {
    		var $view = $('#all_chk').prop("checked", true);
      }

      if (!$(this).is(':checked')) {
        var $view = $('#all_chk').prop("checked", false);
      }
    });

    $(document).on('click', '.action-delete', function(e) {
    	e.preventDefault();
    	var url = $(this).data('url');

	    swal({
	    	title: 'Sure want to delete?',
	    	text: 'This cannot be undone',
	    	type: 'warning',
	    	showCancelButton: true,
	    	confirmButtonText: 'Yes',
	    	cancelButtonText: 'No',
  			confirmButtonColor: "#DD6B55"
	    }).then(function (success) {
	    	if (success) {
	    		$.ajax({
	    			type: "DELETE",
	    			url: url,
	    			dataType: "json",
	    			beforeSend: function(msg) {
	    				HoldOn.open({
	    					theme:"sk-cube-grid"
	    				});
	    			},
	    		}).done(function(result) {
	    			HoldOn.close();
	    			if( result.success == true ) {
							toastr.success( result.msg, "Success", { positionClass: 'toast-bottom-right'} );

							$('#datatable').DataTable().ajax.reload();
						} else {
							toastr.error( result.msg, "Error", { positionClass: 'toast-bottom-right'} );
						}
	    		}).fail(function(error) {
	    			HoldOn.close();
	    			toastr.error( "Something went wrong", "Error", { positionClass: 'toast-bottom-right'} );
	    		});
	    	}
	    });
	  });

	  $(document).on('click', '#bulk_delete', function(){
    	var id = [];

    	$('.student_checkbox:checked').each(function() {
    		id.push($(this).val());
    	});
    	
    	if( id.length > 0 ) {
    		swal({
    			title: 'Sure want to delete selected data?',
    			text: 'This cannot be undone',
    			type: 'warning',
    			showCancelButton: true,
    			confirmButtonText: 'Yes',
    			cancelButtonText: 'No',
    			confirmButtonColor: "#DD6B55"
    		}).then(function (success) {
    			if (success) {
    				$.ajax({
    					url: "<?php echo e(route('admin.role.massremove')); ?>",
    					data: {id:id},
    					dataType: "json",
    					beforeSend: function(msg) {
    						HoldOn.open({
    							theme:"sk-cube-grid"
    						});
    					},
    				}).done(function(result) {
    					HoldOn.close();
    					$('#all_chk').prop("checked", false);
    					if( result.success == true ) {
    						toastr.success( result.msg, "Success", { positionClass: 'toast-bottom-right'} );

    						$('#datatable').DataTable().ajax.reload();
    					} else {
    						toastr.error( result.msg, "Error", { positionClass: 'toast-bottom-right'} );
    					}
    				}).fail(function(error) {
    					HoldOn.close();
    					$('#all_chk').prop("checked", false);
    					toastr.error( "Something went wrong", "Error", { positionClass: 'toast-bottom-right'} );
    				});
    			}
    		});
    	} else {
    		toastr.error( "Please select atleast one checkbox", "Error", { positionClass: 'toast-bottom-right'} );
    	}
    });

    $(document).on('click', '.changeStatus', function(e) {
    	e.preventDefault();
    	var url = $(this).data('url');

	    $.ajax({
  			type: "GET",
  			url: url,
  			dataType: "json",
  			beforeSend: function(msg) {
  				HoldOn.open({
  					theme:"sk-cube-grid"
  				});
  			},
  		}).done(function(result) {
  			HoldOn.close();
  			if( result.success == true ) {
					$("#datatable").DataTable().ajax.reload();
				} else {
					toastr.error( result.msg, "Error", { positionClass: 'toast-bottom-right'} );
				}
  		}).fail(function(error) {
  			HoldOn.close();
  			toastr.error( "Something went wrong", "Error", { positionClass: 'toast-bottom-right'} );
  		});
	  });
  });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/admin/roles/index.blade.php ENDPATH**/ ?>