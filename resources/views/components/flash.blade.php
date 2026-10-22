@if (session('success'))
    <div class="alert alert-success" role="status">
        <x-icon name="check-circle" class="mt-px" />
        <p>{{ session('success') }}</p>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger" role="alert">
        <x-icon name="alert" class="mt-px" />
        <p>{{ session('error') }}</p>
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <x-icon name="alert" class="mt-px" />
        <div>
            <p class="font-semibold">Please fix the highlighted fields.</p>
            @if ($errors->count() > 1)
                <ul class="mt-1 list-disc pl-4 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endif
