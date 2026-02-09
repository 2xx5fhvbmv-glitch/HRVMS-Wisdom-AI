<link href="<?php echo e(URL::asset('resorts_assets/css/skeleton.css')); ?>" rel=stylesheet>
<link href="<?php echo e(URL::asset('resorts_assets/css/bootstrap.min.css')); ?>" rel=stylesheet>
<link href="<?php echo e(URL::asset('resorts_assets/css/select2.min.css')); ?>" rel=stylesheet>
<link href="<?php echo e(URL::asset('resorts_assets/css/slick-theme.css')); ?>" rel=stylesheet>
<link href="<?php echo e(URL::asset('resorts_assets/css/slick.css')); ?>" rel=stylesheet>
<link href="<?php echo e(URL::asset('resorts_assets/css/bootstrap-datepicker.css')); ?>" rel=stylesheet>
<link href="<?php echo e(URL::asset('resorts_assets/css/all.min.css')); ?>"rel=stylesheet>
<link href="<?php echo e(URL::asset('resorts_assets/css/dataTables.min.css')); ?>" rel=stylesheet>
<link href="<?php echo e(URL::asset('resorts_assets/css/fullcalendar.min.css')); ?>" rel=stylesheet>
<link href="<?php echo e(URL::asset('resorts_assets/css/daterangepicker.css')); ?>" rel=stylesheet>
<link rel="stylesheet" href="<?php echo e(URL::asset('applicant_form_assets/css/croppie.css')); ?>">
<link href="<?php echo e(URL::asset('resorts_assets/css/default.css')); ?>" rel=stylesheet>
<link href="<?php echo e(URL::asset('resorts_assets/css/developer.min.css')); ?>" rel="stylesheet">
<link href="<?php echo e(URL::asset('resorts_assets/css/media.css')); ?>" rel=stylesheet>
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo e(URL::asset('resorts_assets/images/apple-touch-icon.png')); ?>">
<link href="<?php echo e(URL::asset('resorts_assets/css/toastr.min.css')); ?>" rel=stylesheet>
<link href="<?php echo e(URL::asset('resorts_assets/css/sweetalert2.min.css')); ?>" rel=stylesheet>


<link href="<?php echo e(URL::asset('resorts_assets/css/flatpickr.min.css')); ?>" rel=stylesheet>
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo e(Common::getWebsiteFavicon()); ?>">



<style>
     div:where(.swal2-icon) .swal2-icon-content {
          font-size: 0.75em !important;
     }

       .menu-box {
            margin-right: 12px;
        }

        .menu-box .btn {
            font-size: 14px;
            padding: 10px 30px;
            background: hwb(0 0% 100% / 0.2);
            color: white;
            border-radius: 100px;
        }

        .menu-box .dropdown-menu {
            display: none;
            margin-top: 0;
            padding: 12px 0 4px;
            z-index: 9;
            background-color: transparent;
            border: none;
        }

        .menu-box .dropdown-menu ul {
            background-color: #013842;
            border-radius: 12px;
            padding: 4px 0;
        }

        .menu-box .dropdown-menu::before {
            content: "";
            position: absolute;
            top: -10px;
            left: 48px;
            transform: translateX(-50%);
            width: 16px;
            height: 8px;
            border-width: 12px;
            border-style: solid;
            border-color: transparent transparent #013842 transparent;
        }

        .menu-box .dropdown-menu li {
            position: relative;
        }

        .menu-box:hover>.dropdown-menu {
            display: block;
        }

        .menu-box .dropdown-item {
            font-weight: 500;
            position: relative;
            padding: 8px 40px 8px 16px;
            font-size: 14px;
            color: white;
        }

        .menu-box .dropdown-item:hover {
            color: #dfff00;
        }

        .menu-box .dropdown-toggle:hover::after {
            color: #dfff00;

        }

        .menu-box .dropdown-toggle::after {
            color: white;
            right: 16px;
            transform: rotate(-90deg);
        }

        .menu-box .dropdown-submenu .dropdown-menu {
            position: relative;
            position: absolute;
            left: 99%;
            top: 0;
        }



        .menu-box .dropdown-submenu:hover>.dropdown-menu {
            display: block;
        }



        /* replace css  max-width: 408px to max-width: 340px , media.css line 713*/
        @media (max-width: 1199px) {
            .navcarosel-box {
                /* max-width: 408px; */
                max-width: 340px;
            }


        }

        @media (max-width: 991px) {
            .menu-box {
                display: none;
            }
        }
</style>
<?php echo $__env->yieldContent('import-css'); ?>

<style>
    /* Universal Public Holiday Styling - Change color here to update everywhere */
    .public-holiday-header,
    .public-holiday-cell {
        background-color: #ff5a5773 !important;
    }
    
    .public-holiday-header {
        font-weight: bold;
    }
    
    .public-holiday-cell {
        opacity: 0.9;
    }
</style><?php /**PATH /workspaces/HRVMS-Wisdom-AI/resources/views/resorts/layouts/css.blade.php ENDPATH**/ ?>