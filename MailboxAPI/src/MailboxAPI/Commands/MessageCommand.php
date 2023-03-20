<?php
declare(strict_types=1);

namespace MailboxAPI\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\player\Player;
use MailboxAPI\MailboxAPI;

class MessageCommand extends Command
{

  protected $plugin;

  public function __construct(MailboxAPI $plugin)
  {
    $this->plugin = $plugin;
    parent::__construct('편지', '편지 명령어.', '/편지');
  }

  public function execute(CommandSender $sender, string $commandLabel, array $args)
  {
    $encode = [
      'type' => 'form',
      'title' => '§l§b[편지]',
      'content' => '§r§7버튼을 눌러주세요.',
      'buttons' => [
        [
          'text' => "§r§7편지작성\n- 플레이어에게 편지를 작성합니다."
        ],
        [
          'text' => "§r§7편지확인\n- 나한테 온 편지를 확인합니다."
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 968;
    $packet->formData = json_encode($encode);
    $sender->getNetworkSession()->sendDataPacket($packet);
  }
}
