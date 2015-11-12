<?php

namespace thebigsmileXD\MinigameBase;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerBucketEvent;
use pocketmine\event\entity\EntityArmorChangeEvent;
use pocketmine\event\entity\EntityInventoryChangeEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\player\PlayerEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerBucketEmptyEvent;

class Main extends PluginBase implements Listener{
	public $runningGames = [];
	public $database;
	public $useSQL = false;
	public $worlds = [];
	public $disableMove = false;
	public $disableChat = false;
	public $disableInteract = false;
	public $disableBreak = false;
	public $disablePlace = false;
	public $disableItemConsume = false;
	public $disableDrop = false;
	public $disableDamage = false;
	public $disablePlayerDeath = false;
	public $disableBucketUse = false;
	public $disableTeleport = false;
	public $disableBowUse = false;
	public $disableHealthRegeneration = false;
	public $disableInventoryChange = false;
	public $disableArmorChange = false;
	public $disableCollisionDamage = false;
	public $useTimer = false;
	public $messages = false;

	public function onEnable(){
		$this->makeSaveFiles();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		if($this->useSQL) $this->connectSQL();
	}

	private function makeSaveFiles(){
		$this->saveDefaultConfig();
		$this->reloadConfig();
		$this->saveResource("config.yml", false);
		$this->saveResource("messages.yml", false);
		$this->reloadConfig();
		$this->getConfig()->save();
		$this->messages = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
	}

	/* input handling */
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if($sender instanceof Player || $sender instanceof ConsoleCommandSender){ // commands for both console and player
			switch($command->getName()){
				case "commandplayerandconsole":
					{
						if($sender->hasPermission("game")){
							return true;
						}
						else{
							$sender->sendMessage($this->getTranslation("no-permission"));
							return true;
						}
						return false;
					}
				default:
					{
						if($sender instanceof Player){ // player only commands
							switch($command->getName()){
								case "commandplayer":
									{
										if($sender->hasPermission("game")){
											return true;
										}
										else{
											$sender->sendMessage($this->getTranslation("no-permission"));
											return true;
										}
										return false;
									}
								default:
									return false;
							}
						}
						elseif($sender instanceof ConsoleCommandSender){ // console only commands
							switch($command->getName()){
								case "commandplayer":
									{
										if($sender->hasPermission("game")){
											return true;
										}
										else{
											$sender->sendMessage($this->getTranslation("no-permission"));
											return true;
										}
										return false;
									}
								default:
									return false;
							}
						}
					}
			}
		}
		else{
			return false;
		}
	}

	/* functions */
	public function getTranslation($string){
		return $this->messages->get($string)?$this->messages->get($string):"string not found, check config";
	}

	public function connectSQL(){
		$host = $this->getConfig()->getNested("sql.host");
		$user = $this->getConfig()->getNested("sql.user");
		$password = $this->getConfig()->getNested("sql.password");
		$database = $this->getConfig()->getNested("sql.database");
		$port = $this->getConfig()->getNested("sql.port");
		$port = empty($port)?3306:$port;
		$db = new \mysqli($host, $user, $password, $database, $port);
		if($db->connect_errno > 0){
			$this->getLogger()->critical('Unable to connect to database [' . $db->connect_error . ']');
			return false;
		}
		else{
			$this->database = $db;
			$this->getLogger()->info('Successfully connected to database [' . $database . ']');
		}
		$request = "CREATE TABLE `mcpe_players` ( `id` INT NOT NULL AUTO_INCREMENT , `name` VARCHAR(30) NOT NULL , `displayname` VARCHAR(255) NULL , `email` VARCHAR(255) NOT NULL , `password` VARCHAR(255) NOT NULL , `registered_since` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , `last_login` TIMESTAMP NULL , `last_logout` TIMESTAMP NULL , `online` TINYINT NOT NULL DEFAULT '0' , `last_ip` VARCHAR(15) NULL , `confirmed` TINYINT NULL , PRIMARY KEY (`id`), UNIQUE (`name`), UNIQUE (`email`)) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
		if(!$result = $this->database->query($request)){
			$this->getLogger()->critical('There was an error running the query [' . $this->database->error . ']');
		}
		else{
			$this->getLogger()->notice('Successfully created database');
		}
	}

	/* eventhandler */
	public function onQuit(PlayerQuitEvent $event){
		$this->loggedInPlayers[$event->getPlayer()->getName()] = null;
		unset($this->loggedInPlayers[$event->getPlayer()->getName()]);
		return;
	}

	public function onMove(PlayerMoveEvent $event){
		if($this->disableMove) $event->setCancelled();
		return;
	}

	public function onInteract(PlayerInteractEvent $event){
		if($this->disableInteract) $event->setCancelled();
		return;
	}

	public function onBreak(BlockBreakEvent $event){
		if($this->disableBreak) $event->setCancelled();
		return;
	}

	public function onPlace(BlockPlaceEvent $event){
		if($this->disablePlace) $event->setCancelled();
		return;
	}

	public function onItemConsume(PlayerItemConsumeEvent $event){
		if($this->disableItemConsume) $event->setCancelled();
		return;
	}

	public function onChat(PlayerChatEvent $event){
		if($this->disableChat) $event->setCancelled();
		return;
	}

	public function onDrop(PlayerDropItemEvent $event){
		if($this->disableDrop) $event->setCancelled();
		return;
	}

	public function onDamage(EntityDamageEvent $event){
		if($event->getEntity() instanceof Player){
			if($this->disableDamage) $event->setCancelled();
			elseif($this->disableCollisionDamage && $event->getCause() instanceof EntityDamageByBlockEvent){
				if($this->disableCollisionDamage) $event->setCancelled();
			}
		}
		return;
	}

	public function onPlayerDeath(EntityDamageEvent $event){
		if($event->getEntity() instanceof Player){
			if($this->disablePlayerDeath){
				if($event->getDamage() >= $event->getEntity()->getHealth()){
					$event->setDamage(0.0);
					$event->getEntity()->setHealth(20);
				}
			}
		}
		return;
	}

	public function onBucketFill(PlayerBucketFillEvent $event){
		if($this->disableBucketUse) $event->setCancelled();
		return;
	}

	public function onBucketEmpty(PlayerBucketEmptyEvent $event){
		if($this->disableBucketUse) $event->setCancelled();
		return;
	}

	public function onArmorChange(EntityArmorChangeEvent $event){
		if($event->getEntity() instanceof Player){
			if($this->disableArmorChange) $event->setCancelled();
		}
		return;
	}

	public function onInventoryChange(EntityInventoryChangeEvent $event){
		if($event->getEntity() instanceof Player){
			if($this->disableInventoryChange) $event->setCancelled();
		}
		return;
	}

	public function onHealthRegeneration(EntityRegainHealthEvent $event){
		if($event->getEntity() instanceof Player && $event->getRegainReason() !== EntityRegainHealthEvent::CAUSE_MAGIC){
			if($this->disableHealthRegeneration) $event->setCancelled();
		}
		return;
	}

	public function onBowUse(EntityShootBowEvent $event){
		if($event->getEntity() instanceof Player){
			if($this->disableBowUse) $event->setCancelled();
		}
		return;
	}

	public function onTeleport(EntityTeleportEvent $event){
		if($event->getEntity() instanceof Player){
			if($this->disableTeleport) $event->setCancelled();
		}
		return;
	}
}