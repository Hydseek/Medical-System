(function(){
    const toggle = document.getElementById('notifications-toggle');
    const list = document.getElementById('notifications-list');
    const items = document.getElementById('notifications-items');
    const countEl = document.getElementById('notifications-count');
    const markAllBtn = document.getElementById('mark-all-read');

    if (!toggle) return;

    toggle.addEventListener('click', (e) => {
        e.preventDefault();
        // toggle dropdown-like display
        const showing = list.style.display === 'block';
        list.style.display = showing ? 'none' : 'block';
        if (!showing) {
            fetchAndRender();
        }
    });

    markAllBtn?.addEventListener('click', async (e) => {
        e.preventDefault();
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const res = await fetch('/notifications/read-all', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf } });
        if (res.ok) {
            fetchAndRender();
        }
    });

    async function fetchAndRender() {
        try {
            const res = await fetch('/notifications/list', { credentials: 'same-origin' });
            if (!res.ok) return;
            const json = await res.json();
            renderList(json.notifications || []);
            countEl.textContent = json.unread || 0;
        } catch (err) {
            console.error(err);
        }
    }

    function renderList(listData) {
        items.innerHTML = '';
        if (!listData.length) {
            items.innerHTML = '<li class="list-group-item text-muted empty">No notifications</li>';
            return;
        }

        listData.forEach(n => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-start';
            li.setAttribute('data-id', n.id);

            const left = document.createElement('div');
            left.className = 'ms-0 me-auto';
            left.innerHTML = `<div class="fw-semibold">${escapeHtml(n.title)}</div><div class="text-muted small">${escapeHtml(n.message)}</div>`;

            const right = document.createElement('div');
            right.className = 'text-end small text-muted';
            right.innerHTML = `${n.createdAt}`;

            if (!n.isRead) {
                li.classList.add('unread');
            }

            li.appendChild(left);
            li.appendChild(right);

            li.addEventListener('click', async () => {
                await markRead(n.id);
                li.classList.remove('unread');
                const newCount = Math.max(0, (parseInt(countEl.textContent || '0') - 1));
                countEl.textContent = String(newCount);
            });

            items.appendChild(li);
        });
    }

    async function markRead(id) {
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            await fetch(`/notifications/${id}/read`, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf } });
        } catch (err) {
            console.error(err);
        }
    }

    function escapeHtml(str){
        return (str+'').replace(/[&"'<>]/g, function(s) {
            return ({'&':'&amp;','"':'&quot;','\'':'&#39;','<':'&lt;','>':'&gt;'}[s]);
        });
    }

    // initial poll every 30s
    fetchAndRender();
    setInterval(fetchAndRender, 30000);
})();