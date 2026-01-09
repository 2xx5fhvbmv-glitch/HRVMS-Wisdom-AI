<header>
    <!-- As a link -->
    <nav class="bg-body-tertiary">
        <div class="container-fluid">
            <div class="row g-sm-3 g-1 justify-content-between align-items-center">
                <div class="col-xl-auto col-auto ">
                    <a href="#" class="brand-logo"><img src="{{ URL::asset('resorts_assets/images/wisdom-ai.png')}}" /></a>
                </div>

                <div class="col-xl-auto col-auto">
                    <a href="#" class="brand-logo resort-logo">
                        <img  src="{{ Common::GetResortLogo($logo->id)}}" />
                    </a>
                </div>
            </div>
        </div>
    </nav>
</header>
