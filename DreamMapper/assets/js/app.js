document.addEventListener('DOMContentLoaded',()=>{
    const form=document.getElementById('dreamForm');
    const status=document.getElementById('processStatus');
    form.addEventListener('submit',async e=>{
        e.preventDefault();
        status.textContent='Analyzing your dream and building the roadmap...';
        const r=await fetch('process_dream.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({
            prompt:document.getElementById('dreamPrompt').value,
            target_date:document.getElementById('targetDate').value
        })});
        const d=await r.json();
        status.textContent=d.message || 'Done';
        if(d.success) setTimeout(()=>location.reload(),500);
    });

    const todoForm=document.getElementById('todoForm');
    todoForm.addEventListener('submit',async e=>{
        e.preventDefault();
        const r=await fetch('api/add_todo.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({
            title:document.getElementById('newTodo').value,
            dream_id:window.currentDreamId || null
        })});
        const d=await r.json();
        if(!d.success && !window.currentDreamId){
            alert('Create a dream first, then add manual tasks.');
            return;
        }
        document.getElementById('newTodo').value='';
        loadDream();
    });

    const btn=document.getElementById('notificationBtn');
    const panel=document.getElementById('notificationPanel');
    btn.addEventListener('click',()=>panel.classList.toggle('open'));

    document.getElementById('themeToggle').addEventListener('click',()=>{
        document.body.classList.toggle('light');
        localStorage.setItem('dm-theme',document.body.classList.contains('light')?'light':'dark');
    });
    if(localStorage.getItem('dm-theme')==='light')document.body.classList.add('light');
});
