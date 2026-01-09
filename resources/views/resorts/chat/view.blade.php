@extends('resorts.layouts.app')
@section('page_tab_title' ,$page_title)


@section('content') 
   <div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Chat System</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
                
            </div>
        </div>

        <div class="card">
            <!-- Chat Interface -->
            <div class="card-body p-0">
                <div class="chat-container">
                    <!-- Chat Header -->
                    <div class="chat-header d-flex align-items-center p-3 border-bottom bg-light">
                        <div class="chat-user-info d-flex align-items-center">
                            <img src="{{ $data['profile'] }}" alt="User" class="rounded-circle me-3" width="40" height="40">
                            <div>
                                <h6 class="mb-0">{{ $data['name'] }}</h6>
                                <small class="text-muted">Online</small>
                            </div>
                        </div>
                        <div class="ms-auto">
                            <button class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Chat Messages Area -->
                    <div class="chat-messages" id="chatMessages">
                       

                    </div>

                    <!-- Chat Input Area -->
                    <div class="chat-input-area p-3 border-top bg-light">
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fa-solid fa-paperclip"></i>
                            </button>
                            <input type="text" class="form-control" placeholder="Type here..." id="messageInput">
                            <button class="btn btn-primary" type="button" id="sendMessage">
                                <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('import-css')
@endsection

@section('import-scripts')

<script>
    

    window.currentUserId = "{{ $resort->id }}";

    window.Echo.connector.pusher.connection.bind('error', function(err) {
        console.error('Pusher connection error:', err);
    });

    window.Echo.private('conversation.' + window.currentUserId)
        .listen('NewConversationMessage', (e) => {
            var isSender = (e.sender_id == window.currentUserId);
            var messageHtml = `
                <div class="message-wrapper mb-3${isSender ? ' sent-message' : ''}">
                    <div class="message ${isSender ? 'sent' : 'received'}">
                        <div class="message-content">${e.message}</div>
                        <div class="message-time">${e.created_at}</div>
                    </div>
                </div>
            `;
            $('#chatMessages').append(messageHtml);
            var container = $('#chatMessages');
            container.scrollTop(container[0].scrollHeight);
        });
</script>

<script>
   $(document).ready(function() {

    // Fetch and render previous conversations when page loads
    function getConversations() {
        var chatId = "{{ $receiver_id }}"; 
        var type = "{{ $type }}"; 
        var url = "{{ route('resort.chat.getConversations', ['type_id' => '__CHAT_ID__', 'type' => '__TYPE__']) }}"
                .replace('__CHAT_ID__', chatId)
                .replace('__TYPE__', type);

        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                // Inject HTML returned from controller
                $('#chatMessages').html(response.html);
                
                // Scroll to bottom after loading
                var container = $('#chatMessages');
                container.scrollTop(container[0].scrollHeight);
            },
            error: function(xhr, status, error) {
                console.error('Error loading conversations:', error);
            }
        });
    }


    // Load conversations initially
    getConversations();

    // Handle send message button click
    $('#sendMessage').on('click', function() {
        var message = $('#messageInput').val().trim();
        if(message) {
            $.ajax({
                url: "{{ route('resort.chat.sendMessage') }}",
                method: 'POST',
                data: {
                    message: message,
                    type_id: "{{ $receiver_id }}",
                    type: "{{ $type }}",
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#messageInput').val('');
                    var currentTime = new Date();
                    var formattedTime = currentTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                    var messageHtml = `
                        <div class="message-wrapper mb-3 sent-message">
                            <div class="message sent">
                                <div class="message-content">${$('<div>').text(message).html()}</div>
                                <div class="message-time">${formattedTime}</div>
                            </div>
                        </div>
                    `;

                    $('#chatMessages').append(messageHtml);
                    var container = $('#chatMessages');
                    container.scrollTop(container[0].scrollHeight);
                },
                error: function() {
                    toastr.error('Failed to send message. Please try again.', 'Error',{
                        positionClass: 'toast-top-right',
                    });
                }
            });
        }
    });
});

</script>

@endsection