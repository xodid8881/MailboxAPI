<?php
declare(strict_types=1);

namespace MailboxAPI;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\player\Player;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;

use LifeInventoryLib\InventoryLib\InvLibManager;
use LifeInventoryLib\InventoryLib\LibInvType;
use LifeInventoryLib\InventoryLib\InvLibAction;
use LifeInventoryLib\InventoryLib\SimpleInventory;
use LifeInventoryLib\InventoryLib\LibInventory;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\inventory\ContainerInventory;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\permission\DefaultPermissions;

class EventListener implements Listener
{
  
  protected $plugin;
  
  public function __construct(MailboxAPI $plugin)
  {
    $this->plugin = $plugin;
  }
  public function OnJoin(PlayerJoinEvent $event)
  {
    $player = $event->getPlayer();
    $name = $player->getName();
    if (!isset ($this->plugin->pldb [strtolower($name)])) {
      $this->plugin->pldb [strtolower ( $name )] = [];
      $this->plugin->save();
    }
    if (!isset ($this->plugin->maildb [strtolower($name)])) {
      $this->plugin->maildb [strtolower ( $name )] ["편지플레이어"] = "";
      $this->plugin->maildb [strtolower ( $name )] ["편지내용"] = "";
      $this->plugin->maildb [strtolower ( $name )] ["편지"] = [];
      $this->plugin->save();
    }
    if (!isset ($this->plugin->pagedb [strtolower($name)])) {
      $this->plugin->pagedb [strtolower ( $name )] ["페이지"] = 0;
      $this->plugin->save();
    }
  }
  public function onTransaction(InventoryTransactionEvent $event) {
    $tag = "§l§b[ MailBox ] §r§7";
    $transaction = $event->getTransaction();
    $player = $transaction->getSource ();
    $name = $player->getName ();
    foreach($transaction->getActions() as $action){
      if($action instanceof SlotChangeAction){
        $inv = $action->getInventory();
        if ($inv instanceof LibInventory) {
          if ($inv->getTitle() == '§l§b[ MailBox ] §r§7| 메일함'){
            $event->cancel ();
            $slot = $action->getSlot ();
            $item = $inv->getItem ($slot);
            $id = $item->getId ();
            $damage = $item->getMeta ();
            $itemname = $item->getCustomName ();
            $nbt = $item->jsonSerialize ();
            if (isset($this->plugin->pldb [strtolower($name)] [$itemname])) {
              $nbt = $this->plugin->pldb [strtolower($name)] [$itemname];
              $item = Item::jsonDeserialize ($nbt);
              $player->getInventory()->addItem($item);
              unset($this->plugin->pldb [strtolower($name)] [$itemname]);
              $this->plugin->save ();
              $inv->onClose($player);
              $player->sendMessage($tag . "메일함에서 {$itemname} 의 물품을 수거했습니다.");
              return true;
            }
            if ( $itemname == "이전페이지" ) {
              $inv->onClose($player);
              $this->plugin->pagedb [strtolower($name)] ["페이지"] -= 1;
              $this->plugin->save ();
              $this->plugin->PlayerBoxEvent ($player);
              return true;
            }
            if ( $itemname == "다음페이지" ) {
              $inv->onClose($player);
              $this->plugin->pagedb [strtolower($name)] ["페이지"] += 1;
              $this->plugin->save ();
              $this->plugin->PlayerBoxEvent ($player);
              return true;
            }
            if ( $itemname == "나가기" ) {
              $inv->onClose($player);
              return true;
            }
          }
        }
      }
    }
  }
  public function onPacket(DataPacketReceiveEvent $event)
  {
    $packet = $event->getPacket();
    $tag = "§l§b[ Mailbox ] §r§7";
    $player = $event->getOrigin()->getPlayer();
    if($packet instanceof ModalFormResponsePacket) {
      $name = $player->getName();
      $id = $packet->formId;
      if($packet->formData == null) {
        return true;
      }
      $data = json_decode($packet->formData, true);
      if ($id === 1752962) {
        if ($data === 0) {
          if ($player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $this->RandomGift($player);
            return true;
          } else {
            $player->sendMessage($tag . "권한이 없습니다.");
            return true;
          }
        }
        if ($data === 1) {
          if ($player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $this->OneGift($player);
            return true;
            
          } else {
            $player->sendMessage($tag . "권한이 없습니다.");
            return true;
          }
        }
        if ($data === 2) {
          if ($player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $this->AllGift($player);
            return true;
            
          } else {
            $player->sendMessage($tag . "권한이 없습니다.");
            return true;
          }
        }
      }
      if ($id === 1752965) {
        if(!isset($data[0])){
          $player->sendMessage($tag . "갯수를 적어주세요.");
          return true;
        }
        if(!is_numeric($data[0])){
          $player->sendMessage($tag . "갯수는 숫자로만 가능합니다.");
          return true;
        }
        if(!isset($data[1])){
          $player->sendMessage($tag . "선물 이름을 정확하게 적어주세요.");
          return true;
        }
        if(is_numeric($data[1])){
          $player->sendMessage($tag . "선물 이름은 숫자가 불가능합니다.");
          return true;
        }
        $this->plugin->RandomItem($player,$data[0],$data[1]);
        return true;
      }
      if ($id === 1752966) {
        if(!isset($data[0])){
          $player->sendMessage($tag . "플레이어 닉네임을 적어주세요.");
          return true;
        }
        if(!isset($data[1])){
          $player->sendMessage($tag . "갯수를 적어주세요.");
          return true;
        }
        if(!is_numeric($data[1])){
          $player->sendMessage($tag . "갯수는 숫자로만 가능합니다.");
          return true;
        }
        if(!isset($data[2])){
          $player->sendMessage($tag . "선물 이름을 정확하게 적어주세요.");
          return true;
        }
        if(is_numeric($data[2])){
          $player->sendMessage($tag . "선물 이름은 숫자가 불가능합니다.");
          return true;
        }
        $this->plugin->getPlayerItem($player,$data[0],$data[1],$data[2]);
      }
      if ($id === 1752967) {
        if(!isset($data[0])){
          $player->sendMessage($tag . "갯수를 적어주세요.");
          return true;
        }
        if(!is_numeric($data[0])){
          $player->sendMessage($tag . "갯수는 숫자로만 가능합니다.");
          return true;
        }
        if(!isset($data[1])){
          $player->sendMessage($tag . "선물 이름을 정확하게 적어주세요.");
          return true;
        }
        if(is_numeric($data[1])){
          $player->sendMessage($tag . "선물 이름은 숫자가 불가능합니다.");
          return true;
        }
        $this->plugin->getItem($player,$data[0],$data[1]);
        return true;
      }
      if ($id === 968) {
        if ($data === 0) {
          $this->NewMail($player);
          return true;
        }
        if ($data === 1) {
          $this->MyMail($player);
          return true;
        }
      }
      if ($id === 969) {
        if(!isset($data[0])){
          $player->sendMessage($tag . "플레이어 이름을 적어주세요.");
          return true;
        }
        if(!isset($data[1])){
          $player->sendMessage($tag . "편지 내용을 적어주세요.");
          return true;
        }
        if(!isset($this->plugin->pldb [strtolower ( $data[0] )])){
          $player->sendMessage($tag . "해당 플레이어는 없는 플레이어 입니다.");
          return true;
        }
        $player->sendMessage($tag . "해당 플레이어에게 편지를 작성했습니다.");
        $this->plugin->MailMsg ($player, $data[0]);
        $this->plugin->maildb [strtolower ( $data[0] )] ["편지"] [$data[1]] = $name;
        $this->plugin->save ();
        return true;
      }
      if($id === 970){
        if($data !== null){
          $arr = [];
          foreach($this->plugin->getMailLists($player) as $Name){
            array_push($arr, $Name);
          }
          foreach($this->plugin->maildb [strtolower ( $name )] ["편지"] as $msg => $test){
            $playername = $this->plugin->maildb [strtolower ( $name )] ["편지"] [$msg];
            if ($playername == $arr[$data]){
              $this->plugin->maildb [strtolower ( $name )] ["편지플레이어"] = $arr[$data];
              $this->plugin->maildb [strtolower ( $name )] ["편지내용"] = $msg;
              $this->plugin->save ();
              $this->Mail ($player, $arr[$data], $msg);
              return true;
            }
          }
        }
      }
      if ($id === 971) {
        if ($data === 0) {
          $playerName = $this->plugin->maildb [strtolower ( $name )] ["편지플레이어"];
          $msg = $this->plugin->maildb [strtolower ( $name )] ["편지내용"];
          if ($this->plugin->maildb [strtolower ( $name )] ["편지"] [$msg]) {
            $player->sendMessage($tag . "해당 편지를 읽고 지웠습니다.");
            unset ($this->plugin->maildb [strtolower ( $name )] ["편지"] [$msg]);
            $this->plugin->save ();
            return true;
          }
        }
        if ($data === 1) {
          $player->sendMessage($tag . "편지함에서 나왔습니다.");
          return true;
        }
      }
    }
  }
  public function RandomGift(Player $player)
  {
    $encode = [
      'type' => 'custom_form',
      'title' => '§l§b[ Mailbox ]',
      'content' => [
        [
          'type' => 'input',
          'text' => "§r§7- 손에 들고있는 아이템 갯수를 설정합니다."
        ],
        [
          'type' => 'input',
          'text' => "§r§7- 선물 이름을 설정합니다.\n§r§7- 손에 든 아이템을 랜덤으로 플레이어에게 지급합니다."
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 1752965;
    $packet->formData = json_encode($encode);
    $player->getNetworkSession()->sendDataPacket($packet);
    return true;
  }
  public function OneGift(Player $player)
  {
    $encode = [
      'type' => 'custom_form',
      'title' => '§l§b[ Mailbox ]',
      'content' => [
        [
          'type' => 'input',
          'text' => "§r§7- 플레이어 이름을 적어주세요."
        ],
        [
          'type' => 'input',
          'text' => "§r§7- 손에 들고있는 아이템 갯수를 설정합니다."
        ],
        [
          'type' => 'input',
          'text' => "§r§7- 선물 이름을 설정합니다.\n§r§7- 손에 든 아이템을 일정 플레이어에게 지급합니다."
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 1752966;
    $packet->formData = json_encode($encode);
    $player->getNetworkSession()->sendDataPacket($packet);
    return true;
  }
  public function AllGift(Player $player)
  {
    $encode = [
      'type' => 'custom_form',
      'title' => '§l§b[ Mailbox ]',
      'content' => [
        [
          'type' => 'input',
          'text' => "§r§7- 손에 들고있는 아이템 갯수를 설정합니다."
        ],
        [
          'type' => 'input',
          'text' => "§r§7- 선물 이름을 설정합니다.\n§r§7- 손에 든 아이템을 모든 플레이어에게 지급합니다."
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 1752967;
    $packet->formData = json_encode($encode);
    $player->getNetworkSession()->sendDataPacket($packet);
    return true;
  }
  public function NewMail(Player $player)
  {
    $encode = [
      'type' => 'custom_form',
      'title' => '§l§b[ 편지작성 ]',
      'content' => [
        [
          'type' => 'input',
          'text' => "§r§7- 편지를 보낼 플레이어를 선택해주세요."
        ],
        [
          'type' => 'input',
          'text' => "§r§7- 편지에 작성할 메세지를 적어주세요."
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 969;
    $packet->formData = json_encode($encode);
    $player->getNetworkSession()->sendDataPacket($packet);
    return true;
  }
  
  public function MyMail(Player $player): bool
  {
    $arr = [];
    foreach($this->plugin->getMailLists($player) as $Name){
      array_push($arr, array('text' => $Name."님이 편지를 보냈습니다."));
    }
    $encode = [
      'type' => 'form',
      'title' => '【 편지함 】',
      'content' => "확인할 편지를 터치하세요.",
      'buttons' => $arr
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 970;
    $packet->formData = json_encode($encode);
    $player->getNetworkSession()->sendDataPacket($packet);
    return true;
  }
  public function Mail(Player $player, $MailName, $msg): bool
  {
    $encode = [
      'type' => 'form',
      'title' => '§l§b[ 편지함 ]',
      'content' => "§r§7{$MailName} 님의 편지 입니다.\n\n내용 : {$msg}\n\n편지를 확인했다면 버튼을 눌러주세요.",
      'buttons' => [
        [
          'text' => "§r§7편지 지우기\n- 편지를 지웁니다."
        ],
        [
          'text' => "§r§7편지 남겨두기\n- 편지를 남겨둡니다."
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 971;
    $packet->formData = json_encode($encode);
    $player->getNetworkSession()->sendDataPacket($packet);
    return true;
  }
}
