<x-mail::message>
# Publicité à modérer

Une nouvelle publicité attend votre validation.

- **Titre** : {{ $titre }}
@if ($agenceName)
- **Agence** : {{ $agenceName }}
@else
- **Demandeur** : {{ $ownerName }}
@endif
@if ($offreTitre)
- **Offre** : {{ $offreTitre }}
@endif
- **Période** : {{ $dateDebut }} → {{ $dateFin }} ({{ $nombreJours }} jour(s))

@if ($description)
{{ $description }}
@endif

<x-mail::button :url="$moderationUrl">
Modérer dans l'admin
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
