/**
 * 
 * Note. For now only UNA core, Artificer and Messenger modules were included in minifying process.
 * 
 */

module.exports = {
  content: [
    './inc/js/*.js',
    './inc/js/classes/*.js',

    './modules/base/**/js/*.js',
    './modules/base/**/template/*.html',

    // Artificer
    './modules/boonex/artificer/js/*.js',
    './modules/boonex/artificer/data/template/**/*.html',
    './modules/boonex/artificer/template/*.html',

    // Messenger
    './modules/boonex/messenger/js/*.js',
    './modules/boonex/messenger/template/*.html',
      
    // Forum
    './modules/boonex/forum/js/*.js',
    './modules/boonex/forum/template/*.html',  

    './template/*.html',
  ],
  safelist: [
    'w-8', 'w-10', 'w-16', 'w-24', 'w-48', 'w-4/6', 
    'h-8', 'h-10', 'h-24', 'h-48',
    '-m-2',
    'text-red-400', 
    'ring-blue-500', 'ring-opacity-20', 
    'focus:ring-blue-500', 'focus:ring-opacity-20',
    'border-blue-500', 'border-opacity-70', 
    'focus:border-blue-500', 'focus:border-opacity-70',

    'bx-def-box-sizing', 'bx-def-align-center',  'bx-def-valign-center', 'bx-def-centered', 
    'bx-def-margin', 'bx-def-margin-left', 'bx-def-margin-left-auto', 'bx-def-margin-right', 'bx-def-margin-top', 'bx-def-margin-top-auto', 'bx-def-margin-bottom', 'bx-def-margin-leftright', 'bx-def-margin-leftright-neg', 'bx-def-margin-topbottom', 'bx-def-margin-topbottom-neg', 'bx-def-margin-lefttopright', 'bx-def-margin-rightbottomleft', 
    'bx-def-margin-sec', 'bx-def-margin-sec-left', 'bx-def-margin-sec-left-auto', 'bx-def-margin-sec-right', 'bx-def-margin-sec-top', 'bx-def-margin-sec-top-auto', 'bx-def-margin-sec-bottom', 'bx-def-margin-sec-leftright', 'bx-def-margin-sec-leftright-neg', 'bx-def-margin-sec-topbottom', 'bx-def-margin-sec-topbottom-neg', 'bx-def-margin-sec-lefttopright', 'bx-def-margin-sec-rightbottomleft', 
    'bx-def-margin-thd', 'bx-def-margin-thd-left', 'bx-def-margin-thd-left-auto', 'bx-def-margin-thd-right', 'bx-def-margin-thd-top', 'bx-def-margin-thd-top-auto', 'bx-def-margin-thd-bottom', 'bx-def-margin-thd-leftright', 'bx-def-margin-thd-leftright-neg', 'bx-def-margin-thd-topbottom', 'bx-def-margin-thd-topbottom-neg', 'bx-def-margin-thd-lefttopright', 'bx-def-margin-thd-rightbottomleft', 
    'bx-def-padding', 'bx-def-padding-left', 'bx-def-padding-left-auto', 'bx-def-padding-right', 'bx-def-padding-top', 'bx-def-padding-top-auto', 'bx-def-padding-bottom', 'bx-def-padding-leftright', 'bx-def-padding-topbottom', 'bx-def-padding-lefttopright', 'bx-def-padding-rightbottomleft', 
    'bx-def-padding-sec', 'bx-def-padding-sec-left', 'bx-def-padding-sec-left-auto', 'bx-def-padding-sec-right', 'bx-def-padding-sec-top', 'bx-def-padding-sec-top-auto', 'bx-def-padding-sec-bottom', 'bx-def-padding-sec-leftright', 'bx-def-padding-sec-topbottom', 'bx-def-padding-sec-lefttopright', 'bx-def-padding-sec-rightbottomleft', 
    'bx-def-padding-thd', 'bx-def-padding-thd-left', 'bx-def-padding-thd-left-auto', 'bx-def-padding-thd-right', 'bx-def-padding-thd-top', 'bx-def-padding-thd-top-auto', 'bx-def-padding-thd-bottom', 'bx-def-padding-thd-leftright', 'bx-def-padding-thd-topbottom', 'bx-def-padding-thd-lefttopright', 'bx-def-padding-thd-rightbottomleft', 
    'bx-def-font-small', 'bx-def-font-middle', 'bx-def-font-large', 'bx-def-font-h3', 'bx-def-font-h2', 'bx-def-font-h1', 
    'bx-def-a-colored',
    'bx-def-unit-alert', 'bx-def-unit-alert-small', 'bx-def-unit-alert-middle',
    'bx-def-label',
    'bx-def-icon-size', 'bx-def-thumb-size', 'bx-def-ava-size', 'bx-def-ava-big-size',
    'bx-def-color-bg-box-active', 

    'bx-form-required', 'bx-form-warn', 'bx-switcher-cont', 
    '.bx-form-input-wrapper-checkbox_set', 'bx-form-input-wrapper-radio_set',
    'bx-form-input-slider', 'bx-form-input-doublerange', 'bx-form-input-select_multiple', 'bx-form-input-select', 'bx-form-input-radio_set', 'bx-form-input-checkbox_set', 'bx-form-input-number', 'bx-form-input-time', 'bx-form-input-datepicker', 'bx-form-input-datetime', 'bx-form-input-textarea', 'bx-form-input-text', 'bx-form-input-price', 'bx-form-input-checkbox', 'bx-form-input-radio', 

    'bx-popup-full-screen', 'bx-popup-fog',
    'bx-informer-msg-info', 'bx-informer-msg-alert', 'bx-informer-msg-error',

    'bx-base-general-unit-meta-username',

    'bx-tl-overflow',

    'sys-auth-block', 'sys-auth-compact-container',

    'flickity-button',
  ],
  darkMode: 'class', // false or 'media' or 'class'
  theme: {
    fontFamily: {
        'inter': ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'Roboto', '"Helvetica Neue"', 'Arial', '"Noto Sans"', 'sans-serif', '"Apple Color Emoji"', '"Segoe UI Emoji"', '"Segoe UI Symbol"', '"Noto Color Emoji"'],
    },
    extend: {
        width: {
            46: '11.5rem',
            50: '12.5rem',
            112: '28rem',
            128: '32rem',
            144: '36rem'
        },
        minWidth: {
            4: '1rem',
            6: '1.5rem',
            88: '22rem'
        },
        maxWidth: {
            32: '8rem',
        },
        height: {
            46: '11.5rem',
            50: '12.5rem',
            112: '28rem',
            128: '32rem',
            144: '36rem'
        },
        lineHeight: {
            11: '2.75rem',
            12: '3rem',
            13: '3.25rem',
            14: '3.5rem',
            15: '3.75rem',
            16: '4rem',
        },
        zIndex: {
            1: 1,
            2: 2,
            3: 3,
            4: 4,
            5: 5,
        },
        flex: {
            2: '2 2 0%',
        },
        animation: {
          goo: "goo 8s infinite",
        },
        keyframes: {
          goo: {
            "0%": {
              transform: "translate(0px, 0px) scale(1)",
            },
            "33%": {
              transform: "translate(30px, -50px) scale(1.2)",
            },
            "66%": {
              transform: "translate(-20px, 20px) scale(0.8)",
            },
            "100%": {
              transform: "translate(0px, 0px) scale(1)",
            },
          },
        },
    },
  },
  plugins: [],
}