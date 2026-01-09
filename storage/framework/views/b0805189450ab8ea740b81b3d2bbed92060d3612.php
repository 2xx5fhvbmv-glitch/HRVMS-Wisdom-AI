
<?php $__env->startSection('page_tab_title' , "Permissions"); ?>

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
          <h1 class="m-0">Resort Permissions</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo e(route('admin.resorts.index')); ?>">Resorts</a></li>
            <li class="breadcrumb-item active">Permissions</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
<style>
    /* Container for checkbox and label */
.custom-checkbox-container {
    /* display: flex;
    align-items: center;
    margin-bottom: 15px; */
}

/* Hide the default checkbox */
input[type="checkbox"].form-check-input {
    display: none;
}

/* Custom checkbox style */
.custom-checkbox-label {
    position: relative;
    padding-left: 22px;
    cursor: pointer;
    font-size: 15px;
    color: #555;
    user-select: none;
    display: inline-block;
}

/* Custom checkbox background */
.custom-checkbox-label::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    height: 20px;
    width: 20px;
    background-color: #f2f2f2;
    border: 2px solid #ccc;
    border-radius: 3px;
    transition: background-color 0.2s, border-color 0.2s;
}

/* Style when the checkbox is checked */
input[type="checkbox"].form-check-input:checked + .custom-checkbox-label::before {
    background-color: #28a745;
    border-color: #28a745;
}

/* Custom checkmark (after the box is checked) */
.custom-checkbox-label::after {
    content: '';
    position: absolute;
    left: 6px;
    top: 3px;
    width: 6px;
    height: 12px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
    opacity: 0;
    transition: opacity 0.2s;
}

/* Show the checkmark when checked */
input[type="checkbox"].form-check-input:checked + .custom-checkbox-label::after {
    opacity: 1;
}

/* Button style */
.custom-button {
    background-color: #28a745;
    border: none;
    padding: 10px 20px;
    color: white;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    border-radius: 5px;
    transition: background-color 0.3s;
    margin-left: 10px;
}

.custom-button:hover {
    background-color: #218838;
}

/* Add some spacing */
/* .custom-checkbox-container + .custom-button {
    margin-top: 10px;
} */

</style>
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">
          <div class="card card-warning">
            <div class="card-body">
              <form method="POST" id="frmEditPermissions" class="form-horizontal" action="<?php echo e(route('admin.resorts.update_permissions', $resort_id)); ?>">
                  <?php echo csrf_field(); ?>
                  <div class="row">
                    <div class="col-md-12">
                        <div id="accordion">
                            <?php if($Resortmodules->isNotEmpty()): ?>
                                <?php $__currentLoopData = $Resortmodules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $modules): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="card">
                                        <div class="card-header" id="heading_<?php echo e($modules->id); ?>">
                                            <h5 class="mb-0">
                                                <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapse_<?php echo e($modules->id); ?>" aria-expanded="false" aria-controls="collapse_<?php echo e($modules->id); ?>">
                                                    <?php echo e($modules->module_name); ?>  
                                                </button>
                                            </h5>
                                        </div>

                                        <?php
                                            $pages = Common::GetModuleWisePages($modules->id);
                                        ?>

                                        <div id="collapse_<?php echo e($modules->id); ?>" class="collapse" aria-labelledby="heading_<?php echo e($modules->id); ?>" data-parent="#accordion">
                                            <div class="card-body">
                                                <table class='table table-responsive border'>
                                                    <tbody>
                                                        <?php if($pages->isNotEmpty()): ?>
                                                            <tr>
                                                                <td>
                                                                    <input class="form-check-input parent-checkbox" data-id="<?php echo e($modules->id); ?>" id="parent_<?php echo e($modules->id); ?>" name="module_permissions_parent" type="checkbox" value="">
                                                                    <label for="parent_<?php echo e($modules->id); ?>">Select All</label>
                                                                </td>
                                                            </tr>
                                                        <?php else: ?>
                                                            <tr>
                                                                <td>No Pages Found..</td>
                                                            </tr>
                                                        <?php endif; ?>
                                                        <tr>
                                                            <?php if($pages->isNotEmpty()): ?>
                                                                <?php $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <td>
                                                                        <div class="custom-checkbox-container">
                                                                            <input class="form-check-input child-checkbox child_parent_<?php echo e($modules->id); ?>"
                                                                            <?php if(array_key_exists($modules->id,$ModuleWisePermission) && in_array($p->id ,$ModuleWisePermission[$modules->id])): ?>
                                                                            checked
                                                                            <?php endif; ?>  id="child_<?php echo e($p->id); ?>" name="module_permissions[<?php echo e($modules->id); ?>][]" type="checkbox" value="<?php echo e($p->id); ?>">
                                                                            <label for="child_<?php echo e($p->id); ?>" class="custom-checkbox-label">
                                                                                <?php echo e($p->page_name); ?>

                                                                            </label>
                                                                        </div>
                                                                    </td>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            <?php endif; ?>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>
                        </div>
                    </div>

                  </div>
                  <div class="container mt-4 table">


                  </div>
                  <hr>
                  <div class="row mt-4">
                      <div class="col-12">
                          <div class="row g-lg-4 g-3">
                              <div class="col-auto">
                                  <button type="submit" class="btn btn-info">Save</button>
                                  <a href="<?php echo e(route('admin.resorts.index')); ?>" class="btn btn-outline-info">Cancel</a>
                              </div>
                          </div>
                      </div>
                  </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-css'); ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<?php $__env->stopSection(); ?>

<?php $__env->startSection('import-scripts'); ?>
<script src="<?php echo e(URL::asset('admin_assets/plugins/datatables/jquery.dataTables.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/datatables-responsive/js/dataTables.responsive.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('admin_assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js')); ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

<script type="text/javascript">
  var url = "<?php echo e(route('admin.role.list')); ?>";

  $(document).ready(function() {
    var table = $('#datatable').DataTable({
      paging: false,
      lengthChange: false,
      ordering: false,
      searching: false,
      info: false
    });

    $(".all").click(function () {
      if ($(this).is(':checked')) {
        $(this).parents("tr").find(".form-check-input").prop("checked", true)
      } else {
        $(this).parents("tr").find(".form-check-input").prop("checked", false)
      }
    });

    $(document).on("click", ".form-check-input", function (e) {
      var numberOfChecked = $(this).parents("tr").find(".form-check-input").filter(':checked').length;
      var totalCheckboxes = $(this).parents("tr").find(".form-check-input").length-1;

      if (numberOfChecked == totalCheckboxes) {
        $(this).parents("tr").find(".all").prop("checked", true);
      }

      if (!$(this).is(':checked')) {
        $(this).parents("tr").find(".all").prop("checked", false);
      }
    });

    $(document).on( "submit", "#frmEditPermissions", function(e) {
      e.preventDefault();

      var url = $(this).attr('action');

      var formData = new FormData(this);

      $.ajax({
        type: "POST",
        url: url,
        data: formData,
        dataType: "JSON",
        contentType: false,
        processData: false,
        cache: false,
        beforeSend: function(msg) {
          HoldOn.open({
            theme:"sk-cube-grid"
          });
        },
        success: function (result) {
          HoldOn.close();

          if( result.success == true ) {
            toastr.success( result.msg, "Success", { positionClass: 'toast-bottom-right'} );
          } else {
            toastr.error( result.msg, "Error", { positionClass: 'toast-bottom-right'} );
          }
        },
        error: function (result) {
          HoldOn.close();
          toastr.error( "Something went wrong", "Error", { positionClass: 'toast-bottom-right'} );
        }
      });
    });
  });
</script>
<script>
    document.querySelectorAll('.select-all').forEach(selectAllCheckbox => {
    selectAllCheckbox.addEventListener('change', function() {
        const positionId = this.dataset.positionId;
        const moduleId = this.dataset.moduleId;
        const isChecked = this.checked;

        document.querySelectorAll(`input[name="permissions[${positionId}][${moduleId}][]"]`).forEach(checkbox => {
            checkbox.checked = isChecked;
        });
    });
});

$(document).ready(function() {
    // When the parent checkbox is clicked
    $('.parent-checkbox').on('change', function() {
        // Get the current state of the parent checkbox (checked or unchecked)
        var isChecked = $(this).is(':checked');

        let moduleid = $(this).data('id');
        $('.child_parent_'+moduleid).prop('checked', isChecked);
    });

    // If any child checkbox is unchecked, uncheck the parent checkbox
    // $('.child-checkbox').on('change', function() {
    //     var allChecked = $(this).closest('tr').find('.child-checkbox').length === $(this).closest('tr').find('.child-checkbox:checked').length;
    //     $(this).closest('tr').find('.parent-checkbox').prop('checked', allChecked);
    // });
});


</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\wamp64\www\Wisdom-Ai\HRVMS-Wisdom-AI\resources\views/admin/resorts/edit-permissions.blade.php ENDPATH**/ ?>