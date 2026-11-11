<?php

namespace App\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Ein Betrieb auf my-servicecenter.
 *
 * - id = Subdomain (z. B. "musterbetrieb" → musterbetrieb.my-servicecenter.com)
 * - eigene Datenbank: sc_tenant_<id>
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * Echte Spalten der Tabelle "tenants". Alles andere landet im JSON-Feld "data".
     */
    public static function getCustomColumns(): array
    {
        return ['id', 'name', 'branche', 'ort', 'email', 'aktiv'];
    }

    /**
     * Die ID ist ein Text (die Subdomain), keine fortlaufende Nummer.
     * Ohne diese drei Methoden würde Laravel die ID nach dem Speichern auf 0 setzen.
     */
    public function getIncrementing()
    {
        return false;
    }

    public function shouldGenerateId(): bool
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }

    protected function casts(): array
    {
        return [
            'aktiv' => 'boolean',
        ];
    }

    /**
     * Adresse des Betriebs, z. B. http://musterbetrieb.my-servicecenter.test
     */
    public function subdomainUrl(): string
    {
        $central = config('tenancy.central_domains')[0];

        return request()->getScheme().'://'.$this->getTenantKey().'.'.$central;
    }
}
