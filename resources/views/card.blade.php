{{-- resources/views/guests/card.blade.php --}}
    <!DOCTYPE html>
<html>
<head>
    <title>Event Invitation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Add Tailwind CSS or your preferred CSS framework --}}
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Add jQuery for AJAX --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
<div class="max-w-2xl w-full space-y-8">
    {{-- Card Container --}}
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        {{-- Guest Card Image --}}
        <div class="relative">
            <img src="{{ $guest->final_url }}"
                 alt="Guest Card"
                 class="w-full h-auto object-cover">
        </div>

        {{-- Response Buttons --}}
        <div class="p-6 space-y-4">
            <h2 class="text-2xl font-bold text-center text-gray-800">
                Will you attend this event?
            </h2>

            <div class="flex space-x-4 justify-center">
                <button onclick="updateAttendance('attending')"
                        class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200">
                    I will attend
                </button>

                <button onclick="updateAttendance('not_attending')"
                        class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-200">
                    I will not attend
                </button>
            </div>

            {{-- Response Message --}}
            <div id="responseMessage" class="text-center hidden">
            </div>
        </div>
    </div>
</div>

<script>
    function updateAttendance(status) {
        $.ajax({
            url: '{{ route("guest.update.attendance", ["eventId" => $guest->event_id, "guestId" => $guest->id]) }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                attendance_status: status
            },
            success: function(response) {
                const messageDiv = $('#responseMessage');
                messageDiv.removeClass('hidden')
                    .removeClass('text-red-600')
                    .addClass('text-green-600')
                    .html('Thank you for your response!');

                // Disable buttons after response
                $('button').attr('disabled', true).addClass('opacity-50');
            },
            error: function(xhr) {
                const messageDiv = $('#responseMessage');
                messageDiv.removeClass('hidden')
                    .removeClass('text-green-600')
                    .addClass('text-red-600')
                    .html('An error occurred. Please try again.');
            }
        });
    }
</script>
</body>
</html>
