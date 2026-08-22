<x-mail::message>
# Réclamation {{ $statutLabel }}

Bonjour {{ $clientName }},

{{ $messageStatut }}

- **Objet** : {{ $objet }}
@if ($agenceName)
- **Agence** : {{ $agenceName }}
@endif
@if ($commandeCode)
- **Commande** : {{ $commandeCode }}
@endif
- **Statut** : {{ $statutLabel }}

<x-mail::button :url="$reclamationUrl">
Consulter ma réclamation
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
