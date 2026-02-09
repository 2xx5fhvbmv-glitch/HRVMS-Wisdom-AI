<!-- footer -->
<footer>
    <div class="footer_wrap">
        <p class="m-0 text-light copy">©<?php echo e(date('Y')); ?>  Wisdom AI Pvt Ltd | Every Data Shielded | Creativity Secured | All
            innovations Protected. </p>
    </div>
</footer>
<div class="navigation-wrapper " >
    <div class="navigation-box bg-gradient">
        <div class="navbar-content" id="navbar-mobile-view">
              
        </div>
        
    </div>
</div>

<div class="notification-wrapper ">
    <div class="notification-title d-flex justify-content-between">
        <h5>Notifications</h5>
        <a href="<?php echo e(route('resort.Mark.NotificationList')); ?>" class="text-underline btn-link-yellow">View All</a>
    </div>
    <div class="notification-body">
        <?php
            $resort_id = Auth::guard('resort-admin')->user()->resort_id;
            $user_id =  isset(Auth::guard('resort-admin')->user()->GetEmployee) ? Auth::guard('resort-admin')->user()->GetEmployee->id : '' ;
            print_r(Common::ResortNotification($user_id,$resort_id));
        ?>
        
    </div>
</div>
<?php /**PATH /workspaces/HRVMS-Wisdom-AI/resources/views/resorts/layouts/footer.blade.php ENDPATH**/ ?>