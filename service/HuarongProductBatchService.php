<?php
declare(strict_types=1);
require_once __DIR__.'/HuarongTighteningReportService.php';
/** Pure product boundary logic. Count physical screws, never count result rows. */
final class HuarongProductBatchService
{
    private $policy;
    public function __construct(?array $policy=null) {
        $this->policy=$policy ?? require dirname(__DIR__).'/app/config/mes_product_batch.php';
    }
    public function assemble(array $rows,array $context,bool $build=true): array {
        $screws=$context['screw_inf'] ?? [];$required=0;
        if (!is_array($screws) || !$screws) throw new RuntimeException('工單缺少 screw_inf');
        foreach ($screws as $screw) {
            $qty=filter_var($screw['screw_quantity'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            if ($qty===false) throw new RuntimeException('工單螺絲數量無效');
            $required+=$qty;
        }
        if ($required>10000) throw new RuntimeException('產品螺絲數量超過處理上限');
        $field=$this->policy['counter_field'] ?? 'last_screw_count';
        if (!in_array($field,['last_screw_count','total_screw_count'],true)) throw new RuntimeException('產品計數欄位設定錯誤');
        $direction=$this->policy['counter_direction'] ?? 'up';
        if (!in_array($direction,['up','down'],true)) throw new RuntimeException('產品計數方向設定錯誤');
        $collected=[];$latest=[];$previous=null;$lastId=0;$complete=false;$identity=[];
        foreach ($rows as $row) {
            $id=(int)($row['id'] ?? 0);
            if ($id<1 || $id<=$lastId) throw new RuntimeException('鎖附紀錄順序或 ID 無效');
            $lastId=$id;
            if (trim((string)($row['barcode'] ?? ''))!==trim((string)($context['SN'] ?? ''))) throw new RuntimeException('產品累積區間混入不同條碼，已停止封包');
            if (!empty($context['job_name']) && trim((string)($row['job_name'] ?? ''))!==trim((string)$context['job_name'])) throw new RuntimeException('產品累積區間混入不同工作，已停止封包');
            foreach (['device_sn','tool_sn','count_type','torque_unit'] as $key) {
                if (!isset($row[$key]) || (string)$row[$key]==='') continue;
                if (isset($identity[$key]) && $identity[$key]!== (string)$row[$key]) throw new RuntimeException('產品區間設備、單位或計數方式變更：'.$key);
                $identity[$key]=(string)$row[$key];
            }
            $status=(int)($row['fasten_status'] ?? -1);
            // INIT/READY/RUNNING/SETTING are not completed tightening actions.
            if (!in_array($status,[3,4,5,6,7,8],true)) continue;
            $value=filter_var($row[$field] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>$required]]);
            if ($value===false) throw new RuntimeException('鎖附紀錄缺少有效產品計數：'.$field);
            $count=$direction==='down'?$required-$value:$value;
            if ($previous===null && $count>1) throw new RuntimeException('產品起始紀錄不完整；不能從中途計數組成整包');
            if ($previous!==null && ($count<$previous || $count>$previous+1)) throw new RuntimeException('產品計數重設或跳號，無法安全判斷產品分界');
            $previous=$count;$collected[]=$row;
            if ($status!==3 && $count>0) $latest[$count]=$row;
            $complete=$count===$required && $status!==3;
            if ($complete) break;
        }
        $result=['complete'=>$complete,'required_screws'=>$required,'completed_screws'=>$previous ?? 0,
            'result_count'=>count($collected),'terminal_id'=>$complete?$lastId:null,'payload'=>null];
        if (!$complete || !$build) return $result;
        if (empty($this->policy['counter_verified'])) throw new RuntimeException('產品計數規則尚未經實機核對；請核對 mes_product_batch.php 後啟用，暫不送 MES');
        if (count($latest)!==$required) throw new RuntimeException('產品計數缺少螺絲位置，無法產生完整報文');
        $mapper=new HuarongTighteningReportService();$base=$context;$base['screw_inf']=[$screws[0]];
        foreach ($collected as &$row) $row['peak_fasten_torque']=$row['final_fasten_torque'] ?? 0;
        unset($row);
        // Reuse the established runParam mapping; completion is determined above.
        $payload=$mapper->buildPayload($collected,$base,true);
        $payload['complete_staus']=1;
        foreach ($latest as $row) if (in_array((int)$row['fasten_status'],[7,8],true)) $payload['complete_staus']=2;
        $installed=array_fill(0,count($screws),0);
        $ranges=$this->policy['material_ranges'] ?? [];
        if (count($screws)>1) {
            $covered=[];
            foreach ($screws as $index=>$screw) {
                $range=$ranges[(string)$screw['screw_item']] ?? null;
                if (!is_array($range) || count($range)!==2 || !is_int($range[0]) || !is_int($range[1]) || $range[0]<1 || $range[1]>$required || $range[1]-$range[0]+1!==(int)$screw['screw_quantity']) throw new RuntimeException('多種螺絲須先設定各料號對應的產品計數範圍 material_ranges');
                for ($n=$range[0];$n<=$range[1];$n++) {if(isset($covered[$n]))throw new RuntimeException('螺絲料號計數範圍重疊');$covered[$n]=$index;}
            }
            if (count($covered)!==$required) throw new RuntimeException('螺絲料號計數範圍未涵蓋整個產品');
        }
        foreach ($latest as $count=>$row) if (in_array((int)$row['fasten_status'],[4,5,6],true)) $installed[count($screws)===1?0:$covered[$count]]++;
        $payload['screw_inf']=[];
        foreach ($screws as $index=>$screw) $payload['screw_inf'][]=['screw_item'=>(string)$screw['screw_item'],'screw_quantity'=>(int)$screw['screw_quantity'],'screw_installed'=>$installed[$index]];
        $result['payload']=$payload;return $result;
    }
}
