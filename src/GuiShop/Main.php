<?php

namespace GuiShop;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\{Item, ItemBlock};
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat as TF;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\event\server\DataPacketReceiveEvent;
use GuiShop\Modals\elements\{Dropdown, Input, Button, Label, Slider, StepSlider, Toggle};
use GuiShop\Modals\network\{GuiDataPickItemPacket, ModalFormRequestPacket, ModalFormResponsePacket, ServerSettingsRequestPacket, ServerSettingsResponsePacket};
use GuiShop\Modals\windows\{CustomForm, ModalWindow, SimpleForm};
use pocketmine\command\{Command, CommandSender, ConsoleCommandSender, CommandExecutor};

use onebone\economyapi\EconomyAPI;

class Main extends PluginBase implements Listener {
  public $shop;
  public $item;

  //documentation for setting up the items
  /*
  "Item name" => [item_id, item_damage, buy_price, sell_price]
  */
public $Blocks = [
    "ICON" => ["Blocks",2,0],
    "Oak Wood" => [17,0,500,100],
    "Birch Wood" => [17,2,500,100],
    "Spruce Wood" => [17,1,500,100],
    "Dark Oak Wood" => [162,1,500,100],
	"Cobblestone" => [4,0,700,200],
	"Obsidian" => [49,0,10000,4500],
	"Bedrock" => [7,0,500000,10000],
	"Sand " => [12,0,200,75],
    "Sandstone " => [24,0,400,275],
	"Nether Rack" => [87,0,100,50],
    "Glass" => [20,0,50,25],
    "Glowstone" => [89,0,1000,200],
    "Sea Lantern" => [169,0,1000,200],
	"Grass" => [2,0,100,50],
	"Dirt" => [3,0,100, 50],
    "Stone" => [1,0,1500,100]
  ];

  public $Ores = [
    "ICON" => ["Ores",266,0],
    "Coal" => [263,0,3500,150],
    "Iron Ingot" => [265,0,4000,2500],
    "Gold Ingot" => [266,0,2750,1000],
    "Diamond" => [264,0,5000,3750]
  ];

  public $Tools = [
    "ICON" => ["Tools",278,0],
    "Diamond Pickaxe" => [278,0,5000,250],
    "Diamond Shovel" => [277,0,5000,250],
    "Diamond Axe" => [279,0,5000,250],
    "Diamond Hoe" => [293,0,5000,250],
    "Diamond Sword" => [276,0,7500,375],
    "Bow" => [261,0,400,200],
    "Arrow" => [262,0,25,5]
  ];

  public $Armor = [
    "ICON" => ["Armor",311,0],
    "Diamond Helmet" => [310,0,3500,500],
    "Diamond Chestplate" => [311,0,7500,1250],
    "Diamond Leggings" => [312,0,3500,750],
    "Diamond Boots" => [313,0,3500,500]
  ];

  public $Farming = [
    "ICON" => ["Farming",293,0],
    "Pumpkin" => [86,0,1000,800],
    "Melon" => [360,13,5000,4500],
    "Carrot" => [391,0,1500,600],
    "Potato" => [392,0,800,575],
    "Sugarcane" => [338,0,300,150],
    "Wheat" => [296,6,100,65],
    "Pumpkin Seed" => [361,0,20,10],
    "Melon Seed" => [362,0,2500,1250],
    "Seed" => [295,0,20,10]
  ];

  public $Food = [
    "ICON" => ["Food",364,0],
	"Cooked Chicken" => [366,0,10,5],
    "Steak" => [364,0,1000,250],
    "Golden Apple" => [322,0,25000,10000],
    "Enchanted Golden Apple" => [466,0,50000,10000]
  ];

  public $Miscellaneous = [
    "ICON" => ["Miscellaneous",368,0],
	"PVP Elixir" => [373,101,35000,1000],
	"Raiding Elixir" => [373,100,10000,1000],
	"Furnace" => [61,0,20,10],
    "Crafting Table" => [58,0,20,10],
	"Ender Chest " => [130,0,100000,50000],
    "Enderpearl" => [368,0,25000,12500],
    "Bone" => [352,0,10000,7500],
    "Book & Quill" => [386,0,100,0]
  ];

  public $Raiding = [
    "ICON" => ["Raiding",46,0],
    "Flint & Steel" => [259,0,100,50],
    "Torch" => [50,0,500,100],
	"Packed Ice " => [174,0,500,250],
    "Water" => [9,0,500000,250],
    "Redstone" => [331,0,2500,75],
    "Chest" => [54,0,100,50]
  ];

  public function onEnable(){
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
    PacketPool::registerPacket(new GuiDataPickItemPacket());
		PacketPool::registerPacket(new ModalFormRequestPacket());
		PacketPool::registerPacket(new ModalFormResponsePacket());
		PacketPool::registerPacket(new ServerSettingsRequestPacket());
		PacketPool::registerPacket(new ServerSettingsResponsePacket());
    $this->item = [$this->Raiding, $this->Farming, $this->Armor, $this->Tools, $this->Food, $this->Ores, $this->Blocks, $this->Miscellaneous];
  }

  public function sendMainShop(Player $player){
    $ui = new SimpleForm("§bRegulated§dFactions PE","       §7Purchase and Sell items Here!");
    foreach($this->item as $category){
      if(isset($category["ICON"])){
        $rawitemdata = $category["ICON"];
        $button = new Button($rawitemdata[0]);
        $button->addImage('url', "http://avengetech.me/items/".$rawitemdata[1]."-".$rawitemdata[2].".png");
        $ui->addButton($button);
      }
    }
    $pk = new ModalFormRequestPacket();
    $pk->formId = 110;
    $pk->formData = json_encode($ui);
    $player->dataPacket($pk);
    return true;
  }

  public function sendShop(Player $player, $id){
    $ui = new SimpleForm("§bShop","       §7Purchase and Sell items Here!");
    $ids = -1;
    foreach($this->item as $category){
      $ids++;
      $rawitemdata = $category["ICON"];
      if($ids == $id){
        $name = $rawitemdata[0];
        $data = $this->$name;
        foreach($data as $name => $item){
          if($name != "ICON"){
            $button = new Button($name);
            $button->addImage('url', "http://avengetech.me/items/".$item[0]."-".$item[1].".png");
            $ui->addButton($button);
          }
        }
      }
    }
    $pk = new ModalFormRequestPacket();
    $pk->formId = 111;
    $pk->formData = json_encode($ui);
    $player->dataPacket($pk);
    return true;
  }

  public function sendConfirm(Player $player, $id){
    $ids = -1;
    $idi = -1;
    foreach($this->item as $category){
      $ids++;
      $rawitemdata = $category["ICON"];
      if($ids == $this->shop[$player->getName()]){
        $name = $rawitemdata[0];
        $data = $this->$name;
        foreach($data as $name => $item){
          if($name != "ICON"){
            if($idi == $id){
              $this->item[$player->getName()] = $id;
              $iname = $name;
              $cost = $item[2];
              $sell = $item[3];
              break;
            }
          }
          $idi++;
        }
      }
    }

    $ui = new CustomForm($iname);
    $slider = new Slider("Amount ",1,64,1);
    $toggle = new Toggle("Selling");
    if($sell == 0) $sell = "0";
    $label = new Label(TF::GREEN."Buy: $".TF::GREEN.$cost.TF::RED."\nSell: $".TF::RED.$sell);
    $ui->addElement($label);
    $ui->addElement($toggle);
    $ui->addElement($slider);
    $pk = new ModalFormRequestPacket();
    $pk->formId = 112;
    $pk->formData = json_encode($ui);
    $player->dataPacket($pk);
    return true;
  }

  public function sell(Player $player, $data, $amount){
    $ids = -1;
    $idi = -1;
    foreach($this->item as $category){
      $ids++;
      $rawitemdata = $category["ICON"];
      if($ids == $this->shop[$player->getName()]){
        $name = $rawitemdata[0];
        $data = $this->$name;
        foreach($data as $name => $item){
          if($name != "ICON"){
            if($idi == $this->item[$player->getName()]){
              $iname = $name;
              $id = $item[0];
              $damage = $item[1];
              $cost = $item[2]*$amount;
              $sell = $item[3]*$amount;
              if($sell == 0){
                $player->sendMessage(TF::BOLD . TF::DARK_GRAY . "(" . TF::RED . "!" . TF::DARK_GRAY . ") " . TF::RESET . TF::GRAY . "This is not sellable!");
                return true;
              }
              if($player->getInventory()->contains(Item::get($id,$damage,$amount))){
                $player->getInventory()->removeItem(Item::get($id,$damage,$amount));
                EconomyAPI::getInstance()->addMoney($player, $sell);
                $player->sendMessage(TF::BOLD . TF::DARK_GRAY . "(" . TF::GREEN . "!" . TF::DARK_GRAY . ") " . TF::RESET . TF::GRAY . "You have sold $amount $iname for $$sell");
              }else{
                $player->sendMessage(TF::BOLD . TF::DARK_GRAY . "(" . TF::RED . "!" . TF::DARK_GRAY . ") " . TF::RESET . TF::GRAY . "You do not have $amount $iname!");
              }
              unset($this->item[$player->getName()]);
              unset($this->shop[$player->getName()]);
              return true;
            }
          }
          $idi++;
        }
      }
    }
    return true;
  }

  public function purchase(Player $player, $data, $amount){
    $ids = -1;
    $idi = -1;
    foreach($this->item as $category){
      $ids++;
      $rawitemdata = $category["ICON"];
      if($ids == $this->shop[$player->getName()]){
        $name = $rawitemdata[0];
        $data = $this->$name;
        foreach($data as $name => $item){
          if($name != "ICON"){
            if($idi == $this->item[$player->getName()]){
              $iname = $name;
              $id = $item[0];
              $damage = $item[1];
              $cost = $item[2]*$amount;
              $sell = $item[3]*$amount;
              if(EconomyAPI::getInstance()->myMoney($player) > $cost){
                $player->getInventory()->addItem(Item::get($id,$damage,$amount));
                EconomyAPI::getInstance()->reduceMoney($player, $cost);
                $player->sendMessage(TF::BOLD . TF::DARK_GRAY . "(" . TF::GREEN . "!" . TF::DARK_GRAY . ") " . TF::RESET . TF::GRAY . "You purchased $amount $iname for $$cost");
              }else{
                $player->sendMessage(TF::BOLD . TF::DARK_GRAY . "(" . TF::RED . "!" . TF::DARK_GRAY . ") " . TF::RESET . TF::GRAY . "You do not have enough money to buy $amount $iname");
              }
              unset($this->item[$player->getName()]);
              unset($this->shop[$player->getName()]);
              return true;
            }
          }
          $idi++;
        }
      }
    }
    return true;
  }

  public function DataPacketReceiveEvent(DataPacketReceiveEvent $event){
    $packet = $event->getPacket();
    $player = $event->getPlayer();
    if($packet instanceof ModalFormResponsePacket){
      $id = $packet->formId;
      $data = $packet->formData;
      $data = json_decode($data);
      if($data === Null) return true;
      if($id === 110){
        $this->shop[$player->getName()] = $data;
        $this->sendShop($player, $data);
        return true;
      }
      if($id === 111){
        //$this->shop[$player->getName()] = $data;
        $this->sendConfirm($player, $data);
        return true;
      }
      if($id === 112){
        $selling = $data[1];
        $amount = $data[2];
        if($selling){
          $this->sell($player, $data, $amount);
          return true;
        }
        $this->purchase($player, $data, $amount);
        return true;
      }
    }
    return true;
  }

  public function onCommand(CommandSender $player, Command $command, string $label, array $args) : bool{
    switch(strtolower($command)){
      case "shop":
        $this->sendMainShop($player);
        return true;
    }
  }

}
