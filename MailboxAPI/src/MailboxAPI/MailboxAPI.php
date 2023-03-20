<?php
declare(strict_types=1);

namespace MailboxAPI;

use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use MailboxAPI\Commands\MainCommand;
use MailboxAPI\Commands\MessageCommand;
use MailboxAPI\Commands\OPCommand;
use pocketmine\scheduler\Task;
use pocketmine\Server;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;

use LifeInventoryLib\LifeInventoryLib;
use LifeInventoryLib\InventoryLib\LibInvType;

use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\world\WorldManager;

class MailboxAPI extends PluginBase
{
  protected $config;
  public $db;
  private static $instance = null;

  public static function getInstance(): MailboxAPI
  {
    return static::$instance;
  }

  public function onLoad():void
  {
    self::$instance = $this;
  }

  public function onEnable():void
  {
    $this->player = new Config ($this->getDataFolder() . "players.yml", Config::YAML);
    $this->pldb = $this->player->getAll();
    $this->mail = new Config ($this->getDataFolder() . "mails.yml", Config::YAML);
    $this->maildb = $this->mail->getAll();
    $this->list = new Config ($this->getDataFolder() . "lists.yml", Config::YAML);
    $this->listdb = $this->player->getAll();
    $this->page = new Config ($this->getDataFolder() . "pages.yml", Config::YAML);
    $this->pagedb = $this->page->getAll();
    $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    $this->getServer()->getCommandMap()->register('MailboxAPI', new MainCommand($this));
    $this->getServer()->getCommandMap()->register('MailboxAPI', new OPCommand($this));
    $this->getServer()->getCommandMap()->register('MailboxAPI', new MessageCommand($this));
  }
  public function getLists($player) : array{
    $name = $player->getName ();
    $arr = [];
    foreach($this->pldb [strtolower ( $name )] as $MailboxAPI => $v){
      array_push($arr, $MailboxAPI);
    }
    return $arr;
  }

  public function getMailLists($player): array
  {
    $name = $player->getName ();
    $arr = [];
    foreach($this->maildb [strtolower ( $name )] ["편지"] as $msg => $v) {
      $list = $this->maildb [strtolower ( $name )] ["편지"] [$msg];
      $arr[] = $list;
    }
    return $arr;
  }

  public function MailMsg ($EventPlayer, $EventPlayerName)
  {
    $tag = "§l§b[편지함]§r§7 ";
    foreach ( $this->getServer ()->getOnlinePlayers () as $player ) {
      $name = $player->getName ();
      if ($EventPlayerName == strtolower($name)){
        $player->sendMessage( $tag . "* " . $EventPlayer->getName () . "님이 편지를 보냈어요 명령어를 이용해 편지를 읽어보세요. [ /편지 ]");
        return true;
      }
    }
  }

  public function RandomItem($player,$itemCount,$itemName){
    $tag = "§l§b[ MailBox ] §r§7";
    $item = $player->getInventory()->getItemInHand();
    $item->setCount((int) $itemCount);
    $nbt = $item->jsonSerialize ();
    foreach ( $this->getServer ()->getOnlinePlayers () as $players ) {
      $name = $players->getName ();
      if (isset($this->pldb [strtolower ( $name )] [$itemName])) {
        $item = Item::jsonDeserialize ( $this->pldb [strtolower ( $name )] [$itemName] );
        $item->setCount((int)$item->getCount()+(int)$itemCount);
        $nbt = $item->jsonSerialize ();
        $this->pldb [strtolower ( $name )] [$itemName] = $nbt;
        $this->save();
        if ($this->getServer()->getPlayerExact($name) != null) {
          $this->getServer()->getPlayerExact($name)->sendMessage( $tag . "* 운영진의 선물이 메일함에 도착했어요. [ /메일함 ]");
          return true;
        }
      } else {
        $this->pldb [strtolower ( $name )] [$itemName] = $nbt;
        $this->save();
        if ($this->getServer()->getPlayerExact($name) != null) {
          $this->getServer()->getPlayerExact($name)->sendMessage( $tag . "* 운영진의 선물이 메일함에 도착했어요. [ /메일함 ]");
          return true;
        }
        return true;
      }
    }
  }
  public function getInventoryPlayerItem($name,$couponname,$nbt){
    $tag = "§l§b[ MailBox ] §r§7";
    if ($this->getServer()->getPlayerExact($name) != null) {
      $this->getServer()->getPlayerExact($name)->sendMessage( $tag . "* 아이템 보호를 위해 메일함으로 아이템이 지급됬습니다. [ /메일함 ]");
      if (isset($this->pldb [strtolower ( $name )] [$couponname])) {
        $item1 = Item::jsonDeserialize ( $nbt );
        $item2 = Item::jsonDeserialize ( $this->pldb [strtolower ( $name )] [$couponname] );
        $item2->setCount((int)$item1->getCount()+(int)$item2->getCount());
        $nbt = $item2->jsonSerialize ();
        $this->pldb [strtolower ( $name )] [$couponname] = $nbt;
        $this->save();
        return true;
      } else {
        $this->pldb [strtolower ( $name )] [$couponname] = $nbt;
        $this->save();
        return true;
      }
    } else {
      if (isset($this->pldb [strtolower ( $name )] [$couponname])) {
        $item1 = Item::jsonDeserialize ( $nbt );
        $item2 = Item::jsonDeserialize ( $this->pldb [strtolower ( $name )] [$couponname] );
        $item2->setCount((int)$item1->getCount()+(int)$item2->getCount());
        $nbt = $item2->jsonSerialize ();
        $this->pldb [strtolower ( $name )] [$couponname] = $nbt;
        $this->save();
        return true;
      } else {
        $this->pldb [strtolower ( $name )] [$couponname] = $nbt;
        $this->save();
        return true;
      }
    }
  }
  public function getPlayerItem($player,$name,$itemCount,$itemName){
    $tag = "§l§b[ MailBox ] §r§7";
    $item = $player->getInventory()->getItemInHand();
    $item->setCount((int) $itemCount);
    $nbt = $item->jsonSerialize ();
    if ($this->getServer()->getPlayerExact($name) != null) {
      $this->getServer()->getPlayerExact($name)->sendMessage( $tag . "* 운영진의 선물이 메일함에 도착했어요. [ /메일함 ]");
      if (isset($this->pldb [strtolower ( $name )] [$itemName])) {
        $item = Item::jsonDeserialize ( $this->pldb [strtolower ( $name )] [$itemName] );
        $item->setCount((int)$item->getCount()+(int)$itemCount);
        $nbt = $item->jsonSerialize ();
        $this->pldb [strtolower ( $name )] [$itemName] = $nbt;
        $this->save();
        return true;
      } else {
        $this->pldb [strtolower ( $name )] [$itemName] = $nbt;
        $this->save();
        return true;
      }
    } else {
      $player->sendMessage( $tag . "해당 플레이어는 접속중이지 않습니다." );
      return true;
    }
  }
  public function getExchange($player,$itemName,$nbt){
    $tag = "§l§b[ MailBox ] §r§7";
    $name = $player->getName ();
    $player->sendMessage( $tag . "* 구매된 아이템이 메일함에 도착했어요! [ /메일함 ]");
    if (isset($this->pldb [strtolower ( $name )] [$itemName])) {
      $Count = Item::jsonDeserialize ( $nbt );
      $itemCount = $Count->getCount ();
      $item = Item::jsonDeserialize ( $this->pldb [strtolower ( $name )] [$itemName] );
      $item->setCount((int)$item->getCount()+(int)$itemCount);
      $NbtItem = $item->jsonSerialize ();
      $this->pldb [strtolower ( $name )] [$itemName] = $NbtItem;
      $this->save();
      return true;
    } else {
      $this->pldb [strtolower ( $name )] [$itemName] = $nbt;
      $this->save();
      return true;
    }
  }
  public function getItem($player,$data,$itemName)
  {
    $tag = "§l§b[ MailBox ] §r§7";
    $name = $player->getName ();
    $item = $player->getInventory()->getItemInHand();
    $item->setCount((int)$data);
    $nbt = $item->jsonSerialize ();
    foreach(Server::getInstance()->getOnlinePlayers() as $players){
      $name = $players->getName ();
      $this->pldb [strtolower ( $name )] [$itemName] = $nbt;
      $this->save();
    }
    Server::getInstance()->broadcastMessage( $tag . "* 운영진의 선물이 메일함에 도착했어요. [ /메일함 ]");
  }
  
  public function HotTimeItemGiveEvent($nbt)
  {
    $tag = "§l§b[ MailBox ] §r§7";
    foreach(Server::getInstance()->getOnlinePlayers() as $players){
      $name = $players->getName ();
      $item = Item::jsonDeserialize ($nbt);
      if(!is_null($item->getCustomName ())){
        $this->pldb [strtolower ( $name )] [$item->getCustomName ()] = $nbt;
      } else {
        $this->pldb [strtolower ( $name )] [$item->getName ()] = $nbt;
      }
      $this->save();
    }
  }
  
  
  public function PlayerBoxEvent($player) {
    $tag = "§l§b[ MailBox ] §r§7";
    $name = $player->getName ();
    $playerPos = $player->getPosition();
    $inv = LifeInventoryLib::getInstance ()->create("DOUBLE_CHEST", new Position($playerPos->x, $playerPos->y - 2, $playerPos->z, $playerPos->getWorld()), '§l§b[ MailBox ] §r§7| 메일함',$player);
    $arr = [];
    $i = 0;
    $page = 0;
    foreach($this->pldb [strtolower($name)] as $MailBox => $v){
      if ( $i <= 49 ) {
        $this->listdb [strtolower($name)] [$page] [$i] = $MailBox;
        $this->save ();
      } else {
        ++$page;
        $pageData = (int)$page-1;
        $getpage = (int)$page*49;
        $iData = $page-$getpage;
        $this->listdb [strtolower($name)] [$page] [$iData] = $MailBox;
        $this->save ();
      }
      ++$i;
    }

    $playerpage = $this->pagedb [strtolower($name)] ["페이지"];
    if (isset($this->listdb [strtolower($name)] [$playerpage])) {
      foreach($this->listdb [strtolower($name)] [$playerpage] as $is => $v){
        $MailBox = $this->listdb [strtolower($name)] [$playerpage] [$is];
        if (isset($this->pldb [strtolower($name)] [$MailBox])) {
          $nbt = $this->pldb [strtolower($name)] [$MailBox];
          $item = Item::jsonDeserialize ($nbt);
          $item->setCustomName ($MailBox);
          $lore = [];
          $lore [] = "{$MailBox}\n인벤토리에 가져오면 메일을 받을 수 있습니다.";
          $item->setLore ($lore);
          $inv->setItem( $is , $item );
        }
      }
    }

    $inv->setItem( 51 , ItemFactory::getInstance()->get(368, 0, 1)->setCustomName("이전페이지")->setLore([ "해당 아이템을 인벤토리로 옴기면 이전페이지로 이동됩니다.\n인벤토리로 가져가보세요." ]) );
    $inv->setItem( 52 , ItemFactory::getInstance()->get(381, 0, 1)->setCustomName("다음페이지")->setLore([ "해당 아이템을 인벤토리로 옴기면 다음페이지로 이동됩니다.\n인벤토리로 가져가보세요." ]) );
    $inv->setItem( 53 , ItemFactory::getInstance()->get(426, 0, 1)->setCustomName("나가기")->setLore([ "메일함에서 나갑니다.\n인벤토리로 가져가보세요." ]) );

    LifeInventoryLib::getInstance ()->send($inv, $player);
  }
  public function onDisable():void
  {
    $this->save();
  }

  public function save():void
  {
    $this->player->setAll($this->pldb);
    $this->player->save();
    $this->mail->setAll($this->maildb);
    $this->mail->save();
    $this->list->setAll($this->listdb);
    $this->list->save();
    $this->page->setAll($this->pagedb);
    $this->page->save();
  }
}
