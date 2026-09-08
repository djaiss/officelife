{{-- The mark of the application: a city with trees, whose trees sway while the pointer is on it. --}}
<svg xmlns="http://www.w3.org/2000/svg"
     id="logo" viewBox="0 0 990 550"
     role="img" aria-labelledby="logo-title logo-description"
     {{ $attributes->class(['shrink-0']) }}>
  <title id="logo-title">City buildings and trees</title>
  <desc id="logo-description">A navy city skyline with green trees and shrubs. Hovering it makes the trees sway in the wind.</desc>

  <defs>
    <linearGradient id="navy" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#0c4a78"/>
      <stop offset="0.52" stop-color="#063b68"/>
      <stop offset="1" stop-color="#012b53"/>
    </linearGradient>
    <linearGradient id="green" x1="0" y1="1" x2="1" y2="0">
      <stop offset="0" stop-color="#08ad6b"/>
      <stop offset="0.55" stop-color="#12bb77"/>
      <stop offset="1" stop-color="#21c98a"/>
    </linearGradient>
    <linearGradient id="glass" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0" stop-color="#d5e8fa"/>
      <stop offset="1" stop-color="#a9c8e9"/>
    </linearGradient>
  </defs>

  <style>
    .tree-sway {
      will-change: transform;
      transform-box: fill-box;
    }
    #tree-left .tree-sway,
    #tree-right .tree-sway { transform-origin: 50% 100%; }
    .tree-hit { fill: transparent; pointer-events: all; }
    @media (prefers-reduced-motion: no-preference) {
      #logo:hover #tree-left .tree-sway {
        animation: wind-left 1.25s ease-in-out infinite;
      }

      #logo:hover #tree-right .tree-sway {
        animation: wind-right 1.12s ease-in-out infinite;
      }
      @keyframes wind-left {
        0%, 100% { transform: rotate(0deg); }
        25% { transform: rotate(1.7deg); }
        55% { transform: rotate(-1.2deg); }
        78% { transform: rotate(0.65deg); }
      }
      @keyframes wind-right {
        0%, 100% { transform: rotate(0deg); }
        24% { transform: rotate(-1.55deg); }
        52% { transform: rotate(1.1deg); }
        77% { transform: rotate(-0.55deg); }
      }
    }
  </style>

  <svg id="logo-artwork" x="-126" y="-355" width="1254" height="1254"
       viewBox="0 0 1254 1254" overflow="visible">
    <g id="buildings">
      <!-- Left building: dark outer shell and bright front face. -->
      <path id="building-left-shell" fill="url(#navy)"
            d="M257 884V598c0-13 8-24 21-28l104-34c13-4 26-2 37 5l42 28v315H257Z"/>
      <path id="building-left-face" fill="#fff"
            d="M279 884V604c0-9 6-16 15-19l98-22c8-2 15 4 15 13v308H279Z"/>
      <path id="window-left-upper" fill="url(#navy)"
            d="M311 626l65-16c4-1 7 2 7 6v41c0 4-2 6-6 7l-65 17c-4 1-7-2-7-6v-42c0-4 2-6 6-7Z"/>
      <path id="window-left-lower" fill="url(#navy)"
            d="M311 698l65-16c4-1 7 2 7 6v29c0 4-2 6-6 7l-65 17c-4 1-7-2-7-6v-30c0-4 2-6 6-7Z"/>

      <!-- Tall center tower. -->
      <path id="building-center-shell" fill="url(#navy)"
            d="M459 884V448c0-18 11-33 28-39l164-51c15-5 29-2 42 7l82 59c10 7 15 17 15 30v430H459Z"/>
      <path id="building-center-face" fill="#fff"
            d="M488 884V454c0-9 6-16 15-19l155-46c11-3 20 5 20 16v479H488Z"/>
      <path id="window-center-upper" fill="url(#navy)"
            d="M523 477l117-34c4-1 7 2 7 6v36c0 4-2 7-6 8l-117 33c-4 1-7-2-7-6v-35c0-4 2-7 6-8Z"/>
      <path id="window-center-middle" fill="url(#navy)"
            d="M523 562l117-30c4-1 7 2 7 6v35c0 4-2 7-6 8l-117 29c-4 1-7-2-7-6v-34c0-4 2-7 6-8Z"/>
      <path id="window-center-lower" fill="url(#navy)"
            d="M523 647l117-28c4-1 7 2 7 6v35c0 4-2 7-6 8l-117 27c-4 1-7-2-7-6v-34c0-4 2-7 6-8Z"/>
      <path id="door-frame" fill="url(#navy)"
            d="M519 884V755c0-7 5-12 12-13l107-8c5 0 8 3 8 8v142H519Z"/>
      <path id="door-left" fill="url(#glass)"
            d="M538 884V772c0-2 2-4 4-4l34-3v119h-38Z"/>
      <path id="door-right" fill="url(#glass)"
            d="M589 884V764l33-3c2 0 4 2 4 4v119h-37Z"/>

      <!-- Right building, layered in front of the center tower's side wall. -->
      <path id="building-right-shell" fill="url(#navy)"
            d="M711 884V652c0-16 10-29 26-33l164-37c13-3 25-1 36 6l45 29c8 5 12 13 12 23v244H711Z"/>
      <path id="building-right-face" fill="#fff"
            d="M739 884V660c0-9 6-16 15-18l152-33c8-2 16 4 16 13v262H739Z"/>
      <path id="window-right-left" fill="url(#navy)"
            d="M772 670l17-3c4-1 7 2 7 6v109c0 4-2 6-6 7h-18c-4 0-6-2-6-6V677c0-4 2-6 6-7Z"/>
      <path id="window-right-middle" fill="url(#navy)"
            d="M819 662l17-3c4-1 7 2 7 6v115c0 4-2 7-6 7h-18c-4 0-6-2-6-6V669c0-4 2-6 6-7Z"/>
      <path id="window-right-right" fill="url(#navy)"
            d="M867 654l17-3c4-1 7 2 7 6v122c0 4-2 7-6 7h-18c-4 0-6-2-6-6V661c0-4 2-6 6-7Z"/>
    </g>

    <!-- Shrubs remain still while the trees move. -->
    <g id="shrubs" fill="url(#green)">
      <path id="shrubs-left" stroke="#fff" stroke-width="9" stroke-linejoin="round"
            d="M239 884c2-17 17-29 38-29h5c5-29 28-50 58-50 28 0 49 16 55 42 7-5 16-8 26-8 25 0 43 18 47 45H239Z"/>
      <path id="shrubs-right"
            d="M762 884c3-23 21-39 45-39 10 0 19 3 26 9 4-24 23-41 50-41 28 0 49 16 55 40 18 1 32 13 37 31H762Z"/>
    </g>

    <!-- Each tree has stable IDs and a rooted transform origin for interaction. -->
    <g id="tree-left" class="tree">
      <g class="tree-sway">
        <circle id="tree-left-canopy" cx="219" cy="755" r="88" fill="url(#green)" stroke="#fff" stroke-width="10"/>
        <g id="tree-left-trunk" fill="none" stroke="#063b68" stroke-width="17" stroke-linecap="round" stroke-linejoin="round">
          <path d="M217 894V755"/>
          <path d="M217 807l-29-28"/>
          <path d="M217 809l34-36"/>
        </g>
      </g>
      <circle class="tree-hit" cx="219" cy="755" r="100"/>
    </g>

    <g id="tree-right" class="tree">
      <g class="tree-sway">
        <circle id="tree-right-canopy" cx="1027" cy="774" r="84" fill="url(#green)" stroke="#fff" stroke-width="10"/>
        <g id="tree-right-trunk" fill="none" stroke="#063b68" stroke-width="17" stroke-linecap="round" stroke-linejoin="round">
          <path d="M1027 894V779"/>
          <path d="M1027 831l-29-28"/>
          <path d="M1027 832l31-30"/>
        </g>
      </g>
      <circle class="tree-hit" cx="1027" cy="774" r="96"/>
    </g>

    <path id="ground-line" d="M181 894h879" fill="none" stroke="#063b68" stroke-width="21" stroke-linecap="round"/>
  </svg>
</svg>
