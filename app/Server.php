<?php

namespace App;

use App\Traits\UsesForge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Laravel\Forge\Exceptions\NotFoundException;
use Exception;

/**
 * @property int $id
 * @property int $server_id
 * @property string $server_name
 * @property string $ports
 * @property ServerRule[] $rules
 */
class Server extends Model
{
    use UsesForge;

    public ?Collection $errors;

    /**
     * @var string[]
     */
    protected $fillable = [
        'server_id',
        'server_name',
        'ports',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->errors = new Collection();
    }

    // Relations

    /**
     * @return HasMany
     */
    public function rules(): HasMany
    {
        return $this->hasMany(ServerRule::class);
    }

    // Helpers

    public static function getOrCreate(array $attributes): self
    {
        $server = Server::where('server_id', $attributes['server_id'])->first();

        if (!$server) {
            $server = Server::create($attributes);
        }

        return $server;
    }

    /**
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !!$this->errors->count();
    }

    /**
     * Delete all firewall rules
     *
     * @return void
     */
    public function deleteRules(): void
    {
        $this->rules->each(function(ServerRule $rule) {
            if ($rule->forge_id) {
                try {
                    $this->forge->deleteFirewallRule($this->server_id, $rule->forge_id);
                } catch (NotFoundException $e) {
                    // Do nothing
                }
            }

            $rule->delete();
        });
    }

    /**
     * Add all firewall rules
     *
     * @return void
     */
    public function installRules(): void
    {
        collect(explode(',', $this->ports))->each(function(string $port) {
            $serverRule = ServerRule::getOrCreate([
                'server_id' => $this->id,
                'port' => trim($port),
            ]);

            echo "Installing rule for {$this->server_id} port {$port}";

            try {
                $serverRule->install();
            } catch (Exception $e) {
                $this->errors->add($e);
            }
        });
    }

    /**
     * Delete the rules in Forge before deleting this model
     *
     * @return bool|null
     */
    public function delete(): ?bool
    {
        $this->deleteRules();
        return parent::delete();
    }
}
