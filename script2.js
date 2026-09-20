:root {
  --primary: #f0ebff;
  --primary-dark: #7c3aed;
  --primary-light: #f0ebff;
  --accent: #7c3aed;
  --accent-coral: #f97066;
  --surface: #ffffff;
  --surface-muted: #fafaf8;
  --surface-strong: #ffffff;
  --border: #ece7f4;
  --ink: #16131d;
  --ink-soft: #625b70;
  --ink-muted: #857d93;
  --text: var(--ink);
  --text-muted: var(--ink-soft);
  --hover: #f7f4ff;
  --tag-bg: #f0ebff;
  --tag-ink: #4a1fa8;
  --shadow: 0 18px 50px rgba(25, 18, 43, 0.08);
  --shadow-soft: 0 12px 30px rgba(25, 18, 43, 0.07);
  --radius-xl: 24px;
  --radius-lg: 20px;
  --radius-md: 16px;
  --radius-sm: 12px;
}

*,
*::before,
*::after {
  box-sizing: border-box;
}

html {
  scroll-behavior: smooth;
}

body {
  margin: 0;
  font-family: "Plus Jakarta Sans", sans-serif;
  color: var(--ink);
  background: #fafaf8;
  min-height: 100vh;
}

a {
  color: inherit;
  text-decoration: none;
}

button,
select,
input {
  font: inherit;
}

button {
  border: 0;
  cursor: pointer;
}

button.is-pressed {
  transform: scale(0.985);
}

.page-shell {
  width: min(1380px, calc(100% - 32px));
  margin: 24px auto 40px;
}

.is-hidden {
  display: none !important;
}

.auth-screen {
  display: none;
}

.auth-screen.auth-screen-active {
  display: block;
}

.app-shell {
  display: grid;
  gap: 20px;
}

.app-layout {
  display: grid;
  grid-template-columns: 280px minmax(0, 1fr);
  gap: 18px;
  align-items: start;
}

.app-content {
  display: grid;
}

.app-view {
  display: none !important;
}

.app-view.app-view-active {
  display: grid !important;
}

.panel,
.topbar,
.auth-intro,
.auth-card {
  border: 1px solid var(--border);
  backdrop-filter: none;
  box-shadow: none;
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  padding: 16px 18px;
  border-radius: 999px;
  background: #ffffff;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.brand-mark {
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background: var(--primary);
  color: var(--surface);
  font-weight: 800;
}

.brand strong,
.brand span {
  display: block;
}

.brand strong,
.section-title h3,
.section-view-top h1,
.profile-panel-card h1,
.auth-intro h1,
.community-card h3,
.event-card h3 {
  font-family: "Plus Jakarta Sans", sans-serif;
}

.brand-wordmark {
  font-family: "Neue Kabel", "Neue Kabel Black", "Avenir Next", "Helvetica Neue", sans-serif;
  font-weight: 900;
  font-size: 1.7rem;
  letter-spacing: -0.04em;
  text-transform: lowercase;
  color: var(--accent);
  line-height: 1;
}

.topbar-status {
  display: flex;
  justify-content: center;
  flex: 1;
}

.online-counter {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border-radius: 999px;
  background: var(--surface);
  color: var(--ink-soft);
}

.status-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--accent-coral);
  box-shadow: none;
}

.topbar-actions {
  display: flex;
  gap: 10px;
}

.user-pill {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border-radius: 999px;
  background: var(--surface);
  color: var(--ink-soft);
  font-weight: 700;
  white-space: nowrap;
}

.topbar-avatar {
  width: 34px;
  height: 34px;
  flex-basis: 34px;
  border-radius: 12px;
}

.user-pill-button {
  border: 1px solid var(--border);
}

.user-pill-button:hover,
.ghost-button:hover,
.primary-button:hover,
.menu-item:hover,
.tab-button:hover,
.interest-chip:hover,
.thread-footer button:hover,
.composer-actions button:hover {
  transform: translateY(-1px);
}

.user-pill-button:hover,
.ghost-button:hover {
  background: var(--primary);
}

.panel {
  border-radius: var(--radius-lg);
  background: #ffffff;
}

.workspace-sidebar,
.workspace-main,
.feed-column,
.right-rail {
  display: grid;
  gap: 18px;
}

.app-menu,
.side-list,
.composer,
.thread-card,
.tasks-panel,
.section-view,
.profile-panel,
.quick-overview,
.project-subpanel {
  padding: 18px;
}

.section-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.section-title h3 {
  margin: 0;
  font-size: 1.2rem;
}

.app-menu {
  background: #ffffff;
  color: var(--ink);
}

.app-menu .section-title h3 {
  color: var(--ink);
}

.menu-item {
  width: 100%;
  padding: 14px 16px;
  margin-top: 10px;
  border-radius: 16px;
  background: transparent;
  color: var(--ink-soft);
  text-align: left;
  font-weight: 700;
}

.menu-item:first-of-type {
  margin-top: 0;
}

.menu-item.is-active {
  background: var(--primary);
  color: var(--accent);
}

.side-list a {
  display: block;
  padding: 14px 0;
  border-top: 1px solid rgba(234, 223, 207, 0.92);
  font-weight: 700;
}

.side-list a:first-of-type {
  border-top: 0;
  padding-top: 0;
}

.side-list a span {
  display: block;
  margin-top: 4px;
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--ink-soft);
}

.workspace {
  display: grid;
}

.workspace-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 300px;
  gap: 18px;
}

.workspace-grid-full {
  grid-template-columns: minmax(0, 1fr) 320px;
}

.workspace-grid-feed{grid-template-columns:minmax(0,1fr) 320px;gap:18px;align-items:start;}.workspace-main-feed,.workspace-right-rail{display:grid;gap:16px;}.composer-top,.thread-head,.profile-inline,.mentor-item{display:flex;align-items:center;gap:12px;}.composer-trigger{flex:1;min-height:52px;padding:0 18px;border-radius:999px;text-align:left;background:#f7f7fb;color:#8b90a7;border:1px solid rgba(124,58,237,.08);}.composer-feed-card,.feed-search-shell-feed,.feed-rail-card,.feed-career-card,.generated-post,.feed-skeleton{border-radius:16px;background:#fff;border:1px solid rgba(15,23,42,.06);box-shadow:0 2px 12px rgba(0,0,0,.06);}.composer-feed-card{padding:18px;}.composer-top-feed{align-items:center;}.composer-trigger-feed{border-radius:999px;background:#f7f7fb;}.composer-actions,.thread-footer,.profile-actions-panel,.music-controls{display:flex;gap:10px;flex-wrap:wrap;}.composer-actions-feed{margin-top:14px;align-items:center;justify-content:flex-start;}.ghost-button,.primary-button,.composer-actions button,.thread-footer button,.tab-button,.interest-chip,.topbar-link,.composer-tool-button{padding:12px 18px;border-radius:999px;font-weight:700;transition:transform .2s ease,background-color .2s ease,color .2s ease;}.ghost-button,.composer-actions button,.thread-footer button,.topbar-link,.tab-button,.interest-chip,.composer-tool-button{background:#fff;color:var(--ink);border:1px solid rgba(15,23,42,.08);}.primary-button{background:var(--accent);color:#fff;box-shadow:none;}.primary-button:hover{background:#6d28d9;}.composer-tool-button{display:inline-flex;align-items:center;gap:8px;cursor:pointer;}.composer-publish-button{margin-left:auto;min-width:188px;}.wide{
  width: 100%;
  justify-content: center;
}

.quick-overview {
  background: #ffffff;
}

.sidebar-tasks-panel {
  background: #ffffff;
}

.overview-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.overview-grid article {
  padding: 16px;
  border-radius: var(--radius-md);
  background: rgba(255, 255, 255, 0.7);
}

.overview-grid strong {
  display: block;
  margin-bottom: 6px;
  font-size: 1.4rem;
}

.overview-grid span,
.thread-text,
.task-row span,
.section-view-top p,
.community-card p,
.music-player p,
.profile-panel-card p,
.profile-summary-grid span {
  color: var(--ink-soft);
  line-height: 1.6;
}

.thread-card {
  background: #ffffff;
}

.thread-card{background:#fff;}.thread-head{justify-content:space-between;align-items:flex-start;}.thread-badge{padding:8px 12px;border-radius:999px;background:rgba(249,112,102,.14);color:var(--accent-coral);font-size:.82rem;font-weight:700;}.thread-badge.warm{background:rgba(249,112,102,.14);color:var(--accent-coral);}.thread-badge.soft{background:var(--primary);color:var(--accent);}.thread-tags{display:flex;flex-wrap:wrap;gap:8px;margin:14px 0;}.thread-tags span{padding:8px 12px;border-radius:999px;background:#f0ebff;color:#4a1fa8;font-size:.82rem;font-weight:700;}.feed-search-shell-feed{position:relative;padding:14px 16px;}.workspace-posts-stack{display:grid;gap:16px;}.generated-post,.feed-skeleton{padding:18px;}.generated-post .thread-head strong{display:block;font-size:1rem;font-weight:800;color:#151821;}.generated-post .thread-head p{margin:4px 0 0;font-size:.86rem;color:#8a90a6;}.generated-post .thread-text{margin:14px 0 0;line-height:1.65;color:#3d4357;}.message-attachment-image{width:100%;margin-top:14px;border-radius:14px;object-fit:cover;max-height:380px;border:1px solid rgba(15,23,42,.06);}.inbox-launcher{cursor:pointer;}.inbox-preview-list,.campus-founders-list,.friend-suggestions-list{display:grid;gap:10px;}.inbox-preview{position:relative;display:grid;gap:4px;width:100%;padding:12px 12px 12px 48px;border-radius:14px;background:#fafafc;border:1px solid rgba(15,23,42,.06);text-align:left;font-weight:800;}.inbox-preview::before{content:"•";position:absolute;left:14px;top:50%;transform:translateY(-50%);width:22px;height:22px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#f97066;color:#fff;font-size:1.2rem;}.inbox-preview.is-unread::after,.notification-card.is-unread::before{content:"";position:absolute;top:14px;right:14px;width:8px;height:8px;border-radius:50%;background:#7c3aed;}.inbox-preview span{display:block;font-size:.86rem;font-weight:500;color:#7b8196;}.campus-community-row{display:flex;align-items:center;gap:12px;width:100%;padding:12px;border-radius:14px;background:#fafafc;border:1px solid rgba(15,23,42,.06);text-align:left;}.campus-community-logo{width:42px;height:42px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;background:#ede9fe;color:#6d28d9;font-weight:800;}.campus-community-copy{display:grid;gap:4px;}.campus-community-copy strong{font-size:.95rem;color:#151821;}.campus-community-copy em{font-style:normal;font-size:.82rem;color:#8a90a6;}.chat-composer-layout{
  display: grid;
  grid-template-columns: 150px minmax(0, 1fr);
  gap: 14px;
}

.social-post {
  display: grid;
  gap: 16px;
  padding: 22px;
  border-radius: 28px;
  background: #fff;
  border: 1px solid var(--border);
}

.social-post-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.social-post-author {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.social-post-avatar {
  flex: 0 0 64px;
  width: 64px;
  height: 64px;
  border-radius: 22px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg,#7c3aed,#4f46e5);
  color: #fff;
  font-size: 1.7rem;
  font-weight: 800;
}

.social-post-meta {
  display: grid;
  gap: 4px;
  min-width: 0;
  padding-top: 4px;
}

.social-post-name {
  display: block;
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--ink);
}

.social-post-time {
  display: block;
  color: var(--ink-soft);
  font-size: .94rem;
}

.social-post-content {
  display: grid;
  gap: 14px;
}

.social-post-content p {
  margin: 0;
  font-size: 1.05rem;
  line-height: 1.75;
  color: var(--ink);
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.social-post-image {
  display: block;
  width: auto;
  max-width: 100%;
  height: auto;
  max-height: min(72vh, 640px);
  margin: 0 auto;
  object-fit: contain;
  border-radius: 12px;
  border: 1px solid rgba(15,23,42,.08);
  background: #f8fafc;
}

@media (max-width: 700px) {
  .social-post-image {
    width: 100%;
    max-height: 70vh;
  }
}

.social-post-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.social-post-actions .ghost-button {
  min-width: 118px;
  justify-content: center;
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

.social-post-actions svg {
  flex: 0 0 auto;
}

.del-btn {
  flex: 0 0 auto;
  width: 42px;
  height: 42px;
  border-radius: 50%;
  border: 1px solid rgba(15,23,42,.08);
  background: #fff;
  color: #9ca3af;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  line-height: 1;
  transition: transform .2s ease, background-color .2s ease, color .2s ease;
}

.del-btn:hover {
  background: #fff1f2;
  color: #dc2626;
  transform: translateY(-1px);
}

.chat-composer-layout-modal {
  min-height: 420px;
}

.chat-sidebar {
  display: grid;
  gap: 10px;
  align-content: start;
}

.chat-contact {
  width: 100%;
  padding: 12px 14px;
  border-radius: 16px;
  background: rgba(241, 228, 208, 0.52);
  text-align: left;
  font-weight: 700;
}

.chat-contact.is-active {
  background: rgba(231, 132, 121, 0.18);
}

.chat-thread {
  display: grid;
  gap: 12px;
}

.chat-page-layout {
  display: grid;
  grid-template-columns: 290px minmax(0, 1fr) 260px;
  gap: 18px;
  align-items: start;
}

.chat-page-sidebar,
.chat-page-thread-shell,
.chat-page-profile {
  min-height: 720px;
}

.chat-page-sidebar,
.chat-page-profile {
  padding: 18px;
}

.chat-page-searchbar {
  margin-bottom: 14px;
}

.chat-filter-row {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 16px;
}

.chat-filter-chip {
  min-height: 40px;
  padding: 0 14px;
  border-radius: 999px;
  background: rgba(241, 228, 208, 0.52);
  color: var(--ink);
  font-weight: 700;
}

.chat-filter-chip.is-active {
  background: linear-gradient(135deg, #6b37ef, #7d4dff);
  color: #fff;
}

.chat-page-contact-list {
  display: grid;
  gap: 10px;
}

.chat-contact-card {
  padding: 14px 14px 14px 16px;
  border-radius: 18px;
  background: rgba(247, 243, 237, 0.8);
}

.chat-contact-copy {
  display: grid;
  gap: 6px;
}

.chat-contact-line {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: baseline;
}

.chat-contact-line span,
.chat-contact-copy p {
  color: var(--ink-soft);
  font-size: 0.85rem;
}

.chat-contact-copy p {
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chat-thread-top-page {
  padding: 18px 18px 0;
}

.chat-page-thread-shell {
  display: grid;
  grid-template-rows: auto auto minmax(0, 1fr) auto;
  overflow: hidden;
  padding: 0;
}

.chat-day-pill {
  justify-self: center;
  margin: 10px 0 0;
  padding: 7px 14px;
  border-radius: 999px;
  background: rgba(241, 228, 208, 0.45);
  color: var(--ink-soft);
  font-size: 0.78rem;
  font-weight: 700;
}

.chat-page-messages {
  min-height: 480px;
  max-height: 480px;
  margin: 12px 18px 0;
  padding: 18px;
  background: rgba(255, 255, 255, 0.74);
}

.message-row {
  display: flex;
  gap: 10px;
  align-items: flex-end;
}

.message-row.outgoing {
  justify-content: flex-end;
}

.chat-message-avatar {
  width: 28px;
  height: 28px;
  border-radius: 999px;
  background: linear-gradient(140deg, #d7c1aa, #f6ecdd);
}

.chat-page-input {
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  padding: 16px 18px 18px;
}

.chat-page-input #chatAttachmentPreview {
  grid-column: 1 / -1;
}

.chat-send-button {
  min-width: 118px;
}

.chat-profile-card {
  display: grid;
  justify-items: center;
  gap: 12px;
  text-align: center;
}

.chat-profile-actions {
  display: flex;
  gap: 10px;
  width: 100%;
}

.chat-profile-actions .ghost-button {
  flex: 1 1 0;
}

.chat-side-section {
  margin-top: 24px;
  display: grid;
  gap: 12px;
}

.chat-side-label {
  color: var(--ink-muted);
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.chat-media-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.chat-media-card {
  min-height: 62px;
  border-radius: 18px;
  background: linear-gradient(135deg, #ff9a63, #6d3bf3);
}

.chat-media-card:nth-child(2) {
  background: linear-gradient(135deg, #f3e6cb, #ead6ff);
}

.chat-media-card-muted {
  display: grid;
  place-items: center;
  background: rgba(241, 228, 208, 0.7);
  color: var(--ink-soft);
  font-weight: 800;
}

.chat-shared-groups {
  display: grid;
  gap: 10px;
}

.chat-group-item {
  display: flex;
  gap: 10px;
  align-items: center;
}

.chat-group-item span {
  width: 34px;
  height: 34px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  background: linear-gradient(135deg, #6b37ef, #8a67ff);
  color: #fff;
  font-size: 0.76rem;
  font-weight: 800;
}

.chat-messages {
  min-height: 220px;
  max-height: 320px;
  overflow: auto;
  display: grid;
  gap: 10px;
  padding: 14px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.7);
}

.chat-composer-layout-modal .chat-messages {
  min-height: 340px;
  max-height: 52vh;
}

.message-bubble {
  max-width: 85%;
  padding: 12px 14px;
  border-radius: 18px;
  line-height: 1.55;
}

.message-bubble.incoming {
  background: rgba(241, 228, 208, 0.74);
}

.message-bubble.outgoing {
  margin-left: auto;
  background: rgba(231, 132, 121, 0.16);
}

.chat-input-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 10px;
}

.event-card {
  background: #ffffff;
  color: var(--ink);
  border: 1px solid var(--border);
}

.event-card .eyebrow {
  background: rgba(249, 112, 102, 0.14);
  color: var(--accent-coral);
}

.event-card p {
  color: var(--ink-soft);
}

.event-card .primary-button {
  background: var(--accent);
  color: #fff;
  box-shadow: none;
}

.events-view {
  gap: 18px;
}

.events-view-top {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
}

.events-preferences {
  display: grid;
  grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr);
  gap: 14px;
  align-items: center;
  padding: 18px;
}

.events-preferences strong,
.events-preferences span {
  display: block;
}

.events-preferences span {
  margin-top: 4px;
  color: var(--ink-soft);
  line-height: 1.5;
}

.events-pref-form,
.events-filter-bar {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
  gap: 10px;
}

.events-interest-row {
  grid-column: 1 / -1;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.event-interest-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border-radius: 999px;
  background: var(--primary);
  color: var(--accent);
  font-weight: 800;
  font-size: .82rem;
}

.event-interest-chip button {
  padding: 0;
  color: inherit;
  background: transparent;
  line-height: 1;
}

.events-format-tabs {
  margin-bottom: 4px;
}

.events-section {
  display: grid;
  gap: 12px;
}

.events-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

.event-card-full {
  overflow: hidden;
  padding: 0;
}

.event-cover {
  display: grid;
  place-items: end start;
  min-height: 150px;
  padding: 14px;
  background: linear-gradient(135deg, #7c3aed, #14b8a6);
  background-size: cover;
  background-position: center;
}

.event-format-pill,
.event-org-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  font-size: .76rem;
  font-weight: 900;
}

.event-format-pill {
  background: rgba(255,255,255,.9);
  color: #24133f;
}

.event-card-body {
  display: grid;
  gap: 12px;
  padding: 16px;
}

.event-card-body h3 {
  margin: 0;
  font-size: 1.08rem;
  line-height: 1.2;
}

.event-meta-grid {
  display: grid;
  gap: 7px;
  color: var(--ink-soft);
  font-size: .9rem;
  line-height: 1.45;
}

.event-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  border-top: 1px solid var(--border);
  padding-top: 12px;
}

.event-org-badge-user {
  background: #f3f4f6;
  color: #4b5563;
}

.event-org-badge-university {
  background: #dbeafe;
  color: #1d4ed8;
}

.event-org-badge-company {
  background: #ede9fe;
  color: #6d28d9;
}

.event-rail-list {
  display: grid;
  gap: 10px;
}

.event-rail-item {
  width: 100%;
  display: grid;
  gap: 6px;
  padding: 12px;
  border-radius: 14px;
  border: 1px solid rgba(124,58,237,.14);
  background: var(--surface-muted);
  text-align: left;
}

.event-rail-item strong {
  color: var(--ink);
  line-height: 1.25;
}

.event-rail-item span {
  color: var(--ink-soft);
  font-size: .82rem;
  line-height: 1.35;
}

.event-create-dialog {
  max-width: 760px;
}

.event-form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.event-form-wide {
  grid-column: 1 / -1;
}

.event-detail-view {
  gap: 16px;
}

.event-detail-shell {
  display: grid;
  gap: 18px;
}

.event-detail-cover {
  min-height: 280px;
  border-radius: 22px;
  background: linear-gradient(135deg, #7c3aed, #14b8a6);
  background-size: cover;
  background-position: center;
}

.event-detail-main {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 320px;
  gap: 18px;
  align-items: start;
}

.event-detail-main h1 {
  margin: 12px 0;
  font-size: clamp(2rem, 4vw, 3.2rem);
  line-height: 1;
}

.event-detail-main p {
  color: var(--ink-soft);
  line-height: 1.7;
  font-size: 1rem;
}

.event-detail-side,
.event-participants-card {
  padding: 18px;
}

.event-detail-meta {
  display: grid;
  gap: 12px;
}

.event-detail-meta div {
  display: grid;
  gap: 3px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--border);
}

.event-detail-meta strong {
  color: var(--ink);
}

.event-detail-meta span {
  color: var(--ink-soft);
  line-height: 1.45;
}

.event-detail-actions {
  display: grid;
  gap: 10px;
  margin-top: 16px;
}

.event-participants-list {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}

.event-participant {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  border-radius: 14px;
  background: var(--surface-muted);
}

.event-participant .avatar {
  width: 40px !important;
  height: 40px !important;
  flex-basis: 40px !important;
  min-width: 40px !important;
}

.mentor-item + .mentor-item {
  margin-top: 14px;
}

.mentor-item+.mentor-item{margin-top:14px;}.mentor-card{display:grid;gap:14px;overflow:hidden;}.feed-rail-card{padding:18px;gap:14px;}.feed-career-card{gap:14px;background:#5b21b6;}.feed-career-card p{color:rgba(255,255,255,.88);}.feed-career-card .primary-button{background:#fff;color:#6637ef;}.friend-suggestion-card{display:grid;gap:10px;padding:14px;border-radius:16px;background:#fafafc;border:1px solid rgba(15,23,42,.06);width:100%;}.friend-suggestion-head{margin:0;}.friend-suggestion-copy{min-width:0;}.friend-suggestion-copy strong,.friend-suggestion-copy p{display:block;overflow-wrap:anywhere;}.friend-suggestion-copy p{margin:2px 0 0;color:#7b8196;line-height:1.5;}.friend-suggestion-card .thread-footer button:not(.add-friend-button){display:none;}.friend-suggestion-card .add-friend-button{width:100%;justify-content:center;background:#7c3aed;color:#fff;border:0;}.friend-suggestion-card .add-friend-button[disabled]{background:#ede9fe;color:#6d28d9;}.post-head-actions{display:flex;align-items:center;gap:10px;}.post-menu-wrap{position:relative;}.post-menu{
  position: absolute;
  top: calc(100% + 6px);
  right: 0;
  display: grid;
  gap: 6px;
  min-width: 160px;
  padding: 8px;
  border-radius: 16px;
  border: 1px solid rgba(234, 223, 207, 0.92);
  background: #ffffff;
  box-shadow: none;
  z-index: 4;
}

.post-menu button {
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(247, 243, 237, 0.84);
  text-align: left;
  color: var(--ink);
  font-weight: 700;
}

.section-view-top {
  margin-bottom: 20px;
}

.section-view-top h1,
.auth-intro h1 {
  margin: 16px 0 12px;
  font-size: clamp(2rem, 4vw, 3.3rem);
  line-height: 1;
}

.section-card-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

.section-card-grid-single {
  grid-template-columns: 1fr;
}

.filter-bar {
  display: grid;
  gap: 12px;
  margin-bottom: 18px;
}

.filter-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.filter-bar-wrap {
  grid-template-columns: minmax(0, 1fr);
}

.filter-input {
  width: 100%;
  min-height: 50px;
  padding: 0 14px;
  border-radius: 16px;
  border: 1px solid var(--border);
  background: #ffffff;
  color: var(--ink);
  outline: none;
}

.tab-switch,
.interest-chip-group {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.tab-button.is-active,
.interest-chip.is-active,
.topbar-link.is-active {
  background: var(--primary);
  color: var(--accent);
}

.community-card {
  padding: 20px;
}

.community-rank {
  display: inline-flex;
  margin-bottom: 12px;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(241, 228, 208, 0.8);
  font-weight: 800;
}

.projects-layout {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

.project-subpanel {
  background: rgba(255, 253, 249, 0.92);
}

.project-code {
  grid-column: 1 / -1;
}

.todo-item {
  display: flex;
  gap: 10px;
  align-items: center;
  padding: 10px 0;
  color: var(--ink-soft);
}

.sidebar-task-form {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 10px;
  margin-bottom: 14px;
}

.sidebar-task-input {
  min-height: 48px;
}

.sidebar-task-list {
  display: grid;
  gap: 10px;
}

.sidebar-task-empty {
  margin: 0;
  padding: 12px 14px;
  border-radius: 14px;
  background: var(--surface-muted);
  color: var(--ink-soft);
  line-height: 1.5;
}

.sidebar-task-item {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 16px;
  border: 1px solid var(--border);
  background: #fff;
}

.sidebar-task-toggle {
  width: 22px;
  height: 22px;
  border-radius: 999px;
  border: 2px solid rgba(124, 58, 237, 0.3);
  background: transparent;
  position: relative;
  flex: 0 0 auto;
}

.sidebar-task-toggle.is-complete {
  background: var(--accent);
  border-color: var(--accent);
}

.sidebar-task-toggle.is-complete::after {
  content: "";
  position: absolute;
  inset: 5px;
  border-radius: 999px;
  background: #fff;
}

.sidebar-task-text {
  min-width: 0;
  color: var(--ink);
  font-weight: 600;
  line-height: 1.45;
  word-break: break-word;
}

.sidebar-task-item.is-complete .sidebar-task-text {
  color: var(--ink-soft);
  text-decoration: line-through;
}

.sidebar-task-delete {
  padding: 0;
  color: var(--ink-muted);
  font-size: 1rem;
  line-height: 1;
}

.kanban-board {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.kanban-column {
  padding: 14px;
  border-radius: 18px;
  background: rgba(241, 228, 208, 0.4);
}

.kanban-column strong {
  display: block;
  margin-bottom: 10px;
}

.kanban-card {
  display: block;
  margin-top: 8px;
  padding: 10px 12px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.86);
}

.chart-line + .chart-line {
  margin-top: 12px;
}

.chart-line span {
  display: block;
  margin-bottom: 6px;
}

.bar {
  height: 12px;
  border-radius: 999px;
  background: rgba(241, 228, 208, 0.62);
  overflow: hidden;
}

.bar i {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, var(--accent), var(--primary-dark));
}

.music-player {
  padding: 6px 0;
}

.code-editor {
  margin: 0;
  padding: 18px;
  overflow: auto;
  border-radius: 18px;
  background: #2f2a25;
  color: #f9efe1;
}

.profile-panel-main,
.profile-form-grid {
  display: grid;
  gap: 18px;
}

.profile-social {
  display: grid;
  gap: 18px;
}

.profile-tab-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.profile-tab {
  padding: 12px 16px;
  border-radius: 999px;
  background: rgba(241, 228, 208, 0.5);
  color: var(--ink-soft);
  font-weight: 700;
}

.profile-tab.is-active {
  background: rgba(231, 132, 121, 0.18);
  color: var(--ink);
}

.profile-tab-panel {
  display: none;
}

.profile-tab-panel.profile-tab-panel-active {
  display: grid;
  gap: 16px;
}

.profile-post-composer {
  background: rgba(255, 253, 249, 0.94);
}

.profile-post-form {
  display: grid;
  gap: 14px;
}

.profile-post-textarea {
  width: 100%;
  min-height: 120px;
  padding: 16px;
  resize: vertical;
  border-radius: 18px;
  border: 1px solid rgba(234, 223, 207, 0.92);
  background: rgba(255, 255, 255, 0.9);
  color: var(--ink);
  font: inherit;
}

.profile-post-actions {
  display: flex;
  justify-content: flex-end;
}

.profile-posts-list,
.profile-mini-grid {
  display: grid;
  gap: 14px;
}

.social-mini-card {
  padding: 18px;
}

.social-mini-card strong {
  display: block;
  margin-bottom: 8px;
}

.social-mini-card p {
  margin: 0;
  color: var(--ink-soft);
  line-height: 1.6;
}

.profile-post-card {
  display: grid;
  gap: 12px;
  padding: 20px;
}

.profile-post-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.profile-post-head p,
.profile-post-card p {
  margin: 0;
}

.profile-post-meta {
  color: var(--ink-muted);
  font-size: 0.95rem;
}

.profile-empty-state {
  padding: 20px;
  border-radius: 20px;
  background: rgba(247, 243, 237, 0.84);
  color: var(--ink-soft);
}

.profile-panel-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  border-radius: var(--radius-lg);
  background: rgba(241, 228, 208, 0.32);
}

.profile-panel-card h1 {
  margin: 0 0 6px;
  font-size: clamp(2rem, 4vw, 3rem);
}

.profile-photo-stack {
  display: grid;
  gap: 10px;
  justify-items: center;
}

.profile-upload-label {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 150px;
}

.profile-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

.profile-summary-grid article {
  padding: 18px;
  border-radius: var(--radius-md);
  background: rgba(255, 255, 255, 0.72);
}

.profile-summary-grid strong {
  display: block;
  margin-bottom: 8px;
}

.profile-form-actions {
  display: flex;
  justify-content: flex-start;
}

.logout-profile-button {
  margin-left: auto;
}

.auth-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.05fr) minmax(360px, 0.95fr);
  gap: 22px;
  align-items: stretch;
  min-height: calc(100vh - 64px);
}

.auth-layout-register,
.auth-layout-login {
  grid-template-columns: minmax(0, 0.95fr) minmax(360px, 0.8fr);
}

.auth-intro,
.auth-card {
  padding: 26px;
}

.auth-intro {
  border-radius: var(--radius-xl);
  background:
    radial-gradient(circle at top left, rgba(231, 132, 121, 0.18), transparent 32%),
    linear-gradient(145deg, rgba(255, 253, 249, 0.96), rgba(247, 243, 237, 0.88));
}

.eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  border-radius: 999px;
  background: rgba(221, 194, 155, 0.25);
  color: var(--ink-soft);
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.hero-text {
  color: var(--ink-soft);
  line-height: 1.7;
}

.auth-points {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 26px;
}

.auth-points article {
  padding: 16px;
  border-radius: var(--radius-md);
  background: rgba(255, 255, 255, 0.68);
}

.auth-points strong {
  display: block;
  margin-bottom: 8px;
  font-size: 1.3rem;
  color: var(--accent);
}

.auth-card {
  display: block;
  border-radius: var(--radius-lg);
  background: rgba(255, 253, 249, 0.9);
}

.auth-copy,
.auth-note,
.auth-instructions {
  color: var(--ink-soft);
  line-height: 1.6;
}

.auth-instructions {
  margin: 0 0 18px;
  padding: 14px 16px;
  border-radius: 18px;
  background: rgba(241, 228, 208, 0.44);
}

.auth-form {
  display: grid;
  gap: 14px;
}

.auth-field {
  display: grid;
  gap: 8px;
}

.auth-field span {
  font-weight: 700;
  color: var(--ink-soft);
}

.auth-input {
  width: 100%;
  min-height: 54px;
  padding: 0 16px;
  border-radius: 18px;
  border: 1px solid rgba(234, 223, 207, 0.92);
  background: rgba(255, 255, 255, 0.86);
  color: var(--ink);
  outline: none;
}

/* ── Styled Select Dropdowns ── */
select.auth-input,
select.filter-input {
  appearance: none;
  -webkit-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%237c3aed' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  padding-right: 44px;
  cursor: pointer;
  transition: border-color 0.18s, background-color 0.18s, box-shadow 0.18s;
}

select.auth-input:hover,
select.filter-input:hover {
  border-color: #c4b5fd;
  background-color: #faf8ff;
}

select.auth-input:focus,
select.filter-input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.12);
  background-color: #ffffff;
}

.auth-switch-button {
  margin-top: 14px;
}

.auth-message {
  min-height: 24px;
  padding: 0 4px;
  font-weight: 600;
}

.auth-message-success {
  color: #8a5a18;
}

.auth-message-error {
  color: #b24b41;
}

.auth-message-info {
  color: var(--ink-muted);
}

.avatar {
  display: grid;
  place-items: center;
  width: 46px;
  height: 46px;
  flex: 0 0 46px;
  border-radius: 16px;
  background: linear-gradient(140deg, var(--primary), #f6ebd9);
  color: var(--ink);
  font-size: 0.9rem;
  font-weight: 800;
}

.avatar.large {
  width: 72px;
  height: 72px;
  flex-basis: 72px;
  border-radius: 22px;
}

.avatar.coral {
  background: linear-gradient(140deg, var(--accent), #f0b0a7);
  color: #fffaf7;
}

.avatar.sand {
  background: linear-gradient(140deg, var(--primary-dark), var(--primary));
}

.avatar.dark {
  background: linear-gradient(140deg, #564f46, #746d62);
  color: #fffdfa;
}

.avatar.muted {
  background: linear-gradient(140deg, #efe4d3, #faf7f1);
}

.avatar.has-photo {
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  color: transparent;
}

.inbox-modal {
  position: fixed;
  inset: 0;
  z-index: 60;
}

.inbox-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(33, 28, 22, 0.32);
}

.inbox-dialog {
  position: absolute;
  top: 50%;
  left: 50%;
  width: min(50vw, 860px);
  min-width: 720px;
  max-height: 78vh;
  transform: translate(-50%, -50%);
  padding: 20px;
  overflow: hidden;
}

.chat-dialog {
  width: min(50vw, 920px);
  min-width: 760px;
}

.profile-settings-dialog {
  width: min(40vw, 760px);
  min-width: 560px;
  max-height: 84vh;
  overflow-y: auto;
}

.profile-settings-layout {
  display: grid;
  grid-template-columns: 240px minmax(0, 1fr);
  gap: 0;
  border: 1px solid var(--border);
  border-radius: 30px;
  overflow: hidden;
  background: #ffffff;
}

.profile-settings-sidebar {
  display: flex;
  flex-direction: column;
  flex: 0 0 260px;
  gap: 18px;
  padding: 28px 20px;
  background: #f6f1e8;
  border-right: 1px solid var(--border);
}

.profile-settings-sidebar h3 {
  margin: 0;
  font-size: 1.05rem;
}

.profile-settings-nav {
  display: grid;
  gap: 10px;
}

.profile-settings-nav-item {
  width: 100%;
  padding: 14px 16px;
  border: 1px solid transparent;
  border-radius: 18px;
  background: transparent;
  color: var(--ink-soft);
  text-align: left;
  font-weight: 700;
}

.profile-settings-nav-item.is-active {
  background: #ffffff;
  border-color: var(--border);
  color: var(--ink);
}

.profile-settings-content {
  min-width: 0;
  padding: 24px;
}

.profile-settings-panel {
  display: none;
}

.profile-settings-panel.profile-settings-panel-active {
  display: grid;
  gap: 18px;
}

.profile-settings-section-head h3 {
  margin: 0 0 8px;
}

.profile-settings-section-head p {
  margin: 0;
  color: var(--ink-soft);
  line-height: 1.5;
}

.profile-settings-identity {
  display: flex;
  align-items: center;
  gap: 18px;
  padding: 18px;
}

.profile-settings-identity h4 {
  margin: 0 0 8px;
  font-size: 1.1rem;
}

.profile-settings-identity p {
  margin: 0;
  color: var(--ink-soft);
}

.profile-form-grid-modal {
  gap: 14px;
}

.profile-security-form {
  display: grid;
  gap: 14px;
}

.profile-security-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.inbox-dialog-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.inbox-dialog-top h2 {
  margin: 12px 0 0;
  font-family: "Plus Jakarta Sans", sans-serif;
}

.inbox-dialog-body {
  display: block;
  min-height: 320px;
}

.notification-list {
  display: grid;
  gap: 12px;
}

.notification-card {
  width: 100%;
  padding: 16px 18px;
  border-radius: 18px;
  background: rgba(247, 243, 237, 0.84);
  text-align: left;
}

.notification-card strong {
  display: block;
  margin-bottom: 6px;
}

.notification-card p {
  margin: 0;
  color: var(--ink-soft);
  line-height: 1.6;
}

.notification-status {
  display: inline-flex;
  margin-top: 10px;
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--ink-muted);
}

@media (max-width: 1120px) {
  .app-layout,
  .workspace-grid,
  .workspace-grid-feed,
  .auth-layout,
  .projects-layout,
  .filter-grid,
  .profile-summary-grid,
  .section-card-grid,
  .auth-points,
  .chat-composer-layout,
  .chat-page-layout,
  .events-grid,
  .event-detail-main,
  .event-participants-list,
  .events-preferences,
  .events-pref-form,
  .events-filter-bar,
  .event-form-grid {
    grid-template-columns: 1fr;
  }

  .inbox-dialog {
    width: min(92vw, 860px);
    min-width: 0;
  }

  .chat-dialog {
    width: min(92vw, 920px);
  }

  .profile-settings-dialog {
    width: min(92vw, 760px);
    min-width: 0;
  }

  .profile-settings-layout {
    grid-template-columns: 1fr;
  }

  .profile-settings-sidebar {
    border-right: 0;
    border-bottom: 1px solid var(--border);
  }
}

@media (max-width: 860px) {
  .page-shell {
    width: min(100% - 20px, 1000px);
    margin-top: 14px;
  }

  .topbar {
    flex-wrap: wrap;
    justify-content: center;
    border-radius: 28px;
  }

  .topbar-status,
  .topbar-actions {
    width: 100%;
    justify-content: center;
  }

  .app-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 560px) {
  .ghost-button,
  .primary-button,
  .composer-actions button,
  .thread-footer button,
  .tab-button,
  .interest-chip {
    width: 100%;
  }

  .profile-panel-card,
  .profile-actions-panel,
  .composer-top,
  .thread-head,
  .profile-settings-identity,
  .profile-security-actions {
    flex-direction: column;
    align-items: flex-start;
  }

  .chat-input-row {
    grid-template-columns: 1fr;
  }

  .chat-page-input {
    grid-template-columns: 1fr;
  }

  .feed-post-preview-grid {
    grid-template-columns: 1fr;
  }

  .logout-profile-button {
    margin-left: 0;
  }
}

.feed-search-shell {
  position: relative;
  display: grid;
  gap: 14px;
  padding: 18px;
  background: rgba(255, 253, 249, 0.9);
}

.feed-search-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 12px;
  align-items: center;
}

.feed-filter-button {
  min-height: 50px;
  min-width: 120px;
}

.feed-filter-popover {
  position: absolute;
  top: calc(100% - 2px);
  right: 18px;
  left: 18px;
  z-index: 8;
  display: grid;
  gap: 16px;
  padding: 18px;
  border-radius: 22px;
  border: 1px solid rgba(234, 223, 207, 0.92);
  background: linear-gradient(145deg, rgba(255, 252, 247, 0.96), rgba(246, 239, 228, 0.88));
}

.feed-filter-group {
  display: grid;
  gap: 10px;
}

.feed-filter-label {
  font-size: 0.88rem;
  font-weight: 800;
  color: var(--ink-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.generated-post .comment-section {
  display: grid;
  gap: 10px;
  margin-top: 14px;
}

.comment-list {
  display: grid;
  gap: 8px;
}

.comment-item,
.comment-empty {
  padding: 10px 12px;
  border-radius: 14px;
  background: rgba(247, 243, 237, 0.84);
  color: var(--ink-soft);
}

.comment-item strong {
  margin-right: 8px;
  color: var(--ink);
}

.like-button.is-active{background:var(--primary);color:var(--accent);}.social-actions{margin-top:16px;align-items:center;justify-content:flex-start;gap:10px;}.social-actions button{min-height:42px;background:#fff;border:1px solid rgba(15,23,42,.08);color:#4f5569;}.like-button::before{content:"❤";margin-right:8px;color:#f97066;}.comment-toggle-button::before{content:"💬";margin-right:8px;}.generated-post.project{position:relative;}.generated-post.project::before{content:"LIVE";position:absolute;top:18px;right:18px;padding:6px 10px;border-radius:999px;background:#dcfce7;color:#15803d;font-size:.72rem;font-weight:800;letter-spacing:.04em;}.generated-post.project::after{content:"🚀 Проект в работе · Подробнее";display:block;margin-top:16px;padding:14px 16px;border-radius:14px;background:#f8f7ff;border:1px solid rgba(124,58,237,.12);color:#5b21b6;font-weight:700;}.attachment-pill{
  display: inline-flex;
  margin-top: 10px;
  padding: 10px 12px;
  border-radius: 14px;
  background: rgba(241, 228, 208, 0.56);
  color: var(--ink-soft);
}

.attachment-preview {
  display: inline-grid;
  gap: 8px;
  justify-items: start;
  padding: 10px 12px;
  border-radius: 16px;
  background: rgba(247, 243, 237, 0.84);
  color: var(--ink-soft);
}

.attachment-preview-thumb {
  width: 72px;
  height: 72px;
  object-fit: cover;
  border-radius: 14px;
  display: block;
}

.comments-modal-body {
  display: grid;
  gap: 14px;
}

.chat-thread-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.message-attachment-image {
  width: min(100%, 260px);
  margin-top: 10px;
  border-radius: 16px;
  display: block;
}

.attachment-button {
  min-height: 50px;
}

.post-dialog {
  width: min(42vw, 720px);
  min-width: 560px;
}

.community-modal-body {
  display: grid;
  gap: 14px;
}

.discovery-card {
  display: grid;
  gap: 14px;
}

.network-layout {
  display: grid;
  gap: 22px;
}

.network-layout-reference {
  gap: 18px;
}

.network-chip-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 16px;
}

.network-chip {
  min-height: 44px;
  padding: 0 18px;
  border: 1px solid rgba(226, 216, 201, 0.92);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.92);
  color: var(--ink);
  font-weight: 800;
  cursor: pointer;
}

.network-chip.is-active {
  background: linear-gradient(135deg, #6b37ef, #7d4cff);
  border-color: transparent;
  color: #fff;
}

.network-results-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

.network-tile {
  display: grid;
  gap: 14px;
  min-height: 240px;
  padding: 18px;
}

.network-tile-head {
  display: flex;
  gap: 14px;
  align-items: flex-start;
}

.network-tile-title {
  min-width: 0;
}

.network-tile-title strong {
  display: block;
  font-size: 1.35rem;
  line-height: 1.1;
}

.network-tile-title p {
  margin: 4px 0 0;
  color: var(--ink-muted);
}

.network-person-avatar {
  width: 58px;
  height: 58px;
  flex: 0 0 58px;
  border-radius: 999px;
}

.network-score {
  margin-left: auto;
  color: #6b37ef;
  font-weight: 800;
  font-size: 0.92rem;
  white-space: nowrap;
}

.network-location {
  color: var(--ink-muted);
  font-size: 0.92rem;
  font-weight: 600;
}

.network-entity-icon {
  width: 42px;
  height: 42px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  background: rgba(108, 79, 255, 0.12);
  color: #6c4fff;
  font-weight: 900;
}

.network-tags {
  margin-top: 4px;
}

.network-card-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: auto;
}

.network-card-actions-wide .ghost-button,
.network-card-actions-wide .primary-button {
  flex: 1 1 180px;
}

.network-cover {
  min-height: 120px;
  border-radius: 18px;
  background-size: cover;
  background-position: center;
}

.university-cover {
  background:
    linear-gradient(180deg, rgba(30, 30, 30, 0.12), rgba(30, 30, 30, 0.12)),
    linear-gradient(135deg, #986b4b, #2f4d71 55%, #86a3c2);
}

.network-company-brand {
  display: flex;
  gap: 12px;
  align-items: flex-start;
}

.network-progress-card {
  display: grid;
  gap: 10px;
  padding: 14px;
  border-radius: 18px;
  background: rgba(244, 240, 233, 0.92);
}

.network-progress-meta {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
  color: var(--ink-muted);
  font-size: 0.86rem;
  font-weight: 700;
}

.network-progress-track {
  height: 8px;
  border-radius: 999px;
  background: rgba(171, 151, 127, 0.18);
  overflow: hidden;
}

.network-progress-track i {
  display: block;
  width: 72%;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, #6b37ef, #b04d3d);
}

.network-mentor-text {
  margin: 0;
  color: var(--ink-muted);
  line-height: 1.6;
}

.network-focus-banner {
  padding: 26px;
  border-radius: 28px;
  background: linear-gradient(135deg, #6b37ef, #8055ff);
  color: #fff;
}

.network-focus-copy span {
  display: inline-flex;
  margin-bottom: 14px;
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.network-focus-copy h2 {
  margin: 0 0 12px;
  max-width: 12ch;
  font-size: clamp(2rem, 3vw, 2.8rem);
  line-height: 0.95;
}

.network-focus-copy p {
  max-width: 42ch;
  margin: 0 0 20px;
  color: rgba(255, 255, 255, 0.88);
}

.network-focus-copy .ghost-button {
  width: fit-content;
  background: rgba(255, 255, 255, 0.16);
  color: #fff;
  border-color: rgba(255, 255, 255, 0.24);
}

.profile-panel-card,
.user-pill,
.chat-contact.is-active {
  background: linear-gradient(145deg, color-mix(in srgb, var(--student-card, #f1e4d0) 85%, white), var(--student-card, #f1e4d0));
  font-family: var(--student-font, "Plus Jakarta Sans", sans-serif);
}

.profile-panel-card strong,
.user-pill span:last-child {
  color: var(--student-accent, var(--ink));
}

.profile-post-card .thread-badge,
.generated-post .thread-badge {
  border: 1px solid rgba(231, 132, 121, 0.18);
}

.profile-mini-grid .generated-post {
  margin: 0;
}

@media (max-width: 1120px) {
  .network-results-grid {
    grid-template-columns: 1fr;
  }

  .post-dialog {
    width: min(92vw, 720px);
    min-width: 0;
  }
}

@media (max-width: 560px) {
  .comment-form,
  .feed-search-row {
    grid-template-columns: 1fr;
  }

  .chat-thread-top {
    flex-direction: column;
    align-items: flex-start;
  }
}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             
/* ============================================================
   PROFILE DASHBOARD — new design
   ============================================================ */

/* View wrapper */
.pf-dashboard-view.app-view-active {
  display: grid !important;
  gap: 20px;
}

.pf-dashboard-header {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pf-back-btn {
  font-size: 0.9rem;
}

.pf-dashboard-grid {
  display: grid;
  gap: 16px;
}

/* Generic row */
.pf-row {
  display: grid;
  gap: 16px;
}

.pf-row-top {
  grid-template-columns: 1fr 280px;
  align-items: stretch;
}

.pf-row-mid {
  grid-template-columns: 1fr 1fr;
  align-items: start;
}

.pf-row-bottom {
  grid-template-columns: 1fr;
  align-items: start;
}

/* ---- HERO CARD ---- */
.pf-hero-card {
  position: relative;
  height: 100%;
  padding: 32px;
  background: linear-gradient(135deg, rgba(124, 58, 237, 0.02) 0%, rgba(124, 58, 237, 0) 100%);
}

.pf-edit-btn {
  position: absolute;
  top: 24px;
  right: 24px;
  font-size: 0.82rem;
  padding: 8px 16px;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.pf-edit-btn:hover {
  background: var(--primary);
  color: var(--accent);
}

.pf-hero-body {
  display: flex;
  gap: 40px;
  align-items: flex-start;
}

.pf-hero-left {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 16px;
  min-width: 160px;
}

.pf-hero-left-info {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.pf-avatar.large {
  width: 100px;
  height: 100px;
  font-size: 2rem;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  box-shadow: 0 4px 16px rgba(124, 58, 237, 0.15);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.pf-avatar.large:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(124, 58, 237, 0.25);
}

.pf-hero-name {
  margin: 0;
  font-size: 1.6rem;
  font-weight: 800;
  line-height: 1.2;
  color: var(--student-accent, var(--ink));
  letter-spacing: -0.02em;
}

.pf-hero-specialty {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--accent);
  text-transform: capitalize;
}

.pf-hero-org {
  margin: 0;
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  color: var(--ink-muted);
}

.pf-hero-email {
  margin: 4px 0 0;
  font-size: 0.82rem;
  color: var(--ink-muted);
  word-break: break-all;
}

.pf-hero-right {
  flex: 1;
  display: grid;
  gap: 20px;
}

.pf-stats-row {
  display: flex;
  gap: 32px;
  padding: 12px 0;
  border-bottom: 1px solid var(--border);
}

.pf-stat {
  display: grid;
  gap: 4px;
  text-align: left;
}

.pf-stat strong {
  font-size: 2rem;
  font-weight: 800;
  line-height: 1;
  color: var(--accent);
}

.pf-stat span {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--ink-muted);
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.pf-hero-section {
  display: grid;
  gap: 8px;
}

.pf-section-label {
  margin: 0;
  font-size: 0.75rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--ink-muted);
}

.pf-hero-bio {
  margin: 0;
  font-size: 0.95rem;
  color: var(--ink-soft);
  line-height: 1.6;
  max-width: 50ch;
  font-weight: 500;
}

.pf-interests-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}

.pf-interest-tag {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border-radius: 999px;
  background: var(--primary);
  color: var(--accent);
  font-size: 0.8rem;
  font-weight: 700;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.pf-interest-tag:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
  background: var(--accent);
  color: var(--primary);
}

.pf-interests-text {
  display: inline-flex;
  align-items: center;
  padding: 8px 16px;
  border-radius: 999px;
  background: var(--primary);
  color: var(--accent);
  font-size: 0.82rem;
  font-weight: 700;
  transition: all 0.2s ease;
}

.pf-interests-text:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
}

.pf-hero-meta-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  font-size: 0.82rem;
  color: var(--ink-muted);
  padding-top: 8px;
  border-top: 1px solid var(--border);
}

.pf-email-badge,
.pf-country-badge {
  font-size: 0.82rem;
  color: var(--ink-muted);
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

/* ---- CAREER CARD ---- */
.pf-career-card {
  display: grid;
  align-content: start;
  gap: 10px;
  height: 100%;
  padding: 18px;
}

.pf-career-head {
  display: flex;
  align-items: center;
  gap: 10px;
}

.pf-career-icon {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background: var(--primary);
  color: var(--accent);
  display: grid;
  place-items: center;
}

.pf-career-title {
  margin: 0;
  font-size: 1.15rem;
  font-weight: 800;
}

.pf-career-desc {
  margin: 0;
  font-size: 0.85rem;
  color: var(--ink-soft);
  line-height: 1.55;
}

.pf-cv-preview {
  display: grid;
  gap: 8px;
  padding: 14px;
  border-radius: 20px;
  background: linear-gradient(180deg, #fcfbff, #f5f1ff);
  border: 1px solid rgba(124, 58, 237, 0.12);
}

.pf-cv-preview-head {
  display: flex;
  align-items: center;
  gap: 10px;
}

.pf-cv-avatar {
  width: 52px;
  height: 52px;
  flex-basis: 52px;
  border-radius: 16px;
}

.pf-cv-preview-copy {
  display: grid;
  gap: 2px;
  min-width: 0;
}

.pf-cv-name {
  font-size: 0.94rem;
  font-weight: 800;
}

.pf-cv-role {
  color: var(--ink-soft);
  font-size: 0.78rem;
  line-height: 1.35;
}

.pf-cv-preview-text {
  margin: 0;
  color: var(--ink);
  font-size: 0.82rem;
  line-height: 1.45;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.pf-cv-preview-meta {
  display: grid;
  gap: 4px;
}

.pf-cv-preview-meta span {
  color: var(--ink-soft);
  font-size: 0.74rem;
  line-height: 1.28;
}

.pf-cv-badge {
  margin-left: auto;
  align-self: flex-start;
  padding: 8px 12px;
  border-radius: 999px;
  background: rgba(124, 58, 237, 0.12);
  color: var(--accent);
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.pf-progress-wrap {
  display: grid;
  gap: 4px;
}

.pf-progress-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  color: var(--ink-muted);
}

.pf-progress-meta strong {
  color: var(--accent);
  font-size: 0.85rem;
}

.pf-progress-track {
  height: 8px;
  border-radius: 999px;
  background: var(--primary);
  overflow: hidden;
}

.pf-progress-track i {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, var(--accent), #a855f7);
}

.pf-cv-button {
  margin-top: 4px;
}

/* ---- INTERNSHIPS MINI ---- */
.pf-internships-card {
  display: grid;
  gap: 16px;
  padding: 22px;
}

.pf-card-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.pf-card-head h3 {
  margin: 0 0 4px;
  font-size: 1.1rem;
  font-weight: 800;
}

.pf-new-badge {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--accent);
}

.pf-trend-icon {
  font-size: 1.2rem;
  color: var(--ink-muted);
}

.pf-internship-list {
  display: grid;
  gap: 10px;
}

.pf-internship-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 14px;
  background: var(--surface-muted);
  border: 1px solid var(--border);
  cursor: pointer;
  transition: background 0.18s;
}

.pf-internship-row:hover {
  background: var(--primary);
}

.pf-company-icon {
  font-size: 1.2rem;
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  background: #fff;
  border-radius: 10px;
  border: 1px solid var(--border);
  flex-shrink: 0;
}

.pf-company-info {
  flex: 1;
}

.pf-company-info strong {
  display: block;
  font-size: 0.88rem;
  font-weight: 700;
  color: var(--ink);
}

.pf-company-info p {
  margin: 2px 0 0;
  font-size: 0.78rem;
  color: var(--ink-muted);
}

.pf-arrow {
  color: var(--ink-muted);
  font-size: 1.2rem;
  font-weight: 700;
}

.pf-see-all {
  font-size: 0.85rem;
  padding: 8px 14px;
  width: fit-content;
  color: var(--accent);
  border-color: transparent;
  background: transparent;
}

/* ---- ACADEMIC EXCHANGE CARD ---- */
.pf-exchange-card {
  position: relative;
  display: grid;
  gap: 16px;
  padding: 22px;
  border-radius: var(--radius-lg);
  overflow: hidden;
  background: #e8e0f8;
  border: 1px solid var(--border);
}

.pf-exchange-blob {
  position: absolute;
  right: -20px;
  top: 50%;
  transform: translateY(-50%);
  width: 160px;
  height: 160px;
  border-radius: 50% 45% 55% 40%;
  background: linear-gradient(135deg, #8b5cf6, #6d28d9);
  opacity: 0.85;
  pointer-events: none;
}

.pf-exchange-content {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-width: 68%;
}

.pf-exchange-badge {
  display: inline-flex;
  padding: 4px 10px;
  border-radius: 999px;
  background: var(--accent);
  color: #fff;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  width: fit-content;
}

.pf-exchange-content h3 {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 800;
  color: var(--ink);
  line-height: 1.25;
}

.pf-exchange-content p {
  margin: 0;
  font-size: 0.82rem;
  color: var(--ink-soft);
  line-height: 1.5;
}

.pf-affiliation-switch {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.pf-affiliation-switch .ghost-button.is-active {
  background: rgba(255, 255, 255, 0.92);
  color: var(--accent);
  border-color: rgba(124, 58, 237, 0.18);
}

.pf-affiliation-select {
  width: 100%;
  background: rgba(255, 255, 255, 0.9);
}

.pf-affiliation-links {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.pf-affiliation-links:empty {
  display: none;
}

.pf-affiliation-links .ghost-button,
.pf-affiliation-links .primary-button {
  width: fit-content;
}

.pf-affiliation-info {
  margin-top: 16px;
  padding: 18px;
  border-radius: 20px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.95);
  display: grid;
  gap: 12px;
}

.pf-affiliation-info.is-hidden {
  display: none;
}

.pf-affiliation-info h4 {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
}

.pf-affiliation-card-header {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pf-affiliation-card-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: rgba(99, 102, 241, 0.14);
  font-size: 1.1rem;
}

.pf-affiliation-card-meta {
  display: inline-block;
  margin-top: 2px;
  color: rgba(15, 23, 42, 0.65);
  font-size: 0.82rem;
}

.pf-affiliation-section {
  padding: 14px;
  border-radius: 18px;
  background: rgba(247, 247, 252, 0.95);
  border: 1px solid rgba(99, 102, 241, 0.16);
  display: grid;
  gap: 8px;
}

.pf-affiliation-section h5 {
  margin: 0;
  font-size: 0.92rem;
  font-weight: 700;
  color: rgba(15, 23, 42, 0.92);
}

.pf-affiliation-section p,
.pf-affiliation-section span,
.pf-affiliation-section a {
  margin: 0;
  color: rgba(15, 23, 42, 0.76);
  font-size: 0.9rem;
  line-height: 1.5;
}

.pf-affiliation-section a {
  color: var(--accent);
  font-weight: 700;
  text-decoration: none;
}

.pf-affiliation-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.pf-affiliation-tag {
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(99, 102, 241, 0.12);
  color: #312e81;
  font-size: 0.78rem;
}

.pf-exchange-content .primary-button {
  width: fit-content;
  padding: 10px 18px;
  font-size: 0.82rem;
}

/* ---- QUICK ACCESS ---- */
.pf-quick-access {
  display: grid;
  gap: 6px;
  padding: 22px;
}

.pf-section-label {
  margin: 0 0 6px;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: var(--ink-muted);
  text-transform: uppercase;
}

.pf-quick-list {
  display: grid;
  gap: 4px;
}

.pf-quick-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-radius: 14px;
  background: var(--surface-muted);
  border: 1px solid var(--border);
  color: var(--ink);
  font-weight: 600;
  font-size: 0.88rem;
  text-align: left;
  cursor: pointer;
  transition: background 0.15s;
  width: 100%;
}

.pf-quick-item:hover {
  background: var(--primary);
}

.pf-quick-item-static {
  cursor: default;
}

.pf-quick-item-static:hover {
  background: var(--surface-muted);
  transform: none;
}

.pf-quick-icon {
  font-size: 1rem;
}

.pf-quick-label {
  flex: 1;
}

.pf-quick-arrow {
  color: var(--ink-muted);
  font-size: 1.1rem;
}

.pf-lang-badge {
  padding: 3px 10px;
  border-radius: 999px;
  background: var(--primary);
  color: var(--accent);
  font-size: 0.75rem;
  font-weight: 800;
}

/* ---- ACTIVITY CARD ---- */
.pf-activity-card {
  display: grid;
  gap: 18px;
  padding: 22px;
}

.pf-activity-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.pf-activity-head h3 {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 800;
}

.pf-live-badge {
  font-size: 0.72rem;
  font-weight: 800;
  color: #ef4444;
  letter-spacing: 0.06em;
}

.pf-activity-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
}

.pf-activity-stat {
  display: grid;
  gap: 3px;
}

.pf-activity-stat > strong {
  font-size: 2rem;
  font-weight: 800;
  line-height: 1;
  color: var(--ink);
}

.pf-stat-label {
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--ink-muted);
}

.pf-stat-delta {
  font-size: 0.75rem;
  font-weight: 700;
  font-style: normal;
}

.pf-delta-positive {
  color: #16a34a;
}

.pf-delta-warn {
  color: #d97706;
}

.pf-mini-bars {
  display: flex;
  align-items: flex-end;
  gap: 4px;
  height: 44px;
  margin-top: 8px;
}

.pf-mini-bars i {
  display: block;
  flex: 1;
  height: var(--h, 50%);
  border-radius: 4px 4px 0 0;
  background: var(--accent);
  opacity: 0.22;
}

.pf-mini-bars i:last-child {
  opacity: 0.9;
}

/* ---- POSTS SECTION ---- */
.pf-posts-section {
  display: grid;
  gap: 18px;
}

.pf-posts-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  flex-wrap: wrap;
}

/* Responsive */
@media (max-width: 1060px) {
  .pf-row-top,
  .pf-row-mid {
    grid-template-columns: 1fr;
  }

  .pf-row-bottom {
    grid-template-columns: 1fr;
  }

  .pf-exchange-blob {
    width: 100px;
    height: 100px;
  }

  .pf-exchange-content {
    max-width: 80%;
  }

  .pf-activity-grid {
    grid-template-columns: 1fr;
  }
}

/* ============================================================
   PROJECTS WORKSPACE — new design
   ============================================================ */

.pw-workspace {
  display: none !important;
  flex-direction: column;
  gap: 20px;
  padding: 0;
  background: transparent;
  border: none;
  position: relative;
}

.pw-workspace.app-view-active {
  display: flex !important;
}

/* Header */
.pw-breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--ink-muted);
  margin-bottom: 6px;
}

.pw-breadcrumb-sep { color: var(--border); }
.pw-breadcrumb-active { color: var(--accent); }

.pw-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.pw-title {
  margin: 0;
  font-size: clamp(1.6rem, 3vw, 2.2rem);
  font-weight: 800;
  letter-spacing: -0.02em;
}

.pw-header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pw-team-avatars {
  display: flex;
  align-items: center;
}

.pw-team-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-size: 0.72rem;
  font-weight: 800;
  color: #fff;
  border: 2px solid #fff;
  margin-left: -8px;
}

.pw-team-avatars .pw-team-avatar:first-child { margin-left: 0; }

.pw-team-count {
  background: var(--surface-muted);
  color: var(--ink-soft);
  border: 2px solid var(--border);
}

.pw-share-btn { padding: 12px 22px; }

/* Stats row */
.pw-stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
}

.pw-stat-card {
  display: grid;
  gap: 8px;
  padding: 20px 22px;
}

.pw-stat-label {
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--ink-muted);
}

.pw-stat-value {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.pw-stat-value > strong {
  font-size: 2rem;
  font-weight: 800;
  line-height: 1;
  color: var(--ink);
}

.pw-stat-delta-pos {
  font-size: 0.82rem;
  font-weight: 700;
  color: #16a34a;
}

.pw-stat-bar {
  height: 5px;
  border-radius: 999px;
  background: var(--primary);
  overflow: hidden;
}

.pw-stat-bar i {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, var(--accent), #a855f7);
}

.pw-deadline-value {
  align-items: center;
  gap: 10px;
}

.pw-calendar-icon { font-size: 1.3rem; }

.pw-tasks-num {
  font-size: 2rem;
  font-weight: 800;
  color: var(--ink);
}

.pw-tasks-of {
  font-size: 0.9rem;
  color: var(--ink-muted);
  font-weight: 600;
  align-self: flex-end;
  margin-bottom: 4px;
}

.pw-stat-sub {
  margin: 0;
  font-size: 0.78rem;
  color: var(--ink-muted);
  font-weight: 600;
}

/* Main layout */
.pw-main-layout {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 16px;
  align-items: start;
}

/* Kanban panel */
.pw-kanban-panel {
  padding: 24px;
  display: grid;
  gap: 20px;
}

.pw-kanban-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.pw-kanban-header h2 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 800;
}

.pw-add-col-btn {
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  color: var(--accent);
  border-color: transparent;
  background: transparent;
  padding: 8px 12px;
}

.pw-kanban-board {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  align-items: start;
}

.pw-kanban-col { display: grid; gap: 10px; }

.pw-col-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.pw-col-title {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.07em;
  color: var(--ink-muted);
}

.pw-col-active { color: var(--accent); }
.pw-col-done   { color: #16a34a; }

.pw-col-count {
  display: grid;
  place-items: center;
  width: 22px;
  height: 22px;
  border-radius: 999px;
  background: var(--surface-muted);
  border: 1px solid var(--border);
  font-size: 0.72rem;
  font-weight: 800;
  color: var(--ink-muted);
}

.pw-count-active { background: var(--primary); color: var(--accent); border-color: transparent; }
.pw-count-done   { background: #dcfce7; color: #16a34a; border-color: transparent; }

.pw-kanban-cards { display: grid; gap: 10px; }

.pw-card {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 16px;
  display: grid;
  gap: 10px;
  box-shadow: 0 1px 6px rgba(0,0,0,0.05);
}

.pw-card-done {
  opacity: 0.7;
}

.pw-card-tag {
  display: inline-flex;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 700;
  width: fit-content;
}

.pw-tag-research { background: #ede9fe; color: #5b21b6; }
.pw-tag-urgent   { background: #fef3c7; color: #92400e; }
.pw-tag-design   { background: #d1fae5; color: #065f46; }

.pw-card-text {
  margin: 0;
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--ink);
  line-height: 1.4;
}

.pw-card-text-done {
  text-decoration: line-through;
  color: var(--ink-muted);
  font-weight: 600;
}

.pw-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-top: 4px;
}

.pw-card-icon { font-size: 1rem; color: var(--ink-muted); }

.pw-card-time {
  font-size: 0.72rem;
  color: var(--ink-muted);
  font-weight: 600;
}

.pw-card-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-size: 0.65rem;
  font-weight: 800;
  color: #fff;
}

.pw-avatar-muted {
  background: var(--surface-muted);
  border: 2px dashed var(--border);
}

.pw-done-check {
  display: grid;
  place-items: center;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #dcfce7;
  color: #16a34a;
  font-weight: 800;
  font-size: 0.85rem;
}

/* Sidebar */
.pw-sidebar {
  display: grid;
  gap: 14px;
}

/* AI card */
.pw-ai-card {
  display: grid;
  gap: 14px;
  padding: 20px;
}

.pw-ai-header {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pw-ai-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: var(--primary);
  color: var(--accent);
  display: grid;
  place-items: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.pw-ai-name {
  display: block;
  font-size: 0.95rem;
  font-weight: 800;
  color: var(--ink);
}

.pw-ai-status {
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #16a34a;
}

.pw-ai-messages {
  display: grid;
  gap: 10px;
  max-height: 160px;
  overflow-y: auto;
}

.pw-ai-msg {
  padding: 12px 14px;
  border-radius: 14px;
  font-size: 0.82rem;
  line-height: 1.5;
  font-weight: 500;
}

.pw-ai-msg-in {
  background: var(--surface-muted);
  border: 1px solid var(--border);
  color: var(--ink-soft);
  border-radius: 14px 14px 14px 4px;
}

.pw-ai-msg-out {
  background: var(--accent);
  color: #fff;
  border-radius: 14px 14px 4px 14px;
}

.pw-ai-input-row {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 8px;
  align-items: center;
}

.pw-ai-input {
  min-height: 44px;
  font-size: 0.85rem;
}

.pw-ai-send {
  width: 44px;
  height: 44px;
  padding: 0;
  font-size: 1.2rem;
  display: grid;
  place-items: center;
}

/* Files card */
.pw-files-card {
  display: grid;
  gap: 14px;
  padding: 20px;
}

.pw-files-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.pw-files-more {
  padding: 4px 8px;
  font-size: 1rem;
  border-color: transparent;
  background: transparent;
  color: var(--ink-muted);
}

.pw-file-list { display: grid; gap: 10px; }

.pw-file-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pw-file-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: grid;
  place-items: center;
  font-size: 0.62rem;
  font-weight: 800;
  flex-shrink: 0;
  color: #fff;
}

.pw-file-pdf { background: #ef4444; }
.pw-file-doc { background: #3b82f6; }
.pw-file-xls { background: #16a34a; }

.pw-file-info { display: grid; gap: 1px; }

.pw-file-info strong {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--ink);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 190px;
}

.pw-file-info span {
  font-size: 0.72rem;
  color: var(--ink-muted);
}

.pw-upload-btn {
  border: 1px dashed var(--border) !important;
  background: var(--surface-muted) !important;
  color: var(--ink-muted) !important;
  font-size: 0.82rem;
  cursor: pointer;
  text-align: center;
  padding: 12px;
  border-radius: 12px;
  display: grid;
  place-items: center;
}

/* FAB */
.pw-fab {
  position: fixed;
  bottom: 32px;
  right: 32px;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  font-size: 1.6rem;
  padding: 0;
  display: none;
  place-items: center;
  box-shadow: 0 4px 20px rgba(124,58,237,0.4);
  z-index: 50;
}

#projectsView.app-view-active ~ * .pw-fab,
.pw-workspace.app-view-active .pw-fab {
  display: grid;
}

@media (max-width: 1060px) {
  .pw-main-layout {
    grid-template-columns: 1fr;
  }
  .pw-kanban-board {
    grid-template-columns: 1fr 1fr;
  }
  .pw-stats-row {
    grid-template-columns: 1fr;
  }
}

/* ============================================================
   PROJECTS LIST VIEW
   ============================================================ */

/* ── Project list / detail toggle ── */
.pw-list-view { display: grid; gap: 28px; }
.pw-detail-view { display: grid; gap: 20px; }

/* List header */
.pw-list-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.pw-list-header h1 {
  margin: 0 0 4px;
  font-size: 1.6rem;
  font-weight: 800;
  color: #16131d;
}

.pw-list-sub {
  margin: 0;
  font-size: 0.85rem;
  color: #857d93;
}

/* ── Project grid ── */
.pw-project-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

/* ── Project card ── */
.pw-project-card {
  background: #ffffff;
  border: 1.5px solid #ece7f4;
  border-radius: 20px;
  padding: 22px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 14px;
  transition: transform 0.18s, box-shadow 0.18s, border-color 0.18s;
}

.pw-project-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 30px rgba(124,58,237,0.12);
  border-color: #7c3aed;
}

/* Card top row: badge + status */
.pw-pcard-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.pw-pcard-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.02em;
}

.pw-pbadge-research { background: #ede9fe; color: #5b21b6; }
.pw-pbadge-design   { background: #d1fae5; color: #065f46; }
.pw-pbadge-ai       { background: #dbeafe; color: #1e40af; }

.pw-pcard-status {
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  padding: 3px 10px;
  border-radius: 999px;
}

.pw-pstatus-active {
  background: #f0ebff;
  color: #7c3aed;
}
.pw-pstatus-done {
  background: #dcfce7;
  color: #16a34a;
}

/* Title & desc */
.pw-pcard-title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 800;
  line-height: 1.3;
  color: #16131d;
}

.pw-pcard-desc {
  margin: 0;
  font-size: 0.82rem;
  color: #625b70;
  line-height: 1.55;
  flex: 1;
}

/* Progress */
.pw-pcard-progress {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.pw-pcard-prog-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.7rem;
  font-weight: 700;
  color: #857d93;
}

.pw-pcard-prog-meta strong {
  color: #7c3aed;
  font-size: 0.82rem;
}

/* Footer: team + deadline */
.pw-pcard-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding-top: 6px;
  border-top: 1px solid #ece7f4;
}

.pw-pcard-team {
  display: flex;
  gap: 0;
}

.pw-pcard-team .pw-team-avatar {
  width: 28px;
  height: 28px;
  font-size: 0.6rem;
  margin-left: -6px;
  border: 2px solid #fff;
}

.pw-pcard-team .pw-team-avatar:first-child {
  margin-left: 0;
}

.pw-pcard-deadline {
  font-size: 0.72rem;
  color: #857d93;
  font-weight: 600;
}

/* ── New project placeholder ── */
.pw-new-project-card {
  background: #fafaf8;
  border: 2px dashed #ece7f4;
  border-radius: 20px;
  padding: 22px;
  min-height: 200px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  cursor: pointer;
  color: #857d93;
  font-weight: 700;
  font-size: 0.85rem;
  transition: background 0.18s, border-color 0.18s, color 0.18s;
}

.pw-new-project-card:hover {
  background: #f0ebff;
  border-color: #7c3aed;
  color: #7c3aed;
}

.pw-new-icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #ede9fe;
  color: #7c3aed;
  font-size: 1.6rem;
  font-weight: 300;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

@media (max-width: 900px) {
  .pw-project-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 580px) {
  .pw-project-grid {
    grid-template-columns: 1fr;
  }
}

/* Detail view back + title row */
.pw-back-and-title {
  display: flex;
  align-items: center;
  gap: 16px;
}

.pw-back-btn {
  font-size: 0.85rem;
  white-space: nowrap;
  flex-shrink: 0;
}

/* ============================================================
   MESSENGER / CHATS — new design
   ============================================================ */

.msg-view {
  display: none !important;
  height: calc(100vh - 80px);
  max-height: 860px;
  border-radius: 24px;
  overflow: hidden;
  border: 1.5px solid #ece7f4;
  background: #fff;
}

.msg-view.app-view-active {
  display: grid !important;
  grid-template-columns: 300px 1fr;
}

/* ── SIDEBAR ── */
.msg-sidebar {
  display: flex;
  flex-direction: column;
  border-right: 1.5px solid #ece7f4;
  background: #fff;
  overflow: hidden;
}

.msg-sidebar-head {
  padding: 24px 20px 12px;
  flex-shrink: 0;
}

.msg-sidebar-title {
  margin: 0;
  font-size: 1.35rem;
  font-weight: 800;
  color: #16131d;
}

.msg-search-wrap {
  position: relative;
  padding: 0 14px 14px;
  flex-shrink: 0;
}

.msg-search-icon {
  position: absolute;
  left: 26px;
  top: 50%;
  transform: translateY(-60%);
  font-size: 0.85rem;
  pointer-events: none;
}

.msg-search-input {
  width: 100%;
  height: 42px;
  border-radius: 999px;
  border: 1.5px solid #ece7f4;
  background: #f8f6ff;
  padding: 0 16px 0 36px;
  font-size: 0.85rem;
  color: #16131d;
  outline: none;
  transition: border-color 0.15s;
}

.msg-search-input:focus {
  border-color: #7c3aed;
}

.msg-contact-list {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 0 8px 12px;
}

/* Contact row */
.msg-contact-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 12px;
  border-radius: 16px;
  border: none;
  background: transparent;
  cursor: pointer;
  text-align: left;
  transition: background 0.15s;
  width: 100%;
}

.msg-contact-item:hover {
  background: #f8f6ff;
}

.msg-contact-item.is-active {
  background: #f0ebff;
}

.msg-contact-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #a78bfa;
  color: #fff;
  font-size: 0.78rem;
  font-weight: 800;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  background-size: cover;
  background-position: center;
  position: relative;
}

.msg-avatar-system { background: #ede9fe; color: #7c3aed; }
.msg-avatar-group  { background: #ede9fe; color: #7c3aed; font-size: 1.1rem; }

.msg-contact-info {
  flex: 1;
  min-width: 0;
}

.msg-contact-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
  margin-bottom: 3px;
}

.msg-contact-name {
  font-size: 0.88rem;
  font-weight: 700;
  color: #16131d;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.msg-contact-time {
  font-size: 0.68rem;
  color: #857d93;
  flex-shrink: 0;
  font-weight: 500;
}

.msg-contact-preview {
  margin: 0;
  font-size: 0.78rem;
  color: #857d93;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-weight: 400;
}

/* ── THREAD SHELL ── */
.msg-thread-shell {
  display: flex;
  flex-direction: column;
  background: #f8f6ff;
  overflow: hidden;
}

/* Thread header */
.msg-thread-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 24px;
  background: #fff;
  border-bottom: 1.5px solid #ece7f4;
  flex-shrink: 0;
}

.msg-thread-peer {
  display: flex;
  align-items: center;
  gap: 12px;
}

.msg-peer-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  font-size: 0.78rem;
  background-size: cover;
  background-position: center;
}

.msg-peer-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.msg-peer-info strong {
  font-size: 0.95rem;
  font-weight: 800;
  color: #16131d;
}

.msg-peer-status {
  font-size: 0.72rem;
  font-weight: 600;
  color: #16a34a;
  white-space: nowrap;
}

.msg-info-btn {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  padding: 0;
  color: #857d93;
  border-color: #ece7f4;
}

.msg-info-btn:hover {
  color: #7c3aed;
  border-color: #7c3aed;
  background: #f0ebff;
}

/* Messages area */
.msg-messages {
  flex: 1;
  overflow-y: auto;
  padding: 24px 24px 12px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.msg-day-divider {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 8px 0 16px;
}

.msg-day-divider span {
  font-size: 0.72rem;
  font-weight: 700;
  color: #857d93;
  background: #f8f6ff;
  padding: 4px 14px;
  border-radius: 999px;
  border: 1px solid #ece7f4;
}

/* Bubble rows */
.msg-bubble-row {
  display: flex;
  align-items: flex-end;
  gap: 10px;
  margin-bottom: 6px;
  width: 100%;
}

.msg-row-in {
  justify-content: flex-start;
  flex-direction: row;
}

.msg-row-out {
  justify-content: flex-end;
  flex-direction: row;
}

.msg-bubble-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: #a78bfa;
  color: #fff;
  font-size: 0.65rem;
  font-weight: 800;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  background-size: cover;
  background-position: center;
}

.msg-bubble-wrap {
  display: flex;
  flex-direction: column;
  gap: 4px;
  max-width: 60%;
}

.msg-wrap-out {
  align-items: flex-end;
}

.msg-row-out .msg-bubble-wrap {
  align-items: flex-end;
}

.msg-bubble {
  padding: 14px 18px;
  border-radius: 20px;
  font-size: 0.88rem;
  line-height: 1.55;
  word-break: break-word;
}

.msg-bubble-in {
  background: #fff;
  color: #16131d;
  border-radius: 4px 20px 20px 20px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}

.msg-bubble-out {
  background: #7c3aed;
  color: #fff;
  border-radius: 20px 20px 4px 20px;
}

.msg-bubble-time {
  font-size: 0.65rem;
  color: #857d93;
  font-weight: 500;
}

.msg-row-out .msg-bubble-time {
  color: #857d93;
}

.msg-loading {
  text-align: center;
  color: #857d93;
  font-size: 0.85rem;
  padding: 24px;
}

/* Input bar */
.msg-input-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 20px;
  background: #fff;
  border-top: 1.5px solid #ece7f4;
  flex-shrink: 0;
}

.msg-attach-btn,
.msg-emoji-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  color: #857d93;
  cursor: pointer;
  background: transparent;
  border: none;
  flex-shrink: 0;
  transition: color 0.15s, background 0.15s;
}

.msg-attach-btn:hover,
.msg-emoji-btn:hover {
  color: #7c3aed;
  background: #f0ebff;
}

.msg-text-input {
  flex: 1;
  height: 48px;
  border-radius: 999px;
  border: 1.5px solid #ece7f4;
  background: #f8f6ff;
  padding: 0 20px;
  font-size: 0.88rem;
  color: #16131d;
  outline: none;
  transition: border-color 0.15s;
}

.msg-text-input:focus {
  border-color: #7c3aed;
}

.msg-send-btn {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #7c3aed;
  color: #fff;
  border: none;
  display: grid;
  place-items: center;
  cursor: pointer;
  flex-shrink: 0;
  transition: background 0.15s, transform 0.15s;
}

.msg-send-btn:hover {
  background: #6d28d9;
  transform: scale(1.06);
}

/* Attachment image in bubble */
.msg-bubble .message-attachment-image {
  max-width: 260px;
  border-radius: 12px;
  margin-top: 8px;
  display: block;
}

@media (max-width: 780px) {
  .msg-view.app-view-active {
    grid-template-columns: 1fr;
  }
  .msg-sidebar {
    display: none;
  }
  .msg-bubble-wrap {
    max-width: 80%;
  }
}

.profile-settings-dialog {
  width: min(58vw, 1080px);
  min-width: 860px;
}

.profile-settings-layout {
  display: flex;
  align-items: stretch;
  min-height: 620px;
}

.profile-settings-sidebar {
  flex: 0 0 260px;
  background: #f6f1e8;
}

.profile-settings-sidebar h3 {
  font-size: 1.2rem;
}

.profile-settings-nav {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.profile-settings-nav-item {
  display: flex;
  align-items: center;
}

.profile-settings-nav-item.is-active {
  box-shadow: 0 8px 20px rgba(22, 19, 29, 0.06);
}

.profile-settings-content {
  display: block;
  flex: 1 1 auto;
  padding: 28px 28px 24px;
}

@media (max-width: 1120px) {
  .profile-settings-dialog {
    width: min(92vw, 760px);
    min-width: 0;
  }

  .profile-settings-layout {
    display: grid;
    min-height: 0;
  }

  .profile-settings-sidebar {
    flex: 0 0 auto;
    border-right: 0;
    border-bottom: 1px solid var(--border);
  }
}

/* ============================================================
   REGISTRATION — 3-step with email verification
   ============================================================ */

/* Stepper */
.reg-stepper {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0;
  margin-bottom: 20px;
  padding: 0 10px;
}

.reg-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  flex-shrink: 0;
}

.reg-step-num {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  font-weight: 800;
  background: #e5e0f0;
  color: #9880c8;
  transition: background 0.25s, color 0.25s;
}

.reg-step-label {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--ink-muted);
  letter-spacing: 0.04em;
  transition: color 0.25s;
}

.reg-step.reg-step-active .reg-step-num {
  background: var(--accent);
  color: #fff;
}

.reg-step.reg-step-active .reg-step-label {
  color: var(--accent);
}

.reg-step.reg-step-done .reg-step-num {
  background: #dcfce7;
  color: #16a34a;
}

.reg-step.reg-step-done .reg-step-label {
  color: #16a34a;
}

.reg-step-line {
  flex: 1;
  height: 2px;
  background: #e5e0f0;
  margin: 0 6px;
  margin-bottom: 20px;
  border-radius: 999px;
  transition: background 0.3s;
}

.reg-step-line.reg-line-done {
  background: var(--accent);
}

/* Verification code inputs */
.verify-code-row {
  display: flex;
  gap: 10px;
  justify-content: center;
  margin: 20px 0 6px;
}

.verify-code-input {
  width: 52px;
  height: 64px;
  border-radius: 16px;
  border: 2px solid var(--border);
  background: #fafaf8;
  text-align: center;
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--ink);
  outline: none;
  transition: border-color 0.18s, background 0.18s, transform 0.12s;
  caret-color: var(--accent);
}

.verify-code-input:focus {
  border-color: var(--accent);
  background: #fff;
  transform: scale(1.06);
  box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
}

.verify-code-input.is-filled {
  border-color: var(--accent);
  background: #f5f0ff;
}

.verify-code-input.is-error {
  border-color: #e05252;
  background: #fff5f5;
  animation: shake 0.35s ease;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20% { transform: translateX(-6px); }
  40% { transform: translateX(6px); }
  60% { transform: translateX(-4px); }
  80% { transform: translateX(4px); }
}

/* Demo notice */
.verify-demo-notice {
  margin: 12px 0;
  padding: 16px 18px;
  border-radius: 18px;
  background: linear-gradient(135deg, #f0f9ff, #e8f4fe);
  border: 1.5px dashed #7cb9f5;
  text-align: center;
}

.verify-demo-label {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #2563eb;
  text-transform: uppercase;
  margin-bottom: 6px;
}

.verify-demo-code {
  font-size: 2.4rem;
  font-weight: 900;
  letter-spacing: 0.25em;
  color: var(--accent);
  font-family: "SFMono-Regular", Consolas, monospace;
  line-height: 1.1;
}

.verify-demo-hint {
  margin-top: 6px;
  font-size: 0.72rem;
  color: #6b7280;
}

/* Resend row */
.verify-resend-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin: 8px 0 4px;
  min-height: 40px;
}

.verify-timer {
  font-size: 0.85rem;
  color: var(--ink-muted);
  font-weight: 600;
}

.verify-resend-button {
  font-size: 0.85rem;
  padding: 8px 16px;
  color: var(--accent);
  border-color: rgba(124, 58, 237, 0.25);
}

/* Success state */
.reg-success-icon {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: #dcfce7;
  color: #16a34a;
  font-size: 2rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
}

.reg-success-title {
  text-align: center;
  font-size: 1.5rem;
  margin: 0 0 12px;
  color: var(--ink);
}

/* Password strength indicator */
.password-strength {
  height: 4px;
  border-radius: 999px;
  background: #e5e0f0;
  margin-top: 6px;
  overflow: hidden;
}

.password-strength-bar {
  height: 100%;
  border-radius: inherit;
  transition: width 0.25s, background 0.25s;
  width: 0%;
}

.password-strength-bar.strength-weak   { width: 33%; background: #ef4444; }
.password-strength-bar.strength-medium { width: 66%; background: #f97316; }
.password-strength-bar.strength-strong { width: 100%; background: #16a34a; }

.password-strength-label {
  font-size: 0.72rem;
  margin-top: 4px;
  font-weight: 600;
}

.password-strength-label.strength-weak   { color: #ef4444; }
.password-strength-label.strength-medium { color: #f97316; }
.password-strength-label.strength-strong { color: #16a34a; }

/* Sending spinner on button */
.primary-button.is-loading {
  opacity: 0.7;
  pointer-events: none;
  position: relative;
}

.primary-button.is-loading::after {
  content: '';
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  display: inline-block;
  margin-left: 10px;
  vertical-align: middle;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.auth-message-success {
  color: #16a34a;
}

/* ============================================================
   DARK THEME
   ============================================================ */

[data-theme="dark"] {
  --primary: #2d1f5e;
  --primary-dark: #9d6fef;
  --primary-light: #2d1f5e;
  --accent: #a78bfa;
  --accent-coral: #fb7185;
  --surface: #1a1625;
  --surface-muted: #120e1d;
  --surface-strong: #1f1830;
  --border: #2e2549;
  --ink: #ede9fe;
  --ink-soft: #c9c0df;
  --ink-muted: #9b8db7;
  --text: var(--ink);
  --text-muted: var(--ink-soft);
  --hover: #241b36;
  --tag-bg: #2d1f5e;
  --tag-ink: #c4a8ff;
  --shadow: 0 24px 70px rgba(0, 0, 0, 0.35);
  --shadow-soft: 0 18px 46px rgba(0, 0, 0, 0.28);
}

[data-theme="dark"] body {
  background: #0d0a18;
  color: var(--ink);
}

/* Topbar */
[data-theme="dark"] .topbar {
  background: #1a1625;
  border-color: var(--border);
}

[data-theme="dark"] .panel,
[data-theme="dark"] .auth-intro,
[data-theme="dark"] .auth-card {
  background: #1a1625;
  border-color: var(--border);
}

[data-theme="dark"] .app-menu {
  background: #1a1625;
}

[data-theme="dark"] .quick-overview,
[data-theme="dark"] .sidebar-tasks-panel,
[data-theme="dark"] .thread-card {
  background: #1a1625;
}

[data-theme="dark"] .menu-item {
  color: var(--ink-soft);
}

[data-theme="dark"] .menu-item.is-active {
  background: var(--primary);
  color: var(--accent);
}

[data-theme="dark"] .menu-item:hover {
  background: #2d1f5e;
}

/* Buttons */
[data-theme="dark"] .ghost-button,
[data-theme="dark"] .composer-actions button,
[data-theme="dark"] .thread-footer button,
[data-theme="dark"] .tab-button,
[data-theme="dark"] .interest-chip,
[data-theme="dark"] .topbar-link,
[data-theme="dark"] .composer-tool-button {
  background: #231c3a;
  color: var(--ink);
  border-color: var(--border);
}

[data-theme="dark"] .ghost-button:hover,
[data-theme="dark"] .user-pill-button:hover {
  background: var(--primary);
}

[data-theme="dark"] .primary-button {
  background: var(--accent);
  color: #0d0a18;
}

[data-theme="dark"] .primary-button:hover {
  background: #c4b5fd;
}

/* User pill */
[data-theme="dark"] .user-pill {
  background: #231c3a;
  color: var(--ink-soft);
}

[data-theme="dark"] .user-pill-button {
  border-color: var(--border);
}

/* Inputs */
[data-theme="dark"] .auth-input,
[data-theme="dark"] input,
[data-theme="dark"] select,
[data-theme="dark"] textarea {
  background: #120e1d;
  border-color: var(--border);
  color: var(--ink);
}

[data-theme="dark"] .auth-input:focus,
[data-theme="dark"] input:focus,
[data-theme="dark"] select:focus,
[data-theme="dark"] textarea:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.15);
}

[data-theme="dark"] .auth-input::placeholder,
[data-theme="dark"] input::placeholder {
  color: var(--ink-muted);
}

/* Composer */
[data-theme="dark"] .composer-trigger,
[data-theme="dark"] .composer-trigger-feed {
  background: #120e1d;
  color: var(--ink-muted);
  border-color: var(--border);
}

/* Feed / thread cards */
[data-theme="dark"] .composer-feed-card,
[data-theme="dark"] .feed-search-shell-feed,
[data-theme="dark"] .feed-rail-card,
[data-theme="dark"] .feed-career-card,
[data-theme="dark"] .generated-post,
[data-theme="dark"] .feed-skeleton {
  background: #1a1625;
  border-color: var(--border);
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.4);
}

/* Avatar */
[data-theme="dark"] .avatar {
  background: #2d1f5e;
  color: var(--accent);
}

[data-theme="dark"] .avatar.sand {
  background: #3a2a10;
  color: #e2a86e;
}

[data-theme="dark"] .avatar.muted {
  background: #231c3a;
  color: var(--ink-muted);
}

/* Overview grid */
[data-theme="dark"] .overview-grid article {
  background: rgba(255, 255, 255, 0.04);
}

/* Sidebar strength card */
[data-theme="dark"] .sidebar-strength-card {
  background: #1a1625;
}

/* Side list */
[data-theme="dark"] .side-list a {
  border-top-color: var(--border);
}

/* Thread tags */
[data-theme="dark"] .thread-tags span {
  background: var(--tag-bg);
  color: var(--tag-ink);
}

/* Like button active */
[data-theme="dark"] .like-button.is-active {
  background: #3d1f6e;
  color: #c4a8ff;
}

/* Inbox / Notifications */
[data-theme="dark"] .inbox-preview,
[data-theme="dark"] .notification-card {
  background: #1f1830;
  border-color: var(--border);
  color: var(--ink);
}

[data-theme="dark"] .inbox-preview.is-unread,
[data-theme="dark"] .notification-card.is-unread {
  background: #2d1f5e;
}

/* Modal / overlay */
[data-theme="dark"] .modal-dialog,
[data-theme="dark"] .inbox-modal-dialog,
[data-theme="dark"] .chat-modal-dialog {
  background: #1a1625;
  border-color: var(--border);
}

[data-theme="dark"] .modal-header {
  background: #1a1625;
  border-color: var(--border);
}

/* Chat */
[data-theme="dark"] .chat-contact {
  background: #231c3a;
  color: var(--ink);
  border-color: var(--border);
}

[data-theme="dark"] .chat-contact.is-active {
  background: var(--primary);
  color: var(--accent);
}

[data-theme="dark"] .message-bubble.incoming {
  background: #231c3a;
  color: var(--ink);
}

[data-theme="dark"] .message-bubble.outgoing {
  background: #3d1f6e;
  color: #ede9fe;
}

/* Msg view */
[data-theme="dark"] .msg-sidebar {
  background: #1a1625;
  border-color: var(--border);
}

[data-theme="dark"] .msg-contact {
  color: var(--ink);
}

[data-theme="dark"] .msg-contact:hover,
[data-theme="dark"] .msg-contact.is-active {
  background: #2d1f5e;
}

[data-theme="dark"] .msg-chat-area {
  background: #120e1d;
}

[data-theme="dark"] .msg-header {
  background: #1a1625;
  border-color: var(--border);
}

[data-theme="dark"] .msg-footer {
  background: #1a1625;
  border-color: var(--border);
}

[data-theme="dark"] .msg-text-input {
  background: #120e1d;
  border-color: var(--border);
  color: var(--ink);
}

[data-theme="dark"] .msg-text-input:focus {
  border-color: var(--accent);
}

[data-theme="dark"] .msg-send-btn {
  background: var(--accent);
  color: #0d0a18;
}

[data-theme="dark"] .msg-send-btn:hover {
  background: #c4b5fd;
}

/* Profile settings sidebar */
[data-theme="dark"] .profile-settings-sidebar {
  background: #231c3a;
}

/* Auth screens */
[data-theme="dark"] .auth-intro,
[data-theme="dark"] .auth-card {
  background: #1a1625;
  border-color: var(--border);
}

[data-theme="dark"] .auth-copy,
[data-theme="dark"] .auth-instructions {
  color: var(--ink-soft);
}

/* Task items */
[data-theme="dark"] .task-row {
  background: #231c3a;
  border-color: var(--border);
}

[data-theme="dark"] .task-row:hover {
  background: #2d1f5e;
}

/* Community/internship cards */
[data-theme="dark"] .community-card,
[data-theme="dark"] .internship-card {
  background: #1a1625;
  border-color: var(--border);
}

/* Profile panel card */
[data-theme="dark"] .profile-panel-card {
  background: #1a1625;
  border-color: var(--border);
}

/* Thread badge */
[data-theme="dark"] .thread-badge.soft {
  background: #2d1f5e;
  color: var(--accent);
}

[data-theme="dark"] .thread-badge.warm {
  background: #3a1520;
  color: #fb7185;
}

/* Verify code input */
[data-theme="dark"] .verify-code-input {
  background: #120e1d;
  border-color: var(--border);
  color: var(--ink);
}

[data-theme="dark"] .verify-code-input:focus {
  background: #1a1625;
  box-shadow: 0 0 0 4px rgba(167, 139, 250, 0.15);
}

[data-theme="dark"] .verify-code-input.is-filled {
  background: #2d1f5e;
  border-color: var(--accent);
}

/* Registration stepper */
[data-theme="dark"] .reg-step-num {
  background: #231c3a;
  color: var(--ink-muted);
}

[data-theme="dark"] .reg-step-line {
  background: #2e2549;
}

[data-theme="dark"] .reg-step-line.reg-line-done {
  background: var(--accent);
}

/* Demo notice */
[data-theme="dark"] .verify-demo-notice {
  background: linear-gradient(135deg, #1a1f3a, #1a2640);
  border-color: #3b5998;
}

/* Password strength bar background */
[data-theme="dark"] .password-strength {
  background: #2e2549;
}

/* Friend suggestions */
[data-theme="dark"] .friend-suggestion-card {
  background: #1f1830;
  border-color: var(--border);
}

/* Discovery card */
[data-theme="dark"] .discovery-card {
  background: #1a1625;
  border-color: var(--border);
}

/* Kanban */
[data-theme="dark"] .kanban-col {
  background: #1a1625;
  border-color: var(--border);
}

[data-theme="dark"] .kanban-card {
  background: #231c3a;
  border-color: var(--border);
  color: var(--ink);
}

/* Post menu */
[data-theme="dark"] .post-menu {
  background: #231c3a;
  border-color: var(--border);
}

[data-theme="dark"] .post-menu button {
  color: var(--ink);
}

[data-theme="dark"] .post-menu button:hover {
  background: #2d1f5e;
}

/* Feed filter popover */
[data-theme="dark"] #feedFilterPopover {
  background: #1a1625;
  border-color: var(--border);
}

/* Topbar online counter */
[data-theme="dark"] .online-counter {
  background: #231c3a;
}

/* Status dot keeps color */
[data-theme="dark"] .status-dot {
  background: var(--accent-coral);
}

/* ============================================================
   THEME TOGGLE BUTTON
   ============================================================ */

.theme-toggle-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border-radius: 14px;
  background: var(--surface);
  border: 1px solid var(--border);
  cursor: pointer;
  transition: background 0.2s ease, transform 0.2s ease, border-color 0.2s ease;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.theme-toggle-button:hover {
  background: var(--primary);
  transform: translateY(-1px);
}

[data-theme="dark"] .theme-toggle-button {
  background: #231c3a;
  border-color: var(--border);
}

[data-theme="dark"] .theme-toggle-button:hover {
  background: var(--primary);
}

/* Smooth theme transition */
*, *::before, *::after {
  transition: background-color 0.25s ease, border-color 0.25s ease, color 0.15s ease;
}

/* But don't animate things that shouldn't transition */
button, input, select, textarea, .avatar, .status-dot, img {
  transition: none;
}

.theme-toggle-button {
  transition: background 0.2s ease, transform 0.2s ease, border-color 0.2s ease;
}
/* ============================================================
   ORG CHIPS — университет и место работы на карточке профиля
   ============================================================ */

.pf-org-chip {
  display: flex;
  align-items: center;
  gap: 10px;
  background: var(--surface-muted);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 9px 12px;
  width: 100%;
  transition: border-color .15s;
}

.pf-org-chip:hover {
  border-color: var(--accent);
}

.pf-org-chip-logo {
  width: 36px;
  height: 36px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .3px;
}

.pf-org-logo--uni {
  background: #e0e7ff;
  color: #3730a3;
}

.pf-org-logo--job {
  background: #fef3c7;
  color: #92400e;
}

.pf-org-chip-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.pf-org-chip-label {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: .6px;
  color: var(--ink-muted);
  line-height: 1;
}

.pf-org-chip-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--ink);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.3;
}

/* ── Select с иконкой слева ── */
.pf-select-with-icon {
  position: relative;
  display: flex;
  align-items: center;
}

.pf-select-icon {
  position: absolute;
  left: 10px;
  width: 30px;
  height: 30px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
  z-index: 1;
  flex-shrink: 0;
  transition: background .15s, color .15s;
}

.pf-select-inner {
  padding-left: 52px !important;
  width: 100%;
}

/* Тёмная тема */
[data-theme="dark"] .pf-org-chip {
  background: #1f1830;
  border-color: var(--border);
}

[data-theme="dark"] .pf-org-chip:hover {
  border-color: var(--accent);
}

[data-theme="dark"] .pf-org-chip-name {
  color: var(--ink);
}

[data-theme="dark"] .pf-org-logo--uni {
  background: #2d2a5e;
  color: #a5b4fc;
}

[data-theme="dark"] .pf-org-logo--job {
  background: #3d2a10;
  color: #fcd34d;
}

/* ============================================================
   DARK THEME — COMPLETE FIXES & IMPROVEMENTS
   ============================================================ */

/* ── Body & page background ── */
[data-theme="dark"] body {
  background: #0d0a18;
}

[data-theme="dark"] .page-shell {
  background: transparent;
}

/* ── Social posts ── */
[data-theme="dark"] .social-post {
  background: #1a1625;
  border-color: var(--border);
}

[data-theme="dark"] .social-post-name {
  color: var(--ink);
}

[data-theme="dark"] .social-post-content p {
  color: var(--ink);
}

[data-theme="dark"] .del-btn {
  background: #231c3a;
  border-color: var(--border);
  color: var(--ink-muted);
}

[data-theme="dark"] .del-btn:hover {
  background: #3a1520;
  color: #fb7185;
}

/* ── Filter input ── */
[data-theme="dark"] .filter-input {
  background: #1a1625;
  border-color: var(--border);
  color: var(--ink);
}

/* ── Feed search shell ── */
[data-theme="dark"] .feed-search-shell {
  background: #1a1625;
}

[data-theme="dark"] .feed-filter-popover {
  background: #1a1625;
  border-color: var(--border);
}

/* ── Composer trigger ── */
[data-theme="dark"] .composer-trigger,
[data-theme="dark"] .composer-trigger-feed {
  background: #120e1d;
  color: var(--ink-muted);
  border-color: var(--border);
}

/* ── Sidebar tasks ── */
[data-theme="dark"] .sidebar-task-item {
  background: #1f1830;
  border-color: var(--border);
}

[data-theme="dark"] .sidebar-task-text {
  color: var(--ink);
}

[data-theme="dark"] .sidebar-task-empty {
  background: #120e1d;
  color: var(--ink-soft);
}

/* ── Kanban ── */
[data-theme="dark"] .kanban-column {
  background: #1a1625;
}

[data-theme="dark"] .kanban-card {
  background: #231c3a;
  border-color: var(--border);
  color: var(--ink);
}

[data-theme="dark"] .pw-card {
  background: #1f1830;
  border-color: var(--border);
}

[data-theme="dark"] .pw-new-project-card {
  background: #120e1d;
  border-color: var(--border);
  color: var(--ink-muted);
}

[data-theme="dark"] .pw-new-project-card:hover {
  background: var(--primary);
  border-color: var(--accent);
  color: var(--accent);
}

[data-theme="dark"] .pw-new-icon {
  background: #2d1f5e;
  color: var(--accent);
}

[data-theme="dark"] .pw-project-card {
  background: #1a1625;
  border-color: var(--border);
}

[data-theme="dark"] .pw-project-card:hover {
  border-color: var(--accent);
  box-shadow: 0 10px 30px rgba(167,139,250,0.1);
}

[data-theme="dark"] .pw-pcard-title {
  color: var(--ink);
}

[data-theme="dark"] .pw-pcard-desc {
  color: var(--ink-soft);
}

[data-theme="dark"] .pw-pcard-footer {
  border-top-color: var(--border);
}

[data-theme="dark"] .pw-team-avatar {
  border-color: #1a1625;
}

[data-theme="dark"] .pw-list-header h1 {
  color: var(--ink);
}

[data-theme="dark"] .pw-list-sub {
  color: var(--ink-soft);
}

[data-theme="dark"] .pw-pstatus-active {
  background: #2d1f5e;
  color: var(--accent);
}

[data-theme="dark"] .pw-pstatus-done {
  background: #14532d;
  color: #86efac;
}

[data-theme="dark"] .pw-pbadge-research {
  background: #2d1f5e;
  color: #c4b5fd;
}

[data-theme="dark"] .pw-pbadge-design {
  background: #14532d;
  color: #86efac;
}

[data-theme="dark"] .pw-pbadge-ai {
  background: #1e3a5f;
  color: #93c5fd;
}

[data-theme="dark"] .pw-tag-research {
  background: #2d1f5e;
  color: #c4b5fd;
}

[data-theme="dark"] .pw-tag-urgent {
  background: #3d2a10;
  color: #fcd34d;
}

[data-theme="dark"] .pw-tag-design {
  background: #14532d;
  color: #86efac;
}

/* ── Profile panel / settings ── */
[data-theme="dark"] .profile-settings-layout {
  background: #1a1625;
}

[data-theme="dark"] .profile-settings-sidebar {
  background: #120e1d;
  border-right-color: var(--border);
}

[data-theme="dark"] .profile-settings-nav-item {
  color: var(--ink-soft);
}

[data-theme="dark"] .profile-settings-nav-item.is-active {
  background: #231c3a;
  border-color: var(--border);
  color: var(--ink);
}

[data-theme="dark"] .profile-settings-content {
  background: #1a1625;
}

[data-theme="dark"] .profile-summary-grid article {
  background: rgba(255,255,255,0.03);
}

[data-theme="dark"] .profile-post-composer {
  background: #1f1830;
}

[data-theme="dark"] .profile-post-textarea {
  background: #120e1d;
  border-color: var(--border);
  color: var(--ink);
}

[data-theme="dark"] .profile-empty-state {
  background: #1f1830;
  color: var(--ink-soft);
}

[data-theme="dark"] .profile-post-card {
  background: #1a1625;
}

[data-theme="dark"] .social-mini-card {
  background: #1a1625;
}

/* ── Profile hero / dashboard ── */
[data-theme="dark"] .pf-hero-card {
  background: #1a1625;
}

[data-theme="dark"] .pf-career-card,
[data-theme="dark"] .pf-internships-card,
[data-theme="dark"] .pf-quick-access,
[data-theme="dark"] .pf-activity-card {
  background: #1a1625;
}

[data-theme="dark"] .pf-internship-row {
  background: #231c3a;
  border-color: var(--border);
}

[data-theme="dark"] .pf-internship-row:hover {
  background: var(--primary);
}

[data-theme="dark"] .pf-company-icon {
  background: #231c3a;
  border-color: var(--border);
}

[data-theme="dark"] .pf-quick-item {
  background: #1f1830;
  border-color: var(--border);
}

[data-theme="dark"] .pf-quick-item:hover {
  background: var(--primary);
}

[data-theme="dark"] .pf-cv-preview {
  background: #1f1830;
  border-color: var(--border);
}

[data-theme="dark"] .pf-exchange-card {
  background: #1f1830;
  border-color: var(--border);
}

[data-theme="dark"] .pf-affiliation-info {
  background: #1f1830;
  border-color: var(--border);
}

[data-theme="dark"] .pf-affiliation-section {
  background: #231c3a;
  border-color: var(--border);
}

[data-theme="dark"] .pf-affiliation-section h5 {
  color: var(--ink);
}

[data-theme="dark"] .pf-affiliation-section p,
[data-theme="dark"] .pf-affiliation-section span {
  color: var(--ink-soft);
}

[data-theme="dark"] .pf-affiliation-tag {
  background: #2d1f5e;
  color: #c4b5fd;
}

/* ── Network ── */
[data-theme="dark"] .network-tile {
  background: #1a1625;
  border-color: var(--border);
}

[data-theme="dark"] .network-chip {
  background: #1f1830;
  border-color: var(--border);
  color: var(--ink);
}

[data-theme="dark"] .network-progress-card {
  background: #1f1830;
}

[data-theme="dark"] .network-progress-track {
  background: #2d1f5e;
}

/* ── Comment / attachment ── */
[data-theme="dark"] .comment-item,
[data-theme="dark"] .comment-empty {
  background: #1f1830;
  color: var(--ink-soft);
}

[data-theme="dark"] .comment-item strong {
  color: var(--ink);
}

[data-theme="dark"] .attachment-pill,
[data-theme="dark"] .attachment-preview {
  background: #231c3a;
  color: var(--ink-soft);
}

/* ── Messenger — complete ── */
[data-theme="dark"] .msg-view {
  background: #1a1625;
  border-color: var(--border);
}

[data-theme="dark"] .msg-sidebar {
  background: #120e1d;
  border-right-color: var(--border);
}

[data-theme="dark"] .msg-sidebar-title {
  color: var(--ink);
}

[data-theme="dark"] .msg-search-input {
  background: #1a1625;
  border-color: var(--border);
  color: var(--ink);
}

[data-theme="dark"] .msg-search-input::placeholder {
  color: var(--ink-muted);
}

[data-theme="dark"] .msg-contact-item:hover {
  background: #231c3a;
}

[data-theme="dark"] .msg-contact-item.is-active {
  background: #2d1f5e;
}

[data-theme="dark"] .msg-contact-name {
  color: var(--ink);
}

[data-theme="dark"] .msg-contact-time,
[data-theme="dark"] .msg-contact-preview {
  color: var(--ink-muted);
}

[data-theme="dark"] .msg-thread-shell {
  background: #0d0a18;
}

[data-theme="dark"] .msg-thread-head {
  background: #1a1625;
  border-bottom-color: var(--border);
}

[data-theme="dark"] .msg-peer-info strong {
  color: var(--ink);
}

[data-theme="dark"] .msg-bubble-in {
  background: #231c3a;
  color: var(--ink);
  box-shadow: none;
}

[data-theme="dark"] .msg-bubble-out {
  background: var(--accent);
  color: #0d0a18;
}

[data-theme="dark"] .msg-day-divider span {
  background: #1a1625;
  border-color: var(--border);
  color: var(--ink-muted);
}

[data-theme="dark"] .msg-input-bar {
  background: #1a1625;
  border-top-color: var(--border);
}

[data-theme="dark"] .msg-text-input {
  background: #120e1d;
  border-color: var(--border);
  color: var(--ink);
}

[data-theme="dark"] .msg-attach-btn,
[data-theme="dark"] .msg-emoji-btn {
  color: var(--ink-muted);
}

[data-theme="dark"] .msg-attach-btn:hover,
[data-theme="dark"] .msg-emoji-btn:hover {
  color: var(--accent);
  background: #2d1f5e;
}

[data-theme="dark"] .chat-no-select {
  background: #0d0a18;
}

/* ── Auth intro gradient ── */
[data-theme="dark"] .auth-intro {
  background:
    radial-gradient(circle at top left, rgba(167,139,250,0.1), transparent 32%),
    linear-gradient(145deg, #1a1625, #120e1d);
}

[data-theme="dark"] .auth-card {
  background: #1a1625;
}

[data-theme="dark"] .eyebrow {
  background: rgba(167,139,250,0.12);
  color: var(--ink-muted);
}

[data-theme="dark"] .auth-points article {
  background: rgba(255,255,255,0.04);
}

[data-theme="dark"] .auth-instructions {
  background: rgba(167,139,250,0.08);
  border: 1px solid rgba(167,139,250,0.12);
}

[data-theme="dark"] .auth-message-success {
  color: #86efac;
}

[data-theme="dark"] .auth-message-error {
  color: #fca5a5;
}

/* ── Role switcher buttons ── */
[data-theme="dark"] .role-btn {
  background: #231c3a;
  color: var(--ink-soft);
  border-color: var(--border);
}

[data-theme="dark"] .role-btn.is-active {
  background: var(--primary);
  color: var(--accent);
  border-color: var(--accent);
}

/* ── Select dropdowns ── */
[data-theme="dark"] select.auth-input,
[data-theme="dark"] select.filter-input {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23a78bfa' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-color: #120e1d;
}

[data-theme="dark"] select.auth-input:hover,
[data-theme="dark"] select.filter-input:hover {
  background-color: #1a1625;
  border-color: var(--accent);
}

/* ── Notification / inbox ── */
[data-theme="dark"] .notification-card {
  background: #1f1830;
  border-color: var(--border);
  color: var(--ink);
}

[data-theme="dark"] .notification-card p {
  color: var(--ink-soft);
}

[data-theme="dark"] .notification-status {
  color: var(--ink-muted);
}

/* ── Friend cards (frc / fc) ── */
[data-theme="dark"] .frc,
[data-theme="dark"] .fc {
  background: #1f1830;
  border-color: var(--border);
}

[data-theme="dark"] .frc-name,
[data-theme="dark"] .fc-name {
  color: var(--ink);
}

[data-theme="dark"] .frc-no {
  background: #231c3a;
  border-color: var(--border);
  color: var(--ink-soft);
}

/* ── MCI (message contact items) ── */
[data-theme="dark"] .mci:hover,
[data-theme="dark"] .mci.active {
  background: rgba(167,139,250,0.08);
}

[data-theme="dark"] .mci-name {
  color: var(--ink);
}

[data-theme="dark"] .mci-prev {
  color: var(--ink-muted);
}

/* ── Message bubbles (mb) ── */
[data-theme="dark"] .mb-in {
  background: #231c3a;
  color: var(--ink);
}

/* ── Chat contact card ── */
[data-theme="dark"] .chat-contact-card {
  background: #1f1830;
  border: 1px solid var(--border);
}

[data-theme="dark"] .chat-filter-chip {
  background: #231c3a;
  color: var(--ink);
}

[data-theme="dark"] .chat-page-messages {
  background: #120e1d;
}

[data-theme="dark"] .chat-messages {
  background: #120e1d;
}

[data-theme="dark"] .chat-day-pill {
  background: #231c3a;
  color: var(--ink-muted);
}

/* ── Bar chart ── */
[data-theme="dark"] .bar {
  background: #2d1f5e;
}

/* ── Org chips ── */
[data-theme="dark"] .pf-org-chip {
  background: #1f1830;
  border-color: var(--border);
}

/* ── Community rank ── */
[data-theme="dark"] .community-rank {
  background: #231c3a;
  color: var(--ink-soft);
}

/* ── Project code ── */
[data-theme="dark"] .code-editor {
  background: #0d0a18;
  color: #e2d9f3;
}

/* ── Profile tab ── */
[data-theme="dark"] .profile-tab {
  background: #231c3a;
  color: var(--ink-soft);
}

[data-theme="dark"] .profile-tab.is-active {
  background: var(--primary);
  color: var(--accent);
}

/* ── Tab / interest chip active ── */
[data-theme="dark"] .tab-button.is-active,
[data-theme="dark"] .interest-chip.is-active {
  background: var(--primary);
  color: var(--accent);
}

/* ── Post menu ── */
[data-theme="dark"] .post-menu {
  background: #231c3a;
  border-color: var(--border);
}

[data-theme="dark"] .post-menu button {
  background: transparent;
  color: var(--ink);
}

[data-theme="dark"] .post-menu button:hover {
  background: var(--primary);
}

/* ── PWs (projects workspace) ── */
[data-theme="dark"] .pw-kanban-panel {
  background: #1a1625;
  border-color: var(--border);
}

[data-theme="dark"] .pw-ai-card,
[data-theme="dark"] .pw-files-card {
  background: #1a1625;
  border-color: var(--border);
}

[data-theme="dark"] .pw-ai-msg-in {
  background: #231c3a;
  border-color: var(--border);
  color: var(--ink-soft);
}

[data-theme="dark"] .pw-col-count {
  background: #231c3a;
  border-color: var(--border);
}

[data-theme="dark"] .pw-count-active {
  background: var(--primary);
  color: var(--accent);
}

[data-theme="dark"] .pw-count-done {
  background: #14532d;
  color: #86efac;
}

[data-theme="dark"] .pw-done-check {
  background: #14532d;
  color: #86efac;
}

[data-theme="dark"] .pw-avatar-muted {
  background: #231c3a;
  border-color: var(--border);
}

[data-theme="dark"] .pw-upload-btn {
  background: #1f1830 !important;
  border-color: var(--border) !important;
  color: var(--ink-muted) !important;
}

/* ── Stat cards header rows ── */
[data-theme="dark"] .pf-stats-row {
  border-bottom-color: var(--border);
}

[data-theme="dark"] .pf-hero-meta-row {
  border-top-color: var(--border);
}

/* ── Image editor overlay ── */
[data-theme="dark"] #imgEditorBox {
  background: #1a1625;
  border-color: var(--border);
}

[data-theme="dark"] #imgEditorWrap {
  border-color: var(--border);
}

/* ── Inline badges / status ── */
[data-theme="dark"] .generated-post.project::after {
  background: #1f1830;
  border-color: rgba(167,139,250,0.15);
  color: var(--accent);
}

[data-theme="dark"] .generated-post.project::before {
  background: #14532d;
  color: #86efac;
}

[data-theme="dark"] .events-preferences,
[data-theme="dark"] .event-card-full,
[data-theme="dark"] .event-detail-side,
[data-theme="dark"] .event-participants-card {
  background: #1a1625;
  border-color: var(--border);
}

[data-theme="dark"] .event-rail-item,
[data-theme="dark"] .event-participant {
  background: #211a33;
  border-color: #3b3156;
}

[data-theme="dark"] .event-rail-item strong,
[data-theme="dark"] .event-card-body h3,
[data-theme="dark"] .event-detail-main h1,
[data-theme="dark"] .event-detail-meta strong {
  color: var(--text);
}

[data-theme="dark"] .event-rail-item span,
[data-theme="dark"] .event-meta-grid,
[data-theme="dark"] .event-detail-main p,
[data-theme="dark"] .event-detail-meta span {
  color: var(--text-muted);
}

/* ── Topbar online counter ── */
[data-theme="dark"] .online-counter {
  background: #231c3a;
}

/* ── Dialog / modal ── */
[data-theme="dark"] .inbox-dialog,
[data-theme="dark"] .post-dialog,
[data-theme="dark"] .chat-dialog,
[data-theme="dark"] .profile-settings-dialog {
  background: #1a1625;
  border-color: var(--border);
}

[data-theme="dark"] .inbox-backdrop {
  background: rgba(0,0,0,0.6);
}

/* ── Section view top ── */
[data-theme="dark"] .section-view {
  background: #1a1625;
}

/* ── Feed filter label ── */
[data-theme="dark"] .feed-filter-label {
  color: var(--ink-muted);
}

/* ── Auth screen background ── */
[data-theme="dark"] .auth-screen {
  background: transparent;
}

/* ── Password strength ── */
[data-theme="dark"] .password-strength {
  background: #2d1f5e;
}

/* ── Success reg icon ── */
[data-theme="dark"] .reg-success-icon {
  background: #14532d;
  color: #86efac;
}

/* ── Verify demo notice ── */
[data-theme="dark"] .verify-demo-notice {
  background: linear-gradient(135deg, #1a1f3a, #1a2640);
  border-color: #3b5998;
}

[data-theme="dark"] .verify-demo-label {
  color: #93c5fd;
}

[data-theme="dark"] .verify-demo-hint {
  color: var(--ink-muted);
}

/* ── Stepper ── */
[data-theme="dark"] .reg-step-num {
  background: #231c3a;
  color: var(--ink-muted);
}

[data-theme="dark"] .reg-step-line {
  background: #2e2549;
}

[data-theme="dark"] .reg-step-line.reg-line-done {
  background: var(--accent);
}

/* ── Theme toggle ── */
[data-theme="dark"] .theme-toggle-button {
  background: #231c3a;
  border-color: var(--border);
}

[data-theme="dark"] .theme-toggle-button:hover {
  background: var(--primary);
}

/* ── Inbox preview ── */
[data-theme="dark"] .inbox-preview {
  background: #1f1830;
  border-color: var(--border);
}

[data-theme="dark"] .inbox-preview span {
  color: var(--ink-muted);
}

[data-theme="dark"] .campus-community-row {
  background: #1f1830;
  border-color: var(--border);
}

[data-theme="dark"] .campus-community-copy strong {
  color: var(--ink);
}

[data-theme="dark"] .campus-community-copy em {
  color: var(--ink-muted);
}

/* ── friend suggestion card ── */
[data-theme="dark"] .friend-suggestion-card {
  background: #1f1830;
  border-color: var(--border);
}

/* ── Network chip (already defined but adding hover) ── */
[data-theme="dark"] .network-chip:hover {
  background: #2d1f5e;
  border-color: var(--accent);
}

/* ============================================================
   DESIGN IMPROVEMENTS — both themes
   ============================================================ */

/* Smoother focus ring on all interactive elements */
.auth-input:focus,
.filter-input:focus,
.msg-text-input:focus,
.msg-search-input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(124,58,237,0.1);
  transition: border-color 0.18s, box-shadow 0.18s;
}

/* Better button press state */
.primary-button:active,
.ghost-button:active {
  transform: translateY(0) scale(0.98);
}

/* Subtle card hover effect */
.pw-project-card,
.network-tile,
.pf-internship-row,
.community-card {
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.pw-project-card:hover,
.network-tile:hover {
  transform: translateY(-3px);
}

/* Social post hover */
.social-post {
  transition: box-shadow 0.2s ease, border-color 0.2s ease;
}
.social-post:hover {
  border-color: rgba(124,58,237,0.18);
}
[data-theme="dark"] .social-post:hover {
  border-color: rgba(167,139,250,0.25);
}

/* Better scrollbar in dark mode */
[data-theme="dark"] ::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
[data-theme="dark"] ::-webkit-scrollbar-track {
  background: #0d0a18;
}
[data-theme="dark"] ::-webkit-scrollbar-thumb {
  background: #2d1f5e;
  border-radius: 999px;
}
[data-theme="dark"] ::-webkit-scrollbar-thumb:hover {
  background: var(--accent);
}

/* Light mode scrollbar */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
::-webkit-scrollbar-track {
  background: #f0ebff;
}
::-webkit-scrollbar-thumb {
  background: #c4b5fd;
  border-radius: 999px;
}
::-webkit-scrollbar-thumb:hover {
  background: var(--accent);
}

/* Menu item transition */
.menu-item {
  transition: background 0.15s ease, color 0.15s ease, transform 0.15s ease;
}

/* Avatar gradient improvements */
.social-post-avatar {
  background: linear-gradient(135deg, #7c3aed, #4f46e5);
}
[data-theme="dark"] .social-post-avatar {
  background: linear-gradient(135deg, #6d28d9, #4338ca);
}

/* Better topbar brand in dark */
[data-theme="dark"] .brand-wordmark {
  color: var(--accent);
}

/* Improved tag bg in dark */
[data-theme="dark"] .thread-tags span {
  background: #2d1f5e;
  color: #c4b5fd;
}

/* Improve interest tags */
[data-theme="dark"] .pf-interest-tag {
  background: #2d1f5e;
  color: #c4b5fd;
}
[data-theme="dark"] .pf-interest-tag:hover {
  background: var(--accent);
  color: #0d0a18;
}

/* Smooth page shell transition */
.page-shell {
  transition: none;
}

/* Better auth form in dark */
[data-theme="dark"] .auth-field span {
  color: var(--ink-soft);
}

/* Quick overview articles */
[data-theme="dark"] .overview-grid article {
  background: rgba(255,255,255,0.03);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
}

/* Sidebar task toggle */
[data-theme="dark"] .sidebar-task-toggle {
  border-color: rgba(167,139,250,0.3);
}

/* Progress track bg */
[data-theme="dark"] .pf-progress-track {
  background: #2d1f5e;
}

[data-theme="dark"] .pf-cv-preview {
  background: linear-gradient(180deg, #1f1830, #1a1625);
  border-color: rgba(167,139,250,0.12);
}

/* Profile settings identity section */
[data-theme="dark"] .profile-settings-identity {
  background: #1f1830;
  border-radius: 16px;
}

/* Better like button */
[data-theme="dark"] .social-actions button {
  background: #231c3a;
  border-color: var(--border);
  color: var(--ink-soft);
}

[data-theme="dark"] .social-actions button:hover {
  background: #2d1f5e;
  color: var(--accent);
}

/* Lang badge */
[data-theme="dark"] .pf-lang-badge {
  background: #2d1f5e;
  color: var(--accent);
}

/* Live delta */
[data-theme="dark"] .pf-delta-positive {
  color: #86efac;
}

[data-theme="dark"] .pf-delta-warn {
  color: #fcd34d;
}

/* Network score */
[data-theme="dark"] .network-score {
  color: #c4b5fd;
}

/* Network entity icon */
[data-theme="dark"] .network-entity-icon {
  background: rgba(167,139,250,0.12);
  color: #c4b5fd;
}

/* Stat delta pos in projects */
[data-theme="dark"] .pw-stat-delta-pos {
  color: #86efac;
}

/* AI status */
[data-theme="dark"] .pw-ai-status {
  color: #86efac;
}

/* Unified rounded-square icons and avatars */
.avatar,
.social-post-avatar,
.frc-av,
.fc-av,
.mci-av,
.notif-avatar,
.msg-contact-avatar,
.msg-peer-avatar,
.msg-bubble-avatar,
.chat-message-avatar,
.network-person-avatar,
.pf-avatar,
.pf-cv-avatar,
.pw-team-avatar,
.pw-card-avatar,
#upModalAvatar {
  border-radius: 16px !important;
  overflow: hidden;
}

.avatar.large,
.pf-avatar.large,
.pf-cv-avatar,
#upModalAvatar {
  border-radius: 22px !important;
}

.avatar.has-photo,
.social-post-avatar.has-photo,
.frc-av.has-photo,
.fc-av.has-photo,
.mci-av.has-photo,
.notif-avatar.has-photo,
.msg-contact-avatar.has-photo,
.msg-peer-avatar.has-photo,
.network-person-avatar.has-photo,
.pf-avatar.has-photo,
.pf-cv-avatar.has-photo,
#upModalAvatar.has-photo {
  position: relative;
  background-image: none !important;
  color: transparent !important;
  font-size: 0 !important;
}

.avatar.has-photo > img,
.social-post-avatar.has-photo > img,
.frc-av.has-photo > img,
.fc-av.has-photo > img,
.mci-av.has-photo > img,
.notif-avatar.has-photo > img,
.msg-contact-avatar.has-photo > img,
.msg-peer-avatar.has-photo > img,
.network-person-avatar.has-photo > img,
.pf-avatar.has-photo > img,
.pf-cv-avatar.has-photo > img,
#upModalAvatar.has-photo > img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
  border-radius: inherit;
}

/* ============================================================
   Sprint 5 realtime integration
   ============================================================ */
.prj-presence-bar {
  display: flex;
  align-items: center;
  margin-top: 8px;
  min-height: 32px;
}

.rt-connection-indicator {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  font-weight: 600;
  color: var(--text-muted, #9ca3af);
}

.prj-task-card[style*="animation"] {
  animation: prjPostIn .25s ease forwards;
}

.prj-post-comments-area .rt-typing-indicator {
  padding: 0 4px;
  margin-bottom: 2px;
}

#chatTypingRT {
  padding: 4px 20px;
  font-size: 12px;
  color: var(--text-muted, #9ca3af);
  font-style: italic;
}

/* Publication and network profile logos */
.workspace-composer-card .avatar,
.composer-top-feed .avatar,
.social-post .social-post-avatar,
.network-tile .network-person-avatar {
  width: 64px !important;
  height: 64px !important;
  flex: 0 0 64px !important;
  min-width: 64px !important;
  border-radius: 18px !important;
  font-size: 18px !important;
  line-height: 1 !important;
}

.social-post .social-post-avatar.has-photo > img,
.network-tile .network-person-avatar.has-photo > img,
.workspace-composer-card .avatar.has-photo > img,
.composer-top-feed .avatar.has-photo > img {
  width: 100% !important;
  height: 100% !important;
  object-fit: cover !important;
  object-position: center !important;
  border-radius: inherit !important;
}

.network-tile-head,
.social-post-author,
.composer-top-feed {
  align-items: center !important;
}

/* Profile avatar sizing */
.pf-hero-card .pf-avatar.large,
.profile-panel-card #profileAvatar,
.public-profile-view #upModalAvatar {
  width: 96px !important;
  height: 96px !important;
  flex: 0 0 96px !important;
  min-width: 96px !important;
  border-radius: 22px !important;
  font-size: 26px !important;
}

.profile-settings-identity #profileSettingsAvatar,
.profile-photo-stack #profileAvatar,
.profile-settings-avatar {
  width: 72px !important;
  height: 72px !important;
  flex: 0 0 72px !important;
  min-width: 72px !important;
  border-radius: 20px !important;
  font-size: 20px !important;
}

.pf-hero-card .pf-avatar.large.has-photo > img,
.profile-panel-card #profileAvatar.has-photo > img,
.public-profile-view #upModalAvatar.has-photo > img,
.profile-settings-identity #profileSettingsAvatar.has-photo > img,
.profile-photo-stack #profileAvatar.has-photo > img {
  width: 100% !important;
  height: 100% !important;
  object-fit: cover !important;
  object-position: center !important;
  border-radius: inherit !important;
}

@media (max-width: 640px) {
  .workspace-composer-card .avatar,
  .composer-top-feed .avatar,
  .social-post .social-post-avatar,
  .network-tile .network-person-avatar {
    width: 56px !important;
    height: 56px !important;
    flex-basis: 56px !important;
    min-width: 56px !important;
    border-radius: 16px !important;
    font-size: 16px !important;
  }

  .pf-hero-card .pf-avatar.large,
  .profile-panel-card #profileAvatar,
  .public-profile-view #upModalAvatar {
    width: 80px !important;
    height: 80px !important;
    flex-basis: 80px !important;
    min-width: 80px !important;
    border-radius: 20px !important;
    font-size: 22px !important;
  }

  .profile-settings-identity #profileSettingsAvatar,
  .profile-photo-stack #profileAvatar {
    width: 64px !important;
    height: 64px !important;
    flex-basis: 64px !important;
    min-width: 64px !important;
    border-radius: 18px !important;
    font-size: 18px !important;
  }
}

/* ============================================================
   PROFESSIONAL UI POLISH — final override layer
   ============================================================ */

body {
  background:
    radial-gradient(circle at top left, rgba(124, 58, 237, 0.07), transparent 28rem),
    linear-gradient(180deg, #fbfbfd 0%, #f6f3fb 100%);
}

.topbar,
.panel,
.composer-feed-card,
.feed-search-shell-feed,
.feed-rail-card,
.generated-post,
.social-post,
.section-view {
  box-shadow: var(--shadow-soft);
}

.topbar {
  border-radius: 20px;
  padding: 14px 18px;
}

.brand-wordmark {
  letter-spacing: 0;
}

.brand-wordmark::after {
  content: "";
  display: block;
  width: 34px;
  height: 3px;
  margin-top: 7px;
  border-radius: 999px;
  background: linear-gradient(90deg, var(--accent), #14b8a6);
}

.app-layout {
  grid-template-columns: 260px minmax(0, 1fr);
}

.app-menu,
.sidebar-tasks-panel,
.composer-feed-card,
.feed-search-shell-feed,
.social-post {
  border-color: color-mix(in srgb, var(--border) 76%, var(--accent));
}

.menu-item {
  display: flex;
  align-items: center;
  min-height: 46px;
  border: 1px solid transparent;
  border-radius: 14px;
}

.menu-item:hover {
  background: var(--hover);
  border-color: var(--border);
}

.menu-item.is-active {
  border-color: rgba(124, 58, 237, 0.18);
}

.composer-feed-card {
  padding: 20px;
}

.composer-trigger,
.filter-input {
  min-height: 54px;
  border-width: 1.5px;
}

.composer-trigger {
  color: var(--ink-muted);
}

.composer-tool-button,
.theme-toggle-button,
.social-post-actions button,
.like-btn,
.cmt-btn {
  min-height: 42px;
}

.composer-tool-button {
  width: 52px;
  justify-content: center;
  padding-inline: 0;
  font-size: 1.2rem;
}

.primary-button,
.ghost-button,
.theme-toggle-button,
.composer-tool-button,
.like-btn,
.cmt-btn {
  border-radius: 14px;
}

.primary-button {
  box-shadow: 0 10px 24px rgba(124, 58, 237, 0.22);
}

.feed-search-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 14px;
  align-items: center;
}

.feed-filter-button {
  min-width: 126px;
}

.social-post {
  gap: 18px;
  padding: 22px;
  border-radius: 18px;
  background: var(--surface);
}

.social-post:hover {
  transform: translateY(-2px);
}

.social-post-header {
  margin-bottom: 0 !important;
}

.social-post-avatar {
  border-radius: 14px !important;
  box-shadow: 0 10px 22px rgba(124, 58, 237, 0.18);
}

.post-author-name,
.social-post-name,
.generated-post .thread-head strong {
  color: var(--text) !important;
}

.social-post-time,
.generated-post .thread-head p {
  color: var(--text-muted) !important;
}

.social-post-content p,
.generated-post .thread-text,
.thread-text {
  color: var(--text) !important;
}

.social-post-actions {
  border-top-color: color-mix(in srgb, var(--border) 84%, var(--accent)) !important;
}

.like-btn,
.cmt-btn,
.social-post-actions .ghost-button {
  background: var(--surface-strong) !important;
  border-color: var(--border) !important;
  color: var(--text-muted) !important;
}

.like-btn:hover,
.cmt-btn:hover,
.social-post-actions .ghost-button:hover {
  background: var(--primary-light) !important;
  border-color: color-mix(in srgb, var(--accent) 45%, var(--border)) !important;
  color: var(--accent) !important;
}

.like-btn.liked {
  background: rgba(124, 58, 237, 0.1) !important;
  color: var(--accent) !important;
}

.social-post-image {
  border-radius: 14px;
  border-color: var(--border);
  background: var(--surface-muted);
}

.del-btn {
  border: 1px solid var(--border) !important;
  background: var(--surface-strong) !important;
}

.online-counter,
.user-pill,
.theme-toggle-button {
  border: 1px solid var(--border);
}

.theme-toggle-button {
  display: inline-grid;
  place-items: center;
  width: 46px;
  height: 46px;
  background: var(--surface);
  color: var(--ink);
  transition: transform .2s ease, background-color .2s ease, border-color .2s ease;
}

input::placeholder,
textarea::placeholder {
  color: var(--ink-muted);
  opacity: 1;
}

[data-theme="dark"] body {
  background:
    radial-gradient(circle at top left, rgba(167, 139, 250, 0.12), transparent 30rem),
    linear-gradient(180deg, #0f0b1a 0%, #090712 100%);
}

[data-theme="dark"] .topbar,
[data-theme="dark"] .panel,
[data-theme="dark"] .composer-feed-card,
[data-theme="dark"] .feed-search-shell-feed,
[data-theme="dark"] .feed-rail-card,
[data-theme="dark"] .generated-post,
[data-theme="dark"] .social-post,
[data-theme="dark"] .section-view {
  background: rgba(26, 22, 37, 0.94);
  border-color: #3b3156;
}

[data-theme="dark"] .app-menu,
[data-theme="dark"] .sidebar-tasks-panel {
  background: rgba(26, 22, 37, 0.94);
}

[data-theme="dark"] .composer-trigger,
[data-theme="dark"] .filter-input,
[data-theme="dark"] .auth-input,
[data-theme="dark"] input,
[data-theme="dark"] select,
[data-theme="dark"] textarea {
  background: #120f1d;
  border-color: #3b3156;
  color: var(--text);
}

[data-theme="dark"] .composer-trigger,
[data-theme="dark"] .filter-input::placeholder,
[data-theme="dark"] input::placeholder,
[data-theme="dark"] textarea::placeholder {
  color: var(--text-muted);
}

[data-theme="dark"] .like-btn,
[data-theme="dark"] .cmt-btn,
[data-theme="dark"] .social-post-actions .ghost-button,
[data-theme="dark"] .theme-toggle-button,
[data-theme="dark"] .online-counter,
[data-theme="dark"] .user-pill {
  background: #211a33 !important;
  border-color: #3b3156 !important;
  color: var(--text-muted) !important;
}

[data-theme="dark"] .like-btn:hover,
[data-theme="dark"] .cmt-btn:hover,
[data-theme="dark"] .theme-toggle-button:hover {
  background: #2d2149 !important;
  color: var(--accent) !important;
}

[data-theme="dark"] .like-btn.liked {
  background: rgba(167, 139, 250, 0.16) !important;
  border-color: rgba(167, 139, 250, 0.5) !important;
  color: var(--accent) !important;
}

[data-theme="dark"] .post-author-name,
[data-theme="dark"] .social-post-name,
[data-theme="dark"] .social-post-content p,
[data-theme="dark"] .thread-text,
[data-theme="dark"] .generated-post .thread-text,
[data-theme="dark"] .generated-post .thread-head strong,
[data-theme="dark"] .frc-name,
[data-theme="dark"] .fc-name,
[data-theme="dark"] .mci-name,
[data-theme="dark"] .notif-text {
  color: var(--text) !important;
}

[data-theme="dark"] .social-post-time,
[data-theme="dark"] .generated-post .thread-head p,
[data-theme="dark"] .mci-prev,
[data-theme="dark"] .notif-time,
[data-theme="dark"] .frc-time {
  color: var(--text-muted) !important;
}

[data-theme="dark"] .del-btn {
  background: transparent !important;
  color: var(--text-muted) !important;
}

[data-theme="dark"] .del-btn:hover {
  background: rgba(251, 113, 133, 0.14) !important;
  color: #fb7185 !important;
}

@media (max-width: 980px) {
  .app-layout,
  .workspace-grid-feed {
    grid-template-columns: 1fr;
  }

  .workspace-sidebar {
    position: static;
  }
}

@media (max-width: 640px) {
  .page-shell {
    width: min(100% - 20px, 1380px);
    margin-top: 12px;
  }

  .topbar {
    border-radius: 16px;
    flex-wrap: wrap;
  }

  .topbar-status {
    order: 3;
    width: 100%;
  }

  .feed-search-row {
    grid-template-columns: 1fr;
  }

  .feed-filter-button,
  .composer-publish-button {
    width: 100%;
  }

  .social-post {
    padding: 18px;
  }
}

/* Final avatar shape override */
.avatar,
.social-post-avatar,
.frc-av,
.fc-av,
.mci-av,
.notif-avatar,
.msg-contact-avatar,
.msg-peer-avatar,
.msg-bubble-avatar,
.chat-message-avatar,
.network-person-avatar,
.pf-avatar,
.pf-cv-avatar,
.pw-team-avatar,
.pw-card-avatar,
#upModalAvatar {
  border-radius: 16px !important;
  overflow: hidden;
}

.avatar.large,
.pf-avatar.large,
.pf-cv-avatar,
#upModalAvatar {
  border-radius: 22px !important;
}

.avatar.has-photo,
.social-post-avatar.has-photo,
.frc-av.has-photo,
.fc-av.has-photo,
.mci-av.has-photo,
.notif-avatar.has-photo,
.msg-contact-avatar.has-photo,
.msg-peer-avatar.has-photo,
.network-person-avatar.has-photo,
.pf-avatar.has-photo,
.pf-cv-avatar.has-photo,
#upModalAvatar.has-photo {
  position: relative;
  background-image: none !important;
  color: transparent !important;
  font-size: 0 !important;
}

.avatar.has-photo > img,
.social-post-avatar.has-photo > img,
.frc-av.has-photo > img,
.fc-av.has-photo > img,
.mci-av.has-photo > img,
.notif-avatar.has-photo > img,
.msg-contact-avatar.has-photo > img,
.msg-peer-avatar.has-photo > img,
.network-person-avatar.has-photo > img,
.pf-avatar.has-photo > img,
.pf-cv-avatar.has-photo > img,
#upModalAvatar.has-photo > img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
  border-radius: inherit;
}

/* ============================================================
   UPF — Unified Profile System
   Covers: clientProfileView, profileView, publicProfileView
   ============================================================ */

/* ── Root layout ─────────────────────────────────────────── */
.upf-root {
  display: flex;
  flex-direction: column;
  gap: 0;
  width: 100%;
  animation: viewEnter .22s cubic-bezier(.4,0,.2,1) both;
}

/* ── Back bar ─────────────────────────────────────────────── */
.upf-back-bar {
  display: flex;
  align-items: center;
  margin-bottom: 0;
  padding-bottom: 12px;
}
.upf-back-btn {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  font-weight: 600;
  color: var(--text-muted, #6b7280);
  background: none;
  border: none;
  cursor: pointer;
  padding: 6px 0;
  transition: color .15s;
}
.upf-back-btn:hover { color: var(--text, #111); }

/* ── Hero block (cover + identity + stats + tabs) ─────────── */
.upf-hero {
  background: var(--surface, #fff);
  border: 1px solid var(--border, #e5e7eb);
  border-radius: 20px;
  overflow: hidden;
  margin-bottom: 18px;
}

/* Cover photo */
.upf-cover {
  height: 200px;
  background: linear-gradient(135deg, #7c3aed, #6366f1, #0ea5e9);
  position: relative;
  overflow: hidden;
}
.upf-cover img {
  width: 100%; height: 100%; object-fit: cover;
}
.upf-cover-edit-btn {
  position: absolute;
  bottom: 14px;
  right: 14px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(0,0,0,.45);
  backdrop-filter: blur(8px);
  color: #fff;
  border: 1px solid rgba(255,255,255,.2);
  border-radius: 8px;
  padding: 7px 13px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: background .15s;
}
.upf-cover-edit-btn:hover { background: rgba(0,0,0,.65); }

/* Identity row */
.upf-identity-row {
  display: flex;
  align-items: flex-end;
  gap: 16px;
  padding: 0 28px 0 28px;
  margin-top: -44px;
  flex-wrap: wrap;
}

/* Avatar */
.upf-avatar-wrap {
  position: relative;
  flex-shrink: 0;
}
.upf-avatar {
  width: 100px;
  height: 100px;
  border-radius: 20px;
  border: 4px solid var(--surface, #fff);
  background: linear-gradient(135deg, #7c3aed, #a78bfa);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: 800;
  color: #fff;
  overflow: hidden;
  position: relative;
  cursor: pointer;
  transition: transform .2s, box-shadow .2s;
  box-shadow: 0 4px 20px rgba(124,58,237,.2);
}
.upf-avatar:hover { transform: scale(1.04); }
.upf-avatar img {
  position: absolute; inset: 0;
  width: 100%; height: 100%;
  object-fit: cover;
}
.upf-verified-badge {
  position: absolute;
  bottom: 2px; right: 2px;
  width: 22px; height: 22px;
  background: #7c3aed;
  border-radius: 50%;
  border: 2px solid var(--surface, #fff);
  display: flex; align-items: center; justify-content: center;
}

/* Name block */
.upf-name-block {
  flex: 1;
  padding-top: 50px;
  min-width: 0;
}
.upf-name {
  font-size: 22px;
  font-weight: 800;
  color: var(--text, #111);
  margin: 0 0 2px;
  line-height: 1.2;
  display: flex;
  align-items: center;
  gap: 6px;
}
.upf-headline {
  font-size: 13px;
  color: var(--text-muted, #6b7280);
  margin: 0 0 8px;
  font-weight: 500;
}
.upf-meta-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}
.upf-chip {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: var(--surface-2, #f3f4f6);
  color: var(--text-muted, #6b7280);
  font-size: 12px;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 99px;
}
[data-theme="dark"] .upf-chip { background: #1e1b2e; color: #a78bfa; }

/* Actions (edit / add friend buttons) */
.upf-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-top: 50px;
  margin-left: auto;
  flex-shrink: 0;
}
.upf-btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  background: #7c3aed;
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 9px 18px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: background .15s, transform .12s;
}
.upf-btn-primary:hover { background: #6d28d9; }
.upf-btn-primary:active { transform: scale(.97); }
.upf-btn-ghost {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: none;
  border: 1.5px solid var(--border, #e5e7eb);
  border-radius: 10px;
  padding: 8px 16px;
  font-size: 13px;
  font-weight: 600;
  color: var(--text, #111);
  cursor: pointer;
  transition: border-color .15s, background .15s;
}
.upf-btn-ghost:hover { border-color: #7c3aed; color: #7c3aed; }

/* Stats bar */
.upf-stats-bar {
  display: flex;
  gap: 0;
  padding: 16px 28px;
  border-top: 1px solid var(--border, #f3f4f6);
  margin-top: 16px;
}
.upf-stat {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 1px;
  padding: 0 24px 0 0;
  border-right: 1px solid var(--border, #e5e7eb);
  margin-right: 24px;
}
.upf-stat:last-child { border-right: none; margin-right: 0; }
.upf-stat-val {
  font-size: 20px;
  font-weight: 800;
  color: var(--text, #111);
}
.upf-stat-lbl {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: var(--text-muted, #9ca3af);
}

/* Tabs */
.upf-tabs {
  display: flex;
  gap: 0;
  padding: 0 28px;
  border-top: 1px solid var(--border, #f3f4f6);
}
.upf-tab {
  background: none;
  border: none;
  border-bottom: 2.5px solid transparent;
  padding: 13px 18px;
  font-size: 13px;
  font-weight: 600;
  color: var(--text-muted, #6b7280);
  cursor: pointer;
  transition: color .15s, border-color .15s;
  margin-bottom: -1px;
}
.upf-tab:hover { color: var(--text, #111); }
.upf-tab.is-active {
  color: #7c3aed;
  border-bottom-color: #7c3aed;
}

/* ── Body: sidebar + feed ─────────────────────────────────── */
.upf-body {
  display: grid;
  grid-template-columns: 300px minmax(0, 1fr);
  gap: 18px;
  align-items: start;
}
@media (max-width: 768px) {
  .upf-body { grid-template-columns: 1fr; }
  .upf-identity-row { margin-top: -36px; padding: 0 16px; }
  .upf-stats-bar { padding: 14px 16px; flex-wrap: wrap; gap: 12px; }
  .upf-stat { border-right: none; padding-right: 0; margin-right: 0; }
  .upf-tabs { padding: 0 10px; overflow-x: auto; }
  .upf-cover { height: 140px; }
  .upf-avatar { width: 80px; height: 80px; font-size: 1.6rem; }
  .upf-name { font-size: 18px; }
  .upf-actions { padding-top: 38px; }
}

/* ── Sidebar cards ────────────────────────────────────────── */
.upf-sidebar {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.upf-card {
  background: var(--surface, #fff);
  border: 1px solid var(--border, #e5e7eb);
  border-radius: 16px;
  padding: 18px 20px;
}
.upf-card-title {
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: var(--text-muted, #9ca3af);
  margin-bottom: 10px;
}
.upf-bio {
  font-size: 13.5px;
  color: var(--text, #111);
  line-height: 1.65;
  margin: 0;
}
.upf-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}
.upf-tag {
  background: var(--surface-2, #f3f4f6);
  color: var(--text-muted, #6b7280);
  font-size: 12px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 99px;
}
[data-theme="dark"] .upf-tag { background: #1e1b2e; color: #a78bfa; }

/* Info rows */
.upf-info-row {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid var(--border, #f3f4f6);
}
.upf-info-row:last-child { border-bottom: none; }
.upf-info-icon {
  width: 28px; height: 28px;
  background: var(--surface-2, #f3f4f6);
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  color: #7c3aed;
}
.upf-info-icon svg { width: 14px; height: 14px; }
.upf-info-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .04em;
  color: var(--text-muted, #9ca3af);
  margin-bottom: 1px;
}
.upf-info-val {
  font-size: 13.5px;
  font-weight: 600;
  color: var(--text, #111);
}

/* CV progress */
.upf-cv-pct {
  font-size: 28px;
  font-weight: 800;
  color: #7c3aed;
  margin-bottom: 8px;
}
.upf-progress-track {
  height: 6px;
  background: var(--surface-2, #f3f4f6);
  border-radius: 99px;
  overflow: hidden;
}
.upf-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #7c3aed, #a78bfa);
  border-radius: 99px;
  transition: width .5s ease;
}

/* ── Feed column ─────────────────────────────────────────── */
.upf-feed {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.upf-tab-panel { display: none; }
.upf-tab-panel.is-active { display: block; }

/* ── Settings card (profileView) ──────────────────────────── */
.upf-settings-card {
  background: var(--surface, #fff);
  border: 1px solid var(--border, #e5e7eb);
  border-radius: 20px;
  padding: 32px;
}
.upf-settings-head {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 28px;
  padding-bottom: 24px;
  border-bottom: 1px solid var(--border, #f3f4f6);
}
.upf-settings-avatar-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}
.upf-settings-avatar {
  width: 80px; height: 80px;
  border-radius: 16px;
  background: linear-gradient(135deg, #7c3aed, #a78bfa);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.7rem; font-weight: 800; color: #fff;
  cursor: pointer;
  overflow: hidden; position: relative;
  transition: transform .2s;
}
.upf-settings-avatar:hover { transform: scale(1.04); }
.upf-settings-avatar img {
  position: absolute; inset: 0;
  width: 100%; height: 100%; object-fit: cover;
}
.upf-upload-label {
  font-size: 12px; font-weight: 600;
  color: var(--text-muted, #6b7280);
  background: none;
  border: 1.5px solid var(--border, #e5e7eb);
  border-radius: 8px;
  padding: 5px 12px;
  cursor: pointer;
  transition: border-color .15s;
}
.upf-upload-label:hover { border-color: #7c3aed; color: #7c3aed; }

/* ── Dark mode ────────────────────────────────────────────── */
[data-theme="dark"] .upf-hero,
[data-theme="dark"] .upf-card,
[data-theme="dark"] .upf-settings-card {
  background: var(--surface, #0f0c1a);
  border-color: #2a2540;
}
[data-theme="dark"] .upf-stats-bar,
[data-theme="dark"] .upf-tabs,
[data-theme="dark"] .upf-info-row {
  border-color: #2a2540;
}
[data-theme="dark"] .upf-info-icon { background: #1e1b2e; }
[data-theme="dark"] .upf-progress-track { background: #1e1b2e; }
[data-theme="dark"] .upf-chip { background: #1e1b2e; color: #a78bfa; }

/* ── app-view-active fix for profile views ────────────────── */
#clientProfileView.app-view-active,
#profileView.app-view-active,
#publicProfileView.app-view-active {
  display: block !important;
}