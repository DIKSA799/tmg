
(()=>{
  const body=document.body;
  const themeButton=document.getElementById('themeToggle');
  const metaTheme=document.querySelector('meta[name="theme-color"]');
  const saved=localStorage.getItem('tmg-theme');
  const applyTheme=(theme)=>{
    body.dataset.theme=theme;
    localStorage.setItem('tmg-theme',theme);
    const isLight=theme==='light';
    themeButton.setAttribute('aria-label',isLight?'Switch to dark mode':'Switch to light mode');
    metaTheme?.setAttribute('content',isLight?'#f5f7fa':'#07090f');
  };
  applyTheme(saved==='light'?'light':'dark');
  themeButton.addEventListener('click',()=>applyTheme(body.dataset.theme==='dark'?'light':'dark'));

  document.querySelectorAll('[data-modal]').forEach(btn=>btn.addEventListener('click',()=>document.getElementById(btn.dataset.modal)?.showModal()));
  document.querySelectorAll('.modal-close').forEach(btn=>btn.addEventListener('click',()=>btn.closest('dialog')?.close()));
  document.querySelectorAll('dialog').forEach(d=>d.addEventListener('click',e=>{if(e.target===d)d.close()}));

  const pointerFine=matchMedia('(hover:hover) and (pointer:fine)').matches;
  if(pointerFine && !matchMedia('(prefers-reduced-motion: reduce)').matches){
    const logoWrap=document.getElementById('heroLogos');
    const logoImgs=[...document.querySelectorAll('.logo-card img')];
    logoWrap?.addEventListener('pointermove',e=>{
      const r=logoWrap.getBoundingClientRect();const x=(e.clientX-r.left)/r.width-.5;const y=(e.clientY-r.top)/r.height-.5;
      logoImgs.forEach((img,i)=>img.style.transform=`translate3d(${x*(i?10:-10)}px,${y*8}px,0) rotateY(${x*(i?6:-6)}deg) rotateX(${-y*5}deg)`);
    });
    logoWrap?.addEventListener('pointerleave',()=>logoImgs.forEach(img=>img.style.transform=''));
    const cv=document.getElementById('candidateVisual'),photo=document.querySelector('.portrait-circle img');
    cv?.addEventListener('pointermove',e=>{const r=cv.getBoundingClientRect();const x=(e.clientX-r.left)/r.width-.5;const y=(e.clientY-r.top)/r.height-.5;photo.style.transform=`scale(1.18) translate3d(${x*8}px,${y*8}px,0)`});
    cv?.addEventListener('pointerleave',()=>photo.style.transform='scale(1.18)');
  }

  const canvas=document.getElementById('particles');
  if(canvas && !matchMedia('(prefers-reduced-motion: reduce)').matches){
    const ctx=canvas.getContext('2d');let points=[],raf;const dpr=Math.min(2,devicePixelRatio||1);
    const resize=()=>{canvas.width=innerWidth*dpr;canvas.height=innerHeight*dpr;canvas.style.width=innerWidth+'px';canvas.style.height=innerHeight+'px';ctx.setTransform(dpr,0,0,dpr,0,0);const n=innerWidth<700?24:58;points=Array.from({length:n},()=>({x:Math.random()*innerWidth,y:Math.random()*innerHeight,vx:(Math.random()-.5)*.22,vy:(Math.random()-.5)*.22,r:Math.random()*2.2+.8,c:Math.random()>.65?'0,135,81':'184,2,1'}));};
    const draw=()=>{ctx.clearRect(0,0,innerWidth,innerHeight);for(const p of points){p.x+=p.vx;p.y+=p.vy;if(p.x<-5)p.x=innerWidth+5;if(p.x>innerWidth+5)p.x=-5;if(p.y<-5)p.y=innerHeight+5;if(p.y>innerHeight+5)p.y=-5;ctx.beginPath();ctx.fillStyle=`rgba(${p.c},${body.dataset.theme==='dark'?.22:.13})`;ctx.arc(p.x,p.y,p.r,0,Math.PI*2);ctx.fill();}raf=requestAnimationFrame(draw)};
    addEventListener('resize',resize,{passive:true});resize();draw();
    document.addEventListener('visibilitychange',()=>{if(document.hidden)cancelAnimationFrame(raf);else draw()});
  }
})();
