<div class="op-segmented">
    <a href="{{ route('tasks.index', request()->except('page')) }}" class="{{ request()->routeIs('tasks.index') ? 'active' : '' }}"><i class="bi bi-list-ul"></i> List</a>
    <a href="{{ route('tasks.board', request()->except(['page', 'status'])) }}" class="{{ request()->routeIs('tasks.board') ? 'active' : '' }}"><i class="bi bi-kanban"></i> Board</a>
</div>
