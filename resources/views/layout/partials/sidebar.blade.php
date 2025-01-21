<!--begin::Sidebar-->
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">         
    <!--begin::Sidebar Brand-->
    <div class="sidebar-brand">         
        <!--begin::Brand Link--> 
        <a class='brand-link' href='{{ route('dashboard') }}'>             
            <!--begin::Brand Image-->           
            <x-authetication::htmls.image :name="'logo.png'" alt="Jang Keyte" class="brand-image opacity-75 shadow w-100"/>  
            <!--begin::Brand Text--> 
            <span class="brand-text fw-light"></span> <!--end::Brand Text--> 
        </a> <!--end::Brand Link--> 
    </div> <!--end::Sidebar Brand--> 

    <!--begin::Sidebar Wrapper-->
    <div class="sidebar-wrapper">
        <nav class="mt-2"> 
            <!--begin::Sidebar Menu-->
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                @include('JangKeyte::layout.partials.menu')
            </ul> <!--end::Sidebar Menu-->
        </nav>
    </div> <!--end::Sidebar Wrapper-->
</aside> <!--end::Sidebar-->
