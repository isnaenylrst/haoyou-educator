@if (session('success'))
    <div class="x-alert x-alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif

@if (session('warning'))
    <div class="x-alert x-alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> {{ session('warning') }}</div>
@endif

@if (session('error'))
    <div class="x-alert x-alert-danger"><i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="alert-error">
        <strong>Data belum bisa disimpan:</strong>
        <ul style="margin:6px 0 0 18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
