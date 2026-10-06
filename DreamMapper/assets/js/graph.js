let cy = null;
let currentDream = null;

async function loadDream() {
    const res = await fetch('api/get_dream.php');
    const data = await res.json();
    currentDream = data.dream;
    renderGraph(data.nodes || []);
    renderTodos(data.todos || []);
    if (currentDream) await loadAnalytics(currentDream.id);
}

function renderGraph(nodes) {
    const elements = [];
    nodes.forEach(n => {
        elements.push({data:{id:'n'+n.id,label:n.title,status:n.status,type:n.node_type}});
        if (n.parent_id) elements.push({data:{id:'e'+n.id,source:'n'+n.parent_id,target:'n'+n.id}});
    });

    if (cy) cy.destroy();
    cy = cytoscape({
        container: document.getElementById('cy'),
        elements,
        layout:{name:'breadthfirst', directed:true, spacingFactor:1.35, padding:30},
        style:[
            {selector:'node', style:{
                'label':'data(label)','text-wrap':'wrap','text-max-width':'120px',
                'background-color':'#6d5dfc','color':'#fff','font-size':'11px',
                'text-valign':'center','text-halign':'center','width':'70px','height':'70px',
                'border-width':2,'border-color':'#fff'
            }},
            {selector:'node[type="dream"]', style:{'background-color':'#111827','width':'95px','height':'95px','font-size':'12px'}},
            {selector:'node[type="backup"]', style:{'background-color':'#f59e0b','shape':'diamond'}},
            {selector:'node[status="completed"]', style:{'background-color':'#10b981'}},
            {selector:'node[status="in_progress"]', style:{'background-color':'#3b82f6'}},
            {selector:'edge', style:{'width':2,'line-color':'#cbd5e1','target-arrow-color':'#cbd5e1','target-arrow-shape':'triangle','curve-style':'bezier'}}
        ]
    });

    cy.on('tap','node', async evt => {
        const n = evt.target;
        const oldTitle = n.data('label');
        const title = prompt('Edit node title:', oldTitle);
        if (title === null) return;
        const status = prompt('Status: pending / in_progress / completed', n.data('status')) || n.data('status');
        const r = await fetch('api/update_node.php',{
            method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({id:n.id().substring(1),title,status})
        });
        const result=await r.json();
        if(result.success) loadDream();
    });
}

function renderTodos(todos) {
    const el=document.getElementById('todoList');
    if(!todos.length){el.innerHTML='<p class="muted">No tasks yet. Add one below.</p>';return;}
    el.innerHTML=todos.map(t=>`
        <label class="todo ${t.completed==1?'done':''}">
          <input type="checkbox" ${t.completed==1?'checked':''} onchange="toggleTodo(${t.id},this.checked)">
          <span>${escapeHtml(t.title)}<small>${t.due_date || 'No due date'}</small></span>
        </label>`).join('');
}
async function toggleTodo(id,completed){
    await fetch('api/update_todo.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,completed})});
    loadDream();
}
function escapeHtml(s){return String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
async function loadAnalytics(dreamId){
    const r=await fetch('analytics.php?dream_id='+dreamId); const d=await r.json();
    if(!d.success)return;
    document.getElementById('dreamScore').textContent=d.metrics.dream_score;
    document.getElementById('completionRate').textContent=d.metrics.completion_rate+'%';
    document.getElementById('successProbability').textContent=d.metrics.success_probability+'%';
    document.getElementById('streakDays').textContent=d.metrics.streak_days+' 🔥';
    document.getElementById('riskIndex').textContent=d.metrics.risk_index+'%';
    document.getElementById('riskBar').style.width=d.metrics.risk_index+'%';
    drawChart(d.trend,d.metrics);
}
let progressChart=null;
function drawChart(trend,m){
    const labels=trend.length?trend.map(x=>x.snapshot_date):['Today'];
    const values=trend.length?trend.map(x=>Number(x.completion_rate)):[m.completion_rate];
    const ctx=document.getElementById('progressChart');
    if(progressChart)progressChart.destroy();
    progressChart=new Chart(ctx,{type:'line',data:{labels,datasets:[{label:'Completion %',data:values,tension:.35,fill:true}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,max:100}}}});
}
document.addEventListener('DOMContentLoaded',()=>{
    loadDream();
    document.getElementById('refreshGraphBtn').onclick=loadDream;
    document.getElementById('addNodeBtn').onclick=async()=>{
        if(!currentDream){alert('Create a dream first.');return;}
        alert('For the prototype, add/edit nodes from the graph after mapping a dream. The node API can be extended for custom parent selection.');
    };
});
