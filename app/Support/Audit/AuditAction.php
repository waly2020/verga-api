<?php

namespace App\Support\Audit;

final class AuditAction
{
    public const AuthAdminLoginSuccess = 'auth.admin.login.success';

    public const AuthAdminLoginFailed = 'auth.admin.login.failed';

    public const AuthAdminLogout = 'auth.admin.logout';

    public const AuthAgenceLoginSuccess = 'auth.agence.login.success';

    public const AuthAgenceLoginFailed = 'auth.agence.login.failed';

    public const AuthAgenceRegister = 'auth.agence.register';

    public const AuthClientLoginSuccess = 'auth.client.login.success';

    public const AuthClientLoginFailed = 'auth.client.login.failed';

    public const AuthClientRegister = 'auth.client.register';

    public const ReversementCreated = 'admin.reversement.created';

    public const ReversementEffectue = 'admin.reversement.effectue';

    public const CommissionUpdated = 'admin.commission.updated';

    public const DestinationCreated = 'admin.destination.created';

    public const DestinationUpdated = 'admin.destination.updated';

    public const CollaborateurCreated = 'admin.collaborateur.created';

    public const CollaborateurDeleted = 'admin.collaborateur.deleted';

    public const AgenceCreated = 'admin.agence.created';

    public const PaiementVerifie = 'admin.paiement.verifie';

    public const BambooRequest = 'bamboo.request';

    public const BambooCallback = 'bamboo.callback';

    public const JobOffresExpire = 'job.offres.expire';

    public const JobPublicitesExpire = 'job.publicites.expire';

    /**
     * @return list<array{value: string, label: string, category: string}>
     */
    public static function options(): array
    {
        return [
            ['value' => self::AuthAdminLoginSuccess, 'label' => 'Connexion admin', 'category' => 'auth'],
            ['value' => self::AuthAdminLoginFailed, 'label' => 'Échec connexion admin', 'category' => 'auth'],
            ['value' => self::AuthAdminLogout, 'label' => 'Déconnexion admin', 'category' => 'auth'],
            ['value' => self::AuthAgenceLoginSuccess, 'label' => 'Connexion agence', 'category' => 'auth'],
            ['value' => self::AuthAgenceLoginFailed, 'label' => 'Échec connexion agence', 'category' => 'auth'],
            ['value' => self::AuthAgenceRegister, 'label' => 'Création compte agence', 'category' => 'auth'],
            ['value' => self::AuthClientLoginSuccess, 'label' => 'Connexion client', 'category' => 'auth'],
            ['value' => self::AuthClientLoginFailed, 'label' => 'Échec connexion client', 'category' => 'auth'],
            ['value' => self::AuthClientRegister, 'label' => 'Création compte client', 'category' => 'auth'],
            ['value' => self::ReversementCreated, 'label' => 'Reversement créé', 'category' => 'reversement'],
            ['value' => self::ReversementEffectue, 'label' => 'Reversement effectué', 'category' => 'reversement'],
            ['value' => self::CommissionUpdated, 'label' => 'Commission modifiée', 'category' => 'commission'],
            ['value' => self::DestinationCreated, 'label' => 'Destination créée', 'category' => 'commission'],
            ['value' => self::DestinationUpdated, 'label' => 'Destination modifiée', 'category' => 'commission'],
            ['value' => self::CollaborateurCreated, 'label' => 'Collaborateur créé', 'category' => 'compte'],
            ['value' => self::CollaborateurDeleted, 'label' => 'Collaborateur supprimé', 'category' => 'compte'],
            ['value' => self::AgenceCreated, 'label' => 'Agence créée (admin)', 'category' => 'compte'],
            ['value' => self::PaiementVerifie, 'label' => 'Paiement vérifié', 'category' => 'bamboo'],
            ['value' => self::BambooRequest, 'label' => 'Appel Bamboo Pay', 'category' => 'bamboo'],
            ['value' => self::BambooCallback, 'label' => 'Callback Bamboo Pay', 'category' => 'bamboo'],
            ['value' => self::JobOffresExpire, 'label' => 'Job offres expirées', 'category' => 'job'],
            ['value' => self::JobPublicitesExpire, 'label' => 'Job publicités expirées', 'category' => 'job'],
        ];
    }

    public static function label(string $action): string
    {
        foreach (self::options() as $option) {
            if ($option['value'] === $action) {
                return $option['label'];
            }
        }

        return $action;
    }

    /**
     * @return list<string>
     */
    public static function critical(): array
    {
        return [
            self::ReversementEffectue,
            self::CommissionUpdated,
            self::CollaborateurDeleted,
        ];
    }
}
