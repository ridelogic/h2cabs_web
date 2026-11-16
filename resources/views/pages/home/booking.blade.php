{{--
    Homepage booking: light blue-white section with a navy "Book a Taxi"
    heading and a wide, horizontal booking card. Same booking route, fields
    and validation as /booking (which keeps the full form). Colours are scoped
    to this section in app.css (.booking-section / .booking-compact).
--}}
<section id="book" aria-labelledby="book-title" class="booking-section relative z-10 scroll-mt-24 bg-[#f1f6fc] px-4 py-12 sm:px-6 sm:py-16">
    <h2 id="book-title" class="text-center text-3xl font-extrabold tracking-tight text-[#12336b] sm:text-4xl">Book a Taxi</h2>
    <div class="mx-auto mt-8 w-full max-w-[1100px] rounded-2xl border border-[#d9e2f0] bg-white p-4 shadow-[0_18px_48px_-24px_rgb(18_51_107/0.28)] sm:p-6 lg:p-8">
        <x-booking.compact-form :booking="$booking" :distance-enabled="app(\App\Places\RouteDistance::class)->available()" />
    </div>
</section>
