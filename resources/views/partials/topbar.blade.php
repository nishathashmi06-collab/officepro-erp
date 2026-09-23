@php($recentNotifications = $authUser->notifications()->limit(6)->get())
<header class="op-topbar">
    <button type="button" class="op-icon-btn" data-sidebar-toggle aria-label="Toggle sidebar"><i class="bi bi-list"></i></button>

    <form class="op-search d-none d-md-block" action="{{ route('search') }}" method="GET" role="search">
        <i class="bi bi-search"></i>
        <input type="search" name="q" id="op-global-search" class="form-control" placeholder="Search employees, tasks, documents…" autocomplete="off" value="{{ request()->routeIs('search') ? request('q') : '' }}" aria-label="Global search">
        <kbd class="d-none d-lg-inline">Ctrl K</kbd>
        <div class="op-search-results" id="op-search-results"></div>
    </form>

    <div class="ms-auto d-flex align-items-center gap-1 gap-sm-2">
        <a href="{{ route('search') }}" class="op-icon-btn d-md-none" aria-label="Search"><i class="bi bi-search"></i></a>

        <button type="button" class="op-icon-btn" data-theme-toggle aria-label="Toggle dark mode" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Toggle dark mode">
            <i class="bi bi-moon-stars" data-theme-icon></i>
        </button>

        <div class="dropdown">
            <button type="button" class="op-icon-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Notifications">
                <i class="bi bi-bell"></i>
                <span class="op-dot {{ $unreadNotificationCount ? '' : 'd-none' }}" data-unread-count>{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end op-notif-menu">
                <div class="head">
                    <div><strong>Notifications</strong> <span class="text-muted small">(<span data-unread-count>{{ $unreadNotificationCount }}</span> unread)</span></div>
                    @if ($unreadNotificationCount)
                        <a href="#" class="small fw-semibold" data-mark-all-read="{{ route('notifications.read-all') }}">Mark all read</a>
                    @endif
                </div>
                <div class="op-notif-list">
                    @forelse ($recentNotifications as $n)
                        <a href="{{ route('notifications.open', $n->id) }}" class="op-notif-item {{ $n->read_at ? '' : 'unread' }}">
                            <span class="op-list-icon op-soft-{{ $n->data['color'] ?? 'primary' }}"><i class="bi {{ $n->data['icon'] ?? 'bi-bell' }}"></i></span>
                            <span class="min-w-0 pe-3">
                                <span class="title d-block">{{ $n->data['title'] ?? 'Notification' }}</span>
                                <span class="msg d-block">{{ Str::limit($n->data['message'] ?? '', 90) }}</span>
                                <time>{{ $n->created_at->diffForHumans() }}</time>
                            </span>
                        </a>
                    @empty
                        <div class="text-center text-muted small p-4"><i class="bi bi-bell-slash fs-4 d-block mb-2"></i>You're all caught up.</div>
                    @endforelse
                </div>
                <a href="{{ route('notifications.index') }}" class="d-block text-center small fw-semibold p-2 border-top">View all notifications</a>
            </div>
        </div>

        <div class="dropdown">
            <button type="button" class="op-user-btn" data-bs-toggle="dropdown" aria-expanded="false">
                <x-avatar :name="$authUser->name" :src="$authUser->photo_url" size="34" />
                <span class="meta d-none d-sm-block"><strong>{{ Str::limit($authUser->name, 18) }}</strong><span>{{ $authUser->role?->name }}</span></span>
                <i class="bi bi-chevron-down small text-muted d-none d-sm-inline"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="min-width: 220px">
                <li class="px-3 py-2">
                    <div class="fw-semibold">{{ $authUser->name }}</div>
                    <div class="small text-muted text-truncate">{{ $authUser->email }}</div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="bi bi-person"></i> My Profile</a></li>
                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-sliders"></i> Account & Preferences</a></li>
                <li><a class="dropdown-item" href="{{ route('attendance.index') }}"><i class="bi bi-fingerprint"></i> My Attendance</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}" data-no-lock>
                        @csrf
                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right text-danger"></i> Sign out</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
