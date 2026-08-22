<x-mail::message>
# Suivi colis — {{ $statutLabel }}

Bonjour {{ $clientName }},

{{ $messageStatut }}

- **Colis** : {{ $colisReference }}
@if ($commandeCode)
- **Commande** : {{ $commandeCode }}
@endif
@if ($offreTitre)
- **Offre** : {{ $offreTitre }}
@endif
@if ($agenceName)
- **Agence** : {{ $agenceName }}
@endif

<x-mail::button :url="$colisUrl">
Suivre mon colis
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
