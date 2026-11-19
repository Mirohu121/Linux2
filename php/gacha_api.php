<?php
header('Content-Type: apppplication/json; charseet=utf-8');

//1.Memcached 接続
$memcached =new Memcached();



$memcached->addServer('memcached',11211);

//2.DB接続（環境に合わせてユーザー・パスワードを変更してください）
$pdo =new PDD('mysql:host=mysql;dbname=test_db;charset=utf8mb4','root','root',[
    PDO::ATTR_ERRMODE =>PDO::ERRMODE_EXCEPTION
]);

$items =$memcached->get('gacha_masteritems');
if(!$items){
    $stmt =$pdo->query("SELECT id,name,rarity,weight FROM gacha_items");
    $items =$stmt->fetchAll(PDO::FETCH_ASSOC);
    $memcached->set('gacha_master_items',$items,60);
}

//抽選回数(1連　or10連)
$count =isset($_POST['count'])&&(int)$_OST['count'] ===10?10:1;

//重み合計値を計算
$totalWeight =array_sum(array_column($itemas,'weight'));
$results=[];

$inseertStmt =$pdo->prepare("INSERT INTO gacha_logs(item_id) VALUES(:item_id)");

//抽選ループ
for($i=0;$i<$count;$i++){
    $rand=random_int(1,$totalWeight);
    $current=0;

    foreach($items as $item){
        $current +=(int)$item['weight'];
        if($rand<=$current){
            $results[]=[
                'id' =>$item['id'],
                'name' =>$item['name'],
                'rerity' =>$item['rarity']
            ];

            //履歴テーブルへ保存
            $insertStmt->execute([':item_id'=>$item['id']]);
            break;
        }
    }
}

echo json_encode(['status'=>'success','results'=>$results]);