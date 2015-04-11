<?php
namespace WsPPServer\Service;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use WsPPServer\Protocol\Protocol;

class PushPull implements MessageComponentInterface 
{
	const MTD_ADD_SUBSCRIPT   = 'addsubscription';
	const MTD_DEL_SUBSCRIPT   = 'delsubscription';
	const MTD_SEND_DATAS      = 'send';
	const MTD_DISCOVERY       = 'discovery';
	const MTD_DISCOVERY_CLOSE = 'discoveryclose';
	const MTD_CONNECT		  = 'connect';
	const MTD_DETECT		  = 'detect';
	
	protected $sclients;
	protected $subscriptions;
	protected $clients;
	protected $discovery;

    public function __construct() 
    {
        $this->sclients      = new \SplObjectStorage;
        $this->subscriptions = new \ArrayObject;
        $this->clients       = new \ArrayObject;
        $this->discovery     = new \ArrayObject();
    }

    public function onOpen(ConnectionInterface $conn) 
    {
        $this->sclients->attach($conn);
        syslog(2, "{New connection socket! ({$conn->resourceId})");
    }

    public function onMessage(ConnectionInterface $from, $msg) 
    {
    	$p = new Protocol($msg);
    	if(!$this->clients->offsetExists($from->resourceId) && $p->getMethode() != self::MTD_CONNECT) {
    		$from->send(json_encode(array('error' => 'not connected')));
    		unset($p);
    		return;
    	}
    	
    	switch ($p->getMethode()) {
    		case self::MTD_ADD_SUBSCRIPT :
    			echo " =>  " . $from->resourceId . " : addsubscriptions\n";
    			$this->addSubscription($from, $p->getSubscription());
    			break;
    		case self::MTD_DEL_SUBSCRIPT :
    			echo " =>  " . $from->resourceId . " : delsubscriptions\n";
    			$this->delSubscription($from, $p->getSubscription());
    			break;
    		case self::MTD_SEND_DATAS :
    			echo " =>  " . $from->resourceId . " : message\n";
    			$this->send($from, $p->getSubscription(), $p->getDatas());
    			break;
    		case self::MTD_CONNECT :
    			echo " =>  " . $from->resourceId . " : connect\n";
    			$this->connect($from, $p->getDatas());
    			break;
    		case self::MTD_DISCOVERY :
    			echo " =>  " . $from->resourceId . " : discovery\n";
    			$this->discovery($from, $p->getSubscription());
    			break;
    		case self::MTD_DETECT :
    			echo " =>  " . $from->resourceId . " : detect\n";
    			$this->detect($from, $p->getSubscription());
    			break;
    	}
        unset($p);
    }

    public function onClose(ConnectionInterface $conn) 
    {
    	if($this->clients->offsetExists($conn->resourceId)) {
	    	$my = $this->clients->offsetGet($conn->resourceId)->offsetGet('identification');
	    	foreach ($this->discovery as $discovery => $clients) {
	    		if($clients->offsetExists($conn->resourceId)) {
	    			$clients->offsetUnset($conn->resourceId);
	    			foreach ($clients as $id => $client) {
	    				$client->send(json_encode(array('subscription' =>  $discovery,'type' => self::MTD_DISCOVERY_CLOSE,'datas' => $my)));
	    			}
	    		}
	    	}
	        $subscriptions = $this->clients[$conn->resourceId]['subscription'];
	        foreach ($subscriptions as $id => $subscription) {
	        	$this->subscriptions[$id]->offsetUnset($conn->resourceId);
	        }
	    	
	        $this->clients->offsetUnset($conn->resourceId);
    	}
        syslog(2, "Connection {$conn->resourceId} has disconnected");
        $conn->close();
        $this->sclients->detach($conn);
        unset($conn); 
    }

    public function onError(ConnectionInterface $conn, \Exception $e) 
    {
    	syslog(1, "An error has occurred: {$e->getMessage()}");
        $conn->close();
    }
    
    public function delSubscription($client, $subscription)
    {
    	$subscription = (string)$subscription;
    	if($this->clients[$client->resourceId]['subscription']->offsetExists($subscription)) {
    		$this->clients[$client->resourceId]['subscription']->offsetUnset($subscription);
    	}
    	if($this->subscriptions[$subscription]->offsetExists($client->resourceId)) {
    		$this->subscriptions[$subscription]->offsetUnset($client->resourceId);
    	}

    	$client->send(json_encode(array('subscription' =>  $subscription,'type' => self::MTD_DEL_SUBSCRIPT,'datas' => true)));
    }
    
    public function addSubscription($client, $subscription)
    {
    	if(!$this->clients[$client->resourceId]['subscription']->offsetExists($subscription)) {
    		$this->clients[$client->resourceId]['subscription']->offsetSet($subscription, $subscription);
    	}
    	if(!$this->subscriptions->offsetExists($subscription)) {
    		$this->subscriptions->offsetSet($subscription, new \ArrayObject());
    	}
    	if(!$this->subscriptions[$subscription]->offsetExists($client->resourceId)) {
    		$this->subscriptions[$subscription]->offsetSet($client->resourceId, $client);
    	}
    	
    	$client->send(json_encode(array('subscription' =>  $subscription,'type' => self::MTD_ADD_SUBSCRIPT,'datas' => true)));
    }
    
    public function send($client, $subscription, $datas)
    {
    	$subscription = (string)$subscription;
    	if(!$this->subscriptions->offsetExists()) {
    		return false;
    	}
    	$clients = $this->subscriptions->offsetGet($subscription);
    	foreach ($clients as $id => $client) {
    		$client->send(json_encode(array('subscription' =>  $subscription,'type' => 'recv', 'datas' => $datas)));
    	}
    }
    
    public function connect($client, $datas)
    {
    	$this->clients->offsetSet($client->resourceId, new \ArrayObject());
    	$this->clients[$client->resourceId]->offsetSet('identification', $datas);
    	$this->clients[$client->resourceId]->offsetSet('subscription', new \ArrayObject());
    	syslog(2, 'client is connected');
    	$client->send(json_encode(array('type' => self::MTD_CONNECT, 'datas' => true)));
    }
    
    public function discovery($from, $subscription)
    {
    	$subscription = (string)$subscription;
    	$cs = array();
    	$my = $this->clients->offsetGet($from->resourceId)->offsetGet('identification');
    	if(!$this->discovery->offsetExists($subscription)) {
    		$this->discovery->offsetSet($subscription, new \ArrayObject());
    	}
    	$discovery = $this->discovery->offsetGet($subscription);
    	foreach ($discovery as $id => $client) {
    		$client->send(json_encode(array('subscription' =>  $subscription,'type' => self::MTD_DISCOVERY,'datas' => $my)));
    		$cs[] = $this->clients->offsetGet($id)->offsetGet('identification');
    	}
    	if(!$this->discovery[$subscription]->offsetExists($from->resourceId)) {
    		$this->discovery[$subscription]->offsetSet($from->resourceId, $from);
    	}
    	$from->send(json_encode(array('subscription' =>  $subscription,'type' => self::MTD_DISCOVERY,'datas' => $cs)));
    }
    
    public function detect($from, $subscription)
    {
    	$subscription = (string)$subscription;
    	$cs = array();
    	if($this->discovery->offsetExists($subscription)) {
	    	$discovery = $this->discovery->offsetGet($subscription);
	    	foreach ($discovery as $id => $client) {
	    		$cs[] = $this->clients->offsetGet($id)->offsetGet('identification');
	    	}
    	}
    	
    	$from->send(json_encode(array('subscription' =>  $subscription,'type' => self::MTD_DISCOVERY,'datas' => $cs)));
    }
}