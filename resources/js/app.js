import './bootstrap';
import './site';
import { initPlaceAutocomplete } from './place-autocomplete';
import { initBooking } from './booking';
import { restoreDraft } from './form-draft';
import { initAdminShell } from './admin';
import { initHeroSlider } from './hero-slider';

initAdminShell();
initHeroSlider();

// Details kept by a "Try again" reload come back before anything else runs.
document.querySelectorAll('[data-booking-form]').forEach(restoreDraft);

initPlaceAutocomplete();
initBooking();
