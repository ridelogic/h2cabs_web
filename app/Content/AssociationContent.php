<?php

namespace App\Content;

/**
 * About-page copy for the Helping Hand Drivers Association. Wording is
 * intentionally general: replace with the association's own approved
 * mission/vision statements (and founding details) when supplied.
 */
class AssociationContent
{
    public function mission(): string
    {
        return 'To give passengers in Puducherry a safe, courteous and fairly priced taxi service, run by local drivers who support one another.';
    }

    public function vision(): string
    {
        return 'A trusted, community-owned cab service that visitors and residents choose first — and that gives local drivers dignified, steady work.';
    }

    /** @return list<array{icon: string, title: string, text: string}> */
    public function purposes(): array
    {
        return [
            [
                'icon' => 'heart',
                'title' => 'A helping hand for drivers',
                'text' => 'The association brings independent drivers together so that no one has to face the road — or hard times — alone.',
            ],
            [
                'icon' => 'shield',
                'title' => 'Shared standards for passengers',
                'text' => 'Members agree on how passengers should be treated: punctual, polite and honest about fares.',
            ],
            [
                'icon' => 'users',
                'title' => 'One name passengers can trust',
                'text' => 'H2 CABS gives the members a single, recognisable service, so passengers know who they are riding with.',
            ],
        ];
    }
}
