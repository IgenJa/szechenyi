import { initMobileMenu } from './components/mobile-menu.js';
import { initLightbox } from './components/lightbox.js';
import { initGalleryFilter } from './components/gallery-filter.js';
import { initForms } from './components/forms.js';
import { initActiveNav } from './components/active-nav.js';

const ready = (fn) => {
  if (document.readyState !== 'loading') fn();
  else document.addEventListener('DOMContentLoaded', fn);
};

ready(() => {
  initMobileMenu();
  initLightbox();
  initGalleryFilter();
  initForms();
  initActiveNav();
});
