(function () {
    'use strict';
    const host=document.getElementById('tariffComponents'); if(!host)return;
    const add=document.getElementById('addTariffComponent'),status=document.getElementById('tariffComponentStatus');
    let next=Math.max(...Array.from(host.children).map(row=>Number(row.dataset.index)))+1;
    function update(){add.disabled=host.children.length>=30;host.querySelectorAll('[data-remove-component]').forEach(b=>{b.disabled=host.children.length<=1;});}
    add.addEventListener('click',()=>{
        if(host.children.length>=30)return;
        const row=host.firstElementChild.cloneNode(true),i=next++;row.dataset.index=i;row.querySelector('legend').textContent='Komponen '+(i+1);
        row.querySelectorAll('.field-error').forEach(e=>e.remove());
        row.querySelectorAll('input,select').forEach(field=>{
            const old=field.id;field.id=old.replace(/\d+$/,String(i));
            row.querySelectorAll('label').forEach(label=>{if(label.htmlFor===old)label.htmlFor=field.id;});
            field.name=field.name.replace(/components\[\d+\]/,'components['+i+']');field.removeAttribute('aria-invalid');field.removeAttribute('aria-describedby');
            field.value=field.name.endsWith('[label]')?'':field.name.endsWith('[basis]')?'fixed':field.name.endsWith('[quantity]')?'1':'0';
        });
        host.append(row);update();row.querySelector('input').focus();status.textContent='Komponen ditambahkan.';
    });
    host.addEventListener('click',e=>{const b=e.target.closest('[data-remove-component]');if(!b||b.disabled)return;b.closest('fieldset').remove();update();add.focus();status.textContent='Komponen dihapus.';});update();
})();
