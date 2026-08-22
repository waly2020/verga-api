<x-mail::message>
# Publicité expirée

Bonjour {{ $ownerName }},

La période de diffusion de **{{ $titre }}** est terminée (fin le {{ $dateFin }}).

Votre publicité n'est plus affichée sur le site. Vous pouvez en soumettre une nouvelle si besoin.

<x-mail::button :url="$publiciteUrl">
Voir mes publicités
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
