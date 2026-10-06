/**
 * assets/js/messages.js
 * ------------------------------------------------------------------
 * Floating committee "Messages" panel (Messenger-style), wired up by
 * layouts/content-topbar.php next to the notification bell. Talks to
 * modules/messages/ajax_*.php. "Real-time" delivery is short polling
 * (same pattern the app already uses for the session heartbeat in
 * app.js), no extra server infrastructure required.
 * ------------------------------------------------------------------
 */
(function () {
  const BADGE_POLL_MS  = 15000; // header badge, panel closed or on the list view
  const THREAD_POLL_MS = 3000;  // open conversation, for new incoming messages

  document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn   = document.getElementById('messagePanelToggle');
    const panel       = document.getElementById('messagePanel');
    const closeBtn    = document.getElementById('messagePanelClose');
    const backBtn     = document.getElementById('messagePanelBack');
    const titleEl     = document.getElementById('messagePanelTitle');
    const bodyEl      = document.getElementById('messagePanelBody');
    const badgeEl     = document.querySelector('[data-message-badge]');
    if (!toggleBtn || !panel) return; // not logged in / topbar not rendered

    const baseUrl = (window.APP_URL || '') + '/modules/messages';
    const csrfToken = window.APP_CSRF_TOKEN || '';

    let isOpen = false;
    let view = 'list'; // 'list' | 'thread'
    let activeCommitteeId = null;
    let newestMessageId = 0;
    let oldestMessageId = 0;
    let hasMoreOlder = false;
    let badgeTimer = null;
    let threadTimer = null;
    let loadingOlder = false;

    /* ---------------------------------------------------------------
     * Badge
     * ------------------------------------------------------------- */
    function setBadge(count) {
      if (!badgeEl) return;
      if (count > 0) {
        badgeEl.textContent = count > 99 ? '99+' : String(count);
        badgeEl.classList.remove('d-none');
      } else {
        badgeEl.textContent = '';
        badgeEl.classList.add('d-none');
      }
    }

    function refreshBadge() {
      if (view === 'thread' && isOpen) return; // thread poll already keeps this current
      appGet(baseUrl + '/ajax_unread_count.php').then(function (data) {
        if (data && data.success) setBadge(data.total_unread || 0);
      });
    }

    function startBadgePolling() {
      stopBadgePolling();
      badgeTimer = window.setInterval(refreshBadge, BADGE_POLL_MS);
    }
    function stopBadgePolling() {
      if (badgeTimer) { window.clearInterval(badgeTimer); badgeTimer = null; }
    }

    /* ---------------------------------------------------------------
     * Panel open / close
     * ------------------------------------------------------------- */
    function openPanel() {
      isOpen = true;
      panel.classList.add('is-open');
      panel.setAttribute('aria-hidden', 'false');
      toggleBtn.setAttribute('aria-expanded', 'true');
      if (view === 'thread' && activeCommitteeId) {
        openThread(activeCommitteeId, titleEl ? titleEl.textContent : '', true);
      } else {
        showList();
      }
    }

    function closePanel() {
      isOpen = false;
      panel.classList.remove('is-open');
      panel.setAttribute('aria-hidden', 'true');
      toggleBtn.setAttribute('aria-expanded', 'false');
      stopThreadPolling();
    }

    toggleBtn.addEventListener('click', function () {
      if (isOpen) closePanel(); else openPanel();
    });
    if (closeBtn) closeBtn.addEventListener('click', closePanel);

    document.addEventListener('click', function (event) {
      if (!isOpen) return;
      if (panel.contains(event.target) || toggleBtn.contains(event.target)) return;
      closePanel();
    });
    panel.addEventListener('click', function (event) { event.stopPropagation(); });

    /* ---------------------------------------------------------------
     * Conversation list
     * ------------------------------------------------------------- */
    function escapeHtml(str) {
      const div = document.createElement('div');
      div.textContent = str == null ? '' : String(str);
      return div.innerHTML;
    }

    function initials(name) {
      const parts = String(name || '').trim().split(/\s+/);
      const a = (parts[0] || '').charAt(0);
      const b = (parts[1] || '').charAt(0);
      const result = (a + b).toUpperCase();
      return result || '?';
    }

    function showList() {
      view = 'list';
      activeCommitteeId = null;
      stopThreadPolling();
      if (backBtn) backBtn.classList.add('d-none');
      if (titleEl) titleEl.textContent = 'Messages';
      bodyEl.innerHTML = '<div class="message-panel-empty">Loading…</div>';
      loadConversations();
    }

    function loadConversations() {
      appGet(baseUrl + '/ajax_conversations.php').then(function (data) {
        if (view !== 'list') return; // user already moved on
        if (!data || !data.success) {
          bodyEl.innerHTML = '<div class="message-panel-empty">Could not load your conversations.</div>';
          return;
        }
        setBadge(data.total_unread || 0);
        renderConversationList(data.conversations || []);
      });
    }

    function renderConversationList(conversations) {
      if (!conversations.length) {
        bodyEl.innerHTML = '<div class="message-panel-empty">'
          + '<i class="bi bi-chat-square-text"></i>'
          + 'You\'re not currently assigned to a committee, so there\'s no group chat to show yet.'
          + '</div>';
        return;
      }

      bodyEl.innerHTML = '';
      conversations.forEach(function (conv) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'message-conv-item' + (conv.unread_count > 0 ? ' has-unread' : '');

        const last = conv.last_message;
        const preview = last
          ? (last.is_mine ? 'You: ' : '') + last.body
          : 'No messages yet — say hello.';
        const time = last ? timeShort(last.created_at) : '';

        btn.innerHTML =
          '<span class="avatar-circle">' + escapeHtml(initials(conv.committee_name)) + '</span>'
          + '<span class="message-conv-text">'
            + '<span class="message-conv-name">' + escapeHtml(conv.committee_name) + '</span><br>'
            + '<span class="message-conv-preview">' + escapeHtml(preview) + '</span>'
          + '</span>'
          + '<span class="message-conv-meta">'
            + '<span class="message-conv-time">' + escapeHtml(time) + '</span>'
            + (conv.unread_count > 0 ? '<span class="message-conv-dot"></span>' : '')
          + '</span>';

        btn.addEventListener('click', function () {
          openThread(conv.committee_id, conv.committee_name);
        });
        bodyEl.appendChild(btn);
      });
    }

    function timeShort(datetime) {
      if (!datetime) return '';
      const d = new Date(datetime.replace(' ', 'T'));
      if (isNaN(d.getTime())) return '';
      const now = new Date();
      const sameDay = d.toDateString() === now.toDateString();
      if (sameDay) return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
      return d.toLocaleDateString([], { month: 'short', day: 'numeric' });
    }

    /* ---------------------------------------------------------------
     * Thread view
     * ------------------------------------------------------------- */
    function stopThreadPolling() {
      if (threadTimer) { window.clearInterval(threadTimer); threadTimer = null; }
    }

    function openThread(committeeId, committeeName, keepScroll) {
      view = 'thread';
      activeCommitteeId = committeeId;
      newestMessageId = 0;
      oldestMessageId = 0;
      hasMoreOlder = false;
      stopThreadPolling();

      if (backBtn) backBtn.classList.remove('d-none');
      if (titleEl) titleEl.textContent = committeeName || 'Committee chat';

      bodyEl.innerHTML =
        '<div class="message-thread">'
          + '<div class="message-thread-messages" id="messageThreadMessages">'
            + '<div class="message-panel-empty">Loading…</div>'
          + '</div>'
          + '<form class="message-thread-composer" id="messageThreadComposer">'
            + '<textarea class="message-thread-input" id="messageThreadInput" '
              + 'placeholder="Message the committee…" rows="1" maxlength="2000"></textarea>'
            + '<button type="submit" class="message-thread-send" id="messageThreadSend" disabled '
              + 'aria-label="Send"><i class="bi bi-send-fill"></i></button>'
          + '</form>'
        + '</div>';

      wireComposer(committeeId);
      loadInitialMessages(committeeId);
      threadTimer = window.setInterval(function () { pollNewMessages(committeeId); }, THREAD_POLL_MS);
    }

    if (backBtn) backBtn.addEventListener('click', showList);

    function messagesContainer() {
      return document.getElementById('messageThreadMessages');
    }

    function isScrolledNearBottom(el) {
      return el.scrollHeight - el.scrollTop - el.clientHeight < 80;
    }

    function renderBubble(msg) {
      const row = document.createElement('div');
      row.className = 'message-bubble-row ' + (msg.is_mine ? 'is-mine' : 'is-theirs');

      let html = '';
      if (!msg.is_mine) {
        html += '<span class="message-bubble-sender">' + escapeHtml(msg.sender_name) + '</span>';
      }
      html += '<span class="message-bubble"></span>';
      html += '<span class="message-bubble-time">' + escapeHtml(msg.created_at_human || '') + '</span>';
      row.innerHTML = html;
      row.querySelector('.message-bubble').textContent = msg.body; // textContent: never render as HTML
      return row;
    }

    function loadInitialMessages(committeeId) {
      appGet(baseUrl + '/ajax_messages.php?committee_id=' + encodeURIComponent(committeeId))
        .then(function (data) {
          if (activeCommitteeId !== committeeId) return;
          const container = messagesContainer();
          if (!container) return;
          if (!data || !data.success) {
            container.innerHTML = '<div class="message-panel-empty">Could not load this conversation.</div>';
            return;
          }
          container.innerHTML = '';
          const messages = data.messages || [];
          if (data.has_more) {
            hasMoreOlder = true;
            container.appendChild(buildLoadMoreButton(committeeId));
          }
          if (!messages.length) {
            const empty = document.createElement('div');
            empty.className = 'message-panel-empty';
            empty.textContent = 'No messages yet — start the conversation.';
            container.appendChild(empty);
          }
          messages.forEach(function (msg) { container.appendChild(renderBubble(msg)); });
          if (messages.length) {
            newestMessageId = messages[messages.length - 1].message_id;
            oldestMessageId = messages[0].message_id;
          }
          container.scrollTop = container.scrollHeight;
          refreshBadge();
        });
    }

    function buildLoadMoreButton(committeeId) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'message-thread-loadmore';
      btn.textContent = 'Load earlier messages';
      btn.addEventListener('click', function () { loadOlderMessages(committeeId, btn); });
      return btn;
    }

    function loadOlderMessages(committeeId, btn) {
      if (loadingOlder || !oldestMessageId) return;
      loadingOlder = true;
      btn.textContent = 'Loading…';

      appGet(baseUrl + '/ajax_messages.php?committee_id=' + encodeURIComponent(committeeId)
        + '&before_id=' + encodeURIComponent(oldestMessageId))
        .then(function (data) {
          loadingOlder = false;
          if (activeCommitteeId !== committeeId) return;
          const container = messagesContainer();
          if (!container || !data || !data.success) return;

          const messages = data.messages || [];
          const prevHeight = container.scrollHeight;

          if (!data.has_more && btn.parentNode) btn.remove();
          else btn.textContent = 'Load earlier messages';

          const frag = document.createDocumentFragment();
          messages.forEach(function (msg) { frag.appendChild(renderBubble(msg)); });
          if (btn.parentNode) btn.after(frag); else container.prepend(frag);

          if (messages.length) oldestMessageId = messages[0].message_id;
          container.scrollTop = container.scrollHeight - prevHeight;
        });
    }

    function pollNewMessages(committeeId) {
      if (!isOpen || view !== 'thread' || activeCommitteeId !== committeeId) return;
      appGet(baseUrl + '/ajax_messages.php?committee_id=' + encodeURIComponent(committeeId)
        + '&after_id=' + encodeURIComponent(newestMessageId || 0))
        .then(function (data) {
          if (activeCommitteeId !== committeeId) return;
          const container = messagesContainer();
          if (!container || !data || !data.success) return;
          const messages = data.messages || [];
          if (!messages.length) return;

          const stayAtBottom = isScrolledNearBottom(container);
          const empty = container.querySelector('.message-panel-empty');
          if (empty) empty.remove();
          messages.forEach(function (msg) {
            if (msg.message_id <= newestMessageId) return; // already rendered (e.g. our own send)
            container.appendChild(renderBubble(msg));
          });
          newestMessageId = Math.max(newestMessageId, messages[messages.length - 1].message_id);
          if (stayAtBottom) container.scrollTop = container.scrollHeight;
        });
    }

    function wireComposer(committeeId) {
      const form = document.getElementById('messageThreadComposer');
      const input = document.getElementById('messageThreadInput');
      const sendBtn = document.getElementById('messageThreadSend');
      if (!form || !input || !sendBtn) return;

      function refreshSendState() { sendBtn.disabled = input.value.trim() === ''; }
      input.addEventListener('input', function () {
        refreshSendState();
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 84) + 'px';
      });
      input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
          event.preventDefault();
          form.requestSubmit ? form.requestSubmit() : sendActive();
        }
      });
      refreshSendState();

      function sendActive() {
        const body = input.value.trim();
        if (!body || sendBtn.disabled) return;
        sendBtn.disabled = true;

        appPost(baseUrl + '/ajax_send.php', {
          csrf_token: csrfToken,
          committee_id: committeeId,
          message: body
        }).then(function (data) {
          if (!data || !data.success) {
            if (window.appToast) appToast('error', (data && data.message) || 'Could not send your message.');
            refreshSendState();
            return;
          }
          input.value = '';
          input.style.height = 'auto';
          const container = messagesContainer();
          if (container && data.message) {
            const empty = container.querySelector('.message-panel-empty');
            if (empty) empty.remove();
            container.appendChild(renderBubble(data.message));
            newestMessageId = Math.max(newestMessageId, data.message.message_id);
            container.scrollTop = container.scrollHeight;
          }
          refreshSendState();
          input.focus();
        });
      }

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        sendActive();
      });
    }

    /* ---------------------------------------------------------------
     * Bootstrap
     * ------------------------------------------------------------- */
    refreshBadge();
    startBadgePolling();
  });
})();
