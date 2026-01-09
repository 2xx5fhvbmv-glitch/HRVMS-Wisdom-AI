@php
     $currentRoute = $page_route;
@endphp
<ul class="nav flex-column">
    @foreach ($menu['menu'] as $s => $ak)
        @if (!isset($ak['submenu']) || empty($ak['submenu']))
            @continue
        @endif
        @php
            $isActiveModule = collect($ak['submenu'])->pluck('route')->contains($currentRoute);
        @endphp
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle  {{ $isActiveModule ? 'show' : '' }}" aria-current="page" href="javascript:void(0)"
                id="dropdownMenuButton{{ $s }}" data-bs-toggle="dropdown" aria-expanded="false">
                <span> {{ $ak['ModuleName'] }}</span>
            </a>
            <div class="dropdown-menu  {{ $isActiveModule ? 'show' : '' }}" aria-labelledby="dropdownMenuButton{{ $s }}">
                <ul class="nav flex-column">
                    @foreach ($ak['submenu'] as $sm)
                            <li>
                                <a class="dropdown-item" href="{{ route($sm['route']) }}">
                                    {{ $sm['PageName'] }}
                                </a>
                            </li>
                    @endforeach
                </ul>
            </div>
        </li>
    @endforeach
</ul>
