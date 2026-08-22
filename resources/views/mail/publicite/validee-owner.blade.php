<x-mail::message>
# Publicité validée

Bonjour {{ $ownerName }},

Votre publicité **{{ $titre }}** a été validée par un administrateur.

Vous pouvez maintenant procéder au **paiement** pour la mettre en ligne.

- **Période** : {{ $dateDebut }} → {{ $dateFin }} ({{ $nombreJours }} jour(s))

<x-mail::button :url="$publiciteUrl">
Payer ma publicité
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
