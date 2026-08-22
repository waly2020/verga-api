<x-mail::message>
# Bonjour {{ $userName }},

Vous avez été ajouté à l'équipe de **{{ $agenceName }}** avec le rôle **{{ $roleName }}**.

Connectez-vous avec l'adresse e-mail et le mot de passe définis par votre administrateur d'agence.

<x-mail::button :url="$appUrl">
Se connecter
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
