// Isolated Summernote bundle using jQuery 3 — does NOT conflict with app's jQuery 4
import jQuery3 from 'jquery';
import 'summernote/dist/summernote-lite';

// Expose jQuery 3 instance for use by initSummernote() in the blade view
window.$sn = jQuery3;
window.summernoteReady = true;
