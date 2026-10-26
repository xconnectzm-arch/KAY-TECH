(function(){
  function q(sel, ctx){ return (ctx||document).querySelector(sel); }
  function has(el){ return !!el; }
  var nav = q('nav') || q('.navbar') || document.body;
  var collapse = q('.navbar-collapse') || q('#navbarSupportedContent') || q('.mobile-menu') || (nav ? q('ul', nav) : null);
  var toggler = q('.navbar-toggler') || q('[data-bs-toggle="collapse"]') || q('[data-toggle="collapse"]') || q('.nav-toggle') || q('.menu-toggle') || q('#menu-toggle');
  if (!has(toggler) && has(nav) && has(collapse)) {
    toggler = document.createElement('button');
    toggler.className = 'navbar-toggler auto-created';
    toggler.setAttribute('aria-controls', 'main-nav');
    toggler.setAttribute('aria-expanded', 'false');
    toggler.setAttribute('aria-label', 'Toggle navigation');
    toggler.innerHTML = '<span class="navbar-toggler-icon"></span>';
    nav.insertBefore(toggler, nav.firstChild);
  }
  if (has(collapse) && !collapse.id) collapse.id = 'main-nav';
  if (has(toggler) && collapse) toggler.setAttribute('aria-controls', collapse.id);

  function openMenu(){ if (!collapse) return; collapse.classList.add('show','is-open'); document.body.classList.add('nav-open'); if (toggler) toggler.setAttribute('aria-expanded','true'); }
  function closeMenu(){ if (!collapse) return; collapse.classList.remove('show','is-open'); document.body.classList.remove('nav-open'); if (toggler) toggler.setAttribute('aria-expanded','false'); }
  function toggleMenu(e){ if (e) e.preventDefault(); if (!collapse) return; (collapse.classList.contains('show')||collapse.classList.contains('is-open')?closeMenu:openMenu)(); }

  closeMenu();
  if (toggler) toggler.addEventListener('click', toggleMenu, false);
  if (collapse) collapse.addEventListener('click', function(e){ var t=e.target; if (t && (t.matches('a,.nav-link,.dropdown-item') || (t.closest && t.closest('a,.nav-link,.dropdown-item')))) closeMenu(); }, false);
  document.addEventListener('click', function(e){ if (!collapse) return; var within=(toggler&&(toggler===e.target||toggler.contains(e.target)))||(collapse===e.target||collapse.contains(e.target)); if(!within) closeMenu(); }, false);
  document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeMenu(); }, false);
  function handleResize(){ var w=window.innerWidth||document.documentElement.clientWidth; if (w>992){ collapse&&collapse.classList.remove('is-open'); } else { closeMenu(); } }
  window.addEventListener('resize', handleResize, false); handleResize();

  var brand = q('.navbar-brand') || (nav ? q('.logo', nav) : null);
  if (brand){ brand.style.display='block'; brand.style.marginLeft='auto'; brand.style.marginRight='auto'; brand.style.textAlign='center'; }
})();
