<?php
require_once __DIR__ . '/../includes/functions.php'; require_role(['admin','staff','cashier']);
if($_SERVER['REQUEST_METHOD']!=='POST'){redirect('cashier/pos.php');}verify_csrf();
$raw=$_POST['cart_json']??'';$cart=json_decode($raw,true);$payment=(float)($_POST['payment']??0);
if(!is_array($cart)||!count($cart)||count($cart)>100||$payment<0){flash('danger','Invalid cart or payment.');redirect('cashier/pos.php');}
$conn->begin_transaction();
try {
 $items=[];$total=0.0;
 foreach($cart as $row){$pid=(int)($row['id']??0);$qty=(int)($row['qty']??0);if($pid<1||$qty<1||$qty>10000)throw new Exception('Invalid product or quantity.');
  $s=$conn->prepare('SELECT id,product_code,name,selling_price,cost_price,stock_quantity FROM products WHERE id=? AND is_active=1 FOR UPDATE');$s->bind_param('i',$pid);$s->execute();$p=$s->get_result()->fetch_assoc();if(!$p)throw new Exception('A product is no longer available.');if((int)$p['stock_quantity']<$qty)throw new Exception('Insufficient stock for '.$p['name'].'.');
  $line=round((float)$p['selling_price']*$qty,2);$total=round($total+$line,2);$items[]=['product'=>$p,'qty'=>$qty,'line'=>$line];
 }
 if($payment+0.00001<$total)throw new Exception('Payment is less than the total.');
 $change=round($payment-$total,2);$uid=(int)current_user()['id'];$receipt='SSS-'.date('YmdHis').'-'.bin2hex(random_bytes(3));
 $s=$conn->prepare("INSERT INTO sales(receipt_no,cashier_id,total,payment_received,change_amount,status) VALUES(?,?,?,?,?,'completed')");$s->bind_param('siddd',$receipt,$uid,$total,$payment,$change);$s->execute();$saleId=$conn->insert_id;
 foreach($items as $it){$p=$it['product'];$pid=(int)$p['id'];$qty=(int)$it['qty'];$price=(float)$p['selling_price'];$cost=(float)$p['cost_price'];$line=(float)$it['line'];
  $s=$conn->prepare('INSERT INTO sale_items(sale_id,product_id,quantity,unit_price,unit_cost,line_total) VALUES(?,?,?,?,?,?)');$s->bind_param('iiiddd',$saleId,$pid,$qty,$price,$cost,$line);$s->execute();
  $s=$conn->prepare('UPDATE products SET stock_quantity=stock_quantity-? WHERE id=? AND stock_quantity>=?');$s->bind_param('iii',$qty,$pid,$qty);$s->execute();if($s->affected_rows!==1)throw new Exception('Stock changed. Please retry checkout.');
  $type='sale';$ref='sale';$note='Sale '.$receipt;$s=$conn->prepare('INSERT INTO stock_movements(product_id,user_id,movement_type,quantity_change,reference_type,reference_id,notes) VALUES(?,?,?,?,?,?,?)');$delta=-$qty;$s->bind_param('iisisis',$pid,$uid,$type,$delta,$ref,$saleId,$note);$s->execute();
 }
 log_activity($conn,'SALE','sales',$saleId,'Receipt '.$receipt.'; total '.$total);
 $conn->commit();redirect('cashier/receipt.php?id='.$saleId);
} catch(Throwable $ex){$conn->rollback();flash('danger',$ex->getMessage());redirect('cashier/pos.php');}
