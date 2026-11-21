{{-- Left brand panel shared by login / forgot / reset password. Pass $logoHref to change where the logo links. --}}
        <div class=" col-md-6">
            <div class="login-left">
                <div class="img-obj cover"><img src="{{ URL::asset('resorts_assets/images/login.jpg')}}" alt="image"></div>
                <div class="row g-sm-3 g-1 justify-content-between align-items-center ">
                    <div class="col-auto">
                        <a href="{{ $logoHref ?? 'https://www.thewisdom.ai/' }}" class="brand-logo">
                          <img style="height: 80px" src="{{ URL::asset('resorts_assets/images/Brand color 1@3x.png')}}"></a>
                    </div>

                </div>
                <div class="head">
                    <h1>Start Your Journey with us</h1>
                </div>
                <div>
                    <div class="login-slider slider">
                        <div>
                            <p>
                              {{ $settings->contents ?? 'Welcome to Wisdom AI HRVMS.' }}
                            </p>
                            
                        </div>
                    </div>
                </div>
                <div class="row g-sm-3 g-1 justify-content-between align-items-center">
                    <div class="col-auto">
                        <a href="https://www.thewisdom.ai/privacy-policy">Privacy Policy</a>
                    </div>
                    <div class="col-auto">
                        <a href="https://www.thewisdom.ai/terms-and-conditions">Terms of services</a>
                    </div>
                </div>
            </div>
        </div>
