/* Browser entrypoint; PHP controllers remain the authoritative backend. */
(async () => {
  const page = location.pathname.split('/').pop().replace(/\.html$/, '');
  const endpoint = page + '.php' + location.search;
  try {
    const response = await fetch(endpoint, {credentials: 'same-origin', redirect: 'follow',
      headers: {'Accept': 'text/html'}});
    if (new URL(response.url).pathname.endsWith('.html') &&
        new URL(response.url).pathname !== location.pathname) {
      location.replace(response.url); return;
    }
    const html = await response.text();
    if (!html.toLowerCase().includes('<!doctype html') && !html.toLowerCase().includes('<html')) {
      throw new Error('Resposta inesperada do servidor (HTTP ' + response.status + ')');
    }
    document.open(); document.write(html); document.close();
    // Original forms submit to their original PHP controller. Intercept ordinary
    // navigation POSTs to preserve the public .html URL and flash messages.
    document.addEventListener('submit', async event => {
      const form = event.target;
      if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'post') return;
      // admin.js handles AJAX forms itself (and may preventDefault).
      if (event.defaultPrevented) return;
      event.preventDefault();
      try {
        const url = form.getAttribute('action') || page + '.php';
        const r = await fetch(url, {method:'POST',body:new FormData(form),credentials:'same-origin'});
        const target = new URL(r.url);
        if (target.pathname.endsWith('.html') && target.pathname !== location.pathname) {
          location.assign(target.href); return;
        }
        const body = await r.text();
        if (body.toLowerCase().includes('<html')) {
          document.open();document.write(body);document.close();
        } else {
          location.reload();
        }
      } catch (e) { alert('Não foi possível concluir a operação: ' + e.message); }
    }, false);
  } catch (error) {
    document.body.textContent = 'Não foi possível carregar o painel: ' + error.message;
  }
})();
