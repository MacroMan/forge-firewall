<?php

namespace App\Commands;

use App\Components\Forge;
use App\Server;
use App\Traits\IPHelper;
use App\Traits\UsesUser;
use App\User;
use LaravelZero\Framework\Commands\Command;
use PhpSchool\CliMenu\Action\GoBackAction;
use PhpSchool\CliMenu\Builder\CliMenuBuilder;
use PhpSchool\CliMenu\CliMenu;
use PhpSchool\CliMenu\Exception\InvalidTerminalException;
use Exception;

class Setup extends Command
{
    use UsesUser, IPHelper;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'setup';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Setup which severs and ports should be whitelisted';

    /**
     * Execute the console command.
     *
     * Display a menu to configure the various options
     */
    public function handle(): void
    {
        if (! env('FORGE_TOKEN')) {
            $this->error('Forge token not set in .env');
            return;
        }

        // Get a fresh IP
        $user = $this->user();
        $user->ip = $this->getCurrentIP();
        $user->save();

        $this->menu('Forge Firewall setup')
            ->addItem('Change rule name', [$this, 'set_name'])
            ->addSubMenu('Configure server', function (CliMenuBuilder $builder) {
                $this->configure_server($builder);
            })
            ->addSubMenu('List configured servers', function (CliMenuBuilder $builder) {
                $this->list_configured_servers($builder);
            })
            ->addItem('Remove all configured servers', [$this, 'remove_all_configured_servers'])
            ->addItem('Reset IP', [$this, 'reset_ip'])
            ->addItem('Fake IP', [$this, 'fake_ip'])
            ->open();
    }

    /**
     * @param CliMenu $menu
     * @return void
     */
    public function set_name(CliMenu $menu): void
    {
        $user = $this->user();
        $result = $menu->askText()
            ->setPromptText('Enter rule name')
            ->setPlaceholderText($user?->name ?: '')
            ->setValidationFailedText('Please enter your rule name')
            ->ask();

        if ($name = $result->fetch()) {
            $user->name = $name;
            $user->save();
        }
    }

    /**
     * @param CliMenuBuilder $builder
     * @return void
     */
    public function configure_server(CliMenuBuilder $builder): void
    {
        $builder->setTitle('Select server')
            ->disableDefaultItems();;

        $servers = Forge::make()->servers();

        foreach ($servers as $server) {
            $builder->addItem($server->name, function (CliMenu $menu) use ($servers) {
                $index = $menu->getSelectedItemIndex();
                $server = Server::getOrCreate([
                    'server_id' => $servers[$index]->id,
                    'server_name' => $servers[$index]->name,
                ]);

                $input = $menu->askText()
                    ->setPromptText('Ports to whitelist (comma seperated)')
                    ->setValidator(function() { return true; });

                if ($server->ports) {
                    $input->setPlaceholderText($server->ports);
                }

                $result = $input->ask();

                if ($ports = $result->fetch()) {
                    $server->ports = $ports;
                    $server->save();
                    $server->installRules();

                    if ($server->hasErrors()) {
                        $menu->close();
                        $this->error("One or more exceptions have occurred:");
                        $server->errors->each(function(Exception $e) {
                            $this->error($e->getMessage());
                        });
                        die();
                    }
                }

                $menu->close();
                $this->handle();
            });
        }

        $builder->addItem('Cancel', new GoBackAction);
    }

    /**
     * Output a list of all configured servers
     *
     * @param CliMenuBuilder $builder
     * @return void
     * @throws InvalidTerminalException
     */
    public function list_configured_servers(CliMenuBuilder $builder): void
    {
        $builder->setTitle('Configured servers. Select one to remove it.')
            ->disableDefaultItems();

        $servers = Server::all();

        if (!$servers->count()) {
            $builder->addStaticItem('No servers configured');
        }

        $servers->each(function(Server $server) use ($builder, $servers) {
            $builder->addItem("{$server->server_id} - {$server->server_name} - {$server->ports}", function(CliMenu $menu) use ($servers) {
                $output = $menu->cancellableConfirm('This will remove all firewall rules for this server Forge. Are you sure?')->display();

                if ($output) {
                    $index = $menu->getSelectedItemIndex();
                    $menu->close();
                    $servers->get($index)->delete();
                    $this->handle();
                }
            });
        });

        $builder->addItem('Go back', new GoBackAction);
    }

    /**
     * @param CliMenu $menu
     * @return void
     * @throws InvalidTerminalException
     */
    public function remove_all_configured_servers(CliMenu $menu): void
    {
        $output = $menu->cancellableConfirm('This will remove all firewall rules for all servers in Forge. Are you sure?')->display();

        if ($output) {
            Server::all()->each(function (Server $server) {
                $server->delete();
            });

            $menu->close();
            $this->handle();
        }
    }

    /**
     * @param CliMenu $menu
     * @return void
     */
    public function reset_ip(CliMenu $menu): void
    {
        $output = $menu->cancellableConfirm('Are you sure?')->display();

        if ($output) {
            User::getUser()->setIP($this->getCurrentIP());
        }
    }

    /**
     * @param CliMenu $menu
     * @return void
     */
    public function fake_ip(CliMenu $menu): void
    {
        $result = $menu->askText()
            ->setPromptText('IP address')
            ->setValidator(function() { return true; })
            ->ask();

        if ($ip = $result->fetch()) {
            User::getUser()->setIP($ip);
        }
    }
}
