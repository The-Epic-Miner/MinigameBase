<?php

namespace thebigsmileXD\BetterLogin;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener{
	public $runningGames = [];
	public $database;
	public $useSQL = false;
	public $worlds = [];

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
		$this->messages = new Config("messages.yml");
		$this->getConfig()->save();
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
	
	// Chat event already handled above
	public function onMove(PlayerMoveEvent $event){
		if(!$this->isLoggedIn($event->getPlayer())) $event->setCancelled();
		return;
	}

	public function onInteract(PlayerInteractEvent $event){
		if(!$this->isLoggedIn($event->getPlayer())) $event->setCancelled();
		return;
	}

	public function onBreak(BlockBreakEvent $event){
		if(!$this->isLoggedIn($event->getPlayer())) $event->setCancelled();
		return;
	}

	public function onPlace(BlockPlaceEvent $event){
		if(!$this->isLoggedIn($event->getPlayer())) $event->setCancelled();
		return;
	}

	public function onItemConsume(PlayerItemConsumeEvent $event){
		if(!$this->isLoggedIn($event->getPlayer())) $event->setCancelled();
		return;
	}

	public function onQuit(PlayerQuitEvent $event){
		$this->loggedInPlayers[$event->getPlayer()->getName()] = null;
		unset($this->loggedInPlayers[$event->getPlayer()->getName()]);
		return;
	}

	public function onChat(PlayerChatEvent $event){
		if(!$this->isLoggedIn($event->getPlayer())) $event->setCancelled();
		return;
	}
}