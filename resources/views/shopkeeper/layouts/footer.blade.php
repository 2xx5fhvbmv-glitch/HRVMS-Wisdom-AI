<footer class="wai-footer">
    <span class="cp">©{{ date('Y') }} Wisdom AI Pvt Ltd</span>
    <div class="taglines">
        <span class="tp"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>Every Data Shielded</span>
        <span class="tp"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 4.9L19 9.8l-4.1 2.9L16 18l-4-2.7L8 18l1.1-5.3L5 9.8l5.1-1.9z"/></svg>Creativity Secured</span>
        <span class="tp"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>All Innovations Protected</span>
    </div>
</footer>
<div class="navigation-wrapper ">
    <div class="navigation-box bg-gradient">
        <div class="navbar-content">
            <ul class="nav flex-column">
                <li class="nav-item ">
                    <a class="nav-link" href="{{route('shopkeeper.dashboard')}}">
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item ">
                    <a class="nav-link" href="{{route('shopkeeper.products')}}">
                        <span>Products</span>
                    </a>
                </li>
                <li class="nav-item ">
                    <a class="nav-link" href="{{route('shopkeeper.payment.history')}}">
                        <span>Payment History</span>
                    </a>
                </li>
                <li class="nav-item ">
                    <a class="nav-link" href="{{route('shopkeeper.configuration')}}">
                        <span>Configuration</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
<div class="notification-wrapper ">
    <div class="notification-title d-flex justify-content-between">
        <h5>Notifications</h5>
        <a href="{{ route('shopkeeper.notifications.list') }}" class="text-underline btn-link-yellow">View All</a>
    </div>
    <div class="notification-body">
    </div>
</div>