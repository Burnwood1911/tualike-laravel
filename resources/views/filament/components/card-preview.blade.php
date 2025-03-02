@php
    $preview = $this->getContainer()->getParentComponent()->getState('preview');
@endphp

@if($preview)
    <div class="rounded-lg overflow-hidden shadow-lg">
        <img src="{{ $preview }}" alt="Card Preview" class="w-full h-auto">
    </div>
@else
    <div class="rounded-lg bg-gray-100 p-4 text-center text-gray-500">
        Upload an image and configure settings to see preview
    </div>
@endif