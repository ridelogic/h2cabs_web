<x-layouts.app :seo="$seo">
    <x-ui.page-hero photo="white-town" crumb="About Us" eyebrow="About H2 CABS" title="The Helping Hand Drivers Association">
        Local drivers who chose to stand together — and to give Puducherry a cab service people can trust.
    </x-ui.page-hero>

    {{-- Introduction --}}
    <section aria-labelledby="intro-title" class="section">
        <div class="container-site grid items-center gap-12 lg:grid-cols-2 lg:gap-20">
            <div>
                <p class="eyebrow">Who we are</p>
                <h2 id="intro-title" class="heading-display mt-3 text-3xl text-brand-blue-950 sm:text-4xl">An association of local drivers</h2>
                <div class="mt-5 space-y-4 text-lg text-muted">
                    <p>
                        The Helping Hand Drivers Association brings together professional drivers who live and work in
                        Puducherry. Its members share a simple idea: drivers are stronger when they help one another, and
                        passengers are better served when drivers work to common standards.
                    </p>
                    <p>
                        H2 CABS — Helping Hand Drivers Association Cabs — is the taxi service the members run together, so
                        residents and visitors can book a local driver under one trusted name.
                    </p>
                </div>
            </div>
            <div class="aspect-[3/2] overflow-hidden rounded-(--radius-card) shadow-lift">
                <x-ui.photo key="colonial-windows" sizes="(min-width: 1024px) 50vw, 100vw" />
            </div>
        </div>
    </section>

    {{-- Mission and vision --}}
    <section aria-labelledby="mission-title" class="section bg-sand">
        <div class="container-site">
            <x-ui.section-heading id="mission-title" eyebrow="Our purpose" title="Mission and vision" />
            <div class="mt-12 grid gap-6 md:grid-cols-2">
                <article class="relative overflow-hidden rounded-(--radius-card) bg-brand-blue-800 p-8 text-white shadow-lift sm:p-10">
                    <x-ui.icon name="target" class="absolute -top-6 -right-6 size-40 text-white/10" />
                    <span class="flex size-12 items-center justify-center rounded-xl bg-white/15"><x-ui.icon name="target" /></span>
                    <h3 class="heading-display mt-5 text-2xl">Our mission</h3>
                    <p class="mt-3 text-lg text-white/85">{{ $content->mission() }}</p>
                </article>
                <article class="relative overflow-hidden rounded-(--radius-card) bg-brand-green-700 p-8 text-white shadow-lift sm:p-10">
                    <x-ui.icon name="eye" class="absolute -top-6 -right-6 size-40 text-white/10" />
                    <span class="flex size-12 items-center justify-center rounded-xl bg-white/15"><x-ui.icon name="eye" /></span>
                    <h3 class="heading-display mt-5 text-2xl">Our vision</h3>
                    <p class="mt-3 text-lg text-white/85">{{ $content->vision() }}</p>
                </article>
            </div>
        </div>
    </section>

    {{-- Why the association exists --}}
    <section aria-labelledby="why-title" class="section">
        <div class="container-site">
            <x-ui.section-heading id="why-title" eyebrow="Why we exist" title="Why the association was formed" />
            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ($content->purposes() as $purpose)
                    <article class="card p-7">
                        <span class="flex size-12 items-center justify-center rounded-xl bg-brand-green-100 text-brand-green-700">
                            <x-ui.icon :name="$purpose['icon']" />
                        </span>
                        <h3 class="mt-5 text-xl font-bold text-brand-blue-950">{{ $purpose['title'] }}</h3>
                        <p class="mt-2 text-muted">{{ $purpose['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Members --}}
    <section id="members" aria-labelledby="members-title" class="section bg-surface">
        <div class="container-site">
            <x-ui.section-heading id="members-title" eyebrow="Our members" title="Office bearers and members">
                {{ config('site.association.name') }}
            </x-ui.section-heading>

            @if ($memberGroups->isNotEmpty())
                @foreach ($memberGroups as $group => $members)
                    @php
                        // The first office bearer (the President, by display order) is highlighted.
                        $lead = $group === 'office_bearer' ? $members->first() : null;
                        $rest = $lead ? $members->slice(1) : $members;
                    @endphp
                    <section class="mt-14 first-of-type:mt-12" aria-labelledby="group-{{ $group }}">
                        <h3 id="group-{{ $group }}" class="flex items-center gap-4 text-sm font-bold tracking-wider text-brand-blue-800 uppercase">
                            <span class="h-px flex-1 bg-line"></span>{{ \App\Models\Member::GROUPS[$group] ?? 'Members' }}<span class="h-px flex-1 bg-line"></span>
                        </h3>
                        @if ($lead)
                            <div class="mx-auto mt-8 max-w-xs">
                                <x-about.member-card :member="$lead" featured />
                            </div>
                        @endif
                        @if ($rest->isNotEmpty())
                            <div class="mt-6 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
                                @foreach ($rest as $member)
                                    <x-about.member-card :member="$member" />
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endforeach
            @else
                <div class="mx-auto mt-12 max-w-3xl">
                    <div aria-hidden="true" class="grid grid-cols-2 gap-4 opacity-60 sm:grid-cols-4">
                        @foreach (range(1, 4) as $placeholder)
                            <div class="card flex flex-col items-center p-6">
                                <span class="flex size-20 items-center justify-center rounded-full bg-brand-blue-50 text-brand-blue-200"><x-ui.icon name="user" class="size-10" /></span>
                                <span class="mt-4 h-3 w-24 rounded-full bg-line"></span>
                                <span class="mt-2 h-2.5 w-16 rounded-full bg-line"></span>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-8 rounded-2xl bg-white p-6 text-center text-muted ring-1 ring-line">
                        <strong class="block text-lg text-brand-blue-950">Member profiles coming soon</strong>
                        The association is preparing its member list. Profiles will appear here once members have approved their details.
                    </p>
                </div>
            @endif
        </div>
    </section>

    <x-ui.cta-band title="Ride with a local driver" text="Book a trip with a member of the Helping Hand Drivers Association." />
</x-layouts.app>
