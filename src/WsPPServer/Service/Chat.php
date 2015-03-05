<?php
namespace WsPPServer\Service;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use WsPPServer\Protocol\Protocol;

class Chat implements MessageComponentInterface 
{
	const MTD_ADD_SUBSCRIPT = "addsubscription";
	const MTD_SEND_DATAS    = "sendmessage";
	const MTD_DISCOVERY     = "discovery";
	
	protected $sclients;
	protected $subscriptions;
	protected $clients;

    public function __construct() 
    {
        $this->sclients      = new \SplObjectStorage;
        $this->subscriptions = new \ArrayObject;
        $this->clients       = new \ArrayObject;
    }

    public function onOpen(ConnectionInterface $conn) 
    {
        $this->sclients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) 
    {
    	echo "message: " . $msg . " De: " . $from->resourceId . "\n";
    	$p = new Protocol($msg);
    	switch ($p->getMethode()) {
    		case self::MTD_ADD_SUBSCRIPT :
    			echo " =>  " . $from->resourceId . " : subscriptions\n";
    			$this->addSubscription($from, $p->getSubscription());
    			break;
    		case self::MTD_SEND_DATAS :
    			echo " =>  " . $from->resourceId . " : message\n";
    			$this->sendMessage($from, $p->getSubscription(), $p->getDatas());
    			break;
    	}
        unset($p);
    }

    public function onClose(ConnectionInterface $conn) 
    {
        $this->sclients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) 
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
    
    public function addSubscription($client, $subscription)
    {
    	if(!$this->subscriptions->offsetExists($subscription)) {
    		$this->subscriptions->offsetSet($subscription, new \ArrayObject());
    	}
    	if(!$this->clients->offsetExists($client->resourceId)) {
    		$this->clients->offsetSet($client->resourceId, new \ArrayObject());
    	}
    	if(!$this->clients[$client->resourceId]->offsetExists($subscription)) {
    		$this->clients[$client->resourceId]->offsetSet($subscription, $subscription);
    	}
    	if(!$this->subscriptions[$subscription]->offsetExists($client->resourceId)) {
    		$this->subscriptions[$subscription]->offsetSet($client->resourceId, $client);
    	}
    }
    
    public function sendMessage($client, $subscription, $datas)
    {
    	if(!$this->subscriptions->offsetExists($subscription)) {
    		return false;
    	}
    	$clients = $this->subscriptions->offsetGet($subscription);
    	foreach ($clients as $id => $client) {
    		$client->send(json_encode(array('subscription' =>  $subscription, 'datas' => $datas)));
    	}
    }
}