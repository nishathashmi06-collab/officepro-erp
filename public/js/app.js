/* ==========================================================================
   OfficePro — vanilla JavaScript (no framework)
   ========================================================================== */
(function () {
    'use strict';

    const root = document.documentElement;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const OP = (window.OP = window.OP || {});

    const store = {
        get(key) { try { return localStorage.getItem(key); } catch (e) { return null; } },
        set(key, value) { try { localStorage.setItem(key, value); } catch (e) { /* storage unavailable */ } },
    };

    OP.request = function (url, method, body) {
        return fetch(url, {
            method: method || 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: body ? JSON.stringify(body) : undefined,
        }).then(function (res) {
            if (!res.ok) { return res.json().catch(function () { return {}; }).then(function (data) { throw data; }); }
            return res.json();
        });
    };

    OP.savePreference = function (data) {
        const url = document.body.dataset.preferencesUrl;
        if (url) { OP.request(url, 'PUT', data).catch(function () {}); }
    };

    OP.toast = function (message, type) {
        let wrap = document.getElementById('op-toasts');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'op-toasts';
            wrap.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            wrap.style.zIndex = 1090;
            document.body.appendChild(wrap);
        }
        const el = document.createElement('div');
        el.className = 'toast align-items-center border-0 text-bg-' + (type || 'dark');
        el.setAttribute('role', 'status');
        el.innerHTML = '<div class="d-flex"><div class="toast-body"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
        el.querySelector('.toast-body').textContent = message;
        wrap.appendChild(el);
        const toast = new bootstrap.Toast(el, { delay: 3500 });
        toast.show();
        el.addEventListener('hidden.bs.toast', function () { el.remove(); });
    };

    /* ---------- Theme (light / dark) ---------- */
    function applyTheme(theme) {
        root.setAttribute('data-bs-theme', theme);
        document.querySelectorAll('[data-theme-icon]').forEach(function (i) {
            i.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
        });
        document.dispatchEvent(new CustomEvent('op:theme', { detail: theme }));
    }
    applyTheme(root.getAttribute('data-bs-theme') || 'light');

    document.addEventListener('click', function (e) {
        const toggle = e.target.closest('[data-theme-toggle]');
        if (!toggle) return;
        const next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        applyTheme(next);
        store.set('op-theme', next);
        OP.savePreference({ theme: next });
    });

    /* ---------- Sidebar ---------- */
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-sidebar-toggle]')) {
            if (window.matchMedia('(min-width: 992px)').matches) {
                const collapsed = root.classList.toggle('op-sidebar-collapsed');
                store.set('op-sidebar', collapsed ? 'collapsed' : 'expanded');
                OP.savePreference({ sidebar: collapsed ? 'collapsed' : 'expanded' });
            } else {
                root.classList.toggle('op-sidebar-open');
            }
        }
        if (e.target.closest('.op-backdrop')) {
            root.classList.remove('op-sidebar-open');
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') root.classList.remove('op-sidebar-open');
    });

    /* ---------- Confirm dialogs for destructive forms ---------- */
    let pendingForm = null;
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.matches('[data-confirm]') || form.dataset.confirmed === '1') return;
        e.preventDefault();
        const modalEl = document.getElementById('op-confirm-modal');
        if (!modalEl) { if (window.confirm(form.dataset.confirm)) { form.dataset.confirmed = '1'; form.submit(); } return; }
        pendingForm = form;
        modalEl.querySelector('[data-confirm-message]').textContent = form.dataset.confirm;
        modalEl.querySelector('[data-confirm-title]').textContent = form.dataset.confirmTitle || 'Are you sure?';
        const btn = modalEl.querySelector('[data-confirm-accept]');
        btn.textContent = form.dataset.confirmButton || 'Confirm';
        btn.className = 'btn ' + (form.dataset.confirmVariant === 'primary' ? 'btn-primary' : 'btn-danger');
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });
    document.addEventListener('click', function (e) {
        if (!e.target.closest('[data-confirm-accept]') || !pendingForm) return;
        pendingForm.dataset.confirmed = '1';
        const submitter = pendingForm;
        pendingForm = null;
        bootstrap.Modal.getInstance(document.getElementById('op-confirm-modal')).hide();
        submitter.submit();
    });

    /* Prevent double submission of regular forms. */
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (e.defaultPrevented || form.method.toLowerCase() === 'get' || form.hasAttribute('data-no-lock')) return;
        form.querySelectorAll('button[type="submit"]').forEach(function (b) {
            b.disabled = true;
            if (!b.querySelector('.spinner-border')) {
                b.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>');
            }
        });
    });
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('button[type="submit"][disabled]').forEach(function (b) {
            b.disabled = false;
            b.querySelector('.spinner-border')?.remove();
        });
    });

    /* ---------- Clickable table rows ---------- */
    document.addEventListener('click', function (e) {
        const row = e.target.closest('tr[data-href]');
        if (!row || e.target.closest('a, button, input, select, label, form')) return;
        window.location.href = row.dataset.href;
    });

    /* ---------- Global search with live results ---------- */
    const searchInput = document.getElementById('op-global-search');
    const searchBox = document.getElementById('op-search-results');
    if (searchInput && searchBox) {
        let timer = null;
        let controller = null;
        const escape = function (s) { const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };

        const render = function (groups, term) {
            const names = Object.keys(groups);
            if (!names.length) {
                searchBox.innerHTML = '<div class="p-3 text-center text-muted small">No results for “' + escape(term) + '”</div>';
            } else {
                searchBox.innerHTML = names.map(function (name) {
                    return '<div class="group-title small-caps">' + escape(name) + '</div>' + groups[name].map(function (item) {
                        return '<a href="' + escape(item.url) + '"><i class="bi ' + escape(item.icon) + '"></i><div class="min-w-0"><div class="fw-semibold text-truncate">' + escape(item.title) + '</div><div class="small text-muted text-truncate">' + escape(item.subtitle) + '</div></div></a>';
                    }).join('');
                }).join('') + '<a href="' + searchInput.form.action + '?q=' + encodeURIComponent(term) + '" class="justify-content-center small fw-semibold text-primary">View all results</a>';
            }
            searchBox.classList.add('show');
        };

        searchInput.addEventListener('input', function () {
            clearTimeout(timer);
            const term = searchInput.value.trim();
            if (term.length < 2) { searchBox.classList.remove('show'); return; }
            timer = setTimeout(function () {
                if (controller) controller.abort();
                controller = new AbortController();
                fetch(searchInput.form.action + '?q=' + encodeURIComponent(term), { headers: { Accept: 'application/json' }, signal: controller.signal, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) { render(data.groups || {}, term); })
                    .catch(function () {});
            }, 250);
        });
        searchInput.addEventListener('keydown', function (e) {
            const links = Array.from(searchBox.querySelectorAll('a'));
            if (!links.length || !searchBox.classList.contains('show')) return;
            let idx = links.findIndex(function (a) { return a.classList.contains('active'); });
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                links.forEach(function (a) { a.classList.remove('active'); });
                idx = e.key === 'ArrowDown' ? Math.min(links.length - 1, idx + 1) : Math.max(0, idx - 1);
                links[idx].classList.add('active');
                links[idx].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter' && idx >= 0) {
                e.preventDefault();
                window.location.href = links[idx].href;
            } else if (e.key === 'Escape') {
                searchBox.classList.remove('show');
            }
        });
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.op-search')) searchBox.classList.remove('show');
        });
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); searchInput.focus(); searchInput.select(); }
        });
    }

    /* ---------- Notifications ---------- */
    function setUnread(count) {
        document.querySelectorAll('[data-unread-count]').forEach(function (el) {
            el.textContent = count > 99 ? '99+' : count;
            el.classList.toggle('d-none', count <= 0);
        });
    }
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-mark-all-read]');
        if (!btn) return;
        e.preventDefault();
        OP.request(btn.dataset.markAllRead, 'POST').then(function () {
            document.querySelectorAll('.op-notif-item.unread').forEach(function (el) { el.classList.remove('unread'); });
            setUnread(0);
        });
    });

    /* ---------- Live clock ---------- */
    document.querySelectorAll('[data-clock]').forEach(function (el) {
        const tick = function () {
            el.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        };
        tick();
        setInterval(tick, 1000);
    });

    /* ---------- Toggle sections (data-toggle-target) ---------- */
    document.querySelectorAll('[data-toggle-target]').forEach(function (input) {
        const sync = function () {
            const target = document.querySelector(input.dataset.toggleTarget);
            if (!target) return;
            const show = input.type === 'checkbox' ? input.checked : input.value !== '';
            target.classList.toggle('d-none', !show);
        };
        input.addEventListener('change', sync);
        sync();
    });

    /* ---------- Image preview ---------- */
    document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            const img = document.querySelector(input.dataset.preview);
            const file = input.files && input.files[0];
            if (img && file && file.type.startsWith('image/')) {
                img.src = URL.createObjectURL(file);
                img.classList.remove('d-none');
            }
        });
    });

    /* ---------- Payroll live calculation ---------- */
    const payrollForm = document.querySelector('[data-payroll-form]');
    if (payrollForm) {
        const val = function (name) { return parseFloat(payrollForm.querySelector('[name="' + name + '"]')?.value) || 0; };
        const fmt = function (n) { return (payrollForm.dataset.currency || '') + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
        const recalc = function () {
            const gross = val('basic_salary') + val('allowances') + val('overtime') + val('bonus');
            const deductions = val('deductions') + val('tax') + val('other_deductions');
            const net = gross - deductions;
            payrollForm.querySelector('[data-gross]').textContent = fmt(gross);
            payrollForm.querySelector('[data-total-deductions]').textContent = fmt(deductions);
            const netEl = payrollForm.querySelector('[data-net]');
            netEl.textContent = fmt(net);
            netEl.classList.toggle('text-danger', net < 0);
        };
        payrollForm.addEventListener('input', recalc);
        recalc();
    }

    /* ---------- Attendance form: hide times when absent / leave ---------- */
    const statusSelect = document.querySelector('[data-attendance-status]');
    if (statusSelect) {
        const sync = function () {
            const noTimes = ['absent', 'leave'].includes(statusSelect.value);
            document.querySelectorAll('[data-attendance-time]').forEach(function (el) { el.classList.toggle('d-none', noTimes); });
        };
        statusSelect.addEventListener('change', sync);
        sync();
    }

    /* ---------- Leave form: live working-day count ---------- */
    const leaveForm = document.querySelector('[data-leave-form]');
    if (leaveForm) {
        const start = leaveForm.querySelector('[name="start_date"]');
        const end = leaveForm.querySelector('[name="end_date"]');
        const out = leaveForm.querySelector('[data-leave-days]');
        const typeSelect = leaveForm.querySelector('[name="leave_type_id"]');
        const attachHint = leaveForm.querySelector('[data-attachment-required]');
        const count = function () {
            if (!start.value || !end.value) { out.textContent = '—'; return; }
            let d = new Date(start.value + 'T00:00:00');
            const last = new Date(end.value + 'T00:00:00');
            let n = 0;
            while (d <= last) { const w = d.getDay(); if (w !== 0 && w !== 6) n++; d.setDate(d.getDate() + 1); }
            out.textContent = last < new Date(start.value + 'T00:00:00') ? 'Invalid range' : n + ' working day' + (n === 1 ? '' : 's');
        };
        const syncType = function () {
            const opt = typeSelect.options[typeSelect.selectedIndex];
            if (attachHint) attachHint.classList.toggle('d-none', !(opt && opt.dataset.requiresAttachment === '1'));
        };
        start.addEventListener('change', function () { if (!end.value || end.value < start.value) end.value = start.value; end.min = start.value; count(); });
        end.addEventListener('change', count);
        typeSelect.addEventListener('change', syncType);
        count();
        syncType();
    }

    /* ---------- Kanban drag & drop ---------- */
    const board = document.querySelector('[data-kanban]');
    if (board) {
        let dragged = null;
        board.addEventListener('dragstart', function (e) {
            const card = e.target.closest('.op-kanban-card[draggable="true"]');
            if (!card) return;
            dragged = card;
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', card.dataset.id);
        });
        board.addEventListener('dragend', function () {
            if (dragged) dragged.classList.remove('dragging');
            board.querySelectorAll('.drag-over').forEach(function (l) { l.classList.remove('drag-over'); });
        });
        board.querySelectorAll('.op-kanban-list').forEach(function (list) {
            list.addEventListener('dragover', function (e) { if (dragged) { e.preventDefault(); list.classList.add('drag-over'); } });
            list.addEventListener('dragleave', function (e) { if (!list.contains(e.relatedTarget)) list.classList.remove('drag-over'); });
            list.addEventListener('drop', function (e) {
                e.preventDefault();
                list.classList.remove('drag-over');
                if (!dragged) return;
                const card = dragged;
                const from = card.parentElement;
                const status = list.dataset.status;
                if (from === list) return;
                list.prepend(card);
                const refresh = function () {
                    board.querySelectorAll('.op-kanban-col').forEach(function (col) {
                        col.querySelector('.count').textContent = col.querySelectorAll('.op-kanban-card').length;
                        const empty = col.querySelector('[data-empty]');
                        if (empty) empty.classList.toggle('d-none', col.querySelectorAll('.op-kanban-card').length > 0);
                    });
                };
                refresh();
                OP.request(card.dataset.url, 'PATCH', { status: status })
                    .then(function (data) {
                        const bar = card.querySelector('.op-progress .bar');
                        if (bar) bar.style.width = data.progress + '%';
                        const pct = card.querySelector('[data-progress-label]');
                        if (pct) pct.textContent = data.progress + '%';
                        OP.toast('Task moved to ' + list.dataset.label, 'success');
                    })
                    .catch(function (err) {
                        from.prepend(card);
                        refresh();
                        OP.toast((err && err.message) || 'You cannot move this task.', 'danger');
                    });
            });
        });
    }

    /* ---------- Bootstrap tooltips & auto-dismiss alerts ---------- */
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) { new bootstrap.Tooltip(el); });
    document.querySelectorAll('.op-alert[data-autohide]').forEach(function (el) {
        setTimeout(function () { el.style.transition = 'opacity .4s'; el.style.opacity = '0'; setTimeout(function () { el.remove(); }, 400); }, 6000);
    });

    /* ---------- Open a tab from the URL hash (#tab-...) ---------- */
    if (location.hash && location.hash.indexOf('#tab-') === 0) {
        const trigger = document.querySelector('[data-bs-target="' + location.hash.replace('#tab-', '#pane-') + '"]');
        if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
    }
    document.querySelectorAll('.op-tabs [data-bs-toggle="tab"]').forEach(function (t) {
        t.addEventListener('shown.bs.tab', function () {
            history.replaceState(null, '', t.dataset.bsTarget.replace('#pane-', '#tab-'));
            window.dispatchEvent(new Event('resize'));
        });
    });
})();
