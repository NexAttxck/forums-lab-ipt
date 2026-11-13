// optional: collapse and expand replies. The buttons only exist once this script runs.
document.querySelectorAll('[data-replies]').forEach(function (c) {
  var box = document.getElementById(c.dataset.replies);
  var b = document.createElement('button');
  b.type = 'button';
  b.textContent = 'Hide replies';
  b.setAttribute('aria-controls', box.id);
  b.setAttribute('aria-expanded', 'true');
  b.addEventListener('click', function () {
    var open = b.getAttribute('aria-expanded') === 'true';
    box.hidden = open;
    b.setAttribute('aria-expanded', String(!open));
    b.textContent = open ? 'Show replies' : 'Hide replies';
  });
  c.insertBefore(b, box);
});
