document.addEventListener('DOMContentLoaded', () => {
  const navToggle = document.querySelector('.nav-toggle');
  const navigation = document.getElementById('primary-navigation');
  if (navToggle && navigation) {
    navToggle.addEventListener('click', () => {
      const isOpen = navToggle.getAttribute('aria-expanded') === 'true';
      navToggle.setAttribute('aria-expanded', String(!isOpen));
      navigation.classList.toggle('is-open', !isOpen);
    });
    navigation.addEventListener('click', event => {
      if (event.target.closest('a')) {
        navToggle.setAttribute('aria-expanded', 'false');
        navigation.classList.remove('is-open');
      }
    });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && navToggle.getAttribute('aria-expanded') === 'true') {
        navToggle.setAttribute('aria-expanded', 'false');
        navigation.classList.remove('is-open');
        navToggle.focus();
      }
    });
  }

  const cart = new Map();
  const rows = document.getElementById('cartRows');
  const totalEl = document.getElementById('cartTotal');
  const payment = document.getElementById('payment');
  const changeEl = document.getElementById('changeAmount');
  const cartJson = document.getElementById('cartJson');
  const complete = document.getElementById('completeSale');
  const peso = n => '₱' + Number(n).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
  function renderCart() {
    if (!rows) return;
    let total = 0;
    if (!cart.size) rows.innerHTML = '<p class="muted">No items yet. Add products from the list.</p>';
    else {
      rows.innerHTML = '';
      cart.forEach((item, id) => {
        const line = item.price * item.qty; total += line;
        const div = document.createElement('div'); div.className='cart-row';
        const name = document.createElement('div'); name.innerHTML = '<b></b><small></small>'; name.querySelector('b').textContent=item.name; name.querySelector('small').textContent=peso(item.price)+' each · max '+item.stock;
        const qty = document.createElement('input'); qty.type='number'; qty.min='1'; qty.max=String(item.stock); qty.value=String(item.qty); qty.setAttribute('aria-label','Quantity for '+item.name);
        qty.addEventListener('change',()=>{let n=parseInt(qty.value,10);if(!Number.isFinite(n)||n<1)n=1;if(n>item.stock)n=item.stock;item.qty=n;renderCart();});
        const amount = document.createElement('strong'); amount.textContent=peso(line);
        const remove = document.createElement('button'); remove.type='button'; remove.className='link-button danger-link'; remove.textContent='Remove'; remove.addEventListener('click',()=>{cart.delete(id);renderCart();});
        div.append(name,qty,amount,remove); rows.appendChild(div);
      });
    }
    totalEl.textContent=peso(total);
    const paid=Number(payment?.value||0);changeEl.textContent=peso(Math.max(0,paid-total));
    cartJson.value=JSON.stringify([...cart.values()].map(i=>({id:i.id,qty:i.qty})));
    complete.disabled=!cart.size||paid+0.00001<total;
  }
  document.querySelectorAll('.add-to-cart').forEach(btn=>btn.addEventListener('click',()=>{
    const id=Number(btn.dataset.id), stock=Number(btn.dataset.stock), old=cart.get(id);
    if(stock<1){alert('This product is out of stock.');return;}
    if(old){if(old.qty>=stock){alert('No more stock available.');return;}old.qty++;}
    else cart.set(id,{id,code:btn.dataset.code,name:btn.dataset.name,price:Number(btn.dataset.price),stock,qty:1});
    renderCart();
  }));
  if(payment) payment.addEventListener('input',renderCart);
  if(rows) renderCart();
  const lookupButton=document.getElementById('lookupButton');
  const lookupCode=document.getElementById('lookupCode');
  const findProduct=()=>{
    if(!lookupCode) return;
    const code=lookupCode.value.trim();
    const item=window.productLookup?.[code];const result=document.getElementById('lookupResult');
    if(!result) return;
    result.textContent=item?`${item.name} — stock ${item.stock_quantity} — reorder at ${item.reorder_level}`:'No product found for that code.';
  };
  if(lookupButton) lookupButton.addEventListener('click',findProduct);
  if(lookupCode) lookupCode.addEventListener('keydown',event=>{
    if(event.key==='Enter'){event.preventDefault();findProduct();}
  });
});
