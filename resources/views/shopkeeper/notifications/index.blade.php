@extends('shopkeeper.layouts.app')
@section('page_tab_title', $page_title)

@section('content')
<div class="body-wrapper pb-5">
    <div class="container-fluid">
        <div class="page-hedding">
            <div class="row justify-content-between g-3">
                <div class="col-auto">
                    <div class="page-title">
                        <span>Shopkeeper</span>
                        <h1>{{ $page_title }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:50px">#</th>
                                <th>Type</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($notifications as $index => $notif)
                                <tr class="{{ $notif->status === 'unread' ? 'table-light' : '' }}">
                                    <td>{{ $notifications->firstItem() + $index }}</td>
                                    <td><span class="badge badge-themeBlue">{{ $notif->type }}</span></td>
                                    <td>{{ $notif->message }}</td>
                                    <td>
                                        @if($notif->status === 'unread')
                                            <span class="badge badge-themeWarning">Unread</span>
                                        @else
                                            <span class="badge badge-themeSuccess">Read</span>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($notif->created_at)->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fa-solid fa-bell-slash fa-2x mb-2"></i>
                                        <p>No notifications yet</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($notifications->hasPages())
                    <div class="d-flex justify-content-center py-3">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
