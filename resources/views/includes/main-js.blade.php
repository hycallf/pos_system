<script src="https://code.jquery.com/jquery-3.7.0.js" integrity="sha256-JlqSTELeR4TLqP0OG9dxM7yDPqX1ox/HfgiSLBj8+kM="
    crossorigin="anonymous"></script>

<!-- Bootstrap -->
{{-- <script src="{{ asset('template/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script> --}}

<!-- DataTables core -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
{{-- <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script> --}}
@vite('resources/js/app.js')
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script defer
    src="https://cdn.datatables.net/v/bs4/jszip-3.10.1/dt-1.13.5/b-2.4.1/b-html5-2.4.1/b-print-2.4.1/sl-1.7.0/datatables.min.js">
</script>
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/1.4.0/perfect-scrollbar.js"></script>
{{-- <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script> --}}
<script defer src="{{ asset('vendor/datatables/buttons.server-side.js') }}"></script>

@include('sweetalert::alert')

@yield('third_party_scripts')

@stack('page_scripts')

@livewireScripts

<script>
    $(document).ready(function() {
        console.log("jQuery version:", $.fn.jquery);
        console.log("DataTable exists?", typeof $.fn.dataTable);
    });
</script>
