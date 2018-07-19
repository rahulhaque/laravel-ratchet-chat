<?php

namespace App\Http\Controllers;

use App\Message;
use Exception;
use SplObjectStorage;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class RatchetController extends Controller implements MessageComponentInterface
{
    protected $clients;
    protected $users;

    /**
     * Store all the connected clients in php SplObjectStorage
     *
     * RatchetController constructor.
     */
    public function __construct()
    {
        $this->clients = new SplObjectStorage;
    }

    /**
     * Store the connected client in SplObjectStorage
     * Notify all clients about total connection
     *
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn)
    {
        echo "Client connected " . $conn->resourceId . " \n";
        $this->clients->attach($conn);
        foreach ($this->clients as $client) {
            $client->send(json_encode([
                "type" => "socket",
                "msg" => "Total Connected: " . count($this->clients)
            ]));
        }
    }

    /**
     * Remove disconnected client from SplObjectStorage
     * Notify all clients about total connection
     *
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        foreach ($this->clients as $client) {
            $client->send(json_encode([
                "type" => "socket",
                "msg" => "Total Connected: " . count($this->clients)
            ]));
        }
    }

    /**
     * Receive message from connected client
     * Broadcast message to other clients
     *
     * @param ConnectionInterface $from
     * @param string $data
     */
    public function onMessage(ConnectionInterface $from, $data)
    {
        $resource_id = $from->resourceId;
        $data = json_decode($data);
        $type = $data->type;
        switch ($type) {
            case 'chat':
                $user_id = $data->user_id;
                $user_name = $data->user_name;
                $chat_msg = $data->chat_msg;
                $response_from = "<span class='text-success'><b>$user_id. $user_name:</b> $chat_msg <span class='text-warning float-right'>".date('Y-m-d h:i a')."</span></span><br><br>";
                $response_to = "<span class='text-info'><b>$user_id. $user_name</b>: $chat_msg <span class='text-warning float-right'>".date('Y-m-d h:i a')."</span></span><br><br>";
                // Output
                $from->send(json_encode([
                    "type" => $type,
                    "msg" => $response_from
                ]));
                foreach ($this->clients as $client) {
                    if ($from != $client) {
                        $client->send(json_encode([
                            "type" => $type,
                            "msg" => $response_to
                        ]));
                    }
                }

                // Save to database
                $message = new Message();
                $message->user_id = $user_id;
                $message->name = $user_name;
                $message->message = $chat_msg;
                $message->save();

                echo "Resource id $resource_id sent $chat_msg \n";
                break;
        }
    }

    /**
     * Throw error and close connection
     *
     * @param ConnectionInterface $conn
     * @param Exception $e
     */
    public function onError(ConnectionInterface $conn, Exception $e)
    {
        $conn->close();
    }
}
