<?php

/*
  Created by MuhammadRestu999 & FearTeam

  Line :
    onEnable     => 31
    login        => 45
    register     => 88
    onPlayerJoin => 121
    onPlayerQuit => 140
*/


namespace fearteam;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use jojoe77777\FormAPI\CustomForm;

class Main extends PluginBase implements Listener {
  private $trying = array();

  public function onEnable(): void {
    $this->getServer()->getPluginManager()->registerEvents($this, $this); // for PlayerJoinEvent and PlayerQuitEvent
    $this->saveDefaultConfig();
    $this->reloadConfig();
    $db = $this->getDataFolder() . "/db.json";

    if(!is_dir($this->getDataFolder())) {
      @mkdir($this->getDataFolder());
    }
    if(!is_file($db)) {
      file_put_contents($db, "{}");
    }
  }

  public function login($player) {
    if(!isset($this->trying[$player->getName()])) {
      $this->trying[$player->getName()] = 1;
    }
    if($this->trying[$player->getName()] > $this->getConfig()->get("main")["maxTrying"]) {
      $text = $this->getConfig()->get("text");
      $kick = $text["login.kick"];
      $player->kick(TextFormat::RED . $kick);
      $this->trying[$player->getName()] = 1;
      return false;
    }

    $form = new CustomForm(function(Player $player, array $data = null) {
      if($data === null) {
        $this->login($player);
        return false;
      }

      $name = $player->getName();
      $db = $this->getDataFolder() . "/db.json";
      $f = file_get_contents($db);
      $json = json_decode($f, true);

      $pswd = $data[1];
      if($json[$name] !== $pswd) {
        $this->trying[$name] += 1;
        $this->login($player);
        return false;
      }
      $this->trying[$name] = 1;

      return true;
    });
    $text = $this->getConfig()->get("text");
    $main = $this->getConfig()->get("main");

    $form->setTitle("Login");
    $form->addLabel(str_replace("{maxTrying}", $main["maxTrying"], str_replace("{trying}", $this->trying[$player->getName()], $text["login.label"])));
    $form->addInput("Password :", "ExamplePassword123", "");
    $form->sendToPlayer($player);
    return $form;
  }

  public function register($player, string $msg = "") {
    $form = new CustomForm(function(Player $player, array $data = null) {
      if($data === null) {
        $this->register($player);
        return false;
      }
      $name = $player->getName();
      $db = $this->getDataFolder() . "/db.json";
      $f = file_get_contents($db);
      $json = json_decode($f, true);

      $pswd = $data[1];
      $cnfr = $data[2];

      if($cnfr !== $pswd) {
        $text = $this->getConfig()->get("text");
        $this->register($player, $text["register.notmatch"] . "\n\n");
        return false;
      }

      $json[$name] = $pswd;
      file_put_contents($db, json_encode($json, JSON_PRETTY_PRINT));
    });
    $text = $this->getConfig()->get("text");

    $form->setTitle("Register");
    $form->addLabel($msg . $text["register.label"]);
    $form->addInput("Password :", "ExamplePassword123", "");
    $form->addInput("Repeat password :", "ExamplePassword123", "");
    $form->sendToPlayer($player);
    return $form;
  }

  public function onPlayerJoin(PlayerJoinEvent $event) {
    var_dump(TextFormat::YELLOW);
    $player = $event->getPlayer();
    $name = $player->getName();

    $db = $this->getDataFolder() . "/db.json";
    $f = file_get_contents($db);
    $json = json_decode($f, true);

    if(array_key_exists($name, $json)) {
      $this->login($player);
    } else {
      $this->register($player);
    }

    $text = $this->getConfig()->get("text");
    $event->setJoinMessage(TextFormat::YELLOW . str_replace("{player_name}", $name, $text["join.message"]));
  }

  public function onPlayerQuit(PlayerQuitEvent $event) {
    $player = $event->getPlayer();
    $name = $player->getName();
    $text = $this->getConfig()->get("text");

    $event->setQuitMessage(TextFormat::YELLOW . str_replace("{player_name}", $name, $text["left.message"]));
  }
}
