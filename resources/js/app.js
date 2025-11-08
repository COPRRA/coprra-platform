import './bootstrap';
import './compare';
import './wishlist';

import { registerSW } from 'virtual:pwa-register';

registerSW({ immediate: true });
