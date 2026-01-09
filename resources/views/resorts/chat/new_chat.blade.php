
@foreach($datas as $data)
     <a href="{{ route('resort.chat.show', ['id' => $data['id'], 'type' => $data['type']]) }}">
          <div class="chat-item d-flex align-items-center p-3 border-bottom position-relative">
               <div class="chat-avatar me-3">
                    <img src="{{ $data['profile'] }}" alt="{{ $data['name'] }}" class="rounded-circle" width="45" height="45">
               </div>
               <div class="chat-info flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                         <h6 class="chat-name mb-1">{{ $data['name'] }}</h6>
                    </div>
                    <p class="chat-message text-muted mb-0">Active</p>
               </div>
          </div>
     </a>
@endforeach
