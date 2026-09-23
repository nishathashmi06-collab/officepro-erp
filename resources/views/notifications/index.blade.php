@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<x-page-header title="Notifications" :subtitle="$unread.' unread notification(s)'" :breadcrumbs="['Notifications' => null]">
    <x-slot:actions>
        <div class="op-segmented">
            <a href="{{ route('notifications.index') }}" class="{{ request('filter') !== 'unread' ? 'active' : '' }}">All</a>
            <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="{{ request('filter') === 'unread' ? 'active' : '' }}">Unread</a>
        </div>
        @if ($unread)
            <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-light"><i class="bi bi-check2-all"></i> Mark all as read</button></form>
        @endif
        <a href="{{ route('profile.edit') }}#notifications" class="btn btn-light"><i class="bi bi-gear"></i> Preferences</a>
    </x-slot:actions>
</x-page-header>
<x-card :padding="false">
    @forelse ($notifications as $n)
        <div class="op-notif-item {{ $n->read_at ? '' : 'unread' }} align-items-center">
            <span class="op-list-icon op-soft-{{ $n->data['color'] ?? 'primary' }}"><i class="bi {{ $n->data['icon'] ?? 'bi-bell' }}"></i></span>
            <a href="{{ route('notifications.open', $n->id) }}" class="flex-fill min-w-0 text-reset">
                <div class="title">{{ $n->data['title'] ?? 'Notification' }}</div>
                <div class="msg">{{ $n->data['message'] ?? '' }}</div>
                <time>{{ $n->created_at->diffForHumans() }} · {{ $n->created_at->format('M d, Y h:i A') }}</time>
            </a>
            <div class="d-flex gap-1 flex-shrink-0 me-3">
                @unless ($n->read_at)
                    <form method="POST" action="{{ route('notifications.read', $n->id) }}">@csrf<button class="btn btn-light btn-sm btn-icon" title="Mark as read" data-bs-toggle="tooltip"><i class="bi bi-check2"></i></button></form>
                @endunless
                <form method="POST" action="{{ route('notifications.destroy', $n->id) }}">@csrf @method('DELETE')<button class="btn btn-light btn-sm btn-icon" title="Remove" data-bs-toggle="tooltip"><i class="bi bi-x"></i></button></form>
            </div>
        </div>
    @empty
        <x-empty-state icon="bi-bell-slash" title="You're all caught up" message="New notifications about leave, tasks, payroll, documents and assets will appear here." />
    @endforelse
    {{ $notifications->links() }}
</x-card>
@endsection
