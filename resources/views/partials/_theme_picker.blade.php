{{-- Theme picker row — dropped into the existing profile dropdown menu
     on both resort-admin and shopkeeper. Styling/behaviour lives in
     partials._theme_engine (included once, in <head>). --}}
<li class="wt-theme-row">
    <span class="img-box"><i class="fa-solid fa-circle-half-stroke"></i></span>
    <span class="wt-theme-label">Theme</span>
    <div class="wt-theme-picker">
        <button type="button" class="wt-theme-btn" data-theme="light">Light</button>
        <button type="button" class="wt-theme-btn" data-theme="dark">Dark</button>
        <button type="button" class="wt-theme-btn" data-theme="teal">Teal</button>
    </div>
</li>
