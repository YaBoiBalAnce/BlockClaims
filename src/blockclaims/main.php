<?php
namespace blockclaims;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\block\Block;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\level\pocketmine\level;
class main extends PluginBase implements Listener{
	private $config;
	private $claims;
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->claims = new Config($this->getDataFolder()."claims.yml",Config::YAML,array(
				"Claims" => array()
		));
		$this->claims->save();
// 		$this->config = new Config($this->getDataFolder()."config.yml",Config::YAML,array(
// 			"blockedworlds" => array(
// 					"blockedexampleworld1", "blockedexampleworld2"
// 			)
// 		));
// 		$this->config->save();
	}
	public function onBlockPlace(BlockPlaceEvent $ev){
		
		if (!$ev->isCancelled()){
		$block = $ev->getBlock();
		$player = $ev->getPlayer();
		if (($claim = $this->inClaim(new Position($block->getX(),$block->getY(),$block->getZ(),$block->getLevel()), $ev->getPlayer())) == false){
		$claimblock = Block::SPONGE;
		if ($block->getId() == Block::SPONGE){
			$this->addClaim(new Position($block->getX(),$block->getY(),$block->getZ(),$block->getLevel()),$player);
			return ;
		}
		return;
		}else{
			$ev->setCancelled();
			$player->sendMessage("Cannot build in this area. \nThis area is claimed by ". $claim);
		}
		}
	}
	public function onBlockBreak(BlockBreakEvent $ev){
		if (!$ev->isCancelled()){
			$block = $ev->getBlock();
			$player = $ev->getPlayer();
			if (!($claim = $this->inClaim(new Position($block->getX(),$block->getY(),$block->getZ(),$block->getLevel()), $ev->getPlayer())) == false){
				$ev->setCancelled();
				$player->sendMessage("Cannot break blocks in this area. \nThis area is claimed by ". $claim);
			}
		}
	}
	
	public function addClaim(Position $pos, Player $player){
		$player->sendMessage("You placed a claim block this area is now claimed by you! :)");
		$pos1 = ($pos->getFloorX()+10).":".$pos->getY().":".($pos->getFloorZ()+10).":".$pos->getLevel()->getName();
		$pos2 = ($pos->getFloorX()-10).":".$pos->getY().":".($pos->getFloorZ()-10).":".$pos->getLevel()->getName();
		$array = $this->claims->get("Claims", []);
		$array[$player->getName()] = array("pos1" => $pos1,"pos2" => $pos2,"owner" => $player->getName(), "names" => array($player->getName()));
		$this->claims->set("Claims",$array);
		$this->claims->save();
	}
	
	public function inClaim(Position $pos, Player $player){
		//echo "\nTesting! \n";
		$claims = $this->claims->get("Claims");
		foreach ($claims as $claim){
			$pos1 = $claim['pos1'];
			$pos2 = $claim['pos2'];
			$v1 = explode(":", $pos1);
			$v2 = explode(":", $pos2);
			$first = new Vector3($v2[0], $v2[1], $v2[2]);
			$second = new Vector3($v1[0], $v1[1], $v1[2]);
			$toCheck = new Vector3($pos->getX(), $pos->getY(), $pos->getZ());
			$isInside = (min($first->getX(),$second->getX()) <= $toCheck->getX()) && (max($first->getX(),$second->getX()) >= $toCheck->getX()) && (min($first->getZ(),$second->getZ()) <= $toCheck->getZ()) && (max($first->getZ(),$second->getZ()) >= $toCheck->getZ());
			echo "result: ".$isInside;
			if ($isInside){
				if (in_array($player->getName(), $claim["names"])){
					return false;
				}
				return $claim['owner'];
			}
		}
		return false;
	}
}