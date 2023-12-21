   <nav aria-label="breadcrumb">
       <ol class="breadcrumb">
           <li class="breadcrumb-item">
               <a
                   href="{{ url()->current() == route('dashboard.index') ? 'javascript:void(0)' : route('dashboard.index') }}">
                   Tổng quan</a>
           </li>
           <li class="breadcrumb-item">
               <a
                   href="{{ url()->current() == route($parentLink) ? 'javascript:void(0)' : route($parentLink) }}">{{ $parentName }}</a>
           </li>
           <li class="breadcrumb-item active">{{ $childrenName }}</li>
       </ol>
   </nav>
