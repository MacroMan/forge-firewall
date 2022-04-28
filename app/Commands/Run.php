<?php

namespace App\Commands;

use App\Server;
use App\Traits\IPHelper;
use App\User;
use Exception;
use LaravelZero\Framework\Commands\Command;

class Run extends Command
{
    use IPHelper;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'run';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Check external IP and update forge servers if changed';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $user = User::getUser();
        if (!$user->name) {
            $this->error("Rule name not set. Please run `php application setup` and set the rule name");
            return;
        }

        $servers = Server::all();
        if (empty($servers)) {
            $this->error("No servers found. Please run `php application setup` and add at least one server");
            return;
        }

        // Get the current IP using a hacky shell command
        $current_ip = $this->getCurrentIP();

        $this->info($current_ip);
        $this->info($user->ip);

        if ($current_ip != $user->ip) {
            $this->notify("IP watcher", "IP address changed to {$current_ip}");
            $user->setIP($current_ip);
            $this->renew_rules();
            $this->notify("IP watcher", "Forge firewall rules updated");
        }
    }

    /**
     * Delete the current rules and re-add them
     *
     * @return void
     */
    private function renew_rules(): void
    {
        Server::all()->each(function(Server $server) {
            // Delete all store rules
            $server->deleteRules();

            // Add new rules
            $server->installRules();

            if ($server->hasErrors()) {
                $server->errors->each(function(Exception $e) {
                    $this->notify("Error adding rule", $e->getMessage());
                });
            }
        });
    }
}
