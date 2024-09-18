<!--begin::Script--> <!--begin::Third Party Plugin(OverlayScrollbars)-->
<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.3.0/browser/overlayscrollbars.browser.es6.min.js" integrity="sha256-H2VM7BKda+v2Z4+DRy69uknwxjyDRhszjXFhsL4gD3w=" crossorigin="anonymous"></script> <!--end::Third Party Plugin(OverlayScrollbars)-->
<!--begin::Required Plugin(popperjs for Bootstrap 5)-->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha256-whL0tQWoY1Ku1iskqPFvmZ+CHsvmRWx/PIoEvIeWh4I=" crossorigin="anonymous"></script> <!--end::Required Plugin(popperjs for Bootstrap 5)-->
<!--begin::Required Plugin(Bootstrap 5)-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!--begin::Required Plugin(AdminLTE)-->
<script src="{{ asset('assets/js/adminlte.js') }}"></script> <!--end::Required Plugin(AdminLTE)-->
<script src="{{ asset('assets/js/iziToast.min.js') }}"></script>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.0/dist/jquery.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

@stack('scripts')

<!--begin::OverlayScrollbars Configure-->
<script>
      const SELECTOR_SIDEBAR_WRAPPER = ".sidebar-wrapper";
      const Default = {
         scrollbarTheme: "os-theme-light",
         scrollbarAutoHide: "leave",
         scrollbarClickScroll: true,
      };
      document.addEventListener("DOMContentLoaded", function() {
         const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
         if (
            sidebarWrapper &&
            typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== "undefined"
         ) {
            OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                  scrollbars: {
                     theme: Default.scrollbarTheme,
                     autoHide: Default.scrollbarAutoHide,
                     clickScroll: Default.scrollbarClickScroll,
                  },
            });
         }
      });
</script> <!--end::OverlayScrollbars Configure--> 

<!-- OPTIONAL SCRIPTS --> <!-- sortablejs -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js" integrity="sha256-ipiJrswvAR4VAx/th+6zWsdeYmVae0iJuiR+6OqHJHQ=" crossorigin="anonymous"></script> <!-- sortablejs -->
<script>
      const connectedSortables = document.querySelectorAll(".connectedSortable");
      connectedSortables.forEach((connectedSortable) => {
         let sortable = new Sortable(connectedSortable, {
            group: "shared",
            handle: ".card-header",
         });
      });

      const cardHeaders = document.querySelectorAll(
         ".connectedSortable .card-header",
      );
      cardHeaders.forEach((cardHeader) => {
         cardHeader.style.cursor = "move";
      });
</script> 

@if( $errors->any() )
   @foreach( $errors->all() as $error )    
   <script>
      iziToast.error({
            title: '',
            position: 'topCenter',
            message: '{{ $error }}',
      });
   </script>
   @endforeach
@endif

@if( session()->get('error') )
<script>        
   iziToast.error({
      title: '',
      position: 'topRight',
      message: '{{ session()->get("error") }}',
   });
</script>
@endif

@if( session()->get('success') )
<script>        
   iziToast.success({
      title: '',
      position: 'topRight',
      message: '{{ session()->get("success") }}',
   });
</script>
@endif
<!--end::Script-->