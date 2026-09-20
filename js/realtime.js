/**
 * ============================================================
 * Sprint 5 — Realtime Manager
 * mendflow / realtime.js
 *
 * Provides:
 *   RealtimeManager  — SSE connection, event routing
 *   PresenceManager  — online members, editing context
 *   TypingManager    — typing indicators
 *   OptimisticStore  — rollback map for optimistic UI
 *
 * Usage (called from main init after login):
 *   window.RT = new RealtimeManager(apiBase, token, userId);
 *   RT.connectGlobal();
 *   RT.connectProject(projectId);          // when opening a project
 *   RT.disconnectProject();                // when leaving
 * ============================================================
 */

'use strict';

/* ════════════════════════════════════════════════════════════
   OPTIMISTIC STORE
   Tracks pending mutations so we can roll back on failure.
════════════════════════════════════════════════════════════ */
class OptimisticStore {
    constructor() {
        /** @type {Map<string, {element: HTMLElement, rollback: () => void}>} */
        this._pending = new Map();
    }

    /**
     * Register an optimistic mutation.
     * @param {string}      key       Unique key (e.g. "like-post-42")
     * @param {HTMLElement} element   DOM element affected
     * @param {() => void}  rollback  Function to undo the mutation
     */
    register(key, element, rollback) {
        this._pending.set(key, { element, rollback });
    }

    /** Confirm success — remove tracking entry */
    confirm(key) {
        this._pending.delete(key);
    }

    /** Roll back and remove tracking entry */
    rollback(key) {
        const entry = this._pending.get(key);
        if (entry) {
            try { entry.rollback(); } catch (_) {}
            this._pending.delete(key);
        }
    }

    /** Roll back all pending for a context (e.g. when connection drops) */
    rollbackAll(prefix = '') {
        for (const [key] of this._pending) {
            if (!prefix || key.startsWith(prefix)) {
                this.rollback(key);
            }
        }
    }
}

/* ════════════════════════════════════════════════════════════
   TYPING MANAGER
   Sends "I'm typing" pings; renders "X is typing..." UI.
════════════════════════════════════════════════════════════ */
class TypingManager {
    /**
     * @param {string} apiBase
     * @param {string} token
     * @param {number} userId
     */
    constructor(apiBase, token, userId) {
        this._api      = apiBase;
        this._token    = token;
        this._userId   = userId;

        /** active polls: contextKey → intervalId */
        this._polls     = new Map();
        /** debounce send timers: contextKey → timeoutId */
        this._debounce  = new Map();
        /** cached typing users: contextKey → [{user_id, first_name}] */
        this._state     = new Map();
        /** UI render callbacks: contextKey → (users) => void */
        this._renderers = new Map();
    }

    /**
     * Call this when user types in an input.
     * @param {string} contextType  'project_post_comment' | 'chat' | 'project_doc' | 'task_comment'
     * @param {number} contextId
     */
    onInput(contextType, contextId) {
        const key = `${contextType}:${contextId}`;

        // Send "typing: true" immediately (debounced)
        clearTimeout(this._debounce.get(key));
        this._sendTyping(contextType, contextId, true);

        // Auto-clear after 4s of no input
        const timer = setTimeout(() => {
            this._sendTyping(contextType, contextId, false);
        }, 4000);
        this._debounce.set(key, timer);

        // Start polling if not already
        if (!this._polls.has(key)) {
            this._startPoll(contextType, contextId);
        }
    }

    /**
     * Register a callback to render typing users in the UI.
     * @param {string}   contextType
     * @param {number}   contextId
     * @param {Function} renderer  (users: Array) => void
     */
    watch(contextType, contextId, renderer) {
        const key = `${contextType}:${contextId}`;
        this._renderers.set(key, renderer);
    }

    unwatch(contextType, contextId) {
        const key = `${contextType}:${contextId}`;
        this._renderers.delete(key);
        clearInterval(this._polls.get(key));
        this._polls.delete(key);
        this._state.delete(key);
    }

    stopAll() {
        for (const [, id] of this._polls) clearInterval(id);
        this._polls.clear();
        this._renderers.clear();
        this._state.clear();
    }

    _startPoll(contextType, contextId) {
        const key = `${contextType}:${contextId}`;
        const id  = setInterval(() => this._fetchTyping(contextType, contextId), 2000);
        this._polls.set(key, id);
        this._fetchTyping(contextType, contextId);
    }

    async _fetchTyping(contextType, contextId) {
        try {
            const res = await fetch(
                `${this._api}/typing.php?context_type=${contextType}&context_id=${contextId}`,
                { headers: { Authorization: `Bearer ${this._token}` } }
            );
            if (!res.ok) return;
            const data  = await res.json();
            const key   = `${contextType}:${contextId}`;
            const users = data.typing || [];
            this._state.set(key, users);
            const renderer = this._renderers.get(key);
            if (renderer) renderer(users);
        } catch (_) {}
    }

    async _sendTyping(contextType, contextId, isTyping) {
        try {
            await fetch(`${this._api}/typing.php`, {
                method:  'POST',
                headers: {
                    'Content-Type':  'application/json',
                    Authorization:   `Bearer ${this._token}`,
                },
                body: JSON.stringify({ context_type: contextType, context_id: contextId, is_typing: isTyping }),
            });
        } catch (_) {}
    }
}

/* ════════════════════════════════════════════════════════════
   PRESENCE MANAGER
   Sends heartbeats; tracks who's online in current project.
════════════════════════════════════════════════════════════ */
class PresenceManager {
    /**
     * @param {string} apiBase
     * @param {string} token
     * @param {number} userId
     */
    constructor(apiBase, token, userId) {
        this._api       = apiBase;
        this._token     = token;
        this._userId    = userId;

        this._projectId   = null;
        this._heartbeatId = null;
        this._pollId      = null;
        this._members     = [];

        /** Callback fired when member list changes */
        this.onMembersChange = null;
    }

    /**
     * Start presence for a project.
     * @param {number} projectId
     */
    enterProject(projectId) {
        this.leaveProject();
        this._projectId = projectId;
        this._heartbeat('connected');

        // Heartbeat every 25s
        this._heartbeatId = setInterval(() => this._heartbeat('connected'), 25000);

        // Poll presence every 5s
        this._pollId = setInterval(() => this._fetchMembers(), 5000);
        this._fetchMembers();
    }

    leaveProject() {
        if (this._projectId) {
            this._heartbeat('disconnected').catch(() => {});
        }
        clearInterval(this._heartbeatId);
        clearInterval(this._pollId);
        this._projectId = null;
        this._members   = [];
    }

    /**
     * Signal that user is editing something.
     * @param {string} context  e.g. "doc:42"
     */
    setEditing(context) {
        if (!this._projectId) return;
        this._sendPresence('editing', context);
    }

    clearEditing() {
        if (!this._projectId) return;
        this._sendPresence('heartbeat', null);
    }

    getMembers() { return this._members; }

    async _heartbeat(action = 'heartbeat', context = null) {
        return this._sendPresence(action, context);
    }

    async _sendPresence(action, context) {
        if (!this._token) return;
        try {
            await fetch(`${this._api}/presence.php`, {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Authorization:  `Bearer ${this._token}`,
                },
                body: JSON.stringify({
                    project_id: this._projectId,
                    action,
                    context,
                }),
            });
        } catch (_) {}
    }

    async _fetchMembers() {
        if (!this._projectId) return;
        try {
            const res = await fetch(
                `${this._api}/presence.php?project_id=${this._projectId}`,
                { headers: { Authorization: `Bearer ${this._token}` } }
            );
            if (!res.ok) return;
            const data = await res.json();
            this._members = data.members || [];
            if (this.onMembersChange) {
                this.onMembersChange(this._members);
            }
        } catch (_) {}
    }
}

/* ════════════════════════════════════════════════════════════
   REALTIME MANAGER
   Owns SSE connections, routes events to registered handlers.
════════════════════════════════════════════════════════════ */
class RealtimeManager {
    /**
     * @param {string} apiBase   e.g. "/api"
     * @param {string} token     JWT
     * @param {number} userId
     */
    constructor(apiBase, token, userId) {
        this._api     = apiBase;
        this._token   = token;
        this._userId  = userId;

        /** @type {EventSource|null} */
        this._globalSrc  = null;
        /** @type {EventSource|null} */
        this._projectSrc = null;

        this._projectId       = null;
        this._globalLastId    = 0;
        this._projectLastId   = 0;

        /** reconnect back-off state */
        this._globalRetry  = 0;
        this._projectRetry = 0;

        /** registered event handlers: Map<string, Set<Function>> */
        this._handlers = new Map();

        // Sub-modules
        this.optimistic = new OptimisticStore();
        this.presence   = new PresenceManager(apiBase, token, userId);
        this.typing     = new TypingManager(apiBase, token, userId);

        // Page visibility — pause/resume on tab hide/show
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) return;
            this._reconnectGlobal();
            if (this._projectId) this._reconnectProject();
        });
    }

    /* ── Public API ─────────────────────────────────────────── */

    /**
     * Subscribe to all events of a given type.
     * @param {string}   eventType  e.g. 'project_post' | 'task_update' | 'notification'
     * @param {Function} handler    (data) => void
     * @returns {() => void}  unsubscribe function
     */
    on(eventType, handler) {
        if (!this._handlers.has(eventType)) {
            this._handlers.set(eventType, new Set());
        }
        this._handlers.get(eventType).add(handler);
        return () => this._handlers.get(eventType)?.delete(handler);
    }

    /** Connect global SSE (feed, notifications) */
    connectGlobal() {
        if (this._globalSrc) return;
        this._openGlobalSSE();
    }

    /** Connect project SSE (posts, tasks, presence) */
    connectProject(projectId) {
        if (this._projectId === projectId && this._projectSrc) return;
        this.disconnectProject();
        this._projectId = projectId;
        this._openProjectSSE();
        this.presence.enterProject(projectId);
    }

    /** Disconnect project SSE */
    disconnectProject() {
        if (this._projectSrc) {
            this._projectSrc.close();
            this._projectSrc = null;
        }
        this.presence.leaveProject();
        this._projectId    = null;
        this._projectRetry = 0;
    }

    /** Disconnect everything */
    destroy() {
        this.disconnectProject();
        if (this._globalSrc) {
            this._globalSrc.close();
            this._globalSrc = null;
        }
        this.typing.stopAll();
    }

    /* ── SSE: global ────────────────────────────────────────── */

    _openGlobalSSE() {
        const url = `${this._api}/sse.php?channel=global&token=${encodeURIComponent(this._token)}`;
        this._globalSrc = this._createSSE(url, 'global');
    }

    _reconnectGlobal() {
        if (this._globalSrc && this._globalSrc.readyState !== EventSource.CLOSED) return;
        this._globalSrc = null;
        this._openGlobalSSE();
    }

    /* ── SSE: project ───────────────────────────────────────── */

    _openProjectSSE() {
        if (!this._projectId) return;
        const url = `${this._api}/sse.php?channel=project&project_id=${this._projectId}&token=${encodeURIComponent(this._token)}`;
        this._projectSrc = this._createSSE(url, 'project');
    }

    _reconnectProject() {
        if (!this._projectId) return;
        if (this._projectSrc && this._projectSrc.readyState !== EventSource.CLOSED) return;
        this._projectSrc = null;
        this._openProjectSSE();
    }

    /* ── SSE factory ────────────────────────────────────────── */

    _createSSE(url, scope) {
        // NOTE: EventSource doesn't support custom headers.
        // Token is passed as query param. Ensure HTTPS in production.
        const src = new EventSource(url, { withCredentials: false });

        const EVENT_TYPES = [
            'connected', 'heartbeat', 'reconnect',
            'project_post', 'task_update', 'presence',
            'global_post', 'notification',
            'comment_added', 'reaction_update',
        ];

        EVENT_TYPES.forEach(type => {
            src.addEventListener(type, (e) => {
                let data;
                try { data = JSON.parse(e.data); } catch { return; }
                if (type === 'reconnect') {
                    src.close();
                    if (scope === 'global') {
                        this._globalSrc = null;
                        this._scheduleReconnect('global');
                    } else {
                        this._projectSrc = null;
                        this._scheduleReconnect('project');
                    }
                    return;
                }
                this._dispatch(type, data);
            });
        });

        src.addEventListener('error', () => {
            if (src.readyState === EventSource.CLOSED) {
                if (scope === 'global') {
                    this._globalSrc = null;
                    this._scheduleReconnect('global');
                } else {
                    this._projectSrc = null;
                    this._scheduleReconnect('project');
                }
            }
        });

        src.onmessage = (e) => {
            // Catch-all for unnamed events
            try {
                const data = JSON.parse(e.data);
                this._dispatch('message', data);
            } catch {}
        };

        return src;
    }

    _scheduleReconnect(scope) {
        const retryKey  = scope === 'global' ? '_globalRetry' : '_projectRetry';
        const delay     = Math.min(1000 * 2 ** this[retryKey], 30000);
        this[retryKey]  = Math.min(this[retryKey] + 1, 6);
        setTimeout(() => {
            if (scope === 'global') this._reconnectGlobal();
            else                    this._reconnectProject();
        }, delay);
    }

    _dispatch(eventType, data) {
        const set = this._handlers.get(eventType);
        if (!set) return;
        set.forEach(fn => {
            try { fn(data); } catch (e) { console.error('[RT]', eventType, e); }
        });
    }
}

/* ════════════════════════════════════════════════════════════
   PRESENCE UI RENDERER
   Renders the online-members pill strip in the project header.
════════════════════════════════════════════════════════════ */
class PresenceUI {
    /**
     * @param {string} containerId  ID of the container element
     * @param {number} currentUserId
     */
    constructor(containerId, currentUserId) {
        this._containerId  = containerId;
        this._currentUserId = currentUserId;
    }

    /**
     * Re-render with latest member list.
     * @param {Array} members  [{user_id, first_name, last_name, avatar, status, editing_context}]
     */
    render(members) {
        const el = document.getElementById(this._containerId);
        if (!el) return;

        const online = members.filter(m => m.status !== 'disconnected');
        if (!online.length) {
            el.innerHTML = '';
            return;
        }

        const MAX_VISIBLE = 5;
        const visible  = online.slice(0, MAX_VISIBLE);
        const overflow = online.length - MAX_VISIBLE;

        const esc = (s) => String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const initials = (m) => ((m.first_name || '')[0] + (m.last_name || '')[0]).toUpperCase();

        const grads = [
            'linear-gradient(135deg,#7c3aed,#a78bfa)',
            'linear-gradient(135deg,#6366f1,#818cf8)',
            'linear-gradient(135deg,#0ea5e9,#38bdf8)',
            'linear-gradient(135deg,#10b981,#34d399)',
            'linear-gradient(135deg,#f59e0b,#fbbf24)',
        ];

        const chips = visible.map((m, i) => {
            const isMe  = String(m.user_id) === String(this._currentUserId);
            const label = isMe ? 'Вы' : `${esc(m.first_name)} ${esc(m.last_name)}`;
            const ctx   = m.editing_context ? ` · редактирует ${esc(m.editing_context)}` : '';
            const grad  = grads[parseInt(m.user_id) % grads.length];

            return `
              <div class="rt-presence-chip" title="${label}${ctx}" style="margin-left:${i > 0 ? '-8px' : '0'}">
                ${m.avatar
                  ? `<img src="${esc(m.avatar)}" alt="${esc(initials(m))}">`
                  : `<span>${esc(initials(m))}</span>`}
                <span class="rt-presence-dot ${m.status === 'connected' ? 'rt-dot-online' : 'rt-dot-idle'}"></span>
              </div>`;
        }).join('');

        const extra = overflow > 0
            ? `<div class="rt-presence-chip rt-presence-overflow">+${overflow}</div>`
            : '';

        el.innerHTML = `
          <div class="rt-presence-strip">
            ${chips}${extra}
            <span class="rt-presence-count">${online.length} онлайн</span>
          </div>`;
    }
}

/* ════════════════════════════════════════════════════════════
   TYPING UI RENDERER
   Renders "Иван, Алия печатают..." below an input.
════════════════════════════════════════════════════════════ */
class TypingUI {
    /**
     * @param {string} containerId
     */
    constructor(containerId) {
        this._containerId = containerId;
    }

    render(users) {
        const el = document.getElementById(this._containerId);
        if (!el) return;

        if (!users || !users.length) {
            el.textContent = '';
            el.style.display = 'none';
            return;
        }

        const esc   = (s) => String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;');
        const names = users.slice(0, 2).map(u => esc(u.first_name)).join(', ');
        const verb  = users.length === 1 ? 'печатает' : 'печатают';
        const more  = users.length > 2 ? ` и ещё ${users.length - 2}` : '';

        el.textContent = `${names}${more} ${verb}...`;
        el.style.display = '';
    }
}

/* ════════════════════════════════════════════════════════════
   SPRINT 5 INTEGRATION — hooks into existing prjState / UI
   Call initSprint5Realtime() after showApp() and user login.
════════════════════════════════════════════════════════════ */

/**
 * Bootstrap Sprint 5 realtime features.
 * Called once after successful login.
 *
 * @param {object} opts
 * @param {string} opts.apiBase
 * @param {string} opts.token
 * @param {object} opts.currentUser  { id, first_name, last_name }
 */
function initSprint5Realtime({ apiBase, token, currentUser }) {
    // Expose globally for access from existing code
    window.RT = new RealtimeManager(apiBase, token, currentUser.id);
    window.RT.connectGlobal();

    /* ── Global: new posts in feed ──────────────────────────── */
    window.RT.on('global_post', (post) => {
        // Delegate to existing banner logic
        if (typeof window._pendingNewPosts !== 'undefined') {
            window._pendingNewPosts.unshift(post);
            const banner = document.getElementById('newPostsBanner');
            if (banner) {
                banner.style.display = 'block';
                const btn = banner.querySelector('button');
                if (btn) btn.textContent = `↑ ${window._pendingNewPosts.length} новых публикаций`;
            }
        }
    });

    /* ── Global: notifications ──────────────────────────────── */
    window.RT.on('notification', (notif) => {
        if (typeof window._handleNewNotification === 'function') {
            window._handleNewNotification(notif);
        }
    });

    /* ── Presence member-change callback ────────────────────── */
    window.RT.presence.onMembersChange = (members) => {
        const ui = window._presenceUI;
        if (ui) ui.render(members);
    };

    console.info('[Sprint 5] Realtime initialized. Global SSE connected.');
}

/**
 * Connect project-scoped realtime when user opens a project detail page.
 *
 * @param {number} projectId
 * @param {object} handlers  Optional custom handlers
 */
function connectProjectRealtime(projectId, handlers = {}) {
    if (!window.RT) return;

    window.RT.connectProject(projectId);

    /* ── New project post ───────────────────────────────────── */
    const unsubPost = window.RT.on('project_post', (post) => {
        if (typeof handlers.onPost === 'function') {
            handlers.onPost(post);
            return;
        }
        // Default: prepend to existing feed list
        const list = document.getElementById('prjFeedList');
        if (!list) return;
        if (list.querySelector(`[data-prj-post-id="${post.id}"]`)) return; // already rendered
        if (typeof window.prjRenderFeedPost === 'function') {
            const el = window.prjRenderFeedPost(post, true);
            list.insertBefore(el, list.firstChild);
            // Update empty state
            document.getElementById('prjFeedEmpty')?.classList.add('is-hidden');
        }
    });

    /* ── Task update (Kanban sync) ──────────────────────────── */
    const unsubTask = window.RT.on('task_update', (upd) => {
        if (typeof handlers.onTask === 'function') {
            handlers.onTask(upd);
            return;
        }
        applyTaskUpdateToKanban(upd);
    });

    /* ── Presence update ────────────────────────────────────── */
    const unsubPres = window.RT.on('presence', (data) => {
        const ui = window._presenceUI;
        if (ui) ui.render(data.users || []);
    });

    // Return cleanup
    return () => {
        unsubPost();
        unsubTask();
        unsubPres();
        window.RT.disconnectProject();
    };
}

/* ════════════════════════════════════════════════════════════
   KANBAN REALTIME SYNC
   Applies incoming task_update events to the existing Kanban UI
   without a full reload.
════════════════════════════════════════════════════════════ */
function applyTaskUpdateToKanban(upd) {
    const { id, status, title, priority, assignee_id, first_name, last_name } = upd;
    if (!id || !status) return;

    const existingCard = document.querySelector(`.prj-task-card[data-task-id="${id}"]`);
    const targetColumn = document.querySelector(`.prj-kol[data-col-key="${status}"], .prj-kol[data-status="${status}"]`);
    if (!targetColumn) return; // column not rendered

    if (existingCard) {
        // Move to correct column if status changed
        const currentCol = existingCard.closest('.prj-kol');
        if (currentCol && currentCol !== targetColumn) {
            // Animate out
            existingCard.style.transition = 'opacity .15s, transform .15s';
            existingCard.style.opacity    = '0';
            existingCard.style.transform  = 'translateY(-6px)';
            setTimeout(() => {
                const addBtn = targetColumn.querySelector('.prj-kol-add');
                if (addBtn) {
                    targetColumn.insertBefore(existingCard, addBtn);
                } else {
                    targetColumn.appendChild(existingCard);
                }
                existingCard.style.opacity   = '1';
                existingCard.style.transform = 'translateY(0)';
                // Update column counts
                _updateKanbanCounts();
            }, 150);
        }

        // Update assignee chip
        if (first_name && assignee_id) {
            const chip = existingCard.querySelector('.prj-task-av');
            if (chip) {
                const init = ((first_name[0] || '') + (last_name[0] || '')).toUpperCase();
                chip.textContent = init;
                chip.title = `${first_name} ${last_name}`;
            }
        }
    } else {
        // New task — build minimal card and append
        const card = _buildMinimalTaskCard(upd);
        const addBtn = targetColumn.querySelector('.prj-kol-add');
        if (addBtn) targetColumn.insertBefore(card, addBtn);
        else        targetColumn.appendChild(card);
        card.style.animation = 'prjPostIn .25s ease forwards';
        _updateKanbanCounts();
    }
}

function _buildMinimalTaskCard(task) {
    const esc  = (s) => String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;');
    const card = document.createElement('div');
    card.className = 'prj-task-card';
    card.dataset.taskId = task.id;

    const pClass  = `prj-priority-${task.priority || 'medium'}`;
    const pLabel  = { low:'Low', medium:'Medium', high:'High', urgent:'Urgent' }[task.priority] || 'Medium';
    const init    = ((task.first_name || '')[0] + (task.last_name || '')[0]).toUpperCase() || '?';
    const grads   = ['linear-gradient(135deg,#7c3aed,#a78bfa)','linear-gradient(135deg,#6366f1,#818cf8)'];
    const grad    = grads[(parseInt(task.assignee_id) || 0) % grads.length];

    card.innerHTML = `
      <p class="prj-task-title">${esc(task.title)}</p>
      <div class="prj-task-footer">
        <span class="prj-task-priority ${pClass}">${esc(pLabel)}</span>
        ${task.assignee_id
          ? `<div class="prj-task-av" style="background:${grad}" title="${esc(task.first_name)} ${esc(task.last_name)}">${esc(init)}</div>`
          : ''}
      </div>`;
    return card;
}

function _updateKanbanCounts() {
    document.querySelectorAll('.prj-kol').forEach(col => {
        const count = col.querySelectorAll('.prj-task-card').length;
        const badge = col.querySelector('.prj-kol-count');
        if (badge) badge.textContent = count;
    });
}

/* ════════════════════════════════════════════════════════════
   PRESENCE UI: CSS injected at runtime
════════════════════════════════════════════════════════════ */
(function injectPresenceStyles() {
    if (document.getElementById('rt-presence-styles')) return;
    const style = document.createElement('style');
    style.id = 'rt-presence-styles';
    style.textContent = `
      /* Presence strip */
      .rt-presence-strip {
        display: flex;
        align-items: center;
        gap: 4px;
      }
      .rt-presence-chip {
        width: 28px;
        height: 28px;
        border-radius: 9px;
        border: 2px solid var(--surface, #fff);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #7c3aed, #a78bfa);
        font-size: 10px;
        font-weight: 700;
        color: #fff;
        position: relative;
        cursor: default;
        flex-shrink: 0;
        transition: transform .15s;
      }
      .rt-presence-chip:hover { transform: scale(1.15); z-index: 2; }
      .rt-presence-chip img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
      .rt-presence-dot {
        position: absolute;
        bottom: 1px;
        right: 1px;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        border: 1.5px solid var(--surface, #fff);
      }
      .rt-dot-online { background: #22c55e; }
      .rt-dot-idle   { background: #f59e0b; }
      .rt-presence-overflow {
        background: var(--surface-2, #f3f4f6);
        color: var(--text-muted, #6b7280);
        font-size: 10px;
        font-weight: 700;
      }
      .rt-presence-count {
        font-size: 11px;
        font-weight: 600;
        color: var(--text-muted, #9ca3af);
        margin-left: 6px;
        white-space: nowrap;
      }

      /* Typing indicator */
      .rt-typing-indicator {
        font-size: 12px;
        color: var(--text-muted, #9ca3af);
        font-style: italic;
        padding: 2px 0 4px;
        min-height: 18px;
        transition: opacity .2s;
      }

      /* Live badge on project tab */
      .rt-live-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 10px;
        font-weight: 700;
        color: #22c55e;
        letter-spacing: .3px;
      }
      .rt-live-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #22c55e;
        animation: rt-pulse 1.6s ease-in-out infinite;
      }
      @keyframes rt-pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: .5; transform: scale(.7); }
      }

      /* SSE connection status dot in topbar (optional) */
      .rt-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        background: #9ca3af;
        margin-right: 4px;
        transition: background .3s;
      }
      .rt-status-dot.connected { background: #22c55e; }
      .rt-status-dot.error     { background: #ef4444; }
    `;
    document.head.appendChild(style);
})();

/* ════════════════════════════════════════════════════════════
   EXPORTS — attach to window for existing vanilla JS codebase
════════════════════════════════════════════════════════════ */
window.RealtimeManager       = RealtimeManager;
window.PresenceManager       = PresenceManager;
window.TypingManager         = TypingManager;
window.PresenceUI            = PresenceUI;
window.TypingUI              = TypingUI;
window.OptimisticStore       = OptimisticStore;
window.initSprint5Realtime   = initSprint5Realtime;
window.connectProjectRealtime = connectProjectRealtime;
window.applyTaskUpdateToKanban = applyTaskUpdateToKanban;
