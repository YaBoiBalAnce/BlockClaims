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
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\pocketmine\utils;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\TextFormat;
class main extends PluginBase implements Listener{
	private $config;
	private $claims;
	private $players;
	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->players = new Config($this->getDataFolder()."players.yml",Config::YAML,array("Players" => array()));
		$this->players->save();
		$this->claims = new Config($this->getDataFolder()."claims.yml",Config::YAML,array(
				"Claims" => array()
		));
		$this->claims->save();
 		$this->config = new Config($this->getDataFolder()."config.yml",Config::YAML,array(
 			"maxclaimamount" => 2,
			"claimsize" => 10,
 			"blocktype" => 19
			
		));
		$this->config->save();
	}
	
	public function onJoin (PlayerJoinEvent $ev){
		$player = $ev->getPlayer();
		$players = $this->players->get("Players");
		if (!isset($players[$player->getName()])){
			$players[$player->getName()] = 0;
			$this->players->set("Players", $players);
			$this->players->save();
			echo "registered player ".$player->getName();
		}
	}
	
	public function onCommand(CommandSender $sender,Command $command, $label,array $args){
		switch ($command->getName()){
			case "claim":
				if ($sender instanceof Player){
					if (isset($args[0])){
						switch (strtolower($args[0])){
							case "addbuilder":
								if (!($claim = $this->inClaim(new Position($sender->getX(), $sender->getY(),$sender->getZ(),$sender->getLevel()), $sender)) == false){
									if ($claim['owner'] === $sender->getName()){
										if (isset($args[1])){
											if ($sender->hasPermission("blockclaims.addbuilder")){
											$this->addBuilder($claim, $args[1]);}
										}else{
											$sender->sendMessage("Usage: /claim addbuilder [playername]");
										}
									}else{
										$sender->sendMessage(TextFormat::RED."You must be standing in a claim made by you! This claim is made by ".$claim['owner']);
									}
								}else{
									$sender->sendMessage(TextFormat::RED."You must be standing in a claim made by you!");
								}
							break;
							case "removebuilder":
								if (!($claim = $this->inClaim(new Position($sender->getX(), $sender->getY(),$sender->getZ(),$sender->getLevel()), $sender)) == false){
									if ($claim['owner'] === $sender->getName()){
										if (isset($args[1])){
											if ($sender->hasPermission("blockclaims.removebuilder")){
											$this->removeBuilder($claim, $args[1], $sender);
											}
										}else{
											$sender->sendMessage("Usage: /claim removebuilder [buildername]");
										}
									}else{
										$sender->sendMessage(TextFormat::RED."You must be standing in a claim made by you! This claim is made by ".$claim['owner']);
									}
								}else{
									$sender->sendMessage(TextFormat::RED."You must be standing in a claim made by you!");
								}
							break;
							case "listbuilders":
								if (!($claim = $this->inClaim(new Position($sender->getX(), $sender->getY(),$sender->getZ(),$sender->getLevel()), $sender)) == false){
									if ($claim['owner'] === $sender->getName()){
										if ($sender->hasPermission("blockclaims.listbuilder")){
										$this->listBuilders($claim, $sender);}
									}else{
										$sender->sendMessage(TextFormat::RED."You must be standing in a claim made by you! This claim is made by ".$claim['owner']);
									}
								}else{
									$sender->sendMessage(TextFormat::RED."You must be standing in a claim made by you!");
								}
							break;
							case "resetclaims":
								if (isset($args[1])){
									if ($sender->hasPermission("blockclaims.resetclaims.other")){
										if (!($player = $this->getServer()->getPlayer($args[1])) == null){
											$this->resetClaims($player);
											$sender->sendMessage("Reseting claims for ".$player->getName());
											return ;
										}else{
											$sender->sendMessage("No such player");
											return ;
										}
									}else{
										$sender->sendMessage("You do not have permission to reset other players claims!");
										return ;
									}
								}
								$this->resetClaims($sender);
							break;
							case "help":
								$this->sendHelpMessage($sender);
							break;
							default:
								$sender->sendMessage("Usage: /claim [subcommand]");
								$sender->sendMessage("If you need help do /claim help");
							return ;
						}
					}else{
						$sender->sendMessage("Usage: /claim [subcommand]");
						$sender->sendMessage("If you need help do /claim help");
					}
				}else{
					$sender->sendMessage("Must must be-ingame to run command!");
				}
			break;
			
		}
	}
	
	public function onBlockPlace(BlockPlaceEvent $ev){
		
		if (!$ev->isCancelled()){
		$block = $ev->getBlock();
		$player = $ev->getPlayer();
		$claimblock = $this->config->get("blocktype");
		if (($claim = $this->inClaim(new Position($block->getX(),$block->getY(),$block->getZ(),$block->getLevel()), $ev->getPlayer())) == false){
		if ($block->getId() == $claimblock){
			
			if ($player->hasPermission("blockclaims.create")){
			if ($this->addClaim(new Position($block->getX(),$block->getY(),$block->getZ(),$block->getLevel()),$player) == false){
				$player->sendMessage("You reached your max amount of claims already!");
				$ev->setCancelled();
			}
			return ;
			}else{
				$ev->setCancelled();
				$player->sendMessage("You do not have permission to claim areas! :(");
				return ;
			}
			
		}
		return;
		}else{
			if (!$player->hasPermission("blockclaims.override")){
			$ev->setCancelled();
			$player->sendMessage("Cannot build in this area. \nThis area is claimed by ". $claim['owner']);
		}
		
		if ($block->getId() == $claimblock){
		if (in_array($player->getName(), $claim['names'])){
			$player->sendMessage("Cannot claim areas inside of claims!");
			$ev->setCancelled();
		}
		}}
		}
	}
	public function onBlockBreak(BlockBreakEvent $ev){
		if (!$ev->isCancelled()){
			$block = $ev->getBlock();
			$player = $ev->getPlayer();
			if (!($claim = $this->inClaim(new Position($block->getX(),$block->getY(),$block->getZ(),$block->getLevel()), $ev->getPlayer())) == false){
				if (!$player->hasPermission("blockclaims.override") and !in_array($player->getName(), $claim['names'])){
				$ev->setCancelled();
				$player->sendMessage("Cannot break blocks in this area. \nThis area is claimed by ". $claim["owner"]);
				return ;
				}
				$claimblock = $this->config->get("blocktype");
				if ($block->getId() == $claimblock){
					$this->removeClaim($claim, $player);
				}
				
			}
		}
	}
	
	public function sendHelpMessage(Player $player){
			$player->sendMessage(TextFormat::GOLD.TextFormat::BOLD."/Claim Help:");
			$player->sendMessage("/claim addbuilder - add player to be able to build on your claim!");
			$player->sendMessage("/claim removebuilder - removes player from being able to build on your claim!");
			$player->sendMessage("/claim listbuilders - list players that are able to build in your claim");
			$player->sendMessage("/claim resetclaims - resets/removes all your claims!");
	}
	
	public function canHaveClaim(Player $player){
		$amount = $this->players->get("Players", []);
		$claims = $amount[$player->getName()];
		if ($claims <= $this->config->get("maxclaimamount") or $player->hasPermission("blockclaims.overclaim")){
			return true;
		}
		return false;
	}
	
	public function resetClaims(Player $player){
		$claims = $this->claims->get("Claims");
		foreach ($claims as $claim){
			if ($claim['owner'] === $player->getName()){
				$claimid = array_search($claim, $claims);
				$pos = explode(":", $claim['pos1']);
				$claimsize = $this->config->get("claimsize");
				$this->getServer()->getLevelByName($pos[3])->setBlockIdAt($pos[0]-$claimsize, $pos[1], $pos[2]-$claimsize, 0);
				unset($claims[$claimid]);
			}
		}
		$this->claims->set("Claims", $claims);
		$this->claims->save();
		$player->sendMessage("Your blockclaims have been reset!");
		$players = $this->players->get("Players");
		$players[$player->getName()] = 0;
		$this->players->set("Players" , $players);
		$this->players->save();
	}
	
	public function removeClaim($claim, Player $player){
		$claims = $this->claims->get("Claims");
		$claimid = array_search($claim, $claims);
		$pos = explode(":", $claim['pos1']);
		$claimsize = $this->config->get("claimsize");
		$this->getServer()->getLevelByName($pos[3])->setBlockIdAt($pos[0]-$claimsize, $pos[1], $pos[2]-$claimsize, 0);
		unset($claims[$claimid]);
		$this->claims->set("Claims", $claims);
		$this->claims->save();
		$player->sendMessage("Claim was successfully deleted!");
		$players = $this->players->get("Players");
		$amount = $players[$player->getName()] - 1;
		$players[$player->getName()] = $amount;
		$this->players->set("Players" , $players);
		$this->players->save();
	}
	
	public function addClaim(Position $pos, Player $player){
		if ($this->canHaveClaim($player)){
		$claimsize = $this->config->get("claimsize");
		$player->sendMessage("You placed a claim block this area is now claimed by you! :)");
		$pos1 = ($pos->getFloorX()+$claimsize).":".$pos->getY().":".($pos->getFloorZ()+$claimsize).":".$pos->getLevel()->getName();
		$pos2 = ($pos->getFloorX()-$claimsize).":".$pos->getY().":".($pos->getFloorZ()-$claimsize).":".$pos->getLevel()->getName();
		$array = $this->claims->get("Claims", []);
		$array[] = array("pos1" => $pos1,"pos2" => $pos2,"owner" => $player->getName(), "names" => array($player->getName()));
		$this->claims->set("Claims",$array);
		$this->claims->save();
		$players = $this->players->get("Players");
		$amount = $players[$player->getName()] + 1;
		$players[$player->getName()] = $amount;
		$this->players->set("Players" , $players);
		$this->players->save();
		return true;
		}
		return false;
		
	}
	
	public function listBuilders($claim, Player $player){
		$claims = $this->claims->get("Claims");
		$claimid = array_search($claim, $claims);
		$builders = $claims[$claimid]['names'];
		$player->sendMessage(TextFormat::GOLD.TextFormat::BOLD."Builders: \n".implode(", ", $builders));
	}
	
	public function removeBuilder($claim, $buildername, Player $player){
		echo "trying removing builder!";
		$claims = $this->claims->get("Claims");
		$claimid = array_search($claim, $claims);
		if (in_array($buildername, $claims[$claimid]['names'], $buildername)){
			$builderid = array_search($buildername, $claims[$claimid]['names']);
			unset($claims[$claimid]['names'][$builderid]);
			$this->claims->set("Claims", $claims);
			$this->claims->save();
			$player->sendMessage("Removed builder succefully from your claim!");
		}else{
			$player->sendMessage("No such builder do /claim builderlist to list builders in your claim!");
		}
	}
	
	public function addBuilder($claim , $playername){
		//echo "tried adding builder!";
		$claims = $this->claims->get("Claims");
		$id = array_search($claim, $claims);
		$claims[$id]['names'][] = $playername;
		$this->claims->set("Claims", $claims);
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
			//echo "result: ".$isInside;
			if ($isInside){
				return $claim;
			}
		}
		return false;
	}
}
