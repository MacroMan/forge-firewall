<?php

namespace App;

use App\Traits\IPHelper;
use App\Traits\UsesForge;
use App\Traits\UsesUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $server_id
 * @property int $port
 * @property boolean $is_installed
 * @property int $forge_id
 * @property Server $server
 */
class ServerRule extends Model
{
    use UsesUser, UsesForge, IPHelper;

    protected $fillable = [
        'server_id',
        'port',
    ];

    // Relations

    /**
     * @return BelongsTo
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    // Helpers

    /**
     * @param array $attributes
     * @return $this
     */
    public static function getOrCreate(array $attributes): self
    {
        $rule = ServerRule::where('server_id', $attributes['server_id'])
            ->where('port', $attributes['port'])->first();

        if (!$rule) {
            $rule = ServerRule::create($attributes);
        }

        return $rule;
    }

    /**
     * Install the rule on the remote server
     *
     * @return bool
     */
    public function install(): bool
    {
        if ($this->is_installed) {
            echo " - Skipping, already instaled\n";
            return true;
        }

        $user = $this->user();
        if (!$user->isIPSet()) {
            $user->setIP($this->getCurrentIP());
            $user->save();
        }

        $result = $this->forge->createFirewallRule($this->server->server_id, [
            'name' => $user->name,
            'ip_address' => $user->ip,
            'port' => trim($this->port),
            'type' => 'allow'
        ]);

        $this->forge_id = $result->id;
        $this->is_installed = true;
        echo " - Rule installed\n";
        return $this->save();
    }
}
