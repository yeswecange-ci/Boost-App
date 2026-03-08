<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacebookPage extends Model
{
    protected $fillable = [
        'page_id', 'ad_account_id', 'page_name', 'access_token', 'instagram_account_id', 'is_active'
    ];

    // Le token ne doit jamais apparaître dans les sérialisations JSON/array
    protected $hidden = ['access_token'];

    protected $casts = ['is_active' => 'boolean'];

    /**
     * Chiffrement transparent du token d'accès Facebook.
     *
     * Le setter chiffre systématiquement avec la clé APP_KEY.
     * Le getter tente un déchiffrement ; en cas d'échec (token legacy en clair),
     * il retourne la valeur brute afin que la prochaine sauvegarde chiffre.
     */
    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if ($value === null || $value === '') {
                    return null;
                }
                try {
                    return decrypt($value);
                } catch (DecryptException) {
                    // Token existant en clair (avant migration) — retourné tel quel.
                    // Il sera chiffré automatiquement à la prochaine mise à jour du modèle.
                    return $value;
                }
            },
            set: fn (?string $value): ?string => $value ? encrypt($value) : null,
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'facebook_page_user');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(FacebookPost::class, 'facebook_page_id');
    }
}