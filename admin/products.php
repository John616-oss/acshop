<?php
require_once __DIR__ . '/../includes/functions.php'; require_role(['admin']);
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf(); $action=$_POST['action']??'';
 if ($action==='save') {
  $id=(int)($_POST['id']??0); $code=trim($_POST['product_code']??''); $name=trim($_POST['name']??''); $cat=(int)($_POST['category_id']??0);
  $cost=(float)($_POST['cost_price']??0); $price=(float)($_POST['selling_price']??0); $stock=(int)($_POST['stock_quantity']??0); $reorder=(int)($_POST['reorder_level']??0);
  if($code===''||$name===''||$cost<0||$price<0||$stock<0||$reorder<0) $error='Check product fields and enter non-negative values.';
  else {
   try {
    if($id) { $s=$conn->prepare('UPDATE products SET product_code=?,name=?,category_id=NULLIF(?,0),cost_price=?,selling_price=?,reorder_level=? WHERE id=?'); $s->bind_param('ssiddii',$code,$name,$cat,$cost,$price,$reorder,$id); $s->execute(); log_activity($conn,'UPDATE','products',$id,'Product updated'); }
    else { $s=$conn->prepare('INSERT INTO products (product_code,name,category_id,cost_price,selling_price,stock_quantity,reorder_level) VALUES (?, ?, NULLIF(?,0), ?, ?, ?, ?)'); $s->bind_param('ssiddii',$code,$name,$cat,$cost,$price,$stock,$reorder); $s->execute(); $id=$conn->insert_id; log_activity($conn,'CREATE','products',$id,'Product created'); }
    flash('success','Product saved.'); redirect('admin/products.php');
   } catch(Throwable $ex) { $error='Could not save product. Product code may already exist.'; }
  }
 }
 if($action==='archive') { $id=(int)$_POST['id']; $s=$conn->prepare('UPDATE products SET is_active=0 WHERE id=?'); $s->bind_param('i',$id); $s->execute(); log_activity($conn,'ARCHIVE','products',$id,'Product archived'); flash('success','Product archived.'); redirect('admin/products.php'); }
 if($action==='activate') { $id=(int)$_POST['id']; $s=$conn->prepare('UPDATE products SET is_active=1 WHERE id=?'); $s->bind_param('i',$id); $s->execute(); log_activity($conn,'ACTIVATE','products',$id,'Product activated'); flash('success','Product activated.'); redirect('admin/products.php'); }
}
$edit=null;
if(isset($_GET['edit'])) { $id=(int)$_GET['edit']; $s=$conn->prepare('SELECT * FROM products WHERE id=?'); $s->bind_param('i',$id); $s->execute(); $edit=$s->get_result()->fetch_assoc(); }
$cats=$conn->query('SELECT id,name FROM categories ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$q=trim($_GET['q']??''); $like='%'.$q.'%'; $s=$conn->prepare('SELECT p.*,c.name category FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE (p.name LIKE ? OR p.product_code LIKE ?) ORDER BY p.created_at DESC'); $s->bind_param('ss',$like,$like); $s->execute(); $products=$s->get_result();
layout_header('Product management');
?>
<?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="grid two section-gap"><section class="card"><h2><?= $edit?'Edit product':'Add product' ?></h2><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>">
<div class="form-grid"><label>Product code<input name="product_code" required value="<?= e($edit['product_code']??'') ?>"></label><label>Product name<input name="name" required value="<?= e($edit['name']??'') ?>"></label>
<label>Category<select name="category_id"><option value="0">Uncategorized</option><?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>" <?= (int)($edit['category_id']??0)===$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></label>
<label>Cost price (₱)<input type="number" min="0" step=".01" name="cost_price" required value="<?= e((string)($edit['cost_price']??'0.00')) ?>"></label><label>Selling price (₱)<input type="number" min="0" step=".01" name="selling_price" required value="<?= e((string)($edit['selling_price']??'0.00')) ?>"></label><?php if (!$edit): ?><label>Opening stock<input type="number" min="0" name="stock_quantity" required value="0"></label><?php else: ?><p class="muted">Stock is managed through Inventory so each change is recorded.</p><?php endif; ?><label>Reorder level<input type="number" min="0" name="reorder_level" required value="<?= e((string)($edit['reorder_level']??5)) ?>"></label></div><button class="btn">Save product</button> <?php if($edit): ?><a class="btn btn-outline" href="<?= BASE_URL ?>/admin/products.php">Cancel</a><?php endif; ?></form></section>
<section class="card"><h2>Find products</h2><form method="get" class="search-form"><input name="q" placeholder="Search name or product code" value="<?= e($q) ?>"><button class="btn">Search</button></form><p class="muted">Use unique product codes for quick POS lookup. Archived products are retained for historical sales.</p></section></div>
<section class="card section-gap"><h2>Product list</h2><div class="table-wrap"><table><thead><tr><th>Code</th><th>Product</th><th>Category</th><th>Cost</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php while($p=$products->fetch_assoc()): ?><tr><td><?= e($p['product_code']) ?></td><td><?= e($p['name']) ?></td><td><?= e($p['category']??'—') ?></td><td><?= money((float)$p['cost_price']) ?></td><td><?= money((float)$p['selling_price']) ?></td><td><?= (int)$p['stock_quantity'] ?></td><td><?= $p['is_active']?'Active':'Archived' ?></td><td class="actions-cell"><a class="btn btn-small btn-action btn-edit" href="?edit=<?= $p['id'] ?>">Edit</a><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="action" value="<?= $p['is_active']?'archive':'activate' ?>"><button class="btn btn-small btn-action <?= $p['is_active']?'btn-reject':'btn-approve' ?>"><?= $p['is_active']?'Archive':'Activate' ?></button></form></td></tr><?php endwhile; ?>
</tbody></table></div></section>
<?php layout_footer(); ?>
