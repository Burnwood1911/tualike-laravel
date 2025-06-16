<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Canvas Area -->
<div class="lg:col-span-2">
    <div class="bg-white rounded-lg shadow-sm border p-4">
        <h3 class="text-lg font-medium mb-4">Card Preview</h3>
        <div id="card-canvas" class="relative border-2 border-dashed border-gray-300 rounded-lg overflow-hidden bg-gray-50"
             style="width: 600px; height: 400px; margin: 0 auto;">

            <!-- Background Image -->
            @if($record->image)
                <img src="{{ Storage::disk('r2')->url($record->image) }}"
                     alt="Card Background"
                     class="absolute inset-0 w-full h-full object-contain bg-white rounded-lg">
            @endif

            <!-- Rest of your draggable elements... -->
            <div id="name-element"
                 class="absolute cursor-move select-none draggable-element z-10"
                 style="left: 50px; top: {{ $record->name_y ?? 100 }}px;
                        font-size: {{ $record->name_font_size ?? 24 }}px;
                        color: {{ $record->name_color ?? '#000000' }};">
                Alex Paul Rossi
            </div>

            {{-- <div id="invite-element"
                 class="absolute cursor-move select-none draggable-element z-10"
                 style="left: {{ $record->invite_x ?? 50 }}px;
                        top: {{ $record->invite_y ?? 150 }}px;
                        font-size: {{ $record->invite_font_size ?? 16 }}px;
                        color: {{ $record->type_color ?? '#666666' }};">
                You are cordially invited
            </div>

            @if(!$record->hide_qr)
                <div id="qr-element"
                     class="absolute cursor-move select-none draggable-element z-10"
                     style="bottom: 20px; {{ $record->qr_position === 'bottom-right' ? 'right: 20px;' : 'left: 20px;' }}">
                    <div class="w-16 h-16 bg-gray-800 rounded flex items-center justify-center text-white text-xs">
                        QR
                    </div>
                </div>
            @endif --}}
        </div>
    </div>
</div>


    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let selectedElement = null;
            let isDragging = false;
            let dragOffset = { x: 0, y: 0 };

            // Make elements draggable
            const draggableElements = document.querySelectorAll('.draggable-element');

            draggableElements.forEach(element => {
                element.addEventListener('mousedown', startDrag);
                element.addEventListener('click', selectElement);
            });

            function startDrag(e) {
                isDragging = true;
                selectedElement = e.target;

                const rect = selectedElement.getBoundingClientRect();
                const canvasRect = document.getElementById('card-canvas').getBoundingClientRect();

                dragOffset.x = e.clientX - rect.left;
                dragOffset.y = e.clientY - rect.top;

                document.addEventListener('mousemove', drag);
                document.addEventListener('mouseup', stopDrag);

                e.preventDefault();
            }

            function drag(e) {
                if (!isDragging || !selectedElement) return;

                const canvas = document.getElementById('card-canvas');
                const canvasRect = canvas.getBoundingClientRect();

                const x = e.clientX - canvasRect.left - dragOffset.x;
                const y = e.clientY - canvasRect.top - dragOffset.y;

                selectedElement.style.left = Math.max(0, Math.min(x, canvasRect.width - selectedElement.offsetWidth)) + 'px';
                selectedElement.style.top = Math.max(0, Math.min(y, canvasRect.height - selectedElement.offsetHeight)) + 'px';
            }

            function stopDrag() {
                isDragging = false;
                document.removeEventListener('mousemove', drag);
                document.removeEventListener('mouseup', stopDrag);
            }

            function selectElement(e) {
                if (isDragging) return;

                // Remove previous selection
                document.querySelectorAll('.draggable-element').forEach(el => {
                    el.classList.remove('ring-2', 'ring-blue-500');
                });

                // Add selection to clicked element
                e.target.classList.add('ring-2', 'ring-blue-500');
                selectedElement = e.target;
            }

            // Property controls
            document.getElementById('name-font-size').addEventListener('input', function(e) {
                const nameElement = document.getElementById('name-element');
                nameElement.style.fontSize = e.target.value + 'px';
                document.getElementById('name-font-size-value').textContent = e.target.value + 'px';
            });

            document.getElementById('name-color').addEventListener('input', function(e) {
                const nameElement = document.getElementById('name-element');
                nameElement.style.color = e.target.value;
            });

            document.getElementById('invite-font-size').addEventListener('input', function(e) {
                const inviteElement = document.getElementById('invite-element');
                inviteElement.style.fontSize = e.target.value + 'px';
                document.getElementById('invite-font-size-value').textContent = e.target.value + 'px';
            });

            document.getElementById('invite-color').addEventListener('input', function(e) {
                const inviteElement = document.getElementById('invite-element');
                inviteElement.style.color = e.target.value;
            });

           // Save changes
document.getElementById('save-changes').addEventListener('click', function() {
    const nameElement = document.getElementById('name-element');
    const inviteElement = document.getElementById('invite-element');

    const data = {
        name_y: parseInt(nameElement.style.top) || {{ $record->name_y ?? 100 }},
        name_font_size: parseInt(nameElement.style.fontSize) || {{ $record->name_font_size ?? 24 }},
        name_color: document.getElementById('name-color').value,
        invite_x: parseInt(inviteElement.style.left) || {{ $record->invite_x ?? 50 }},
        invite_y: parseInt(inviteElement.style.top) || {{ $record->invite_y ?? 150 }},
        invite_font_size: parseInt(inviteElement.style.fontSize) || {{ $record->invite_font_size ?? 16 }},
        type_color: document.getElementById('invite-color').value,
    };

    // Use Livewire to save
    $wire.updateCardData(data);
});
        });
    </script>
    @endpush
</x-filament-panels::page>