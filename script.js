/**
 * Mendflow styles bundle (script2.js from Downloads — CSS exported as .js).
 * Injected when style.css is not already linked (e.g. standalone script.js pages).
 */
(function loadScript2Styles() {
  const STYLE_ID = 'mf-script2-styles';
  if (document.getElementById(STYLE_ID)) return;
  if (document.querySelector('link[href*="style.css"]')) return;

  fetch('./script2.js', { cache: 'no-cache' })
    .then((response) => (response.ok ? response.text() : Promise.reject(new Error('script2.js not found'))))
    .then((css) => {
      if (!css || (!css.includes(':root') && !css.includes('body{'))) return;
      const tag = document.createElement('style');
      tag.id = STYLE_ID;
      tag.textContent = css;
      document.head.appendChild(tag);
    })
    .catch((error) => {
      console.warn('mendflow: не удалось загрузить script2.js (styles)', error);
    });
})();

const STORAGE_KEYS = {
  accounts: 'pwe-accounts',
  account: 'pwe-account',
  session: 'pwe-session',
  posts: 'pwe-posts',
  chats: 'pwe-live-chats',
  friends: 'pwe-friends',
  friendships: 'pwe-friendships',
  notifications: 'pwe-notifications',
  tasks: 'pwe-tasks',
  activityData: 'pwe-activity-data',
};

// Load activity data from JSON on app start
const loadActivityData = async () => {
  try {
    const response = await fetch('./database/activity-data.json');
    const data = await response.json();
    window.localStorage.setItem(STORAGE_KEYS.activityData, JSON.stringify(data));
    return data;
  } catch (error) {
    console.warn('mendflow: не удалось загрузить данные активности', error);
    return {};
  }
};

const getActivityData = (email) => {
  const dataJson = window.localStorage.getItem(STORAGE_KEYS.activityData);
  const allData = dataJson ? JSON.parse(dataJson) : {};
  return allData[email] || {
    profileViews: 0,
    cvResponses: 0,
    networkConnections: 0,
    posts: 0,
    projects: 0,
    friends: 0,
  };
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
const brandLink = document.querySelector('.brand');

const userPill = document.querySelector('#userPill');
const topbarAvatar = document.querySelector('#topbarAvatar');
const topbarUserName = document.querySelector('#topbarUserName');
const composerAvatar = document.querySelector('#composerAvatar');
const goToOwnProfileButton = document.querySelector('#goToOwnProfileButton');
const feedPublishButton = document.querySelector('#feedPublishButton');
const appViews = Array.from(document.querySelectorAll('.app-view'));
const viewSwitchers = Array.from(document.querySelectorAll('[data-view-target]'));

const profileBackButton = document.querySelector('#profileBackButton');
const clientProfileBackButton = document.querySelector('#clientProfileBackButton');
const editProfileButton = document.querySelector('#editProfileButton');
const logoutButton = document.querySelector('#logoutButton');
const profilePhotoInput = document.querySelector('#profilePhotoInput');
const profileDetailsForm = document.querySelector('#profileDetailsForm');
const profileSettingsModal = document.querySelector('#profileSettingsModal');
const closeProfileSettingsButton = document.querySelector('#closeProfileSettingsButton');
const closeProfileSettingsBackdrop = document.querySelector('#closeProfileSettingsBackdrop');
const profileSettingsForm = document.querySelector('#profileSettingsForm');
const profileSecurityForm = document.querySelector('#profileSecurityForm');
const profileSettingsLogoutButton = document.querySelector('#profileSettingsLogoutButton');
const profileSettingsAvatar = document.querySelector('#profileSettingsAvatar');
const profileSettingsName = document.querySelector('#profileSettingsName');
const profileSettingsEmail = document.querySelector('#profileSettingsEmail');
const profileSettingsOrganizationInput = document.querySelector('#profileSettingsOrganization');
const profileSettingsSpecialtyInput = document.querySelector('#profileSettingsSpecialty');
const profileSettingsEducationInput = document.querySelector('#profileSettingsEducation');
const profileSettingsCountryInput = document.querySelector('#profileSettingsCountry');
const profileSettingsBioInput = document.querySelector('#profileSettingsBio');
const profileSettingsInterestInputs = Array.from(document.querySelectorAll('.settings-interest-select'));
const profileSettingsNavButtons = Array.from(document.querySelectorAll('[data-settings-panel]'));
const profileSettingsPanels = Array.from(document.querySelectorAll('[data-settings-panel-content]'));
const profileSecurityEmail = document.querySelector('#profileSecurityEmail');
const profileSecurityPassword = document.querySelector('#profileSecurityPassword');
const profileSecurityPasswordConfirm = document.querySelector('#profileSecurityPasswordConfirm');
const profileFontThemeInput = null;
const profileAccentColorInput = null;
const profileCardColorInput = null;
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
const clientProfileFriendsCount = document.querySelector('#clientProfileFriendsCount');
const clientProfileProjectsCount = document.querySelector('#clientProfileProjectsCount');
const clientProfilePostsCount = document.querySelector('#clientProfilePostsCount');
const clientProfileBio = document.querySelector('#clientProfileBio');
const profileStrength = document.querySelector('#profileStrength');
const profileStrengthText = document.querySelector('#profileStrengthText');
const sidebarStrengthCard = document.querySelector('.sidebar-strength-card');
const sidebarTaskForm = document.querySelector('#sidebarTaskForm');
const sidebarTaskInput = document.querySelector('#sidebarTaskInput');
const sidebarTaskList = document.querySelector('#sidebarTaskList');
const profileTabs = Array.from(document.querySelectorAll('[data-profile-tab]'));
const profilePanels = Array.from(document.querySelectorAll('[data-profile-panel]'));
const openProfilePostButton = document.querySelector('#openProfilePostButton');
const profilePostsList = document.querySelector('#profilePostsList');
const profilePanelCard = document.querySelector('#clientProfileView .profile-panel-card');
const profileActivityCard = document.querySelector('#clientProfileView .pf-activity-card');
const profileViewsCount = document.querySelector('#profileViewsCount');
const profileCvResponsesCount = document.querySelector('#profileCvResponsesCount');
const profileNetworkConnectionsCount = document.querySelector('#profileNetworkConnectionsCount');

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
const chatSearchInput = document.querySelector('#chatSearchInput');
const chatFilterButtons = Array.from(document.querySelectorAll('[data-chat-filter]'));
const chatContactsList = document.querySelector('#chatContactsList');
const chatHeaderAvatar = document.querySelector('#chatHeaderAvatar');
const chatHeaderName = document.querySelector('#chatHeaderName');
const chatHeaderMeta = document.querySelector('#chatHeaderMeta');
const chatProfileButton = document.querySelector('#chatProfileButton');
const chatSidebarProfileButton = document.querySelector('#chatSidebarProfileButton');
const chatProfileAvatarLarge = document.querySelector('#chatProfileAvatarLarge');
const chatSidebarName = document.querySelector('#chatSidebarName');
const chatSidebarMeta = document.querySelector('#chatSidebarMeta');
const chatMediaGrid = document.querySelector('#chatMediaGrid');
const chatGroupList = document.querySelector('#chatGroupList');
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
const pfNotificationsButton = document.querySelector('#pf-notifications-btn');
const networkFocusButton = document.querySelector('.network-focus-banner .ghost-button');
const chatEmojiButton = document.querySelector('.msg-emoji-btn');
const pwListView = document.querySelector('#pwListView');
const pwDetailView = document.querySelector('#pwDetailView');
const pwDetailTitle = document.querySelector('#pwDetailTitle');
const pwBackToList = document.querySelector('#pwBackToList');
const pwNewProjectCard = document.querySelector('#pwNewProjectCard');
const pwNewProjectButton = document.querySelector('#pwNewProjectBtn');
const pwShareButton = document.querySelector('#pwDetailView .pw-header-actions .pw-share-btn');
const pwAddColumnButton = document.querySelector('.pw-add-col-btn');
const pwAiInput = document.querySelector('.pw-ai-input');
const pwAiMessages = document.querySelector('.pw-ai-messages');
const pwAiSendButton = document.querySelector('.pw-ai-send');
const pwFilesMoreButton = document.querySelector('.pw-files-more');
const pwFileUploadInput = document.querySelector('#pwFileUpload');
const pwFabButton = document.querySelector('.pw-fab');
const profileCvButton = document.querySelector('.pf-cv-button');
const profileCvAvatar = document.querySelector('#profileCvAvatar');
const profileCvName = document.querySelector('#profileCvName');
const profileCvRole = document.querySelector('#profileCvRole');
const profileCvSummary = document.querySelector('#profileCvSummary');
const profileCvEducation = document.querySelector('#profileCvEducation');
const profileCvFocus = document.querySelector('#profileCvFocus');
const profileCvLocation = document.querySelector('#profileCvLocation');
const profileCvCompletion = document.querySelector('#profileCvCompletion');
const profileCvCompletionBar = document.querySelector('#profileCvCompletionBar');
const profileAffiliationSwitch = document.querySelector('#profileAffiliationSwitch');
const profileAffiliationButtons = Array.from(document.querySelectorAll('[data-affiliation-type]'));
const profileAffiliationSelect = document.querySelector('#profileAffiliationSelect');
const profileAffiliationLinks = document.querySelector('#profileAffiliationLinks');
const profileAffiliationInfo = document.querySelector('#profileAffiliationInfo');
const saveAffiliationButton = document.querySelector('#saveAffiliationButton');

const STUDENTS = {
  me: {
    key: 'me',
    headline: 'Student account',
  },
  tomiris: {
    key: 'tomiris',
    firstName: 'Томирис',
    lastName: 'Назарбекова',
    organization: 'AITU',
    specialty: 'computer-science',
    education: 'bachelor',
    countryFlag: '🇰🇿',
    countryName: 'Казахстан',
    interests: ['ai', 'frontend', 'product'],
    email: 'tomiris@pwe.student',
    bio: 'Backend mentor и частый собеседник по карьерным вопросам. Увлекаюсь AI и стартапами.',
  },
  asem: {
    key: 'asem',
    firstName: 'Асем',
    lastName: 'Серикова',
    organization: 'NU',
    specialty: 'design',
    education: 'bachelor',
    countryFlag: '🇰🇿',
    countryName: 'Казахстан',
    interests: ['frontend', 'design', 'product'],
    email: 'asem@pwe.student',
    bio: 'Frontend friend для совместных pet-project и crit sessions. Специалист по UI/UX.',
  },
  daniyar: {
    key: 'daniyar',
    firstName: 'Данияр',
    lastName: 'Кайратов',
    organization: 'SDU',
    specialty: 'business',
    education: 'master',
    countryFlag: '🇰🇿',
    countryName: 'Казахстан',
    interests: ['business', 'data', 'product'],
    email: 'daniyar@pwe.student',
    bio: 'Business analyst с опытом в стартапах и маркетинге. Помогаю с стратегией.',
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
    title: 'Мероприятия',
    description: 'Сообщество для стартап-идей, питчей и поиска co-founders в кампусе.',
    tags: ['Startups', 'Pitching', 'Founders'],
  },
  'product-circle': {
    title: 'EcoCampus',
    description: 'GreenTech-сообщество с живыми продуктами, пилотами и коллаборациями студентов.',
    tags: ['GreenTech', 'Product', 'Community'],
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
    title: 'Мероприятия',
    text: 'тебя пригласили на pitch practice',
    targetView: 'workspaceView',
    read: false,
  },
  {
    id: 'notif-ai-study',
    title: 'AI Study Circle',
    text: 'добавили новый open session на этой неделе',
    targetView: 'workspaceView',
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
  founders: { label: 'Мероприятия', meta: 'Campus events', studentKey: '' },
  career: { label: 'Career Board', meta: 'Internship matches', studentKey: '' },
};

const CHAT_AUTOREPLIES = {
  tomiris: ['Супер, скинь материалы и я пройдусь по ним.', 'Можешь отправить файл прямо сюда, я посмотрю.', 'Открыла твой запрос, давай соберем next steps.'],
  founders: ['Отлично, закинь deck или видео-питч сюда.', 'Подключили тебя к ближайшему practice slot.', 'Можешь прислать материалы, мы дадим фидбек до созвона.'],
  career: ['Увидел твой отклик, могу прислать еще подходящие роли.', 'Добавил тебе match по еще одной internship позиции.', 'Если хочешь, адаптируем CV под вакансию.'],
};

const AFFILIATION_OPTIONS = {
  study: ['AITU', 'NU', 'KBTU', 'SDU', 'DKU', 'IITU'],
  work: ['Kaspi.kz', 'Beeline Kazakhstan', 'BTS Digital', 'Freedom', 'Yandex Qazaqstan', 'Astana Hub'],
};

const AFFILIATION_ICONS = {
  study: '🎓',
  work: '💼',
};

const AFFILIATION_DETAILS = {
  study: {
    AITU: {
      title: 'AITU',
      description: 'Академия цифрового развития с карьерными программами, хакатонами и практическими треками для студентов IT-направлений.',
      website: 'https://aitu.edu.kz',
      events: ['AITU Career Day', 'Digital Product Meetup'],
      tags: ['ИТ', 'Предпринимательство', 'Стажировки'],
    },
    NU: {
      title: 'Nazarbayev University',
      description: 'Международный университет с программами обмена, научными клубами и карьерным центром для старта в research и tech.',
      website: 'https://nu.edu.kz',
      events: ['Open Day', 'NU Tech Talks'],
      tags: ['Research', 'Exchange', 'Innovation'],
    },
    KBTU: {
      title: 'KBTU',
      description: 'Казахстанско-Британский технический университет со strong связями в финансах и IT и активной карьерной поддержкой.',
      website: 'https://kbtu.kz',
      events: ['Finance Hackathon', 'Career Week'],
      tags: ['Финтех', 'Engineering', 'Карьера'],
    },
    SDU: {
      title: 'SDU',
      description: 'Сулейман Демирель Университет — крупный вуз с широкими программами, партнерскими мероприятиями и поддержкой стартапов.',
      website: 'https://sdu.edu.kz',
      events: ['Startup Weekend', 'Interfaculty Forum'],
      tags: ['Бизнес', 'Технологии', 'Сеть'],
    },
    DKU: {
      title: 'DKU',
      description: 'Американский университет в Казахстане с международными программами, академическим обменом и карьерными событиями.',
      website: 'https://dku.edu.kz',
      events: ['DKU Global Fair', 'Exchange Week'],
      tags: ['International', 'Exchange', 'Media'],
    },
    IITU: {
      title: 'IITU',
      description: 'Институт информационных технологий предлагает глубокие практики, проекты и доступ к корпоративным партнёрам.',
      website: 'https://iitu.edu.kz',
      events: ['DevCamp', 'Corporate Day'],
      tags: ['IT', 'Проекты', 'Стажировки'],
    },
  },
  work: {
    'Kaspi.kz': {
      title: 'Kaspi.kz',
      description: 'Крупнейшая финтех-компания Казахстана с программами стажировок и карьерным треком для молодых специалистов.',
      website: 'https://kaspi.kz',
      events: ['Kaspi Tech Day', 'Campus Talk'],
      tags: ['Fintech', 'Analytics', 'Product'],
    },
    'Beeline Kazakhstan': {
      title: 'Beeline Kazakhstan',
      description: 'Телеком-компания с возможностями в разработке, маркетинге и аналитике, а также партнерскими образовательными проектами.',
      website: 'https://beeline.kz',
      events: ['Beeline Innovation', 'Digital Sprint'],
      tags: ['Telecom', 'Digital', 'Events'],
    },
    'BTS Digital': {
      title: 'BTS Digital',
      description: 'IT-компания с digital-продуктами, где доступны программы для junior-разработчиков и UX-специалистов.',
      website: 'https://btsdigital.kz',
      events: ['Design Jam', 'Developer Meetup'],
      tags: ['Product', 'UX', 'Development'],
    },
    Freedom: {
      title: 'Freedom',
      description: 'Креативное агентство с открытыми вакансиями для молодых специалистов и доступом к коммерческим проектам.',
      website: 'https://freedom.kz',
      events: ['Creative Labs', 'Brand Talks'],
      tags: ['Marketing', 'Design', 'Strategy'],
    },
    'Yandex Qazaqstan': {
      title: 'Yandex Qazaqstan',
      description: 'Региональное подразделение Яндекса с проектами в data science, разработке и мобильных сервисах.',
      website: 'https://yandex.kz',
      events: ['Yandex Intern Day', 'Tech Sprint'],
      tags: ['Data', 'Mobile', 'AI'],
    },
    'Astana Hub': {
      title: 'Astana Hub',
      description: 'IT-парк и экосистема стартапов с событиями, акселераторами и знаниями для молодых профессионалов.',
      website: 'https://astanahub.com',
      events: ['Startup Fest', 'Mentor Session'],
      tags: ['Startup', 'Acceleration', 'Networking'],
    },
  },
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
let activeChatFilter = 'all';
let serverChatCache = {};
let serverFeedItems = [];
let serverFeedPage = 1;
let serverFeedHasMore = true;
let serverFeedLoading = false;
let serverFeedInitialized = false;
let activeEditingPostId = '';
let workspaceSearchDebounceId = 0;
let activeSettingsPanel = 'profile';
let activeScreen = 'register';
let activeViewId = 'workspaceView';
let activeModalId = '';
let activeProjectName = '';
let currentHistoryDepth = 0;
let projectExtraColumnCount = 0;

const getAccounts=()=>{const d={firstName:"Test",lastName:"Student",email:"demo@mendflow.test",password:"demo123"};try{return[d,...JSON.parse(localStorage.getItem(STORAGE_KEYS.accounts)||"[]").filter(a=>a?.email&&a.email!=d.email)]}catch{return[d]}};

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

const getTaskOwnerKey = () => {
  const account = getAccount();
  return account?.email || 'guest';
};

const getSidebarTasks = () => {
  const raw = window.localStorage.getItem(STORAGE_KEYS.tasks);
  if (!raw) return [];
  try {
    const parsed = JSON.parse(raw) || {};
    return Array.isArray(parsed[getTaskOwnerKey()]) ? parsed[getTaskOwnerKey()] : [];
  } catch {
    return [];
  }
};

const saveSidebarTasks = (tasks) => {
  let parsed = {};
  try {
    parsed = JSON.parse(window.localStorage.getItem(STORAGE_KEYS.tasks) || '{}') || {};
  } catch {
    parsed = {};
  }
  parsed[getTaskOwnerKey()] = tasks;
  window.localStorage.setItem(STORAGE_KEYS.tasks, JSON.stringify(parsed));
};

const renderSidebarTasks = () => {
  if (!sidebarTaskList) return;
  const tasks = getSidebarTasks();

  if (!tasks.length) {
    sidebarTaskList.innerHTML = '<p class="sidebar-task-empty">Добавь задачу, и она появится здесь. Выполненное можно сразу вычеркнуть.</p>';
    return;
  }

  sidebarTaskList.innerHTML = tasks.map((task) => `
    <div class="sidebar-task-item ${task.done ? 'is-complete' : ''}" data-task-id="${escapeHtml(task.id)}">
      <button class="sidebar-task-toggle ${task.done ? 'is-complete' : ''}" type="button" data-task-toggle="${escapeHtml(task.id)}" aria-label="${task.done ? 'Вернуть задачу' : 'Отметить задачу выполненной'}"></button>
      <span class="sidebar-task-text">${escapeHtml(task.text)}</span>
      <button class="sidebar-task-delete" type="button" data-task-delete="${escapeHtml(task.id)}" aria-label="Удалить задачу">×</button>
    </div>
  `).join('');

  sidebarTaskList.querySelectorAll('[data-task-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const taskId = button.dataset.taskToggle || '';
      const nextTasks = getSidebarTasks().map((task) => (
        task.id === taskId ? { ...task, done: !task.done } : task
      ));
      saveSidebarTasks(nextTasks);
      renderSidebarTasks();
    });
  });

  sidebarTaskList.querySelectorAll('[data-task-delete]').forEach((button) => {
    button.addEventListener('click', () => {
      const taskId = button.dataset.taskDelete || '';
      const nextTasks = getSidebarTasks().filter((task) => task.id !== taskId);
      saveSidebarTasks(nextTasks);
      renderSidebarTasks();
    });
  });
};

const addSidebarTask = (text) => {
  const normalized = text.trim();
  if (!normalized) return false;
  const nextTasks = [
    {
      id: `task-${Date.now()}`,
      text: normalized,
      done: false,
    },
    ...getSidebarTasks(),
  ];
  saveSidebarTasks(nextTasks);
  renderSidebarTasks();
  return true;
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

const getCurrentStudentKey = () => {
  const account = getAccount();
  return account?.email ? getAccountChatId(account.email) : 'me';
};

const normalizeStudentKey = (studentKey = 'me') => (
  !studentKey || studentKey === 'me' ? getCurrentStudentKey() : studentKey
);

const getFriendships = () => {
  const raw = window.localStorage.getItem(STORAGE_KEYS.friendships);
  if (!raw) return {};
  try {
    return JSON.parse(raw) || {};
  } catch {
    return {};
  }
};

const saveFriendships = (friendships) => {
  window.localStorage.setItem(STORAGE_KEYS.friendships, JSON.stringify(friendships));
};

const ensureFriendshipBucket = (friendships, studentKey) => {
  const key = normalizeStudentKey(studentKey);
  if (!friendships[key]) {
    friendships[key] = {
      friends: [],
      outgoing: [],
      incoming: [],
    };
  }
  friendships[key].friends = Array.from(new Set(friendships[key].friends || []));
  friendships[key].outgoing = Array.from(new Set(friendships[key].outgoing || []));
  friendships[key].incoming = Array.from(new Set(friendships[key].incoming || []));
  return friendships[key];
};

const updateLegacyFriendsCache = (friendships = getFriendships()) => {
  const bucket = ensureFriendshipBucket(friendships, getCurrentStudentKey());
  saveFriends(bucket.friends || []);
};

const getFriendshipStatus = (studentKey) => {
  const targetKey = normalizeStudentKey(studentKey);
  const currentKey = getCurrentStudentKey();
  if (targetKey === currentKey) return 'self';

  const friendships = getFriendships();
  const currentBucket = ensureFriendshipBucket(friendships, currentKey);
  if (currentBucket.friends.includes(targetKey)) return 'friends';
  if (currentBucket.outgoing.includes(targetKey)) return 'outgoing';
  if (currentBucket.incoming.includes(targetKey)) return 'incoming';
  return 'none';
};

const buildStudentNotification = ({
  studentKey = '',
  actorKey = '',
  recipientKey = '',
  title = '',
  text = '',
  type = 'info',
}) => ({
  id: `${type}-${studentKey}-${Date.now()}-${Math.random().toString(16).slice(2, 8)}`,
  title,
  text,
  type,
  actorKey,
  recipientKey,
  targetView: 'clientProfileView',
  targetStudentKey: studentKey,
  read: false,
});

const prependNotification = (notification) => {
  const notifications = getAllNotifications();
  saveAllNotifications([notification, ...notifications]);
};

const sendFriendRequest = (studentKey) => {
  const currentKey = getCurrentStudentKey();
  const targetKey = normalizeStudentKey(studentKey);
  if (!targetKey || targetKey === currentKey) return false;

  const friendships = getFriendships();
  const currentBucket = ensureFriendshipBucket(friendships, currentKey);
  const targetBucket = ensureFriendshipBucket(friendships, targetKey);
  if (
    currentBucket.friends.includes(targetKey)
    || currentBucket.outgoing.includes(targetKey)
    || currentBucket.incoming.includes(targetKey)
  ) {
    return false;
  }

  currentBucket.outgoing.push(targetKey);
  targetBucket.incoming.push(currentKey);
  saveFriendships(friendships);
  updateLegacyFriendsCache(friendships);

  prependNotification(buildStudentNotification({
    studentKey: targetKey,
    actorKey: currentKey,
    recipientKey: currentKey,
    title: getStudentName(targetKey),
    text: 'Заявка отправлена. Теперь ждём подтверждения второй стороны.',
    type: 'friend-request-sent',
  }));
  prependNotification(buildStudentNotification({
    studentKey: currentKey,
    actorKey: currentKey,
    recipientKey: targetKey,
    title: getStudentName(currentKey),
    text: 'Хочет добавить тебя в друзья. Открой уведомление и подтверди заявку.',
    type: 'friend-request-received',
  }));
  return true;
};

const acceptFriendRequest = (studentKey) => {
  const currentKey = getCurrentStudentKey();
  const targetKey = normalizeStudentKey(studentKey);
  if (!targetKey || targetKey === currentKey) return false;

  const friendships = getFriendships();
  const currentBucket = ensureFriendshipBucket(friendships, currentKey);
  const targetBucket = ensureFriendshipBucket(friendships, targetKey);
  if (!currentBucket.incoming.includes(targetKey)) return false;

  currentBucket.incoming = currentBucket.incoming.filter((key) => key !== targetKey);
  targetBucket.outgoing = targetBucket.outgoing.filter((key) => key !== currentKey);
  currentBucket.friends.push(targetKey);
  targetBucket.friends.push(currentKey);

  saveFriendships(friendships);
  updateLegacyFriendsCache(friendships);

  prependNotification(buildStudentNotification({
    studentKey: targetKey,
    actorKey: currentKey,
    recipientKey: currentKey,
    title: getStudentName(targetKey),
    text: 'Заявка подтверждена. Теперь вы у друг друга в друзьях.',
    type: 'friend-accept',
  }));
  prependNotification(buildStudentNotification({
    studentKey: currentKey,
    actorKey: currentKey,
    recipientKey: targetKey,
    title: getStudentName(currentKey),
    text: 'Принял(а) твою заявку. Теперь вы у друг друга в друзьях.',
    type: 'friend-accept',
  }));
  return true;
};

const cancelFriendRequest = (studentKey) => {
  const currentKey = getCurrentStudentKey();
  const targetKey = normalizeStudentKey(studentKey);
  if (!targetKey || targetKey === currentKey) return false;

  const friendships = getFriendships();
  const currentBucket = ensureFriendshipBucket(friendships, currentKey);
  const targetBucket = ensureFriendshipBucket(friendships, targetKey);
  const changed = currentBucket.outgoing.includes(targetKey) || currentBucket.incoming.includes(targetKey);
  if (!changed) return false;

  currentBucket.outgoing = currentBucket.outgoing.filter((key) => key !== targetKey);
  currentBucket.incoming = currentBucket.incoming.filter((key) => key !== targetKey);
  targetBucket.outgoing = targetBucket.outgoing.filter((key) => key !== currentKey);
  targetBucket.incoming = targetBucket.incoming.filter((key) => key !== currentKey);

  saveFriendships(friendships);
  updateLegacyFriendsCache(friendships);
  return true;
};

const getFriendshipLists = (studentKey = getCurrentStudentKey()) => {
  const friendships = getFriendships();
  const bucket = ensureFriendshipBucket(friendships, studentKey);
  return {
    friends: bucket.friends.slice(),
    outgoing: bucket.outgoing.slice(),
    incoming: bucket.incoming.slice(),
  };
};

const getNotifications = () => {
  const raw = window.localStorage.getItem(STORAGE_KEYS.notifications);
  const currentKey = getCurrentStudentKey();
  if (!raw) {
    return structuredClone(DEFAULT_NOTIFICATIONS).filter((notification) => (
      !notification.recipientKey || normalizeStudentKey(notification.recipientKey) === currentKey
    ));
  }
  try {
    return (JSON.parse(raw) || []).filter((notification) => (
      !notification?.recipientKey || normalizeStudentKey(notification.recipientKey) === currentKey
    ));
  } catch {
    return structuredClone(DEFAULT_NOTIFICATIONS).filter((notification) => (
      !notification.recipientKey || normalizeStudentKey(notification.recipientKey) === currentKey
    ));
  }
};

const saveNotifications = (notifications) => {
  window.localStorage.setItem(STORAGE_KEYS.notifications, JSON.stringify(notifications));
};

const getAllNotifications = () => {
  const raw = window.localStorage.getItem(STORAGE_KEYS.notifications);
  if (!raw) return structuredClone(DEFAULT_NOTIFICATIONS);
  try {
    return JSON.parse(raw) || [];
  } catch {
    return structuredClone(DEFAULT_NOTIFICATIONS);
  }
};

const saveAllNotifications = (notifications) => {
  saveNotifications(notifications);
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

const modalRegistry = {
  inboxModal,
  postModal,
  commentsModal,
  communityModal,
  profileSettingsModal,
};

const modalElements = Object.values(modalRegistry).filter(Boolean);

const normalizeViewId = (viewId = 'workspaceView') => {
  if (viewId === 'communitiesView') return 'connectView';
  return document.getElementById(viewId) ? viewId : 'workspaceView';
};

const canOpenAppShell = () => {
  const account = getAccount();
  const session = getSession();
  return Boolean(account && session && session === account.email);
};

const renderProjectWorkspace = ({ open = false, name = 'Рабочее пространство' } = {}) => {
  if (!pwListView || !pwDetailView) return;
  pwListView.classList.toggle('is-hidden', open);
  pwDetailView.classList.toggle('is-hidden', !open);
  if (pwDetailTitle) {
    pwDetailTitle.textContent = name || 'Рабочее пространство';
  }
  activeProjectName = open ? (name || 'Рабочее пространство') : '';
};

const syncModalVisibility = (modalId = '') => {
  modalElements.forEach((element) => {
    element.classList.toggle('is-hidden', element.id !== modalId);
  });
};

const goToRegStep = (step) => {
  const stepCardIds = ['regCardStep1', 'regCardStep2', 'regCardStep3'];
  stepCardIds.forEach((id, i) => {
    const el = document.querySelector(`#${id}`);
    if (el) el.classList.toggle('is-hidden', i + 1 !== step);
  });
  const stepEls = [
    document.querySelector('#regStep1'),
    document.querySelector('#regStep2'),
    document.querySelector('#regStep3'),
  ];
  stepEls.forEach((el, i) => {
    if (!el) return;
    el.classList.remove('reg-step-active', 'reg-step-done');
    if (i + 1 === step) el.classList.add('reg-step-active');
    else if (i + 1 < step) el.classList.add('reg-step-done');
  });
  const line1 = document.querySelector('#regStepLine1');
  const line2 = document.querySelector('#regStepLine2');
  if (line1) line1.classList.toggle('reg-line-done', step > 1);
  if (line2) line2.classList.toggle('reg-line-done', step > 2);
};

const renderRegisterScreen = () => {
  registerScreen.classList.add('auth-screen-active');
  loginScreen.classList.remove('auth-screen-active');
  appShell.classList.add('is-hidden');
  activeScreen = 'register';
  activeModalId = '';
  syncModalVisibility('');
  goToRegStep(1);
};

const renderLoginScreen = () => {
  registerScreen.classList.remove('auth-screen-active');
  loginScreen.classList.add('auth-screen-active');
  appShell.classList.add('is-hidden');
  activeScreen = 'login';
  activeModalId = '';
  syncModalVisibility('');
};

const setActiveSwitcher = (viewId) => {
  viewSwitchers.forEach((button) => {
    button.classList.toggle('is-active', button.dataset.viewTarget === viewId);
  });
};

const renderAppView = (viewId = 'workspaceView') => {
  const nextViewId = normalizeViewId(viewId);
  registerScreen.classList.remove('auth-screen-active');
  loginScreen.classList.remove('auth-screen-active');
  appShell.classList.remove('is-hidden');
  appViews.forEach((view) => {
    view.classList.toggle('app-view-active', view.id === nextViewId);
  });
  setActiveSwitcher(nextViewId);
  activeScreen = 'app';
  activeViewId = nextViewId;
};

const getCurrentNavigationState = () => ({
  screen: activeScreen,
  viewId: normalizeViewId(activeViewId),
  viewedStudentKey,
  activeProfileTab,
  activeChatId,
  modalId: activeModalId,
  projectOpen: activeViewId === 'projectsView' && Boolean(pwDetailView && !pwDetailView.classList.contains('is-hidden')),
  projectName: activeProjectName,
  depth: currentHistoryDepth,
});

const normalizeNavigationState = (state = {}) => {
  const accountExists = Boolean(getAccount());
  let screen = state.screen === 'register' || state.screen === 'login' ? state.screen : 'app';
  const viewId = normalizeViewId(state.viewId || activeViewId || 'workspaceView');

  if (screen === 'app' && !canOpenAppShell()) {
    screen = accountExists ? 'login' : 'register';
  }

  return {
    screen,
    viewId,
    viewedStudentKey: state.viewedStudentKey || viewedStudentKey || 'me',
    activeProfileTab: state.activeProfileTab || activeProfileTab || 'posts',
    activeChatId: state.activeChatId || activeChatId || 'tomiris',
    modalId: state.modalId && modalRegistry[state.modalId] ? state.modalId : '',
    projectOpen: Boolean(state.projectOpen) && viewId === 'projectsView',
    projectName: state.projectName || activeProjectName || 'Рабочее пространство',
    depth: Number.isFinite(Number(state.depth)) ? Number(state.depth) : currentHistoryDepth,
  };
};

const navigationStateSignature = (state) => JSON.stringify({
  screen: state.screen,
  viewId: state.viewId,
  viewedStudentKey: state.viewedStudentKey,
  activeProfileTab: state.activeProfileTab,
  activeChatId: state.activeChatId,
  modalId: state.modalId,
  projectOpen: state.projectOpen,
  projectName: state.projectName,
});

const buildHistoryUrl = (state) => {
  const params = new URLSearchParams();
  if (state.screen !== 'app') {
    params.set('screen', state.screen);
  } else {
    params.set('view', state.viewId);
    if (state.viewId === 'clientProfileView') {
      params.set('student', state.viewedStudentKey || 'me');
      params.set('tab', state.activeProfileTab || 'posts');
    }
    if (state.viewId === 'chatsView' && state.activeChatId) {
      params.set('chat', state.activeChatId);
    }
    if (state.viewId === 'projectsView' && state.projectOpen) {
      params.set('project', state.projectName || 'Рабочее пространство');
    }
  }
  if (state.modalId) {
    params.set('modal', state.modalId);
  }
  const hash = params.toString();
  return `${window.location.pathname}${window.location.search}${hash ? `#${hash}` : ''}`;
};

const parseNavigationStateFromHash = () => {
  const hash = window.location.hash.replace(/^#/, '');
  if (!hash) return null;
  const params = new URLSearchParams(hash);
  const screen = params.get('screen');
  const view = params.get('view');
  return normalizeNavigationState({
    screen: screen || 'app',
    viewId: view || 'workspaceView',
    viewedStudentKey: params.get('student') || undefined,
    activeProfileTab: params.get('tab') || undefined,
    activeChatId: params.get('chat') || undefined,
    modalId: params.get('modal') || undefined,
    projectOpen: params.has('project'),
    projectName: params.get('project') || undefined,
  });
};

const applyNavigationState = (incomingState, { replace = false, fromHistory = false } = {}) => {
  const nextState = normalizeNavigationState(incomingState);
  const currentState = normalizeNavigationState(getCurrentNavigationState());
  const stateChanged = navigationStateSignature(nextState) !== navigationStateSignature(currentState);

  viewedStudentKey = nextState.viewedStudentKey;
  activeProfileTab = nextState.activeProfileTab;
  activeChatId = nextState.activeChatId;

  if (nextState.screen === 'register') {
    renderRegisterScreen();
  } else if (nextState.screen === 'login') {
    renderLoginScreen();
  } else {
    renderAppView(nextState.viewId);
    renderProjectWorkspace({
      open: nextState.projectOpen,
      name: nextState.projectName,
    });
  }

  if (nextState.screen !== 'app') {
    renderProjectWorkspace({ open: false });
  }

  activeModalId = nextState.modalId;
  syncModalVisibility(activeModalId);

  if (nextState.viewId === 'clientProfileView' || nextState.viewedStudentKey !== currentState.viewedStudentKey) {
    renderProfilePanels();
  }

  if (nextState.viewId === 'chatsView') {
    renderLiveChat();
    loadServerChat(activeChatId);
  }

  setActiveProfileTab(nextState.activeProfileTab);

  if (fromHistory) {
    currentHistoryDepth = nextState.depth;
    return;
  }

  if (!stateChanged && !replace) return;

  const depth = replace ? currentHistoryDepth : currentHistoryDepth + 1;
  const historyState = { ...nextState, depth };
  currentHistoryDepth = depth;
  window.history[replace ? 'replaceState' : 'pushState'](historyState, '', buildHistoryUrl(historyState));
};

const navigateTo = (partialState = {}, options = {}) => {
  applyNavigationState({
    ...getCurrentNavigationState(),
    ...partialState,
  }, options);
};

const goBack = (fallbackState = { screen: 'app', viewId: 'workspaceView', modalId: '' }) => {
  if (currentHistoryDepth > 0) {
    window.history.back();
    return;
  }
  applyNavigationState({
    ...getCurrentNavigationState(),
    ...fallbackState,
  }, { replace: true });
};

const showRegisterScreen = (options = {}) => {
  navigateTo({ screen: 'register', modalId: '' }, options);
};

const showLoginScreen = (options = {}) => {
  navigateTo({ screen: 'login', modalId: '' }, options);
};

const showAppView = (viewId = 'workspaceView', options = {}) => {
  navigateTo({ screen: 'app', viewId: normalizeViewId(viewId), modalId: '' }, options);
};

const openModal = (element, options = {}) => {
  if (!element) return;
  navigateTo({ modalId: element.id }, options);
};

const closeModal = (element, options = {}) => {
  if (!element) return;
  if (options.skipHistory) {
    activeModalId = '';
    element.classList.add('is-hidden');
    if (!options.silentStateSync) {
      applyNavigationState({
        ...getCurrentNavigationState(),
        modalId: '',
      }, { replace: true });
    }
    return;
  }
  if (activeModalId === element.id) {
    goBack({ ...getCurrentNavigationState(), modalId: '' });
    return;
  }
  element.classList.add('is-hidden');
};

const showActionNotice = (() => {
  let timeoutId = 0;
  let notice = null;

  return (message) => {
    if (!notice) {
      notice = document.createElement('div');
      notice.id = 'appActionNotice';
      Object.assign(notice.style, {
        position: 'fixed',
        left: '50%',
        bottom: '24px',
        transform: 'translateX(-50%)',
        padding: '12px 16px',
        borderRadius: '999px',
        background: 'rgba(22, 19, 29, 0.92)',
        color: '#fff',
        fontSize: '14px',
        fontWeight: '600',
        zIndex: '9999',
        opacity: '0',
        pointerEvents: 'none',
        transition: 'opacity 180ms ease',
        boxShadow: '0 10px 30px rgba(22, 19, 29, 0.18)',
      });
      document.body.appendChild(notice);
    }

    notice.textContent = message;
    notice.style.opacity = '1';
    window.clearTimeout(timeoutId);
    timeoutId = window.setTimeout(() => {
      if (notice) {
        notice.style.opacity = '0';
      }
    }, 2200);
  };
})();

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
      accentColor: profile.style?.accentColor || account.style?.accentColor || '#7c3aed',
      cardColor: profile.style?.cardColor || account.style?.cardColor || '#ffffff',
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

const isStudentMatch = (studentKey, targetKey) => normalizeStudentKey(studentKey) === normalizeStudentKey(targetKey);

const isPostOwnedByStudent = (post, studentKey) => {
  const authorKey = post.authorKey || 'me';
  if (studentKey === 'me') {
    return authorKey === 'me' || isStudentMatch(authorKey, getCurrentStudentKey());
  }
  return isStudentMatch(authorKey, studentKey);
};

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
    repostsCount: Number(post.repostsCount || 0),
    repostedByMe: Boolean(post.repostedByMe),
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
    isRepost: Boolean(post.isRepost),
    repostOfId: post.repostOfId ? String(post.repostOfId) : '',
    repostAuthorKey: post.repostAuthorKey || '',
    repostPreview: post.repostPreview || '',
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
      organization: account.organization || 'Не указано',
      specialty: account.specialty || 'Не выбрано',
      education: account.education || 'Не выбрано',
      countryFlag: account.countryFlag || '🌍',
      countryName: account.countryName || 'Не указано',
      interests: account.interests || [],
      email: account.email,
      photo: account.photo || '',
      style: account.style || {},
      bio: account.bio || 'Добавь информацию о себе в настройках профиля.',
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
  const accent = student?.style?.accentColor || '#7c3aed';
  const card = student?.style?.cardColor || '#ffffff';
  const font = student?.style?.fontTheme || 'manrope';
  const fontFamily = font === 'fraunces'
    ? '"Neue Kabel", "Neue Kabel Black", "Avenir Next", "Helvetica Neue", sans-serif'
    : font === 'mono'
      ? '"SFMono-Regular", Consolas, monospace'
      : '"Plus Jakarta Sans", sans-serif';
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
  interestInputs.forEach((input, index) => {
    input.value = account.interests?.[index] || '';
  });
};

const syncProfileSettingsModal = (account) => {
  if (!account || !profileSettingsForm) return;
  const fullName = `${account.firstName} ${account.lastName}`.trim();
  profileSettingsOrganizationInput.value = account.organization || '';
  profileSettingsSpecialtyInput.value = account.specialty || '';
  profileSettingsEducationInput.value = account.education || '';
  profileSettingsCountryInput.value = account.countryValue || '';
  if (profileSettingsBioInput) profileSettingsBioInput.value = account.bio || '';
  profileSettingsInterestInputs.forEach((input, index) => {
    input.value = account.interests?.[index] || '';
  });
  if (profileSettingsName) profileSettingsName.textContent = fullName;
  if (profileSettingsEmail) profileSettingsEmail.textContent = account.email || '';
  if (profileSecurityEmail) profileSecurityEmail.value = account.email || '';
  applyAvatar(profileSettingsAvatar, getInitials(fullName), account.photo || '');
};

const getAffiliationType = (account = getAccount()) => {
  if (!account) return 'study';
  if (account.affiliationType === 'work' || account.affiliationType === 'study') {
    return account.affiliationType;
  }
  const organization = String(account.organization || '').toLowerCase();
  return AFFILIATION_OPTIONS.work.some((item) => item.toLowerCase() === organization) ? 'work' : 'study';
};

const renderAffiliationOptions = (type = 'study', selectedValue = '') => {
  if (!profileAffiliationSelect) return;
  const options = AFFILIATION_OPTIONS[type] || AFFILIATION_OPTIONS.study;
  profileAffiliationSelect.innerHTML = [
    `<option value="">${type === 'work' ? 'Выбери компанию' : 'Выбери университет'}</option>`,
    ...options.map((item) => `<option value="${escapeHtml(item)}">${escapeHtml(item)}</option>`),
  ].join('');
  profileAffiliationSelect.value = options.includes(selectedValue) ? selectedValue : '';
};

const renderAffiliationInfo = (type = 'study', selectedValue = '') => {
  if (!profileAffiliationInfo) return;
  const label = String(selectedValue || '').trim();
  const details = AFFILIATION_DETAILS[type]?.[label];
  if (!details) {
    profileAffiliationInfo.innerHTML = '';
    profileAffiliationInfo.classList.add('is-hidden');
    return;
  }

  const sections = [];
  const title = escapeHtml(details.title);
  const description = String(details.description || '').trim();
  const website = String(details.website || '').trim();
  const events = Array.isArray(details.events) ? details.events.filter(Boolean) : [];
  const tags = Array.isArray(details.tags) ? details.tags.filter(Boolean) : [];

  if (description) {
    sections.push(`
      <div class="pf-affiliation-section">
        <h5>Об организации</h5>
        <p>${escapeHtml(description)}</p>
      </div>
    `);
  }

  if (website) {
    sections.push(`
      <div class="pf-affiliation-section">
        <h5>Официальный сайт</h5>
        <a href="${escapeHtml(website)}" target="_blank" rel="noopener noreferrer">${escapeHtml(website)}</a>
      </div>
    `);
  }

  if (events.length) {
    sections.push(`
      <div class="pf-affiliation-section">
        <h5>Ближайшие мероприятия</h5>
        <p>${escapeHtml(events.join(', '))}</p>
      </div>
    `);
  }

  if (tags.length) {
    sections.push(`
      <div class="pf-affiliation-section">
        <h5>Направления</h5>
        <div class="pf-affiliation-tags">
          ${tags.map((tag) => `<span class="pf-affiliation-tag">${escapeHtml(tag)}</span>`).join('')}
        </div>
      </div>
    `);
  }

  const icon = AFFILIATION_ICONS[type] || '';
  const metaLabel = type === 'work' ? 'Компания' : 'Университет';

  profileAffiliationInfo.classList.remove('is-hidden');
  profileAffiliationInfo.innerHTML = `
    <div class="pf-affiliation-card-header">
      <div class="pf-affiliation-card-icon">${escapeHtml(icon)}</div>
      <div>
        <h4>${title}</h4>
        <span class="pf-affiliation-card-meta">${metaLabel}</span>
      </div>
    </div>
    ${sections.join('')}
  `;
};

const renderAffiliationLinks = (type = 'study', selectedValue = '') => {
  if (!profileAffiliationLinks) return;
  const label = String(selectedValue || '').trim();
  if (!label) {
    profileAffiliationLinks.innerHTML = '';
    renderAffiliationInfo(type, '');
    return;
  }
  const icon = AFFILIATION_ICONS[type] || '';
  if (type === 'work') {
    profileAffiliationLinks.innerHTML = `
      <button class="ghost-button" type="button" data-affiliation-link="company">${escapeHtml(icon)} ${escapeHtml(label)}</button>
      <button class="ghost-button" type="button" data-affiliation-link="events">Мероприятия</button>
    `;
  } else {
    profileAffiliationLinks.innerHTML = `
      <button class="ghost-button" type="button" data-affiliation-link="university">${escapeHtml(icon)} ${escapeHtml(label)}</button>
      <button class="ghost-button" type="button" data-affiliation-link="exchange">Акад. обмен</button>
      <button class="ghost-button" type="button" data-affiliation-link="events">Мероприятия</button>
    `;
  }

  renderAffiliationInfo(type, label);

  profileAffiliationLinks.querySelectorAll('[data-affiliation-link]').forEach((button) => {
    button.addEventListener('click', () => {
      const action = button.dataset.affiliationLink || '';
      const selectedText = profileAffiliationSelect?.value.trim() || selectedValue || '';

      if (action === 'company' && selectedText) {
        const entity = NETWORK_ENTITIES.find((item) => item.type === 'companies' && item.title === selectedText);
        if (entity) {
          handleNetworkEntityAction(entity.id);
          return;
        }
        showAppView('connectView');
        activeNetworkFilter = 'companies';
        networkFilterButtons.forEach((item) => item.classList.toggle('is-active', item.dataset.networkFilter === 'companies'));
        connectSearchInput.value = selectedText;
        renderConnectResults();
        bindDynamicInteractions();
        showActionNotice(`Открыли страницу компании ${selectedText}.`);
        return;
      }

      if (action === 'university' && selectedText) {
        const entity = NETWORK_ENTITIES.find((item) => item.type === 'universities' && item.title === selectedText);
        if (entity) {
          handleNetworkEntityAction(entity.id);
          return;
        }
        showAppView('connectView');
        activeNetworkFilter = 'universities';
        networkFilterButtons.forEach((item) => item.classList.toggle('is-active', item.dataset.networkFilter === 'universities'));
        connectSearchInput.value = selectedText;
        renderConnectResults();
        bindDynamicInteractions();
        showActionNotice(`Открыли страницу университета ${selectedText}.`);
        return;
      }

      if (action === 'exchange') {
        showAppView('connectView');
        activeNetworkFilter = 'universities';
        networkFilterButtons.forEach((item) => item.classList.toggle('is-active', item.dataset.networkFilter === 'universities'));
        connectSearchInput.value = '';
        renderConnectResults();
        bindDynamicInteractions();
        showActionNotice('Открыли возможности академического обмена и университетские связи.');
        return;
      }

      if (action === 'events') {
        showAppView('communitiesView');
        showActionNotice('Открыли мероприятия и сообщества.');
      }
    });
  });
};

const syncAffiliationCard = (account = getAccount()) => {
  const type = getAffiliationType(account);
  profileAffiliationButtons.forEach((button) => {
    button.classList.toggle('is-active', button.dataset.affiliationType === type);
  });
  renderAffiliationOptions(type, account?.organization || '');
  renderAffiliationLinks(type, account?.organization || '');
};

const setActiveSettingsPanel = (panelId = 'profile') => {
  activeSettingsPanel = panelId === 'security' ? 'security' : 'profile';
  profileSettingsNavButtons.forEach((button) => {
    button.classList.toggle('is-active', button.dataset.settingsPanel === activeSettingsPanel);
  });
  profileSettingsPanels.forEach((panel) => {
    panel.classList.toggle('profile-settings-panel-active', panel.dataset.settingsPanelContent === activeSettingsPanel);
  });
};

const collectProfileDraft = ({ fromModal = false } = {}) => {
  const account = getAccount();
  if (!account) return null;
  const countrySource = fromModal ? profileSettingsCountryInput : profileCountryInput;
  const organizationSource = fromModal ? profileSettingsOrganizationInput : profileOrganizationInput;
  const specialtySource = fromModal ? profileSettingsSpecialtyInput : profileSpecialtyInput;
  const educationSource = fromModal ? profileSettingsEducationInput : profileEducationInput;
  const bioSource = fromModal ? profileSettingsBioInput : null;
  const interestSource = fromModal ? profileSettingsInterestInputs : interestInputs;
  const [countryFlag = '', countryName = ''] = String(countrySource.value).split('|');

  return {
    ...account,
    organization: organizationSource.value.trim(),
    specialty: specialtySource.value,
    education: educationSource.value,
    countryValue: countrySource.value,
    countryFlag,
    countryName,
    bio: bioSource ? bioSource.value.trim() : (account.bio || ''),
    interests: interestSource.map((input) => input.value).filter(Boolean).slice(0, 3),
    style: {
      fontTheme: 'manrope',
      accentColor: '#7c3aed',
      cardColor: '#ffffff',
    },
  };
};

const persistProfileDraft = async (nextAccount) => {
  if (!nextAccount) return;

  if (isHostedMode()) {
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
    syncProfileSettingsModal(mergedAccount);
    await syncRemoteUsers();
    return;
  }

  saveAccount(nextAccount);
  hydrateUser(nextAccount);
  syncProfileSettingsModal(nextAccount);
};

const updateStrengthUI = (account) => {
  const score = calculateStrength(account);
  if (profileStrength) {
    profileStrength.textContent = String(score);
  }
  if (profileStrengthText) {
    profileStrengthText.textContent = score >= 80
      ? 'Профиль выглядит сильно: у тебя уже есть учебные данные, интересы, стиль и фото.'
      : 'Добавь организацию, специальность, интересы, страну, стиль и фото, чтобы профиль стал заметнее.';
  }
  if (sidebarStrengthCard) {
    sidebarStrengthCard.classList.toggle('is-hidden', score >= 100);
  }
};

const updateCvPreview = (account) => {
  if (!account || !profileCvName) return;
  const fullName = `${account.firstName} ${account.lastName}`.trim() || 'Student';
  const specialtyText = specialtyLabel(account.specialty);
  const educationText = educationLabel(account.education);
  const interests = (account.interests || []).filter(Boolean).map(interestLabel);
  const focusText = interests.length ? interests.join(', ') : 'Выбери интересы';
  const locationText = account.countryName ? `${account.countryFlag ? `${account.countryFlag} ` : ''}${account.countryName}` : 'не указана';
  const educationLine = account.organization || account.specialty
    ? [account.organization || 'Организация не указана', specialtyText !== 'Пока не указано' ? specialtyText : '', educationText].filter(Boolean).join(' • ')
    : 'пока не заполнено';
  const score = calculateStrength(account);

  profileCvName.textContent = fullName;
  profileCvRole.textContent = specialtyText !== 'Пока не указано'
    ? `${specialtyText}${educationText ? ` • ${educationText}` : ''}`
    : 'Student profile is building automatically';
  profileCvEducation.textContent = `Образование: ${educationLine}`;
  profileCvFocus.textContent = `Фокус: ${focusText}`;
  profileCvLocation.textContent = `Локация: ${locationText}`;
  profileCvSummary.textContent = account.organization || interests.length || account.countryName
    ? `${fullName} развивает профиль в направлении ${focusText.toLowerCase()}. CV будет автоматически усиливаться за счет учебных данных, активности в ленте, проектов и новых достижений на платформе.`
    : 'Профиль пока собирает базовые данные. Добавь учебную организацию, специальность, интересы и страну, чтобы система начала собирать сильное CV автоматически.';
  profileCvCompletion.textContent = `${score}%`;
  if (profileCvCompletionBar) {
    profileCvCompletionBar.style.width = `${score}%`;
  }
  applyAvatar(profileCvAvatar, getInitials(fullName), account.photo || '');
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

const getNotificationActionsMarkup = (notification) => {
  if (notification.type === 'friend-request-received' && notification.actorKey) {
    const status = getFriendshipStatus(notification.actorKey);
    if (status === 'incoming') {
      return `
        <div class="thread-footer">
          <button type="button" class="primary-button" data-accept-friend-request="${escapeHtml(notification.actorKey)}">Принять</button>
          <button type="button" class="ghost-button" data-cancel-friend-request="${escapeHtml(notification.actorKey)}">Отклонить</button>
        </div>
      `;
    }
  }

  if (notification.type === 'friend-request-sent' && notification.targetStudentKey) {
    const status = getFriendshipStatus(notification.targetStudentKey);
    if (status === 'outgoing') {
      return `
        <div class="thread-footer">
          <button type="button" class="ghost-button" data-cancel-friend-request="${escapeHtml(notification.targetStudentKey)}">Отменить заявку</button>
        </div>
      `;
    }
  }

  return '';
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

const getFriendActionButtonLabel = (studentKey) => ({
  self: '',
  none: 'Отправить заявку',
  outgoing: 'Заявка отправлена',
  incoming: 'Принять заявку',
  friends: 'В друзьях',
}[getFriendshipStatus(studentKey)] || 'Отправить заявку');

const renderFriendActionButton = (studentKey, className = 'primary-button wide') => {
  const status = getFriendshipStatus(studentKey);
  if (status === 'self') return '';
  const disabled = status === 'friends';
  return `<button type="button" class="${className}" data-friend-action="${escapeHtml(studentKey)}" ${disabled ? 'disabled' : ''}>${escapeHtml(getFriendActionButtonLabel(studentKey))}</button>`;
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
  const repostCount = Number(post.repostsCount || 0);
  const repostButtonLabel = post.repostedByMe ? 'Репостнуто' : 'Репост';
  const repostMeta = post.isRepost
    ? `<div class="attachment-pill">Репост из ленты${post.repostPreview ? `: ${escapeHtml(post.repostPreview)}` : ''}</div>`
    : '';
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
      ${repostMeta}
      ${attachmentMarkup}
      <div class="thread-tags">
        ${(post.tags || []).map((tag) => `<span>#${escapeHtml(interestLabel(tag).toLowerCase())}</span>`).join('')}
      </div>
      <div class="thread-footer social-actions">
        <button type="button" class="like-button ${liked ? 'is-active' : ''}" data-like-post="${escapeHtml(post.id)}">${liked ? 'Убрать лайк' : 'Лайк'} ${post.likes || 0}</button>
        <button type="button" class="comment-toggle-button" data-comment-post="${escapeHtml(post.id)}">Комментарии ${commentCount}${unreadCommentBadge}</button>
        <button type="button" class="ghost-button ${post.repostedByMe ? 'is-active' : ''}" data-repost-post="${escapeHtml(post.id)}">${repostButtonLabel} ${repostCount}</button>
      </div>
    </article>
  `;
};

const renderProfilePosts = (studentKey) => {
  const posts = getRenderablePosts().filter((post) => isPostOwnedByStudent(post, studentKey));
  if (posts.length === 0) {
    return '<div class="profile-empty-state">Пока нет публикаций в этой категории.</div>';
  }
  return posts.map((post) => renderPostCard(post, 'profile')).join('');
};

const getStudentActivityStats = (studentKey) => {
  const posts = getRenderablePosts().filter((post) => isPostOwnedByStudent(post, studentKey));
  const projectsCount = posts.filter((post) => post.type === 'project').length;
  const postsCount = posts.filter((post) => !post.isRepost).length;
  const friendships = getFriendshipLists(studentKey);
  return {
    friendsCount: friendships.friends.length,
    projectsCount,
    postsCount,
  };
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
  const stats = getStudentActivityStats(viewedStudentKey);
  const profileSummary = student.organization || student.specialty || student.countryName
    ? `${fullName} строит профиль${student.organization ? ` в ${student.organization}` : ''}${specialtyText !== 'Пока не указано' ? ` с фокусом на ${specialtyText}` : ''}.`
    : 'Добавь информацию о себе в настройках профиля.';

  clientProfileName.textContent = fullName;
  clientProfileEmail.textContent = student.email || 'student@pwe.student';
  clientProfileEmailCard.textContent = student.email || 'student@pwe.student';
  clientProfileOrganizationCard.textContent = student.organization || 'Пока не указано';
  clientProfileSpecialtyCard.textContent = educationText ? `${specialtyText} • ${educationText}` : specialtyText;
  clientProfileCountryCard.textContent = countryText;
  
  // Render interests as hashtags
  if (clientProfileInterestsCard) {
    if (interestsText === 'Пока не указано' || !student.interests.length) {
      clientProfileInterestsCard.textContent = '—';
    } else {
      clientProfileInterestsCard.innerHTML = student.interests
        .filter(Boolean)
        .map((interest) => `<span class="pf-interest-tag">#${escapeHtml(interestLabel(interest).toLowerCase())}</span>`)
        .join('');
    }
  }
  
  if (clientProfileFriendsCount) clientProfileFriendsCount.textContent = String(stats.friendsCount);
  if (clientProfileProjectsCount) clientProfileProjectsCount.textContent = String(stats.projectsCount);
  if (clientProfilePostsCount) clientProfilePostsCount.textContent = String(stats.postsCount);
  if (clientProfileBio) clientProfileBio.textContent = profileSummary;
  applyAvatar(clientProfileAvatar, initials, student.photo);
  applyProfileStyle(student);
  editProfileButton.classList.toggle('is-hidden', viewedStudentKey !== 'me');
  openProfilePostButton.classList.toggle('is-hidden', viewedStudentKey !== 'me');
  if (profileActivityCard) {
    profileActivityCard.classList.toggle('is-hidden', viewedStudentKey !== 'me');
  }
  const friendshipLists = getFriendshipLists(viewedStudentKey);
  const friendCards = friendshipLists.friends
    .map((friendKey) => {
      const friend = getStudentData(friendKey);
      if (!friend) return '';
      return `
        <article class="panel social-mini-card">
          <strong>${escapeHtml(`${friend.firstName} ${friend.lastName}`.trim())}</strong>
          <p>${escapeHtml(friend.bio || friend.organization || 'Студент mendflow')}</p>
        </article>
      `;
    })
    .filter(Boolean)
    .join('');
  const outgoingCards = friendshipLists.outgoing
    .map((friendKey) => `<article class="panel social-mini-card"><strong>${escapeHtml(getStudentName(friendKey))}</strong><p>Заявка отправлена, ожидается подтверждение.</p></article>`)
    .join('');
  const incomingCards = friendshipLists.incoming
    .map((friendKey) => `<article class="panel social-mini-card"><strong>${escapeHtml(getStudentName(friendKey))}</strong><p>Входящая заявка. Подтверди её в уведомлениях.</p></article>`)
    .join('');

  document.querySelector('[data-profile-panel="posts"] #profilePostsList').innerHTML = renderProfilePosts(viewedStudentKey);
  document.querySelector('[data-profile-panel="friends"]').innerHTML = `
    <div class="profile-mini-grid">
      ${friendCards || '<div class="profile-empty-state">Пока нет друзей. Сначала отправь или подтверди заявку.</div>'}
      ${viewedStudentKey === 'me' ? incomingCards : ''}
      ${viewedStudentKey === 'me' ? outgoingCards : ''}
    </div>
  `;
  document.querySelector('[data-profile-panel="projects"]').innerHTML = `
    <div class="profile-mini-grid">
      ${getRenderablePosts().filter((post) => isPostOwnedByStudent(post, viewedStudentKey) && post.type === 'project').map((post) => renderPostCard(post, 'profile')).join('') || '<div class="profile-empty-state">Пока нет опубликованных проектов.</div>'}
    </div>
  `;

  // Update activity stats with real data
  const activity = getActivityData(student.email);
  if (profileViewsCount) profileViewsCount.textContent = String(activity.profileViews || 0);
  if (profileCvResponsesCount) profileCvResponsesCount.textContent = String(activity.cvResponses || 0);
  if (profileNetworkConnectionsCount) profileNetworkConnectionsCount.textContent = String(activity.networkConnections || 0);
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
    <div class="notification-card ${notification.read ? '' : 'is-unread'}" role="button" tabindex="0" data-notification-id="${escapeHtml(notification.id)}">
      <strong>${escapeHtml(notification.title)}</strong>
      <p>${escapeHtml(notification.text)}</p>
      <span class="notification-status">${notification.read ? 'Прочитано' : 'Новое уведомление'}</span>
      ${getNotificationActionsMarkup(notification)}
    </div>
  `).join('');

  const openNotification = (notificationId) => {
    const nextNotifications = getAllNotifications().map((notification) => (
      notification.id === notificationId ? { ...notification, read: true } : notification
    ));
    saveAllNotifications(nextNotifications);
    const target = nextNotifications.find((notification) => notification.id === notificationId);
    renderNotifications();
    closeModal(inboxModal, { skipHistory: true });
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
    button.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      event.preventDefault();
      openNotification(button.dataset.notificationId);
    });
  });

  notificationList.querySelectorAll('[data-accept-friend-request]').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.stopPropagation();
      if (!acceptFriendRequest(button.dataset.acceptFriendRequest)) return;
      renderNotifications();
      renderFriendSuggestions();
      renderConnectResults();
      renderProfilePanels();
      bindDynamicInteractions();
      showActionNotice('Заявка подтверждена. Теперь вы в друзьях.');
    });
  });

  notificationList.querySelectorAll('[data-cancel-friend-request]').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.stopPropagation();
      if (!cancelFriendRequest(button.dataset.cancelFriendRequest)) return;
      renderNotifications();
      renderFriendSuggestions();
      renderConnectResults();
      renderProfilePanels();
      bindDynamicInteractions();
      showActionNotice('Заявка отменена.');
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
        ${renderFriendActionButton(student.key, 'primary-button add-friend-button')}
      </div>
    </article>
  `).join('');
};

const getNetworkCategory = (student) => {
  const org = String(student.organization || '').toLowerCase();
  if (org.includes('university') || org.includes('универ') || org.includes('aitu') || org.includes('nu') || org.includes('kbtu') || org.includes('dku')) return 'universities';
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

  return `
    <article class="panel network-tile network-tile-student">
      <div class="network-tile-head">
        <div class="avatar sand network-person-avatar ${student.photo ? 'has-photo' : ''}" style="${student.photo ? `background-image:url('${student.photo}')` : ''}">${escapeHtml(getInitials(fullName))}</div>
        <div class="network-tile-title">
          <strong>${escapeHtml(fullName)}</strong>
          <p>${escapeHtml(subtitle)}</p>
        </div>
        <span class="network-score">★ 4.9</span>
      </div>
      <span class="network-location">${escapeHtml(location)}</span>
      ${renderNetworkTags(tags)}
      <div class="network-card-actions network-card-actions-wide">
        <button type="button" class="ghost-button student-profile-trigger" data-student-key="${escapeHtml(student.key)}">Профиль</button>
        ${renderFriendActionButton(student.key, 'primary-button wide add-friend-button')}
      </div>
    </article>
  `;
};

const renderNetworkEntityCard = (entity) => {
  if (entity.type === 'universities') {
    return `
      <article class="panel network-tile network-tile-university">
        <div class="network-cover university-cover"></div>
        <div class="network-tile-head">
          <div class="network-tile-title">
            <strong>${escapeHtml(entity.title)}</strong>
            <p>${escapeHtml(entity.subtitle)}</p>
          </div>
          <span class="network-score">★ ${escapeHtml(entity.rating)}</span>
        </div>
        <span class="network-location">${escapeHtml(entity.meta)}</span>
        ${renderNetworkTags(entity.tags)}
        <div class="network-card-actions network-card-actions-wide">
          <button type="button" class="ghost-button wide" data-network-entity-id="${escapeHtml(entity.id)}">${escapeHtml(entity.action)}</button>
        </div>
      </article>
    `;
  }

  return `
    <article class="panel network-tile network-tile-company">
      <div class="network-company-brand">
        <div class="network-entity-icon">${escapeHtml(entity.title.slice(0, 1))}</div>
        <div class="network-tile-title">
          <strong>${escapeHtml(entity.title)}</strong>
          <p>${escapeHtml(entity.subtitle)}</p>
        </div>
      </div>
      <div class="network-progress-card">
        <div class="network-progress-meta">
          <span>Открытые вакансии</span>
          <strong>${entity.type === 'companies' ? '12 позиций' : escapeHtml(entity.rating)}</strong>
        </div>
        <div class="network-progress-track"><i></i></div>
      </div>
      <span class="network-location">${escapeHtml(entity.meta)}</span>
      <div class="network-card-actions network-card-actions-wide">
        <button type="button" class="primary-button wide" data-network-entity-id="${escapeHtml(entity.id)}">${escapeHtml(entity.action)}</button>
      </div>
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

  const mentorStudent = students[1] || getNetworkStudents()[1];
  const items = [];

  if (students[0]) items.push(renderNetworkStudentCard(students[0]));
  if (entities.find((item) => item.type === 'universities')) items.push(renderNetworkEntityCard(entities.find((item) => item.type === 'universities')));
  if (entities.find((item) => item.type === 'companies')) items.push(renderNetworkEntityCard(entities.find((item) => item.type === 'companies')));
  if (mentorStudent) {
    items.push(`
      <article class="panel network-tile network-tile-mentor">
        <div class="network-tile-head">
          <div class="avatar sand network-person-avatar ${mentorStudent.photo ? 'has-photo' : ''}" style="${mentorStudent.photo ? `background-image:url('${mentorStudent.photo}')` : ''}">${escapeHtml(getInitials(`${mentorStudent.firstName} ${mentorStudent.lastName}`))}</div>
          <div class="network-tile-title">
            <strong>${escapeHtml(`${mentorStudent.firstName} ${mentorStudent.lastName}`)}</strong>
            <p>${escapeHtml((mentorStudent.specialty ? specialtyLabel(mentorStudent.specialty) : 'Mentor').toUpperCase())} @ ${escapeHtml(mentorStudent.organization || 'PWE')}</p>
          </div>
        </div>
        <p class="network-mentor-text">Помогает студентам с портфолио, карьерным ростом и поиском сильных академических связей.</p>
        <div class="network-card-actions network-card-actions-wide">
          <button type="button" class="ghost-button student-profile-trigger" data-student-key="${escapeHtml(mentorStudent.key)}">Профиль</button>
          ${renderFriendActionButton(mentorStudent.key, 'primary-button add-friend-button')}
        </div>
      </article>
    `);
  }

  connectResults.innerHTML = items.join('') || '<div class="profile-empty-state">Пока нет подходящих карточек по этому фильтру.</div>';
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
          toggleLikeLocally(button.dataset.likePost);
          showActionNotice('Лайк сохранён локально.');
          return;
        }
      }
      toggleLikeLocally(button.dataset.likePost);
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
      openCommentsLocally(activeCommentsPostId);
      showActionNotice('Комментарии открыты в локальном режиме.');
      return;
    }
  }
  openCommentsLocally(activeCommentsPostId);
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
            closeModal(commentsModal, { skipHistory: true });
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
        closeModal(commentsModal, { skipHistory: true });
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
    };
  });

  document.querySelectorAll('.open-chat-button').forEach((button) => {
    button.onclick = () => {
      const studentKey = button.dataset.openStudentChat;
      if (!studentKey) return;
      activeChatId = studentKey;
      renderLiveChat();
      loadServerChat(activeChatId);
      showAppView('chatsView');
    };
  });

  document.querySelectorAll('.add-friend-button').forEach((button) => {
    button.onclick = () => {
      const targetKey = button.dataset.friendAction || button.dataset.studentKey;
      if (!targetKey) return;
      const status = getFriendshipStatus(targetKey);
      const changed = status === 'incoming'
        ? acceptFriendRequest(targetKey)
        : status === 'outgoing'
          ? cancelFriendRequest(targetKey)
          : status === 'none'
            ? sendFriendRequest(targetKey)
            : false;
      if (!changed) return;
      renderNotifications();
      renderFriendSuggestions();
      renderConnectResults();
      renderProfilePanels();
      bindDynamicInteractions();
      showActionNotice(
        status === 'incoming'
          ? 'Заявка подтверждена.'
          : status === 'outgoing'
            ? 'Заявка отменена.'
            : 'Заявка в друзья отправлена.'
      );
    };
  });

  document.querySelectorAll('[data-repost-post]').forEach((button) => {
    button.onclick = () => {
      const result = toggleRepostLocally(button.dataset.repostPost);
      showActionNotice(result === 'exists' ? 'Этот пост уже репостнут.' : 'Пост репостнут.');
    };
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

  document.querySelectorAll('[data-network-entity-id]').forEach((button) => {
    button.onclick = () => {
      handleNetworkEntityAction(button.dataset.networkEntityId || '');
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
  if (!communityNameFilter || !communityInterestFilter || !communitySpecialtyFilter || !communityEducationFilter || communityCards.length === 0) {
    return;
  }

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

const getCurrentChatMeta = (chatId = activeChatId) => {
  if (isAccountChatId(chatId)) {
    const student = getStudentData(chatId);
    if (!student) return null;
    const label = `${student.firstName} ${student.lastName}`.trim();
    const meta = student.organization
      ? `${student.organization}${student.specialty ? ` • ${specialtyLabel(student.specialty)}` : ''}`
      : student.email;
    return {
      label,
      meta,
      studentKey: student.key,
      photo: student.photo || '',
      isGroup: false,
      groups: ['Дизайн-клуб', 'Хакатон 2024'],
    };
  }

  const meta = CHAT_META[chatId];
  if (!meta) return null;
  return {
    label: meta.label,
    meta: meta.meta,
    studentKey: meta.studentKey || '',
    photo: '',
    isGroup: true,
    groups: ['Мероприятия', 'Career Board'],
  };
};

const renderChatHeader = () => {
  const meta = getCurrentChatMeta();
  if (!meta) return;

  chatHeaderName.textContent = meta.label;
  chatHeaderMeta.textContent = meta.meta;
  chatProfileButton.dataset.studentKey = meta.studentKey || '';
  chatProfileButton.style.display = meta.studentKey ? '' : 'none';
  applyAvatar(chatHeaderAvatar, getInitials(meta.label), meta.photo || '');

  if (chatSidebarProfileButton) {
    chatSidebarProfileButton.dataset.studentKey = meta.studentKey || '';
    chatSidebarProfileButton.style.display = meta.studentKey ? '' : 'none';
  }
  if (chatSidebarName) chatSidebarName.textContent = meta.label;
  if (chatSidebarMeta) chatSidebarMeta.textContent = meta.meta;
  if (chatProfileAvatarLarge) applyAvatar(chatProfileAvatarLarge, getInitials(meta.label), meta.photo || '');
  if (chatGroupList) {
    chatGroupList.innerHTML = (meta.groups || []).map((group, index) => `
      <div class="chat-group-item">
        <span>${escapeHtml(group.slice(0, 2).toUpperCase())}</span>
        <strong>${escapeHtml(group)}</strong>
      </div>
    `).join('');
  }
  if (chatMediaGrid) {
    chatMediaGrid.innerHTML = `
      <div class="chat-media-card"></div>
      <div class="chat-media-card"></div>
      <div class="chat-media-card chat-media-card-muted">+12</div>
    `;
  }
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
  const query = String(chatSearchInput?.value || '').trim().toLowerCase();
  const chats = getChats();

  const registeredContacts = getRegisteredStudents({ excludeCurrent: true }).map((student) => {
    const chatId = student.key;
    const threadId = getChatThreadId(chatId);
    const thread = (isHostedMode() ? serverChatCache[chatId] : chats[threadId]) || [];
    const last = thread[thread.length - 1];
    const label = `${student.firstName} ${student.lastName}`.trim();
    const preview = last ? (last.text || '📎 Вложение') : 'Начните диалог';
    const time = last?.createdAt ? new Intl.DateTimeFormat('ru-RU', { hour: '2-digit', minute: '2-digit' }).format(new Date(last.createdAt)) : '';
    const initials = getInitials(label);
    const isActive = activeChatId === chatId;
    return { chatId, label, preview, time, isGroup: false, initials, photo: student.photo || '', isActive };
  });

  const systemContacts = Object.entries(CHAT_META).map(([chatId, meta]) => {
    const thread = chats[getChatThreadId(chatId)] || [];
    const last = thread[thread.length - 1];
    const preview = last ? (last.direction === 'outgoing' ? `Вы: ${last.text}` : last.text) || 'Групповой чат' : 'Групповой чат';
    const isActive = activeChatId === chatId;
    return { chatId, label: meta.label, preview, time: last ? 'Вчера' : '', isGroup: chatId === 'founders', initials: getInitials(meta.label), photo: '', isActive };
  });

  const items = [...registeredContacts, ...systemContacts]
    .filter((item) => activeChatFilter === 'all' || (activeChatFilter === 'groups' ? item.isGroup : !item.isGroup))
    .filter((item) => !query || `${item.label} ${item.preview}`.toLowerCase().includes(query));

  chatContactsList.innerHTML = items.map((item) => `
    <button class="msg-contact-item ${item.isActive ? 'is-active' : ''}" type="button" data-live-chat="${escapeHtml(item.chatId)}">
      <div class="msg-contact-avatar ${item.isGroup ? 'msg-avatar-group' : ''} ${item.photo ? 'has-photo' : ''}"
           style="${item.photo ? `background-image:url('${item.photo}')` : ''}">
        ${item.photo ? '' : item.isGroup ? '👥' : escapeHtml(item.initials)}
      </div>
      <div class="msg-contact-info">
        <div class="msg-contact-row">
          <strong class="msg-contact-name">${escapeHtml(item.label)}</strong>
          <span class="msg-contact-time">${escapeHtml(item.time)}</span>
        </div>
        <p class="msg-contact-preview">${escapeHtml(item.preview)}</p>
      </div>
    </button>
  `).join('') || '<div class="msg-empty">Чаты не найдены</div>';

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

  // Peer info for avatar in bubbles
  const peerMeta = getCurrentChatMeta();
  const peerInitials = peerMeta ? getInitials(peerMeta.label) : '??';
  const peerPhoto = peerMeta?.photo || '';

  if (isHostedMode() && isAccountChatId(activeChatId) && serverChatCache[activeChatId] === undefined) {
    liveChatMessages.innerHTML = '<div class="msg-loading">Загружаю переписку...</div>';
    loadServerChat(activeChatId);
    return;
  }

  liveChatMessages.innerHTML = '<div class="msg-day-divider"><span>Сегодня</span></div>' + messages.map((message) => {
    const isOut = message.direction === 'outgoing';
    const timeStr = message.createdAt
      ? new Intl.DateTimeFormat('ru-RU', { hour: '2-digit', minute: '2-digit' }).format(new Date(message.createdAt))
      : '';
    const avatarHtml = !isOut ? `
      <div class="msg-bubble-avatar ${peerPhoto ? 'has-photo' : ''}"
           style="${peerPhoto ? `background-image:url('${peerPhoto}')` : ''}">
        ${peerPhoto ? '' : escapeHtml(peerInitials)}
      </div>` : '<div class="msg-bubble-avatar-placeholder"></div>';
    return `
      <div class="msg-bubble-row ${isOut ? 'msg-row-out' : 'msg-row-in'}">
        ${!isOut ? avatarHtml : ''}
        <div class="msg-bubble-wrap ${isOut ? 'msg-wrap-out' : ''}">
          <div class="msg-bubble ${isOut ? 'msg-bubble-out' : 'msg-bubble-in'}">
            ${message.text ? `<span>${escapeHtml(message.text)}</span>` : ''}
            ${renderAttachment(message)}
          </div>
          ${timeStr ? `<span class="msg-bubble-time">${isOut ? '✓✓ ' : ''}${timeStr}</span>` : ''}
        </div>
      </div>
    `;
  }).join('');
  liveChatMessages.scrollTop = liveChatMessages.scrollHeight;
};

const openProjectWorkspace = (name = 'Рабочее пространство') => {
  showAppView('projectsView');
  navigateTo({
    screen: 'app',
    viewId: 'projectsView',
    projectOpen: true,
    projectName: name,
  });
};

const addProjectKanbanColumn = () => {
  const board = document.querySelector('.pw-kanban-board');
  if (!board) return;
  projectExtraColumnCount += 1;
  board.insertAdjacentHTML('beforeend', `
    <div class="pw-kanban-col">
      <div class="pw-col-head">
        <span class="pw-col-title">NEW COLUMN ${projectExtraColumnCount}</span>
        <span class="pw-col-count">1</span>
      </div>
      <div class="pw-kanban-cards">
        <article class="pw-card">
          <span class="pw-card-tag pw-tag-design">Новая задача</span>
          <p class="pw-card-text">Добавь сюда первый шаг для нового этапа проекта.</p>
          <div class="pw-card-footer">
            <span class="pw-card-time">Сейчас</span>
            <span class="pw-card-avatar" style="background:#6d28d9">MF</span>
          </div>
        </article>
      </div>
    </div>
  `);
  showActionNotice('Новая колонка добавлена в канбан.');
};

const addQuickProjectTask = () => {
  const firstColumn = document.querySelector('.pw-kanban-board .pw-kanban-col .pw-kanban-cards');
  if (!firstColumn) return;
  firstColumn.insertAdjacentHTML('afterbegin', `
    <article class="pw-card">
      <span class="pw-card-tag pw-tag-urgent">Новая задача</span>
      <p class="pw-card-text">Быстрая задача создана через кнопку действия.</p>
      <div class="pw-card-footer">
        <span class="pw-card-time">Только что</span>
        <span class="pw-card-avatar" style="background:#f97316">MF</span>
      </div>
    </article>
  `);
  showActionNotice('Новая задача добавлена в backlog.');
};

const sendProjectAiMessage = () => {
  const text = String(pwAiInput?.value || '').trim();
  if (!text || !pwAiMessages) return;
  pwAiMessages.insertAdjacentHTML('beforeend', `<div class="pw-ai-msg pw-ai-msg-out">${escapeHtml(text)}</div>`);
  pwAiMessages.insertAdjacentHTML('beforeend', '<div class="pw-ai-msg pw-ai-msg-in">Принял запрос. Разбей его на шаги в канбане, и я помогу приоритизировать задачи.</div>');
  pwAiInput.value = '';
  pwAiMessages.scrollTop = pwAiMessages.scrollHeight;
};

const insertTextAtCursor = (input, text) => {
  if (!input) return;
  const start = input.selectionStart ?? input.value.length;
  const end = input.selectionEnd ?? input.value.length;
  input.value = `${input.value.slice(0, start)}${text}${input.value.slice(end)}`;
  const caret = start + text.length;
  input.setSelectionRange(caret, caret);
  input.focus();
};

const handleNetworkEntityAction = (entityId = '') => {
  const entity = NETWORK_ENTITIES.find((item) => item.id === entityId);
  if (!entity) return;

  if (entity.type === 'companies') {
    showAppView('internshipsView');
    internshipQuery.value = entity.title;
    updateInternships();
    showActionNotice(`Открыли стажировки по направлению ${entity.title}.`);
    return;
  }

  activeNetworkFilter = entity.type;
  networkFilterButtons.forEach((button) => {
    button.classList.toggle('is-active', button.dataset.networkFilter === entity.type);
  });
  connectSearchInput.value = entity.title;
  renderConnectResults();
  bindDynamicInteractions();
  showActionNotice(`Показали карточки по направлению ${entity.title}.`);
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
    commentsCount: 0,
    unreadComments: 0,
    repostsCount: 0,
    repostedByMe: false,
    authorKey: 'me',
    authorPhoto: account.photo || '',
    attachment,
  });
  savePosts(posts);
  renderPosts();
};

const toggleLikeLocally = (postId) => {
  if (isHostedMode()) {
    serverFeedItems = serverFeedItems.map((post) => {
      if (String(post.id) !== String(postId)) return post;
      const likedByMe = !Boolean(post.likedByMe);
      return {
        ...post,
        likedByMe,
        likes: Math.max(0, Number(post.likes || 0) + (likedByMe ? 1 : -1)),
      };
    });
    renderPosts();
    return;
  }

  const nextPosts = getPosts().map((post) => {
    if (String(post.id) !== String(postId)) return post;
    const likedByMe = !Boolean(post.likedByMe);
    return {
      ...post,
      likedByMe,
      likes: Math.max(0, Number(post.likes || 0) + (likedByMe ? 1 : -1)),
    };
  });
  savePosts(nextPosts);
  renderPosts();
};

const openCommentsLocally = (postId) => {
  activeCommentsPostId = String(postId);
  const posts = getRenderablePosts();
  const post = posts.find((item) => String(item.id) === String(postId));
  if (isHostedMode()) {
    setServerPostComments(postId, post?.comments || []);
  } else {
    const nextPosts = getPosts().map((item) => String(item.id) === String(postId)
      ? { ...item, unreadComments: 0 }
      : item);
    savePosts(nextPosts);
  }
  commentsModalList.innerHTML = renderCommentList(post?.comments || []);
  openModal(commentsModal);
  renderPosts();
};

const toggleRepostLocally = (postId) => {
  const account = getAccount();
  if (!account) return 'no-account';
  const currentKey = getCurrentStudentKey();

  if (isHostedMode()) {
    const sourcePost = serverFeedItems.find((post) => String(post.id) === String(postId));
    if (!sourcePost) return 'missing';
    const existingRepost = serverFeedItems.find((post) => post.isRepost && String(post.repostOfId) === String(postId) && isPostOwnedByStudent(post, currentKey));

    if (existingRepost) {
      return 'exists';
    }

    const repostId = crypto.randomUUID ? crypto.randomUUID() : `repost-${Date.now()}`;
    serverFeedItems = [
      {
        ...sourcePost,
        id: repostId,
        ownPost: true,
        authorKey: 'me',
        authorPhoto: account.photo || '',
        text: sourcePost.text,
        createdAt: new Date().toISOString(),
        likes: 0,
        likedByMe: false,
        comments: [],
        commentsCount: 0,
        unreadComments: 0,
        repostsCount: 0,
        repostedByMe: false,
        isRepost: true,
        repostOfId: String(postId),
        repostAuthorKey: sourcePost.authorKey || '',
        repostPreview: sourcePost.text || '',
      },
      ...serverFeedItems.map((post) => String(post.id) === String(postId)
        ? { ...post, repostedByMe: true, repostsCount: Number(post.repostsCount || 0) + 1 }
        : post),
    ];
    renderPosts();
    return 'created';
  }

  const posts = getPosts();
  const sourcePost = posts.find((post) => String(post.id) === String(postId));
  if (!sourcePost) return 'missing';
  const existingRepost = posts.find((post) => post.isRepost && String(post.repostOfId) === String(postId) && isPostOwnedByStudent(post, currentKey));

  if (existingRepost) {
    return 'exists';
  }

  const repostId = crypto.randomUUID ? crypto.randomUUID() : `repost-${Date.now()}`;
  posts.push({
    ...sourcePost,
    id: repostId,
    ownPost: true,
    authorKey: 'me',
    authorPhoto: account.photo || '',
    createdAt: new Date().toISOString(),
    likes: 0,
    likedByMe: false,
    comments: [],
    commentsCount: 0,
    unreadComments: 0,
    repostsCount: 0,
    repostedByMe: false,
    isRepost: true,
    repostOfId: String(postId),
    repostAuthorKey: sourcePost.authorKey || '',
    repostPreview: sourcePost.text || '',
  });
  const nextPosts = posts.map((post) => String(post.id) === String(postId)
    ? { ...post, repostedByMe: true, repostsCount: Number(post.repostsCount || 0) + 1 }
    : post);
  savePosts(nextPosts);
  renderPosts();
  return 'created';
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
  syncProfileSettingsModal(account);
  syncAffiliationCard(account);
  renderSidebarTasks();
  updateStrengthUI(account);
  updateCvPreview(account);
  viewedStudentKey = 'me';
  renderProfilePanels();
  renderPosts();
};

/* ── Email Verification System ── */

let pendingRegisterAccount = null;
let verifyCode = '';
let verifyResendTimerId = 0;
let verifyResendSecondsLeft = 0;

const REG_STEP_IDS = ['regCardStep1', 'regCardStep2', 'regCardStep3'];
const regStep1Card = document.querySelector('#regCardStep1');
const regCardStep2 = document.querySelector('#regCardStep2');
const regCardStep3 = document.querySelector('#regCardStep3');
const regStep1El = document.querySelector('#regStep1');
const regStep2El = document.querySelector('#regStep2');
const regStep3El = document.querySelector('#regStep3');
const regStepLine1 = document.querySelector('#regStepLine1');
const regStepLine2 = document.querySelector('#regStepLine2');
const verifyDemoNotice = document.querySelector('#verifyDemoNotice');
const verifyDemoCode = document.querySelector('#verifyDemoCode');
const verifyEmailDisplay = document.querySelector('#verifyEmailDisplay');
const verifyForm = document.querySelector('#verifyForm');
const verifyCodeInputs = Array.from(document.querySelectorAll('.verify-code-input'));
const verifySubmitButton = document.querySelector('#verifySubmitButton');
const verifyTimer = document.querySelector('#verifyTimer');
const verifyResendButton = document.querySelector('#verifyResendButton');
const verifyBackButton = document.querySelector('#verifyBackButton');
const regSuccessLoginButton = document.querySelector('#regSuccessLoginButton');
const regSuccessName = document.querySelector('#regSuccessName');
const registerSubmitButton = document.querySelector('#registerSubmitButton');

const startResendTimer = (seconds = 60) => {
  verifyResendSecondsLeft = seconds;
  if (verifyResendButton) verifyResendButton.classList.add('is-hidden');
  const tick = () => {
    if (verifyTimer) verifyTimer.textContent = `Повторная отправка через ${verifyResendSecondsLeft} сек`;
    if (verifyResendSecondsLeft <= 0) {
      if (verifyTimer) verifyTimer.textContent = '';
      if (verifyResendButton) verifyResendButton.classList.remove('is-hidden');
      return;
    }
    verifyResendSecondsLeft -= 1;
    verifyResendTimerId = window.setTimeout(tick, 1000);
  };
  window.clearTimeout(verifyResendTimerId);
  tick();
};

const sendVerificationCode = async (email, isResend = false) => {
  if (window.location.protocol === 'file:') {
    verifyCode = generateVerifyCode();
    if (verifyDemoNotice) verifyDemoNotice.classList.remove('is-hidden');
    if (verifyDemoCode) verifyDemoCode.textContent = verifyCode;
    startResendTimer(30);
    return;
  }
  try {
    const response = await postJson('./api/auth/send-code.php', { email });
    if (response.code) {
      verifyCode = String(response.code);
      if (verifyDemoNotice) verifyDemoNotice.classList.remove('is-hidden');
      if (verifyDemoCode) verifyDemoCode.textContent = verifyCode;
    } else {
      verifyCode = '';
      if (verifyDemoNotice) verifyDemoNotice.classList.add('is-hidden');
    }
  } catch {
    verifyCode = generateVerifyCode();
    if (verifyDemoNotice) verifyDemoNotice.classList.remove('is-hidden');
    if (verifyDemoCode) verifyDemoCode.textContent = verifyCode;
  }
  startResendTimer(60);
};

const getVerifyCodeValue = () => verifyCodeInputs.map((input) => input.value).join('');

const clearVerifyCodeInputs = () => {
  verifyCodeInputs.forEach((input) => {
    input.value = '';
    input.classList.remove('is-filled', 'is-error');
  });
  if (verifyCodeInputs[0]) verifyCodeInputs[0].focus();
};

const flashVerifyError = () => {
  verifyCodeInputs.forEach((input) => {
    input.classList.add('is-error');
    input.value = '';
  });
  window.setTimeout(() => {
    verifyCodeInputs.forEach((input) => input.classList.remove('is-error'));
    if (verifyCodeInputs[0]) verifyCodeInputs[0].focus();
  }, 600);
};

verifyCodeInputs.forEach((input, index) => {
  input.addEventListener('input', (event) => {
    const val = event.target.value.replace(/[^0-9]/g, '').slice(-1);
    input.value = val;
    input.classList.toggle('is-filled', Boolean(val));
    if (val && index < verifyCodeInputs.length - 1) {
      verifyCodeInputs[index + 1].focus();
    }
    if (getVerifyCodeValue().length === 6 && verifySubmitButton) {
      verifySubmitButton.click();
    }
  });

  input.addEventListener('keydown', (event) => {
    if (event.key === 'Backspace' && !input.value && index > 0) {
      verifyCodeInputs[index - 1].value = '';
      verifyCodeInputs[index - 1].classList.remove('is-filled');
      verifyCodeInputs[index - 1].focus();
    }
    if (event.key === 'ArrowLeft' && index > 0) verifyCodeInputs[index - 1].focus();
    if (event.key === 'ArrowRight' && index < verifyCodeInputs.length - 1) verifyCodeInputs[index + 1].focus();
  });

  input.addEventListener('paste', (event) => {
    event.preventDefault();
    const pasted = (event.clipboardData?.getData('text') || '').replace(/[^0-9]/g, '').slice(0, 6);
    pasted.split('').forEach((char, i) => {
      if (verifyCodeInputs[i]) {
        verifyCodeInputs[i].value = char;
        verifyCodeInputs[i].classList.add('is-filled');
      }
    });
    const nextIndex = Math.min(pasted.length, verifyCodeInputs.length - 1);
    verifyCodeInputs[nextIndex].focus();
    if (pasted.length === 6 && verifySubmitButton) {
      window.setTimeout(() => verifySubmitButton.click(), 80);
    }
  });
});

if (verifyForm) {
  verifyForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const entered = getVerifyCodeValue();
    if (entered.length < 6) {
      setRegisterMessage('Введи все 6 цифр кода.', 'error');
      return;
    }

    if (window.location.protocol !== 'file:') {
      try {
        if (verifySubmitButton) verifySubmitButton.classList.add('is-loading');
        const response = await postJson('./api/auth/verify-code.php', {
          email: pendingRegisterAccount?.email || '',
          code: entered,
        });
        if (!response.valid) {
          flashVerifyError();
          setRegisterMessage('Код неверный или устарел. Попробуй снова.', 'error');
          return;
        }
        await completeRegistration(pendingRegisterAccount);
        return;
      } catch (error) {
        flashVerifyError();
        setRegisterMessage(error.message || 'Ошибка проверки кода.', 'error');
        return;
      } finally {
        if (verifySubmitButton) verifySubmitButton.classList.remove('is-loading');
      }
    }

    if (entered === verifyCode) {
      await completeRegistration(pendingRegisterAccount);
    } else {
      flashVerifyError();
      setRegisterMessage('Код неверный. Проверь ещё раз.', 'error');
    }
  });
}

const completeRegistration = async (account) => {
  if (!account) return;
  window.clearTimeout(verifyResendTimerId);
  setRegisterMessage('');
  goToRegStep(3);

  if (regSuccessName) {
    regSuccessName.textContent = `Привет, ${account.firstName}! Добро пожаловать в mendflow.`;
  }

  if (window.location.protocol !== 'file:') {
    try {
      const response = await postJson('./api/auth/register.php', { ...account, verified: true });
      const nextAccount = mergeProfileIntoAccount({
        ...account,
        firstName: response.user?.firstName || account.firstName,
        lastName: response.user?.lastName || account.lastName,
        email: response.user?.email || account.email,
      }, response.user?.profile || {});
      saveAccount(nextAccount);
      return;
    } catch {
      saveAccount(account);
    }
  } else {
    saveAccount(account);
    updateRegisteredCount();
  }
};

if (verifyResendButton) {
  verifyResendButton.addEventListener('click', async () => {
    if (!pendingRegisterAccount) return;
    verifyResendButton.classList.add('is-hidden');
    clearVerifyCodeInputs();
    setRegisterMessage('Код отправлен повторно.', 'info');
    await sendVerificationCode(pendingRegisterAccount.email, true);
  });
}

if (verifyBackButton) {
  verifyBackButton.addEventListener('click', () => {
    window.clearTimeout(verifyResendTimerId);
    pendingRegisterAccount = null;
    verifyCode = '';
    clearVerifyCodeInputs();
    setRegisterMessage('');
    goToRegStep(1);
  });
}

if (regSuccessLoginButton) {
  regSuccessLoginButton.addEventListener('click', () => {
    const account = pendingRegisterAccount || getAccount();
    registerForm.reset();
    setRegisterMessage('');
    goToRegStep(1);
    showLoginScreen();
    if (account) {
      loginIdentifierInput.value = account.email;
      setLoginMessage(`Аккаунт создан. Войди по email ${account.email}.`, 'success');
    }
  });
}

const getPasswordStrength = (pw) => {
  if (!pw || pw.length < 4) return 'weak';
  const hasLower = /[a-z]/.test(pw);
  const hasUpper = /[A-Z]/.test(pw);
  const hasNum = /[0-9]/.test(pw);
  const hasSpec = /[^a-zA-Z0-9]/.test(pw);
  const score = [hasLower, hasUpper, hasNum, hasSpec].filter(Boolean).length;
  if (pw.length >= 8 && score >= 3) return 'strong';
  if (pw.length >= 6 && score >= 2) return 'medium';
  return 'weak';
};

const injectPasswordStrength = () => {
  const passwordField = registerForm?.querySelector('input[name="password"]');
  if (!passwordField) return;
  const wrapper = passwordField.parentElement;
  const strengthDiv = document.createElement('div');
  strengthDiv.innerHTML = `
    <div class="password-strength"><div class="password-strength-bar" id="pwStrengthBar"></div></div>
    <div class="password-strength-label" id="pwStrengthLabel"></div>
  `;
  wrapper.appendChild(strengthDiv);
  const bar = document.querySelector('#pwStrengthBar');
  const label = document.querySelector('#pwStrengthLabel');
  const LABELS = { weak: 'Слабый пароль', medium: 'Средний пароль', strong: 'Надёжный пароль' };
  passwordField.addEventListener('input', () => {
    const str = getPasswordStrength(passwordField.value);
    if (bar) { bar.className = `password-strength-bar strength-${str}`; }
    if (label) { label.textContent = passwordField.value ? LABELS[str] : ''; label.className = `password-strength-label strength-${str}`; }
  });
};

injectPasswordStrength();

registerForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  const formData = new FormData(registerForm);
  const firstName = String(formData.get('firstName')).trim();
  const lastName = String(formData.get('lastName')).trim();
  const email = String(formData.get('email')).trim().toLowerCase();
  const password = String(formData.get('password')).trim();
  const passwordConfirm = String(formData.get('passwordConfirm')).trim();

  const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  if (!firstName || !lastName || !emailValid || password.length < 8) {
    setRegisterMessage('Проверь поля: имя, фамилия и email обязательны. Пароль — мин. 8 символов, Aa1!', 'error');
    return;
  }
  if (password !== passwordConfirm) {
    setRegisterMessage('Пароли не совпадают. Проверь повторный ввод.', 'error');
    return;
  }
  const savedAccount = getAccounts().find((item) => item.email === email);
  if (savedAccount) {
    setRegisterMessage('Аккаунт с таким email уже существует. Перейди на страницу входа.', 'error');
    return;
  }

  pendingRegisterAccount = {
    firstName,
    lastName,
    email,
    password,
    organization: '',
    specialty: '',
    education: '',
    countryValue: '',
    countryFlag: '',
    countryName: '',
    interests: [],
    photo: '',
    style: { fontTheme: 'manrope', accentColor: '#7c3aed', cardColor: '#ffffff' },
  };

  setRegisterMessage('');
  if (registerSubmitButton) registerSubmitButton.classList.add('is-loading');
  await sendVerificationCode(email);
  if (registerSubmitButton) registerSubmitButton.classList.remove('is-loading');

  if (verifyEmailDisplay) verifyEmailDisplay.textContent = email;
  clearVerifyCodeInputs();
  goToRegStep(2);
  window.setTimeout(() => { if (verifyCodeInputs[0]) verifyCodeInputs[0].focus(); }, 120);
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
          accentColor: '#7c3aed',
          cardColor: '#ffffff',
        },
      }, response.user?.profile || {});

      saveAccount(nextAccount);
      window.localStorage.setItem(STORAGE_KEYS.session, nextAccount.email);
      hydrateUser(nextAccount);
      loginForm.reset();
      setLoginMessage('');
      showAppView('workspaceView');
      return;
    }
  } catch (error) {
    setLoginMessage(error.message, 'info');
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

profileBackButton.addEventListener('click', () => goBack({ screen: 'app', viewId: 'workspaceView', modalId: '' }));
clientProfileBackButton.addEventListener('click', () => goBack({ screen: 'app', viewId: 'workspaceView', modalId: '' }));
editProfileButton.addEventListener('click', () => {
  const account = getAccount();
  if (account) {
    syncProfileSettingsModal(account);
  }
  setActiveSettingsPanel('profile');
  openModal(profileSettingsModal);
});

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
  const nextAccount = collectProfileDraft();
  if (!nextAccount) return;

  try {
    await persistProfileDraft(nextAccount);
  } catch (error) {
    setLoginMessage(error.message, 'error');
  }
});

if (profileSettingsForm) {
  profileSettingsForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const nextAccount = collectProfileDraft({ fromModal: true });
    if (!nextAccount) return;

    try {
      await persistProfileDraft(nextAccount);
      showActionNotice('Профиль обновлён.');
      closeModal(profileSettingsModal, { skipHistory: true });
    } catch (error) {
      setLoginMessage(error.message, 'error');
    }
  });
}

if (profileSecurityForm) {
  profileSecurityForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const account = getAccount();
    if (!account) return;
    const password = profileSecurityPassword.value.trim();
    const passwordConfirm = profileSecurityPasswordConfirm.value.trim();

    if (password.length < 8) {
      setLoginMessage('Новый пароль должен быть не короче 8 символов.', 'error');
      return;
    }
    if (!/[a-zа-яё]/u.test(password)) {
      setLoginMessage('Пароль должен содержать строчную букву.', 'error');
      return;
    }
    if (!/[A-ZА-ЯЁ]/u.test(password)) {
      setLoginMessage('Пароль должен содержать заглавную букву.', 'error');
      return;
    }
    if (!/\d/u.test(password)) {
      setLoginMessage('Пароль должен содержать цифру.', 'error');
      return;
    }
    if (!/[^a-zA-Zа-яА-ЯёЁ0-9]/u.test(password)) {
      setLoginMessage('Пароль должен содержать спецсимвол (!@#$% и т.п.).', 'error');
      return;
    }
    if (password !== passwordConfirm) {
      setLoginMessage('Подтверждение пароля не совпадает.', 'error');
      return;
    }

    const nextAccount = { ...account, password };
    saveAccount(nextAccount);
    hydrateUser(nextAccount);
    profileSecurityForm.reset();
    if (profileSecurityEmail) profileSecurityEmail.value = nextAccount.email || '';
    showActionNotice('Пароль обновлён.');
  });
}

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

if (feedPublishButton) {
  feedPublishButton.addEventListener('click', () => {
    viewedStudentKey = 'me';
    renderProfilePanels();
    showAppView('clientProfileView');
    setActiveProfileTab('posts');
    openModal(postModal);
  });
}

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
    closeModal(postModal, { skipHistory: true });
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
      const account = getAccount();
      const author = account ? `${account.firstName} ${account.lastName}`.trim() : 'Студент';
      const nextComments = [
        ...((serverFeedItems.find((post) => String(post.id) === String(activeCommentsPostId))?.comments) || []),
        { author, text },
      ];
      setServerPostComments(activeCommentsPostId, nextComments);
      commentsModalList.innerHTML = renderCommentList(nextComments);
      commentsModalInput.value = '';
      renderPosts();
      showActionNotice('Комментарий сохранён локально.');
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
  showActionNotice('Комментарий добавлен.');
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
});
chatPreviews.forEach((button) => {
  button.addEventListener('click', () => {
    activeChatId = button.dataset.openChat;
    renderLiveChat();
    showAppView('chatsView');
  });
});
const openActiveChatProfile = () => {
  const key = chatProfileButton.dataset.studentKey;
  if (!key) return;
  viewedStudentKey = key;
  renderProfilePanels();
  showAppView('clientProfileView');
  setActiveProfileTab('posts');
};

chatProfileButton.addEventListener('click', openActiveChatProfile);
if (chatSidebarProfileButton) {
  chatSidebarProfileButton.addEventListener('click', openActiveChatProfile);
}

if (brandLink) {
  brandLink.addEventListener('click', (event) => {
    event.preventDefault();
    if (canOpenAppShell()) {
      showAppView('workspaceView');
      return;
    }
    const account = getAccount();
    if (account) {
      showLoginScreen();
      loginIdentifierInput.value = account.email;
      return;
    }
    showRegisterScreen();
  });
}

if (pfNotificationsButton) {
  pfNotificationsButton.addEventListener('click', () => openModal(inboxModal));
}

if (networkFocusButton) {
  networkFocusButton.addEventListener('click', () => {
    showAppView('internshipsView');
    showActionNotice('Открыли раздел со стажировками и условиями отбора.');
  });
}

if (chatEmojiButton) {
  chatEmojiButton.addEventListener('click', () => {
    if (window.MF_EMOJI) {
      MF_EMOJI.openPicker(chatEmojiButton, liveChatInput);
    } else {
      insertTextAtCursor(liveChatInput, ':joy: ');
    }
  });
}

if (pwNewProjectCard) {
  pwNewProjectCard.addEventListener('click', () => openProjectWorkspace('Новый проект'));
}

if (pwNewProjectButton) {
  pwNewProjectButton.addEventListener('click', () => openProjectWorkspace('Новый проект'));
}

document.querySelectorAll('.pw-project-card').forEach((card) => {
  card.addEventListener('click', () => openProjectWorkspace(card.dataset.projectName || 'Рабочее пространство'));
});

if (pwBackToList) {
  pwBackToList.addEventListener('click', () => goBack({ screen: 'app', viewId: 'projectsView', projectOpen: false, modalId: '' }));
}

if (pwShareButton) {
  pwShareButton.addEventListener('click', async () => {
    const projectTitle = String(pwDetailTitle?.textContent || 'Рабочее пространство').trim();
    const shareText = `mendflow: ${projectTitle}`;
    try {
      if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(shareText);
        showActionNotice('Название проекта скопировано для отправки.');
        return;
      }
    } catch {
      // Fall back to a simple status note if clipboard access is unavailable.
    }
    showActionNotice(`Готово к шарингу: ${projectTitle}`);
  });
}

if (pwAddColumnButton) {
  pwAddColumnButton.addEventListener('click', addProjectKanbanColumn);
}

if (pwAiSendButton) {
  pwAiSendButton.addEventListener('click', sendProjectAiMessage);
}

if (pwAiInput) {
  pwAiInput.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    sendProjectAiMessage();
  });
}

if (pwFilesMoreButton) {
  pwFilesMoreButton.addEventListener('click', () => {
    pwFileUploadInput?.click();
  });
}

if (pwFabButton) {
  pwFabButton.addEventListener('click', addQuickProjectTask);
}

if (profileCvButton) {
  profileCvButton.addEventListener('click', () => {
    const account = getAccount();
    if (account) {
      syncProfileSettingsModal(account);
    }
    setActiveSettingsPanel('profile');
    openModal(profileSettingsModal);
    profileSettingsOrganizationInput?.focus();
    showActionNotice('Открыли настройки профиля для улучшения CV.');
  });
}

profileAffiliationButtons.forEach((button) => {
  button.addEventListener('click', () => {
    const type = button.dataset.affiliationType === 'work' ? 'work' : 'study';
    profileAffiliationButtons.forEach((item) => {
      item.classList.toggle('is-active', item === button);
    });
    renderAffiliationOptions(type, '');
    renderAffiliationLinks(type, '');
  });
});

if (profileAffiliationSelect) {
  profileAffiliationSelect.addEventListener('change', async () => {
    const account = getAccount();
    if (!account) return;
    const selectedType = profileAffiliationButtons.find((button) => button.classList.contains('is-active'))?.dataset.affiliationType === 'work'
      ? 'work'
      : 'study';
    const selectedOrganization = profileAffiliationSelect.value.trim();

    renderAffiliationLinks(selectedType, selectedOrganization);

    if (!selectedOrganization) return;

    const nextAccount = {
      ...account,
      organization: selectedOrganization,
      affiliationType: selectedType,
    };

    if (profileOrganizationInput) profileOrganizationInput.value = selectedOrganization;
    if (profileSettingsOrganizationInput) profileSettingsOrganizationInput.value = selectedOrganization;

    try {
      await persistProfileDraft(nextAccount);
      showActionNotice(selectedType === 'work' ? 'Компания сохранена в профиле.' : 'Университет сохранён в профиле.');
    } catch (error) {
      setLoginMessage(error.message, 'error');
    }
  });
}

if (saveAffiliationButton) {
  saveAffiliationButton.addEventListener('click', async () => {
    const account = getAccount();
    if (!account || !profileAffiliationSelect) return;
    const selectedType = profileAffiliationButtons.find((button) => button.classList.contains('is-active'))?.dataset.affiliationType === 'work'
      ? 'work'
      : 'study';
    const selectedOrganization = profileAffiliationSelect.value.trim();

    if (!selectedOrganization) {
      showActionNotice(selectedType === 'work' ? 'Сначала выбери компанию.' : 'Сначала выбери университет.');
      return;
    }

    const nextAccount = {
      ...account,
      organization: selectedOrganization,
      affiliationType: selectedType,
    };

    if (profileOrganizationInput) profileOrganizationInput.value = selectedOrganization;
    if (profileSettingsOrganizationInput) profileSettingsOrganizationInput.value = selectedOrganization;

    try {
      await persistProfileDraft(nextAccount);
      syncAffiliationCard(nextAccount);
      if (profileSettingsModal) {
        closeModal(profileSettingsModal, { skipHistory: true });
      }
      showActionNotice(selectedType === 'work' ? 'Компания сохранена в профиле.' : 'Университет сохранён в профиле.');
    } catch (error) {
      setLoginMessage(error.message, 'error');
    }
  });
}

if (sidebarTaskForm && sidebarTaskInput) {
  sidebarTaskForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const added = addSidebarTask(sidebarTaskInput.value);
    if (!added) {
      showActionNotice('Сначала напиши задачу.');
      return;
    }
    sidebarTaskInput.value = '';
    sidebarTaskInput.focus();
  });
}

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

[communityNameFilter, communityInterestFilter, communitySpecialtyFilter, communityEducationFilter]
  .filter(Boolean)
  .forEach((control) => {
    control.addEventListener('input', updateCommunities);
    control.addEventListener('change', updateCommunities);
  });

connectSearchInput.addEventListener('input', () => {
  renderConnectResults();
  bindDynamicInteractions();
});

if (chatSearchInput) {
  chatSearchInput.addEventListener('input', () => {
    renderChatContacts();
  });
}

chatFilterButtons.forEach((button) => {
  button.addEventListener('click', () => {
    activeChatFilter = button.dataset.chatFilter || 'all';
    chatFilterButtons.forEach((item) => item.classList.toggle('is-active', item === button));
    renderChatContacts();
  });
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

if (closeCommunityButton && communityModal) {
  closeCommunityButton.addEventListener('click', () => closeModal(communityModal));
}

if (closeCommunityBackdrop && communityModal) {
  closeCommunityBackdrop.addEventListener('click', () => closeModal(communityModal));
}

if (closeProfileSettingsButton && profileSettingsModal) {
  closeProfileSettingsButton.addEventListener('click', () => closeModal(profileSettingsModal));
}

if (closeProfileSettingsBackdrop && profileSettingsModal) {
  closeProfileSettingsBackdrop.addEventListener('click', () => closeModal(profileSettingsModal));
}

if (profileSettingsLogoutButton) {
  profileSettingsLogoutButton.addEventListener('click', () => {
    closeModal(profileSettingsModal, { skipHistory: true });
    logoutButton.click();
  });
}

profileSettingsNavButtons.forEach((button) => {
  button.addEventListener('click', () => {
    setActiveSettingsPanel(button.dataset.settingsPanel || 'profile');
  });
});

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
    {
      id: 'seed-3',
      type: 'post',
      feedMode: 'recommendations',
      interestMode: 'design',
      text: 'Ищу дизайнера для стартапа в сфере образования. Нужен опыт в UI/UX и знание Figma.',
      createdAt: new Date(Date.now() - 86400000).toISOString(), // 1 day ago
      tags: ['design', 'product'],
      likes: 4,
      comments: [{ author: 'Асем С.', text: 'Могу помочь с дизайном интерфейсов.' }],
      authorKey: 'me',
    },
    {
      id: 'seed-4',
      type: 'project',
      feedMode: 'subscriptions',
      interestMode: 'ai',
      text: 'Разрабатываю AI-ассистента для студентов. Помогает с планированием и анализом успеваемости.',
      createdAt: new Date(Date.now() - 172800000).toISOString(), // 2 days ago
      tags: ['ai', 'frontend'],
      likes: 8,
      comments: [{ author: 'Томирис Н.', text: 'Звучит полезно, хочу протестировать.' }],
      authorKey: 'me',
    },
    {
      id: 'seed-5',
      type: 'post',
      feedMode: 'recommendations',
      interestMode: 'business',
      text: 'Организую воркшоп по бизнес-моделям для стартапов. Приходите, если интересуетесь предпринимательством.',
      createdAt: new Date(Date.now() - 259200000).toISOString(), // 3 days ago
      tags: ['business', 'product'],
      likes: 12,
      comments: [{ author: 'Томирис Н.', text: 'Буду рада присоединиться!' }],
      authorKey: 'tomiris',
    },
    {
      id: 'seed-6',
      type: 'project',
      feedMode: 'subscriptions',
      interestMode: 'data',
      text: 'Анализ данных для маркетинговых кампаний. Ищу коллег для совместного проекта.',
      createdAt: new Date(Date.now() - 345600000).toISOString(), // 4 days ago
      tags: ['data', 'business'],
      likes: 7,
      comments: [{ author: 'Асем С.', text: 'Интересно, давай обсудим.' }],
      authorKey: 'asem',
    },
    {
      id: 'seed-7',
      type: 'post',
      feedMode: 'recommendations',
      interestMode: 'business',
      text: 'Делюсь опытом запуска стартапа в Казахстане. Какие вопросы у вас?',
      createdAt: new Date(Date.now() - 432000000).toISOString(), // 5 days ago
      tags: ['business', 'product'],
      likes: 15,
      comments: [{ author: 'Томирис Н.', text: 'Расскажи про финансирование!' }],
      authorKey: 'daniyar',
    },
  ]);
};

const seedFriends = () => {
  const friendships = getFriendships();
  if (friendships.length > 0) return;
  saveFriendships([
    { from: 'me', to: 'tomiris', status: 'accepted' },
    { from: 'me', to: 'asem', status: 'accepted' },
    { from: 'me', to: 'daniyar', status: 'accepted' },
    { from: 'tomiris', to: 'me', status: 'accepted' },
    { from: 'asem', to: 'me', status: 'accepted' },
    { from: 'daniyar', to: 'me', status: 'accepted' },
  ]);
};

const seedTasks = () => {
  const tasks = getTasks();
  if (tasks.length > 0) return;
  saveTasks([
    { id: 'task-1', text: 'Завершить проект по AI-ассистенту', completed: false },
    { id: 'task-2', text: 'Подготовить презентацию для воркшопа', completed: false },
    { id: 'task-3', text: 'Связаться с командой по Campus Match', completed: true },
  ]);
};

const updateOnlineCount = () => {
  updateRegisteredCount();
};

// ── Theme toggle ──────────────────────────────────────────────
const THEME_KEY = 'pwe-theme';

const applyTheme = (theme) => {
  document.documentElement.setAttribute('data-theme', theme);
  const icon = document.querySelector('#themeToggleButton .theme-toggle-icon');
  if (icon) icon.textContent = theme === 'dark' ? '☀️' : '🌙';
};

const getSavedTheme = () => {
  const saved = window.localStorage.getItem(THEME_KEY);
  if (saved === 'dark' || saved === 'light') return saved;
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};

const toggleTheme = () => {
  const current = document.documentElement.getAttribute('data-theme') || 'light';
  const next = current === 'dark' ? 'light' : 'dark';
  applyTheme(next);
  window.localStorage.setItem(THEME_KEY, next);
};

document.querySelector('#themeToggleButton')?.addEventListener('click', toggleTheme);

// Listen for OS preference changes
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
  if (!window.localStorage.getItem(THEME_KEY)) {
    applyTheme(event.matches ? 'dark' : 'light');
  }
});
// ──────────────────────────────────────────────────────────────

const boot = () => {
  loadActivityData();
  applyTheme(getSavedTheme());
  updateOnlineCount();
  if (!isHostedMode()) {
    seedPosts();
    seedFriends();
    seedTasks();
  }
  renderSidebarTasks();
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
  const hashState = parseNavigationStateFromHash();
  if (account && session === account.email) {
    applyNavigationState(hashState && hashState.screen === 'app'
      ? hashState
      : {
          screen: 'app',
          viewId: 'workspaceView',
          viewedStudentKey: 'me',
          activeProfileTab: 'posts',
          activeChatId,
          modalId: '',
          projectOpen: false,
          projectName: '',
        }, { replace: true });
    return;
  }
  applyNavigationState(hashState || {
    screen: account ? 'login' : 'register',
    viewId: 'workspaceView',
    modalId: '',
    projectOpen: false,
    projectName: '',
  }, { replace: true });
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

window.addEventListener('popstate', (event) => {
  const nextState = event.state ? normalizeNavigationState(event.state) : (parseNavigationStateFromHash() || normalizeNavigationState({
    screen: canOpenAppShell() ? 'app' : (getAccount() ? 'login' : 'register'),
    viewId: 'workspaceView',
  }));
  applyNavigationState(nextState, { fromHistory: true });
});

// ── Extra button wiring ──
// Community triggers: СМОТРЕТЬ ВСЕ
document.querySelectorAll('[data-view-target="communitiesView"]').forEach((btn) => {
  btn.addEventListener('click', () => {
    showAppView('connectView');
    showActionNotice('Раздел сообществ открыт внутри вкладки "Сеть".');
  });
});

// Campus community rows → open community modal
document.querySelectorAll('.community-trigger').forEach((btn) => {
  btn.addEventListener('click', () => {
    const key = btn.dataset.communityKey;
    const community = COMMUNITIES[key];
    if (!community) return;
    communityModalTitle.textContent = community.title;
    communityModalDescription.textContent = community.description;
    communityModalTags.innerHTML = (community.tags || []).map((tag) => `<span>${escapeHtml(tag)}</span>`).join('');
    openModal(communityModal);
  });
});

// Internship cards → open internships view
document.querySelectorAll('.internship-card').forEach((card) => {
  card.addEventListener('click', () => showAppView('internshipsView'));
});

// Network / connect cards dynamic button clicks
document.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-view-target]');
  if (btn && !e.defaultPrevented) {
    e.preventDefault();
    showAppView(btn.dataset.viewTarget);
  }
});