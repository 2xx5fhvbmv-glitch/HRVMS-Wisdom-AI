@php
    $currentRoute = $page_route;
@endphp
<div class="carosel-menu">
    @foreach ($menu['menu'] as $ak)
        @if (!isset($ak['submenu']) || empty($ak['submenu']))
            @continue
        @endif

            @php
                $isActiveModule = collect($ak['submenu'])->pluck('route')->contains($currentRoute);
            @endphp
            <div class="text-center" id="caroselMenuActive">

                <div class="btn-group">
                    <a type="button" class="dropdown-toggle {{ $isActiveModule ? 'active' : '' }}"  data-bs-toggle="dropdown"
                        aria-expanded="false">
                        {{ $ak['ModuleName'] }}
                    </a>
                    <div class="dropdown-menu carosel-nav-menu">
                        <ul class="nav flex-column">
                            @foreach ($ak['submenu'] as $sm)
                                <li>
                                    <a class="dropdown-item @if($currentRoute == $sm['route']) active-Route @endif" href="{{ route($sm['route'])}}">
                                        {{$sm['PageName']}}
                                    </a>
                                </li>
                            @endforeach             
                        </ul>
                    </div>
                </div>
            </div>
    
    @endforeach
</div>