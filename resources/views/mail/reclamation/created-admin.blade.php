<x-mail::message>
# Nouvelle réclamation

- **Client** : {{ $clientName }}
@if ($agenceName)
- **Agence** : {{ $agenceName }}
@endif
@if ($commandeCode)
- **Commande** : {{ $commandeCode }}
@endif
- **Objet** : {{ $objet }}

{{ $description }}

<x-mail::button :url="$reclamationUrl">
Traiter dans l'admin
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
