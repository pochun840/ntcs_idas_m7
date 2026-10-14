<?php
declare(strict_types=1);
require_once __DIR__ . '/JobConfigControllerSettingsService.php';
require_once dirname(__DIR__) . '/modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';
final class BarcodeModbusReadService
{
    public function read(): array
    {
        $settings=JobConfigControllerSettingsService::read();
        $client=new ModbusMaster(CONTROLLER_IP,'TCP');
        $client->port=$settings['port'];$client->timeout_sec=2;$client->deadline=microtime(true)+2.0;
        $raw=$client->readMultipleRegisters($settings['unit_id'],396,50);
        if(is_string($raw)) {
            if(strlen($raw)!==100) throw new RuntimeException('Incomplete barcode registers');
            $words=array_values(unpack('n*',$raw));
        } elseif(is_array($raw) && count($raw)===100) {
            $bytes=array_values($raw);$words=[];
            for($i=0;$i<100;$i+=2) $words[]=((int)$bytes[$i]<<8)|(int)$bytes[$i+1];
        } elseif(is_array($raw) && count($raw)===50) $words=array_values($raw);
        else throw new RuntimeException('Incomplete barcode registers');
        $barcode=self::decodeRegisters($words);
        return ['success'=>true,'barcode'=>$barcode,'read_at'=>date('c'),'unit_id'=>$settings['unit_id'],'address'=>396,'quantity'=>50];
    }

    /** Packed ASCII: high byte then low byte; also accept legacy one-byte words. */
    public static function decodeRegisters(array $words): string
    {
        $packed=false;
        // Inspect only the field up to its terminator.
        foreach($words as $word){$word=(int)$word;if($word===0)break;if($word>255){$packed=true;break;}}
        $barcode='';
        foreach($words as $word){
            $word=(int)$word;
            if($word<0||$word>65535)throw new RuntimeException('Invalid barcode register value');
            $bytes=$packed?[($word>>8)&255,$word&255]:[$word];
            foreach($bytes as $byte){
                if($byte===0)return $barcode;
                if($byte<32||$byte>126)throw new RuntimeException('Invalid barcode register character');
                $barcode.=chr($byte);
            }
        }
        return $barcode;
    }
}
