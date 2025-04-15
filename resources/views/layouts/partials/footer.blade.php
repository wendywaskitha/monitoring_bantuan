<footer class="footer mt-auto py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                {{-- Gunakan helper setting() untuk nama di footer --}}
                <small>Hak Cipta &copy; {{ date('Y') }} {{ setting('app_name', config('app.name')) }}. All Rights Reserved.</small>
                {{-- <small class="d-block">Dinas Terkait</small> --}}
            </div>
            <div class="col-md-6 text-center text-md-end">
                <a href="#" class="text-white-50 me-3"><i class="bi bi-facebook fs-5"></i></a>
                <a href="#" class="text-white-50"><i class="bi bi-instagram fs-5"></i></a>
            </div>
        </div>
    </div>
</footer>
