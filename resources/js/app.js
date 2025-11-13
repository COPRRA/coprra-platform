import './bootstrap';
import './compare';
import './wishlist';
import './live-search';

import { registerSW } from 'virtual:pwa-register';

registerSW({ immediate: true });
