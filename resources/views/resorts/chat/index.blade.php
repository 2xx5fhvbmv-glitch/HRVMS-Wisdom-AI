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
                <div class="col-auto ms-auto">
                    <a class="btn btn-theme " href="#new-chat-modal" data-bs-toggle="modal">
                       New Chat
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row g-md-3 g-2 align-items-center">
                    <div class="col-xl-3 col-lg-5 col-md-7 col-sm-8 ">
                            <div class="input-group">
                                <input type="search" class="form-control " placeholder="Search" />
                                <i class="fa-solid fa-search"></i>
                            </div>
                        </div>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="chat-list">
                    @if($chats->isEmpty())
                        <div class="text-center p-5">
                            <h5 class="text-muted">No chats available</h5>
                            <p class="text-muted  ">Start a new chat to see conversations here</p>
                         </div>
                    @endif
                    @foreach($chats as $chat)
                        <a href="{{ route('resort.chat.show', ['id' => $chat['id'], 'type' => $chat['type']]) }}">
                              <div class="chat-item d-flex align-items-center p-3 border-bottom position-relative">
                                   <div class="chat-avatar me-3">
                                        <img src="{{ $chat['profile'] }}" alt="{{ $chat['name'] }}" class="rounded-circle" width="45" height="45">
                                   </div>
                                   <div class="chat-info flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                             <h6 class="chat-name mb-1">{{ $chat['name'] }}</h6>
                                             <small class="text-muted">{{ $chat['last_seen'] }}</small>
                                        </div>
                                        <p class="chat-message text-muted mb-0">{{ $chat['last_msg'] }}</p>
                                   </div>
                              </div>
                         </a>
                    @endforeach
                </div>
            </div>
        </div>
        
        
    </div>
</div>


<div class="modal fade" id="new-chat-modal" tabindex="-1" aria-labelledby="newChatLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-lg-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newChatLabel">Chat with </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="chatModalBody">
                <div class="employee-search-container p-3 border-bottom bg-light">
                    <div class="input-group">
                        <input type="search" class="form-control" placeholder="Search employees..." id="modalEmployeeSearch" />
                        <span class="input-group-text">
                            <i class="fa-solid fa-search"></i>
                        </span>
                    </div>
                </div>

                <div class="chat-list" id="employeeListModal">
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="chat-container" id="employeeContainer">
                                <!-- Employees will be appended here via AJAX -->
                            </div>
                        </div>
                    </div>
                </div>

                <div class="loading-spinner text-center p-3 d-none" id="modalLoadingSpinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div class="no-results text-center p-4 d-none" id="modalNoResults">
                    <h5 class="text-muted">No employees found</h5>
                    <p class="text-muted">Try adjusting your search criteria</p>
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
     $(document).ready(function(){
           $('#new-chat-modal').on('show.bs.modal', function () {
               $('#modalLoadingSpinner').removeClass('d-none');
               $('#modalNoResults').addClass('d-none');
               $('#employeeContainer').empty();
               getEmployeesForChat();
          });

          $('#modalEmployeeSearch').on('keyup', function() {
               getEmployeesForChat();
          });

          function getEmployeesForChat() {
               var search = $('#modalEmployeeSearch').val();
               $.ajax({
                    url: "{{ route('resort.chat.new') }}",
                    type: 'GET',
                    data: { search: search },
                    beforeSend: function() {
                         $('#modalLoadingSpinner').removeClass('d-none');
                         $('#employeeContainer').empty();
                         $('#modalNoResults').addClass('d-none');
                    },
                    success: function(response) {
                         // Handle successful response
                         $('#employeeContainer').html(response.html);
                    },
                    error: function() {
                         // Handle error
                         $('#modalNoResults').removeClass('d-none');
                    },
                    complete: function() {
                         $('#modalLoadingSpinner').addClass('d-none');
                    }
               });
          }
     });

</script>

@endsection