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
          <li class="dropdown-submenu">
               <a class="dropdown-item dropdown-toggle {{ $isActiveModule ? 'activeModule' : '' }}" href="javascript:void()">{{ $ak['ModuleName'] }}</a>
               <ul class="dropdown-menu">
                    @foreach ($ak['submenu'] as $sm)
                         <li><a class="dropdown-item @if($currentRoute == $sm['route']) activeModule @endif" href="{{ route($sm['route']) }}">{{ $sm['PageName'] }}</a></li>
                    @endforeach
               </ul>
          </li>
     @endforeach
</ul>