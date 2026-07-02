/**
 * Mendflow emoji set — shortcodes :joy: + brand SVG stickers.
 * Interface icons use the same purple/gold flat style (ICONS).
 */
(function () {
  'use strict';

  const PURPLE = '#7c3aed';
  const PURPLE_LIGHT = '#a78bfa';
  const PURPLE_PALE = '#f0ebff';
  const CORAL = '#f97066';
  const FACE = '#fff8ee';
  const OUTLINE = '#c4b5fd';
  const INK = '#3b2d5c';
  const GOLD = '#fbbf24';
  const GOLD_DK = '#f59e0b';
  const BLUE = '#60a5fa';
  const GREEN = '#34d399';

  function svg(body) {
    return 'data:image/svg+xml,' + encodeURIComponent(
      `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">${body}</svg>`
    );
  }

  function neonIcon(body) {
    return svg(`
      <defs>
        <linearGradient id="niG" x1="0" y1="0" x2="64" y2="64">
          <stop offset="0%" stop-color="#22d3ee" stop-opacity=".38"/>
          <stop offset="100%" stop-color="#7c3aed" stop-opacity=".22"/>
        </linearGradient>
        <filter id="niN" x="-25%" y="-25%" width="150%" height="150%">
          <feGaussianBlur stdDeviation="1.1" result="b"/>
          <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
        </filter>
      </defs>
      <rect width="64" height="64" rx="16" fill="#0d0f18"/>
      <rect width="64" height="64" rx="16" fill="url(#niG)"/>
      <path d="M8 14 H56 M8 26 H56 M8 38 H56" stroke="#22d3ee" stroke-width=".35" opacity=".1"/>
      <path d="M14 8 V56 M26 8 V56 M38 8 V56 M50 8 V56" stroke="#22d3ee" stroke-width=".35" opacity=".1"/>
      <g filter="url(#niN)">${body}</g>
    `);
  }

  function sticker(body) {
    return svg(`<rect width="64" height="64" rx="18" fill="${PURPLE_PALE}"/>${body}`);
  }

  /** Brand chat / reaction stickers */
  const EMOJIS = {
    joy: {
      label: 'Радость',
      unicode: '😄',
      svg: sticker(`
        <circle cx="32" cy="34" r="21" fill="${FACE}" stroke="${OUTLINE}" stroke-width="2"/>
        <path d="M22 30 Q24 27 27 30" fill="none" stroke="${INK}" stroke-width="2.5" stroke-linecap="round"/>
        <path d="M37 30 Q40 27 43 30" fill="none" stroke="${INK}" stroke-width="2.5" stroke-linecap="round"/>
        <path d="M21 38 Q32 50 43 38" fill="none" stroke="${PURPLE}" stroke-width="3" stroke-linecap="round"/>
        <circle cx="20" cy="36" r="3" fill="${CORAL}" opacity=".45"/>
        <circle cx="44" cy="36" r="3" fill="${CORAL}" opacity=".45"/>
      `),
    },
    love: {
      label: 'Люблю',
      unicode: '🥰',
      svg: sticker(`
        <circle cx="32" cy="34" r="21" fill="${FACE}" stroke="${OUTLINE}" stroke-width="2"/>
        <path d="M24 29 C24 26 27 26 28 28 C29 26 32 26 32 29 C32 26 35 26 36 28 C37 26 40 26 40 29 C40 33 32 39 32 39 C32 39 24 33 24 29 Z" fill="${CORAL}"/>
        <path d="M22 40 Q32 48 42 40" fill="none" stroke="${PURPLE}" stroke-width="2.8" stroke-linecap="round"/>
        <circle cx="19" cy="35" r="3" fill="${CORAL}" opacity=".35"/>
        <circle cx="45" cy="35" r="3" fill="${CORAL}" opacity=".35"/>
      `),
    },
    fire: {
      label: 'Огонь',
      unicode: '🔥',
      svg: sticker(`
        <path d="M32 10 C36 18 44 20 44 30 C44 40 38 48 32 52 C26 48 20 40 20 30 C20 22 26 18 28 14 C28 20 24 24 26 30 C27 24 30 20 32 10 Z" fill="${CORAL}"/>
        <path d="M32 24 C34 28 38 29 38 34 C38 39 35 44 32 46 C29 44 26 39 26 34 C26 30 29 28 32 24 Z" fill="${GOLD}"/>
        <path d="M32 32 C33 34 35 35 35 37 C35 39 33 41 32 42 C31 41 29 39 29 37 C29 35 31 34 32 32 Z" fill="#fff" opacity=".85"/>
      `),
    },
    rocket: {
      label: 'Ракета',
      unicode: '🚀',
      svg: sticker(`
        <path d="M32 11 C40 17 42 26 42 33 L22 33 C22 26 24 17 32 11 Z" fill="${PURPLE}"/>
        <circle cx="32" cy="23" r="5" fill="#fff"/>
        <circle cx="32" cy="23" r="2.5" fill="${BLUE}"/>
        <path d="M22 33 L15 41 L25 38 Z" fill="${PURPLE_LIGHT}"/>
        <path d="M42 33 L49 41 L39 38 Z" fill="${PURPLE_LIGHT}"/>
        <path d="M28 39 Q32 52 36 39 Q32 45 28 39 Z" fill="${GOLD}"/>
        <circle cx="14" cy="44" r="2" fill="${GOLD}" opacity=".6"/>
        <circle cx="50" cy="40" r="1.5" fill="${GOLD}" opacity=".5"/>
      `),
    },
    idea: {
      label: 'Идея',
      unicode: '💡',
      svg: sticker(`
        <path d="M32 11 C24 11 18 18 18 26 C18 31 21 35 25 38 L25 44 L39 44 L39 38 C43 35 46 31 46 26 C46 18 40 11 32 11 Z" fill="${GOLD}" stroke="${GOLD_DK}" stroke-width="2" stroke-linejoin="round"/>
        <rect x="27" y="44" width="10" height="5" rx="2" fill="${PURPLE}"/>
        <rect x="25" y="49" width="14" height="4" rx="2" fill="${PURPLE_LIGHT}"/>
        <path d="M32 19 L32 28" stroke="#fff" stroke-width="2.5" stroke-linecap="round" opacity=".75"/>
        <path d="M27 22 L37 22" stroke="#fff" stroke-width="2" stroke-linecap="round" opacity=".5"/>
      `),
    },
    party: {
      label: 'Праздник',
      unicode: '🎉',
      svg: sticker(`
        <path d="M18 46 L28 18 L38 46 Z" fill="${PURPLE}" stroke="${PURPLE_LIGHT}" stroke-width="1.5" stroke-linejoin="round"/>
        <circle cx="28" cy="18" r="4" fill="${GOLD}"/>
        <rect x="16" y="46" width="24" height="5" rx="2.5" fill="${PURPLE_LIGHT}"/>
        <circle cx="46" cy="20" r="3" fill="${CORAL}"/>
        <circle cx="52" cy="32" r="2.5" fill="${GOLD}"/>
        <circle cx="44" cy="38" r="2" fill="${BLUE}"/>
        <path d="M48 14 L50 18 L54 18 L51 21 L52 25 L48 23 L44 25 L45 21 L42 18 L46 18 Z" fill="${GOLD}"/>
        <path d="M12 28 L14 32 L10 32 Z" fill="${PURPLE_LIGHT}"/>
        <path d="M14 42 L16 46 L12 46 Z" fill="${CORAL}"/>
      `),
    },
    clap: {
      label: 'Аплодисменты',
      unicode: '👏',
      svg: sticker(`
        <path d="M18 38 C16 32 18 24 24 22 C26 28 24 36 22 42 Z" fill="${FACE}" stroke="${OUTLINE}" stroke-width="1.8" stroke-linejoin="round"/>
        <path d="M28 36 C26 28 28 18 34 16 C36 24 34 34 32 42 Z" fill="${FACE}" stroke="${OUTLINE}" stroke-width="1.8" stroke-linejoin="round"/>
        <path d="M38 38 C40 30 40 20 34 18 C32 26 34 36 36 42 Z" fill="${FACE}" stroke="${OUTLINE}" stroke-width="1.8" stroke-linejoin="round"/>
        <path d="M46 40 C48 34 46 26 40 24 C38 30 40 38 42 44 Z" fill="${FACE}" stroke="${OUTLINE}" stroke-width="1.8" stroke-linejoin="round"/>
        <path d="M14 44 C20 48 28 50 36 48 C42 46 48 42 50 38" fill="none" stroke="${GOLD}" stroke-width="2.5" stroke-linecap="round" opacity=".7"/>
        <circle cx="12" cy="26" r="2" fill="${GOLD}" opacity=".55"/>
        <circle cx="52" cy="24" r="2" fill="${CORAL}" opacity=".55"/>
      `),
    },
    wow: {
      label: 'Вау',
      unicode: '🤩',
      svg: sticker(`
        <circle cx="32" cy="34" r="21" fill="${FACE}" stroke="${OUTLINE}" stroke-width="2"/>
        <path d="M24 28 L26 32 L30 32 L27 34 L28 38 L24 36 L20 38 L21 34 L18 32 L22 32 Z" fill="${GOLD}"/>
        <path d="M36 28 L38 32 L42 32 L39 34 L40 38 L36 36 L32 38 L33 34 L30 32 L34 32 Z" fill="${GOLD}"/>
        <ellipse cx="32" cy="42" rx="7" ry="4" fill="${PURPLE}" opacity=".85"/>
        <path d="M14 16 L16 20 L12 20 Z" fill="${GOLD}" opacity=".7"/>
        <path d="M50 18 L52 22 L48 22 Z" fill="${CORAL}" opacity=".7"/>
      `),
    },
    thumb: {
      label: 'Класс',
      unicode: '👍',
      svg: sticker(`
        <path d="M22 38 L22 28 C22 24 26 22 30 24 L34 14 C35 12 38 12 39 14 C40 16 39 19 37 20 L36 26 L44 26 C48 26 50 29 49 33 L46 44 C45 48 42 50 38 50 L26 50 C23 50 22 48 22 46 Z" fill="${FACE}" stroke="${OUTLINE}" stroke-width="2" stroke-linejoin="round"/>
        <rect x="14" y="30" width="8" height="20" rx="4" fill="${PURPLE_LIGHT}"/>
        <path d="M30 24 L32 18" stroke="${OUTLINE}" stroke-width="2" stroke-linecap="round"/>
      `),
    },
    cool: {
      label: 'Круто',
      unicode: '😎',
      svg: sticker(`
        <circle cx="32" cy="34" r="21" fill="${FACE}" stroke="${OUTLINE}" stroke-width="2"/>
        <rect x="17" y="28" width="30" height="10" rx="5" fill="${INK}"/>
        <rect x="19" y="30" width="11" height="6" rx="3" fill="${PURPLE_LIGHT}" opacity=".35"/>
        <rect x="34" y="30" width="11" height="6" rx="3" fill="${PURPLE_LIGHT}" opacity=".35"/>
        <line x1="17" y1="33" x2="12" y2="31" stroke="${INK}" stroke-width="2.5" stroke-linecap="round"/>
        <line x1="47" y1="33" x2="52" y2="31" stroke="${INK}" stroke-width="2.5" stroke-linecap="round"/>
        <path d="M24 42 Q32 47 40 42" fill="none" stroke="${PURPLE}" stroke-width="2.8" stroke-linecap="round"/>
      `),
    },
    sad: {
      label: 'Грусть',
      unicode: '😢',
      svg: sticker(`
        <circle cx="32" cy="34" r="21" fill="${FACE}" stroke="${OUTLINE}" stroke-width="2"/>
        <circle cx="24" cy="30" r="2.8" fill="${INK}"/>
        <circle cx="40" cy="30" r="2.8" fill="${INK}"/>
        <path d="M24 44 Q32 38 40 44" fill="none" stroke="${PURPLE}" stroke-width="2.8" stroke-linecap="round"/>
        <path d="M40 34 C42 38 44 42 46 46" fill="none" stroke="${BLUE}" stroke-width="2.5" stroke-linecap="round"/>
        <circle cx="46" cy="47" r="3" fill="${BLUE}" opacity=".75"/>
        <circle cx="44" cy="50" r="2" fill="${BLUE}" opacity=".5"/>
      `),
    },
    think: {
      label: 'Думаю',
      unicode: '🤔',
      svg: sticker(`
        <circle cx="32" cy="34" r="21" fill="${FACE}" stroke="${OUTLINE}" stroke-width="2"/>
        <circle cx="24" cy="30" r="2.8" fill="${INK}"/>
        <circle cx="40" cy="30" r="2.8" fill="${INK}"/>
        <path d="M26 42 L38 42" stroke="${PURPLE}" stroke-width="2.8" stroke-linecap="round"/>
        <path d="M42 38 C46 36 48 32 46 28" fill="none" stroke="${FACE}" stroke-width="8" stroke-linecap="round"/>
        <path d="M42 38 C46 36 48 32 46 28" fill="none" stroke="${OUTLINE}" stroke-width="2" stroke-linecap="round"/>
        <circle cx="48" cy="24" r="2" fill="${PURPLE_LIGHT}"/>
        <circle cx="52" cy="20" r="1.5" fill="${PURPLE_LIGHT}" opacity=".6"/>
      `),
    },
    star: {
      label: 'Звезда',
      unicode: '⭐',
      svg: sticker(`
        <path d="M32 13 L38 26 L52 28 L42 38 L44 52 L32 45 L20 52 L22 38 L12 28 L26 26 Z" fill="${GOLD}" stroke="${GOLD_DK}" stroke-width="1.5" stroke-linejoin="round"/>
        <circle cx="32" cy="32" r="4" fill="#fff" opacity=".45"/>
      `),
    },
    team: {
      label: 'Команда',
      unicode: '👥',
      svg: sticker(`
        <circle cx="24" cy="26" r="8" fill="${PURPLE}"/>
        <circle cx="42" cy="28" r="7" fill="${PURPLE_LIGHT}"/>
        <path d="M10 50 C10 40 16 36 24 36 C32 36 38 40 38 50 Z" fill="${PURPLE}"/>
        <path d="M36 50 C36 42 40 38 46 38 C52 38 56 42 56 50 Z" fill="${PURPLE_LIGHT}"/>
        <circle cx="32" cy="48" r="2" fill="${GOLD}" opacity=".55"/>
      `),
    },
    code: {
      label: 'Код',
      unicode: '💻',
      svg: sticker(`
        <rect x="10" y="18" width="44" height="28" rx="5" fill="${PURPLE}"/>
        <rect x="14" y="22" width="36" height="20" rx="3" fill="${INK}"/>
        <path d="M20 28 L24 32 L20 36" fill="none" stroke="${GREEN}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M30 36 L34 28" fill="none" stroke="${PURPLE_LIGHT}" stroke-width="2" stroke-linecap="round"/>
        <rect x="18" y="46" width="28" height="4" rx="2" fill="${PURPLE_LIGHT}"/>
        <circle cx="32" cy="48" r="1.5" fill="${GOLD}"/>
      `),
    },
    coffee: {
      label: 'Кофе',
      unicode: '☕',
      svg: sticker(`
        <path d="M18 24 H38 V42 C38 47 35 50 28 50 C21 50 18 47 18 42 Z" fill="#fff" stroke="${OUTLINE}" stroke-width="2" stroke-linejoin="round"/>
        <path d="M18 24 H38 C38 20 35 17 28 17 C21 17 18 20 18 24 Z" fill="${INK}"/>
        <path d="M38 28 H42 C46 28 48 31 48 34 C48 37 46 40 42 40 H38" fill="none" stroke="${OUTLINE}" stroke-width="2" stroke-linecap="round"/>
        <path d="M22 30 C24 28 26 28 28 30" fill="none" stroke="${CORAL}" stroke-width="2" stroke-linecap="round" opacity=".6"/>
        <path d="M30 34 C32 32 34 32 36 34" fill="none" stroke="${CORAL}" stroke-width="2" stroke-linecap="round" opacity=".6"/>
        <ellipse cx="28" cy="14" rx="8" ry="3" fill="${PURPLE_PALE}" stroke="${OUTLINE}" stroke-width="1.5"/>
      `),
    },
  };

  const UNICODE_TO_ID = {};
  Object.keys(EMOJIS).forEach((id) => {
    const u = EMOJIS[id].unicode;
    if (u) UNICODE_TO_ID[u] = id;
  });

  const ICONS = {
    trophy: {
      label: 'Достижение',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <path d="M22 14 H42 V25 C42 32 37 37 32 37 C27 37 22 32 22 25 Z" fill="${GOLD}" stroke="${GOLD_DK}" stroke-width="2" stroke-linejoin="round"/>
        <path d="M22 18 C14 18 14 29 23 29" fill="none" stroke="${GOLD_DK}" stroke-width="2.5" stroke-linecap="round"/>
        <path d="M42 18 C50 18 50 29 41 29" fill="none" stroke="${GOLD_DK}" stroke-width="2.5" stroke-linecap="round"/>
        <rect x="30" y="37" width="4" height="8" fill="${GOLD_DK}"/>
        <rect x="23" y="45" width="18" height="6" rx="2.5" fill="${PURPLE}"/>
        <path d="M32 20 l1.7 3.5 3.9 .6 -2.8 2.7 .7 3.9 -3.5-1.8 -3.5 1.8 .7-3.9 -2.8-2.7 3.9-.6 z" fill="#fff" opacity=".9"/>
      `),
    },
    chat: {
      label: 'Обсуждение',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <rect x="12" y="16" width="40" height="28" rx="10" fill="${PURPLE}"/>
        <path d="M22 41 L22 52 L34 43 Z" fill="${PURPLE}"/>
        <circle cx="24" cy="30" r="3" fill="#fff"/>
        <circle cx="32" cy="30" r="3" fill="#fff"/>
        <circle cx="40" cy="30" r="3" fill="#fff"/>
      `),
    },
    megaphone: {
      label: 'Анонс',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <path d="M14 28 L38 17 V47 L14 36 Z" fill="${PURPLE}"/>
        <rect x="10" y="28" width="6" height="8" rx="2" fill="${PURPLE}"/>
        <rect x="20" y="36" width="7" height="13" rx="3" fill="${PURPLE_LIGHT}"/>
        <path d="M42 24 C50 27 50 37 42 40" fill="none" stroke="${GOLD}" stroke-width="3" stroke-linecap="round"/>
        <path d="M47 19 C59 24 59 40 47 45" fill="none" stroke="${GOLD}" stroke-width="3" stroke-linecap="round" opacity=".6"/>
      `),
    },
    calendar: {
      label: 'Дата',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <rect x="13" y="17" width="38" height="33" rx="6" fill="#fff" stroke="${OUTLINE}" stroke-width="2"/>
        <path d="M13 26 a6 6 0 0 1 6-6 H45 a6 6 0 0 1 6 6 V27 H13 Z" fill="${PURPLE}"/>
        <rect x="22" y="13" width="4" height="10" rx="2" fill="${PURPLE_LIGHT}"/>
        <rect x="38" y="13" width="4" height="10" rx="2" fill="${PURPLE_LIGHT}"/>
        <circle cx="23" cy="35" r="2.6" fill="${CORAL}"/>
        <circle cx="32" cy="35" r="2.6" fill="${PURPLE}"/>
        <circle cx="41" cy="35" r="2.6" fill="${PURPLE}"/>
        <circle cx="23" cy="43" r="2.6" fill="${PURPLE}"/>
        <circle cx="32" cy="43" r="2.6" fill="${PURPLE}"/>
      `),
    },
    pin: {
      label: 'Место',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <path d="M32 12 C23 12 16 19 16 28 C16 40 32 53 32 53 C32 53 48 40 48 28 C48 19 41 12 32 12 Z" fill="${PURPLE}"/>
        <circle cx="32" cy="28" r="7" fill="#fff"/>
      `),
    },
    sparkles: {
      label: 'AI',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <path d="M29 11 C30 22 33 25 44 26 C33 27 30 30 29 41 C28 30 25 27 14 26 C25 25 28 22 29 11 Z" fill="${PURPLE}"/>
        <path d="M46 35 C46.6 41 48 42.4 54 43 C48 43.6 46.6 45 46 51 C45.4 45 44 43.6 38 43 C44 42.4 45.4 41 46 35 Z" fill="${GOLD}"/>
      `),
    },
    idea: {
      label: 'Идея',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <path d="M32 10 C24 10 18 17 18 26 C18 32 22 37 26 39 L26 46 L38 46 L38 39 C42 37 46 32 46 26 C46 17 40 10 32 10 Z" fill="${GOLD}" stroke="${GOLD_DK}" stroke-width="2" stroke-linejoin="round"/>
        <rect x="27" y="46" width="10" height="5" rx="2" fill="${PURPLE}"/>
        <rect x="25" y="51" width="14" height="4" rx="2" fill="${PURPLE_LIGHT}"/>
        <path d="M32 18 L32 28" stroke="#fff" stroke-width="2.5" stroke-linecap="round" opacity=".7"/>
      `),
    },
    memo: {
      label: 'Заметка',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <rect x="17" y="13" width="27" height="37" rx="5" fill="#fff" stroke="${OUTLINE}" stroke-width="2"/>
        <line x1="23" y1="23" x2="38" y2="23" stroke="${PURPLE}" stroke-width="2.6" stroke-linecap="round"/>
        <line x1="23" y1="30" x2="38" y2="30" stroke="${PURPLE_LIGHT}" stroke-width="2.6" stroke-linecap="round"/>
        <line x1="23" y1="37" x2="33" y2="37" stroke="${PURPLE_LIGHT}" stroke-width="2.6" stroke-linecap="round"/>
        <path d="M39 45 L51 33 L55 37 L43 49 L37 50 Z" fill="${GOLD}" stroke="${GOLD_DK}" stroke-width="1.5" stroke-linejoin="round"/>
      `),
    },
    globe: {
      label: 'Онлайн',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <circle cx="32" cy="32" r="18" fill="${BLUE}"/>
        <path d="M14 32 H50 M32 14 V50" stroke="#fff" stroke-width="1.8" opacity=".85"/>
        <ellipse cx="32" cy="32" rx="9" ry="18" fill="none" stroke="#fff" stroke-width="1.8" opacity=".85"/>
        <ellipse cx="32" cy="32" rx="18" ry="9" fill="none" stroke="#fff" stroke-width="1.8" opacity=".85"/>
        <circle cx="32" cy="32" r="18" fill="none" stroke="${PURPLE}" stroke-width="2"/>
      `),
    },
    target: {
      label: 'Цель',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <circle cx="32" cy="32" r="18" fill="none" stroke="${PURPLE}" stroke-width="3"/>
        <circle cx="32" cy="32" r="11" fill="none" stroke="${PURPLE_LIGHT}" stroke-width="3"/>
        <circle cx="32" cy="32" r="4" fill="${CORAL}"/>
      `),
    },
    people: {
      label: 'Участники',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <circle cx="24" cy="25" r="8" fill="${PURPLE}"/>
        <circle cx="42" cy="27" r="7" fill="${PURPLE_LIGHT}"/>
        <path d="M10 50 C10 40 16 36 24 36 C32 36 38 40 38 50 Z" fill="${PURPLE}"/>
        <path d="M36 50 C36 42 40 38 46 38 C52 38 56 42 56 50 Z" fill="${PURPLE_LIGHT}"/>
      `),
    },
    check: {
      label: 'Задачи',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="#ecfdf5"/>
        <rect x="14" y="14" width="36" height="36" rx="10" fill="${GREEN}" stroke="#059669" stroke-width="2"/>
        <path d="M22 32 L28 38 L44 22" fill="none" stroke="#fff" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/>
      `),
    },
    rocket: {
      label: 'Релиз',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <path d="M32 10 C40 16 43 26 43 34 L21 34 C21 26 24 16 32 10 Z" fill="${PURPLE}"/>
        <circle cx="32" cy="24" r="5" fill="#fff"/>
        <circle cx="32" cy="24" r="2.5" fill="${BLUE}"/>
        <path d="M21 34 L14 42 L24 39 Z" fill="${PURPLE_LIGHT}"/>
        <path d="M43 34 L50 42 L40 39 Z" fill="${PURPLE_LIGHT}"/>
        <path d="M28 40 Q32 52 36 40 Q32 46 28 40 Z" fill="${GOLD}"/>
      `),
    },
    star: {
      label: 'Рекомендуемое',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <path d="M32 12 L38 25 L52 27 L42 37 L44 51 L32 44 L20 51 L22 37 L12 27 L26 25 Z" fill="${GOLD}" stroke="${GOLD_DK}" stroke-width="1.5" stroke-linejoin="round"/>
      `),
    },
    edit: {
      label: 'Редактировать',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <path d="M16 40 L40 16 L48 24 L24 48 L14 50 Z" fill="#fff" stroke="${PURPLE}" stroke-width="2.5" stroke-linejoin="round"/>
        <path d="M38 18 L46 26" stroke="${PURPLE}" stroke-width="2.5" stroke-linecap="round"/>
        <path d="M40 16 L44 12 C46 10 50 10 52 12 C54 14 54 18 52 20 L48 24 Z" fill="${PURPLE}"/>
        <path d="M14 50 L24 48" stroke="${GOLD}" stroke-width="2.5" stroke-linecap="round"/>
      `),
    },
    compose: {
      label: 'Новый чат',
      svg: svg(`
        <defs>
          <linearGradient id="cmpG" x1="0" y1="0" x2="64" y2="64">
            <stop offset="0%" stop-color="#22d3ee" stop-opacity=".45"/>
            <stop offset="100%" stop-color="#7c3aed" stop-opacity=".2"/>
          </linearGradient>
          <filter id="cmpN" x="-25%" y="-25%" width="150%" height="150%">
            <feGaussianBlur stdDeviation="1.4" result="b"/>
            <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
          </filter>
        </defs>
        <rect width="64" height="64" rx="16" fill="#0a0c14"/>
        <rect width="64" height="64" rx="16" fill="url(#cmpG)"/>
        <path d="M8 14 H56 M8 26 H56 M8 38 H56" stroke="#22d3ee" stroke-width=".4" opacity=".1"/>
        <path d="M14 8 V56 M26 8 V56 M38 8 V56 M50 8 V56" stroke="#22d3ee" stroke-width=".4" opacity=".1"/>
        <path d="M17 43 L41 19 L49 27 L25 51 L13 53 Z" fill="none" stroke="#22d3ee" stroke-width="2.8" stroke-linejoin="round" filter="url(#cmpN)"/>
        <path d="M39 21 L47 29" stroke="#a78bfa" stroke-width="2.5" stroke-linecap="round"/>
        <path d="M41 19 L45 15 C47 13 51 13 53 15 C55 17 55 21 53 23 L49 27 Z" fill="#7c3aed"/>
        <path d="M13 53 L25 51" stroke="#fbbf24" stroke-width="2.2" stroke-linecap="round"/>
        <rect x="44" y="11" width="4.5" height="4.5" rx="1.1" fill="#22d3ee" opacity=".85"/>
        <rect x="51" y="17" width="3" height="3" rx=".7" fill="#c4b5fd" opacity=".7"/>
        <rect x="46" y="24" width="2" height="2" rx=".4" fill="#22d3ee" opacity=".55"/>
      `),
    },
    camera: {
      label: 'Фото',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <rect x="8" y="20" width="48" height="34" rx="7" fill="${PURPLE}"/>
        <path d="M22 20 L26 13 L38 13 L42 20" fill="${PURPLE}" stroke="${PURPLE}" stroke-width="1" stroke-linejoin="round"/>
        <circle cx="32" cy="37" r="10" fill="#fff" opacity="0.18"/>
        <circle cx="32" cy="37" r="7" fill="#fff"/>
        <circle cx="32" cy="37" r="4" fill="${PURPLE}"/>
        <circle cx="32" cy="37" r="1.5" fill="#fff"/>
        <circle cx="46" cy="26" r="2.5" fill="#fff" opacity="0.6"/>
      `),
    },
    book: {
      label: 'Книга',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <rect x="12" y="12" width="14" height="40" rx="4" fill="${PURPLE}"/>
        <rect x="22" y="12" width="30" height="40" rx="4" fill="#fff" stroke="${OUTLINE}" stroke-width="1.5"/>
        <line x1="28" y1="22" x2="46" y2="22" stroke="${PURPLE}" stroke-width="2.5" stroke-linecap="round"/>
        <line x1="28" y1="30" x2="46" y2="30" stroke="${PURPLE_LIGHT}" stroke-width="2.5" stroke-linecap="round"/>
        <line x1="28" y1="38" x2="39" y2="38" stroke="${PURPLE_LIGHT}" stroke-width="2" stroke-linecap="round"/>
        <circle cx="17" cy="18" r="2" fill="#fff" opacity=".5"/>
      `),
    },
    trash: {
      label: 'Удалить',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <path d="M22 24 H42 L40 50 H24 Z" fill="#fff" stroke="${CORAL}" stroke-width="2.5" stroke-linejoin="round"/>
        <rect x="18" y="20" width="28" height="6" rx="2" fill="${CORAL}"/>
        <path d="M28 16 H36 V22 H28 Z" fill="none" stroke="${CORAL}" stroke-width="2.5" stroke-linejoin="round"/>
        <line x1="30" y1="30" x2="30" y2="44" stroke="${CORAL}" stroke-width="2.5" stroke-linecap="round"/>
        <line x1="34" y1="30" x2="34" y2="44" stroke="${CORAL}" stroke-width="2.5" stroke-linecap="round"/>
      `),
    },
    eye: {
      label: 'Превью',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <path d="M32 18 C18 18 10 32 10 32 C10 32 18 46 32 46 C46 46 54 32 54 32 C54 32 46 18 32 18 Z" fill="#fff" stroke="${PURPLE}" stroke-width="2.5" stroke-linejoin="round"/>
        <circle cx="32" cy="32" r="8" fill="${PURPLE}"/>
        <circle cx="35" cy="29" r="2.5" fill="#fff" opacity=".75"/>
      `),
    },
    search: {
      label: 'Поиск',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <circle cx="28" cy="28" r="14" fill="#fff" stroke="${PURPLE}" stroke-width="3"/>
        <line x1="38" y1="38" x2="50" y2="50" stroke="${PURPLE}" stroke-width="4" stroke-linecap="round"/>
      `),
    },
    plane: {
      label: 'Обмен',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <path d="M8 34 L26 24 L48 12 C52 10 56 14 54 18 L42 38 L46 52 L38 48 L34 36 L22 44 L18 42 Z" fill="${PURPLE}"/>
        <path d="M44 16 L50 22" stroke="${PURPLE_LIGHT}" stroke-width="2" stroke-linecap="round" opacity=".6"/>
        <circle cx="46" cy="16" r="2.5" fill="#fff" opacity=".5"/>
      `),
    },
    clock: {
      label: 'Длительность',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <circle cx="32" cy="34" r="18" fill="#fff" stroke="${PURPLE}" stroke-width="2.5"/>
        <line x1="32" y1="34" x2="32" y2="22" stroke="${PURPLE}" stroke-width="3" stroke-linecap="round"/>
        <line x1="32" y1="34" x2="43" y2="40" stroke="${CORAL}" stroke-width="3" stroke-linecap="round"/>
        <circle cx="32" cy="34" r="2.5" fill="${PURPLE}"/>
        <line x1="32" y1="16" x2="32" y2="19" stroke="${PURPLE}" stroke-width="2" stroke-linecap="round"/>
        <line x1="50" y1="34" x2="47" y2="34" stroke="${PURPLE}" stroke-width="2" stroke-linecap="round"/>
        <line x1="32" y1="52" x2="32" y2="49" stroke="${PURPLE}" stroke-width="2" stroke-linecap="round"/>
        <line x1="14" y1="34" x2="17" y2="34" stroke="${PURPLE}" stroke-width="2" stroke-linecap="round"/>
      `),
    },
    grad: {
      label: 'Образование',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <polygon points="32,12 56,26 32,40 8,26" fill="${PURPLE}"/>
        <path d="M18 30 L18 46 Q18 52 32 52 Q46 52 46 46 L46 30" fill="none" stroke="${PURPLE}" stroke-width="3" stroke-linecap="round"/>
        <rect x="50" y="24" width="5" height="20" rx="2.5" fill="${PURPLE_LIGHT}"/>
        <circle cx="52" cy="46" r="4" fill="${CORAL}"/>
        <line x1="50" y1="24" x2="56" y2="26" stroke="${PURPLE}" stroke-width="1.5" stroke-linecap="round"/>
      `),
    },
    coin: {
      label: 'Стоимость',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <circle cx="32" cy="32" r="18" fill="${GOLD}" stroke="${GOLD_DK}" stroke-width="2"/>
        <circle cx="32" cy="32" r="13" fill="${GOLD_DK}" opacity=".25"/>
        <path d="M32 20 V44" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/>
        <path d="M26 25 Q26 20 32 20 Q40 20 40 27 Q40 32 32 32 Q40 32 40 38 Q40 44 32 44 Q26 44 26 38" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/>
      `),
    },
    building: {
      label: 'Университет',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <path d="M10 32 L32 12 L54 32" fill="${PURPLE}"/>
        <rect x="10" y="30" width="44" height="6" rx="1" fill="${PURPLE}"/>
        <rect x="14" y="36" width="8" height="16" rx="1" fill="${PURPLE}"/>
        <rect x="28" y="30" width="8" height="22" rx="1" fill="${PURPLE}"/>
        <rect x="42" y="36" width="8" height="16" rx="1" fill="${PURPLE}"/>
        <rect x="10" y="52" width="44" height="4" rx="2" fill="${PURPLE}"/>
        <rect x="30" y="36" width="4" height="8" rx="1" fill="#fff"/>
        <circle cx="32" cy="20" r="2.5" fill="#fff" opacity=".6"/>
      `),
    },
    feed: {
      label: 'Лента',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <rect x="10" y="12" width="44" height="40" rx="6" fill="#fff" stroke="${OUTLINE}" stroke-width="1.5"/>
        <rect x="16" y="18" width="32" height="7" rx="2.5" fill="${PURPLE}"/>
        <rect x="16" y="30" width="14" height="14" rx="3" fill="${PURPLE_PALE}" stroke="${OUTLINE}" stroke-width="1.5"/>
        <line x1="35" y1="33" x2="43" y2="33" stroke="${PURPLE}" stroke-width="2.5" stroke-linecap="round"/>
        <line x1="35" y1="39" x2="43" y2="39" stroke="${PURPLE_LIGHT}" stroke-width="2" stroke-linecap="round"/>
        <line x1="16" y1="48" x2="48" y2="48" stroke="${PURPLE_LIGHT}" stroke-width="1.5" stroke-linecap="round" opacity=".6"/>
      `),
    },
    overview: {
      label: 'Обзор',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <ellipse cx="32" cy="32" rx="20" ry="13" fill="none" stroke="${PURPLE}" stroke-width="3"/>
        <circle cx="32" cy="32" r="7" fill="${PURPLE}"/>
        <circle cx="35" cy="29" r="2.5" fill="#fff" opacity=".75"/>
        <path d="M12 32 C14 28 20 20 32 20" fill="none" stroke="${PURPLE_LIGHT}" stroke-width="1.5" stroke-linecap="round" opacity=".5"/>
      `),
    },
    grant: {
      label: 'Грант',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <circle cx="32" cy="30" r="16" fill="#34d399" stroke="#10b981" stroke-width="2"/>
        <path d="M22 30 L28 36 L42 22" fill="none" stroke="#fff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M24 48 L32 56 L40 48 L36 46 L32 50 L28 46 Z" fill="${GOLD}" stroke="${GOLD_DK}" stroke-width="1.5" stroke-linejoin="round"/>
      `),
    },
    alarm: {
      label: 'Дедлайн',
      svg: svg(`
        <rect width="64" height="64" rx="16" fill="${PURPLE_PALE}"/>
        <circle cx="32" cy="34" r="18" fill="#fff" stroke="${CORAL}" stroke-width="2.5"/>
        <line x1="32" y1="34" x2="32" y2="24" stroke="${PURPLE}" stroke-width="3" stroke-linecap="round"/>
        <line x1="32" y1="34" x2="41" y2="38" stroke="${CORAL}" stroke-width="3" stroke-linecap="round"/>
        <circle cx="32" cy="34" r="2.5" fill="${CORAL}"/>
        <path d="M20 18 L14 12 M44 18 L50 12" stroke="${CORAL}" stroke-width="2.5" stroke-linecap="round"/>
        <line x1="26" y1="10" x2="38" y2="10" stroke="${CORAL}" stroke-width="2" stroke-linecap="round"/>
      `),
    },
    moon: {
      label: 'Тёмная тема',
      svg: svg(`
        <defs>
          <linearGradient id="moonGlow" x1="0" y1="0" x2="64" y2="64">
            <stop offset="0%" stop-color="#22d3ee" stop-opacity=".35"/>
            <stop offset="100%" stop-color="#7c3aed" stop-opacity=".15"/>
          </linearGradient>
          <filter id="moonNeon" x="-20%" y="-20%" width="140%" height="140%">
            <feGaussianBlur stdDeviation="1.2" result="b"/>
            <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
          </filter>
        </defs>
        <rect width="64" height="64" rx="18" fill="#0d0f1a"/>
        <rect width="64" height="64" rx="18" fill="url(#moonGlow)"/>
        <path d="M8 12 H56 M8 24 H56 M8 36 H56 M8 48 H56" stroke="#22d3ee" stroke-width=".35" opacity=".08"/>
        <path d="M12 8 V56 M24 8 V56 M36 8 V56 M48 8 V56" stroke="#22d3ee" stroke-width=".35" opacity=".08"/>
        <path d="M36 14 C25 16 17 25 17 35 C17 47 27 55 39 53 C29 53 21 45 21 35 C21 24 28 16 36 14 Z" fill="#c4b5fd" filter="url(#moonNeon)"/>
        <path d="M36 14 C25 16 17 25 17 35 C17 47 27 55 39 53 C29 53 21 45 21 35 C21 24 28 16 36 14 Z" fill="none" stroke="#22d3ee" stroke-width=".8" opacity=".45"/>
        <rect x="43" y="13" width="5" height="5" rx="1.2" fill="#a78bfa"/>
        <rect x="51" y="21" width="3.5" height="3.5" rx=".8" fill="#22d3ee" opacity=".85"/>
        <rect x="45" y="28" width="2.5" height="2.5" rx=".5" fill="#c4b5fd" opacity=".9"/>
        <rect x="52" y="32" width="2" height="2" rx=".4" fill="#22d3ee" opacity=".6"/>
        <circle cx="52" cy="14" r="1" fill="#fff" opacity=".5"/>
      `),
    },
    sun: {
      label: 'Светлая тема',
      svg: svg(`
        <defs>
          <radialGradient id="sunHalo" cx="50%" cy="50%" r="50%">
            <stop offset="0%" stop-color="#fef08a" stop-opacity="1"/>
            <stop offset="70%" stop-color="#fbbf24" stop-opacity=".6"/>
            <stop offset="100%" stop-color="#f59e0b" stop-opacity="0"/>
          </radialGradient>
          <filter id="sunGlow" x="-30%" y="-30%" width="160%" height="160%">
            <feGaussianBlur stdDeviation="2" result="b"/>
            <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
          </filter>
        </defs>
        <rect width="64" height="64" rx="18" fill="#1a1408"/>
        <rect width="64" height="64" rx="18" fill="url(#sunHalo)" opacity=".25"/>
        <circle cx="32" cy="32" r="18" fill="#fbbf24" opacity=".15" filter="url(#sunGlow)"/>
        <circle cx="32" cy="32" r="10" fill="${GOLD}" stroke="#fde047" stroke-width="1"/>
        <circle cx="32" cy="32" r="5.5" fill="#fff" opacity=".55"/>
        <line x1="32" y1="6"  x2="32" y2="14" stroke="#fde047" stroke-width="2.5" stroke-linecap="round" opacity=".9"/>
        <line x1="32" y1="50" x2="32" y2="58" stroke="#fde047" stroke-width="2.5" stroke-linecap="round" opacity=".9"/>
        <line x1="6"  y1="32" x2="14" y2="32" stroke="#fde047" stroke-width="2.5" stroke-linecap="round" opacity=".9"/>
        <line x1="50" y1="32" x2="58" y2="32" stroke="#fde047" stroke-width="2.5" stroke-linecap="round" opacity=".9"/>
        <line x1="13" y1="13" x2="18" y2="18" stroke="#fbbf24" stroke-width="2" stroke-linecap="round"/>
        <line x1="46" y1="46" x2="51" y2="51" stroke="#fbbf24" stroke-width="2" stroke-linecap="round"/>
        <line x1="51" y1="13" x2="46" y2="18" stroke="#fbbf24" stroke-width="2" stroke-linecap="round"/>
        <line x1="18" y1="46" x2="13" y2="51" stroke="#fbbf24" stroke-width="2" stroke-linecap="round"/>
        <rect x="44" y="10" width="3" height="3" rx=".5" fill="#fde047" opacity=".7"/>
        <rect x="48" y="16" width="2" height="2" rx=".4" fill="#fbbf24" opacity=".5"/>
      `),
    },
    thread: {
      label: 'Ветка',
      svg: neonIcon(`
        <path d="M20 18 C20 14 23 11 27 11 H37 C41 11 44 14 44 18 V24" fill="none" stroke="#a78bfa" stroke-width="2.8" stroke-linecap="round"/>
        <path d="M20 24 V38 C20 42 23 45 27 45 H37 C41 45 44 42 44 38 V32" fill="none" stroke="#22d3ee" stroke-width="2.8" stroke-linecap="round"/>
        <circle cx="44" cy="32" r="4" fill="#7c3aed" stroke="#c4b5fd" stroke-width="1.5"/>
        <circle cx="20" cy="18" r="4" fill="#7c3aed" stroke="#c4b5fd" stroke-width="1.5"/>
        <rect x="46" y="12" width="3" height="3" rx=".7" fill="#22d3ee" opacity=".75"/>
        <rect x="12" y="40" width="2.5" height="2.5" rx=".5" fill="#c4b5fd" opacity=".8"/>
      `),
    },
    bolt: {
      label: 'Активность',
      svg: neonIcon(`
        <path d="M36 10 L22 34 H32 L28 54 L46 28 H36 Z" fill="#fbbf24" stroke="#fde047" stroke-width="1.5" stroke-linejoin="round"/>
        <path d="M36 10 L22 34 H32 L28 54 L46 28 H36 Z" fill="none" stroke="#22d3ee" stroke-width=".8" opacity=".55"/>
        <rect x="48" y="14" width="3" height="3" rx=".6" fill="#22d3ee" opacity=".7"/>
        <rect x="10" y="44" width="2.5" height="2.5" rx=".5" fill="#a78bfa" opacity=".65"/>
      `),
    },
    pulse: {
      label: 'Открыт',
      svg: neonIcon(`
        <circle cx="32" cy="32" r="10" fill="#34d399" opacity=".25"/>
        <circle cx="32" cy="32" r="7" fill="#34d399" stroke="#6ee7b7" stroke-width="2"/>
        <circle cx="32" cy="32" r="3" fill="#ecfdf5"/>
        <circle cx="32" cy="32" r="14" fill="none" stroke="#34d399" stroke-width="1.5" opacity=".45"/>
        <circle cx="32" cy="32" r="18" fill="none" stroke="#22d3ee" stroke-width=".8" opacity=".25"/>
      `),
    },
    resolved: {
      label: 'Решён',
      svg: neonIcon(`
        <circle cx="32" cy="32" r="16" fill="#34d399" opacity=".18"/>
        <circle cx="32" cy="32" r="12" fill="none" stroke="#34d399" stroke-width="2.5"/>
        <path d="M24 32 L29 37 L41 25" fill="none" stroke="#6ee7b7" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
        <rect x="46" y="12" width="3" height="3" rx=".6" fill="#22d3ee" opacity=".7"/>
      `),
    },
    thought: {
      label: 'Пусто',
      svg: neonIcon(`
        <ellipse cx="32" cy="28" rx="16" ry="12" fill="#7c3aed" opacity=".35" stroke="#a78bfa" stroke-width="2"/>
        <circle cx="22" cy="44" r="4" fill="#7c3aed" opacity=".45"/>
        <circle cx="32" cy="50" r="2.5" fill="#7c3aed" opacity=".35"/>
        <circle cx="40" cy="44" r="3" fill="#7c3aed" opacity=".4"/>
        <circle cx="26" cy="24" r="2" fill="#22d3ee" opacity=".55"/>
        <circle cx="38" cy="26" r="1.5" fill="#c4b5fd" opacity=".7"/>
      `),
    },
    reply: {
      label: 'Ответить',
      svg: neonIcon(`
        <path d="M44 18 C44 14 41 11 37 11 H21 C17 11 14 14 14 18 V30 L22 24 H37 C41 24 44 21 44 18 Z" fill="#7c3aed" stroke="#a78bfa" stroke-width="1.8" stroke-linejoin="round"/>
        <path d="M18 38 L14 48 L24 42" fill="none" stroke="#22d3ee" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
        <rect x="48" y="14" width="3" height="3" rx=".6" fill="#22d3ee" opacity=".75"/>
      `),
    },
    useful: {
      label: 'Полезно',
      svg: neonIcon(`
        <path d="M24 42 V30 C24 26 27 24 31 25 L34 16 C35 14 38 14 39 16 C40 18 39 21 37 22 L36 28 H44 C48 28 50 31 49 35 L46 44 C45 48 42 50 38 50 H26 C24 50 22 48 22 46 Z" fill="#7c3aed" stroke="#a78bfa" stroke-width="1.8" stroke-linejoin="round"/>
        <rect x="14" y="32" width="8" height="18" rx="4" fill="#22d3ee" opacity=".85"/>
        <path d="M31 25 L33 18" stroke="#c4b5fd" stroke-width="2" stroke-linecap="round"/>
        <rect x="46" y="12" width="3" height="3" rx=".6" fill="#22d3ee" opacity=".75"/>
      `),
    },
    bookmark: {
      label: 'Закладка',
      svg: neonIcon(`
        <path d="M18 14 H46 V50 L32 40 L18 50 Z" fill="none" stroke="#a78bfa" stroke-width="2.8" stroke-linejoin="round"/>
        <path d="M18 14 H46 V50 L32 40 L18 50 Z" fill="#7c3aed" opacity=".35"/>
        <path d="M32 22 V36" stroke="#22d3ee" stroke-width="2.5" stroke-linecap="round"/>
        <path d="M26 28 H38" stroke="#22d3ee" stroke-width="2.5" stroke-linecap="round"/>
        <rect x="48" y="14" width="3" height="3" rx=".6" fill="#22d3ee" opacity=".7"/>
      `),
    },
    read: {
      label: 'Чтение',
      svg: neonIcon(`
        <circle cx="32" cy="34" r="16" fill="none" stroke="#a78bfa" stroke-width="2.5"/>
        <line x1="32" y1="34" x2="32" y2="22" stroke="#22d3ee" stroke-width="3" stroke-linecap="round"/>
        <line x1="32" y1="34" x2="42" y2="40" stroke="#fbbf24" stroke-width="3" stroke-linecap="round"/>
        <circle cx="32" cy="34" r="3" fill="#c4b5fd"/>
        <circle cx="32" cy="34" r="20" fill="none" stroke="#22d3ee" stroke-width=".8" opacity=".35"/>
      `),
    },
    article: {
      label: 'Статья',
      svg: neonIcon(`
        <rect x="16" y="12" width="32" height="40" rx="6" fill="none" stroke="#a78bfa" stroke-width="2.5"/>
        <rect x="16" y="12" width="32" height="40" rx="6" fill="#7c3aed" opacity=".22"/>
        <line x1="22" y1="24" x2="42" y2="24" stroke="#22d3ee" stroke-width="2.5" stroke-linecap="round"/>
        <line x1="22" y1="32" x2="38" y2="32" stroke="#c4b5fd" stroke-width="2" stroke-linecap="round"/>
        <line x1="22" y1="40" x2="34" y2="40" stroke="#c4b5fd" stroke-width="2" stroke-linecap="round"/>
        <rect x="44" y="16" width="3" height="3" rx=".6" fill="#22d3ee" opacity=".75"/>
      `),
    },
  };

  /** Achievement badge stickers — rich 3D style for profile badges */
  function badge3d(body, opts = {}) {
    const bg = opts.bg || '#eef2ff';
    const bg2 = opts.bg2 || '#e0e7ff';
    return svg(`
      <defs>
        <radialGradient id="bBg" cx="38%" cy="28%" r="72%">
          <stop offset="0%" stop-color="#ffffff"/>
          <stop offset="55%" stop-color="${bg}"/>
          <stop offset="100%" stop-color="${bg2}"/>
        </radialGradient>
        <linearGradient id="bSh" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#fff" stop-opacity=".55"/>
          <stop offset="100%" stop-color="#000" stop-opacity=".08"/>
        </linearGradient>
        <filter id="bDrop" x="-25%" y="-20%" width="150%" height="150%">
          <feDropShadow dx="0" dy="2.5" stdDeviation="2.2" flood-color="#3b2d5c" flood-opacity=".28"/>
        </filter>
      </defs>
      <rect width="64" height="64" rx="18" fill="url(#bBg)"/>
      <ellipse cx="32" cy="54" rx="16" ry="4" fill="#3b2d5c" opacity=".08"/>
      <g filter="url(#bDrop)">${body}</g>
      <ellipse cx="24" cy="18" rx="10" ry="6" fill="url(#bSh)" opacity=".35"/>
    `);
  }

  const BADGE_ICONS = {
    product_launch: {
      label: 'Запустил продукт',
      svg: badge3d(`
        <path d="M32 9 C42 16 45 27 45 35 L19 35 C19 27 22 16 32 9 Z" fill="${PURPLE}"/>
        <path d="M32 9 C38 14 41 22 41 28 L23 28 C23 22 26 14 32 9 Z" fill="${PURPLE_LIGHT}" opacity=".45"/>
        <circle cx="32" cy="24" r="6" fill="#fff"/>
        <circle cx="32" cy="24" r="3.2" fill="${BLUE}"/>
        <circle cx="30" cy="22" r="1.2" fill="#fff" opacity=".85"/>
        <path d="M19 35 L11 44 L22 40 Z" fill="${PURPLE_LIGHT}"/>
        <path d="M45 35 L53 44 L42 40 Z" fill="${PURPLE_LIGHT}"/>
        <path d="M27 38 Q32 54 37 38 Q32 47 27 38 Z" fill="${GOLD}"/>
        <path d="M29 42 Q32 50 35 42" fill="${GOLD_DK}" opacity=".55"/>
        <circle cx="10" cy="46" r="2.2" fill="${GOLD}" opacity=".65"/>
        <circle cx="54" cy="42" r="1.8" fill="${GOLD}" opacity=".5"/>
      `, { bg: '#f5f3ff', bg2: '#ede9fe' }),
    },
    team_builder: {
      label: 'Построил команду',
      svg: badge3d(`
        <circle cx="23" cy="24" r="9" fill="${PURPLE}"/>
        <circle cx="23" cy="24" r="9" fill="#fff" opacity=".12"/>
        <circle cx="42" cy="26" r="8" fill="${PURPLE_LIGHT}"/>
        <circle cx="42" cy="26" r="8" fill="#fff" opacity=".1"/>
        <path d="M8 52 C8 41 14 36 23 36 C32 36 38 41 38 52 Z" fill="${PURPLE}"/>
        <path d="M36 52 C36 43 40 38 47 38 C54 38 58 43 58 52 Z" fill="${PURPLE_LIGHT}"/>
        <circle cx="20" cy="22" r="1.5" fill="#fff" opacity=".7"/>
        <circle cx="39" cy="24" r="1.3" fill="#fff" opacity=".65"/>
      `, { bg: '#f0f9ff', bg2: '#e0f2fe' }),
    },
    serial_founder: {
      label: 'Серийный основатель',
      svg: badge3d(`
        <path d="M32 10 C23 10 16 18 16 27 C16 33 19 38 23 41 L23 47 L41 47 L41 41 C45 38 48 33 48 27 C48 18 41 10 32 10 Z" fill="${GOLD}" stroke="${GOLD_DK}" stroke-width="1.5" stroke-linejoin="round"/>
        <path d="M32 10 C27 10 22 14 20 20 C24 18 28 17 32 17 C36 17 40 18 44 20 C42 14 37 10 32 10 Z" fill="#fff" opacity=".35"/>
        <rect x="26" y="47" width="12" height="5" rx="2" fill="${PURPLE}"/>
        <rect x="24" y="52" width="16" height="4" rx="2" fill="${PURPLE_LIGHT}"/>
        <path d="M32 18 L32 30" stroke="#fff" stroke-width="3" stroke-linecap="round" opacity=".8"/>
        <path d="M26 22 L38 22" stroke="#fff" stroke-width="2.2" stroke-linecap="round" opacity=".55"/>
        <circle cx="32" cy="27" r="8" fill="#fff" opacity=".18"/>
      `, { bg: '#fffbeb', bg2: '#fef3c7' }),
    },
    first_course: {
      label: 'Первый курс',
      svg: badge3d(`
        <g transform="rotate(-10 19 40)">
          <rect x="11" y="30" width="15" height="22" rx="2.5" fill="${GREEN}"/>
          <rect x="13" y="30" width="2.5" height="22" rx="1" fill="#fff" opacity=".22"/>
        </g>
        <g transform="rotate(-3 28 38)">
          <rect x="19" y="26" width="15" height="26" rx="2.5" fill="${CORAL}"/>
          <rect x="21" y="26" width="2.5" height="26" rx="1" fill="#fff" opacity=".2"/>
        </g>
        <rect x="28" y="18" width="18" height="32" rx="3" fill="${BLUE}"/>
        <rect x="31" y="18" width="3.5" height="32" rx="1.2" fill="#fff" opacity=".28"/>
        <line x1="34" y1="26" x2="42" y2="26" stroke="#fff" stroke-width="2" stroke-linecap="round" opacity=".55"/>
        <line x1="34" y1="32" x2="42" y2="32" stroke="#fff" stroke-width="1.8" stroke-linecap="round" opacity=".45"/>
        <line x1="34" y1="38" x2="40" y2="38" stroke="#fff" stroke-width="1.6" stroke-linecap="round" opacity=".35"/>
        <circle cx="15" cy="24" r="2" fill="${PURPLE_LIGHT}" opacity=".55"/>
      `, { bg: '#f5f3ff', bg2: '#ede9fe' }),
    },
    top_course: {
      label: 'Топ-курс',
      svg: badge3d(`
        <path d="M32 10 L39 25.5 L55.5 28 L43.5 39.5 L47 56 L32 47.5 L17 56 L20.5 39.5 L8.5 28 L25 25.5 Z" fill="${GOLD}" stroke="${GOLD_DK}" stroke-width="1.8" stroke-linejoin="round"/>
        <path d="M32 14 L37.5 26 L49 27.5 L39.5 36.5 L42 49 L32 42.5 L22 49 L24.5 36.5 L15 27.5 L26.5 26 Z" fill="#fff" opacity=".22"/>
        <circle cx="32" cy="31" r="6" fill="#fff" opacity=".42"/>
        <path d="M29 20 L32 16 L35 20" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" opacity=".65"/>
        <circle cx="26" cy="22" r="1.2" fill="#fff" opacity=".5"/>
        <circle cx="38" cy="22" r="1" fill="#fff" opacity=".45"/>
      `, { bg: '#fffbeb', bg2: '#fef3c7' }),
    },
  };

  const BADGE_KEY_TO_ICON = {
    product_launch: 'product_launch',
    team_builder: 'team_builder',
    serial_founder: 'serial_founder',
    first_course: 'first_course',
    top_course: 'top_course',
  };

  /** Системные emoji → брендовые ICONS в тексте постов */
  const ICON_UNICODE = {
    '✅': 'check',
    '🏆': 'trophy',
    '🚀': 'rocket',
    '👥': 'people',
    '💬': 'chat',
    '📢': 'megaphone',
    '📄': 'memo',
    '📖': 'book',
    '✏': 'edit',
    '✏️': 'edit',
    '🗑': 'trash',
    '🗑️': 'trash',
    '👁': 'eye',
    '👁️': 'eye',
    '🔍': 'search',
  };

  const REACTION_IDS = ['joy', 'love', 'fire', 'rocket', 'idea', 'party', 'clap', 'wow'];

  function token(id) {
    return ':' + id + ':';
  }

  function parseToken(str) {
    if (!str || str.charAt(0) !== ':') return null;
    const end = str.indexOf(':', 1);
    if (end <= 1) return null;
    const id = str.slice(1, end);
    return EMOJIS[id] ? id : null;
  }

  function escAttr(s) {
    return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
  }

  function html(id, size) {
    const e = EMOJIS[id];
    if (!e || !e.svg) return '';
    const px = size || 24;
    return `<img class="mf-emoji mf-sticker" src="${e.svg}" alt="${escAttr(e.label)}" title="${escAttr(e.label)}" width="${px}" height="${px}" draggable="false" loading="lazy">`;
  }

  function charHtml(id, size) {
    return html(id, size || 36);
  }

  function iconHtml(id, size) {
    const e = ICONS[id];
    if (!e) return '';
    const px = size || 18;
    return `<img class="mf-emoji mf-ico" src="${e.svg}" alt="${escAttr(e.label)}" title="${escAttr(e.label)}" width="${px}" height="${px}" draggable="false" loading="lazy">`;
  }

  function badgeIconHtml(key, size) {
    const id = BADGE_KEY_TO_ICON[key] || key;
    const e = BADGE_ICONS[id];
    if (!e) return '';
    const px = size || 24;
    return `<img class="mf-emoji mf-badge-sticker" src="${e.svg}" alt="${escAttr(e.label)}" title="${escAttr(e.label)}" width="${px}" height="${px}" draggable="false" loading="lazy">`;
  }

  function attachIcons(root) {
    const scope = root || document;
    scope.querySelectorAll('[data-mf-ico]').forEach((el) => {
      if (el.dataset.mfIcoBound) return;
      const id = el.getAttribute('data-mf-ico');
      const markup = iconHtml(id, parseInt(el.getAttribute('data-mf-ico-size'), 10) || undefined);
      if (markup) {
        el.innerHTML = markup;
        el.dataset.mfIcoBound = '1';
      }
    });
  }

  function preview(text, maxLen) {
    if (text == null || text === '') return '';
    let raw = String(text).replace(/:([a-z]+):/g, (match, id) => {
      const e = EMOJIS[id];
      return e ? e.unicode : match;
    });
    if (maxLen && raw.length > maxLen) raw = raw.slice(0, maxLen) + '…';
    return raw;
  }

  function reactionHtml(value, size) {
    const id = parseToken(value);
    if (id) return html(id, size || 22);
    return value;
  }

  function renderTextWithUnicode(text) {
    if (!text) return '';
    const emojiKeys = Object.keys(UNICODE_TO_ID);
    const iconKeys = Object.keys(ICON_UNICODE);
    const allKeys = emojiKeys.concat(iconKeys);
    if (!allKeys.length) {
      const d = document.createElement('div');
      d.textContent = text;
      return d.innerHTML;
    }
    const pattern = new RegExp('(' + allKeys.map((u) => u.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|') + ')', 'g');
    const bits = text.split(pattern);
    let out = '';
    const d = document.createElement('div');
    for (let i = 0; i < bits.length; i++) {
      const bit = bits[i];
      if (!bit) continue;
      const stickerId = UNICODE_TO_ID[bit];
      const iconId = ICON_UNICODE[bit];
      if (stickerId) out += html(stickerId, 26);
      else if (iconId) out += iconHtml(iconId, 16);
      else {
        d.textContent = bit;
        out += d.innerHTML;
      }
    }
    return out;
  }

  function render(text) {
    if (text == null || text === '') return '';
    const raw = String(text);
    const parts = [];
    const re = /:([a-z]+):/g;
    let last = 0;
    let m;
    while ((m = re.exec(raw)) !== null) {
      if (!EMOJIS[m[1]]) continue;
      if (m.index > last) parts.push({ type: 'text', value: raw.slice(last, m.index) });
      parts.push({ type: 'emoji', id: m[1] });
      last = m.index + m[0].length;
    }
    if (last < raw.length) parts.push({ type: 'text', value: raw.slice(last) });

    if (!parts.length) {
      return renderTextWithUnicode(raw);
    }

    let out = '';
    for (const p of parts) {
      if (p.type === 'text') {
        out += renderTextWithUnicode(p.value);
      } else {
        out += html(p.id, 26);
      }
    }
    return out;
  }

  function insert(input, id) {
    if (!input || !EMOJIS[id]) return;
    const insertText = token(id);
    const start = input.selectionStart ?? input.value.length;
    const end = input.selectionEnd ?? start;
    const before = input.value.slice(0, start);
    const after = input.value.slice(end);
    input.value = before + insertText + after;
    const pos = start + insertText.length;
    input.setSelectionRange(pos, pos);
    input.focus();
    input.dispatchEvent(new Event('input', { bubbles: true }));
  }

  function closePicker() {
    document.querySelectorAll('.mf-emoji-picker').forEach((p) => p.remove());
  }

  function openPicker(anchorEl, input, opts) {
    opts = opts || {};
    closePicker();
    document.querySelectorAll('.prj-emoji-picker').forEach((p) => p.remove());

    const picker = document.createElement('div');
    picker.className = 'mf-emoji-picker';
    picker.setAttribute('role', 'dialog');
    picker.setAttribute('aria-label', 'Смайлики Mendflow');

    const head = document.createElement('div');
    head.className = 'mf-emoji-picker-head';
    head.innerHTML = '<span class="mf-emoji-picker-brand">mendflow</span><span class="mf-emoji-picker-sub">смайлики</span>';
    picker.appendChild(head);

    const grid = document.createElement('div');
    grid.className = 'mf-emoji-grid';

    Object.entries(EMOJIS).forEach(([id, e]) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'mf-emoji-option';
      btn.title = e.label;
      btn.setAttribute('aria-label', e.label);
      btn.innerHTML = charHtml(id, 40);
      btn.addEventListener('click', (ev) => {
        ev.stopPropagation();
        closePicker();
        if (typeof opts.onSelect === 'function') {
          opts.onSelect(id, token(id));
        } else if (input) {
          insert(input, id);
        }
      });
      grid.appendChild(btn);
    });
    picker.appendChild(grid);

    document.body.appendChild(picker);

    const rect = anchorEl.getBoundingClientRect();
    const pw = picker.offsetWidth || 300;
    const ph = picker.offsetHeight || 240;
    let top = rect.bottom + 8;
    let left = rect.left;
    if (left + pw > window.innerWidth - 12) left = window.innerWidth - pw - 12;
    if (left < 12) left = 12;
    if (top + ph > window.innerHeight - 12) top = rect.top - ph - 8;
    picker.style.top = top + 'px';
    picker.style.left = left + 'px';

    setTimeout(() => {
      const close = (ev) => {
        if (!picker.contains(ev.target) && ev.target !== anchorEl) {
          closePicker();
          document.removeEventListener('click', close);
        }
      };
      document.addEventListener('click', close);
    }, 10);
  }

  function attachTrigger(btn, input, opts) {
    if (!btn) return;
    btn.addEventListener('click', (ev) => {
      ev.preventDefault();
      ev.stopPropagation();
      const target = input || (btn.dataset.mfEmojiFor ? document.getElementById(btn.dataset.mfEmojiFor) : null);
      openPicker(btn, target, opts);
    });
  }

  function attachAll() {
    document.querySelectorAll('[data-mf-emoji-trigger]').forEach((btn) => {
      if (btn.dataset.mfEmojiBound) return;
      btn.dataset.mfEmojiBound = '1';
      if (!btn.querySelector('.mf-emoji')) {
        btn.innerHTML = html('joy', 24);
      }
      const input = btn.dataset.mfEmojiFor ? document.getElementById(btn.dataset.mfEmojiFor) : null;
      attachTrigger(btn, input);
    });
  }

  window.MF_EMOJI = {
    EMOJIS,
    ICONS,
    BADGE_ICONS,
    REACTION_IDS,
    token,
    html,
    charHtml,
    icon: iconHtml,
    badgeIcon: badgeIconHtml,
    attachIcons,
    reactionHtml,
    preview,
    render,
    insert,
    openPicker,
    attachTrigger,
    attachAll,
    closePicker,
    initAll,
  };

  function initAll(root) {
    attachAll();
    attachIcons(root);
    const themeIcon = document.querySelector('.theme-toggle-icon');
    if (themeIcon && !themeIcon.querySelector('img')) {
      const t = document.documentElement.dataset.theme || 'light';
      themeIcon.innerHTML = iconHtml(t === 'dark' ? 'sun' : 'moon', 38);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }
})();
