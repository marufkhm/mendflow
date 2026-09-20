const STORAGE_KEYS = {
  accounts: 'pwe-accounts',
  account: 'pwe-account',
  session: 'pwe-session',
  posts: 'pwe-posts',
  chats: 'pwe-live-chats',
  friends: 'pwe-friends',
  notifications: 'pwe-notifications',
};

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('./sw.js?v=2', { updateViaCache: 'none' }).then((registration) => {
      registration.update().catch(() => {
        // Keep the app functional even if update check fails.
      });
    }).catch(() => {
      // PWA scaffold remains optional.
    });
  });
}

const registerScreen = document.querySelector('#registerScreen');
const loginScreen = document.querySelector('#loginScreen');
const appShell = document.querySelector('#appShell');
const registerForm = document.querySelector('#registerForm');
const loginForm = document.querySelector('#loginForm');
const goToLoginButton = document.querySelector('#goToLoginButton');
const backToRegisterButton = document.querySelector('#backToRegister');
const registerMessage = document.querySelector('#registerMessage');
const loginMessage = document.querySelector('#loginMessage');
const loginIdentifierInput = loginForm.querySelector('input[name="identifier"]');

const userPill = document.querySelector('#userPill');
const topbarAvatar = document.querySelector('#topbarAvatar');
const topbarUserName = document.querySelector('#topbarUserName');
const composerAvatar = document.querySelector('#composerAvatar');
const goToOwnProfileButton = document.querySelector('#goToOwnProfileButton');
const appViews = Array.from(document.querySelectorAll('.app-view'));
const viewSwitchers = Array.from(document.querySelectorAll('[data-view-target]'));

const profileBackButton = document.querySelector('#profileBackButton');
const clientProfileBackButton = document.querySelector('#clientProfileBackButton');
const editProfileButton = document.querySelector('#editProfileButton');
const logoutButton = document.querySelector('#logoutButton');
const profilePhotoInput = document.querySelector('#profilePhotoInput');
const profileDetailsForm = document.querySelector('#profileDetailsForm');
const profileFontThemeInput = document.querySelector('#profileFontTheme');
const profileAccentColorInput = document.querySelector('#profileAccentColor');
const profileCardColorInput = document.querySelector('#profileCardColor');
const profileOrganizationInput = document.querySelector('#profileOrganization');
const profileSpecialtyInput = document.querySelector('#profileSpecialty');
const profileEducationInput = document.querySelector('#profileEducation');
const profileCountryInput = document.querySelector('#profileCountry');
const interestInputs = Array.from(document.querySelectorAll('.interest-select'));

const profileAvatar = document.querySelector('#profileAvatar');
const profileName = document.querySelector('#profileName');
const profileEmail = document.querySelector('#profileEmail');
const profileOrganizationCard = document.querySelector('#profileOrganizationCard');
const profileSpecialtyCard = document.querySelector('#profileSpecialtyCard');
const profileCountryCard = document.querySelector('#profileCountryCard');
const profileInterestsCard = document.querySelector('#profileInterestsCard');
const profileEmailCard = document.querySelector('#profileEmailCard');
const clientProfileAvatar = document.querySelector('#clientProfileAvatar');
const clientProfileName = document.querySelector('#clientProfileName');
const clientProfileEmail = document.querySelector('#clientProfileEmail');
const clientProfileOrganizationCard = document.querySelector('#clientProfileOrganizationCard');
const clientProfileSpecialtyCard = document.querySelector('#clientProfileSpecialtyCard');
const clientProfileCountryCard = document.querySelector('#clientProfileCountryCard');
const clientProfileInterestsCard = document.querySelector('#clientProfileInterestsCard');
const clientProfileEmailCard = document.querySelector('#clientProfileEmailCard');
const profileStrength = document.querySelector('#profileStrength');
const profileStrengthText = document.querySelector('#profileStrengthText');
const sidebarStrengthCard = document.querySelector('.sidebar-strength-card');
const profileTabs = Array.from(document.querySelectorAll('[data-profile-tab]'));
const profilePanels = Array.from(document.querySelectorAll('[data-profile-panel]'));
const openProfilePostButton = document.querySelector('#openProfilePostButton');
const profilePostsList = document.querySelector('#profilePostsList');
const profilePanelCard = document.querySelector('#clientProfileView .profile-panel-card');

const workspacePostsList = document.querySelector('#workspacePostsList');
const connectSearchInput = document.querySelector('#connectSearchInput');
const connectResults = document.querySelector('#connectResults');
const networkFeaturedCard = document.querySelector('#networkFeaturedCard');
const networkSideGrid = document.querySelector('#networkSideGrid');
const networkFilterButtons = Array.from(document.querySelectorAll('[data-network-filter]'));
const workspaceSearchInput = document.querySelector('#workspaceSearchInput');
const toggleFeedFiltersButton = document.querySelector('#toggleFeedFiltersButton');
const feedFilterPopover = document.querySelector('#feedFilterPopover');
const homeTypeButtons = Array.from(document.querySelectorAll('[data-main-type]'));
const homeModeButtons = Array.from(document.querySelectorAll('[data-home-mode]'));
const feedModeButtons = Array.from(document.querySelectorAll('[data-feed-mode]'));
const interestChips = Array.from(document.querySelectorAll('[data-interest]'));
const internshipQuery = document.querySelector('#internshipQuery');
const internshipScheduleFilter = document.querySelector('#internshipScheduleFilter');
const internshipEducationFilter = document.querySelector('#internshipEducationFilter');
const internshipSpecialtyFilter = document.querySelector('#internshipSpecialtyFilter');
const internshipCards = Array.from(document.querySelectorAll('.internship-card'));
const communityNameFilter = document.querySelector('#communityNameFilter');
const communityInterestFilter = document.querySelector('#communityInterestFilter');
const communitySpecialtyFilter = document.querySelector('#communitySpecialtyFilter');
const communityEducationFilter = document.querySelector('#communityEducationFilter');
const communityCards = Array.from(document.querySelectorAll('.community-card'));

const openInboxButton = document.querySelector('#openInboxButton');
const inboxModal = document.querySelector('#inboxModal');
const closeInboxButton = document.querySelector('#closeInboxButton');
const closeInboxBackdrop = document.querySelector('#closeInboxBackdrop');
const inboxPreviewList = document.querySelector('#inboxPreviewList');
const notificationList = document.querySelector('#notificationList');
const friendSuggestionsList = document.querySelector('#friendSuggestionsList');

const openChatsMenuButton = document.querySelector('#openChatsMenuButton');
const chatModal = document.querySelector('#chatModal');
const closeChatButton = document.querySelector('#closeChatButton');
const closeChatBackdrop = document.querySelector('#closeChatBackdrop');
const chatContactsList = document.querySelector('#chatContactsList');
const chatHeaderAvatar = document.querySelector('#chatHeaderAvatar');
const chatHeaderName = document.querySelector('#chatHeaderName');
const chatHeaderMeta = document.querySelector('#chatHeaderMeta');
const chatProfileButton = document.querySelector('#chatProfileButton');
const chatPreviews = Array.from(document.querySelectorAll('[data-open-chat]'));
const liveChatMessages = document.querySelector('#liveChatMessages');
const liveChatForm = document.querySelector('#liveChatForm');
const liveChatInput = document.querySelector('#liveChatInput');
const chatAttachmentInput = document.querySelector('#chatAttachmentInput');
const chatAttachmentPreview = document.querySelector('#chatAttachmentPreview');

const postModal = document.querySelector('#postModal');
const closePostBackdrop = document.querySelector('#closePostBackdrop');
const closePostButton = document.querySelector('#closePostButton');
const modalPostForm = document.querySelector('#modalPostForm');
const modalPostInput = document.querySelector('#modalPostInput');
const modalPostAttachmentInput = document.querySelector('#modalPostAttachmentInput');
const modalPostAttachmentPreview = document.querySelector('#modalPostAttachmentPreview');

const commentsModal = document.querySelector('#commentsModal');
const closeCommentsBackdrop = document.querySelector('#closeCommentsBackdrop');
const closeCommentsButton = document.querySelector('#closeCommentsButton');
const commentsModalList = document.querySelector('#commentsModalList');
const commentsModalForm = document.querySelector('#commentsModalForm');
const commentsModalInput = document.querySelector('#commentsModalInput');

const communityModal = document.querySelector('#communityModal');
const closeCommunityBackdrop = document.querySelector('#closeCommunityBackdrop');
const closeCommunityButton = document.querySelector('#closeCommunityButton');
const communityModalTitle = document.querySelector('#communityModalTitle');
const communityModalDescription = document.querySelector('#communityModalDescription');
const communityModalTags = document.querySelector('#communityModalTags');

const onlineCount = document.querySelector('#onlineCount');

const STUDENTS = {
  me: {
    key: 'me',
    headline: 'Student account',
  },
  tomiris: {
    key: 'tomiris',
    firstName: 'Томирис',
    lastName: 'Н.',
    organization: 'AITU',
    specialty: 'computer-science',
    education: 'bachelor',
    countryFlag: '🇰🇿',
    countryName: 'Казахстан',
    interests: ['ai', 'frontend', 'product'],
    email: 'tomiris@pwe.student',
    bio: 'Backend mentor и частый собеседник по карьерным вопросам.',
  },
  asem: {
    key: 'asem',
    firstName: 'Асем',
    lastName: 'С.',
    organization: 'NU',
    specialty: 'design',
    education: 'bachelor',
    countryFlag: '🇰🇿',
    countryName: 'Казахстан',
    interests: ['frontend', 'design', 'product'],
    email: 'asem@pwe.student',
    bio: 'Frontend friend для совместных pet-project и crit sessions.',
  },
};

const COMMUNITIES = {
  'ai-study': {
    title: 'AI Study Circle',
    description: 'Разбор моделей, mini-research groups и weekly build sessions.',
    tags: ['AI', 'Research', 'Build sessions'],
  },
  'design-jam': {
    title: 'Design Jam Almaty',
    description: 'Кейс-спринты, crit sessions и коллаборации между дизайнерами и продуктами.',
    tags: ['Design', 'Crit', 'Collaboration'],
  },
  'campus-founders': {
    title: 'Campus Founders',
    description: 'Сообщество для стартап-идей, питчей и поиска co-founders в кампусе.',
    tags: ['Startups', 'Pitching', 'Founders'],
  },
};

const NETWORK_ENTITIES = [
  {
    id: 'nu',
    type: 'universities',
    title: 'Nazarbayev University',
    subtitle: 'Ведущий исследовательский университет Центральной Азии.',
    meta: 'Астана, Казахстан',
    rating: '4.9',
    tags: ['STEM', 'Research'],
    action: 'Перейти',
  },
  {
    id: 'nis',
    type: 'schools',
    title: 'НИШ ФМН',
    subtitle: 'Интеллектуальная школа физико-математического направления.',
    meta: 'Астана',
    rating: '5.0',
    tags: ['Olympiad', 'Science'],
    action: 'Смотреть',
  },
  {
    id: 'kaspi',
    type: 'companies',
    title: 'Kaspi.kz',
    subtitle: 'Финтех-лидер, меняющий облик цифровых услуг Казахстана.',
    meta: 'Казахстан',
    rating: '4.8',
    tags: ['Fintech', 'Product'],
    action: 'Смотреть вакансии',
  },
  {
    id: 'kbtu',
    type: 'universities',
    title: 'KBTU',
    subtitle: 'Технический университет с сильной инженерной и data школой.',
    meta: 'Алматы',
    rating: '4.7',
    tags: ['Engineering', 'Data'],
    action: 'Перейти',
  },
  {
    id: 'aitu',
    type: 'universities',
    title: 'AITU',
    subtitle: 'IT университет с сильным tech-community и startup focus.',
    meta: 'Астана',
    rating: '4.9',
    tags: ['IT', 'Startup'],
    action: 'Перейти',
  },
  {
    id: 'bts',
    type: 'companies',
    title: 'BTS Digital',
    subtitle: 'Цифровая экосистема и продуктовые команды роста.',
    meta: 'Казахстан',
    rating: '4.2',
    tags: ['Digital', 'Product'],
    action: 'Смотреть вакансии',
  },
];

const DEFAULT_NOTIFICATIONS = [
  {
    id: 'notif-chat-tomiris',
    title: 'Томирис Н.',
    text: 'ответила на твой вопрос по backend roadmap',
    targetView: 'clientProfileView',
    targetStudentKey: 'tomiris',
    read: false,
  },
  {
    id: 'notif-founders',
    title: 'Campus Founders',
    text: 'тебя пригласили на pitch practice',
    targetView: 'communitiesView',
    read: false,
  },
  {
    id: 'notif-career',
    title: 'PWE Career Board',
    text: '1 вакансия совпала с твоим профилем',
    targetView: 'internshipsView',
    read: false,
  },
  {
    id: 'notif-ai-study',
    title: 'AI Study Circle',
    text: 'добавили новый open session на этой неделе',
    targetView: 'communitiesView',
    read: true,
  },
];

const DEFAULT_CHATS = {
  tomiris: [
    {
      direction: 'incoming',
      text: 'Привет! Если хочешь, я могу посмотреть твой backend roadmap.',
    },
    {
      direction: 'outgoing',
      text: 'Да, было бы супер. Особенно по стеку и first internship prep.',
    },
  ],
  founders: [
    {
      direction: 'incoming',
      text: 'Ты идешь на pitch practice в пятницу? Можем помочь собрать сторителлинг.',
    },
  ],
  career: [
    {
      direction: 'incoming',
      text: 'У тебя появился strong match на internship. Хочешь, подготовлю shortlist?',
    },
  ],
};

const CHAT_META = {
  tomiris: { label: 'Томирис Н.', meta: 'Backend mentor', studentKey: 'tomiris' },
  founders: { label: 'Campus Founders', meta: 'Startup community', studentKey: '' },
  career: { label: 'Career Board', meta: 'Internship matches', studentKey: '' },
};

const CHAT_AUTOREPLIES = {
  tomiris: ['Супер, скинь материалы и я пройдусь по ним.', 'Можешь отправить файл прямо сюда, я посмотрю.', 'Открыла твой запрос, давай соберем next steps.'],
  founders: ['Отлично, закинь deck или видео-питч сюда.', 'Подключили тебя к ближайшему practice slot.', 'Можешь прислать материалы, мы дадим фидбек до созвона.'],
  career: ['Увидел твой отклик, могу прислать еще подходящие роли.', 'Добавил тебе match по еще одной internship позиции.', 'Если хочешь, адаптируем CV под вакансию.'],
};

let activeFeedMode = 'subscriptions';
let activeInterest = 'design';
let activeMainType = 'all';
let activeHomeMode = 'recommendations';
let activeWorkspaceQuery = '';
let activeChatId = 'tomiris';
let activeProfileTab = 'posts';
let viewedStudentKey = 'me';
let activeCommentsPostId = '';
let activePostMenuId = '';
let csrfToken = '';
let remoteRegisteredUsers = [];
let activeNetworkFilter = 'all';
let serverChatCache = {};
let serverFeedItems = [];
let serverFeedPage = 1;
let serverFeedHasMore = true;
let serverFeedLoading = false;
let serverFeedInitialized = false;
let activeEditingPostId = '';
let workspaceSearchDebounceId = 0;

const getAccounts = () => {
  const raw = window.localStorage.getItem(STORAGE_KEYS.accounts);
  if (!raw) {
    const legacy = window.localStorage.getItem(STORAGE_KEYS.account);
    if (!legacy) return [];
    try {
      return [JSON.parse(legacy)];
    } catch {
      return [];
    }
  }
  try {
    return JSON.parse(raw);
  } catch {
    return [];
  }
};

const saveAccounts = (accounts) => {
  window.localStorage.setItem(STORAGE_KEYS.accounts, JSON.stringify(accounts));
};

const getSession = () => window.localStorage.getItem(STORAGE_KEYS.session);

const getAccount = () => {
  const sessionEmail = getSession();
  const accounts = getAccounts();
  if (sessionEmail) {
    return accounts.find((account) => account.email === sessionEmail) || null;
  }
  const raw = window.localStorage.getItem(STORAGE_KEYS.account);
  return raw ? JSON.parse(raw) : null;
};

const saveAccount = (account) => {
  const accounts = getAccounts();
  const nextAccounts = accounts.some((item) => item.email === account.email)
    ? accounts.map((item) => item.email === account.email ? account : item)
    : [...accounts, account];
  saveAccounts(nextAccounts);
  window.localStorage.setItem(STORAGE_KEYS.account, JSON.stringify(account));
};

const getPosts = () => {
  const raw = window.localStorage.getItem(STORAGE_KEYS.posts);
  if (!raw) return [];
  try {
    return JSON.parse(raw);
  } catch {
    return [];
  }
};

const savePosts = (posts) => {
  window.localStorage.setItem(STORAGE_KEYS.posts, JSON.stringify(posts));
};

const getFriends = () => {
  const raw = window.localStorage.getItem(STORAGE_KEYS.friends);
  if (!raw) return [];
  try {
    return JSON.parse(raw);
  } catch {
    return [];
  }
};

const saveFriends = (friends) => {
  window.localStorage.setItem(STORAGE_KEYS.friends, JSON.stringify(friends));
};

const getNotifications = () => {
  const raw = window.localStorage.getItem(STORAGE_KEYS.notifications);
  if (!raw) return structuredClone(DEFAULT_NOTIFICATIONS);
  try {
    return JSON.parse(raw);
  } catch {
    return structuredClone(DEFAULT_NOTIFICATIONS);
  }
};

const saveNotifications = (notifications) => {
  window.localStorage.setItem(STORAGE_KEYS.notifications, JSON.stringify(notifications));
};

const getChats = () => {
  const raw = window.localStorage.getItem(STORAGE_KEYS.chats);
  if (!raw) return structuredClone(DEFAULT_CHATS);
  try {
    return { ...structuredClone(DEFAULT_CHATS), ...JSON.parse(raw) };
  } catch {
    return structuredClone(DEFAULT_CHATS);
  }
};

const saveChats = (chats) => {
  window.localStorage.setItem(STORAGE_KEYS.chats, JSON.stringify(chats));
};

const getAccountChatId = (email = '') => `account:${String(email).trim().toLowerCase()}`;

const isAccountChatId = (chatId = '') => chatId.startsWith('account:');

const getChatThreadId = (chatId = '') => {
  if (!isAccountChatId(chatId)) return chatId;
  const currentEmail = String(getAccount()?.email || '').trim().toLowerCase();
  const otherEmail = chatId.replace(/^account:/, '').trim().toLowerCase();
  if (!currentEmail || !otherEmail) return chatId;
  return `dm:${[currentEmail, otherEmail].sort().join('|')}`;
};

const getRegisteredStudents = ({ excludeCurrent = false } = {}) => {
  const currentEmail = getAccount()?.email || '';
  const sourceAccounts = remoteRegisteredUsers.length > 0 ? remoteRegisteredUsers : getAccounts();
  return sourceAccounts
    .filter((account) => account?.email)
    .filter((account) => !excludeCurrent || account.email !== currentEmail)
    .map((account) => ({
      key: getAccountChatId(account.email),
      firstName: account.firstName || '',
      lastName: account.lastName || '',
      organization: account.organization || '',
      specialty: account.specialty || '',
      education: account.education || '',
      countryFlag: account.countryFlag || '',
      countryName: account.countryName || '',
      interests: account.interests || [],
      email: account.email,
      photo: account.photo || '',
      style: account.style || {},
      bio: account.organization
        ? `${account.organization}${account.specialty ? ` • ${specialtyLabel(account.specialty)}` : ''}`
        : 'Студент PWE',
    }));
};

const specialtyLabel = (value) => ({
  'computer-science': 'Computer Science',
  design: 'Design',
  business: 'Business',
}[value] || 'Пока не указано');

const educationLabel = (value) => ({
  bachelor: 'Bachelor',
  master: 'Master',
}[value] || '');

const interestLabel = (value) => ({
  design: 'Design',
  frontend: 'Frontend',
  ai: 'AI',
  product: 'Product',
  data: 'Data',
}[value] || value || '');

const networkTypeLabel = (value) => ({
  students: 'Студент года',
  schools: 'Школа',
  universities: 'Университет',
  companies: 'Компания',
}[value] || 'Сеть');

const getInitials = (name) => {
  const parts = name.trim().split(/\s+/).filter(Boolean).slice(0, 2);
  if (parts.length === 0) return 'ST';
  return parts.map((part) => part[0]).join('').toUpperCase();
};

const escapeHtml = (value) => String(value)
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;');

const applyAvatar = (element, initials, photo) => {
  if (!element) return;
  element.textContent = initials;
  if (photo) {
    element.classList.add('has-photo');
    element.style.backgroundImage = `url("${photo}")`;
  } else {
    element.classList.remove('has-photo');
    element.style.backgroundImage = '';
  }
};

const setRegisterMessage = (text, type = 'info') => {
  registerMessage.textContent = text;
  registerMessage.className = `auth-message auth-message-${type}`;
};

const setLoginMessage = (text, type = 'info') => {
  loginMessage.textContent = text;
  loginMessage.className = `auth-message auth-message-${type}`;
};

const showRegisterScreen = () => {
  registerScreen.classList.add('auth-screen-active');
  loginScreen.classList.remove('auth-screen-active');
  appShell.classList.add('is-hidden');
};

const showLoginScreen = () => {
  registerScreen.classList.remove('auth-screen-active');
  loginScreen.classList.add('auth-screen-active');
  appShell.classList.add('is-hidden');
};

const setActiveSwitcher = (viewId) => {
  viewSwitchers.forEach((button) => {
    button.classList.toggle('is-active', button.dataset.viewTarget === viewId);
  });
};

const showAppView = (viewId = 'workspaceView') => {
  registerScreen.classList.remove('auth-screen-active');
  loginScreen.classList.remove('auth-screen-active');
  appShell.classList.remove('is-hidden');
  appViews.forEach((view) => {
    view.classList.toggle('app-view-active', view.id === viewId);
  });
  setActiveSwitcher(viewId);
};

const openModal = (element) => element.classList.remove('is-hidden');
const closeModal = (element) => element.classList.add('is-hidden');

const toggleFeedFilters = () => {
  feedFilterPopover.classList.toggle('is-hidden');
};

const postJson = async (url, payload) => {
  if (window.location.protocol === 'file:') {
    throw new Error('Открой сайт через PHP-сервер, а не как file:// страницу.');
  }

  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
    },
    credentials: 'same-origin',
    body: JSON.stringify(payload),
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok || data.ok === false) {
    throw new Error(data.message || `HTTP ${response.status}: request failed`);
  }
  return data;
};

const getJson = async (url) => {
  if (window.location.protocol === 'file:') {
    throw new Error('Открой сайт через PHP-сервер, а не как file:// страницу.');
  }

  const response = await fetch(url, {
    method: 'GET',
    credentials: 'same-origin',
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok || data.ok === false) {
    throw new Error(data.message || `HTTP ${response.status}: request failed`);
  }
  return data;
};

const postFormData = async (url, formData) => {
  if (window.location.protocol === 'file:') {
    throw new Error('Открой сайт через PHP-сервер, а не как file:// страницу.');
  }

  const response = await fetch(url, {
    method: 'POST',
    headers: {
      ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
    },
    credentials: 'same-origin',
    body: formData,
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok || data.ok === false) {
    throw new Error(data.message || `HTTP ${response.status}: request failed`);
  }
  return data;
};

const mergeProfileIntoAccount = (account, profile = {}) => {
  if (!account) return account;

  return {
    ...account,
    firstName: profile.firstName || account.firstName || '',
    lastName: profile.lastName || account.lastName || '',
    email: profile.email || account.email || '',
    organization: profile.organization || '',
    specialty: profile.specialty || '',
    education: profile.education || '',
    countryValue: profile.countryValue || '',
    countryFlag: profile.countryCode || '',
    countryName: profile.countryName || '',
    interests: Array.isArray(profile.interests) ? profile.interests.slice(0, 3) : [],
    photo: profile.photo || '',
    style: {
      fontTheme: profile.style?.fontTheme || account.style?.fontTheme || 'manrope',
      accentColor: profile.style?.accentColor || account.style?.accentColor || '#e78479',
      cardColor: profile.style?.cardColor || account.style?.cardColor || '#f1e4d0',
    },
  };
};

const loadCurrentProfileFromServer = async (baseAccount = null) => {
  if (!isHostedMode()) return baseAccount;

  const data = await getJson('./api/profile/get.php');
  const fallbackAccount = baseAccount || getAccount();
  const nextAccount = mergeProfileIntoAccount(fallbackAccount, data.profile || {});
  saveAccount(nextAccount);
  hydrateUser(nextAccount);
  await syncRemoteUsers();
  return nextAccount;
};

const postForm = async (url, formData) => {
  if (window.location.protocol === 'file:') {
    throw new Error('Открой сайт через PHP-сервер, а не как file:// страницу.');
  }

  const response = await fetch(url, {
    method: 'POST',
    headers: {
      ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
    },
    credentials: 'same-origin',
    body: formData,
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok || data.ok === false) {
    throw new Error(data.message || `HTTP ${response.status}: request failed`);
  }
  return data;
};

const MAX_UPLOAD_BYTES = 7340032;
const validateFile = (file, allowedPrefixes, allowedExact = []) => {
  if (!file) return { ok: true };
  if (file.size > MAX_UPLOAD_BYTES) {
    return { ok: false, message: 'Файл больше 7 МБ. Выбери материал до 7 МБ.' };
  }
  const typeAllowed = allowedPrefixes.some((prefix) => file.type.startsWith(prefix))
    || allowedExact.includes(file.type);
  if (!typeAllowed) {
    return { ok: false, message: 'Недопустимый тип файла.' };
  }
  return { ok: true };
};

const updateRegisteredCount = async () => {
  if (window.location.protocol !== 'file:') {
    try {
      const data = await getJson('./api/auth/stats.php');
      onlineCount.textContent = new Intl.NumberFormat('ru-RU').format(data.registeredUsers || 0);
      return;
    } catch {
      // Fall back to local browser cache when backend is unavailable.
    }
  }

  onlineCount.textContent = new Intl.NumberFormat('ru-RU').format(getAccounts().length);
};

const syncRemoteUsers = async () => {
  if (window.location.protocol === 'file:') {
    remoteRegisteredUsers = [];
    return;
  }

  try {
    const data = await getJson('./api/auth/users.php');
    remoteRegisteredUsers = Array.isArray(data.users) ? data.users : [];
  } catch {
    remoteRegisteredUsers = [];
  }
};

const isHostedMode = () => window.location.protocol !== 'file:';

const getHostedAttachmentUrl = (path = '') => {
  if (!path) return '';
  if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('data:')) {
    return path;
  }
  return path.startsWith('/') ? path : `/${path}`;
};

const getRenderablePosts = () => (isHostedMode() ? serverFeedItems : getPosts().slice().reverse());

const renderFeedSkeletons = (count = 3) => Array.from({ length: count }, () => `
  <article class="thread-card panel feed-skeleton">
    <div class="skeleton-line skeleton-line-title"></div>
    <div class="skeleton-line"></div>
    <div class="skeleton-line skeleton-line-wide"></div>
  </article>
`).join('');

const mapServerPostToClient = (post) => {
  const currentEmail = String(getAccount()?.email || '').trim().toLowerCase();
  const authorEmail = String(post.author?.email || '').trim().toLowerCase();
  return {
    id: String(post.id),
    text: post.text || '',
    type: post.type || 'post',
    feedMode: post.feedMode || 'recommendations',
    interestMode: post.interestMode || ((post.tags || [])[0] || 'design'),
    tags: Array.isArray(post.tags) ? post.tags : [],
    likes: Number(post.likes || 0),
    likedByMe: Boolean(post.likedByMe),
    comments: Array.isArray(post.comments) ? post.comments : [],
    commentsCount: Number(post.commentsCount || 0),
    unreadComments: Number(post.unreadComments || 0),
    createdAt: post.createdAt || new Date().toISOString(),
    updatedAt: post.updatedAt || '',
    ownPost: Boolean(post.ownPost || (authorEmail && authorEmail === currentEmail)),
    authorKey: post.authorKey || (authorEmail && authorEmail === currentEmail ? 'me' : getAccountChatId(authorEmail)),
    authorPhoto: post.author?.photo || '',
    author: post.author || null,
    attachment: post.attachment ? {
      path: post.attachment.path || '',
      name: post.attachment.name || '',
      kind: post.attachment.kind || 'document',
      data: post.attachment.data || '',
    } : null,
    isEditing: false,
  };
};

const upsertServerPost = (incomingPost) => {
  const mappedPost = mapServerPostToClient(incomingPost);
  const existingIndex = serverFeedItems.findIndex((post) => String(post.id) === String(mappedPost.id));
  if (existingIndex >= 0) {
    serverFeedItems = serverFeedItems.map((post, index) => index === existingIndex ? mappedPost : post);
  } else {
    serverFeedItems = [mappedPost, ...serverFeedItems];
  }
};

const setServerPostComments = (postId, comments) => {
  serverFeedItems = serverFeedItems.map((post) => String(post.id) === String(postId)
    ? {
        ...post,
        comments,
        commentsCount: comments.length,
        unreadComments: 0,
      }
    : post);
};

const renderAttachmentPreview = (input, container) => {
  const [file] = input.files || [];
  if (!file) {
    container.innerHTML = '';
    return;
  }
  const reader = new FileReader();
  reader.addEventListener('load', () => {
    if (file.type.startsWith('image/')) {
      container.innerHTML = `<div class="attachment-preview"><img class="attachment-preview-thumb" src="${reader.result}" alt="${escapeHtml(file.name)}"><span>${escapeHtml(file.name)}</span></div>`;
      return;
    }
    if (file.type.startsWith('video/')) {
      container.innerHTML = `<div class="attachment-preview"><video class="attachment-preview-thumb" src="${reader.result}"></video><span>${escapeHtml(file.name)}</span></div>`;
      return;
    }
    container.innerHTML = `<div class="attachment-preview"><span>${escapeHtml(file.name)}</span></div>`;
  });
  reader.readAsDataURL(file);
};

const formatPostTime = (createdAt) => {
  const date = new Date(createdAt);
  if (Number.isNaN(date.getTime())) return 'только что';
  return new Intl.DateTimeFormat('ru-RU', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
};

const getStudentData = (studentKey = 'me') => {
  if (studentKey === 'me') {
    const account = getAccount();
    if (!account) return null;
    return {
      key: 'me',
      firstName: account.firstName,
      lastName: account.lastName,
      organization: account.organization,
      specialty: account.specialty,
      education: account.education,
      countryFlag: account.countryFlag,
      countryName: account.countryName,
      interests: account.interests || [],
      email: account.email,
      photo: account.photo || '',
      style: account.style || {},
      bio: 'Student account',
    };
  }

  if (isAccountChatId(studentKey)) {
    return getRegisteredStudents().find((student) => student.key === studentKey) || null;
  }

  return STUDENTS[studentKey] || null;
};

const getStudentName = (studentKey = 'me') => {
  const student = getStudentData(studentKey);
  return student ? `${student.firstName} ${student.lastName}`.trim() : 'Студент';
};

const getCardStyle = (student) => {
  const accent = student?.style?.accentColor || '#e78479';
  const card = student?.style?.cardColor || '#f1e4d0';
  const font = student?.style?.fontTheme || 'manrope';
  const fontFamily = font === 'fraunces'
    ? '"Fraunces", serif'
    : font === 'mono'
      ? '"SFMono-Regular", Consolas, monospace'
      : '"Manrope", sans-serif';
  return `--student-accent:${accent};--student-card:${card};--student-font:${fontFamily};`;
};

const applyProfileStyle = (student) => {
  const style = getCardStyle(student);
  [profilePanelCard, userPill].forEach((element) => {
    if (element) element.style.cssText += style;
  });
};

const calculateStrength = (account) => {
  let score = 25;
  if (account.organization) score += 15;
  if (account.specialty) score += 15;
  if (account.education) score += 10;
  if (account.countryFlag) score += 10;
  if ((account.interests || []).filter(Boolean).length > 0) score += 15;
  if (account.style?.accentColor) score += 5;
  if (account.photo) score += 5;
  return Math.min(score, 100);
};

const syncProfileForm = (account) => {
  profileOrganizationInput.value = account.organization || '';
  profileSpecialtyInput.value = account.specialty || '';
  profileEducationInput.value = account.education || '';
  profileCountryInput.value = account.countryValue || '';
  profileFontThemeInput.value = account.style?.fontTheme || 'manrope';
  profileAccentColorInput.value = account.style?.accentColor || '#e78479';
  profileCardColorInput.value = account.style?.cardColor || '#f1e4d0';
  interestInputs.forEach((input, index) => {
    input.value = account.interests?.[index] || '';
  });
};

const updateStrengthUI = (account) => {
  const score = calculateStrength(account);
  profileStrength.textContent = String(score);
  profileStrengthText.textContent = score >= 80
    ? 'Профиль выглядит сильно: у тебя уже есть учебные данные, интересы, стиль и фото.'
    : 'Добавь организацию, специальность, интересы, страну, стиль и фото, чтобы профиль стал заметнее.';
  sidebarStrengthCard.classList.toggle('is-hidden', score >= 100);
};

const renderCommentList = (comments = []) => {
  if (comments.length === 0) return '<div class="comment-empty">Пока без комментариев.</div>';
  return comments.map((comment) => `
    <div class="comment-item">
      <strong>${escapeHtml(comment.author)}</strong>
      <span>${escapeHtml(comment.text)}</span>
    </div>
  `).join('');
};

const renderPostAttachment = (attachment) => {
  if (!attachment) return '';
  if (attachment.kind === 'image') {
    return `<img class="message-attachment-image" src="${attachment.data}" alt="${escapeHtml(attachment.name)}">`;
  }
  if (attachment.kind === 'video') {
    return `<video class="message-attachment-image" controls src="${attachment.data}"></video>`;
  }
  return `<div class="attachment-pill">${escapeHtml(attachment.name)} · ${escapeHtml(attachment.kind)}</div>`;
};

const renderPostCard = (post, variant = 'home') => {
  const student = getStudentData(post.authorKey || 'me');
  const name = student ? `${student.firstName} ${student.lastName}`.trim() : 'Студент';
  const initials = getInitials(name);
  const badge = post.type === 'project' ? 'Проект' : 'Пост';
  const feedAudience = post.feedMode === 'subscriptions' ? 'Подписки' : 'Рекомендации';
  const photoStyle = post.authorPhoto || student?.photo || '';
  const attachmentMarkup = renderPostAttachment(post.attachment);
  const liked = Boolean(post.likedByMe);
  const ownPostActions = (post.ownPost || post.authorKey === 'me')
    ? `
      <div class="post-menu-wrap">
        <button type="button" class="post-more-button" data-toggle-post-menu="${escapeHtml(post.id)}">...</button>
        ${activePostMenuId === post.id ? `
          <div class="post-menu">
            <button type="button" data-edit-post="${escapeHtml(post.id)}">Редактировать</button>
            <button type="button" data-delete-post="${escapeHtml(post.id)}">Удалить</button>
          </div>
        ` : ''}
      </div>
    `
    : '';
  const unreadCommentBadge = post.unreadComments ? ` · новых ${post.unreadComments}` : '';
  const commentCount = post.commentsCount ?? post.comments?.length ?? 0;
  return `
    <article class="thread-card panel generated-post ${post.type}" data-post-id="${escapeHtml(post.id)}" data-main-type="${escapeHtml(post.type)}" data-home-mode="${escapeHtml(post.feedMode)}" data-student-mode="${escapeHtml(post.feedMode)} ${escapeHtml(post.interestMode || '')}" data-tags="${escapeHtml((post.tags || ['design']).join(' '))}">
      <div class="thread-head">
        <div class="profile-inline">
          <div class="avatar ${photoStyle ? 'has-photo' : 'muted'}" style="${photoStyle ? `background-image:url('${photoStyle}')` : ''}">${initials}</div>
          <div>
            <strong>${escapeHtml(name)}</strong>
            <p>${escapeHtml(feedAudience)} • ${formatPostTime(post.createdAt)}</p>
          </div>
        </div>
        <div class="post-head-actions">
          <span class="thread-badge ${post.type === 'project' ? 'warm' : 'soft'}">${badge}</span>
          ${ownPostActions}
        </div>
      </div>
      <p class="thread-text">${escapeHtml(post.text)}</p>
      ${attachmentMarkup}
      <div class="thread-tags">
        ${(post.tags || []).map((tag) => `<span>#${escapeHtml(interestLabel(tag).toLowerCase())}</span>`).join('')}
      </div>
      <div class="thread-footer social-actions">
        <button type="button" class="like-button ${liked ? 'is-active' : ''}" data-like-post="${escapeHtml(post.id)}">${liked ? 'Убрать лайк' : 'Лайк'} ${post.likes || 0}</button>
        <button type="button" class="comment-toggle-button" data-comment-post="${escapeHtml(post.id)}">Комментарии ${commentCount}${unreadCommentBadge}</button>
      </div>
    </article>
  `;
};

const renderProfilePosts = (studentKey) => {
  const posts = getRenderablePosts().filter((post) => (post.authorKey || 'me') === studentKey);
  if (posts.length === 0) {
    return '<div class="profile-empty-state">Пока нет публикаций в этой категории.</div>';
  }
  return posts.map((post) => renderPostCard(post, 'profile')).join('');
};

const renderProfilePanels = () => {
  const student = getStudentData(viewedStudentKey);
  if (!student) return;
  const fullName = `${student.firstName} ${student.lastName}`.trim();
  const initials = getInitials(fullName);
  const countryText = student.countryFlag ? `${student.countryFlag} ${student.countryName}` : 'Пока не указано';
  const interestsText = (student.interests || []).filter(Boolean).map(interestLabel).join(', ') || 'Пока не указано';
  const specialtyText = specialtyLabel(student.specialty);
  const educationText = educationLabel(student.education);

  clientProfileName.textContent = fullName;
  clientProfileEmail.textContent = student.email || 'student@pwe.student';
  clientProfileEmailCard.textContent = student.email || 'student@pwe.student';
  clientProfileOrganizationCard.textContent = student.organization || 'Пока не указано';
  clientProfileSpecialtyCard.textContent = educationText ? `${specialtyText} • ${educationText}` : specialtyText;
  clientProfileCountryCard.textContent = countryText;
  clientProfileInterestsCard.textContent = interestsText;
  applyAvatar(clientProfileAvatar, initials, student.photo);
  applyProfileStyle(student);
  editProfileButton.classList.toggle('is-hidden', viewedStudentKey !== 'me');
  openProfilePostButton.classList.toggle('is-hidden', viewedStudentKey !== 'me');

  document.querySelector('[data-profile-panel="posts"] #profilePostsList').innerHTML = renderProfilePosts(viewedStudentKey);
  document.querySelector('[data-profile-panel="projects"]').innerHTML = `
    <div class="profile-mini-grid">
      ${getRenderablePosts().filter((post) => (post.authorKey || 'me') === viewedStudentKey && post.type === 'project').map((post) => renderPostCard(post, 'profile')).join('') || '<div class="profile-empty-state">Пока нет опубликованных проектов.</div>'}
    </div>
  `;
};

const renderNotifications = () => {
  const notifications = getNotifications();
  const previewItems = notifications.slice(0, 3);

  inboxPreviewList.innerHTML = previewItems.map((notification) => `
    <button class="inbox-preview ${notification.read ? '' : 'is-unread'}" type="button" data-notification-id="${escapeHtml(notification.id)}">
      ${escapeHtml(notification.title)}
      <span>${escapeHtml(notification.text)}</span>
    </button>
  `).join('');

  notificationList.innerHTML = notifications.map((notification) => `
    <button class="notification-card ${notification.read ? '' : 'is-unread'}" type="button" data-notification-id="${escapeHtml(notification.id)}">
      <strong>${escapeHtml(notification.title)}</strong>
      <p>${escapeHtml(notification.text)}</p>
      <span class="notification-status">${notification.read ? 'Прочитано' : 'Новое уведомление'}</span>
    </button>
  `).join('');

  const openNotification = (notificationId) => {
    const nextNotifications = getNotifications().map((notification) => (
      notification.id === notificationId ? { ...notification, read: true } : notification
    ));
    saveNotifications(nextNotifications);
    const target = nextNotifications.find((notification) => notification.id === notificationId);
  renderNotifications();
    closeModal(inboxModal);
    if (!target) return;
    if (target.targetStudentKey) {
      viewedStudentKey = target.targetStudentKey;
      renderProfilePanels();
    }
    if (target.targetView === 'clientProfileView') {
      setActiveProfileTab('posts');
    }
    showAppView(target.targetView || 'workspaceView');
  };

  inboxPreviewList.querySelectorAll('[data-notification-id]').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.stopPropagation();
      openNotification(button.dataset.notificationId);
    });
  });

  notificationList.querySelectorAll('[data-notification-id]').forEach((button) => {
    button.addEventListener('click', () => {
      openNotification(button.dataset.notificationId);
    });
  });
};

const renderFriendSuggestions = () => {
  const account = getAccount();
  const interestSet = new Set((account?.interests || []).filter(Boolean));
  const suggestionsBase = [
    ...getRegisteredStudents({ excludeCurrent: true }),
    ...Object.values(STUDENTS).filter((student) => student.key !== 'me'),
  ];
  const suggestions = suggestionsBase
    .map((student) => ({
      ...student,
      score: (student.interests || []).filter((interest) => interestSet.has(interest)).length,
    }))
    .filter((student, index, collection) => collection.findIndex((item) => item.key === student.key) === index)
    .sort((left, right) => right.score - left.score)
    .slice(0, 3);

  friendSuggestionsList.innerHTML = suggestions.map((student) => `
    <article class="friend-suggestion-card">
      <div class="mentor-item friend-suggestion-head">
        <div class="avatar sand">${getInitials(`${student.firstName} ${student.lastName}`)}</div>
        <div class="friend-suggestion-copy">
          <strong>${escapeHtml(`${student.firstName} ${student.lastName}`)}</strong>
          <p>${escapeHtml(student.bio)}</p>
        </div>
      </div>
      <div class="thread-tags">
        ${(student.interests || []).slice(0, 3).map((interest) => `<span>#${escapeHtml(interestLabel(interest).toLowerCase())}</span>`).join('')}
      </div>
      <div class="thread-footer">
        <button type="button" class="student-profile-trigger" data-student-key="${escapeHtml(student.key)}">Профиль</button>
        <button type="button" class="open-chat-button" data-open-student-chat="${escapeHtml(student.key)}">Написать</button>
        <button type="button" class="add-friend-button" data-student-key="${escapeHtml(student.key)}">Подружиться</button>
      </div>
    </article>
  `).join('');
};

const getNetworkCategory = (student) => {
  const org = String(student.organization || '').toLowerCase();
  if (org.includes('school') || org.includes('lyceum') || org.includes('ниш')) return 'schools';
  if (org.includes('university') || org.includes('универ') || org.includes('aitu') || org.includes('nu') || org.includes('kbtu')) return 'universities';
  return 'students';
};

const getNetworkStudents = () => getRegisteredStudents({ excludeCurrent: true })
  .map((student) => ({
    ...student,
    type: getNetworkCategory(student),
    matchScore: (student.interests || []).length,
  }));

const renderNetworkTags = (tags = []) => `
  <div class="thread-tags network-tags">
    ${tags.slice(0, 3).map((tag) => `<span>${tag.startsWith('#') ? escapeHtml(tag) : `#${escapeHtml(tag)}`}</span>`).join('')}
  </div>
`;

const renderNetworkStudentCard = (student, variant = 'compact') => {
  const fullName = `${student.firstName} ${student.lastName}`.trim();
  const subtitle = student.organization
    ? `${student.organization}${student.specialty ? ` • ${specialtyLabel(student.specialty)}` : ''}`
    : (student.bio || 'Студент PWE');
  const location = student.countryName ? `${student.countryFlag ? `${student.countryFlag} ` : ''}${student.countryName}` : 'Казахстан';
  const tags = (student.interests || []).map((interest) => interestLabel(interest));

  if (variant === 'featured') {
    return `
      <div class="network-feature-layout">
        <div class="network-feature-avatar-wrap">
          <div class="network-feature-avatar ${student.photo ? 'has-photo' : ''}" style="${student.photo ? `background-image:url('${student.photo}')` : ''}">
            ${student.photo ? '' : escapeHtml(getInitials(fullName))}
          </div>
        </div>
        <div class="network-feature-copy">
          <div class="network-feature-meta">
            <span class="network-pill">${escapeHtml(networkTypeLabel('students'))}</span>
            <span>${escapeHtml(location)}</span>
          </div>
          <h3>${escapeHtml(fullName)}</h3>
          <p>${escapeHtml(subtitle)}</p>
          ${renderNetworkTags(tags)}
          <div class="network-card-actions">
            <button type="button" class="student-profile-trigger" data-student-key="${escapeHtml(student.key)}">Профиль</button>
            <button type="button" class="open-chat-button" data-open-student-chat="${escapeHtml(student.key)}">Написать</button>
            <button type="button" class="add-friend-button" data-student-key="${escapeHtml(student.key)}">Подружиться</button>
          </div>
        </div>
      </div>
    `;
  }

  return `
    <article class="panel network-card network-card-student">
      <div class="network-card-header">
        <div class="avatar sand ${student.photo ? 'has-photo' : ''}" style="${student.photo ? `background-image:url('${student.photo}')` : ''}">${escapeHtml(getInitials(fullName))}</div>
        <div>
          <strong>${escapeHtml(fullName)}</strong>
          <p>${escapeHtml(subtitle)}</p>
        </div>
      </div>
      ${renderNetworkTags(tags)}
      <div class="network-card-actions">
        <button type="button" class="student-profile-trigger" data-student-key="${escapeHtml(student.key)}">Профиль</button>
        <button type="button" class="open-chat-button" data-open-student-chat="${escapeHtml(student.key)}">Написать</button>
      </div>
    </article>
  `;
};

const renderNetworkEntityCard = (entity, variant = 'compact') => {
  if (variant === 'compact') {
    return `
      <article class="panel network-card network-card-entity">
        <div class="network-entity-icon">${escapeHtml(entity.title.slice(0, 1))}</div>
        <span class="network-rating">★ ${escapeHtml(entity.rating)}</span>
        <h3>${escapeHtml(entity.title)}</h3>
        <p>${escapeHtml(entity.subtitle)}</p>
        ${renderNetworkTags(entity.tags)}
        <button type="button" class="ghost-button wide">${escapeHtml(entity.action)}</button>
      </article>
    `;
  }

  return `
    <article class="panel network-strip-card">
      <div class="network-strip-icon">${escapeHtml(entity.title.slice(0, 1))}</div>
      <span class="network-rating">★ ${escapeHtml(entity.rating)}</span>
      <strong>${escapeHtml(entity.title)}</strong>
      <p>${escapeHtml(entity.subtitle)}</p>
    </article>
  `;
};

const renderConnectResults = () => {
  const query = connectSearchInput.value.trim().toLowerCase();
  const students = getNetworkStudents().filter((student) => {
    const haystack = `${student.firstName} ${student.lastName} ${student.email || ''} ${student.organization || ''}`.toLowerCase();
    const categoryMatch = activeNetworkFilter === 'all' || student.type === activeNetworkFilter;
    return categoryMatch && (!query || haystack.includes(query));
  });
  const entities = NETWORK_ENTITIES.filter((entity) => {
    const haystack = `${entity.title} ${entity.subtitle} ${entity.meta}`.toLowerCase();
    const categoryMatch = activeNetworkFilter === 'all' || entity.type === activeNetworkFilter;
    return categoryMatch && (!query || haystack.includes(query));
  });

  const featuredStudent = students[0] || getNetworkStudents()[0] || Object.values(STUDENTS).find((student) => student.key !== 'me');
  if (networkFeaturedCard) {
    networkFeaturedCard.innerHTML = featuredStudent
      ? renderNetworkStudentCard(featuredStudent, 'featured')
      : '<div class="profile-empty-state">Пока нет подходящих студентов для витрины.</div>';
  }

  if (networkSideGrid) {
    const sideItems = [...entities.slice(0, 2), ...students.slice(1, 2)];
    networkSideGrid.innerHTML = sideItems.map((item) => ('firstName' in item
      ? renderNetworkStudentCard(item, 'compact')
      : renderNetworkEntityCard(item, 'compact'))).join('') || '<div class="profile-empty-state">Подходящие карточки появятся здесь после регистрации студентов.</div>';
  }

  const bottomItems = [...entities.slice(2), ...students.slice(1, 5)];
  connectResults.innerHTML = bottomItems.map((item) => ('firstName' in item
    ? `<article class="panel network-strip-card network-strip-student">
        <div class="network-strip-student-head">
          <div class="avatar sand ${item.photo ? 'has-photo' : ''}" style="${item.photo ? `background-image:url('${item.photo}')` : ''}">${escapeHtml(getInitials(`${item.firstName} ${item.lastName}`))}</div>
          <div>
            <strong>${escapeHtml(`${item.firstName} ${item.lastName}`)}</strong>
            <p>${escapeHtml(item.organization || item.bio || 'Студент PWE')}</p>
          </div>
        </div>
        ${renderNetworkTags((item.interests || []).map((interest) => interestLabel(interest)))}
        <button type="button" class="primary-button wide open-chat-button" data-open-student-chat="${escapeHtml(item.key)}">Подключиться</button>
      </article>`
    : renderNetworkEntityCard(item, 'strip'))).join('') || '<div class="profile-empty-state">Пока нет подходящих карточек по этому фильтру.</div>';
};

const fetchFeed = async ({ page = 1, reset = false } = {}) => {
  if (!isHostedMode() || serverFeedLoading) return;

  serverFeedLoading = true;
  if (reset) {
    workspacePostsList.innerHTML = renderFeedSkeletons(3);
  } else {
    workspacePostsList.insertAdjacentHTML('beforeend', renderFeedSkeletons(2));
  }

  try {
    const params = new URLSearchParams({
      page: String(page),
      limit: '5',
      mode: activeHomeMode,
      type: activeMainType,
      query: activeWorkspaceQuery,
    });
    const data = await getJson(`./api/feed/list.php?${params.toString()}`);
    const items = Array.isArray(data.items) ? data.items.map(mapServerPostToClient) : [];
    serverFeedItems = reset ? items : [...serverFeedItems, ...items];
    serverFeedPage = Number(data.page || page);
    serverFeedHasMore = Boolean(data.hasMore);
    serverFeedInitialized = true;
  } catch (error) {
    workspacePostsList.innerHTML = `<div class="profile-empty-state">${escapeHtml(error.message || 'Не удалось загрузить ленту.')}</div>`;
  } finally {
    serverFeedLoading = false;
    renderPosts();
  }
};

const renderPosts = () => {
  const posts = getRenderablePosts();
  if (isHostedMode() && !serverFeedInitialized && !serverFeedLoading) {
    workspacePostsList.innerHTML = renderFeedSkeletons(3);
  } else {
    workspacePostsList.innerHTML = posts.map((post) => renderPostCard(post, 'home')).join('')
      || '<div class="profile-empty-state">Пока в ленте нет публикаций.</div>';
    if (isHostedMode() && serverFeedLoading) {
      workspacePostsList.insertAdjacentHTML('beforeend', renderFeedSkeletons(2));
    }
  }
  renderProfilePanels();
  renderNotifications();
  renderFriendSuggestions();
  renderConnectResults();
  bindDynamicInteractions();
  updateHomeFeed();
  updateStudentsFeed();
};

const bindDynamicInteractions = () => {
  document.querySelectorAll('[data-like-post]').forEach((button) => {
    button.onclick = async () => {
      if (isHostedMode()) {
        try {
          const data = await postJson('./api/feed/like.php', {
            postId: button.dataset.likePost,
          });
          serverFeedItems = serverFeedItems.map((post) => String(post.id) === String(button.dataset.likePost)
            ? {
                ...post,
                likedByMe: Boolean(data.likedByMe),
                likes: Number(data.likes || 0),
              }
            : post);
          renderPosts();
          return;
        } catch (error) {
          setLoginMessage(error.message, 'error');
          return;
        }
      }

      const posts = getPosts();
      const nextPosts = posts.map((post) => {
        if (post.id !== button.dataset.likePost) return post;
        const likedByMe = !post.likedByMe;
        return {
          ...post,
          likedByMe,
          likes: Math.max(0, (post.likes || 0) + (likedByMe ? 1 : -1)),
        };
      });
      savePosts(nextPosts);
      renderPosts();
    };
  });

  document.querySelectorAll('[data-comment-post]').forEach((button) => {
    button.onclick = async () => {
      activeCommentsPostId = button.dataset.commentPost;
      if (isHostedMode()) {
        try {
          const data = await getJson(`./api/feed/comments.php?postId=${encodeURIComponent(activeCommentsPostId)}`);
          const comments = Array.isArray(data.comments) ? data.comments : [];
          setServerPostComments(activeCommentsPostId, comments);
          commentsModalList.innerHTML = renderCommentList(comments);
          openModal(commentsModal);
          renderPosts();
          return;
        } catch (error) {
          setLoginMessage(error.message, 'error');
          return;
        }
      }

      const posts = getPosts();
      const post = posts.find((item) => item.id === activeCommentsPostId);
      const nextPosts = posts.map((item) => item.id === activeCommentsPostId ? { ...item, unreadComments: 0 } : item);
      savePosts(nextPosts);
      commentsModalList.innerHTML = renderCommentList(post?.comments || []);
      openModal(commentsModal);
      renderPosts();
    };
  });

  document.querySelectorAll('[data-delete-post]').forEach((button) => {
    button.onclick = async () => {
      if (isHostedMode()) {
        try {
          await postJson('./api/feed/delete.php', {
            postId: button.dataset.deletePost,
          });
          serverFeedItems = serverFeedItems.filter((post) => String(post.id) !== String(button.dataset.deletePost));
          if (activeCommentsPostId === button.dataset.deletePost) {
            activeCommentsPostId = '';
            closeModal(commentsModal);
          }
          renderPosts();
          return;
        } catch (error) {
          setLoginMessage(error.message, 'error');
          return;
        }
      }

      const nextPosts = getPosts().filter((post) => post.id !== button.dataset.deletePost);
      savePosts(nextPosts);
      if (activeCommentsPostId === button.dataset.deletePost) {
        activeCommentsPostId = '';
        closeModal(commentsModal);
      }
      renderPosts();
    };
  });

  document.querySelectorAll('[data-toggle-post-menu]').forEach((button) => {
    button.onclick = () => {
      activePostMenuId = activePostMenuId === button.dataset.togglePostMenu ? '' : button.dataset.togglePostMenu;
      renderPosts();
    };
  });

  document.querySelectorAll('[data-edit-post]').forEach((button) => {
    button.onclick = () => {
      const post = getRenderablePosts().find((item) => String(item.id) === String(button.dataset.editPost));
      if (!post) return;
      modalPostInput.value = post.text;
      activePostMenuId = '';
      activeEditingPostId = String(post.id);
      if (isHostedMode()) {
        serverFeedItems = serverFeedItems.map((item) => ({ ...item, isEditing: String(item.id) === activeEditingPostId }));
      } else {
        const nextPosts = getPosts().map((item) => item.id === post.id ? { ...item, isEditing: true } : { ...item, isEditing: false });
        savePosts(nextPosts);
      }
      modalPostAttachmentPreview.innerHTML = post.attachment ? renderPostAttachment(post.attachment) : '';
      openModal(postModal);
    };
  });

  document.querySelectorAll('.student-profile-trigger').forEach((button) => {
    button.onclick = () => {
      viewedStudentKey = button.dataset.studentKey || 'me';
      renderProfilePanels();
      showAppView('clientProfileView');
      setActiveProfileTab('posts');
      closeModal(chatModal);
    };
  });

  document.querySelectorAll('.open-chat-button').forEach((button) => {
    button.onclick = () => {
      const studentKey = button.dataset.openStudentChat;
      if (!studentKey) return;
      activeChatId = studentKey;
      renderLiveChat();
      loadServerChat(activeChatId);
      openModal(chatModal);
    };
  });

  document.querySelectorAll('.add-friend-button').forEach((button) => {
    button.onclick = () => {
      const friends = new Set(getFriends());
      friends.add(button.dataset.studentKey);
      saveFriends(Array.from(friends));
      button.textContent = 'В друзьях';
      button.disabled = true;
    };
    if (getFriends().includes(button.dataset.studentKey)) {
      button.textContent = 'В друзьях';
      button.disabled = true;
    }
  });

  document.querySelectorAll('.community-trigger').forEach((card) => {
    card.onclick = () => {
      const community = COMMUNITIES[card.dataset.communityKey];
      if (!community) return;
      communityModalTitle.textContent = community.title;
      communityModalDescription.textContent = community.description;
      communityModalTags.innerHTML = community.tags.map((tag) => `<span>${escapeHtml(tag)}</span>`).join('');
      openModal(communityModal);
    };
  });
};

const setActiveProfileTab = (tabId = 'posts') => {
  activeProfileTab = tabId;
  profileTabs.forEach((button) => {
    button.classList.toggle('is-active', button.dataset.profileTab === tabId);
  });
  profilePanels.forEach((panel) => {
    panel.classList.toggle('profile-tab-panel-active', panel.dataset.profilePanel === tabId);
  });
};

const updateHomeFeed = () => {
  homeTypeButtons.forEach((button) => {
    button.classList.toggle('is-active', button.dataset.mainType === activeMainType);
  });
  homeModeButtons.forEach((button) => {
    button.classList.toggle('is-active', button.dataset.homeMode === activeHomeMode);
  });

  if (isHostedMode()) {
    return;
  }

  Array.from(document.querySelectorAll('#workspaceView .generated-post, #workspaceView .workspace-static-post')).forEach((post) => {
    const typeMatch = activeMainType === 'all' || post.dataset.mainType === activeMainType;
    const modeMatch = post.dataset.homeMode === activeHomeMode;
    const searchSource = `${post.textContent} ${post.dataset.searchText || ''}`.toLowerCase();
    const queryMatch = !activeWorkspaceQuery || searchSource.includes(activeWorkspaceQuery);
    post.style.display = typeMatch && modeMatch && queryMatch ? '' : 'none';
  });
};

const updateStudentsFeed = () => {
  // Legacy separate students feed removed; the main workspace now acts as the student feed.
};

const updateInternships = () => {
  const query = internshipQuery.value.trim().toLowerCase();
  const schedule = internshipScheduleFilter.value;
  const education = internshipEducationFilter.value;
  const specialty = internshipSpecialtyFilter.value;
  internshipCards.forEach((card) => {
    const title = card.dataset.title.toLowerCase();
    const company = card.dataset.company.toLowerCase();
    const matchQuery = !query || title.includes(query) || company.includes(query);
    const matchSchedule = !schedule || card.dataset.schedule === schedule;
    const matchEducation = !education || card.dataset.education === education;
    const matchSpecialty = !specialty || card.dataset.specialty === specialty;
    card.style.display = matchQuery && matchSchedule && matchEducation && matchSpecialty ? '' : 'none';
  });
};

const updateCommunities = () => {
  const name = communityNameFilter.value.trim().toLowerCase();
  const interest = communityInterestFilter.value;
  const specialty = communitySpecialtyFilter.value;
  const education = communityEducationFilter.value;
  communityCards.forEach((card) => {
    const title = card.querySelector('h3')?.textContent.toLowerCase() || '';
    const matchName = !name || title.includes(name);
    const matchInterest = !interest || card.dataset.interest === interest;
    const matchSpecialty = !specialty || card.dataset.specialty === specialty;
    const matchEducation = !education || card.dataset.education === education;
    card.style.display = matchName && matchInterest && matchSpecialty && matchEducation ? '' : 'none';
  });
};

const renderChatHeader = () => {
  if (isAccountChatId(activeChatId)) {
    const student = getStudentData(activeChatId);
    if (!student) return;
    const label = `${student.firstName} ${student.lastName}`.trim();
    const meta = student.organization
      ? `${student.organization}${student.specialty ? ` • ${specialtyLabel(student.specialty)}` : ''}`
      : student.email;
    chatHeaderName.textContent = label;
    chatHeaderMeta.textContent = meta;
    chatProfileButton.dataset.studentKey = student.key;
    chatProfileButton.style.display = '';
    applyAvatar(chatHeaderAvatar, getInitials(label), student.photo || '');
    return;
  }

  const meta = CHAT_META[activeChatId];
  if (!meta) return;
  chatHeaderName.textContent = meta.label;
  chatHeaderMeta.textContent = meta.meta;
  chatProfileButton.dataset.studentKey = meta.studentKey || '';
  chatProfileButton.style.display = meta.studentKey ? '' : 'none';
  applyAvatar(chatHeaderAvatar, getInitials(meta.label), '');
};

const renderAttachment = (message) => {
  if (!message.attachment) return '';
  const attachmentSource = message.attachment.data || getHostedAttachmentUrl(message.attachment.path || '');
  if (message.attachment.kind === 'image') {
    return `<img class="message-attachment-image" src="${escapeHtml(attachmentSource)}" alt="${escapeHtml(message.attachment.name)}">`;
  }
  if (message.attachment.kind === 'video') {
    return `<video class="message-attachment-image" controls src="${escapeHtml(attachmentSource)}"></video>`;
  }
  return `<div class="attachment-pill">${escapeHtml(message.attachment.name)} · ${escapeHtml(message.attachment.kind)}</div>`;
};

const loadServerChat = async (chatId) => {
  if (!isHostedMode() || !isAccountChatId(chatId)) return;
  const student = getStudentData(chatId);
  if (!student?.email) return;

  try {
    const data = await getJson(`./api/messages/list.php?peer=${encodeURIComponent(student.email)}`);
    serverChatCache = {
      ...serverChatCache,
      [chatId]: Array.isArray(data.messages) ? data.messages : [],
    };
    if (activeChatId === chatId) {
      renderLiveChat();
    }
  } catch {
    serverChatCache = {
      ...serverChatCache,
      [chatId]: [],
    };
    if (activeChatId === chatId) {
      renderLiveChat();
    }
  }
};

const renderChatContacts = () => {
  const registeredContacts = getRegisteredStudents({ excludeCurrent: true }).map((student) => {
    const label = `${student.firstName} ${student.lastName}`.trim();
    return `
      <button class="chat-contact ${activeChatId === student.key ? 'is-active' : ''}" type="button" data-live-chat="${escapeHtml(student.key)}" style="${getCardStyle(student)}">
        ${escapeHtml(label)}
      </button>
    `;
  });

  const systemContacts = Object.entries(CHAT_META).map(([chatId, meta]) => `
    <button class="chat-contact ${activeChatId === chatId ? 'is-active' : ''}" type="button" data-live-chat="${escapeHtml(chatId)}">
      ${escapeHtml(meta.label)}
    </button>
  `);

  chatContactsList.innerHTML = [...registeredContacts, ...systemContacts].join('');
  chatContactsList.querySelectorAll('[data-live-chat]').forEach((button) => {
    button.addEventListener('click', () => {
      activeChatId = button.dataset.liveChat;
      renderLiveChat();
      loadServerChat(activeChatId);
    });
  });
};

const renderLiveChat = () => {
  const chats = getChats();
  const messages = isHostedMode() && isAccountChatId(activeChatId)
    ? (serverChatCache[activeChatId] || [])
    : (chats[getChatThreadId(activeChatId)] || []);
  renderChatContacts();
  renderChatHeader();
  liveChatMessages.innerHTML = messages.map((message) => `
    <div class="message-bubble ${message.direction}">
      ${message.text ? `<div>${escapeHtml(message.text)}</div>` : ''}
      ${renderAttachment(message)}
    </div>
  `).join('');
  liveChatMessages.scrollTop = liveChatMessages.scrollHeight;

  if (isHostedMode() && isAccountChatId(activeChatId) && serverChatCache[activeChatId] === undefined) {
    liveChatMessages.innerHTML = '<div class="comment-empty">Загружаю переписку...</div>';
    loadServerChat(activeChatId);
  }
};

const appendAutoReply = () => {
  const chats = getChats();
  const threadId = getChatThreadId(activeChatId);
  const nextMessages = chats[threadId] || [];
  const replyPool = isAccountChatId(activeChatId)
    ? ['Привет, увидел твое сообщение.', 'Спасибо, сейчас посмотрю и отвечу.', 'Супер, давай обсудим это подробнее.']
    : (CHAT_AUTOREPLIES[activeChatId] || ['Принял, продолжаем.']);
  const reply = replyPool[Math.floor(Math.random() * replyPool.length)];
  nextMessages.push({ direction: 'incoming', text: reply });
  chats[threadId] = nextMessages;
  saveChats(chats);
  renderLiveChat();
};

const createPost = async ({ text, attachment = null, file = null }) => {
  const account = getAccount();
  if (!account) return;
  const tags = (account.interests || []).filter(Boolean);
  if (isHostedMode()) {
    if (activeEditingPostId) {
      const data = await postJson('./api/feed/update.php', {
        postId: activeEditingPostId,
        text,
      });
      upsertServerPost(data.post || {});
      activeEditingPostId = '';
      renderPosts();
      return;
    }

    const formData = new FormData();
    formData.append('text', text);
    formData.append('type', 'post');
    formData.append('feedMode', 'recommendations');
    tags.forEach((tag) => formData.append('tags[]', tag));
    if (file) {
      formData.append('attachment', file);
    }
    const data = await postFormData('./api/feed/create.php', formData);
    upsertServerPost(data.post || {});
    renderPosts();
    return;
  }

  const posts = getPosts();
  const editingPost = posts.find((post) => post.isEditing);
  if (editingPost) {
    const nextPosts = posts.map((post) => post.id === editingPost.id
      ? {
          ...post,
          text,
          attachment: attachment || post.attachment || null,
          isEditing: false,
        }
      : { ...post, isEditing: false });
    savePosts(nextPosts);
    renderPosts();
    return;
  }
  posts.push({
    id: crypto.randomUUID ? crypto.randomUUID() : String(Date.now()),
    type: 'post',
    feedMode: 'recommendations',
    interestMode: tags[0] || 'design',
    text,
    createdAt: new Date().toISOString(),
    tags: tags.length ? tags : ['design'],
    likes: 0,
    likedByMe: false,
    comments: [],
    unreadComments: 0,
    authorKey: 'me',
    authorPhoto: account.photo || '',
    attachment,
  });
  savePosts(posts);
  renderPosts();
};

const hydrateUser = (account) => {
  const fullName = `${account.firstName} ${account.lastName}`.trim();
  const initials = getInitials(fullName);
  const countryText = account.countryFlag ? `${account.countryFlag} ${account.countryName}` : 'Пока не указано';
  const interestsText = (account.interests || []).filter(Boolean).map(interestLabel).join(', ') || 'Пока не указано';
  const specialtyText = specialtyLabel(account.specialty);
  const educationText = educationLabel(account.education);

  topbarUserName.textContent = fullName;
  profileName.textContent = fullName;
  profileEmail.textContent = account.email;
  profileEmailCard.textContent = account.email;
  profileOrganizationCard.textContent = account.organization || 'Пока не указано';
  profileSpecialtyCard.textContent = educationText ? `${specialtyText} • ${educationText}` : specialtyText;
  profileCountryCard.textContent = countryText;
  profileInterestsCard.textContent = interestsText;

  applyAvatar(composerAvatar, initials, account.photo);
  applyAvatar(profileAvatar, initials, account.photo);
  applyAvatar(topbarAvatar, initials, account.photo);
  syncProfileForm(account);
  updateStrengthUI(account);
  viewedStudentKey = 'me';
  renderProfilePanels();
  renderPosts();
};

registerForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  const formData = new FormData(registerForm);
  const account = {
    firstName: String(formData.get('firstName')).trim(),
    lastName: String(formData.get('lastName')).trim(),
    email: String(formData.get('email')).trim().toLowerCase(),
    password: String(formData.get('password')).trim(),
    organization: '',
    specialty: '',
    education: '',
    countryValue: '',
    countryFlag: '',
    countryName: '',
    interests: [],
    photo: '',
    style: {
      fontTheme: 'manrope',
      accentColor: '#e78479',
      cardColor: '#f1e4d0',
    },
  };
  const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(account.email);
  if (!account.firstName || !account.lastName || !emailValid || account.password.length < 6) {
    setRegisterMessage('Проверь поля: имя, фамилия и email обязательны, пароль минимум 6 символов.', 'error');
    return;
  }
  const savedAccount = getAccounts().find((item) => item.email === account.email);
  if (savedAccount) {
    setRegisterMessage('Аккаунт с таким email уже существует. Перейди на страницу входа.', 'error');
    return;
  }

  if (window.location.protocol !== 'file:') {
    try {
      const response = await postJson('./api/auth/register.php', account);
      const nextAccount = mergeProfileIntoAccount({
        ...account,
        firstName: response.user?.firstName || account.firstName,
        lastName: response.user?.lastName || account.lastName,
        email: response.user?.email || account.email,
      }, response.user?.profile || {});
      saveAccount(nextAccount);
      window.localStorage.setItem(STORAGE_KEYS.session, nextAccount.email);
      hydrateUser(nextAccount);
      await loadCurrentProfileFromServer(nextAccount);
      registerForm.reset();
      setRegisterMessage('');
      await syncRemoteUsers();
      updateRegisteredCount();
      showAppView('workspaceView');
      return;
    } catch (error) {
      setRegisterMessage(error.message, 'error');
      return;
    }
  }

  saveAccount(account);
  window.localStorage.setItem(STORAGE_KEYS.session, account.email);
  hydrateUser(account);
  registerForm.reset();
  setRegisterMessage('');
  updateRegisteredCount();
  showAppView('workspaceView');
});

loginForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  const formData = new FormData(loginForm);
  const identifier = String(formData.get('identifier')).trim().toLowerCase();
  const password = String(formData.get('password')).trim();

  try {
    if (window.location.protocol !== 'file:') {
      const response = await postJson('./api/auth/login.php', {
        email: identifier,
        password,
      });

      const savedAccount = getAccounts().find((account) => account.email === identifier);
      const nextAccount = mergeProfileIntoAccount(savedAccount || {
        firstName: response.user?.firstName || '',
        lastName: response.user?.lastName || '',
        email: response.user?.email || identifier,
        password,
        organization: '',
        specialty: '',
        education: '',
        countryValue: '',
        countryFlag: '',
        countryName: '',
        interests: [],
        photo: '',
        style: {
          fontTheme: 'manrope',
          accentColor: '#e78479',
          cardColor: '#f1e4d0',
        },
      }, response.user?.profile || {});

      saveAccount(nextAccount);
      window.localStorage.setItem(STORAGE_KEYS.session, nextAccount.email);
      hydrateUser(nextAccount);
      await loadCurrentProfileFromServer(nextAccount);
      loginForm.reset();
      setLoginMessage('');
      await syncRemoteUsers();
      updateRegisteredCount();
      showAppView('workspaceView');
      return;
    }
  } catch (error) {
    if (window.location.protocol !== 'file:') {
      setLoginMessage(error.message, 'error');
      return;
    }
  }

  const localAccount = getAccounts().find((account) => account.email === identifier);
  if (localAccount) {
    if (localAccount.password !== password) {
      setLoginMessage('Неверный email или пароль. Используй данные из регистрации.', 'error');
      return;
    }

    window.localStorage.setItem(STORAGE_KEYS.session, localAccount.email);
    hydrateUser(localAccount);
    loginForm.reset();
    setLoginMessage('');
    updateRegisteredCount();
    showAppView('workspaceView');
    return;
  }

  setLoginMessage('Неверный email или пароль.', 'error');
});

goToLoginButton.addEventListener('click', () => {
  showLoginScreen();
  const account = getAccount();
  loginIdentifierInput.value = account ? account.email : '';
  setLoginMessage('Если аккаунт уже создан, войди по email.', 'info');
});

backToRegisterButton.addEventListener('click', () => {
  showRegisterScreen();
  setRegisterMessage('Если нужно, обнови данные и зарегистрируйся заново.', 'info');
  setLoginMessage('');
});

userPill.addEventListener('click', () => {
  viewedStudentKey = 'me';
  renderProfilePanels();
  showAppView('clientProfileView');
  setActiveProfileTab('posts');
});

profileBackButton.addEventListener('click', () => showAppView('workspaceView'));
clientProfileBackButton.addEventListener('click', () => showAppView('workspaceView'));
editProfileButton.addEventListener('click', () => showAppView('profileView'));

logoutButton.addEventListener('click', () => {
  window.localStorage.removeItem(STORAGE_KEYS.session);
  showLoginScreen();
  const account = getAccount();
  loginForm.reset();
  loginIdentifierInput.value = account ? account.email : '';
  setLoginMessage('Ты вышел из аккаунта. Чтобы вернуться на платформу, снова войди.', 'info');
});

profileDetailsForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  const account = getAccount();
  if (!account) return;
  const [countryFlag = '', countryName = ''] = String(profileCountryInput.value).split('|');
  const nextAccount = {
    ...account,
    organization: profileOrganizationInput.value.trim(),
    specialty: profileSpecialtyInput.value,
    education: profileEducationInput.value,
    countryValue: profileCountryInput.value,
    countryFlag,
    countryName,
    interests: interestInputs.map((input) => input.value).filter(Boolean).slice(0, 3),
    style: {
      fontTheme: profileFontThemeInput.value,
      accentColor: profileAccentColorInput.value,
      cardColor: profileCardColorInput.value,
    },
  };

  if (isHostedMode()) {
    try {
      const response = await postJson('./api/profile/update.php', {
        organization: nextAccount.organization,
        specialty: nextAccount.specialty,
        education: nextAccount.education,
        countryCode: nextAccount.countryFlag,
        countryName: nextAccount.countryName,
        interests: nextAccount.interests,
        style: nextAccount.style,
      });
      const mergedAccount = mergeProfileIntoAccount(nextAccount, response.profile || {});
      saveAccount(mergedAccount);
      hydrateUser(mergedAccount);
      await syncRemoteUsers();
      return;
    } catch (error) {
      setLoginMessage(error.message, 'error');
      return;
    }
  }

  saveAccount(nextAccount);
  hydrateUser(nextAccount);
});

profilePhotoInput.addEventListener('change', async () => {
  const [file] = profilePhotoInput.files || [];
  const result = validateFile(file, ['image/']);
  if (!file || !result.ok) {
    if (file && !result.ok) {
      setLoginMessage(result.message, 'error');
    }
    profilePhotoInput.value = '';
    return;
  }
  const account = getAccount();
  if (!account) return;

  if (isHostedMode()) {
    try {
      const formData = new FormData();
      formData.append('avatar', file);
      const response = await postFormData('./api/profile/avatar.php', formData);
      const nextAccount = mergeProfileIntoAccount(account, response.profile || {});
      saveAccount(nextAccount);
      hydrateUser(nextAccount);
      await syncRemoteUsers();
      profilePhotoInput.value = '';
      return;
    } catch (error) {
      setLoginMessage(error.message, 'error');
      profilePhotoInput.value = '';
      return;
    }
  }

  const reader = new FileReader();
  reader.addEventListener('load', () => {
    const nextAccount = { ...account, photo: typeof reader.result === 'string' ? reader.result : '' };
    saveAccount(nextAccount);
    hydrateUser(nextAccount);
    profilePhotoInput.value = '';
  });
  reader.readAsDataURL(file);
});

goToOwnProfileButton.addEventListener('click', () => {
  viewedStudentKey = 'me';
  renderProfilePanels();
  showAppView('clientProfileView');
  setActiveProfileTab('posts');
  openModal(postModal);
});

openProfilePostButton.addEventListener('click', () => {
  viewedStudentKey = 'me';
  renderProfilePanels();
  showAppView('clientProfileView');
  setActiveProfileTab('posts');
  openModal(postModal);
});

closePostButton.addEventListener('click', () => {
  modalPostAttachmentPreview.innerHTML = '';
  activeEditingPostId = '';
  closeModal(postModal);
});
closePostBackdrop.addEventListener('click', () => {
  modalPostAttachmentPreview.innerHTML = '';
  activeEditingPostId = '';
  closeModal(postModal);
});

modalPostForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  const text = modalPostInput.value.trim();
  const [file] = modalPostAttachmentInput.files || [];
  const editingPost = activeEditingPostId
    ? getRenderablePosts().find((post) => String(post.id) === String(activeEditingPostId))
    : null;
  const hasExistingAttachment = Boolean(editingPost?.attachment);
  if (!text && !file && !hasExistingAttachment) {
    setLoginMessage('Пост без текста и файла нельзя опубликовать.', 'error');
    return;
  }

  const finishSubmit = async (attachment = null) => {
    await createPost({ text, attachment, file });
    modalPostForm.reset();
    modalPostAttachmentPreview.innerHTML = '';
    activeEditingPostId = '';
    closeModal(postModal);
    viewedStudentKey = 'me';
    renderProfilePanels();
    showAppView('clientProfileView');
    setActiveProfileTab('posts');
  };

  if (isHostedMode()) {
    try {
      await finishSubmit();
    } catch (error) {
      setLoginMessage(error.message, 'error');
    }
    return;
  }

  if (file) {
    const reader = new FileReader();
    reader.addEventListener('load', async () => {
      await finishSubmit({
        name: file.name,
        kind: file.type.startsWith('image/') ? 'image' : 'video',
        data: typeof reader.result === 'string' ? reader.result : '',
      });
    });
    reader.readAsDataURL(file);
    return;
  }

  finishSubmit();
});

closeCommentsButton.addEventListener('click', () => closeModal(commentsModal));
closeCommentsBackdrop.addEventListener('click', () => closeModal(commentsModal));

commentsModalForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  const text = commentsModalInput.value.trim();
  if (!text || !activeCommentsPostId) return;

  if (isHostedMode()) {
    try {
      const data = await postJson('./api/feed/comment.php', {
        postId: activeCommentsPostId,
        body: text,
      });
      const currentComments = (serverFeedItems.find((post) => String(post.id) === String(activeCommentsPostId))?.comments) || [];
      const nextComments = [...currentComments, data.comment].filter(Boolean);
      setServerPostComments(activeCommentsPostId, nextComments);
      commentsModalList.innerHTML = renderCommentList(nextComments);
      commentsModalInput.value = '';
      renderPosts();
      return;
    } catch (error) {
      setLoginMessage(error.message, 'error');
      return;
    }
  }

  const posts = getPosts();
  const account = getAccount();
  const author = account ? `${account.firstName} ${account.lastName}`.trim() : 'Студент';
  const nextPosts = posts.map((post) => post.id === activeCommentsPostId
    ? {
        ...post,
        comments: [...(post.comments || []), { author, text }],
      }
    : post);
  savePosts(nextPosts);
  const nextPost = nextPosts.find((post) => post.id === activeCommentsPostId);
  commentsModalList.innerHTML = renderCommentList(nextPost?.comments || []);
  commentsModalInput.value = '';
  renderPosts();
});

modalPostAttachmentInput.addEventListener('change', () => {
  const [file] = modalPostAttachmentInput.files || [];
  const result = validateFile(file, ['image/', 'video/']);
  if (!result.ok) {
    modalPostAttachmentInput.value = '';
    modalPostAttachmentPreview.innerHTML = '';
    setRegisterMessage(result.message, 'error');
    return;
  }
  renderAttachmentPreview(modalPostAttachmentInput, modalPostAttachmentPreview);
});

chatAttachmentInput.addEventListener('change', () => {
  const [file] = chatAttachmentInput.files || [];
  const result = validateFile(file, ['image/', 'video/'], ['application/pdf']);
  if (!result.ok) {
    chatAttachmentInput.value = '';
    chatAttachmentPreview.innerHTML = '';
    setLoginMessage(result.message, 'error');
    return;
  }
  renderAttachmentPreview(chatAttachmentInput, chatAttachmentPreview);
});

openInboxButton.addEventListener('click', () => openModal(inboxModal));
closeInboxButton.addEventListener('click', () => closeModal(inboxModal));
closeInboxBackdrop.addEventListener('click', () => closeModal(inboxModal));
openChatsMenuButton.addEventListener('click', () => {
  const registeredContacts = getRegisteredStudents({ excludeCurrent: true });
  if (isAccountChatId(activeChatId) && !getStudentData(activeChatId)) {
    activeChatId = registeredContacts[0]?.key || 'tomiris';
  } else if (!activeChatId) {
    activeChatId = registeredContacts[0]?.key || 'tomiris';
  }
  renderLiveChat();
  loadServerChat(activeChatId);
  openModal(chatModal);
});
closeChatButton.addEventListener('click', () => closeModal(chatModal));
closeChatBackdrop.addEventListener('click', () => closeModal(chatModal));
chatPreviews.forEach((button) => {
  button.addEventListener('click', () => {
    activeChatId = button.dataset.openChat;
    renderLiveChat();
    openModal(chatModal);
  });
});
chatProfileButton.addEventListener('click', () => {
  const key = chatProfileButton.dataset.studentKey;
  if (!key) return;
  viewedStudentKey = key;
  renderProfilePanels();
  closeModal(chatModal);
  showAppView('clientProfileView');
  setActiveProfileTab('posts');
});

liveChatForm.addEventListener('submit', (event) => {
  event.preventDefault();
  const text = liveChatInput.value.trim();
  const chats = getChats();
  const threadId = getChatThreadId(activeChatId);
  const nextMessages = chats[threadId] || [];
  if (!text && !chatAttachmentInput.files?.length) return;

  if (chatAttachmentInput.files?.length) {
    const [file] = chatAttachmentInput.files;
    const reader = new FileReader();
    reader.addEventListener('load', () => {
      nextMessages.push({
        direction: 'outgoing',
        text,
        attachment: {
          name: file.name,
          kind: file.type.startsWith('image/') ? 'image' : file.type.startsWith('video/') ? 'video' : 'document',
          data: typeof reader.result === 'string' ? reader.result : '',
        },
      });
      chats[threadId] = nextMessages;
      saveChats(chats);
      liveChatForm.reset();
      chatAttachmentPreview.innerHTML = '';
      renderLiveChat();
      window.setTimeout(appendAutoReply, 700);
    });
    reader.readAsDataURL(file);
    return;
  }

  nextMessages.push({ direction: 'outgoing', text });
  chats[threadId] = nextMessages;
  saveChats(chats);
  liveChatForm.reset();
  chatAttachmentPreview.innerHTML = '';
  renderLiveChat();
  window.setTimeout(appendAutoReply, 700);
});

profileTabs.forEach((button) => {
  button.addEventListener('click', () => setActiveProfileTab(button.dataset.profileTab));
});

viewSwitchers.forEach((button) => {
  button.addEventListener('click', () => showAppView(button.dataset.viewTarget));
});

homeTypeButtons.forEach((button) => {
  button.addEventListener('click', () => {
    activeMainType = button.dataset.mainType;
    if (isHostedMode()) {
      updateHomeFeed();
      fetchFeed({ page: 1, reset: true });
      closeModal(feedFilterPopover);
      return;
    }
    updateHomeFeed();
    closeModal(feedFilterPopover);
  });
});

homeModeButtons.forEach((button) => {
  button.addEventListener('click', () => {
    activeHomeMode = button.dataset.homeMode;
    if (isHostedMode()) {
      updateHomeFeed();
      fetchFeed({ page: 1, reset: true });
      closeModal(feedFilterPopover);
      return;
    }
    updateHomeFeed();
    closeModal(feedFilterPopover);
  });
});

workspaceSearchInput.addEventListener('input', () => {
  activeWorkspaceQuery = workspaceSearchInput.value.trim().toLowerCase();
  if (isHostedMode()) {
    window.clearTimeout(workspaceSearchDebounceId);
    workspaceSearchDebounceId = window.setTimeout(() => {
      fetchFeed({ page: 1, reset: true });
    }, 250);
    return;
  }
  updateHomeFeed();
});

window.addEventListener('scroll', () => {
  if (!isHostedMode() || serverFeedLoading || !serverFeedHasMore) return;
  const activeView = document.querySelector('.app-view.app-view-active');
  if (!activeView || activeView.id !== 'workspaceView') return;
  const threshold = document.documentElement.scrollHeight - window.innerHeight - 320;
  if (window.scrollY < threshold) return;
  fetchFeed({ page: serverFeedPage + 1, reset: false });
});

toggleFeedFiltersButton.addEventListener('click', () => {
  toggleFeedFilters();
});

document.addEventListener('click', (event) => {
  const target = event.target;
  if (!(target instanceof Element)) return;
  if (target.closest('#toggleFeedFiltersButton') || target.closest('#feedFilterPopover')) return;
  closeModal(feedFilterPopover);
});

feedModeButtons.forEach((button) => {
  button.addEventListener('click', () => {
    activeFeedMode = button.dataset.feedMode;
    updateStudentsFeed();
  });
});

interestChips.forEach((chip) => {
  chip.addEventListener('click', () => {
    activeInterest = chip.dataset.interest;
    updateStudentsFeed();
  });
});

[internshipQuery, internshipScheduleFilter, internshipEducationFilter, internshipSpecialtyFilter].forEach((control) => {
  control.addEventListener('input', updateInternships);
  control.addEventListener('change', updateInternships);
});

[communityNameFilter, communityInterestFilter, communitySpecialtyFilter, communityEducationFilter].forEach((control) => {
  control.addEventListener('input', updateCommunities);
  control.addEventListener('change', updateCommunities);
});

connectSearchInput.addEventListener('input', () => {
  renderConnectResults();
  bindDynamicInteractions();
});

networkFilterButtons.forEach((button) => {
  button.addEventListener('click', () => {
    activeNetworkFilter = button.dataset.networkFilter || 'all';
    networkFilterButtons.forEach((item) => {
      item.classList.toggle('is-active', item === button);
    });
    renderConnectResults();
    bindDynamicInteractions();
  });
});

closeCommunityButton.addEventListener('click', () => closeModal(communityModal));
closeCommunityBackdrop.addEventListener('click', () => closeModal(communityModal));

document.querySelectorAll('button').forEach((button) => {
  button.addEventListener('click', () => {
    button.classList.add('is-pressed');
    window.setTimeout(() => button.classList.remove('is-pressed'), 180);
  });
});

const seedPosts = () => {
  if (getPosts().length > 0) return;
  savePosts([
    {
      id: 'seed-1',
      type: 'post',
      feedMode: 'recommendations',
      interestMode: 'ai',
      text: 'Собираю небольшую AI study-group для студентов, кому интересны NLP и реальные pet projects.',
      createdAt: new Date().toISOString(),
      tags: ['ai', 'product'],
      likes: 6,
      comments: [{ author: 'Томирис Н.', text: 'Звучит сильно, я бы зашла на первый созвон.' }],
      authorKey: 'tomiris',
    },
    {
      id: 'seed-2',
      type: 'project',
      feedMode: 'subscriptions',
      interestMode: 'frontend',
      text: 'Campus Match: собираю MVP платформы, которая помогает студентам искать команды по навыкам и интересам.',
      createdAt: new Date().toISOString(),
      tags: ['frontend', 'design'],
      likes: 11,
      comments: [{ author: 'Асем С.', text: 'Могу помочь с UI и onboarding flow.' }],
      authorKey: 'asem',
    },
  ]);
};

const updateOnlineCount = () => {
  updateRegisteredCount();
};

const boot = () => {
  updateOnlineCount();
  if (!isHostedMode()) {
    seedPosts();
  }
  const account = getAccount();
  const session = getSession();
  const params = new URLSearchParams(window.location.search);
  const verified = params.get('verified');
  const verifyMessage = params.get('message');

  if (account) {
    loginIdentifierInput.value = account.email;
    hydrateUser(account);
    if (isHostedMode()) {
      loadCurrentProfileFromServer(account).catch(() => {
        // Fall back to cached local profile if backend profile is unavailable.
      });
    }
  }

  if (window.location.protocol !== 'file:') {
    fetchFeed({ page: 1, reset: true }).catch(() => {
      // Feed falls back to empty state if the backend is unavailable.
    });
    syncRemoteUsers().then(() => {
      renderConnectResults();
      renderLiveChat();
      renderFriendSuggestions();
      bindDynamicInteractions();
    });
    fetch('./api/auth/csrf.php', { credentials: 'same-origin' })
      .then((response) => response.json())
      .then((data) => {
        csrfToken = data.token || '';
      })
      .catch(() => {
        csrfToken = '';
      });
  }
  renderLiveChat();
  renderPosts();
  renderNotifications();
  updateInternships();
  updateCommunities();
  setActiveProfileTab('posts');
  if (account && session === account.email) {
    showAppView('workspaceView');
    return;
  }
  showRegisterScreen();
  if (account) {
    setRegisterMessage('Регистрация снова доступна. Если аккаунт уже есть, нажми "Уже зарегистрирован? Войти".', 'info');
    setLoginMessage(
      verified === '1'
        ? 'Email подтвержден. Теперь можно войти.'
        : verified === 'error'
          ? (verifyMessage || 'Ссылка подтверждения недействительна.')
          : 'Аккаунт уже создан. Войди по email, чтобы открыть платформу.',
      verified === 'error' ? 'error' : 'info'
    );
    return;
  }
  setRegisterMessage('Сначала создай аккаунт студента, затем перейди на страницу входа.', 'info');
};

boot();
