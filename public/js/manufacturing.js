(function () {
  'use strict';

  var root = document.querySelector('[data-partners-carousel]');
  if (!root) return;

  var viewport = root.querySelector('[data-carousel-viewport]');
  var previous = root.querySelector('[data-carousel-previous]');
  var next = root.querySelector('[data-carousel-next]');
  if (!viewport || !previous || !next) return;

  function updateControls() {
    var maxScroll = Math.max(0, viewport.scrollWidth - viewport.clientWidth);
    var hasOverflow = maxScroll > 4;
    previous.hidden = !hasOverflow;
    next.hidden = !hasOverflow;
    previous.disabled = !hasOverflow || viewport.scrollLeft <= 4;
    next.disabled = !hasOverflow || viewport.scrollLeft >= maxScroll - 4;
  }

  function move(direction) {
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    viewport.scrollBy({
      left: direction * Math.max(240, Math.round(viewport.clientWidth * 0.82)),
      behavior: reduceMotion ? 'auto' : 'smooth'
    });
  }

  previous.addEventListener('click', function () { move(-1); });
  next.addEventListener('click', function () { move(1); });
  viewport.addEventListener('scroll', updateControls, { passive: true });
  window.addEventListener('resize', updateControls, { passive: true });

  if ('ResizeObserver' in window) {
    new ResizeObserver(updateControls).observe(viewport);
  }

  updateControls();
})();
