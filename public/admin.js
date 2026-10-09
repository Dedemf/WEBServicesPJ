const NEWS_KEY='csjsm_news_prototype_v1';
const NEWS_DB='csjsm_news_store_v1';
const USERS_KEY='csjsm_local_users_v1';
const LEGACY_AUTH_KEY='csjsm_local_editor_v1';
const SESSION_KEY='csjsm_editor_session_v1';
let editingPhotos=[];
let primaryPhotoIndex=0;
let compressionPromise=null;
let currentUser=null;
let newsDatabasePromise;
let isSetup=false;
let API_MODE=false;
let CSRF='';
async function api(path,options={}){
  const init={credentials:'same-origin',headers:{},...options};
  if(CSRF&&(init.method||'GET').toUpperCase()!=='GET')init.headers['X-CSRF-Token']=CSRF;
  const res=await fetch(path,init);
  const data=await res.json().catch(()=>null);
  if(!res.ok||!data||data.ok===false){const err=Error((data&&data.error)||`Error ${res.status}`);err.status=res.status;err.data=data;throw err}
  return data;
}
function setBackendStatus(mode){
  const el=document.querySelector('#backend-status');
  if(!el)return;
  el.classList.remove('checking','online','local');
  el.classList.add(mode);
  el.innerHTML=mode==='online'
    ?'<span aria-hidden="true"></span> Conectado a la base de datos'
    :mode==='local'
    ?'<span aria-hidden="true"></span> Modo local: abre con http://localhost/servicios-judiciales/admin.html para usar la base de datos'
    :'<span aria-hidden="true"></span> Verificando conexión…';
}
function setLoginMode(){isSetup=false;const t=document.querySelector('#auth-title');if(t)t.textContent='Panel de noticias';const u=document.querySelector('#username');if(u){u.value='';u.readOnly=false}}
async function detectBackend(){
  try{
    const r=await api('api/auth.php?action=status');
    API_MODE=true;CSRF=(await api('api/csrf.php')).csrf||'';
    setBackendStatus('online');
    if(isSetup)setLoginMode();
    return r.user||null;
  }catch{API_MODE=false;setBackendStatus('local');return null}
}
function openFeaturedModal(destacadas,onResolve){
  const old=document.querySelector('#featured-modal');
  if(old)old.remove();
  const box=document.createElement('div');
  box.id='featured-modal';box.className='modal-overlay';box.setAttribute('role','dialog');box.setAttribute('aria-label','Límite de destacadas');
  box.innerHTML=`<div class="modal-card" role="document">
    <h3>Ya hay 3 destacadas en portada</h3>
    <p>Para destacar la nueva noticia, desactiva una de estas o publícala sin destacar.</p>
    <div class="modal-list">${destacadas.map((d,i)=>`<label><input type="radio" name="replace-featured" value="${escapeHtml(d.id)}" ${i===0?'checked':''}><span><b>${escapeHtml(d.title)}</b><small>${escapeHtml((d.published_at||'').slice(0,10))}</small></span></label>`).join('')}</div>
    <div class="modal-actions">
      <button type="button" class="button button-outline-dark" data-m="cancel">Cancelar</button>
      <button type="button" class="button button-outline-dark" data-m="plain">Publicar sin destacar</button>
      <button type="button" class="button button-red" data-m="replace">Reemplazar y publicar</button>
    </div></div>`;
  document.body.appendChild(box);
  document.body.style.overflow='hidden';
  function close(){box.remove();document.body.style.overflow=''}
  box.addEventListener('click',e=>{if(e.target===box){close();onResolve('cancel')}});
  document.addEventListener('keydown',function esc(e){if(e.key==='Escape'){close();onResolve('cancel');document.removeEventListener('keydown',esc)}});
  box.querySelector('[data-m="cancel"]').addEventListener('click',()=>{close();onResolve('cancel')});
  box.querySelector('[data-m="plain"]').addEventListener('click',()=>{close();onResolve('plain')});
  box.querySelector('[data-m="replace"]').addEventListener('click',()=>{
    const sel=box.querySelector('input[name="replace-featured"]:checked');
    close();onResolve('replace',sel?sel.value:null);
  });
  box.querySelector('[data-m="replace"]').focus();
}
function buildNewsFormData(featured){
  const fd=new FormData();
  const editId=document.querySelector('#edit-id').value;
  if(editId)fd.append('id',editId);
  fd.append('action','guardar');
  fd.append('title',document.querySelector('#news-title').value.trim());
  fd.append('summary',document.querySelector('#news-summary').value.trim());
  fd.append('body',document.querySelector('#news-body').value.trim());
  fd.append('published_at',document.querySelector('#news-date').value);
  fd.append('category',document.querySelector('#news-category').value);
  fd.append('featured',featured?'1':'0');
  fd.append('video',document.querySelector('#news-video').value.trim());
  fd.append('status','activo');
  fd.append('primary_photo_index',String(Math.max(0,primaryPhotoIndex)));
  fd.append('keep_photos',JSON.stringify(editingPhotos.filter(p=>p.serverIndex>=0&&!p.file).map(p=>p.serverIndex)));
  editingPhotos.filter(p=>p.file).slice(0,6).forEach(p=>fd.append('photos[]',p.file));
  return fd;
}
async function submitNewsToApi(featured){
  try{
    await api('api/admin/noticias.php',{method:'POST',body:buildNewsFormData(featured)});
    clearEditor();renderManaged();
  }catch(e){
    if(e.status===409&&e.data&&Array.isArray(e.data.destacadas)&&featured){
      openFeaturedModal(e.data.destacadas,async(choice,replaceId)=>{
        if(choice==='cancel')return;
        if(choice==='plain'){await submitNewsToApi(false);return}
        try{
          const fd=new FormData();fd.append('action','destacar');fd.append('id',replaceId);fd.append('featured','0');
          await api('api/admin/noticias.php',{method:'POST',body:fd});
          await submitNewsToApi(true);
        }catch(err){const m=document.querySelector('#form-error');m.textContent=err.message;m.hidden=false}
      });
      return;
    }
    throw e;
  }
}
  function parsePhotos(value){
if(!value)return [];
  if(Array.isArray(value))return value.map(p=>typeof p==='string'?{src:p}:p);
  try{const arr=JSON.parse(value);return Array.isArray(arr)?arr:[]}catch{return []}
}
const defaultStory={id:'official-1415894',title:'Corte de San Martín impulsa jornada educativa sobre la ruta de la flagrancia delictiva',summary:'Estudiantes conocieron de manera vivencial el funcionamiento del sistema de justicia y reflexionaron sobre la prevención del delito.',body:'La Corte Superior de Justicia de San Martín desarrolló una jornada educativa dirigida a estudiantes de secundaria, como parte de las acciones orientadas a fortalecer la cultura de legalidad y prevenir el delito entre adolescentes.\n\nLa actividad acercó a las y los estudiantes al funcionamiento del sistema de justicia y promovió la reflexión sobre sus derechos y responsabilidades.',date:'2026-07-03',category:'Servicios judiciales',image:'assets/noticia-oficial-2026.jpg',external:'https://www.gob.pe/institucion/csjsanmartin/noticias/1415894-corte-de-san-martin-impulsa-jornada-educativa-sobre-la-ruta-de-la-flagrancia-delictiva-para-prevenir-el-delito-en-adolescentes',featured:true,official:true};
function openNewsDatabase(){if(!('indexedDB'in window))return Promise.reject(Error('IndexedDB no está disponible'));if(!newsDatabasePromise)newsDatabasePromise=new Promise((resolve,reject)=>{const request=indexedDB.open(NEWS_DB,1);request.onupgradeneeded=()=>{if(!request.result.objectStoreNames.contains('news'))request.result.createObjectStore('news',{keyPath:'id'})};request.onsuccess=()=>resolve(request.result);request.onerror=()=>reject(request.error)}).catch(error=>{newsDatabasePromise=null;throw error});return newsDatabasePromise}
async function savedNews(){try{const db=await openNewsDatabase();const rows=await new Promise((resolve,reject)=>{const transaction=db.transaction('news','readonly');const request=transaction.objectStore('news').getAll();request.onsuccess=()=>resolve(request.result||[]);request.onerror=()=>reject(request.error);});if(rows.length)return rows;const old=JSON.parse(localStorage.getItem(NEWS_KEY)||'[]');if(Array.isArray(old)&&old.length){for(const news of old)await writeNews(news);localStorage.removeItem(NEWS_KEY);return old}return []}catch{try{const old=JSON.parse(localStorage.getItem(NEWS_KEY)||'[]');return Array.isArray(old)?old:[]}catch{return []}}}
async function writeNews(news){const db=await openNewsDatabase();return new Promise((resolve,reject)=>{const transaction=db.transaction('news','readwrite');transaction.objectStore('news').put(news);transaction.oncomplete=()=>resolve();transaction.onerror=()=>reject(transaction.error);transaction.onabort=()=>reject(transaction.error)})}
async function deleteNews(id){const db=await openNewsDatabase();return new Promise((resolve,reject)=>{const transaction=db.transaction('news','readwrite');transaction.objectStore('news').delete(id);transaction.oncomplete=()=>resolve();transaction.onerror=()=>reject(transaction.error);transaction.onabort=()=>reject(transaction.error)})}
async function allNews(){return [defaultStory,...(await savedNews()).filter(n=>n.id!==defaultStory.id)]}
function escapeHtml(s=''){return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
function oldAccount(){try{return JSON.parse(localStorage.getItem(LEGACY_AUTH_KEY))}catch{return null}}
function saveUsers(users){localStorage.setItem(USERS_KEY,JSON.stringify(users))}
function getUsers(){try{const list=JSON.parse(localStorage.getItem(USERS_KEY));if(Array.isArray(list))return list}catch{}const legacy=oldAccount();if(legacy){const admin={id:'admin-main',name:'Administrador principal',username:'admin',role:'admin',active:true,salt:legacy.salt,hash:legacy.hash,algorithm:'legacy-sha256'};saveUsers([admin]);return [admin]}return []}
function randomSalt(){return Array.from(crypto.getRandomValues(new Uint8Array(16)),b=>b.toString(16).padStart(2,'0')).join('')}
async function hashPassword(password,salt){const material=await crypto.subtle.importKey('raw',new TextEncoder().encode(password),'PBKDF2',false,['deriveBits']);const saltBytes=new Uint8Array(salt.match(/.{2}/g).map(byte=>parseInt(byte,16)));const bits=await crypto.subtle.deriveBits({name:'PBKDF2',salt:saltBytes,iterations:150000,hash:'SHA-256'},material,256);return Array.from(new Uint8Array(bits),b=>b.toString(16).padStart(2,'0')).join('')}
async function legacyHash(password,salt){const bytes=await crypto.subtle.digest('SHA-256',new TextEncoder().encode(`${salt}:${password}`));return Array.from(new Uint8Array(bytes),b=>b.toString(16).padStart(2,'0')).join('')}
function showError(message){const error=document.querySelector('#auth-error');error.textContent=message;error.hidden=false}
function showDashboard(user){currentUser=user;document.querySelector('#auth-view').hidden=true;document.querySelector('#dashboard').hidden=false;document.querySelector('#signed-user').textContent=`${user.name} · ${user.role==='admin'?'Administrador':'Editor'}`;document.querySelector('#tab-users').hidden=user.role!=='admin';document.querySelector('#tab-news').click();renderManaged();if(user.role==='admin')renderUsers()}
let apiNewsCache=[];
async function renderManaged(){
let entries;
if(API_MODE){
  try{
    const r=await api('api/admin/noticias.php?action=listar');
    apiNewsCache=r.data.map(n=>({id:n.id,title:n.title,summary:n.summary,body:n.body,date:(n.published_at||'').slice(0,10),category:n.category,featured:!!n.featured,photos:parsePhotos(n.photos),primaryPhotoIndex:n.primary_photo_index||0,image:n.image,official:!!n.official,status:n.status,video_url:n.video_url||''}));
  }catch{apiNewsCache=[]}
  entries=apiNewsCache.filter(n=>!n.official);
}else entries=(await allNews()).filter(n=>!n.official);
document.querySelector('#published-count').textContent=entries.length;const box=document.querySelector('#managed-news');box.innerHTML=entries.length?entries.map(n=>`<article class="managed-item"><div>${n.image?`<img src="${escapeHtml(n.image)}" alt="">`:'<span class="managed-thumb">CSJ</span>'}</div><div><h3>${escapeHtml(n.title)}</h3><small>${escapeHtml(n.date)} · ${escapeHtml(n.category)}</small><div class="managed-actions"><button type="button" data-edit="${escapeHtml(n.id)}">Editar</button><button type="button" data-delete="${escapeHtml(n.id)}">Eliminar</button></div></div></article>`).join(''):'<p class="manage-hint">Aún no hay noticias publicadas.</p>';box.querySelectorAll('[data-edit]').forEach(button=>button.addEventListener('click',()=>editNews(button.dataset.edit)));box.querySelectorAll('[data-delete]').forEach(button=>button.addEventListener('click',async()=>{if(!confirm('¿Archivar esta noticia? Dejará de verse en el portal.'))return;try{if(API_MODE){const fd=new FormData();fd.append('action','archivar');fd.append('id',button.dataset.delete);await api('api/admin/noticias.php',{method:'POST',body:fd})}else await deleteNews(button.dataset.delete);renderManaged()}catch(e){alert(e.message)}}))}
async function renderUsers(){if(!currentUser||currentUser.role!=='admin')return;
let list;
if(API_MODE){
  try{
    const r=await api('api/admin/usuarios.php?action=listar');
    list=r.data.map(u=>({id:u.id,name:u.display_name,username:u.username,role:u.role,active:u.status==='activo',remote:true}));
  }catch{list=[]}
}else list=getUsers();
document.querySelector('#user-count').textContent=list.length;const box=document.querySelector('#users-list');box.innerHTML=list.map(user=>`<article class="managed-item user-item"><div class="user-avatar">${escapeHtml((user.name||user.username).slice(0,1).toUpperCase())}</div><div><h3>${escapeHtml(user.name||user.username)}</h3><small>@${escapeHtml(user.username)} · ${user.role==='admin'?'Administrador':'Editor de contenidos'} · ${user.active?'Activo':'Desactivado'}</small><div class="managed-actions">${(user.id==='admin-main'||(API_MODE&&user.username==='admin'))&&list.length<=1&&!API_MODE?'<span class="protected-user">Cuenta principal</span>':`<button type="button" data-toggle-user="${escapeHtml(String(user.id))}">${user.active?'Desactivar':'Activar'}</button><button type="button" data-delete-user="${escapeHtml(String(user.id))}">Eliminar</button>`}</div></div></article>`).join('');box.querySelectorAll('[data-toggle-user]').forEach(button=>button.addEventListener('click',async()=>{try{if(API_MODE){const fd=new FormData();fd.append('action','actualizar');fd.append('id',button.dataset.toggleUser);const target=list.find(u=>String(u.id)===button.dataset.toggleUser);fd.append('name',target.name);fd.append('role',target.role);fd.append('status',target.active?'inactivo':'activo');await api('api/admin/usuarios.php',{method:'POST',body:fd})}else{const users=getUsers();const user=users.find(item=>item.id===button.dataset.toggleUser);if(!user)return;user.active=!user.active;saveUsers(users)}renderUsers()}catch(e){alert(e.message)}}));box.querySelectorAll('[data-delete-user]').forEach(button=>button.addEventListener('click',async()=>{if(API_MODE){if(!confirm('¿Desactivar este acceso? El usuario perderá el acceso al panel.'))return;try{const fd=new FormData();fd.append('action','desactivar');fd.append('id',button.dataset.deleteUser);await api('api/admin/usuarios.php',{method:'POST',body:fd});renderUsers()}catch(e){alert(e.message)}return}if(!confirm('¿Eliminar este acceso local? El usuario perderá acceso en este navegador.'))return;saveUsers(getUsers().filter(user=>user.id!==button.dataset.deleteUser));renderUsers()}))}
function renderPhotoPreview(){const panel=document.querySelector('#image-preview');const list=document.querySelector('#photo-list');panel.hidden=editingPhotos.length===0;list.innerHTML=editingPhotos.map((photo,index)=>`<div class="photo-preview-card ${index===primaryPhotoIndex?'is-primary':''}"><img src="${photo.src}" alt="Vista previa de la foto ${index+1}"><label><input type="radio" name="primary-photo" value="${index}" ${index===primaryPhotoIndex?'checked':''}> Principal</label><small>${formatBytes(photo.processedBytes||estimateDataUrlBytes(photo.src))}</small><button type="button" data-remove-photo="${index}" aria-label="Quitar foto ${index+1}">×</button></div>`).join('');list.querySelectorAll('[name="primary-photo"]').forEach(radio=>radio.addEventListener('change',()=>{primaryPhotoIndex=Number(radio.value);renderPhotoPreview()}));list.querySelectorAll('[data-remove-photo]').forEach(button=>button.addEventListener('click',()=>{const index=Number(button.dataset.removePhoto);editingPhotos.splice(index,1);if(index===primaryPhotoIndex)primaryPhotoIndex=0;else if(index<primaryPhotoIndex)primaryPhotoIndex--;renderPhotoPreview();updatePhotoSummary()}))}
function updatePhotoSummary(){const info=document.querySelector('#image-optimization-info');if(!editingPhotos.length){info.textContent='';return}const original=editingPhotos.reduce((sum,photo)=>sum+(photo.originalBytes||photo.processedBytes||estimateDataUrlBytes(photo.src)),0);const processed=editingPhotos.reduce((sum,photo)=>sum+(photo.processedBytes||estimateDataUrlBytes(photo.src)),0);info.textContent=`${editingPhotos.length} ${editingPhotos.length===1?'foto procesada':'fotos procesadas'} · ${formatBytes(original)} → ${formatBytes(processed)} · marca una como principal`}
function clearEditor(){document.querySelector('#news-form').reset();document.querySelector('#edit-id').value='';document.querySelector('#form-title').textContent='Nueva noticia';document.querySelector('#image-optimization-info').textContent='';document.querySelector('#news-date').value=new Date().toISOString().slice(0,10);document.querySelector('#form-error').hidden=true;editingPhotos=[];primaryPhotoIndex=0;compressionPromise=null;renderPhotoPreview()}
async function editNews(id){const news=API_MODE?apiNewsCache.find(n=>n.id===id):(await savedNews()).find(n=>n.id===id);if(!news)return;document.querySelector('#edit-id').value=news.id;document.querySelector('#news-title').value=news.title;document.querySelector('#news-summary').value=news.summary;document.querySelector('#news-body').value=news.body;document.querySelector('#news-date').value=news.date;document.querySelector('#news-category').value=news.category;document.querySelector('#news-featured').checked=!!news.featured;document.querySelector('#news-video').value=news.video_url||news.video||'';editingPhotos=Array.isArray(news.photos)&&news.photos.length?news.photos.map((photo,index)=>typeof photo==='string'?{src:photo,processedBytes:estimateDataUrlBytes(photo),serverIndex:index}:{src:photo.src,processedBytes:estimateDataUrlBytes(photo.src||''),serverIndex:index}):news.image?[{src:news.image,processedBytes:estimateDataUrlBytes(news.image),serverIndex:-1}]:[];primaryPhotoIndex=Math.min(news.primaryPhotoIndex||0,Math.max(0,editingPhotos.length-1));compressionPromise=null;renderPhotoPreview();updatePhotoSummary();document.querySelector('#form-title').textContent='Editar noticia';document.querySelector('#news-form').scrollIntoView({behavior:'smooth',block:'start'})}
function estimateDataUrlBytes(dataUrl){const payload=dataUrl.split(',')[1]||'';return Math.round(payload.length*.75)}
function formatBytes(bytes){if(bytes<1024)return `${bytes} B`;if(bytes<1024*1024)return `${(bytes/1024).toFixed(0)} KB`;return `${(bytes/(1024*1024)).toFixed(1)} MB`}
function optimizeImage(file){return new Promise((resolve,reject)=>{if(!file)return resolve(null);const reader=new FileReader();reader.onerror=()=>reject(Error(`No se pudo leer “${file.name}”.`));reader.onload=()=>{const image=new Image();image.onerror=()=>reject(Error(`No se pudo procesar “${file.name}”. El formato o las dimensiones podrían no ser compatibles con este navegador.`));image.onload=()=>{const scale=Math.min(1,1600/image.width,1200/image.height);const canvas=document.createElement('canvas');canvas.width=Math.max(1,Math.round(image.width*scale));canvas.height=Math.max(1,Math.round(image.height*scale));const context=canvas.getContext('2d');if(!context)return reject(Error('El navegador no pudo iniciar la compresión de imagen.'));context.drawImage(image,0,0,canvas.width,canvas.height);let output=canvas.toDataURL('image/webp',.76);if(!output.startsWith('data:image/webp')){context.fillStyle='#fff';context.fillRect(0,0,canvas.width,canvas.height);context.drawImage(image,0,0,canvas.width,canvas.height);output=canvas.toDataURL('image/jpeg',.76)}resolve({src:output,originalBytes:file.size,processedBytes:estimateDataUrlBytes(output),width:canvas.width,height:canvas.height,format:output.startsWith('data:image/webp')?'WebP':'JPEG',name:file.name})};image.src=reader.result};reader.readAsDataURL(file)})}
async function compressFiles(files){const results=[];const info=document.querySelector('#image-optimization-info');for(let index=0;index<files.length;index++){info.textContent=`Comprimiendo foto ${index+1} de ${files.length}…`;results.push(await optimizeImage(files[index]))}return results}
function clearUserForm(){document.querySelector('#user-form').reset();document.querySelector('#user-edit-id').value='';document.querySelector('#user-form-title').textContent='Crear usuario';document.querySelector('#save-user-button').innerHTML='Crear usuario <span>→</span>';document.querySelector('#user-error').hidden=true}

const authForm=document.querySelector('#auth-form');
authForm.addEventListener('submit',async event=>{event.preventDefault();const password=document.querySelector('#password').value;try{
if(API_MODE){
  const fd=new FormData();fd.append('action','login');
  fd.append('username',document.querySelector('#username').value.trim());
  fd.append('password',password);
  const r=await api('api/auth.php',{method:'POST',body:fd});
  CSRF=r.csrf||CSRF;
  sessionStorage.removeItem(SESSION_KEY);
  showDashboard({id:r.user.id,name:r.user.name,username:r.user.username,role:r.user.role});
  return;
}
const username=document.querySelector('#username').value.trim().toLocaleLowerCase('es');const user=getUsers().find(item=>item.username.toLocaleLowerCase('es')===username);if(!user||!user.active)return showError('Usuario o contraseña incorrectos, o cuenta desactivada.');const hash=user.algorithm==='legacy-sha256'?await legacyHash(password,user.salt):await hashPassword(password,user.salt);if(hash!==user.hash)return showError('Usuario o contraseña incorrectos.');sessionStorage.setItem(SESSION_KEY,JSON.stringify({id:user.id}));showDashboard(user)}catch(e){showError(e.message||'No se pudo validar el acceso. Prueba en un navegador actualizado.')}});
(async()=>{const remote=await detectBackend();if(remote){showDashboard({id:remote.id,name:remote.name,username:remote.username,role:remote.role});return}try{const session=JSON.parse(sessionStorage.getItem(SESSION_KEY)||'null');const user=session&&getUsers().find(item=>item.id===session.id&&item.active);if(user)showDashboard(user)}catch{}})();
document.querySelector('#logout-button').addEventListener('click',async()=>{if(API_MODE){try{const fd=new FormData();fd.append('action','logout');await api('api/auth.php',{method:'POST',body:fd})}catch{}}sessionStorage.removeItem(SESSION_KEY);location.reload()});
document.querySelector('#tab-news').addEventListener('click',()=>{document.querySelector('#tab-news').classList.add('active');document.querySelector('#tab-news').setAttribute('aria-selected','true');document.querySelector('#tab-users').classList.remove('active');document.querySelector('#tab-users').setAttribute('aria-selected','false');document.querySelector('#news-panel').hidden=false;document.querySelector('#users-panel').hidden=true});
document.querySelector('#tab-users').addEventListener('click',()=>{if(!currentUser||currentUser.role!=='admin')return;document.querySelector('#tab-users').classList.add('active');document.querySelector('#tab-users').setAttribute('aria-selected','true');document.querySelector('#tab-news').classList.remove('active');document.querySelector('#tab-news').setAttribute('aria-selected','false');document.querySelector('#users-panel').hidden=false;document.querySelector('#news-panel').hidden=true;renderUsers()});
document.querySelector('#news-image').addEventListener('change',async event=>{const files=Array.from(event.target.files||[]);event.target.value='';if(!files.length)return;document.querySelector('#image-preview').hidden=false;const preceding=compressionPromise;const task=(async()=>{if(preceding)await preceding.catch(()=>{});const added=(await compressFiles(files)).filter(Boolean).map((p,i)=>({...p,file:files[i],serverIndex:-1}));if(editingPhotos.length===0)primaryPhotoIndex=0;editingPhotos=[...editingPhotos,...added];renderPhotoPreview();updatePhotoSummary()})();compressionPromise=task;document.querySelector('#clear-form').disabled=true;document.querySelector('#remove-all-photos').disabled=true;try{await task}catch(error){const message=document.querySelector('#form-error');message.textContent=error.message;message.hidden=false}finally{if(compressionPromise===task){compressionPromise=null;document.querySelector('#clear-form').disabled=false;document.querySelector('#remove-all-photos').disabled=false}}});
document.querySelector('#remove-all-photos').addEventListener('click',()=>{editingPhotos=[];primaryPhotoIndex=0;compressionPromise=null;document.querySelector('#news-image').value='';renderPhotoPreview();updatePhotoSummary()});
document.querySelector('#clear-form').addEventListener('click',event=>{event.preventDefault();clearEditor()});
document.querySelector('#news-form').addEventListener('submit',async event=>{event.preventDefault();const button=event.currentTarget.querySelector('button[type="submit"]');button.disabled=true;const error=document.querySelector('#form-error');error.hidden=true;try{if(compressionPromise)await compressionPromise;
if(API_MODE){
  await submitNewsToApi(document.querySelector('#news-featured').checked);
  return;
}
const editId=document.querySelector('#edit-id').value;const photos=editingPhotos;const primaryPhoto=photos[primaryPhotoIndex]||photos[0]||null;const next={id:editId||`news-${Date.now()}`,title:document.querySelector('#news-title').value.trim(),summary:document.querySelector('#news-summary').value.trim(),body:document.querySelector('#news-body').value.trim(),date:document.querySelector('#news-date').value,category:document.querySelector('#news-category').value,photos,primaryPhotoIndex:Math.max(0,primaryPhoto?photos.indexOf(primaryPhoto):0),image:primaryPhoto?.src||'',featured:document.querySelector('#news-featured').checked};await writeNews(next);clearEditor();renderManaged()}catch(err){error.textContent=err.name==='QuotaExceededError'?'El navegador no tiene espacio suficiente para esta galería. Reduce la cantidad o resolución de fotos e inténtalo de nuevo.':err.message||'No se pudo guardar la noticia.';error.hidden=false}finally{button.disabled=false}});
document.querySelector('#user-form').addEventListener('submit',async event=>{event.preventDefault();const error=document.querySelector('#user-error');error.hidden=true;const name=document.querySelector('#user-name').value.trim();const username=document.querySelector('#new-username').value.trim().toLocaleLowerCase('es');const password=document.querySelector('#new-user-password').value;const role=document.querySelector('#user-role').value;if(!/^[a-z0-9._-]{3,40}$/.test(username)){error.textContent='Usa de 3 a 40 caracteres: letras, números, punto, guion o guion bajo.';error.hidden=false;return}if(password.length<10){error.textContent='La contraseña inicial debe tener al menos 10 caracteres.';error.hidden=false;return}
if(API_MODE){
  try{
    const fd=new FormData();fd.append('action','crear');
    fd.append('name',name);fd.append('username',username);fd.append('password',password);fd.append('role',role);
    await api('api/admin/usuarios.php',{method:'POST',body:fd});
    clearUserForm();renderUsers();
  }catch(e){error.textContent=e.message;error.hidden=false}
  return;
}
const users=getUsers();if(users.some(user=>user.username.toLocaleLowerCase('es')===username)){error.textContent='Ese nombre de usuario ya existe.';error.hidden=false;return}try{const salt=randomSalt();users.push({id:`user-${Date.now()}`,name,username,role,active:true,salt,hash:await hashPassword(password,salt),algorithm:'pbkdf2'});saveUsers(users);clearUserForm();renderUsers()}catch{error.textContent='No se pudo guardar el usuario en este navegador.';error.hidden=false}});
document.querySelector('#clear-user-form').addEventListener('click',event=>{event.preventDefault();clearUserForm()});
document.querySelector('#acc-save').addEventListener('click',async()=>{
  const err=document.querySelector('#acc-error'),ok=document.querySelector('#acc-ok');
  err.hidden=true;ok.hidden=true;
  const current=document.querySelector('#acc-current').value;
  const next=document.querySelector('#acc-new').value;
  if(next.length<10){err.textContent='La nueva clave debe tener al menos 10 caracteres.';err.hidden=false;return}
  try{
    if(API_MODE){
      const fd=new FormData();fd.append('action','password');fd.append('current',current);fd.append('new',next);
      await api('api/auth.php',{method:'POST',body:fd});
    }else{
      if(!currentUser)throw Error('Sin sesión.');
      const users=getUsers();const u=users.find(x=>x.id===currentUser.id);
      const hash=u.algorithm==='legacy-sha256'?await legacyHash(current,u.salt):await hashPassword(current,u.salt);
      if(hash!==u.hash)throw Error('La clave actual no es correcta.');
      u.salt=randomSalt();u.hash=await hashPassword(next,u.salt);u.algorithm='pbkdf2';saveUsers(users);
    }
    document.querySelector('#acc-current').value='';document.querySelector('#acc-new').value='';
    document.querySelector('#must-change')?.remove();ok.hidden=false;
  }catch(e){err.textContent=e.message;err.hidden=false}
});
document.querySelector('#news-date').value=new Date().toISOString().slice(0,10);
