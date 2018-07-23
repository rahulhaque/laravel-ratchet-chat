<?php

namespace App\Console\Commands;

use App\Http\Controllers\RatchetController;
use Illuminate\Console\Command;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory as LoopFactory;
use React\Socket\Server as Reactor;

class StartRatchetServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ratchet:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start ratchet server';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $port = env('RATCHET_PORT') ? env('RATCHET_PORT') : 8090;
        echo "Ratchet server started on localhost:$port \n";
        $loop = LoopFactory::create();
        $socket = new Reactor($port, $loop);
        $server = new IoServer(new HttpServer(new WsServer(new RatchetController($loop))), $socket, $loop);
        $server->run();
    }
}
