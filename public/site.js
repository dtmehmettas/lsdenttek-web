(function () {
  // Menü (mobil)
  var menu = document.getElementById('menu'), burger = document.getElementById('burger');
  if (burger && menu) {
    burger.addEventListener('click', function () { var o = menu.classList.toggle('open'); burger.setAttribute('aria-expanded', o ? 'true' : 'false'); });
    menu.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', function () { menu.classList.remove('open'); }); });
  }
  // Tedaviler alt menüsü (dokunmatik / klavye)
  document.querySelectorAll('.sub').forEach(function (s) {
    var b = s.querySelector('.sub-btn'); if (!b) return;
    b.addEventListener('click', function (e) { e.stopPropagation(); var o = s.classList.toggle('open'); b.setAttribute('aria-expanded', o ? 'true' : 'false'); });
    document.addEventListener('click', function () { s.classList.remove('open'); b.setAttribute('aria-expanded', 'false'); });
  });
  // Dil menüsü
  document.querySelectorAll('.lang').forEach(function (w) {
    var b = w.querySelector('.lang-btn'); if (!b) return;
    b.addEventListener('click', function (e) { e.stopPropagation(); var o = w.classList.toggle('open'); b.setAttribute('aria-expanded', o ? 'true' : 'false'); });
    document.addEventListener('click', function () { w.classList.remove('open'); b.setAttribute('aria-expanded', 'false'); });
  });
  // Geçiş bandı: kapatılınca localStorage'da hatırlanır
  var banner = document.getElementById('banner'), bx = document.getElementById('bannerX');
  if (banner) {
    var closed = false; try { closed = localStorage.getItem('lsBanner') === '1'; } catch (e) {}
    if (!closed) banner.hidden = false;
    if (bx) bx.addEventListener('click', function () { banner.hidden = true; try { localStorage.setItem('lsBanner', '1'); } catch (e) {} });
  }
  // İletişim formu
  var f = document.getElementById('contactForm');
  if (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = f.querySelector('button[type=submit]'); btn.disabled = true; btn.textContent = btn.getAttribute('data-sending');
      fetch(f.action, { method: 'POST', body: new FormData(f) })
        .then(function (r) { return r.json(); })
        .then(function (j) { if (!j.ok) throw 0; f.reset(); document.getElementById('formOk').hidden = false; document.getElementById('formErr').hidden = true; })
        .catch(function () { document.getElementById('formErr').hidden = false; })
        .finally(function () { btn.disabled = false; btn.textContent = btn.getAttribute('data-label'); });
    });
  }
})();
