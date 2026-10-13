@if (session('ok'))
    <p role="status" class="rounded-lg bg-exito-suave p-3 text-sm font-semibold text-exito">{{ session('ok') }}</p>
@endif
@if ($errors->any())
    <div role="alert" class="rounded-lg bg-peligro-suave p-3 text-sm text-peligro">
        <ul class="list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
