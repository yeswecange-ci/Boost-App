<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Règle anti-SSRF : empêche de saisir une URL pointant vers
 * des plages IP privées / loopback / métadonnées cloud.
 *
 * Utilisée pour les URLs de webhook configurables par l'admin
 * afin qu'un attaquant ayant pris le compte admin ne puisse pas
 * rediriger les requêtes HTTP du serveur vers des ressources internes.
 */
class NoPrivateIpUrl implements ValidationRule
{
    /**
     * Plages CIDR bloquées.
     */
    private const BLOCKED_RANGES = [
        // Loopback
        '127.0.0.0/8',
        '::1/128',
        // Lien local / APIPA
        '169.254.0.0/16',   // inclut AWS/GCP/Azure metadata 169.254.169.254
        'fe80::/10',
        // Privées RFC 1918
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        // Docker / containers
        '100.64.0.0/10',
        // Multicast & broadcast
        '224.0.0.0/4',
        '240.0.0.0/4',
    ];

    /**
     * Hôtes explicitement bloqués (métadonnées cloud).
     */
    private const BLOCKED_HOSTS = [
        'metadata.google.internal',
        'metadata.internal',
        'instance-data',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value) {
            return;
        }

        $parsed = parse_url((string) $value);
        $host   = $parsed['host'] ?? null;

        if (! $host) {
            $fail("L'URL du champ :attribute est invalide.");
            return;
        }

        // Vérification des hôtes bloqués
        if (in_array(strtolower($host), self::BLOCKED_HOSTS, true)) {
            $fail("L'URL du champ :attribute pointe vers une ressource interne non autorisée.");
            return;
        }

        // Résolution DNS → IP
        $ip = gethostbyname($host);

        if ($ip === $host && ! filter_var($ip, FILTER_VALIDATE_IP)) {
            // Hôte non résolvable : on laisse passer (sera rejeté à l'utilisation)
            return;
        }

        foreach (self::BLOCKED_RANGES as $cidr) {
            if ($this->ipInCidr($ip, $cidr)) {
                $fail("L'URL du champ :attribute pointe vers une adresse IP privée ou réservée.");
                return;
            }
        }
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$range, $prefix] = explode('/', $cidr);

        // IPv6
        if (str_contains($cidr, ':')) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return false;
            }
            $ipBin    = inet_pton($ip);
            $rangeBin = inet_pton($range);
            if ($ipBin === false || $rangeBin === false) return false;

            $prefixBytes = (int) floor((int) $prefix / 8);
            $prefixBits  = (int) $prefix % 8;

            if ($prefixBytes > 0 && substr($ipBin, 0, $prefixBytes) !== substr($rangeBin, 0, $prefixBytes)) {
                return false;
            }
            if ($prefixBits > 0) {
                $mask = 0xFF & (0xFF << (8 - $prefixBits));
                if ((ord($ipBin[$prefixBytes]) & $mask) !== (ord($rangeBin[$prefixBytes]) & $mask)) {
                    return false;
                }
            }
            return true;
        }

        // IPv4
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        $ipLong    = ip2long($ip);
        $rangeLong = ip2long($range);
        $mask      = ~((1 << (32 - (int) $prefix)) - 1);

        return ($ipLong & $mask) === ($rangeLong & $mask);
    }
}
