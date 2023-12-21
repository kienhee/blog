 @if (session('msgSuccess'))
     <div class=" mt-3 mx-3 alert alert-success alert-dismissible" role="alert">
         {{ session('msgSuccess') }}
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
     </div>
 @endif
 @if (session('msgError'))
     <div class="mt-3 mx-3  alert alert-danger alert-dismissible" role="alert">
         {{ session('msgError') }}
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
     </div>
 @endif
