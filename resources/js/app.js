import './bootstrap';
import './animations/image-effects';
import './compare';
import './wishlist';

import { registerSW } from 'virtual:pwa-register';
registerSW({ immediate: true });
