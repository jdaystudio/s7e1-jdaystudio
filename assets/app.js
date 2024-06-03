/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 *
 */

import './styles/app.css';
import './styles/app-button.css'
import './styles/sliding-checkbox.css'
import './styles/topbar.css';

// create global $ and jQuery variables for our simple js code
import './lib/jquery.js';
window.$ = window.jQuery = $;