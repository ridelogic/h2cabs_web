<?php

namespace App\Content;

/**
 * Pondicherry-area destinations. Descriptions avoid opening hours, ticket
 * prices and travel times, which change and are not verified here.
 *
 * `photo` is a key in config/photos.php. `slug` is used to preselect the
 * destination on the booking form.
 */
class DestinationCatalog
{
    /** @var array<string, string> */
    public const CATEGORIES = [
        'beach' => 'Beaches & backwaters',
        'heritage' => 'Heritage',
        'spiritual' => 'Spiritual',
    ];

    /** @return list<array{slug: string, name: string, area: string, category: string, intro: string, tip: string, photo: string}> */
    public function all(): array
    {
        return [
            [
                'slug' => 'promenade-rock-beach',
                'name' => 'Promenade & Rock Beach',
                'area' => 'Goubert Avenue',
                'category' => 'beach',
                'intro' => 'Pondicherry\'s seafront walk runs along the Bay of Bengal, lined with heritage buildings, statues and the old lighthouse. Mornings and evenings are the favourite times for a stroll.',
                'tip' => 'Ask your driver to drop you at one end and pick you up at the other.',
                'photo' => 'rock-beach',
            ],
            [
                'slug' => 'white-town',
                'name' => 'French Quarter (White Town)',
                'area' => 'Between the canal and the sea',
                'category' => 'heritage',
                'intro' => 'Quiet, tree-shaded streets of French-colonial villas in mustard and white, with bougainvillea, cafés, galleries and boutique stays.',
                'tip' => 'The lanes are best explored on foot — we can wait nearby while you walk.',
                'photo' => 'white-town',
            ],
            [
                'slug' => 'auroville',
                'name' => 'Auroville',
                'area' => 'North of Puducherry',
                'category' => 'spiritual',
                'intro' => 'An international township founded in 1968, known for its golden Matrimandir, green forest and community-run cafés and shops.',
                'tip' => 'Visits to the Matrimandir viewing point usually start at the visitors\' centre; check current arrangements before you go.',
                'photo' => 'auroville',
            ],
            [
                'slug' => 'paradise-beach',
                'name' => 'Paradise Beach',
                'area' => 'Near Chunnambar',
                'category' => 'beach',
                'intro' => 'A sandy, palm-fringed beach reached by boat through the backwaters — a popular half-day outing from town.',
                'tip' => 'Combine it with the Chunnambar Boat House, where the boats leave from.',
                'photo' => 'paradise-beach',
            ],
            [
                'slug' => 'chunnambar-boat-house',
                'name' => 'Chunnambar Boat House',
                'area' => 'Chunnambar backwaters',
                'category' => 'beach',
                'intro' => 'The starting point for boat rides along the Chunnambar backwaters, with a café and riverside seating.',
                'tip' => 'Boat services can change with the season and weather; we can help you check before setting off.',
                'photo' => 'chunnambar',
            ],
            [
                'slug' => 'sri-aurobindo-ashram',
                'name' => 'Sri Aurobindo Ashram',
                'area' => 'White Town',
                'category' => 'spiritual',
                'intro' => 'A spiritual community founded in 1926, with a peaceful main building in the heart of the old town.',
                'tip' => 'Please dress modestly and keep silence inside the ashram.',
                'photo' => 'aurobindo-ashram',
            ],
            [
                'slug' => 'sacred-heart-basilica',
                'name' => 'Basilica of the Sacred Heart',
                'area' => 'Subbiah Salai',
                'category' => 'heritage',
                'intro' => 'A Gothic-revival church with twin spires and stained-glass windows, one of Pondicherry\'s best-known landmarks.',
                'tip' => 'Easy to combine with a White Town drive.',
                'photo' => 'basilica',
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public function find(string $slug): ?array
    {
        return collect($this->all())->firstWhere('slug', $slug);
    }

    /** @return list<string> */
    public function slugs(): array
    {
        return array_column($this->all(), 'slug');
    }

    /**
     * Suggested combinations for a day out. Durations are deliberately not
     * stated; they depend on traffic and how long guests stay.
     *
     * @return list<array{title: string, text: string, stops: list<string>}>
     */
    public function itineraries(): array
    {
        return [
            [
                'title' => 'Heritage town drive',
                'text' => 'Colonial streets, the seafront and the town\'s best-known landmarks.',
                'stops' => ['white-town', 'sacred-heart-basilica', 'sri-aurobindo-ashram', 'promenade-rock-beach'],
            ],
            [
                'title' => 'Beaches & backwaters',
                'text' => 'A boat ride through the backwaters to the sand, then the seafront at sunset.',
                'stops' => ['chunnambar-boat-house', 'paradise-beach', 'promenade-rock-beach'],
            ],
            [
                'title' => 'Auroville outing',
                'text' => 'Auroville\'s visitors\' centre and Matrimandir, then back to town through the French Quarter.',
                'stops' => ['auroville', 'white-town'],
            ],
        ];
    }
}
