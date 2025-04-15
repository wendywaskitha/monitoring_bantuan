{{-- Bootstrap 5 JS Bundle --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
</script>

{{-- Stack untuk JS spesifik halaman --}}
@stack('scripts')

{{-- Script untuk Animasi Navbar Scroll --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.getElementById('mainNavbar');
    if (navbar) {
        const scrollThreshold = 50;
        const addScrolledClass = () => {
            if (window.scrollY > scrollThreshold || document.documentElement.scrollTop >
                scrollThreshold) { // Cek kedua properti
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        };
        addScrolledClass(); // Cek saat load
        window.addEventListener('scroll', addScrolledClass, {
            passive: true
        }); // Event listener pasif
    }
});
</script>
